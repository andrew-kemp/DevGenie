<?php
session_start();
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../../vendor/autoload.php');

// Fetch SSO config
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'sso_%'");
$settings = [];
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$conn->close();

if (empty($settings['sso_client_id']) || empty($settings['sso_tenant_id'])) {
    die("SSO not configured. Please contact your administrator.");
}

$oidc_url = !empty($settings['sso_metadata_url']) ?
    $settings['sso_metadata_url'] :
    "https://login.microsoftonline.com/{$settings['sso_tenant_id']}/v2.0/.well-known/openid-configuration";

$redirect_uri = !empty($settings['sso_redirect_uri']) ?
    $settings['sso_redirect_uri'] :
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}/entra_sso/callback.php";

$oidc = new Jumbojett\OpenIDConnectClient(
    $oidc_url,
    $settings['sso_client_id'],
    $settings['sso_client_secret'] ?? ''
);
$oidc->setRedirectURL($redirect_uri);

$scope = !empty($settings['sso_scopes']) ? $settings['sso_scopes'] : 'openid profile email';
$oidc->addScope($scope);

try {
    $oidc->authenticate();
    $claims = $oidc->getVerifiedClaims();

    $external_id = $claims->sub ?? null;
    $display_name = $claims->name ?? ($claims->given_name ?? '') . ' ' . ($claims->family_name ?? '');
    $dev_email = $claims->email ?? $claims->upn ?? $claims->preferred_username ?? null;

    if (!$dev_email || !$external_id) {
        die("Could not retrieve user details from Entra SSO. Authentication failed.");
    }

    // Provision user
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $stmt = $conn->prepare("SELECT id, is_admin FROM users WHERE dev_email=?");
    $stmt->bind_param("s", $dev_email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($user = $res->fetch_assoc()) {
        // Existing user: update name/external_id
        $uid = $user['id'];
        $is_admin = $user['is_admin'];
        $stmt2 = $conn->prepare("UPDATE users SET display_name=?, external_id=? WHERE id=?");
        $stmt2->bind_param("ssi", $display_name, $external_id, $uid);
        $stmt2->execute();
    } else {
        // New user: check admin list
        $admin_emails = [];
        $ares = $conn->query("SELECT setting_value FROM settings WHERE setting_key='admin_emails'");
        if ($row = $ares->fetch_assoc()) {
            $admin_emails = array_map('trim', explode(',', $row['setting_value']));
        }
        $is_admin = in_array(strtolower($dev_email), array_map('strtolower', $admin_emails)) ? 1 : 0;
        $stmt2 = $conn->prepare("INSERT INTO users (display_name, dev_email, external_id, is_admin) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("sssi", $display_name, $dev_email, $external_id, $is_admin);
        $stmt2->execute();
        $uid = $conn->insert_id;
    }
    $conn->close();

    // Set session and redirect
    $_SESSION['user_id'] = $uid;
    $_SESSION['is_admin'] = $is_admin;
    header("Location: " . ($_SESSION['after_login'] ?? '/index.php'));
    exit;
} catch (Exception $e) {
    die("SSO Login failed: " . htmlspecialchars($e->getMessage()));
}
?>
<?php
session_start();
require_once(__DIR__ . '/../../config/config.php');

// Fetch SSO config from settings table
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

require_once(__DIR__ . '/../../vendor/autoload.php');
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

$oidc->authenticate();
?>
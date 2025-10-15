<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require_once(__DIR__ . '/../config/config.php');
$feedback = "";

function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }

// Figure out the correct callback URL for this portal
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$domain = $_SERVER['HTTP_HOST'];
$callback_url = $scheme . '://' . $domain . '/entra_sso/callback';

if (isset($_POST['save_sso'])) {
    $fields = [
        'sso_tenant_id', 'sso_client_id', 'sso_client_secret', 'sso_redirect_uri', 'sso_metadata_url', 'sso_issuer_url', 'sso_logout_url', 'sso_scopes'
    ];
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    foreach ($fields as $k) {
        if (isset($_POST[$k])) {
            $v = $conn->real_escape_string($_POST[$k]);
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value='$v'");
        }
    }
    $conn->close();
    $feedback = "SSO settings saved!";
}

// Load settings for prefilling
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$settings = [];
$res = $conn->query("SELECT setting_key, setting_value FROM settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Configure Entra SSO</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
    .guide { margin: 1.5em 0 2em 0; color: #234; background: #f8faff; padding: 18px 22px; border-radius: 10px; border: 1px solid #d9e2f3;}
    .feedback { background: #c1fbc1; color: #184d18; padding: 8px 16px; border-radius: 6px; margin-bottom:1em; border: 1px solid #70db70;}
    form label { display:block; margin: 1em 0 0.5em 0; font-weight: 500;}
    input[type="text"], input[type="email"], input[type="password"], textarea {
        width: 98%; padding: 8px; border-radius: 5px; border: 1px solid #b8b8d0; font-size:1em;
    }
    button {
        background: #4263eb; color: #fff; padding: 12px 36px; border:none; border-radius:7px; font-size:1.1em; margin-top:1.5em;
        font-weight: 600; cursor:pointer; transition: background 0.15s;
    }
    button:hover { background: #2347ba; }
    </style>
</head>
<body>
<div class="container" style="max-width: 650px;">
    <h2>Configure Entra SSO</h2>
    <div class="guide">
        <b>Guide:</b> <br>
        1. Register an application in <b>Entra ID</b>.<br>
        2. Set the Redirect URI to: <b><?=esc($callback_url)?></b><br>
        3. Assign any necessary API permissions (OpenID, profile, email, etc.).<br>
        4. (Optional) Download and provide the SSO metadata URL.<br>
        5. Enter or update the details below, then save.<br>
        <a href="https://learn.microsoft.com/en-us/azure/active-directory/develop/quickstart-register-app" target="_blank">Microsoft Docs: Register an App</a>
    </div>
    <?php if ($feedback) echo "<div class='feedback'>$feedback</div>"; ?>
    <form method="post">
        <label>Tenant ID:
            <input type="text" name="sso_tenant_id" value="<?=esc($settings['sso_tenant_id']??'')?>" required>
        </label>
        <label>Application (client) ID:
            <input type="text" name="sso_client_id" value="<?=esc($settings['sso_client_id']??'')?>" required>
        </label>
        <label>Client Secret (optional, for confidential clients):
            <input type="password" name="sso_client_secret" value="<?=esc($settings['sso_client_secret']??'')?>">
        </label>
        <label>Redirect URI:
            <input type="text" name="sso_redirect_uri" value="<?=esc($settings['sso_redirect_uri']??$callback_url)?>" required>
        </label>
        <label>Metadata URL (optional): 
            <input type="text" name="sso_metadata_url" value="<?=esc($settings['sso_metadata_url']??'')?>">
            <small>For Entra ID, OIDC config is usually: <code>https://login.microsoftonline.com/{tenant}/v2.0/.well-known/openid-configuration</code></small>
        </label>
        <label>Issuer URL (optional): 
            <input type="text" name="sso_issuer_url" value="<?=esc($settings['sso_issuer_url']??'')?>">
            <small>Example: <code>https://login.microsoftonline.com/{tenant}/v2.0</code></small>
        </label>
        <label>Logout URL (optional): 
            <input type="text" name="sso_logout_url" value="<?=esc($settings['sso_logout_url']??'')?>">
            <small>Example: <code>https://login.microsoftonline.com/common/oauth2/logout?post_logout_redirect_uri=<?=esc($callback_url)?></code></small>
        </label>
        <label>Scopes (space-separated, e.g. "openid profile email"):
            <input type="text" name="sso_scopes" value="<?=esc($settings['sso_scopes']??'openid profile email')?>">
        </label>
        <button type="submit" name="save_sso">Save SSO Settings</button>
    </form>
    <p style="margin-top:2em"><a href="config_wizard.php">&laquo; Back to Config Wizard</a></p>
</div>
</body>
</html>
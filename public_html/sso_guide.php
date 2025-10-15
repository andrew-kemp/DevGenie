<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require_once(__DIR__ . '/../config/config.php');
$feedback = "";

function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }

if (isset($_POST['save_sso'])) {
    $fields = [
        'sso_tenant_id', 'sso_client_id', 'sso_redirect_uri', 'sso_metadata_url', 'sso_scopes'
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
    <title>Configure Azure Entra SSO</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
    .guide { margin: 1.5em 0 2em 0; color: #234; background: #f8faff; padding: 18px 22px; border-radius: 10px; border: 1px solid #d9e2f3;}
    </style>
</head>
<body>
<div class="container" style="max-width: 650px;">
    <h2>Configure Azure Entra SSO</h2>
    <div class="guide">
        <b>Guide:</b> <br>
        1. Register an application in Azure Entra ID (Azure Active Directory). <br>
        2. Set the Redirect URI to: <b>https://your-portal-domain/entra_sso/callback</b> (replace with your actual domain).<br>
        3. Assign any necessary API permissions (OpenID, profile, email, etc.).<br>
        4. (Optional) Download and provide the SSO metadata URL.<br>
        5. Enter or update the details below, then save.<br>
        <a href="https://learn.microsoft.com/en-us/azure/active-directory/develop/quickstart-register-app" target="_blank">Microsoft Docs: Register an App</a>
    </div>
    <?php if ($feedback) echo "<div class='feedback'>$feedback</div>"; ?>
    <form method="post">
        <label>Tenant ID: <input type="text" name="sso_tenant_id" value="<?=esc($settings['sso_tenant_id']??'')?>" required></label>
        <label>Application (client) ID: <input type="text" name="sso_client_id" value="<?=esc($settings['sso_client_id']??'')?>" required></label>
        <label>Redirect URI: <input type="text" name="sso_redirect_uri" value="<?=esc($settings['sso_redirect_uri']??'')?>" required></label>
        <label>Metadata URL (optional): <input type="text" name="sso_metadata_url" value="<?=esc($settings['sso_metadata_url']??'')?>"></label>
        <label>Scopes (space-separated, e.g. "openid profile email"): <input type="text" name="sso_scopes" value="<?=esc($settings['sso_scopes']??'openid profile email')?>"></label>
        <button type="submit" name="save_sso">Save SSO Settings</button>
    </form>
    <p style="margin-top:2em"><a href="config_wizard.php">&laquo; Back to Config Wizard</a></p>
</div>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require_once(__DIR__ . '/../config/config.php');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$feedback = "";

// Handle updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'smtp_host', 'smtp_port', 'smtp_user',
        'smtp_from', 'smtp_from_name',
        'kv_uri', 'sp_client_id', 'tenant_id'
    ];

    foreach ($fields as $k) {
        if (isset($_POST[$k])) {
            $v = $conn->real_escape_string($_POST[$k]);
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value='$v'");
        }
    }
    // Special: SMTP password update (not shown)
    if (!empty($_POST['smtp_pass'])) {
        $v = $conn->real_escape_string($_POST['smtp_pass']);
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('smtp_pass', '$v') ON DUPLICATE KEY UPDATE setting_value='$v'");
    }
    $feedback = "Settings updated!";
}

// Load settings
$settings = [];
$res = $conn->query("SELECT setting_key, setting_value FROM settings");
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$conn->close();

function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>DevGenie Settings</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>Portal Settings</h2>
    <?php if ($feedback) echo "<div class='feedback'>$feedback</div>"; ?>
    <form method="post">
        <h4>SMTP Settings</h4>
        <label>SMTP Host: <input type="text" name="smtp_host" value="<?=esc($settings['smtp_host']??'')?>" required></label>
        <label>SMTP Port: <input type="text" name="smtp_port" value="<?=esc($settings['smtp_port']??'')?>" required></label>
        <label>SMTP Username: <input type="text" name="smtp_user" value="<?=esc($settings['smtp_user']??'')?>" required></label>
        <label>SMTP New Password: <input type="password" name="smtp_pass" autocomplete="new-password"></label>
        <label>SMTP From Address: <input type="email" name="smtp_from" value="<?=esc($settings['smtp_from']??'')?>" required></label>
        <label>SMTP From Name: <input type="text" name="smtp_from_name" value="<?=esc($settings['smtp_from_name']??'')?>" required></label>
        <h4>Azure Settings</h4>
        <label>Key Vault URI: <input type="text" name="kv_uri" value="<?=esc($settings['kv_uri']??'')?>" required></label>
        <label>Service Principal (App) Client ID: <input type="text" name="sp_client_id" value="<?=esc($settings['sp_client_id']??'')?>" required></label>
        <label>Tenant ID: <input type="text" name="tenant_id" value="<?=esc($settings['tenant_id']??'')?>" required></label>
        <button type="submit">Update Settings</button>
    </form>
    <hr>
    <h4>Current Settings (excluding SMTP password):</h4>
    <ul>
        <li><strong>SMTP Host:</strong> <?=esc($settings['smtp_host']??'')?></li>
        <li><strong>SMTP Port:</strong> <?=esc($settings['smtp_port']??'')?></li>
        <li><strong>SMTP Username:</strong> <?=esc($settings['smtp_user']??'')?></li>
        <li><strong>SMTP From Address:</strong> <?=esc($settings['smtp_from']??'')?></li>
        <li><strong>SMTP From Name:</strong> <?=esc($settings['smtp_from_name']??'')?></li>
        <li><strong>Key Vault URI:</strong> <?=esc($settings['kv_uri']??'')?></li>
        <li><strong>Service Principal (App) Client ID:</strong> <?=esc($settings['sp_client_id']??'')?></li>
        <li><strong>Tenant ID:</strong> <?=esc($settings['tenant_id']??'')?></li>
    </ul>
    <p style="margin-top:2em"><a href="index.php">Return to Portal Home</a></p>
</div>
</body>
</html>
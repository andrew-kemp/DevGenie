<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
require_once(__DIR__ . '/../../config/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'kv_url', 'kv_tenant_id', 'kv_client_id', 'kv_client_secret',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption'
    ];
    foreach ($fields as $f) {
        $v = trim($_POST[$f] ?? '');
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=?");
        $stmt->bind_param("sss", $f, $v, $v);
        $stmt->execute();
    }
    $msg = "Key Vault and SMTP settings updated.";
}

$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'kv_%' OR setting_key LIKE 'smtp_%'");
$settings = [];
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$conn->close();
function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Key Vault / SMTP Settings</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="max-width:700px;">
    <h2>Key Vault & SMTP Settings</h2>
    <?php if (!empty($msg)) echo "<div class='success'>$msg</div>"; ?>
    <form method="post" autocomplete="off">
        <fieldset>
            <legend>Azure Key Vault</legend>
            <label>Key Vault URL:<br>
                <input type="text" name="kv_url" value="<?=esc($settings['kv_url'] ?? '')?>" style="width:95%">
            </label><br>
            <label>Tenant ID:<br>
                <input type="text" name="kv_tenant_id" value="<?=esc($settings['kv_tenant_id'] ?? '')?>" style="width:95%">
            </label><br>
            <label>Client ID:<br>
                <input type="text" name="kv_client_id" value="<?=esc($settings['kv_client_id'] ?? '')?>" style="width:95%">
            </label><br>
            <label>Client Secret:<br>
                <input type="text" name="kv_client_secret" value="<?=esc($settings['kv_client_secret'] ?? '')?>" style="width:95%">
            </label>
        </fieldset>
        <fieldset>
            <legend>SMTP</legend>
            <label>SMTP Host:<br>
                <input type="text" name="smtp_host" value="<?=esc($settings['smtp_host'] ?? '')?>" style="width:95%">
            </label><br>
            <label>SMTP Port:<br>
                <input type="number" name="smtp_port" value="<?=esc($settings['smtp_port'] ?? '')?>" style="width:95%">
            </label><br>
            <label>SMTP Username:<br>
                <input type="text" name="smtp_username" value="<?=esc($settings['smtp_username'] ?? '')?>" style="width:95%">
            </label><br>
            <label>SMTP Password:<br>
                <input type="text" name="smtp_password" value="<?=esc($settings['smtp_password'] ?? '')?>" style="width:95%">
            </label><br>
            <label>Encryption:<br>
                <input type="text" name="smtp_encryption" value="<?=esc($settings['smtp_encryption'] ?? '')?>" placeholder="e.g. tls or ssl" style="width:95%">
            </label>
        </fieldset>
        <button type="submit">Save Settings</button>
    </form>
    <p><a href="index.php">&laquo; Back to Admin Dashboard</a></p>
</div>
</body>
</html>
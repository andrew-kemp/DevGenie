<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
require_once(__DIR__ . '/../../config/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['azure_client_id', 'azure_tenant_id', 'azure_client_secret'];
    foreach ($fields as $f) {
        $v = trim($_POST[$f] ?? '');
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=?");
        $stmt->bind_param("sss", $f, $v, $v);
        $stmt->execute();
    }
    $msg = "Azure App Registration settings updated.";
}

$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'azure_%'");
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
    <title>Azure App Registration</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="max-width:700px;">
    <h2>Azure App Registration</h2>
    <?php if (!empty($msg)) echo "<div class='success'>$msg</div>"; ?>
    <form method="post" autocomplete="off">
        <label>Client ID:<br>
            <input type="text" name="azure_client_id" value="<?=esc($settings['azure_client_id'] ?? '')?>" style="width:95%">
        </label><br>
        <label>Tenant ID:<br>
            <input type="text" name="azure_tenant_id" value="<?=esc($settings['azure_tenant_id'] ?? '')?>" style="width:95%">
        </label><br>
        <label>Client Secret:<br>
            <input type="text" name="azure_client_secret" value="<?=esc($settings['azure_client_secret'] ?? '')?>" style="width:95%">
        </label><br>
        <button type="submit">Save Settings</button>
    </form>
    <p><a href="index.php">&laquo; Back to Admin Dashboard</a></p>
</div>
</body>
</html>
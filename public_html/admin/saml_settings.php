<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
require_once(__DIR__ . '/../config/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'saml_entity_id', 'saml_acs_url', 'saml_sls_url', 'saml_nameid_format',
        'idp_entity_id', 'idp_sso_url', 'idp_slo_url', 'idp_x509cert'
    ];
    foreach ($fields as $f) {
        $v = trim($_POST[$f] ?? '');
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=?");
        $stmt->bind_param("sss", $f, $v, $v);
        $stmt->execute();
    }
    $msg = "SAML/Entra settings updated.";
}

// Load current settings
$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'saml_%' OR setting_key LIKE 'idp_%'");
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
    <title>SAML/Entra SSO Settings</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="max-width:700px;">
    <h2>SAML / Microsoft Entra SSO Settings</h2>
    <?php if (!empty($msg)) echo "<div class='success'>$msg</div>"; ?>
    <form method="post" autocomplete="off">
        <fieldset>
            <legend>Service Provider (SP) Settings</legend>
            <label>Entity ID:<br>
                <input type="text" name="saml_entity_id" value="<?=esc($settings['saml_entity_id'] ?? '')?>" style="width:95%">
            </label><br>
            <label>ACS URL:<br>
                <input type="text" name="saml_acs_url" value="<?=esc($settings['saml_acs_url'] ?? '')?>" style="width:95%">
            </label><br>
            <label>SLS URL:<br>
                <input type="text" name="saml_sls_url" value="<?=esc($settings['saml_sls_url'] ?? '')?>" style="width:95%">
            </label><br>
            <label>NameID Format:<br>
                <input type="text" name="saml_nameid_format" value="<?=esc($settings['saml_nameid_format'] ?? 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress')?>" style="width:95%">
            </label>
        </fieldset>
        <fieldset>
            <legend>Identity Provider (IdP) Settings</legend>
            <label>Entity ID:<br>
                <input type="text" name="idp_entity_id" value="<?=esc($settings['idp_entity_id'] ?? '')?>" style="width:95%">
            </label><br>
            <label>Single Sign-On URL:<br>
                <input type="text" name="idp_sso_url" value="<?=esc($settings['idp_sso_url'] ?? '')?>" style="width:95%">
            </label><br>
            <label>Single Logout URL:<br>
                <input type="text" name="idp_slo_url" value="<?=esc($settings['idp_slo_url'] ?? '')?>" style="width:95%">
            </label><br>
            <label>x509 Certificate:<br>
                <textarea name="idp_x509cert" rows="5" style="width:95%"><?=esc($settings['idp_x509cert'] ?? '')?></textarea>
            </label>
        </fieldset>
        <button type="submit">Save Settings</button>
    </form>
    <p><a href="index.php">&laquo; Back to Admin Dashboard</a></p>
</div>
</body>
</html>
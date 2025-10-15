<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require_once(__DIR__ . '/../config/config.php');

$cert_path = CERT_PATH;
$key_path = KEY_PATH;
$feedback = "";

// Handle certificate generation
if (isset($_POST['generate_cert'])) {
    $dn = array("CN" => "DevGenieKeyVault", "O" => "DevGenie", "C" => "GB");
    $privkey = openssl_pkey_new(["private_key_bits" => 4096, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
    $csr = openssl_csr_new($dn, $privkey);
    $x509 = openssl_csr_sign($csr, null, $privkey, 3650);
    openssl_x509_export_to_file($x509, $cert_path);
    openssl_pkey_export_to_file($privkey, $key_path);
    chmod($cert_path, 0644);
    chmod($key_path, 0600);
    $feedback = "Certificate and key generated at $cert_path and $key_path.";
}

// Handle Azure SP certificate upload
if (isset($_POST['upload_cert'])) {
    $sp_client_id = trim($_POST['sp_client_id']);
    $cmd = "az ad app credential reset --id \"$sp_client_id\" --cert \"$cert_path\" --append 2>&1";
    $output = shell_exec($cmd);
    $feedback = "Azure CLI output:<br><pre>$output</pre>";
}

// Handle saving settings (SMTP, Key Vault, SP info)
if (isset($_POST['save_settings'])) {
    $smtp_host = trim($_POST['smtp_host']);
    $smtp_port = trim($_POST['smtp_port']);
    $smtp_user = trim($_POST['smtp_user']);
    $smtp_from = trim($_POST['smtp_from']);
    $kv_uri = trim($_POST['kv_uri']);
    $sp_client_id = trim($_POST['sp_client_id']);
    $tenant_id = trim($_POST['tenant_id']);
    $data = [
        'smtp_host' => $smtp_host,
        'smtp_port' => $smtp_port,
        'smtp_user' => $smtp_user,
        'smtp_from' => $smtp_from,
        'kv_uri' => $kv_uri,
        'sp_client_id' => $sp_client_id,
        'tenant_id' => $tenant_id,
        'cert_path' => $cert_path,
        'key_path' => $key_path,
    ];
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    foreach ($data as $k => $v) {
        $k = $conn->real_escape_string($k);
        $v = $conn->real_escape_string($v);
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value='$v'");
    }
    $conn->close();
    $feedback = "Settings saved!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>DevGenie Config Wizard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>Portal Configuration</h2>
    <?php if ($feedback) echo "<div class='feedback'>$feedback</div>"; ?>
    <form method="post">
        <h4>SMTP Settings</h4>
        <label>SMTP Host: <input type="text" name="smtp_host" required></label>
        <label>SMTP Port: <input type="text" name="smtp_port" required></label>
        <label>SMTP Username: <input type="text" name="smtp_user" required></label>
        <label>SMTP From Address: <input type="email" name="smtp_from" required></label>
        <h4>Azure Settings</h4>
        <label>Key Vault URI: <input type="text" name="kv_uri" required></label>
        <label>Service Principal (App) Client ID: <input type="text" name="sp_client_id" required></label>
        <label>Tenant ID: <input type="text" name="tenant_id" required></label>
        <button type="submit" name="save_settings">Save All Settings</button>
    </form>
    <hr>
    <form method="post">
        <h4>Certificate Management</h4>
        <button type="submit" name="generate_cert">Generate new 4096-bit certificate</button>
    </form>
    <form method="post">
        <h4>Upload Certificate to Azure Service Principal</h4>
        <label>Service Principal (App) Client ID: <input type="text" name="sp_client_id" required></label>
        <button type="submit" name="upload_cert">Upload Cert to Azure</button>
    </form>
</div>
</body>
</html>
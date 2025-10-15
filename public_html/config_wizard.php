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
$error = "";

// Helper: Escape HTML
function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }

// Step 1: Create Service Principal
if (isset($_POST['create_sp'])) {
    $sp_name = "DevGenieSP-" . uniqid();
    $cmd = "az ad sp create-for-rbac --name '$sp_name' --skip-assignment --sdk-auth -o json 2>&1";
    $output = shell_exec($cmd);
    $response = json_decode($output, true);

    if (isset($response['clientId'])) {
        $feedback = "Service Principal created.<br>Client ID: " . esc($response['clientId']) . "<br>Tenant ID: " . esc($response['tenantId']);
        $_POST['sp_client_id'] = $response['clientId'];
        $_POST['tenant_id'] = $response['tenantId'];
    } else {
        $error = "Failed to create Service Principal. Azure CLI output:<br><pre>" . esc($output) . "</pre>";
    }
}

// Step 2: Assign SP permissions to Key Vault (after vault info provided)
if (isset($_POST['assign_kv_access'])) {
    $kv_name = trim($_POST['kv_name']);
    $sp_client_id = trim($_POST['sp_client_id']);
    $cmd = "az keyvault set-policy --name '$kv_name' --spn '$sp_client_id' --secret-permissions get list set delete --key-permissions get list --certificate-permissions get list 2>&1";
    $output = shell_exec($cmd);
    if (strpos($output, 'updated') !== false || strpos($output, 'created') !== false) {
        $feedback = "Assigned Service Principal access to Key Vault.";
    } else {
        $error = "Failed to assign access. Azure CLI output:<br><pre>" . esc($output) . "</pre>";
    }
}

// Step 3: Create Key Vault
if (isset($_POST['create_kv'])) {
    $kv_name = trim($_POST['kv_name']);
    $resource_group = trim($_POST['kv_rg']);
    $location = trim($_POST['kv_location']);
    $cmd = "az keyvault create --name '$kv_name' --resource-group '$resource_group' --location '$location' -o json 2>&1";
    $output = shell_exec($cmd);
    $json = json_decode($output, true);

    if (isset($json['properties']['vaultUri'])) {
        $feedback = "Key Vault created.<br>Key Vault URI: " . esc($json['properties']['vaultUri']);
        $_POST['kv_uri'] = $json['properties']['vaultUri'];
    } else {
        $error = "Failed to create Key Vault. Azure CLI output:<br><pre>$output</pre>";
    }
}

// Step 4: Save all settings (including from above)
if (isset($_POST['save_settings'])) {
    $fields = [
        'smtp_host', 'smtp_port', 'smtp_user',
        'smtp_from', 'smtp_from_name',
        'kv_uri', 'sp_client_id', 'tenant_id'
    ];
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    foreach ($fields as $k) {
        if (isset($_POST[$k])) {
            $v = $conn->real_escape_string($_POST[$k]);
            $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value='$v'");
        }
    }
    $conn->close();
    $feedback = "Settings saved!";
}

// Step 5: Generate certificate
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

// Step 6: Upload cert to SP
if (isset($_POST['upload_cert'])) {
    $sp_client_id = trim($_POST['sp_client_id']);
    $cmd = "az ad app credential reset --id \"$sp_client_id\" --cert \"$cert_path\" --append 2>&1";
    $output = shell_exec($cmd);
    $feedback = "Azure CLI output:<br><pre>$output</pre>";
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
    <title>DevGenie Config Wizard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>Portal Configuration</h2>
    <?php if ($feedback) echo "<div class='feedback'>$feedback</div>"; ?>
    <?php if ($error) echo "<div class='error'>$error</div>"; ?>

    <form method="post">
        <h4>SMTP Settings</h4>
        <label>SMTP Host: <input type="text" name="smtp_host" value="<?=esc($settings['smtp_host']??'')?>" required></label>
        <label>SMTP Port: <input type="text" name="smtp_port" value="<?=esc($settings['smtp_port']??'')?>" required></label>
        <label>SMTP Username: <input type="text" name="smtp_user" value="<?=esc($settings['smtp_user']??'')?>" required></label>
        <label>SMTP From Address: <input type="email" name="smtp_from" value="<?=esc($settings['smtp_from']??'')?>" required></label>
        <label>SMTP From Name: <input type="text" name="smtp_from_name" value="<?=esc($settings['smtp_from_name']??'')?>" required></label>
        <h4>Azure Settings</h4>
        <label>Key Vault URI: <input type="text" name="kv_uri" value="<?=esc($settings['kv_uri']??'')?>" required></label>
        <label>Service Principal (App) Client ID: <input type="text" name="sp_client_id" value="<?=esc($settings['sp_client_id']??'')?>" required></label>
        <label>Tenant ID: <input type="text" name="tenant_id" value="<?=esc($settings['tenant_id']??'')?>" required></label>
        <button type="submit" name="save_settings">Save All Settings</button>
    </form>

    <hr>

    <form method="post">
        <h4>Create Service Principal (Azure)</h4>
        <button type="submit" name="create_sp">Create Service Principal</button>
    </form>
    <form method="post">
        <h4>Create Key Vault (Azure)</h4>
        <label>Key Vault Name: <input type="text" name="kv_name" required></label>
        <label>Azure Resource Group: <input type="text" name="kv_rg" required></label>
        <label>Azure Location: <input type="text" name="kv_location" required></label>
        <button type="submit" name="create_kv">Create Key Vault</button>
    </form>
    <form method="post">
        <h4>Assign SP Access to Key Vault</h4>
        <label>Key Vault Name: <input type="text" name="kv_name" required></label>
        <label>Service Principal (App) Client ID: <input type="text" name="sp_client_id" value="<?=esc($settings['sp_client_id']??'')?>" required></label>
        <button type="submit" name="assign_kv_access">Assign SP Access to Key Vault</button>
    </form>
    <hr>
    <form method="post">
        <h4>Certificate Management</h4>
        <button type="submit" name="generate_cert">Generate new 4096-bit certificate</button>
    </form>
    <form method="post">
        <h4>Upload Certificate to Azure Service Principal</h4>
        <label>Service Principal (App) Client ID: <input type="text" name="sp_client_id" value="<?=esc($settings['sp_client_id']??'')?>" required></label>
        <button type="submit" name="upload_cert">Upload Cert to Azure</button>
    </form>
    <p style="margin-top:2em"><a href="index.php">Return to Portal Home</a></p>
</div>
</body>
</html>
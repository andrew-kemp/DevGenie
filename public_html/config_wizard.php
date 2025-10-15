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

// Step 1: Save all settings (including from above)
if (isset($_POST['save_settings'])) {
    $fields = [
        'smtp_host', 'smtp_port', 'smtp_user',
        'smtp_from', 'smtp_from_name',
        'app_reg_name', 'kv_name',
        'azure_subscription_id', 'kv_rg', 'rg_location',
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

// Step 2: Generate certificate
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

// Step 3: Upload cert to SP
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
    <style>
    .wizard-guide-btn {
        display: block;
        width: 90%;
        max-width: 620px;
        margin: 0 auto 2em auto;
        padding: 20px;
        font-size: 1.2em;
        font-weight: 700;
        text-align: center;
        background: #f5faff;
        color: #4263eb;
        border: 2px solid #b9c6f2;
        border-radius: 10px;
        text-decoration: none;
        transition: background 0.15s, box-shadow 0.15s;
        box-shadow: 0 2px 24px rgba(44,80,140,0.09);
    }
    .wizard-guide-btn:hover {
        background: #e8f0fe;
        color: #2c3f85;
        border-color: #4263eb;
        box-shadow: 0 4px 30px rgba(44,80,140,0.13);
    }
    .section-label {
        font-weight: bold;
        margin-top: 1.6em;
        font-size: 1.07em;
        color: #234;
    }
    </style>
    <script>
    function updateKvUri() {
        var name = document.getElementById('kv_name').value.trim();
        var uriInput = document.getElementById('kv_uri');
        if(name.match(/^[a-zA-Z0-9-]+$/) && name.length >= 3 && name.length <= 24) {
            uriInput.value = 'https://' + name + '.vault.azure.net/';
        } else {
            uriInput.value = '';
        }
    }
    window.onload = function() {
        updateKvUri();
        if(document.getElementById('kv_name'))
            document.getElementById('kv_name').addEventListener('input', updateKvUri);
    };
    </script>
</head>
<body>
<div class="container" style="max-width: 740px;">
    <h2>Portal Configuration</h2>
    <a href="setup_guide.php" class="wizard-guide-btn">Guide Me to Create App Registration and Key Vault</a>
    <a href="sso_guide.php" class="wizard-guide-btn" style="margin-top:-1em;">Configure Azure Entra SSO</a>

    <div style="margin:2em 0 1.5em 0; text-align:center; color:#567;">
        <b>Already have an App Registration/Service Principal and Key Vault?</b>
        <br>Enter the details below to connect your portal.
    </div>

    <?php if ($feedback) echo "<div class='feedback'>$feedback</div>"; ?>
    <?php if ($error) echo "<div class='error'>$error</div>"; ?>

    <form method="post">
        <span class="section-label">SMTP Settings</span>
        <label>SMTP Host: <input type="text" name="smtp_host" value="<?=esc($settings['smtp_host']??'')?>" required></label>
        <label>SMTP Port: <input type="text" name="smtp_port" value="<?=esc($settings['smtp_port']??'')?>" required></label>
        <label>SMTP Username: <input type="text" name="smtp_user" value="<?=esc($settings['smtp_user']??'')?>" required></label>
        <label>SMTP From Address: <input type="email" name="smtp_from" value="<?=esc($settings['smtp_from']??'')?>" required></label>
        <label>SMTP From Name: <input type="text" name="smtp_from_name" value="<?=esc($settings['smtp_from_name']??'')?>" required></label>
        <span class="section-label">Azure & Entra Manual Entry</span>
        <label>App Registration Name: <input type="text" name="app_reg_name" value="<?=esc($settings['app_reg_name']??'DevGenieSP-'.uniqid())?>" required></label>
        <label>Service Principal (App) Client ID: <input type="text" name="sp_client_id" value="<?=esc($settings['sp_client_id']??'')?>" required></label>
        <label>Tenant ID: <input type="text" name="tenant_id" value="<?=esc($settings['tenant_id']??'')?>" required></label>
        <label>Azure Subscription ID: <input type="text" name="azure_subscription_id" value="<?=esc($settings['azure_subscription_id']??'')?>" required></label>
        <label>Resource Group: <input type="text" name="kv_rg" value="<?=esc($settings['kv_rg']??'')?>" required></label>
        <label>Resource Group Location: <input type="text" name="rg_location" value="<?=esc($settings['rg_location']??'uksouth')?>" required></label>
        <label>Key Vault Name:
            <input type="text" name="kv_name" id="kv_name" value="<?=esc($settings['kv_name']??'devgeniekv'.rand(1000,9999))?>" required />
        </label>
        <label>Key Vault URI:
            <input type="text" name="kv_uri" id="kv_uri" value="<?=esc($settings['kv_uri']??'')?>" readonly />
        </label>
        <button type="submit" name="save_settings">Save All Settings</button>
    </form>

    <hr>

    <form method="post">
        <span class="section-label">Certificate Management</span>
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
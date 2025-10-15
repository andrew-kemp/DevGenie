<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }

$show_script = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appname = trim($_POST['appname']);
    $kv_name = trim($_POST['kv_name']);
    $kv_rg = trim($_POST['kv_rg']);
    $rg_location = trim($_POST['rg_location']);
    $subid = trim($_POST['subscription_id']);

    // Store these values in the DB for reference
    require_once(__DIR__ . '/../config/config.php');
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $fields = [
        'app_reg_name' => $appname,
        'kv_name' => $kv_name,
        'kv_rg' => $kv_rg,
        'rg_location' => $rg_location,
        'azure_subscription_id' => $subid
    ];
    foreach ($fields as $k => $v) {
        $v = $conn->real_escape_string($v);
        $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$k', '$v') ON DUPLICATE KEY UPDATE setting_value='$v'");
    }
    $conn->close();

    $show_script = true;
    $powershell = <<<POWERSHELL
# Log in (if not already) and set subscription
Connect-AzAccount
Set-AzContext -SubscriptionId "$subid"

# 1. Create the App Registration (Application)
\$app = New-AzADApplication -DisplayName "$appname"

# 2. Create the Service Principal
\$sp  = New-AzADServicePrincipal -ApplicationId \$app.AppId

# 3. Create a client secret (save this securely!)
\$secret = New-AzADAppCredential -ApplicationId \$app.AppId -DisplayName "DevGenieSecret"
Write-Host "Client Secret (save this!): \$([\$secret.SecretText])"

# 4. Create the Key Vault in the Resource Group's location
\$kv = New-AzKeyVault -Name "$kv_name" -ResourceGroupName "$kv_rg" -Location "$rg_location"

# 5. Assign Key Vault access policy to the Service Principal
Set-AzKeyVaultAccessPolicy -VaultName "$kv_name" -ServicePrincipalName \$sp.AppId `
    -PermissionsToSecrets get,list,set,delete `
    -PermissionsToCertificates get,list `
    -PermissionsToKeys get,list

Write-Host "`n==== Output these values into your DevGenie Portal Setup ===="
Write-Host "Application (client) ID: \$([\$app.AppId])"
Write-Host "Client Secret: \$([\$secret.SecretText])"
Write-Host "Directory (tenant) ID: \$((Get-AzContext).Tenant.Id)"
Write-Host "Key Vault URI: \$([\$kv.VaultUri])"
Write-Host "Key Vault Name: \$([\$kv.VaultName])"
POWERSHELL;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Guide: Create App Registration &amp; Key Vault</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
    textarea.code-block { width: 100%; min-height: 280px; font-family: 'Fira Mono', 'Consolas', monospace; font-size: 1em; background: #f4f7fa; border: 1px solid #c1cce0; border-radius: 8px; padding: 12px; }
    ol li { margin-bottom: 1em; }
    </style>
</head>
<body>
<div class="container">
    <h2>Guide: Create App Registration &amp; Key Vault</h2>
    <ol>
        <li>
            Fill in the details below and click <b>Generate PowerShell Script</b>.
        </li>
        <li>
            Copy the generated script and run it in <b>Azure Cloud Shell</b> or your local PowerShell with the <b>Az</b> module installed.<br>
            <a href="https://shell.azure.com/" target="_blank">Open Azure Cloud Shell</a>
        </li>
        <li>
            <b>Copy the outputted values (Client ID, Tenant ID, Secret, Key Vault URI)</b> and paste them back into the Config Wizard to complete your portal setup.
        </li>
        <li>
            <a href="https://learn.microsoft.com/en-us/azure/active-directory/develop/quickstart-register-app" target="_blank">See Microsoft Docs: Register an App</a>
        </li>
    </ol>
    <form method="post" style="margin-bottom:2em;">
        <label>App Registration Name: <input type="text" name="appname" required value="<?=esc($_POST['appname']??'DevGenieSP-'.uniqid())?>"></label>
        <label>Key Vault Name: <input type="text" name="kv_name" required value="<?=esc($_POST['kv_name']??'devgeniekv'.rand(1000,9999))?>"></label>
        <label>Azure Subscription ID: <input type="text" name="subscription_id" required value="<?=esc($_POST['subscription_id']??'')?>"></label>
        <label>Resource Group Name: <input type="text" name="kv_rg" required value="<?=esc($_POST['kv_rg']??'')?>"></label>
        <label>Resource Group Location: <input type="text" name="rg_location" required value="<?=esc($_POST['rg_location']??'uksouth')?>"></label>
        <button type="submit">Generate PowerShell Script</button>
    </form>
    <?php if ($show_script): ?>
        <h3>Copy and Run This PowerShell Script</h3>
        <textarea class="code-block" readonly><?=esc($powershell)?></textarea>
        <div class="feedback" style="margin-top:1em;">
            <b>After running:</b> Copy the <b>Client ID</b>, <b>Tenant ID</b>, <b>Client Secret</b>, and <b>Key Vault URI</b> into the main Config Wizard form.
        </div>
    <?php endif; ?>
    <p style="margin-top:2em"><a href="config_wizard.php">&laquo; Back to Config Wizard</a></p>
</div>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
$admin_pages = [
    'index.php'             => 'Admin Dashboard',
    'saml_settings.php'     => 'SAML/Entra SSO Settings',
    'azure_settings.php'    => 'Azure App Registration',
    'keyvault_settings.php' => 'Key Vault / SMTP Settings',
    'users.php'             => 'User Management',
    'admin_users.php'       => 'Admin User Management',
];
$current_page = basename($_SERVER['PHP_SELF']);
// TODO: Load and save Key Vault/SMTP settings here.
?>
<!DOCTYPE html>
<html>
<head>
    <title>Key Vault / SMTP Settings - DevGenie Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    .admin-main-card {
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 8px 32px rgba(44,80,140,0.10), 0 1.5px 8px rgba(44,80,140,0.08);
        max-width: 540px;
        margin: 2.5em auto 0 auto;
        padding: 2.2em 2.2em 2em 2.2em;
    }
    .admin-section-title {
        font-size: 1.33em;
        font-weight: 700;
        color: #1d2769;
        margin-bottom: 1.2em;
        letter-spacing: 0.01em;
    }
    </style>
</head>
<body>
<nav class="admin-nav">
    <?php foreach ($admin_pages as $file => $label): ?>
        <a href="<?= $file ?>" class="<?= $current_page === $file ? 'active' : '' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
    <a href="../index.php" class="nav-portal">Return to Portal</a>
</nav>
<div class="admin-main-card">
    <div class="admin-section-title">Key Vault / SMTP Settings</div>
    <div style="margin-bottom:2em;font-size:1.06em;color:#555e73;">
        Configure Azure Key Vault and SMTP settings for secure email delivery.<br>
        <span style="color:#b20e3a;font-weight:500;">Super admin only:</span> Only super admins can modify these settings.
    </div>
    <form method="post" autocomplete="off">
        <div class="form-group">
            <label for="keyvault_url">Key Vault URL:</label>
            <input type="text" name="keyvault_url" id="keyvault_url" value="" required>
        </div>
        <div class="form-group">
            <label for="smtp_host">SMTP Host:</label>
            <input type="text" name="smtp_host" id="smtp_host" value="" required>
        </div>
        <div class="form-group">
            <label for="smtp_port">SMTP Port:</label>
            <input type="text" name="smtp_port" id="smtp_port" value="" required>
        </div>
        <div class="form-group">
            <label for="smtp_user">SMTP Username:</label>
            <input type="text" name="smtp_user" id="smtp_user" value="" required>
        </div>
        <div class="form-group">
            <label for="smtp_pass">SMTP Password:</label>
            <input type="password" name="smtp_pass" id="smtp_pass" value="" required>
        </div>
        <button type="submit" class="admin-update-btn">Save Key Vault / SMTP Settings</button>
    </form>
</div>
</body>
</html>
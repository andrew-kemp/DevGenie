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
?>
<!DOCTYPE html>
<html>
<head>
    <title>DevGenie Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    .admin-main-card {
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 8px 32px rgba(44,80,140,0.10), 0 1.5px 8px rgba(44,80,140,0.08);
        max-width: 520px;
        margin: 2.5em auto 0 auto;
        padding: 2.3em 2.2em 2.6em 2.2em;
        text-align: center;
    }
    .admin-section-title {
        font-size: 1.33em;
        font-weight: 700;
        color: #1d2769;
        margin-bottom: 1.2em;
        letter-spacing: 0.01em;
    }
    .admin-dashboard-links {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1.1em;
        margin-top: 1.7em;
    }
    .admin-dashboard-links a {
        background: #f3e8ff;
        color: #7c3aed;
        font-weight: 600;
        border-radius: 8px;
        border: 1.2px solid #e9d5ff;
        padding: 0.92em 1.3em;
        text-decoration: none;
        font-size: 1.09em;
        transition: background 0.14s, color 0.15s, border 0.13s;
        margin-bottom: 0.3em;
        display: inline-block;
    }
    .admin-dashboard-links a:hover {
        background: #c7d2fe;
        color: #4c1d95;
        border-color: #a5b4fc;
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
    <div class="admin-section-title">Welcome to the Admin Dashboard</div>
    <div style="font-size:1.09em;color:#555e73;">
        Use the menu above to manage SSO, app registration, settings, users, and admin permissions.<br>
        <span style="color:#7c3aed;font-weight:600;">Tip:</span> Only super admins can change system-level settings.
    </div>
    <div class="admin-dashboard-links">
        <?php foreach ($admin_pages as $file => $label): ?>
            <?php if ($file !== 'index.php'): ?>
                <a href="<?= $file ?>"><?= htmlspecialchars($label) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
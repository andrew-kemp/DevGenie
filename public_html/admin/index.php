<?php
session_start();
// Only allow admins
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
</head>
<body>
<nav class="admin-nav">
    <?php foreach ($admin_pages as $file => $label): ?>
        <a href="<?= $file ?>" class="<?= $current_page === $file ? 'active' : '' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
    <a href="../index.php">Return to Portal</a>
</nav>
<div class="container" style="max-width:700px;">
    <h2>Admin Dashboard</h2>
    <p>Welcome to the DevGenie Admin Dashboard. Use the menu above to manage SSO, users, and settings.</p>
</div>
</body>
</html>
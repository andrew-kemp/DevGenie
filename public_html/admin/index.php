<?php
session_start();
// Only allow admins
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>DevGenie Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="max-width:700px;">
    <h2>Admin Dashboard</h2>
    <ul>
        <li><a href="saml_settings.php">SAML/Entra SSO Settings</a></li>
        <li><a href="azure_settings.php">Azure App Registration</a></li>
        <li><a href="keyvault_settings.php">Key Vault / SMTP Settings</a></li>
        <li><a href="users.php">User Management</a></li>
        <li><a href="../index.php">Return to Portal</a></li>
    </ul>
</div>
</body>
</html>
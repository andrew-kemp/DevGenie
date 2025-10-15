<?php
require_once(__DIR__ . '/../config/config.php');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$result = $conn->query("SELECT id FROM admins LIMIT 1");
if (!$result || $result->num_rows === 0) {
    $conn->close();
    header("Location: setup.php");
    exit;
}
$conn->close();

session_start();
$logged_in = isset($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>DevGenie Portal</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h1>Welcome to DevGenie Portal!</h1>
    <?php if ($logged_in): ?>
        <ul>
            <li><a href="config_wizard.php">Initial Setup & Configuration Wizard</a></li>
            <li><a href="settings.php">View/Update Portal Settings</a></li>
            <li><a href="logout.php">Log out</a></li>
        </ul>
    <?php else: ?>
        <p><a href="login.php">Admin Login</a></p>
    <?php endif; ?>
</div>
</body>
</html>
<?php
require_once(__DIR__ . '/../config/config.php');

// First: If no admin exists, redirect to setup (for any user)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$result = $conn->query("SELECT id FROM admins LIMIT 1");
if (!$result || $result->num_rows === 0) {
    $conn->close();
    header("Location: setup.php");
    exit;
}
$conn->close();

// Now enforce login/session
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
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
    <p>You are logged in. <a href="logout.php">Log out</a></p>
</div>
</body>
</html>
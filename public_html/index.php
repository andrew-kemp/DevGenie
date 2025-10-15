<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
require_once(__DIR__ . '/../config/config.php');

// Create DB connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo "<!DOCTYPE html><html><head><link rel='stylesheet' href='assets/style.css'></head><body><div class='container'>";
    echo "<h2>Portal Error</h2>";
    echo "<p class='error'>Database connection failed: " . htmlspecialchars($conn->connect_error) . "</p>";
    exit("</div></body></html>");
}

// Check if at least one admin exists
$result = $conn->query("SELECT id FROM admins LIMIT 1");
if (!$result) {
    echo "<!DOCTYPE html><html><head><link rel='stylesheet' href='assets/style.css'></head><body><div class='container'>";
    echo "<h2>Portal Error</h2>";
    echo "<p class='error'>Could not query admin table. Please check your database schema.</p>";
    $conn->close();
    exit("</div></body></html>");
}
if ($result->num_rows === 0) {
    $conn->close();
    header('Location: /setup.php');
    exit;
}
$conn->close();
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
    <p>DevGenie is installed and ready to use.</p>
    <p><a href="logout.php">Log out</a></p>
</div>
</body>
</html>
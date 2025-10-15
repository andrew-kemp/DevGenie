<!DOCTYPE html>
<html>
<head>
    <title>DevGenie Portal</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
<?php
require_once(__DIR__ . '/../config/config.php');

// Create DB connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo "<p class='error'>Database connection failed: " . htmlspecialchars($conn->connect_error) . "</p>";
    exit;
}

// Check if at least one admin exists
$result = $conn->query("SELECT id FROM admins LIMIT 1");
if (!$result) {
    echo "<p class='error'>Could not query admin table. Please check your database schema.</p>";
    $conn->close();
    exit;
}
if ($result->num_rows === 0) {
    header('Location: /setup.php');
    exit;
}

echo "<h1>Welcome to DevGenie Portal!</h1>";
$conn->close();
?>
<p>DevGenie is installed and ready to use.</p>
</div>
</body>
</html>
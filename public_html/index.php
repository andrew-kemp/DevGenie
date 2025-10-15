<?php
require_once(__DIR__ . '/../config/config.php');

// Create DB connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check if at least one admin exists
$result = $conn->query("SELECT id FROM admins LIMIT 1");
if ($result->num_rows === 0) {
    header('Location: /setup.php');
    exit;
}

echo "<h1>Welcome to DevGenie Portal!</h1>";
$conn->close();
?>
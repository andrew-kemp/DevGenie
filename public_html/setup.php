<?php
session_start();
require_once(__DIR__ . '/../config/config.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$error = "";

// Try DB connection and show errors if any
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    echo "<!DOCTYPE html><html><head><link rel='stylesheet' href='assets/style.css'></head><body><div class='container'>";
    echo "<h2>Setup Error</h2>";
    echo "<p class='error'>Database connection failed: " . htmlspecialchars($conn->connect_error) . "</p>";
    echo "<p>Please check your <code>config/config.php</code> and database settings.</p>";
    exit("</div></body></html>");
}

// Try to query the admins table
$result = @$conn->query("SELECT id FROM admins LIMIT 1");
if (!$result) {
    echo "<!DOCTYPE html><html><head><link rel='stylesheet' href='assets/style.css'></head><body><div class='container'>";
    echo "<h2>Setup Error</h2>";
    echo "<p class='error'>Could not query <strong>admins</strong> table: " . htmlspecialchars($conn->error) . "</p>";
    echo "<p>Check that your database is initialized and the required tables exist.</p>";
    exit("</div></body></html>");
}

// If admin exists, redirect to login
if ($result->num_rows > 0) {
    header("Location: login.php");
    exit;
}

// Handle admin account creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_setup'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($username && $email && $password && $confirm_password) {
        if ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admins (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password_hash);
            if ($stmt->execute()) {
                // Auto-login admin user
                $_SESSION['admin_id'] = $stmt->insert_id;
                header('Location: config_wizard.php');
                exit;
            } else {
                $error = "Failed to create admin. Error: " . htmlspecialchars($stmt->error);
            }
        }
    } else {
        $error = "All fields are required.";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>DevGenie Setup</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>Step 1: Create Admin Account</h2>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <form method="post" autocomplete="off">
        <input type="hidden" name="admin_setup" value="1">
        <label>Admin Username: <input type="text" name="username" required autocomplete="off"></label>
        <label>Admin Email: <input type="email" name="email" required autocomplete="off"></label>
        <label>Password: <input type="password" name="password" required autocomplete="off"></label>
        <label>Confirm Password: <input type="password" name="confirm_password" required autocomplete="off"></label>
        <button type="submit">Set Up Admin</button>
    </form>
</div>
</body>
</html>
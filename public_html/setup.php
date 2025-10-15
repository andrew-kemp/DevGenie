<?php
$has_config = file_exists(__DIR__ . '/../config/config.php');
$can_connect = false;
$admin_exists = false;
$err = '';
if ($has_config) {
    require_once(__DIR__ . '/../config/config.php');
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $can_connect = !$conn->connect_error;
    if ($can_connect) {
        // Create the admins table if it doesn't exist
        $conn->query("CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(128) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $res = $conn->query("SELECT COUNT(*) as cnt FROM admins");
        $row = $res ? $res->fetch_assoc() : null;
        $admin_exists = $row && $row['cnt'] > 0;
    }
}

// If setup is complete, redirect to login
if ($has_config && $can_connect && $admin_exists) {
    header("Location: /login.php");
    exit;
}

// On POST, create admin user (simple example)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_connect && !$admin_exists) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) {
        $err = "Username and password required.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hash);
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: /login.php");
            exit;
        } else {
            $err = "Could not create admin: " . $conn->error;
        }
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
<div class="container" style="max-width:450px;">
<h2>DevGenie First-Time Setup</h2>
<?php if (!$has_config): ?>
    <div class="error"><b>Configuration file not found.</b> Please run the install script to create <code>config/config.php</code>.</div>
<?php elseif (!$can_connect): ?>
    <div class="error"><b>Could not connect to database.</b> Check your config and DB server.</div>
<?php elseif ($admin_exists): ?>
    <div class="success"><b>Setup already complete.</b> <a href="login.php">Go to login</a></div>
<?php else: ?>
    <form method="post" autocomplete="off">
        <?php if ($err): ?><div class="error"><?=htmlspecialchars($err)?></div><?php endif; ?>
        <label>Admin Username:<br><input type="text" name="username" required></label><br>
        <label>Password:<br><input type="password" name="password" required></label><br>
        <button type="submit">Create Admin User</button>
    </form>
<?php endif; ?>
</div>
</body>
</html>
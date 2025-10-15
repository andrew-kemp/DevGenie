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
            email VARCHAR(255) NOT NULL UNIQUE,
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

// On POST, create admin user (with email and password confirmation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_connect && !$admin_exists) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    if (!$username || !$email || !$password || !$password2) {
        $err = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Please enter a valid email address.";
    } elseif ($password !== $password2) {
        $err = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $err = "Password must be at least 8 characters.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO admins (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hash);
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
function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>DevGenie Setup</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
    .setup-container {
        max-width: 520px;
        margin: 3em auto;
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 6px 32px 0 #19376b14;
        padding: 2.5em 2.5em 2em 2.5em;
    }
    .setup-container h2 {
        font-size: 2em;
        margin-bottom: 1.2em;
        color: #222a36;
        font-weight: 700;
    }
    .setup-container label {
        font-weight: 600;
        color: #253157;
        margin-top: 1em;
        display: block;
        margin-bottom: 0.5em;
    }
    .setup-container input[type="text"],
    .setup-container input[type="email"],
    .setup-container input[type="password"] {
        width: 100%;
        box-sizing: border-box;
        font-size: 1.1em;
        margin-bottom: 1.1em;
        padding: 0.7em 1em;
        border-radius: 9px;
        border: 1px solid #e2eafd;
        background: #f7faff;
        transition: border 0.15s;
    }
    .setup-container input:focus {
        border: 1.5px solid #4263eb;
        outline: none;
        background: #f0f4ff;
    }
    .setup-container .error {
        background: #ffe6e6;
        color: #b1001b;
        border: 1px solid #ffb2b2;
        border-radius: 7px;
        padding: 9px 14px;
        margin-bottom: 1em;
        font-weight: 500;
    }
    .setup-container button {
        background: #4263eb;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 0.8em 1.7em;
        font-weight: 700;
        font-size: 1.15em;
        margin-top: 0.6em;
        cursor: pointer;
        transition: background 0.15s;
    }
    .setup-container button:hover {
        background: #3047c9;
    }
    </style>
</head>
<body>
<div class="setup-container">
    <h2>DevGenie First-Time Setup</h2>
    <?php if (!$has_config): ?>
        <div class="error"><b>Configuration file not found.</b> Please run the install script to create <code>config/config.php</code>.</div>
    <?php elseif (!$can_connect): ?>
        <div class="error"><b>Could not connect to database.</b> Check your config and DB server.</div>
    <?php elseif ($admin_exists): ?>
        <div class="success"><b>Setup already complete.</b> <a href="login.php">Go to login</a></div>
    <?php else: ?>
        <?php if ($err): ?><div class="error"><?=esc($err)?></div><?php endif; ?>
        <form method="post" autocomplete="off">
            <label>Admin Username:
                <input type="text" name="username" autocomplete="username" required value="<?=esc($_POST['username'] ?? '')?>">
            </label>
            <label>Email Address:
                <input type="email" name="email" autocomplete="email" required value="<?=esc($_POST['email'] ?? '')?>">
            </label>
            <label>Password:
                <input type="password" name="password" autocomplete="new-password" required>
            </label>
            <label>Confirm Password:
                <input type="password" name="password2" autocomplete="new-password" required>
            </label>
            <button type="submit">Create Admin User</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
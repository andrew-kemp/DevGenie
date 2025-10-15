<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once(__DIR__ . '/../config/config.php');

function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $error = "Please enter both username and password.";
    } else {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM admins WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        $admin = $res->fetch_assoc();
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Success - set admin session and redirect to admin dashboard
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['is_admin'] = true;
            header("Location: admin/index.php");
            exit;
        } else {
            $error = "Invalid credentials.";
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - DevGenie Portal</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
    .login-container {
        max-width:350px; margin:2em auto; background:#f8faff; border-radius:12px; box-shadow:0 2px 16px #ccd8ee55; padding:2em 2.5em;
    }
    label { display:block; margin-top:1.3em; font-weight:500; }
    input[type="text"], input[type="password"] {
        width:95%; padding:8px; border:1px solid #b8c6d9; border-radius:5px; font-size:1em;
    }
    button { margin-top:1.4em; background:#2347ba; color:#fff; padding:10px 32px; border-radius:6px; border:none; font-weight:600; font-size:1.1em;}
    .error { color:#b20e3a; background:#ffe3e7; border:1px solid #ffb1c2; padding:8px 14px; border-radius:6px; margin-bottom:.8em;}
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if ($error) echo "<div class='error'>".esc($error)."</div>"; ?>
        <form method="post" autocomplete="off">
            <label>Username:<br>
                <input type="text" name="username" required autofocus>
            </label>
            <label>Password:<br>
                <input type="password" name="password" required>
            </label>
            <button type="submit">Sign In</button>
        </form>
        <p style="margin-top:2em"><a href="login.php">&laquo; Back to user login</a></p>
    </div>
</body>
</html>
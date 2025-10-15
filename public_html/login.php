<?php
// Redirect to setup.php if config is missing
if (!file_exists(__DIR__ . '/../config/config.php')) {
    header("Location: /setup.php");
    exit;
}
require_once(__DIR__ . '/../config/config.php');

// Check if admin exists in DB
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$res = $conn->query("SHOW TABLES LIKE 'admins'");
$admin_exists = false;
if ($res && $res->num_rows > 0) {
    $res2 = $conn->query("SELECT COUNT(*) as cnt FROM admins");
    $row = $res2 ? $res2->fetch_assoc() : null;
    $admin_exists = $row && $row['cnt'] > 0;
}
if (!$admin_exists) {
    header("Location: /setup.php");
    exit;
}
$conn->close();

session_start();
// Redirect already authenticated users
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/index.php");
    exit;
}
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign In - DevGenie Portal</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
    .wizard-guide-btn {
        display: block;
        width: 90%;
        max-width: 400px;
        margin: 1.5em auto;
        padding: 20px;
        font-size: 1.2em;
        font-weight: 700;
        text-align: center;
        background: #f5faff;
        color: #4263eb;
        border: 2px solid #b9c6f2;
        border-radius: 10px;
        text-decoration: none;
        transition: background 0.15s, box-shadow 0.15s;
        box-shadow: 0 2px 24px rgba(44,80,140,0.09);
    }
    .wizard-guide-btn:hover {
        background: #e8f0fe;
        color: #2c3f85;
        border-color: #4263eb;
        box-shadow: 0 4px 30px rgba(44,80,140,0.13);
    }
    .admin-login-section {
        max-width: 400px;
        margin: 2em auto;
        padding: 2em;
        background: #f9f9f9;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    hr {
        margin: 2em 0;
    }
    </style>
</head>
<body>
<div class="container" style="max-width:500px;">
    <h2>Sign In</h2>
    <a class="wizard-guide-btn" href="/saml/login.php"><b>Sign in with Entra SSO (SAML)</b></a>
    <hr>
    <div class="admin-login-section">
        <form method="post" action="admin_login.php">
            <h4>Admin Login (local)</h4>
            <label>Username:
                <input type="text" name="username" autocomplete="username" required>
            </label>
            <br>
            <label>Password:
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <br>
            <button type="submit">Sign In (Admin)</button>
        </form>
    </div>
</div>
</body>
</html>
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

if (isset($_SESSION['admin_id'])) {
    header("Location: admin/index.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Load user info if SAML user logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    require_once(__DIR__ . '/../db/users.php');
    $user = user_by_id($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>DevGenie Portal</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container" style="max-width:700px;">
    <h2>Welcome to DevGenie Portal</h2>
    <?php if ($user): ?>
        <p>Hello, <b><?=htmlspecialchars($user['display_name'])?></b> (<?=htmlspecialchars($user['dev_email'])?>)!</p>
        <?php if ($user['is_admin']): ?>
            <p><a href="admin/index.php">Go to Admin Dashboard</a></p>
        <?php endif; ?>
        <p><a href="profile.php">My Profile</a></p>
    <?php endif; ?>
    <p><a href="logout.php">Logout</a></p>
</div>
</body>
</html>
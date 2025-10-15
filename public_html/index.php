<?php
session_start();
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
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
            <p><a href="admin_users.php">Admin User Management</a></p>
        <?php endif; ?>
        <p><a href="profile.php">My Profile</a></p>
    <?php elseif (isset($_SESSION['admin_id'])): ?>
        <p>Logged in as <b>admin</b>.</p>
        <p><a href="admin_users.php">Admin User Management</a></p>
    <?php endif; ?>
    <p><a href="logout.php">Logout</a></p>
</div>
</body>
</html>
<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once(__DIR__ . '/../db/users.php');
$user = user_by_id($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>DevGenie Portal</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="portal-welcome">Welcome to DevGenie Portal</div>
    <p>Hello, <b><?=htmlspecialchars($user['display_name'])?></b> (<?=htmlspecialchars($user['dev_email'])?>)!</p>
    <div style="margin: 2em 0 1.3em 0;">
        <a class="portal-profile-link" href="profile.php">My Profile</a>
        <a class="portal-profile-link" href="request.php">New User Request</a>
        <a class="portal-profile-link" href="requests.php">My Requests</a>
        <a href="logout.php">Logout</a>
    </div>
    <?php if ($user['is_admin'] || $user['is_super_admin']): ?>
    <div style="margin-top:1.5em;">
        <a href="admin/index.php" class="admin-dashboard-btn">Go to Admin Dashboard</a>
    </div>
    <?php endif; ?>
    <?php if ($user['is_approver']): ?>
    <div style="margin-top:1.2em;">
        <a href="approver_dashboard.php" class="approver-dashboard-btn">Go to Approver Dashboard</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
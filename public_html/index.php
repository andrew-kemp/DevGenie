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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .dashboard-card {
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(44,80,140,0.10), 0 1.5px 8px rgba(44,80,140,0.08);
            max-width: 480px;
            margin: 3em auto 0 auto;
            padding: 2.4em 2em 2em 2em;
        }
        .portal-welcome {
            font-size: 1.6em;
            font-weight: 700;
            color: #2347ba;
            margin-bottom: 0.7em;
            text-align: center;
            letter-spacing: 0.01em;
        }
        .user-greeting {
            color: #232946;
            font-size: 1.08em;
            margin-bottom: 0.9em;
            text-align: center;
        }
        .main-menu {
            text-align: center;
            margin: 2.2em 0 1.1em 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 1.1em;
        }
        .main-menu a {
            background: #f3e8ff;
            color: #7c3aed;
            font-weight: 600;
            border-radius: 8px;
            border: 1.2px solid #e9d5ff;
            padding: 0.92em 1.3em;
            text-decoration: none;
            font-size: 1.09em;
            transition: background 0.14s, color 0.15s, border 0.13s;
            margin-bottom: 0.3em;
            display: inline-block;
        }
        .main-menu a:hover {
            background: #c7d2fe;
            color: #4c1d95;
            border-color: #a5b4fc;
        }
        .dashboard-extras {
            text-align: center;
            margin-top: 2.2em;
        }
        @media (max-width: 600px) {
            .dashboard-card { padding: 1.1em 0.7em 1.5em 0.7em; }
            .main-menu { flex-direction: column; gap: 0.8em; }
        }
    </style>
</head>
<body>
    <div class="dashboard-card">
        <div class="portal-welcome">Welcome to DevGenie Portal</div>
        <div class="user-greeting">
            Hello, <b><?=htmlspecialchars($user['display_name'])?></b>
            (<?=htmlspecialchars($user['dev_email'])?>)!
        </div>
        <div class="main-menu">
            <a href="profile.php">My Profile</a>
            <a href="request.php">New User Request</a>
            <a href="requests.php">My Requests</a>
            <a href="logout.php">Logout</a>
        </div>
        <?php if ($user['is_admin'] || $user['is_super_admin']): ?>
        <div class="dashboard-extras">
            <a href="admin/index.php" class="admin-dashboard-btn">Go to Admin Dashboard</a>
        </div>
        <?php endif; ?>
        <?php if ($user['is_approver']): ?>
        <div class="dashboard-extras">
            <a href="approver_dashboard.php" class="approver-dashboard-btn">Go to Approver Dashboard</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
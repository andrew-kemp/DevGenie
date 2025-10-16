<?php
// Enable error reporting for debugging during development (remove for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

// Load config and connect to DB
require_once(__DIR__ . '/../../config/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get all users for admin checkbox table
$user_res = $conn->query("SELECT id, display_name, dev_email, is_admin, is_approver, is_super_admin FROM users ORDER BY id ASC");
$users = [];
while ($row = $user_res->fetch_assoc()) {
    $users[] = $row;
}

// Handle form submission to update admin/approver/superadmin status
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Checkbox arrays
    $admin_ids = isset($_POST['admin_ids']) ? $_POST['admin_ids'] : [];
    $approver_ids = isset($_POST['approver_ids']) ? $_POST['approver_ids'] : [];
    $superadmin_ids = isset($_POST['superadmin_ids']) ? $_POST['superadmin_ids'] : [];

    // Update users
    foreach ($users as $user) {
        $is_admin = in_array($user['id'], $admin_ids) ? 1 : 0;
        $is_approver = in_array($user['id'], $approver_ids) ? 1 : 0;
        $is_super_admin = in_array($user['id'], $superadmin_ids) ? 1 : 0;

        // Ensure at least one super admin always exists
        if ($user['is_super_admin'] && !$is_super_admin) {
            $super_admin_count = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE is_super_admin=1")->fetch_assoc()['cnt'];
            if ($super_admin_count <= 1) {
                $is_super_admin = 1; // Prevent removal of last super admin
            }
        }

        $stmt = $conn->prepare(
            "UPDATE users SET is_admin=?, is_approver=?, is_super_admin=? WHERE id=?"
        );
        $stmt->bind_param("iiii", $is_admin, $is_approver, $is_super_admin, $user['id']);
        $stmt->execute();
        $stmt->close();
    }

    $success = "Admin/approver roles updated successfully.";
    // Refresh users
    $user_res = $conn->query("SELECT id, display_name, dev_email, is_admin, is_approver, is_super_admin FROM users ORDER BY id ASC");
    $users = [];
    while ($row = $user_res->fetch_assoc()) {
        $users[] = $row;
    }
}

$conn->close();

function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }

$admin_pages = [
    'index.php'             => 'Admin Dashboard',
    'saml_settings.php'     => 'SAML/Entra SSO Settings',
    'azure_settings.php'    => 'Azure App Registration',
    'keyvault_settings.php' => 'Key Vault / SMTP Settings',
    'users.php'             => 'User Management',
    'admin_users.php'       => 'Admin User Management',
];
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin User Management - DevGenie Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    .admin-main-card {
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 8px 32px rgba(44,80,140,0.10), 0 1.5px 8px rgba(44,80,140,0.08);
        max-width: 650px;
        margin: 2.5em auto 0 auto;
        padding: 2.2em 2.2em 2.4em 2.2em;
    }
    .admin-section-title {
        font-size: 1.33em;
        font-weight: 700;
        color: #1d2769;
        margin-bottom: 1.2em;
        letter-spacing: 0.01em;
    }
    .admin-update-btn {
        width: 100%;
        margin: 1.5em 0 1em 0;
        background: linear-gradient(90deg, #2347ba 60%, #4f8cff 100%);
        color: #fff;
        border: none;
        border-radius: 9px;
        padding: 14px 0;
        font-size: 1.13em;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 2px 12px #b6d1ff24;
        transition: background 0.14s;
        display: block;
    }
    .admin-update-btn:hover {
        background: linear-gradient(90deg,#193073 60%,#4f8cff 100%);
    }
    .admin-table-holder {
        margin-top: 2.2em;
        background: #f9fbfd;
        border-radius: 14px;
        box-shadow: 0 2px 10px #b6d1ff24;
        overflow-x: auto;
        padding: 1.2em;
    }
    .admin-table {
        width: 100%;
        border-radius: 10px;
        background: #f2f6ff;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 1em;
        margin-bottom: 0.5em;
    }
    .admin-table th, .admin-table td {
        padding: 12px 14px;
        text-align: left;
    }
    .admin-table th {
        background: #e6eaff;
        font-weight: 600;
        color: #193073;
        border-bottom: 2px solid #c7d3f7;
    }
    .admin-table tr:nth-child(even) {
        background: #fafdff;
    }
    .admin-table tr.highlight {
        background: #dbeafe !important;
    }
    .admin-checkbox-col {
        text-align: center;
    }
    .admin-table tr:hover {
        background: #e0eaff !important;
    }
    .success-msg {
        color: #257a3e;
        background: #e8ffe8;
        border: 1px solid #b7efb7;
        padding: .8em 1.3em;
        border-radius: 10px;
        margin-bottom: 1.2em;
        font-size: 1.05em;
        text-align: center;
    }
    @media (max-width: 700px) {
        .admin-main-card { padding: 1.2em 0.4em 1.3em 0.4em; }
        .admin-table-holder { padding: 0.5em; }
        .admin-table th, .admin-table td { padding: 7px 6px; }
    }
    </style>
</head>
<body>
<nav class="admin-nav">
    <?php foreach ($admin_pages as $file => $label): ?>
        <a href="<?= $file ?>" class="<?= $current_page === $file ? 'active' : '' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
    <a href="../index.php" class="nav-portal">Return to Portal</a>
</nav>
<div class="admin-main-card">
    <div class="admin-section-title">Admin User Management</div>
    <?php if ($success): ?>
        <div class="success-msg"><?=esc($success)?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <div class="admin-table-holder">
            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email (UPN)</th>
                    <th>Admin</th>
                    <th>Approver</th>
                    <th>Super Admin</th>
                </tr>
                <?php foreach ($users as $user): ?>
                    <tr<?= $user['is_super_admin'] ? ' class="highlight"' : '' ?>>
                        <td><?=esc($user['id'])?></td>
                        <td><?=esc($user['display_name'])?></td>
                        <td><?=esc($user['dev_email'])?></td>
                        <td class="admin-checkbox-col">
                            <input type="checkbox" name="admin_ids[]" value="<?=esc($user['id'])?>" <?= $user['is_admin'] ? 'checked' : '' ?>>
                        </td>
                        <td class="admin-checkbox-col">
                            <input type="checkbox" name="approver_ids[]" value="<?=esc($user['id'])?>" <?= $user['is_approver'] ? 'checked' : '' ?>>
                        </td>
                        <td class="admin-checkbox-col">
                            <input type="checkbox" name="superadmin_ids[]" value="<?=esc($user['id'])?>" <?= $user['is_super_admin'] ? 'checked' : '' ?> <?= $user['is_super_admin'] && count(array_filter($users, fn($u) => $u['is_super_admin'])) === 1 ? 'disabled title="At least one super admin required"' : '' ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <button type="submit" class="admin-update-btn">Update Admin/Approver Roles</button>
    </form>
    <div style="margin-top:1.3em;font-size:0.97em;color:#444;">
        <span style="color:#7c3aed;font-weight:600;">Note:</span> At least one super admin must always exist.<br>
        Super admins can manage all settings and users.
    </div>
</div>
</body>
</html>
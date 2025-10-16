<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
require_once(__DIR__ . '/../../config/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$res = $conn->query("SELECT id, display_name, dev_email, prod_email, is_admin, is_approver, is_super_admin, created_at FROM users ORDER BY created_at DESC");
$users = [];
while ($row = $res->fetch_assoc()) $users[] = $row;
$conn->close();

$admin_pages = [
    'index.php'             => 'Admin Dashboard',
    'saml_settings.php'     => 'SAML/Entra SSO Settings',
    'azure_settings.php'    => 'Azure App Registration',
    'keyvault_settings.php' => 'Key Vault / SMTP Settings',
    'users.php'             => 'User Management',
    'admin_users.php'       => 'Admin User Management',
];
$current_page = basename($_SERVER['PHP_SELF']);
function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management - DevGenie Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    .admin-main-card {
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 8px 32px rgba(44,80,140,0.10), 0 1.5px 8px rgba(44,80,140,0.08);
        max-width: 820px;
        margin: 2.5em auto 0 auto;
        padding: 2.1em 2em 2.3em 2em;
    }
    .admin-section-title {
        font-size: 1.33em;
        font-weight: 700;
        color: #1d2769;
        margin-bottom: 1.2em;
        letter-spacing: 0.01em;
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
    @media (max-width: 900px) {
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
    <div class="admin-section-title">User Management</div>
    <div class="admin-table-holder">
        <table class="admin-table">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Dev Email</th>
                <th>Prod Email</th>
                <th>Roles</th>
                <th>Created</th>
            </tr>
            <?php foreach ($users as $user): ?>
            <tr class="<?= $user['is_super_admin'] ? 'highlight' : '' ?>">
                <td><?= esc($user['id']) ?></td>
                <td><?= esc($user['display_name']) ?></td>
                <td><?= esc($user['dev_email']) ?></td>
                <td><?= esc($user['prod_email']) ?></td>
                <td>
                    <?= $user['is_super_admin'] ? '<b style="color:#7c3aed;">Super Admin</b><br>' : '' ?>
                    <?= $user['is_admin'] ? 'Admin<br>' : '' ?>
                    <?= $user['is_approver'] ? 'Approver<br>' : '' ?>
                </td>
                <td><?= esc($user['created_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
require_once(__DIR__ . '/../../config/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Get current admin_emails setting
$admin_emails = '';
$res = $conn->query("SELECT setting_value FROM settings WHERE setting_key='admin_emails'");
if ($row = $res->fetch_assoc()) {
    $admin_emails = $row['setting_value'];
}

// Get all users
$user_res = $conn->query("SELECT id, display_name, dev_email, is_admin FROM users ORDER BY id ASC");
$users = [];
while ($row = $user_res->fetch_assoc()) {
    $users[] = $row;
}

// On POST, compute new admin email list from union of textarea + checkboxes
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Gather emails from textarea
    $input = trim($_POST['admin_emails'] ?? '');
    $emails_from_text = array_filter(array_map('trim', preg_split('/[\n,]+/', $input)));
    // Gather emails from checkboxes
    $emails_from_check = isset($_POST['admin_checkbox']) && is_array($_POST['admin_checkbox'])
        ? array_map('trim', $_POST['admin_checkbox'])
        : [];
    // Union, dedupe, normalize (lowercase)
    $emails = array_unique(array_map('strtolower', array_merge($emails_from_text, $emails_from_check)));
    $emails_str = implode(',', $emails);

    // Upsert into settings
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_emails', ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $stmt->bind_param("s", $emails_str);
    $stmt->execute();
    $stmt->close();

    $admin_emails = $emails_str;
    $success = "Updated admin user list.";

    // Refresh user list to reflect changes immediately
    $user_res = $conn->query("SELECT id, display_name, dev_email, is_admin FROM users ORDER BY id ASC");
    $users = [];
    while ($row = $user_res->fetch_assoc()) {
        $users[] = $row;
    }
}

$conn->close();

function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }
$admin_emails_arr = array_map('strtolower', array_map('trim', explode(',', $admin_emails)));

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
    <title>Admin User Management (UPN/Email List)</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    .admin-main-card {
        background: #fff;
        border-radius: 22px;
        box-shadow: 0 8px 32px rgba(44,80,140,0.10), 0 1.5px 8px rgba(44,80,140,0.08);
        max-width: 570px;
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
    .admin-label {
        font-weight: 600;
        color: #232946;
        margin-bottom: 0.33em;
        display: block;
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
    hr {
        border: 0;
        border-top: 1.5px solid #e4e4e4;
        margin: 2.1em 0 1.5em 0;
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
    <div class="admin-section-title">Admin User Management (UPN/Email List)</div>
    <?php if ($success): ?>
        <div class="success-msg"><?=esc($success)?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
        <label for="admin_emails" class="admin-label">
            Admin UPNs/Emails <span style="font-weight:400">(one per line or comma separated):</span>
        </label>
        <textarea name="admin_emails" id="admin_emails" rows="5" style="width:100%;max-width:100%;"></textarea>
        <button type="submit" class="admin-update-btn">Update Admin List</button>
        <hr>
        <div style="font-weight:700;font-size:1.08em;margin-bottom:0.8em;">All Users (Check to make admin)</div>
        <div class="admin-table-holder">
            <table class="admin-table">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email (UPN)</th>
                    <th>Admin?</th>
                    <th class="admin-checkbox-col">Admin<br>Checkbox</th>
                </tr>
                <?php foreach ($users as $user):
                    $is_listed_admin = in_array(strtolower($user['dev_email']), $admin_emails_arr);
                    ?>
                    <tr class="<?= $is_listed_admin ? 'highlight' : '' ?>">
                        <td><?=esc($user['id'])?></td>
                        <td><?=esc($user['display_name'])?></td>
                        <td><?=esc($user['dev_email'])?></td>
                        <td><?= $user['is_admin'] ? "Yes" : ($is_listed_admin ? "<b>Will be admin on next login</b>" : "No") ?></td>
                        <td class="admin-checkbox-col">
                            <input type="checkbox" name="admin_checkbox[]" value="<?=esc($user['dev_email'])?>" <?= $is_listed_admin ? 'checked' : '' ?>>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <button type="submit" class="admin-update-btn">Update Admin List</button>
    </form>
</div>
</body>
</html>
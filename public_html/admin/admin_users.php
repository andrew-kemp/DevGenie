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
</head>
<body>
<nav class="admin-nav">
    <?php foreach ($admin_pages as $file => $label): ?>
        <a href="<?= $file ?>" class="<?= $current_page === $file ? 'active' : '' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
    <?php endforeach; ?>
    <a href="../index.php">Return to Portal</a>
</nav>
<div class="container" style="max-width:700px;">
    <h2>Admin User Management (UPN/Email List)</h2>
    <?php if ($success): ?>
        <div class="success-msg"><?=esc($success)?></div>
    <?php endif; ?>

    <form method="post">
        <label for="admin_emails"><b>Admin UPNs/Emails</b> (one per line or comma separated):</label><br>
        <textarea name="admin_emails" id="admin_emails" rows="6" style="width:100%;max-width:500px;"><?=esc(str_replace(',', "\n", $admin_emails))?></textarea>
        <br>
        <button type="submit" style="margin-top:1em;">Update Admin List</button>
        <hr>
        <h3>All Users (Check to make admin)</h3>
        <table border="1" cellpadding="6" style="width:100%; margin-bottom:2em;" class="admin-table">
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
        <button type="submit">Update Admin List</button>
    </form>
</div>
</body>
</html>
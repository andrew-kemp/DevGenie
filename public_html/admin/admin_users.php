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

// Handle update
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['admin_emails'] ?? '');
    // Normalize: comma or newline separated, remove spaces
    $emails = array_filter(array_map('trim', preg_split('/[\n,]+/', $input)));
    $emails_str = implode(',', $emails);

    // Upsert into settings
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_emails', ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $stmt->bind_param("s", $emails_str);
    $stmt->execute();
    $stmt->close();

    $admin_emails = $emails_str;
    $success = "Updated admin user list.";
}

// Optionally, show currently matched admin users
$user_res = $conn->query("SELECT id, display_name, dev_email, is_admin FROM users ORDER BY id ASC");
$users = [];
while ($row = $user_res->fetch_assoc()) {
    $users[] = $row;
}
$conn->close();

function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin User Management (by UPN/Email)</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="max-width:700px;">
    <h2>Admin User Management (UPN/Email List)</h2>
    <?php if ($success): ?>
        <div style="color:green;padding:.5em 1em;background:#e8ffe8;border:1px solid #b7efb7;"><?=esc($success)?></div>
    <?php endif; ?>

    <form method="post">
        <label for="admin_emails"><b>Admin UPNs/Emails</b> (one per line or comma separated):</label><br>
        <textarea name="admin_emails" id="admin_emails" rows="6" style="width:100%;max-width:500px;"><?=esc(str_replace(',', "\n", $admin_emails))?></textarea>
        <br>
        <button type="submit" style="margin-top:1em;">Update Admin List</button>
    </form>
    <hr>
    <h3>Current Users Matching Admin List</h3>
    <table border="1" cellpadding="6" style="width:100%; margin-bottom:2em;">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email (UPN)</th>
            <th>Admin?</th>
        </tr>
        <?php
        $admin_emails_arr = array_map('strtolower', array_map('trim', explode(',', $admin_emails)));
        foreach ($users as $user):
            $is_listed_admin = in_array(strtolower($user['dev_email']), $admin_emails_arr);
        ?>
        <tr style="<?= $is_listed_admin ? 'background:#e5f5ff;' : '' ?>">
            <td><?=esc($user['id'])?></td>
            <td><?=esc($user['display_name'])?></td>
            <td><?=esc($user['dev_email'])?></td>
            <td><?= $user['is_admin'] ? "Yes" : ($is_listed_admin ? "<b>Will be admin on next login</b>" : "No") ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><a href="index.php">&laquo; Back to Admin Dashboard</a></p>
</div>
</body>
</html>
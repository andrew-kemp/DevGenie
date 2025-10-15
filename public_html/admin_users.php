<?php
session_start();
require_once(__DIR__ . '/../db/users.php');
function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    if (isset($_POST['make_admin'])) {
        update_user($user_id, ['is_admin'=>1]);
    } elseif (isset($_POST['remove_admin'])) {
        update_user($user_id, ['is_admin'=>0]);
    } elseif (isset($_POST['delete_user'])) {
        delete_user($user_id);
    }
}

$users = all_users();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - User Management</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container" style="max-width:800px;">
    <h2>User Management</h2>
    <table border="1" width="100%" cellpadding="6" style="background:#fff;">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Dev Email</th>
            <th>Prod Email</th>
            <th>Admin?</th>
            <th>Preference</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?=esc($u['id'])?></td>
            <td><?=esc($u['display_name'])?></td>
            <td><?=esc($u['dev_email'])?></td>
            <td><?=esc($u['prod_email'])?></td>
            <td><?=esc($u['is_admin']) ? 'Yes' : 'No'?></td>
            <td><?=esc($u['notification_email_preference'])?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?=esc($u['id'])?>">
                    <?php if (!$u['is_admin']): ?>
                        <button name="make_admin">Make Admin</button>
                    <?php else: ?>
                        <button name="remove_admin">Remove Admin</button>
                    <?php endif; ?>
                    <button name="delete_user" onclick="return confirm('Delete user?')">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>
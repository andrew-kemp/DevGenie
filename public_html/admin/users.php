<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}
require_once(__DIR__ . '/../config/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$res = $conn->query("SELECT id, display_name, dev_email, is_admin FROM users ORDER BY id DESC");
$users = [];
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}
$conn->close();
function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container" style="max-width:700px;">
    <h2>User Management</h2>
    <table border="1" cellpadding="6" style="width:100%; margin-bottom:2em;">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Admin</th>
        </tr>
        <?php foreach($users as $user): ?>
        <tr>
            <td><?=esc($user['id'])?></td>
            <td><?=esc($user['display_name'])?></td>
            <td><?=esc($user['dev_email'])?></td>
            <td><?=esc($user['is_admin']) ? "Yes" : "No"?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p><a href="index.php">&laquo; Back to Admin Dashboard</a></p>
</div>
</body>
</html>
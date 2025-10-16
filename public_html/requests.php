<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once(__DIR__ . '/../config/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$stmt = $conn->prepare("SELECT * FROM requests WHERE requester_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$res = $stmt->get_result();
$requests = [];
while ($row = $res->fetch_assoc()) $requests[] = $row;
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Requests - DevGenie Portal</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>My Requests</h2>
    <table class="admin-table">
        <tr>
            <th>Date</th><th>Colleague</th><th>Email</th><th>Status</th><th>Approver</th><th>Comment</th>
        </tr>
        <?php foreach ($requests as $r): ?>
        <tr>
            <td><?=htmlspecialchars($r['created_at'])?></td>
            <td><?=htmlspecialchars($r['req_first_name']." ".$r['req_last_name'])?></td>
            <td><?=htmlspecialchars($r['req_prod_email'])?></td>
            <td><?=ucfirst($r['status'])?></td>
            <td><?=htmlspecialchars($r['approver_id'] ?? '')?></td>
            <td><?=htmlspecialchars($r['approval_comment'] ?? '')?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div><a href="index.php">&laquo; Back to Portal</a></div>
</div>
</body>
</html>
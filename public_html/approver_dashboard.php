<?php
session_start();
require_once(__DIR__ . '/../db/users.php');
$user = user_by_id($_SESSION['user_id'] ?? 0);
if (!$user || !$user['is_approver']) {
    header("Location: index.php");
    exit;
}
require_once(__DIR__ . '/../config/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action'];
    $comment = trim($_POST['comment'] ?? '');
    if ($action == 'reject' && !$comment) {
        $err = "Comment required for rejection.";
    } else {
        $status = $action == 'approve' ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE requests SET status=?, approver_id=?, approval_comment=? WHERE id=?");
        $stmt->bind_param("sisi", $status, $user['id'], $comment, $request_id);
        $stmt->execute();
        // TODO: On approval, automate TAP creation, email, etc.
        $stmt->close();
        $msg = "Request $status.";
    }
}

$res = $conn->query("SELECT r.*, u.display_name as requester_name FROM requests r JOIN users u ON r.requester_id=u.id WHERE r.status='pending' ORDER BY created_at ASC");
$pending = [];
while ($row = $res->fetch_assoc()) $pending[] = $row;
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Approver Dashboard - DevGenie Portal</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <h2>Requests Awaiting Approval</h2>
    <?php if (!empty($msg)): ?><div class="success-msg"><?=htmlspecialchars($msg)?></div><?php endif; ?>
    <table class="admin-table">
        <tr>
            <th>Date</th><th>Requester</th><th>Colleague</th><th>Email</th><th>Action</th>
        </tr>
        <?php foreach ($pending as $r): ?>
        <tr>
            <td><?=htmlspecialchars($r['created_at'])?></td>
            <td><?=htmlspecialchars($r['requester_name'])?></td>
            <td><?=htmlspecialchars($r['req_first_name']." ".$r['req_last_name'])?></td>
            <td><?=htmlspecialchars($r['req_prod_email'])?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="request_id" value="<?=$r['id']?>">
                    <input type="text" name="comment" placeholder="Comment (required if rejecting)" style="width:140px;">
                    <button name="action" value="approve">Approve</button>
                    <button name="action" value="reject" style="background:#c02626;">Reject</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div><a href="index.php">&laquo; Back to Portal</a></div>
</div>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
require_once(__DIR__ . '/../db/users.php');
$user = user_by_id($_SESSION['user_id']);
$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $prod_email = trim($_POST['prod_email'] ?? '');
    $my_prod_email = trim($_POST['my_prod_email'] ?? '');

    if (!$first || !$last || !$prod_email || !$my_prod_email) {
        $msg = "All fields are required.";
    } elseif (!filter_var($prod_email, FILTER_VALIDATE_EMAIL) || !filter_var($my_prod_email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please enter valid email addresses.";
    } else {
        require_once(__DIR__ . '/../config/config.php');
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        // Optionally update user's prod_email
        $stmt = $conn->prepare("UPDATE users SET prod_email=? WHERE id=?");
        $stmt->bind_param("si", $my_prod_email, $user['id']);
        $stmt->execute();
        $stmt->close();

        // Insert request
        $stmt = $conn->prepare("INSERT INTO requests (requester_id, req_first_name, req_last_name, req_prod_email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user['id'], $first, $last, $prod_email);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        $msg = "Request submitted!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Request New User - DevGenie Portal</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container" style="max-width:480px;">
    <h2>Request a New User Account</h2>
    <?php if ($msg): ?><div class="success-msg"><?=htmlspecialchars($msg)?></div><?php endif; ?>
    <form method="post">
        <label>Your production email (for notifications):</label>
        <input type="email" name="my_prod_email" value="<?=htmlspecialchars($user['prod_email'] ?? '')?>" required>
        <label>Colleague's production email:</label>
        <input type="email" name="prod_email" required>
        <label>Colleague's first name:</label>
        <input type="text" name="first_name" required>
        <label>Colleague's last name:</label>
        <input type="text" name="last_name" required>
        <button type="submit">Submit Request</button>
    </form>
    <div style="margin-top:1.5em;"><a href="index.php">&laquo; Back to Portal</a></div>
</div>
</body>
</html>
<?php
session_start();
require_once(__DIR__ . '/../db/users.php');
function esc($x) { return htmlspecialchars($x ?? '', ENT_QUOTES); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user = user_by_id($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [];
    if (isset($_POST['prod_email'])) $fields['prod_email'] = trim($_POST['prod_email']);
    if (isset($_POST['notification_email_preference'])) $fields['notification_email_preference'] = $_POST['notification_email_preference'];
    if ($fields) update_user($user['id'], $fields);
    $user = user_by_id($user['id']); // reload
    $feedback = "Profile updated!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container" style="max-width:500px;">
    <h2>My Profile</h2>
    <?php if (!empty($feedback)) echo "<div class='feedback'>$feedback</div>"; ?>
    <form method="post">
        <label>Display Name:<br>
          <input type="text" value="<?=esc($user['display_name'])?>" readonly>
        </label>
        <label>Signed-in (dev) email:<br>
          <input type="email" value="<?=esc($user['dev_email'])?>" readonly>
        </label>
        <label>Alternate notification email (prod):<br>
          <input type="email" name="prod_email" value="<?=esc($user['prod_email'])?>">
        </label>
        <label>Notification preference:<br>
            <input type="radio" name="notification_email_preference" value="dev" <?=($user['notification_email_preference']=='dev'?'checked':'')?>> Signed-in account<br>
            <input type="radio" name="notification_email_preference" value="prod" <?=($user['notification_email_preference']=='prod'?'checked':'')?>> Alternate email<br>
            <input type="radio" name="notification_email_preference" value="both" <?=($user['notification_email_preference']=='both'?'checked':'')?>> Both<br>
        </label>
        <button type="submit">Save Profile</button>
    </form>
</div>
</body>
</html>
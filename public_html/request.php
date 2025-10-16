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
        $stmt = $conn->prepare("INSERT INTO requests (first_name, last_name, external_email, requester_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $first, $last, $prod_email, $user['id']);
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
    <title>Request a New User - DevGenie Portal</title>
    <link rel="stylesheet" href="assets/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .form-card {
            background: #f9fbfd;
            border-radius: 18px;
            box-shadow: 0 2px 16px #b6d1ff22;
            padding: 2.2em 2em 2em 2em;
            max-width: 430px;
            margin: 0 auto;
        }
        .form-title {
            font-size: 1.45em;
            font-weight: 700;
            color: #2347ba;
            margin-bottom: 0.5em;
            letter-spacing: 0.01em;
            text-align: center;
        }
        .form-desc {
            color: #555e73;
            font-size: 1.02em;
            margin-bottom: 1.7em;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1.25em;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 0.4em;
            display: block;
            color: #1e293b;
        }
        .form-group input[type="email"], .form-group input[type="text"] {
            width: 100%;
            padding: 11px 13px;
            border: 1.4px solid #b6c6d9;
            border-radius: 7px;
            font-size: 1em;
            background: #fff;
            transition: border 0.14s;
        }
        .form-group input[type="email"]:focus, .form-group input[type="text"]:focus {
            border-color: #2563eb;
            outline: none;
        }
        .form-actions {
            margin-top: 1.7em;
        }
        .form-actions button {
            background: linear-gradient(90deg, #2347ba 60%, #4f8cff 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 13px 0;
            width: 100%;
            font-size: 1.08em;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 10px #b6d1ff24;
            transition: background 0.14s;
        }
        .form-actions button:hover {
            background: linear-gradient(90deg,#193073 60%,#4f8cff 100%);
        }
        .form-footer {
            margin-top: 2em;
            text-align: center;
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
        @media (max-width: 600px) {
            .form-card { padding: 1.1em 0.7em 1.5em 0.7em; }
        }
    </style>
</head>
<body>
<div class="container" style="background:none;box-shadow:none;padding:0;max-width:100%;">
    <div class="form-card">
        <div class="form-title">Request a New User Account</div>
        <div class="form-desc">
            Fill in your colleague's details. They will receive a TAP (Temporary Access Pass) at their production email.
        </div>
        <?php if ($msg): ?><div class="success-msg"><?=htmlspecialchars($msg)?></div><?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="form-group">
                <label for="my_prod_email">Your production email (for notifications):</label>
                <input type="email" name="my_prod_email" id="my_prod_email" value="<?=htmlspecialchars($user['prod_email'] ?? '')?>" required>
            </div>
            <div class="form-group">
                <label for="prod_email">Colleague's production email:</label>
                <input type="email" name="prod_email" id="prod_email" required>
            </div>
            <div class="form-group">
                <label for="first_name">Colleague's first name:</label>
                <input type="text" name="first_name" id="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Colleague's last name:</label>
                <input type="text" name="last_name" id="last_name" required>
            </div>
            <div class="form-actions">
                <button type="submit">Submit Request</button>
            </div>
        </form>
        <div class="form-footer">
            <a href="index.php">&laquo; Back to Portal</a>
        </div>
    </div>
</div>
</body>
</html>
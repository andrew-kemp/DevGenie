<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign In - DevGenie Portal</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
    .wizard-guide-btn {
        display: block;
        width: 90%;
        max-width: 400px;
        margin: 1.5em auto;
        padding: 20px;
        font-size: 1.2em;
        font-weight: 700;
        text-align: center;
        background: #f5faff;
        color: #4263eb;
        border: 2px solid #b9c6f2;
        border-radius: 10px;
        text-decoration: none;
        transition: background 0.15s, box-shadow 0.15s;
        box-shadow: 0 2px 24px rgba(44,80,140,0.09);
    }
    .wizard-guide-btn:hover {
        background: #e8f0fe;
        color: #2c3f85;
        border-color: #4263eb;
        box-shadow: 0 4px 30px rgba(44,80,140,0.13);
    }
    </style>
</head>
<body>
<div class="container" style="max-width:400px;">
    <h2>Sign In</h2>
    <a class="wizard-guide-btn" href="/entra_sso/login"><b>Sign in with Entra SSO</b></a>
    <hr>
    <form method="post" action="admin_login.php">
        <h4>Admin Login (local)</h4>
        <label>Username: <input type="text" name="username"></label>
        <label>Password: <input type="password" name="password"></label>
        <button type="submit">Sign In (Admin)</button>
    </form>
</div>
</body>
</html>
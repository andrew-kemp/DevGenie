<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
if (isset($_SESSION['admin_id'])) {
    header("Location: admin/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign In - DevGenie Portal</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="assets/style.css">
    <script>
        // Attempt SSO login automatically after DOM loads
        window.onload = function() {
            window.location.href = "/saml/login.php";
        };
    </script>
    <style>
    .admin-link {
        position: fixed;
        bottom: 18px;
        right: 24px;
        background: #ede9fe;
        color: #7c3aed;
        padding: 7px 16px;
        border-radius: 7px;
        border: 1.3px solid #e9d5ff;
        font-weight: 600;
        font-size: 1em;
        text-decoration: none;
        z-index: 100;
        box-shadow: 0 2px 12px #b6d1ff24;
    }
    .admin-link:hover {
        background: #c7d2fe;
        color: #4c1d95;
        border-color: #a5b4fc;
    }
    .sso-btn {
        display: block;
        width: 100%;
        font-size: 1.18em;
        padding: 21px 0;
        background: linear-gradient(90deg,#e0ecfc 0,#dbeafe 100%);
        color: #1d2769;
        font-weight: 700;
        border: 2px solid #3b82f6;
        border-radius: 14px;
        margin: 0 auto 2.2em auto;
        box-shadow: 0 2px 18px #b6d1ff22;
        cursor: pointer;
        text-align: center;
        transition: background 0.14s, border 0.13s, box-shadow 0.14s, color 0.14s;
        text-decoration: none;
    }
    .sso-btn:hover {
        background: linear-gradient(90deg,#dbeafe 0,#bae6fd 100%);
        border-color: #2563eb;
        color: #193073;
        box-shadow: 0 4px 24px #a5b4fc33;
    }
    </style>
</head>
<body>
<div class="container" style="max-width:420px;">
    <h2 style="margin-bottom:2em;">Sign In</h2>
    <noscript>
        <a class="sso-btn" href="/saml/login.php">Sign in with Entra SSO (SAML)</a>
        <div style="color:#b20e3a; background:#ffe3e7; border:1px solid #ffb1c2; border-radius:7px; padding:9px 13px; font-size:1.08em;">
            JavaScript is required for auto-login. Click the button above to sign in.
        </div>
    </noscript>
    <a href="/admin/" class="admin-link">Admin login</a>
</div>
</body>
</html>
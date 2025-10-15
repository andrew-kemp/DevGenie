<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use OneLogin\Saml2\Auth;

session_start();

$settings = require __DIR__ . '/settings.php';
$auth = new Auth($settings);
$auth->processResponse();

$errors = $auth->getErrors();
if (!empty($errors)) {
    echo "SAML Authentication error: " . implode(', ', $errors);
    exit;
}
if (!$auth->isAuthenticated()) {
    echo "SAML Authentication failed.";
    exit;
}

$email = $auth->getNameId();
$attributes = $auth->getAttributes();
$display_name = $attributes['name'][0] ?? ($attributes['displayName'][0] ?? ($attributes['givenname'][0] ?? $email));

// Security: Basic email format validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email address in SAML response.";
    exit;
}

require_once(__DIR__ . '/../../config/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Use prepared statements everywhere
$stmt = $conn->prepare("SELECT id, is_admin FROM users WHERE dev_email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($user = $res->fetch_assoc()) {
    $uid = $user['id'];
    $is_admin = $user['is_admin'];
    $stmt2 = $conn->prepare("UPDATE users SET display_name=? WHERE id=?");
    $stmt2->bind_param("si", $display_name, $uid);
    $stmt2->execute();
    $stmt2->close();
} else {
    $admin_emails = [];
    $ares = $conn->query("SELECT setting_value FROM settings WHERE setting_key='admin_emails'");
    if ($row = $ares->fetch_assoc()) {
        $admin_emails = array_map('trim', explode(',', $row['setting_value']));
    }
    $is_admin = in_array(strtolower($email), array_map('strtolower', $admin_emails)) ? 1 : 0;
    $stmt2 = $conn->prepare("INSERT INTO users (display_name, dev_email, is_admin) VALUES (?, ?, ?)");
    $stmt2->bind_param("ssi", $display_name, $email, $is_admin);
    $stmt2->execute();
    $uid = $conn->insert_id;
    $stmt2->close();
}
$stmt->close();
$conn->close();

$_SESSION['user_id'] = $uid;
$_SESSION['is_admin'] = $is_admin;

// Redirect based on role
if ($is_admin) {
    header("Location: /admin/index.php");
} else {
    header("Location: /index.php");
}
exit;
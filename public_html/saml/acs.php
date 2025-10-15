<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use OneLogin\Saml2\Auth;

session_start();
$settings = require __DIR__ . '/settings.php';
$auth = new Auth($settings);
$auth->processResponse();

if (!$auth->isAuthenticated()) {
    die('SAML Authentication failed.');
}

$attributes = $auth->getAttributes();
$email = $auth->getNameId();

// You now have the authenticated user. Provision session here.
$_SESSION['user_email'] = $email;
// ...load or create user, set session, redirect, etc.

header('Location: /index.php');
exit;
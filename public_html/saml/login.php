<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use OneLogin\Saml2\Auth;

$settings = require __DIR__ . '/settings.php';
$auth = new Auth($settings);

// If you want a RelayState, provide it as a parameter.
// $auth->login('https://yourdomain/after_login.php');
$auth->login();
exit;
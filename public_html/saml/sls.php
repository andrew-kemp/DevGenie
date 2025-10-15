<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use OneLogin\Saml2\Auth;

session_start();

$settings = require __DIR__ . '/settings.php';
$auth = new Auth($settings);

$auth->processSLO(); // This takes care of SAML Single Logout if supported

// Clear local session (for good measure)
session_unset();
session_destroy();

header("Location: /login.php");
exit;
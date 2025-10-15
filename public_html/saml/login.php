<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use OneLogin\Saml2\Auth;

$settings = require __DIR__ . '/settings.php';
$auth = new Auth($settings);

$auth->login(); // Redirects to Entra SAML SSO
exit;
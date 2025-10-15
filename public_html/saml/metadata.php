<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use OneLogin\Saml2\Settings;

$settings = require __DIR__ . '/settings.php';
$samlSettings = new Settings($settings, true);

header('Content-Type: text/xml');
echo $samlSettings->getSPMetadata();
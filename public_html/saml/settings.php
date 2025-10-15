<?php
require_once(__DIR__ . '/../../config/config.php');
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$res = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'saml_%' OR setting_key LIKE 'idp_%'");
$settings = [];
while ($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$conn->close();

return [
    'strict' => true,
    'debug' => true,
    'sp' => [
        'entityId' => $settings['saml_entity_id'] ?? '',
        'assertionConsumerService' => [
            'url' => $settings['saml_acs_url'] ?? '',
        ],
        'singleLogoutService' => [
            'url' => $settings['saml_sls_url'] ?? '',
        ],
        'NameIDFormat' => $settings['saml_nameid_format'] ?? 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
        // 'x509cert' => $settings['saml_x509cert'] ?? '',
        // 'privateKey' => $settings['saml_private_key'] ?? '',
    ],
    'idp' => [
        'entityId' => $settings['idp_entity_id'] ?? '',
        'singleSignOnService' => [
            'url' => $settings['idp_sso_url'] ?? '',
        ],
        'singleLogoutService' => [
            'url' => $settings['idp_slo_url'] ?? '',
        ],
        'x509cert' => $settings['idp_x509cert'] ?? '',
    ],
];
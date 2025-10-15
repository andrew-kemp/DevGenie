<?php
return [
    'strict' => true,
    'debug' => true,
    'sp' => [
        'entityId' => 'https://YOUR_DOMAIN/saml/metadata.php',
        'assertionConsumerService' => [
            'url' => 'https://YOUR_DOMAIN/saml/acs.php',
        ],
        'singleLogoutService' => [
            'url' => 'https://YOUR_DOMAIN/saml/sls.php',
        ],
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
        // 'x509cert' => '',
        // 'privateKey' => '',
    ],
    'idp' => [
        'entityId' => 'https://sts.windows.net/{TENANT_ID}/',
        'singleSignOnService' => [
            'url' => 'https://login.microsoftonline.com/{TENANT_ID}/saml2',
        ],
        'singleLogoutService' => [
            'url' => 'https://login.microsoftonline.com/{TENANT_ID}/saml2',
        ],
        'x509cert' => 'MIIC...THE_LONG_IDP_CERTIFICATE...',
    ],
];
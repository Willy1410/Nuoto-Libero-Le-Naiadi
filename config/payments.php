<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';
appLoadEnvFile(dirname(__DIR__) . '/.env');

return [
    'stripe' => [
        'publishable_key' => appEnv('STRIPE_PUBLISHABLE_KEY', ''),
        'secret_key' => appEnv('STRIPE_SECRET_KEY', ''),
        'webhook_secret' => appEnv('STRIPE_WEBHOOK_SECRET', ''),
        'mode' => appEnv('STRIPE_MODE', 'test'),
    ],
    'paypal' => [
        'client_id' => appEnv('PAYPAL_CLIENT_ID', ''),
        'client_secret' => appEnv('PAYPAL_CLIENT_SECRET', ''),
        'mode' => appEnv('PAYPAL_MODE', 'sandbox'),
        'webhook_id' => appEnv('PAYPAL_WEBHOOK_ID', ''),
    ],
    'bonifico' => [
        'intestatario' => appEnv('BONIFICO_INTESTATARIO', 'Nuoto Libero SSD'),
        'iban' => appEnv('BONIFICO_IBAN', ''),
        'banca' => appEnv('BONIFICO_BANCA', ''),
        'causale_template' => appEnv('BONIFICO_CAUSALE_TEMPLATE', 'Nome Cognome - Pacchetto - Email'),
        'email_conferma' => appEnv('BONIFICO_EMAIL_CONFERMA', ''),
    ],
];

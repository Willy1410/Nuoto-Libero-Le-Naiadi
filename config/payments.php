<?php
declare(strict_types=1);

/**
 * Configurazione pagamenti (solo test locale)
 */

return [
    'stripe' => [
        'publishable_key' => 'pk_test_YOUR_STRIPE_PUBLISHABLE_KEY',
        'secret_key' => 'sk_test_YOUR_STRIPE_SECRET_KEY',
        'webhook_secret' => 'whsec_YOUR_STRIPE_WEBHOOK_SECRET',
        'mode' => 'test',
    ],
    'paypal' => [
        'client_id' => 'YOUR_PAYPAL_SANDBOX_CLIENT_ID',
        'client_secret' => 'YOUR_PAYPAL_SANDBOX_CLIENT_SECRET',
        'mode' => 'sandbox',
        'webhook_id' => 'YOUR_PAYPAL_SANDBOX_WEBHOOK_ID',
    ],
    'bonifico' => [
        'intestatario' => 'Nuoto Libero SSD',
        'iban' => 'IT00X0000000000000000000000',
        'banca' => 'Banca di Test Locale',
        'causale_template' => 'Nome Cognome - Pacchetto - Email',
        'email_conferma' => 'inserisci-la-tua-gmail@gmail.com',
    ],
];
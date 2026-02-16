<?php
declare(strict_types=1);

/**
 * Configurazione SMTP locale (XAMPP)
 *
 * IMPORTANTE:
 * - Solo ambiente locale/test.
 * - Password app Gmail fornita dall'utente.
 * - Inserire la casella Gmail reale in GMAIL_SMTP_USER.
 */

$gmailUser = getenv('GMAIL_SMTP_USER') ?: 'inserisci-la-tua-gmail@gmail.com';
$gmailAppPassword = getenv('GMAIL_SMTP_APP_PASSWORD') ?: 'yyvb ckzs zvpi rwdb';

return [
    'enabled' => true,
    'from_email' => $gmailUser,
    'from_name' => 'Nuoto Libero',
    'admin_email' => $gmailUser,
    'admin_name' => 'Segreteria Nuoto Libero',
    'send_copy_to_sender' => false,
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => $gmailUser,
        'password' => $gmailAppPassword,
        'encryption' => 'tls',
        'auth' => true,
        'timeout' => 15,
    ],
];
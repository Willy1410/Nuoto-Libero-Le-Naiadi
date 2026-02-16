<?php
/**
 * SMTP Gmail configuration (local test/XAMPP).
 *
 * NOTE:
 * - Use ONLY local/test credentials.
 * - You can override values with environment variables:
 *   GMAIL_SMTP_USER, GMAIL_SMTP_APP_PASSWORD, MAIL_ENABLED
 */

$gmailUser = getenv('GMAIL_SMTP_USER') ?: 'info@glisqualetti.it';
$gmailAppPassword = getenv('GMAIL_SMTP_APP_PASSWORD') ?: 'yyvb ckzs zvpi rwdb';
$mailEnabledEnv = getenv('MAIL_ENABLED');
$mailEnabled = $mailEnabledEnv === false ? true : filter_var($mailEnabledEnv, FILTER_VALIDATE_BOOLEAN);

return [
    'enabled' => $mailEnabled,
    'from_email' => $gmailUser,
    'from_name' => 'Gli Squaletti',
    'admin_email' => $gmailUser,
    'admin_name' => 'Segreteria Gli Squaletti',
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

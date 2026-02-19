<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';
appLoadEnvFile(dirname(__DIR__) . '/.env');

$mailEnabled = appEnvBool('MAIL_ENABLED', false);
$mailFromEmail = appEnv('MAIL_FROM_EMAIL', 'noreply@nuotolibero.local');
$mailFromName = appEnv('MAIL_FROM_NAME', 'Nuoto libero Le Naiadi');
$mailAdminEmail = appEnv('MAIL_ADMIN_EMAIL', $mailFromEmail);
$mailAdminName = appEnv('MAIL_ADMIN_NAME', 'Segreteria Nuoto libero Le Naiadi');
$mailCopyToSender = appEnvBool('MAIL_SEND_COPY_TO_SENDER', false);

return [
    'enabled' => $mailEnabled,
    'from_email' => $mailFromEmail,
    'from_name' => $mailFromName,
    'admin_email' => $mailAdminEmail,
    'admin_name' => $mailAdminName,
    'send_copy_to_sender' => $mailCopyToSender,
    'smtp' => [
        'host' => appEnv('MAIL_SMTP_HOST', ''),
        'port' => (int)appEnv('MAIL_SMTP_PORT', '587'),
        'username' => appEnv('MAIL_SMTP_USER', ''),
        'password' => preg_replace('/\s+/', '', appEnv('MAIL_SMTP_PASS', '')) ?: '',
        'encryption' => appEnv('MAIL_SMTP_ENCRYPTION', 'tls'),
        'auth' => appEnvBool('MAIL_SMTP_AUTH', true),
        'timeout' => (int)appEnv('MAIL_SMTP_TIMEOUT', '15'),
    ],
];


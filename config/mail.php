<?php
/**
 * Local mail configuration (XAMPP/test only)
 *
 * NOTE:
 * - Keep `enabled` false until SMTP data is configured.
 * - Use only test/local credentials.
 */

return [
    'enabled' => false,
    'from_email' => 'noreply@nuotolibero.local',
    'from_name' => 'Nuoto Libero (Test Locale)',
    'admin_email' => 'admin@nuotolibero.local',
    'admin_name' => 'Admin Nuoto Libero',
    'send_copy_to_sender' => false,
    'smtp' => [
        'host' => 'smtp.mailtrap.io',
        'port' => 2525,
        'username' => 'YOUR_SMTP_USERNAME',
        'password' => 'YOUR_SMTP_PASSWORD',
        'encryption' => 'tls', // tls | ssl | ''
        'auth' => true,
        'timeout' => 10,
    ],
];

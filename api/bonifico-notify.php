<?php
declare(strict_types=1);

/**
 * Notifica bonifico da frontend pubblico
 */

require_once __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    sendJson(405, ['success' => false, 'message' => 'Metodo non consentito']);
}

$data = getJsonInput();
if (!$data) {
    sendJson(400, ['success' => false, 'message' => 'Payload non valido']);
}

$firstName = sanitizeText((string)($data['firstName'] ?? ''), 100);
$lastName = sanitizeText((string)($data['lastName'] ?? ''), 100);
$email = strtolower(sanitizeText((string)($data['email'] ?? ''), 255));
$phone = sanitizeText((string)($data['phone'] ?? ''), 40);
$package = sanitizeText((string)($data['package'] ?? ''), 120);
$amount = (float)($data['amount'] ?? 0);
$transferReference = sanitizeText((string)($data['transferReference'] ?? ''), 120);
$transferDate = sanitizeText((string)($data['transferDate'] ?? ''), 20);
$notes = sanitizeText((string)($data['notes'] ?? ''), 1200);

if ($firstName === '' || $lastName === '' || $email === '' || $package === '' || $transferReference === '' || $transferDate === '') {
    sendJson(400, ['success' => false, 'message' => 'Compila tutti i campi obbligatori del bonifico']);
}

if (!validateEmail($email)) {
    sendJson(400, ['success' => false, 'message' => 'Email non valida']);
}

if (!isMailConfigured()) {
    sendJson(503, [
        'success' => false,
        'message' => 'SMTP non configurato. Controlla config/mail.php e logs/mail.log',
    ]);
}

$paymentsConfigPath = PROJECT_ROOT . '/config/payments.php';
$paymentsConfig = file_exists($paymentsConfigPath) ? require $paymentsConfigPath : [];
$bonificoEmail = sanitizeText((string)($paymentsConfig['bonifico']['email_conferma'] ?? ($MAIL_CONFIG['admin_email'] ?? '')), 255);
if ($bonificoEmail === '' || !validateEmail($bonificoEmail)) {
    $bonificoEmail = sanitizeText((string)($MAIL_CONFIG['admin_email'] ?? ''), 255);
}

if ($bonificoEmail === '' || !validateEmail($bonificoEmail)) {
    sendJson(500, ['success' => false, 'message' => 'Email amministrazione non configurata']);
}

$orderId = 'BON-' . strtoupper(substr(str_replace('-', '', generateUuid()), 0, 10));
$fullName = trim($firstName . ' ' . $lastName);

$body = '<p><strong>Nuova notifica bonifico</strong></p>'
    . '<p><strong>Ordine:</strong> ' . htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Cliente:</strong> ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Telefono:</strong> ' . ($phone !== '' ? htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') : '-') . '</p>'
    . '<p><strong>Pacchetto:</strong> ' . htmlspecialchars($package, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Importo:</strong> EUR ' . number_format($amount, 2, ',', '.') . '</p>'
    . '<p><strong>Riferimento bonifico:</strong> ' . htmlspecialchars($transferReference, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Data bonifico:</strong> ' . htmlspecialchars($transferDate, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Note:</strong><br>' . ($notes !== '' ? nl2br(htmlspecialchars($notes, ENT_QUOTES, 'UTF-8')) : '-') . '</p>';

$sent = sendTemplateEmail(
    $bonificoEmail,
    'Amministrazione',
    '[Bonifico] Notifica ' . $orderId,
    'Nuova notifica bonifico',
    $body,
    'Notifica bonifico da ' . $fullName
);

if (!$sent) {
    sendJson(500, ['success' => false, 'message' => 'Invio notifica bonifico non riuscito. Verifica logs/mail.log']);
}

sendJson(200, [
    'success' => true,
    'orderId' => $orderId,
    'message' => 'Notifica bonifico inviata. Ti contatteremo dopo la verifica.',
]);
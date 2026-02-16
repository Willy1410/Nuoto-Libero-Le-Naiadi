<?php
/**
 * Bank transfer notification endpoint
 * POST /api/bonifico-notify.php
 */

require_once __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Payload non valido']);
    exit();
}

$firstName = sanitizeInput($data['firstName'] ?? '');
$lastName = sanitizeInput($data['lastName'] ?? '');
$email = sanitizeInput($data['email'] ?? '');
$phone = sanitizeInput($data['phone'] ?? '');
$package = sanitizeInput($data['package'] ?? '');
$amount = (float)($data['amount'] ?? 0);
$transferReference = sanitizeInput($data['transferReference'] ?? '');
$transferDate = sanitizeInput($data['transferDate'] ?? '');
$notes = sanitizeInput($data['notes'] ?? '');

if ($firstName === '' || $lastName === '' || $email === '' || $package === '' || $transferReference === '' || $transferDate === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Compila tutti i campi obbligatori del bonifico']);
    exit();
}

if (!validateEmail($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email non valida']);
    exit();
}

if (!isMailConfigured()) {
    logMailEvent('warning', 'Bonifico notification rejected: SMTP not configured', [
        'email' => $email,
        'package' => $package,
    ]);
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Servizio email non configurato in locale. Controlla config/mail.php e logs/mail.log',
    ]);
    exit();
}

$paymentsConfigPath = __DIR__ . '/../config/payments.php';
$paymentsConfig = file_exists($paymentsConfigPath) ? require $paymentsConfigPath : [];
$bonificoEmail = $paymentsConfig['bonifico']['email_conferma'] ?? ($MAIL_CONFIG['admin_email'] ?? '');
$bonificoEmail = is_string($bonificoEmail) ? trim($bonificoEmail) : '';
if ($bonificoEmail === '') {
    $bonificoEmail = (string)($MAIL_CONFIG['admin_email'] ?? '');
}

$orderId = 'BON-' . strtoupper(substr(str_replace('-', '', generateUuid()), 0, 10));
$subject = '[Bonifico] Notifica pagamento ' . $orderId;
$fullName = trim($firstName . ' ' . $lastName);

$htmlContent = '<h2>Nuova notifica bonifico</h2>'
    . '<p><strong>Ordine:</strong> ' . $orderId . '</p>'
    . '<p><strong>Cliente:</strong> ' . $fullName . '</p>'
    . '<p><strong>Email:</strong> ' . $email . '</p>'
    . '<p><strong>Telefono:</strong> ' . ($phone !== '' ? $phone : '-') . '</p>'
    . '<p><strong>Pacchetto:</strong> ' . $package . '</p>'
    . '<p><strong>Importo:</strong> EUR ' . number_format($amount, 2, ',', '.') . '</p>'
    . '<p><strong>Riferimento bonifico:</strong> ' . $transferReference . '</p>'
    . '<p><strong>Data bonifico:</strong> ' . $transferDate . '</p>'
    . '<p><strong>Note:</strong><br>' . ($notes !== '' ? nl2br($notes) : '-') . '</p>';

$textContent = "Nuova notifica bonifico\n"
    . "Ordine: {$orderId}\n"
    . "Cliente: {$fullName}\n"
    . "Email: {$email}\n"
    . 'Telefono: ' . ($phone !== '' ? $phone : '-') . "\n"
    . "Pacchetto: {$package}\n"
    . 'Importo: EUR ' . number_format($amount, 2, ',', '.') . "\n"
    . "Riferimento: {$transferReference}\n"
    . "Data bonifico: {$transferDate}\n"
    . 'Note: ' . ($notes !== '' ? $notes : '-') . "\n";

$sent = sendEmail($bonificoEmail, 'Amministrazione', $subject, $htmlContent, $textContent);
if (!$sent) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Invio notifica bonifico non riuscito. Verifica logs/mail.log',
    ]);
    exit();
}

echo json_encode([
    'success' => true,
    'orderId' => $orderId,
    'message' => 'Notifica bonifico inviata. Ti contatteremo dopo la verifica.',
]);

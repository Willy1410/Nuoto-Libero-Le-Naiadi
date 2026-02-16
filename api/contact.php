<?php
/**
 * Contact form endpoint
 * POST /api/contact.php
 */

require_once __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit();
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$honeypot = trim((string)($data['website'] ?? ''));
if ($honeypot !== '') {
    logMailEvent('warning', 'Contact form blocked by honeypot');
    echo json_encode(['success' => true, 'message' => 'Messaggio ricevuto']);
    exit();
}

$name = sanitizeInput($data['name'] ?? '');
$email = sanitizeInput($data['email'] ?? '');
$phone = sanitizeInput($data['phone'] ?? '');
$subject = sanitizeInput($data['subject'] ?? '');
$message = sanitizeInput($data['message'] ?? '');
$privacyAccepted = filter_var($data['privacy'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($name === '' || $email === '' || $subject === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Compila tutti i campi obbligatori']);
    exit();
}

if (!validateEmail($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email non valida']);
    exit();
}

if (!$privacyAccepted) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Consenso privacy obbligatorio']);
    exit();
}

if (!isMailConfigured()) {
    logMailEvent('warning', 'Contact form rejected: SMTP not configured', [
        'email' => $email,
        'subject' => $subject,
    ]);
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Servizio email non configurato in locale. Controlla config/mail.php e logs/mail.log',
    ]);
    exit();
}

$adminEmail = (string)($MAIL_CONFIG['admin_email'] ?? '');
$adminName = (string)($MAIL_CONFIG['admin_name'] ?? 'Admin');
if ($adminEmail === '') {
    logMailEvent('error', 'Contact form rejected: admin email missing');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configurazione email incompleta']);
    exit();
}

$mailSubject = '[Contatti] ' . $subject;
$htmlContent = '<h2>Nuovo messaggio dal sito</h2>'
    . '<p><strong>Nome:</strong> ' . $name . '</p>'
    . '<p><strong>Email:</strong> ' . $email . '</p>'
    . '<p><strong>Telefono:</strong> ' . ($phone !== '' ? $phone : '-') . '</p>'
    . '<p><strong>Oggetto:</strong> ' . $subject . '</p>'
    . '<p><strong>Messaggio:</strong><br>' . nl2br($message) . '</p>';
$textContent = "Nuovo messaggio dal sito\n"
    . "Nome: {$name}\n"
    . "Email: {$email}\n"
    . 'Telefono: ' . ($phone !== '' ? $phone : '-') . "\n"
    . "Oggetto: {$subject}\n"
    . "Messaggio:\n{$message}\n";

$sent = sendEmail($adminEmail, $adminName, $mailSubject, $htmlContent, $textContent);
if (!$sent) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Invio email non riuscito. Verifica logs/mail.log',
    ]);
    exit();
}

if (!empty($MAIL_CONFIG['send_copy_to_sender'])) {
    $copyHtml = '<p>Ciao ' . $name . ',</p>'
        . '<p>abbiamo ricevuto il tuo messaggio e ti risponderemo il prima possibile.</p>'
        . '<hr>'
        . '<p><strong>Riepilogo:</strong> ' . $subject . '</p>';
    sendEmail($email, $name, 'Conferma ricezione messaggio', $copyHtml);
}

echo json_encode([
    'success' => true,
    'message' => 'Grazie! Il tuo messaggio e stato inviato correttamente.',
]);

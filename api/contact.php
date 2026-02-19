<?php
declare(strict_types=1);

/**
 * Endpoint contatti
 */

require_once __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    sendJson(405, ['success' => false, 'message' => 'Metodo non consentito']);
}

enforceRateLimit('contact-form', 8, 300);

$data = getJsonInput();
if (!$data) {
    $data = $_POST;
}

$honeypot = sanitizeText((string)($data['website'] ?? ''), 255);
if ($honeypot !== '') {
    logMailEvent('warning', 'Contact form bloccato da honeypot');
    sendJson(200, ['success' => true, 'message' => 'Messaggio ricevuto']);
}

$name = sanitizeText((string)($data['name'] ?? ''), 120);
$email = strtolower(sanitizeText((string)($data['email'] ?? ''), 255));
$phone = sanitizeText((string)($data['phone'] ?? ''), 40);
$subjectRaw = sanitizeText((string)($data['subject'] ?? ''), 120);
$subjectOther = sanitizeText((string)($data['subject_other'] ?? ''), 220);
$message = sanitizeText((string)($data['message'] ?? ''), 4000);
$privacy = filter_var($data['privacy'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($name === '' || $email === '' || $subjectRaw === '' || $message === '') {
    sendJson(400, ['success' => false, 'message' => 'Compila tutti i campi obbligatori']);
}

if (!validateEmail($email)) {
    sendJson(400, ['success' => false, 'message' => 'Email non valida']);
}

if (!$privacy) {
    sendJson(400, ['success' => false, 'message' => 'Consenso privacy obbligatorio']);
}

$subjectKey = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $subjectRaw)));
$subjectMap = [
    'informazioni-iscrizione' => 'Informazioni iscrizione',
    'orari-corsi' => 'Orari corsi',
    'costi' => 'Costi',
    'problemi-con-account' => 'Problemi con account',
    'problemi-account' => 'Problemi con account',
    'altro' => 'Altro',
    'informazioni' => 'Richiesta informazioni',
    'pacchetti' => 'Informazioni sui pacchetti',
    'certificato' => 'Certificato medico',
    'problemi' => 'Problemi tecnici',
    'feedback' => 'Feedback e suggerimenti',
];

if (!isset($subjectMap[$subjectKey])) {
    sendJson(400, ['success' => false, 'message' => 'Oggetto non valido']);
}

if ($subjectKey === 'altro' && $subjectOther === '') {
    sendJson(400, ['success' => false, 'message' => 'Specifica il dettaglio per il campo Altro']);
}

$subjectLabel = $subjectMap[$subjectKey];
$subject = $subjectKey === 'altro'
    ? ($subjectLabel . ' - ' . $subjectOther)
    : $subjectLabel;

$adminEmail = sanitizeText((string)($MAIL_CONFIG['admin_email'] ?? ''), 255);
$adminName = sanitizeText((string)($MAIL_CONFIG['admin_name'] ?? 'Admin'), 120);

if ($adminEmail === '' || !validateEmail($adminEmail)) {
    sendJson(500, ['success' => false, 'message' => 'Configurazione email admin non valida']);
}

$body = '<p><strong>Nuovo messaggio dal sito</strong></p>'
    . '<p><strong>Nome:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>'
    . '<p><strong>Telefono:</strong> ' . ($phone !== '' ? htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') : '-') . '</p>'
    . '<p><strong>Oggetto:</strong> ' . htmlspecialchars($subjectLabel, ENT_QUOTES, 'UTF-8') . '</p>'
    . ($subjectKey === 'altro'
        ? '<p><strong>Dettaglio altro:</strong> ' . htmlspecialchars($subjectOther, ENT_QUOTES, 'UTF-8') . '</p>'
        : '')
    . '<p><strong>Messaggio:</strong><br>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';

$sent = sendTemplateEmail(
    $adminEmail,
    $adminName,
    '[Contatti] ' . $subject,
    'Nuovo messaggio dal form contatti',
    $body,
    'Nuovo messaggio da ' . $name
);

if (!$sent) {
    sendJson(500, ['success' => false, 'message' => 'Invio email non riuscito. Verifica logs/mail.log']);
}

if (!empty($MAIL_CONFIG['send_copy_to_sender'])) {
    $copyBody = '<p>Ciao <strong>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
        . '<p>abbiamo ricevuto il tuo messaggio. Ti risponderemo al piu presto.</p>'
        . '<p><strong>Oggetto:</strong> ' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</p>';

    sendTemplateEmail($email, $name, 'Conferma ricezione messaggio', 'Messaggio ricevuto', $copyBody);
}

sendJson(200, ['success' => true, 'message' => 'Grazie! Il tuo messaggio e stato inviato correttamente.']);

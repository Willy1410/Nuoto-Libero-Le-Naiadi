<?php
declare(strict_types=1);

/**
 * Email conferma ordine da checkout pubblico (PayPal/Stripe/In struttura)
 * POST /api/order-confirmation.php
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
$paymentMethod = strtolower(sanitizeText((string)($data['paymentMethod'] ?? ''), 20));
$orderId = sanitizeText((string)($data['orderId'] ?? ''), 80);
$transactionId = sanitizeText((string)($data['transactionId'] ?? ''), 120);
$amount = (float)($data['price'] ?? 0);

if ($firstName === '' || $lastName === '' || $email === '' || $package === '' || $paymentMethod === '' || $orderId === '') {
    sendJson(400, ['success' => false, 'message' => 'Compila tutti i campi obbligatori']);
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

$paymentLabels = [
    'paypal' => 'PayPal',
    'stripe' => 'Carta di credito/debito',
    'carta' => 'Carta di credito/debito',
    'instore' => 'Pagamento in struttura',
    'contanti' => 'Pagamento in struttura',
    'bonifico' => 'Bonifico bancario',
];
$paymentLabel = $paymentLabels[$paymentMethod] ?? ucfirst($paymentMethod);
$fullName = trim($firstName . ' ' . $lastName);
$isImmediatePayment = in_array($paymentMethod, ['paypal', 'stripe', 'carta'], true);

$title = $isImmediatePayment ? 'Pagamento ricevuto' : 'Richiesta pacchetto ricevuta';
$subject = $isImmediatePayment
    ? 'Conferma pagamento ordine ' . $orderId . ' - Gli Squaletti'
    : 'Conferma ordine ' . $orderId . ' - Gli Squaletti';

$body = '<p>Ciao <strong>' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
    . '<p>ti confermiamo la ricezione del tuo ordine.</p>'
    . '<p><strong>Codice ordine:</strong> <code>' . htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') . '</code><br>'
    . '<strong>Pacchetto:</strong> ' . htmlspecialchars($package, ENT_QUOTES, 'UTF-8') . '<br>'
    . '<strong>Metodo pagamento:</strong> ' . htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8') . '<br>'
    . '<strong>Importo:</strong> EUR ' . number_format($amount, 2, ',', '.') . '<br>'
    . '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>'
    . '<strong>Telefono:</strong> ' . htmlspecialchars($phone !== '' ? $phone : '-', ENT_QUOTES, 'UTF-8') . '</p>';

if ($transactionId !== '') {
    $body .= '<p><strong>ID transazione:</strong> <code>' . htmlspecialchars($transactionId, ENT_QUOTES, 'UTF-8') . '</code></p>';
}

if ($isImmediatePayment) {
    $body .= '<p>Il pagamento risulta registrato correttamente.</p>';
} else {
    $body .= '<p>La richiesta e in verifica. La segreteria ti aggiornera a breve.</p>';
}

$sent = sendTemplateEmail(
    $email,
    $fullName,
    $subject,
    $title,
    $body,
    'Conferma ordine pacchetto'
);

if (!$sent) {
    sendJson(500, ['success' => false, 'message' => 'Ordine registrato ma email non inviata. Verifica logs/mail.log']);
}

sendJson(200, [
    'success' => true,
    'message' => 'Email di conferma inviata correttamente',
]);

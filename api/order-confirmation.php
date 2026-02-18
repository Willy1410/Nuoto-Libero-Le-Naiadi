<?php
declare(strict_types=1);

/**
 * Public order confirmation email endpoint for checkout page
 * POST /api/order-confirmation.php
 */

require_once __DIR__ . '/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit();
}

enforceRateLimit('order-confirmation', 10, 600);

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
$paymentMethod = strtolower(sanitizeInput($data['paymentMethod'] ?? ''));
$orderId = sanitizeInput($data['orderId'] ?? '');
$transactionId = sanitizeInput($data['transactionId'] ?? '');
$amount = (float)($data['price'] ?? 0);

if ($firstName === '' || $lastName === '' || $email === '' || $package === '' || $paymentMethod === '' || $orderId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Compila tutti i campi obbligatori']);
    exit();
}

if (!validateEmail($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email non valida']);
    exit();
}

if (!isMailConfigured()) {
    logMailEvent('warning', 'Order confirmation rejected: SMTP not configured', [
        'email' => $email,
        'order_id' => $orderId,
        'payment_method' => $paymentMethod,
    ]);
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'SMTP non configurato. Verifica config/mail.php e logs/mail.log',
    ]);
    exit();
}

$paymentLabels = [
    'bonifico' => 'Bonifico bancario',
    'instore' => 'Contributo in struttura',
    'contanti' => 'Contributo in struttura',
];
$allowedMethods = array_keys($paymentLabels);
if (!in_array($paymentMethod, $allowedMethods, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Metodo contributo non supportato']);
    exit();
}
$paymentLabel = $paymentLabels[$paymentMethod] ?? ucfirst($paymentMethod);
$fullName = trim($firstName . ' ' . $lastName);
$title = 'Richiesta pacchetto ricevuta';
$subject = 'Conferma richiesta ' . $orderId . ' - Gli Squaletti';

$body = '<p>Ciao <strong>' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
    . '<p>ti confermiamo la ricezione della tua richiesta.</p>'
    . '<p><strong>Codice pratica:</strong> <code>' . htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') . '</code><br>'
    . '<strong>Pacchetto:</strong> ' . htmlspecialchars($package, ENT_QUOTES, 'UTF-8') . '<br>'
    . '<strong>Metodo pagamento:</strong> ' . htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8') . '<br>'
    . '<strong>Importo:</strong> EUR ' . number_format($amount, 2, ',', '.') . '<br>'
    . '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>'
    . '<strong>Telefono:</strong> ' . htmlspecialchars($phone !== '' ? $phone : '-', ENT_QUOTES, 'UTF-8') . '</p>';

if ($transactionId !== '') {
    $body .= '<p><strong>ID transazione:</strong> <code>' . htmlspecialchars($transactionId, ENT_QUOTES, 'UTF-8') . '</code></p>';
}

$body .= '<p>La richiesta e in fase di verifica. La segreteria ti aggiornera appena possibile.</p>';

$text = "Conferma richiesta {$orderId}\n"
    . "Cliente: {$fullName}\n"
    . "Pacchetto: {$package}\n"
    . "Metodo: {$paymentLabel}\n"
    . 'Importo: EUR ' . number_format($amount, 2, ',', '.') . "\n"
    . "Email: {$email}\n"
    . 'Telefono: ' . ($phone !== '' ? $phone : '-') . "\n"
    . ($transactionId !== '' ? "Transazione: {$transactionId}\n" : '');

$sent = sendBrandedEmail(
    $email,
    $fullName,
    $subject,
    $title,
    $body,
    'Conferma richiesta pacchetto',
    $text
);

if (!$sent) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Richiesta registrata ma email non inviata. Verifica logs/mail.log',
    ]);
    exit();
}

$adminEmail = trim((string)($MAIL_CONFIG['admin_email'] ?? ''));
if ($adminEmail !== '' && validateEmail($adminEmail)) {
    $adminBody = '<p>Nuova richiesta pacchetto ricevuta.</p>'
        . '<p><strong>Pratica:</strong> <code>' . htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') . '</code><br>'
        . '<strong>Cliente:</strong> ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Pacchetto:</strong> ' . htmlspecialchars($package, ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Metodo:</strong> ' . htmlspecialchars($paymentLabel, ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Importo:</strong> EUR ' . number_format($amount, 2, ',', '.') . '</p>';
    sendBrandedEmail(
        $adminEmail,
        (string)($MAIL_CONFIG['admin_name'] ?? 'Amministrazione'),
        '[Richiesta] ' . $orderId . ' - ' . SITE_NAME,
        'Nuova richiesta pacchetto',
        $adminBody,
        'Nuova richiesta ricevuta'
    );
}

echo json_encode([
    'success' => true,
    'message' => 'Email di conferma inviata correttamente',
]);

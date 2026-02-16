<?php
/**
 * Shared API configuration (DB, auth, mail, helpers)
 * File: api/config.php
 */

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('Europe/Rome');

$allowedOrigins = [
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:8080',
];
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($requestOrigin && in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
} elseif (!$requestOrigin) {
    header('Access-Control-Allow-Origin: http://localhost');
}

header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'piscina_gestione');
define('DB_CHARSET', 'utf8mb4');

define('JWT_SECRET', getenv('JWT_SECRET_LOCAL') ?: 'LOCAL_TEST_SECRET_CHANGE_ME_2026');
define('JWT_EXPIRATION', 86400);

define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('UPLOAD_ALLOWED_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAIL_LOG_PATH', __DIR__ . '/../logs/mail.log');

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

$mailConfigPath = __DIR__ . '/../config/mail.php';
$MAIL_CONFIG = [
    'enabled' => false,
    'from_email' => 'noreply@nuotolibero.local',
    'from_name' => 'Nuoto Libero (Local)',
    'admin_email' => 'admin@nuotolibero.local',
    'admin_name' => 'Admin',
    'send_copy_to_sender' => false,
    'smtp' => [
        'host' => '',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'auth' => true,
        'timeout' => 10,
    ],
];
if (file_exists($mailConfigPath)) {
    $loadedMailConfig = require $mailConfigPath;
    if (is_array($loadedMailConfig)) {
        $MAIL_CONFIG = array_replace_recursive($MAIL_CONFIG, $loadedMailConfig);
    }
}

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('DB connection error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore connessione database',
    ]);
    exit();
}

function base64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64UrlDecode(string $value): string
{
    $padding = strlen($value) % 4;
    if ($padding > 0) {
        $value .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($value, '-_', '+/')) ?: '';
}

function generateJWT(string $userId, string $email, string $role): string
{
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'email' => $email,
        'role' => $role,
        'iat' => time(),
        'exp' => time() + JWT_EXPIRATION,
    ]);

    $base64UrlHeader = base64UrlEncode($header ?: '{}');
    $base64UrlPayload = base64UrlEncode($payload ?: '{}');
    $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = base64UrlEncode($signature);

    return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
}

function verifyJWT(?string $token): ?array
{
    if (!$token) {
        return null;
    }

    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        return null;
    }

    [$header, $payload, $signatureProvided] = $tokenParts;
    if (!$header || !$payload || !$signatureProvided) {
        return null;
    }

    $expectedSignature = base64UrlEncode(
        hash_hmac('sha256', $header . '.' . $payload, JWT_SECRET, true)
    );
    if (!hash_equals($expectedSignature, $signatureProvided)) {
        return null;
    }

    $payloadData = json_decode(base64UrlDecode($payload), true);
    if (!is_array($payloadData)) {
        return null;
    }

    if (!isset($payloadData['exp']) || (int)$payloadData['exp'] < time()) {
        return null;
    }

    return $payloadData;
}

function getAuthorizationHeader(): string
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
    if ($authHeader) {
        return $authHeader;
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                return (string)$value;
            }
        }
    }

    return '';
}

function getCurrentUser(): ?array
{
    $authHeader = getAuthorizationHeader();
    if (!$authHeader) {
        return null;
    }

    if (!preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
        return null;
    }

    return verifyJWT(trim($matches[1]));
}

function requireAuth(): array
{
    $user = getCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non autenticato']);
        exit();
    }

    return $user;
}

function requireRole(int $minLevel): array
{
    global $pdo;
    $user = requireAuth();

    $stmt = $pdo->prepare(
        'SELECT r.livello FROM profili p JOIN ruoli r ON p.ruolo_id = r.id WHERE p.id = ?'
    );
    $stmt->execute([$user['user_id']]);
    $profile = $stmt->fetch();

    if (!$profile || (int)$profile['livello'] < $minLevel) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accesso negato']);
        exit();
    }

    return $user;
}

function logActivity(?string $userId, string $azione, string $descrizione = '', string $tabella = '', string $recordId = ''): void
{
    global $pdo;

    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $pdo->prepare(
            'INSERT INTO activity_log (user_id, azione, descrizione, tabella_riferimento, record_id, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $azione, $descrizione, $tabella, $recordId, $ip, $userAgent]);
    } catch (Throwable $e) {
        error_log('Activity log write failed: ' . $e->getMessage());
    }
}

function generateUuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function generateQRCode(string $acquistoId): string
{
    return 'PSC-' . substr(str_replace('-', '', $acquistoId), 0, 8) . '-' . time();
}

function logMailEvent(string $level, string $message, array $context = []): void
{
    $sanitized = [];
    foreach ($context as $key => $value) {
        if (preg_match('/pass|secret|token|api[_-]?key/i', (string)$key)) {
            $sanitized[$key] = '***';
            continue;
        }
        $sanitized[$key] = is_scalar($value) || $value === null ? $value : '[complex]';
    }

    $line = sprintf(
        "[%s] [%s] %s%s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $sanitized ? ' | ' . json_encode($sanitized, JSON_UNESCAPED_UNICODE) : ''
    );

    $logDir = dirname(MAIL_LOG_PATH);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    file_put_contents(MAIL_LOG_PATH, $line, FILE_APPEND);
}

function isMailConfigured(): bool
{
    global $MAIL_CONFIG;

    if (empty($MAIL_CONFIG['enabled'])) {
        return false;
    }

    $smtp = $MAIL_CONFIG['smtp'] ?? [];
    $host = trim((string)($smtp['host'] ?? ''));
    $auth = !empty($smtp['auth']);
    $username = trim((string)($smtp['username'] ?? ''));
    $password = trim((string)($smtp['password'] ?? ''));

    if ($host === '') {
        return false;
    }

    if ($auth && ($username === '' || $password === '' || str_contains($username, 'YOUR_') || str_contains($password, 'YOUR_'))) {
        return false;
    }

    return true;
}

function sendEmail(string $to, string $toName, string $subject, string $htmlContent, string $textContent = ''): bool
{
    global $MAIL_CONFIG;

    if (!isMailConfigured()) {
        logMailEvent('warning', 'SMTP not configured, email not sent', [
            'to' => $to,
            'subject' => $subject,
        ]);
        return false;
    }

    if (!class_exists(PHPMailer::class)) {
        logMailEvent('error', 'PHPMailer not available. Run composer install.', [
            'to' => $to,
            'subject' => $subject,
        ]);
        return false;
    }

    try {
        $smtp = $MAIL_CONFIG['smtp'] ?? [];

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string)($smtp['host'] ?? '');
        $mail->Port = (int)($smtp['port'] ?? 587);
        $mail->SMTPAuth = !empty($smtp['auth']);
        $mail->Username = (string)($smtp['username'] ?? '');
        $mail->Password = (string)($smtp['password'] ?? '');
        $mail->Timeout = (int)($smtp['timeout'] ?? 10);

        $encryption = strtolower((string)($smtp['encryption'] ?? ''));
        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }

        $mail->CharSet = 'UTF-8';
        $mail->setFrom((string)$MAIL_CONFIG['from_email'], (string)$MAIL_CONFIG['from_name']);
        $mail->addAddress($to, $toName);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlContent;
        $mail->AltBody = $textContent !== '' ? $textContent : strip_tags($htmlContent);

        $mail->send();
        logMailEvent('info', 'Email sent', [
            'to' => $to,
            'subject' => $subject,
        ]);

        return true;
    } catch (MailerException $e) {
        logMailEvent('error', 'Email send failed', [
            'to' => $to,
            'subject' => $subject,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}

function validateEmail(string $email): bool
{
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateCodiceFiscale(string $cf): bool
{
    return (bool)preg_match('/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/', strtoupper($cf));
}

function getFasciaOraria(string $ora): string
{
    $hour = (int)date('H', strtotime($ora));
    return ($hour < 14) ? 'mattina' : 'pomeriggio';
}

function sanitizeInput($input)
{
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }

    return htmlspecialchars(strip_tags(trim((string)$input)), ENT_QUOTES, 'UTF-8');
}

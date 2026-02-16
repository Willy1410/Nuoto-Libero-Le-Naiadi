<?php
declare(strict_types=1);

/**
 * Shared API bootstrap: logging, DB, auth, mail, helpers.
 */

use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

date_default_timezone_set('Europe/Rome');
mb_internal_encoding('UTF-8');

const PROJECT_ROOT = __DIR__ . '/..';
const LOG_DIR = PROJECT_ROOT . '/logs';
const ERROR_LOG_PATH = LOG_DIR . '/error.log';
const MAIL_LOG_PATH = LOG_DIR . '/mail.log';

if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}
if (!file_exists(ERROR_LOG_PATH)) {
    touch(ERROR_LOG_PATH);
}
if (!file_exists(MAIL_LOG_PATH)) {
    touch(MAIL_LOG_PATH);
}

ini_set('error_log', ERROR_LOG_PATH);

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

function getAppBaseUrl(): string
{
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    $basePath = preg_replace('#/api/[^/]+$#', '', $scriptName);
    if (!is_string($basePath)) {
        $basePath = '';
    }

    return rtrim($scheme . '://' . $host . $basePath, '/');
}

const APP_BASE_URL = '';

$allowedOriginsPattern = '#^https?://(localhost|127\.0\.0\.1|10\.\d+\.\d+\.\d+|192\.168\.\d+\.\d+|172\.(1[6-9]|2\d|3[0-1])\.\d+\.\d+)(:\d+)?$#i';
$requestOrigin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
if ($requestOrigin !== '' && preg_match($allowedOriginsPattern, $requestOrigin)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
} else {
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
define('DB_NAME', getenv('DB_NAME') ?: 'nuoto_libero');
define('DB_CHARSET', 'utf8mb4');

define('JWT_SECRET', getenv('JWT_SECRET_LOCAL') ?: 'LOCAL_TEST_SECRET_CHANGE_ME_2026');
define('JWT_EXPIRATION', 86400);

define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('UPLOAD_ALLOWED_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);
define('UPLOAD_DIR', PROJECT_ROOT . '/uploads/');
define('SITE_NAME', 'Gli Squaletti');
define('SITE_LOGO_URL', 'https://www.genspark.ai/api/files/s/s3WpPfgP');

$autoloadPath = PROJECT_ROOT . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

$mailConfigPath = PROJECT_ROOT . '/config/mail.php';
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
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
} catch (Throwable $e) {
    error_log('DB connection error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore connessione database',
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function sendJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

function getJsonInput(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function sanitizeInput($input)
{
    if (is_array($input)) {
        $clean = [];
        foreach ($input as $key => $value) {
            $clean[$key] = sanitizeInput($value);
        }
        return $clean;
    }

    $value = trim((string)$input);
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
    return is_string($value) ? $value : '';
}

function sanitizeText(string $value, int $maxLength = 1000): string
{
    $clean = sanitizeInput($value);
    if (mb_strlen($clean) > $maxLength) {
        return mb_substr($clean, 0, $maxLength);
    }
    return $clean;
}

function validateEmail(string $email): bool
{
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validateCodiceFiscale(string $cf): bool
{
    return (bool)preg_match('/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/', strtoupper($cf));
}

function validatePasswordStrength(string $password): bool
{
    return strlen($password) >= 8;
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

    $decoded = base64_decode(strtr($value, '-_', '+/'), true);
    return is_string($decoded) ? $decoded : '';
}

function generateJWT(string $userId, string $email, string $role, int $roleLevel): string
{
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'email' => $email,
        'role' => $role,
        'role_level' => $roleLevel,
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
    if ($header === '' || $payload === '' || $signatureProvided === '') {
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
    if (is_string($authHeader) && $authHeader !== '') {
        return $authHeader;
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (strtolower((string)$key) === 'authorization') {
                    return (string)$value;
                }
            }
        }
    }

    return '';
}

function getCurrentUser(): ?array
{
    $authHeader = getAuthorizationHeader();
    if (!preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
        return null;
    }

    return verifyJWT(trim((string)$matches[1]));
}

function requireAuth(): array
{
    $user = getCurrentUser();
    if (!$user) {
        sendJson(401, ['success' => false, 'message' => 'Non autenticato']);
    }

    return $user;
}

function requireRole(int $minLevel): array
{
    global $pdo;

    $user = requireAuth();

    $stmt = $pdo->prepare(
        'SELECT r.nome, r.livello
         FROM profili p
         JOIN ruoli r ON p.ruolo_id = r.id
         WHERE p.id = ? AND p.attivo = 1'
    );
    $stmt->execute([$user['user_id']]);
    $profile = $stmt->fetch();

    if (!$profile || (int)$profile['livello'] < $minLevel) {
        sendJson(403, ['success' => false, 'message' => 'Accesso negato']);
    }

    $user['role'] = $profile['nome'];
    $user['role_level'] = (int)$profile['livello'];

    return $user;
}

function logActivity(?string $userId, string $action, string $description = '', string $table = '', string $recordId = ''): void
{
    global $pdo;

    try {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $userAgent = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

        $stmt = $pdo->prepare(
            'INSERT INTO activity_log (user_id, azione, descrizione, tabella_riferimento, record_id, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $action, $description, $table, $recordId, $ip, $userAgent]);
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
    $raw = strtoupper(str_replace('-', '', $acquistoId));
    return 'NL-' . substr($raw, 0, 12);
}

function getFasciaOraria(string $time): string
{
    $hour = (int)date('H', strtotime($time));
    return $hour < 14 ? 'mattina' : 'pomeriggio';
}

function logMailEvent(string $level, string $message, array $context = []): void
{
    $level = strtolower($level);
    if (!in_array($level, ['warning', 'error'], true)) {
        return;
    }

    $safeContext = [];
    foreach ($context as $key => $value) {
        if (preg_match('/pass|secret|token|api[_-]?key/i', (string)$key)) {
            $safeContext[$key] = '***';
        } else {
            $safeContext[$key] = is_scalar($value) || $value === null ? $value : '[complex]';
        }
    }

    $line = sprintf(
        "[%s] [%s] %s%s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $safeContext ? ' | ' . json_encode($safeContext, JSON_UNESCAPED_UNICODE) : ''
    );

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
    $username = trim((string)($smtp['username'] ?? ''));
    $password = trim((string)($smtp['password'] ?? ''));

    if ($host === '' || $username === '' || $password === '') {
        return false;
    }

    return true;
}

function sendEmail(string $to, string $toName, string $subject, string $htmlContent, string $textContent = ''): bool
{
    global $MAIL_CONFIG;

    if (!isMailConfigured()) {
        logMailEvent('warning', 'SMTP non configurato, email non inviata', [
            'to' => $to,
            'subject' => $subject,
        ]);
        return false;
    }

    if (!class_exists(PHPMailer::class)) {
        logMailEvent('error', 'PHPMailer non disponibile. Esegui composer install.', [
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
        $mail->Timeout = (int)($smtp['timeout'] ?? 15);

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

        return true;
    } catch (MailerException $e) {
        logMailEvent('error', 'Invio email fallito', [
            'to' => $to,
            'subject' => $subject,
            'error' => $e->getMessage(),
        ]);
        return false;
    }
}

function buildEmailTemplate(string $title, string $bodyHtml, string $previewText = ''): string
{
    $preview = $previewText !== '' ? $previewText : strip_tags($title);
    $safePreview = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $logoUrl = htmlspecialchars(SITE_LOGO_URL, ENT_QUOTES, 'UTF-8');

    return '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
        . '<body style="margin:0;padding:0;background:#f8f9fa;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">'
        . '<div style="max-width:640px;margin:0 auto;padding:24px;">'
        . '<div style="background:linear-gradient(135deg,#00a8e8,#0077b6);padding:18px 22px;border-radius:12px 12px 0 0;color:#fff;">'
        . '<table role="presentation" style="width:100%;border-collapse:collapse;"><tr>'
        . '<td style="width:70px;vertical-align:middle;"><img src="' . $logoUrl . '" alt="Logo Gli Squaletti" style="width:56px;height:56px;border-radius:8px;display:block;"></td>'
        . '<td style="vertical-align:middle;"><h1 style="margin:0;font-size:22px;line-height:1.2;">' . SITE_NAME . '</h1>'
        . '<p style="margin:6px 0 0 0;opacity:.92;font-size:13px;">' . $safePreview . '</p></td>'
        . '</tr></table>'
        . '</div>'
        . '<div style="background:#ffffff;padding:24px;border-radius:0 0 12px 12px;border:1px solid #e5e7eb;border-top:none;">'
        . '<h2 style="margin-top:0;color:#0077b6;font-size:20px;">' . $safeTitle . '</h2>'
        . $bodyHtml
        . '<hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">'
        . '<p style="margin:0;font-size:12px;color:#6b7280;">Messaggio automatico - ' . SITE_NAME . '</p>'
        . '</div></div></body></html>';
}

function sendTemplateEmail(string $to, string $name, string $subject, string $title, string $bodyHtml, string $previewText = ''): bool
{
    $html = buildEmailTemplate($title, $bodyHtml, $previewText);
    return sendEmail($to, $name, $subject, $html);
}

function wasNotificationSent(string $acquistoId, string $tipo): bool
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT id FROM notifiche_email WHERE acquisto_id = ? AND tipo = ? LIMIT 1');
    $stmt->execute([$acquistoId, $tipo]);
    return (bool)$stmt->fetch();
}

function markNotificationSent(string $acquistoId, string $tipo): void
{
    global $pdo;

    $stmt = $pdo->prepare('INSERT IGNORE INTO notifiche_email (id, acquisto_id, tipo) VALUES (?, ?, ?)');
    $stmt->execute([generateUuid(), $acquistoId, $tipo]);
}

function localAppBaseUrl(): string
{
    $fromEnv = getenv('APP_BASE_URL');
    if (is_string($fromEnv) && trim($fromEnv) !== '') {
        return rtrim($fromEnv, '/');
    }

    return getAppBaseUrl();
}

function dispatchPendingExpiryReminders(?string $userId = null): void
{
    global $pdo;

    try {
        $sql = 'SELECT a.id, a.data_scadenza, DATEDIFF(a.data_scadenza, CURDATE()) AS giorni_alla_scadenza,
                       p.nome AS pacchetto_nome,
                       u.nome, u.cognome, u.email
                FROM acquisti a
                JOIN pacchetti p ON p.id = a.pacchetto_id
                JOIN profili u ON u.id = a.user_id
                WHERE a.stato_pagamento = "confirmed"
                  AND a.data_scadenza IS NOT NULL
                  AND DATEDIFF(a.data_scadenza, CURDATE()) BETWEEN 0 AND 7';

        $params = [];
        if ($userId !== null && $userId !== '') {
            $sql .= ' AND a.user_id = ?';
            $params[] = $userId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $purchaseId = (string)$row['id'];
            if (wasNotificationSent($purchaseId, 'expiry_7days')) {
                continue;
            }

            $days = (int)$row['giorni_alla_scadenza'];
            $body = '<p>Ciao <strong>' . htmlspecialchars((string)$row['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
                . '<p>il tuo pacchetto <strong>' . htmlspecialchars((string)$row['pacchetto_nome'], ENT_QUOTES, 'UTF-8') . '</strong> scadra tra circa <strong>' . $days . ' giorni</strong>.</p>'
                . '<p>Data scadenza: <strong>' . htmlspecialchars((string)$row['data_scadenza'], ENT_QUOTES, 'UTF-8') . '</strong></p>';

            $sent = sendTemplateEmail(
                (string)$row['email'],
                trim((string)$row['nome'] . ' ' . (string)$row['cognome']),
                'Promemoria scadenza pacchetto',
                'Pacchetto in scadenza',
                $body
            );

            if ($sent) {
                markNotificationSent($purchaseId, 'expiry_7days');
            }
        }
    } catch (Throwable $e) {
        error_log('dispatchPendingExpiryReminders error: ' . $e->getMessage());
    }
}

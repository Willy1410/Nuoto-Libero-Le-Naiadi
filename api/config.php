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
mb_internal_encoding('UTF-8');

define('PROJECT_ROOT', realpath(__DIR__ . '/..') ?: (__DIR__ . '/..'));

$envLoaderPath = PROJECT_ROOT . '/config/env.php';
if (file_exists($envLoaderPath)) {
    require_once $envLoaderPath;
    if (function_exists('appLoadEnvFile')) {
        appLoadEnvFile(PROJECT_ROOT . '/.env');
    }
}

$bootstrapPath = PROJECT_ROOT . '/bootstrap.php';
if (file_exists($bootstrapPath)) {
    require_once $bootstrapPath;
}

define('LOG_DIR', PROJECT_ROOT . '/logs');
define('ERROR_LOG_PATH', LOG_DIR . '/error.log');
define('RATE_LIMIT_DIR', LOG_DIR . '/ratelimit');
define('JWT_REVOKE_DIR', LOG_DIR . '/jwt_revocations');

if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}
if (!is_dir(RATE_LIMIT_DIR)) {
    mkdir(RATE_LIMIT_DIR, 0755, true);
}
if (!is_dir(JWT_REVOKE_DIR)) {
    mkdir(JWT_REVOKE_DIR, 0755, true);
}
if (!file_exists(ERROR_LOG_PATH)) {
    touch(ERROR_LOG_PATH);
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

$allowedOriginsRaw = trim((string)(getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost,http://127.0.0.1,http://localhost:8080'));
$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $allowedOriginsRaw))));
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($requestOrigin && in_array($requestOrigin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
} elseif (!$requestOrigin && !empty($allowedOrigins)) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
}

header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cross-Origin-Resource-Policy: same-origin');
header("Permissions-Policy: geolocation=(), microphone=(), camera=(self)");
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Type: application/json; charset=UTF-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function getAppBaseUrl(): string
{
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
    $host = preg_replace('/[^a-zA-Z0-9\\.\\-:\\[\\]]/', '', $host) ?? 'localhost';
    if ($host === '') {
        $host = 'localhost';
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    $basePath = preg_replace('#/api/[^/]+$#', '', $scriptName);
    if (!is_string($basePath)) {
        $basePath = '';
    }

    return rtrim($scheme . '://' . $host . $basePath, '/');
}

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'nuoto_libero');
define('DB_CHARSET', 'utf8mb4');

$jwtSecret = trim((string)(getenv('JWT_SECRET') ?: getenv('JWT_SECRET_LOCAL') ?: ''));
if ($jwtSecret === '') {
    $jwtSecret = 'LOCAL_DEV_ONLY_CHANGE_ME';
}
define('JWT_SECRET', $jwtSecret);
define('JWT_EXPIRATION', 86400);

$currentHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? 'localhost'));
if (JWT_SECRET === 'LOCAL_DEV_ONLY_CHANGE_ME' && !in_array($currentHost, ['localhost', '127.0.0.1'], true)) {
    error_log('SECURITY WARNING: JWT_SECRET non configurato su host non locale');
}

define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('UPLOAD_ALLOWED_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);
define('UPLOAD_DIR', PROJECT_ROOT . '/uploads/');
define('MAIL_LOG_PATH', LOG_DIR . '/mail.log');
define('MAIL_QUEUE_DIR', LOG_DIR . '/mail_queue');
define('SITE_NAME', 'Nuoto libero Le Naiadi');
define('SITE_LOGO_URL', 'https://public.gensparkspace.com/api/files/s/s3WpPfgP');

if (!file_exists(MAIL_LOG_PATH)) {
    touch(MAIL_LOG_PATH);
}
if (!is_dir(MAIL_QUEUE_DIR)) {
    mkdir(MAIL_QUEUE_DIR, 0755, true);
}

$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

$mailConfigPath = __DIR__ . '/../config/mail.php';
$MAIL_CONFIG = [
    'enabled' => filter_var(getenv('MAIL_ENABLED') ?: '0', FILTER_VALIDATE_BOOLEAN),
    'queue_fallback_on_error' => true,
    'from_email' => (string)(getenv('MAIL_FROM_EMAIL') ?: 'noreply@nuotolibero.local'),
    'from_name' => (string)(getenv('MAIL_FROM_NAME') ?: 'Nuoto Libero'),
    'admin_email' => (string)(getenv('MAIL_ADMIN_EMAIL') ?: 'admin@nuotolibero.local'),
    'admin_name' => (string)(getenv('MAIL_ADMIN_NAME') ?: 'Admin'),
    'send_copy_to_sender' => filter_var(getenv('MAIL_SEND_COPY_TO_SENDER') ?: '0', FILTER_VALIDATE_BOOLEAN),
    'smtp' => [
        'host' => (string)(getenv('MAIL_SMTP_HOST') ?: ''),
        'port' => (int)(getenv('MAIL_SMTP_PORT') ?: 587),
        'username' => (string)(getenv('MAIL_SMTP_USER') ?: ''),
        'password' => (string)(getenv('MAIL_SMTP_PASS') ?: ''),
        'encryption' => (string)(getenv('MAIL_SMTP_ENCRYPTION') ?: 'tls'),
        'auth' => filter_var(getenv('MAIL_SMTP_AUTH') ?: '1', FILTER_VALIDATE_BOOLEAN),
        'timeout' => (int)(getenv('MAIL_SMTP_TIMEOUT') ?: 10),
    ],
];
if (file_exists($mailConfigPath)) {
    $loadedMailConfig = require $mailConfigPath;
    if (is_array($loadedMailConfig)) {
        $MAIL_CONFIG = array_replace_recursive($MAIL_CONFIG, $loadedMailConfig);
    }
}

try {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
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

function sanitizeText(string $value, int $maxLength = 1000): string
{
    $clean = sanitizeInput($value);
    if (mb_strlen($clean) > $maxLength) {
        return mb_substr($clean, 0, $maxLength);
    }

    return $clean;
}

function validatePasswordStrength(string $password): bool
{
    if (strlen($password) < 10) {
        return false;
    }

    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    if (!preg_match('/\d/', $password)) {
        return false;
    }
    if (!preg_match('/[^a-zA-Z\d]/', $password)) {
        return false;
    }

    return true;
}

function passwordPolicyHint(): string
{
    return 'minimo 10 caratteri, con maiuscola, minuscola, numero e simbolo';
}

function buildSecurePasswordHash(string $password): string
{
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;

    if ($algo === PASSWORD_ARGON2ID) {
        $hash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 1 << 16,
            'time_cost' => 4,
            'threads' => 2,
        ]);
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
    }

    if (!is_string($hash) || $hash === '') {
        throw new RuntimeException('Impossibile generare hash password');
    }

    return $hash;
}

function passwordHashNeedsUpgrade(string $hash): bool
{
    if ($hash === '') {
        return false;
    }

    if (defined('PASSWORD_ARGON2ID')) {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => 1 << 16,
            'time_cost' => 4,
            'threads' => 2,
        ]);
    }

    return password_needs_rehash($hash, PASSWORD_BCRYPT);
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

function generateJWT(string $userId, string $email, string $role, ?int $roleLevel = null): string
{
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payloadData = [
        'user_id' => $userId,
        'email' => $email,
        'role' => $role,
        'iat' => time(),
        'exp' => time() + JWT_EXPIRATION,
    ];
    if ($roleLevel !== null) {
        $payloadData['role_level'] = $roleLevel;
    }
    $payload = json_encode($payloadData);

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

    if (isJwtTokenRevoked($token)) {
        return null;
    }

    return $payloadData;
}

function getAuthorizationHeader(): string
{
    $candidateKeys = [
        'HTTP_AUTHORIZATION',
        'Authorization',
        'REDIRECT_HTTP_AUTHORIZATION',
        'REDIRECT_Authorization',
        'HTTP_X_AUTHORIZATION',
        'X-Authorization',
    ];

    foreach ($candidateKeys as $key) {
        $value = trim((string)($_SERVER[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $normalized = trim((string)$value);
                if ($normalized !== '') {
                    return $normalized;
                }
            }
        }
    }

    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $key => $value) {
            if (strtolower((string)$key) === 'authorization') {
                $normalized = trim((string)$value);
                if ($normalized !== '') {
                    return $normalized;
                }
            }
        }
    }

    return '';
}

function extractBearerToken(string $authHeader): ?string
{
    $value = trim($authHeader);
    if ($value === '') {
        return null;
    }

    if (!preg_match('/^Bearer\s+(.+)$/i', $value, $matches)) {
        return null;
    }

    $token = trim((string)$matches[1]);
    if ($token === '') {
        return null;
    }

    if (!preg_match('/^[A-Za-z0-9\-\._~\+\/]+=*$/', $token)) {
        return null;
    }

    return $token;
}

function jwtRevocationFilePath(string $token): string
{
    return JWT_REVOKE_DIR . '/' . hash('sha256', $token) . '.json';
}

function revokeJwtToken(string $token, int $exp): void
{
    if ($token === '' || $exp <= time()) {
        return;
    }

    if (!is_dir(JWT_REVOKE_DIR)) {
        @mkdir(JWT_REVOKE_DIR, 0755, true);
    }

    $payload = [
        'exp' => $exp,
        'revoked_at' => time(),
    ];
    @file_put_contents(jwtRevocationFilePath($token), json_encode($payload, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function isJwtTokenRevoked(string $token): bool
{
    if ($token === '') {
        return false;
    }

    $path = jwtRevocationFilePath($token);
    if (!is_file($path)) {
        return false;
    }

    $raw = @file_get_contents($path);
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    $exp = is_array($decoded) ? (int)($decoded['exp'] ?? 0) : 0;
    if ($exp > 0 && $exp < time()) {
        @unlink($path);
        return false;
    }

    return true;
}

function getSessionAuthenticatedUser(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }

    $userId = trim((string)($_SESSION['auth_user_id'] ?? ''));
    if ($userId === '') {
        return null;
    }

    $role = strtolower(trim((string)($_SESSION['auth_role'] ?? '')));
    if ($role === 'user') {
        $role = 'utente';
    }

    return [
        'user_id' => $userId,
        'email' => (string)($_SESSION['auth_email'] ?? ''),
        'role' => $role,
    ];
}

function getCurrentUser(): ?array
{
    $authHeader = getAuthorizationHeader();
    if ($authHeader !== '') {
        $token = extractBearerToken($authHeader);
        if ($token) {
            $verified = verifyJWT($token);
            if (is_array($verified)) {
                return $verified;
            }
        }
    }

    return getSessionAuthenticatedUser();
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

function getClientIp(): string
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return '0.0.0.0';
}

/**
 * Semplice rate limiter persistente su file per endpoint sensibili.
 */
function enforceRateLimit(string $scope, int $maxRequests, int $windowSeconds, ?string $identifier = null): void
{
    if ($maxRequests < 1 || $windowSeconds < 1) {
        return;
    }

    $scope = strtolower(trim($scope));
    $scope = preg_replace('/[^a-z0-9_-]/', '_', $scope) ?: 'global';
    $idSource = trim((string)($identifier ?? getClientIp()));
    if ($idSource === '') {
        $idSource = getClientIp();
    }

    if (!is_dir(RATE_LIMIT_DIR)) {
        @mkdir(RATE_LIMIT_DIR, 0755, true);
    }

    $hash = hash('sha256', $scope . '|' . $idSource);
    $path = RATE_LIMIT_DIR . '/' . $scope . '_' . $hash . '.json';
    $now = time();

    $state = [
        'window_start' => $now,
        'count' => 0,
    ];

    $blocked = false;
    $retryAfter = 0;

    $fp = @fopen($path, 'c+');
    if ($fp === false) {
        return;
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            return;
        }

        $raw = stream_get_contents($fp);
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $state['window_start'] = (int)($decoded['window_start'] ?? $now);
                $state['count'] = (int)($decoded['count'] ?? 0);
            }
        }

        if (($now - (int)$state['window_start']) >= $windowSeconds) {
            $state['window_start'] = $now;
            $state['count'] = 0;
        }

        $state['count'] = (int)$state['count'] + 1;
        if ($state['count'] > $maxRequests) {
            $blocked = true;
            $retryAfter = max(1, $windowSeconds - ($now - (int)$state['window_start']));
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($state, JSON_UNESCAPED_UNICODE));
        fflush($fp);
        flock($fp, LOCK_UN);
    } finally {
        fclose($fp);
    }

    if ($blocked) {
        header('Retry-After: ' . $retryAfter);
        sendJson(429, [
            'success' => false,
            'message' => 'Troppe richieste. Riprova tra ' . $retryAfter . ' secondi.',
        ]);
    }
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
    $base = strtoupper(substr(str_replace('-', '', $acquistoId), 0, 10));
    $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    return 'PSC-' . $base . '-' . $suffix;
}

function ensureQrTokenSchemaColumn(): void
{
    global $pdo;

    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }
    $bootstrapped = true;

    try {
        $check = $pdo->query("SHOW COLUMNS FROM profili LIKE 'qr_token'");
        $hasColumn = $check && $check->fetch();
        if (!$hasColumn) {
            $pdo->exec(
                'ALTER TABLE profili
                 ADD COLUMN qr_token VARCHAR(96) NULL UNIQUE AFTER email'
            );
        }
    } catch (Throwable $e) {
        error_log('ensureQrTokenSchemaColumn error: ' . $e->getMessage());
    }
}

function generateQrToken(): string
{
    return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
}

function generateUniqueUserQrToken(int $maxAttempts = 16): string
{
    global $pdo;

    ensureQrTokenSchemaColumn();

    $stmt = $pdo->prepare('SELECT id FROM profili WHERE qr_token = ? LIMIT 1');
    for ($i = 0; $i < $maxAttempts; $i++) {
        $candidate = generateQrToken();
        $stmt->execute([$candidate]);
        if (!$stmt->fetch()) {
            return $candidate;
        }
    }

    throw new RuntimeException('Impossibile generare qr_token univoco');
}

function getOrCreateUserQrToken(string $userId): string
{
    global $pdo;

    ensureQrTokenSchemaColumn();

    $ownsTransaction = false;
    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $ownsTransaction = true;
        }

        $stmt = $pdo->prepare('SELECT qr_token FROM profili WHERE id = ? LIMIT 1 FOR UPDATE');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Utente non trovato per qr_token');
        }

        $current = trim((string)($row['qr_token'] ?? ''));
        if ($current !== '') {
            if ($ownsTransaction) {
                $pdo->commit();
            }
            return $current;
        }

        $token = generateUniqueUserQrToken();
        $update = $pdo->prepare(
            'UPDATE profili
             SET qr_token = ?
             WHERE id = ?
               AND (qr_token IS NULL OR qr_token = "")'
        );
        $update->execute([$token, $userId]);

        if ($update->rowCount() < 1) {
            $stmt = $pdo->prepare('SELECT qr_token FROM profili WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $fallback = trim((string)($stmt->fetch()['qr_token'] ?? ''));
            if ($fallback !== '') {
                if ($ownsTransaction) {
                    $pdo->commit();
                }
                return $fallback;
            }

            throw new RuntimeException('Impossibile impostare qr_token utente');
        }

        if ($ownsTransaction) {
            $pdo->commit();
        }

        return $token;
    } catch (Throwable $e) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function buildUserQrUrl(string $qrToken): string
{
    return rtrim(localAppBaseUrl(), '/') . '/q/' . rawurlencode($qrToken);
}

function extractQrTokenFromInput(string $raw): string
{
    $value = trim($raw);
    if ($value === '') {
        return '';
    }

    if (preg_match('#/q/([A-Za-z0-9\-_]{16,128})#', $value, $match) === 1) {
        return (string)$match[1];
    }

    if (preg_match('/^[A-Za-z0-9\-_]{16,128}$/', $value) === 1) {
        return $value;
    }

    if (preg_match('/\b((?:PSC|NL)-[A-Z0-9\-]+)\b/i', $value, $legacy) === 1) {
        return strtoupper((string)$legacy[1]);
    }

    return '';
}

function getUserRoleLevel(string $userId): int
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT r.livello
         FROM profili p
         JOIN ruoli r ON p.ruolo_id = r.id
         WHERE p.id = ?
         LIMIT 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    return $row ? (int)$row['livello'] : 0;
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

function queueEmailFallback(
    string $to,
    string $toName,
    string $subject,
    string $htmlContent,
    string $textContent = '',
    array $attachments = [],
    string $reason = ''
): bool {
    global $MAIL_CONFIG;

    if (empty($MAIL_CONFIG['queue_fallback_on_error'])) {
        return false;
    }

    if (!is_dir(MAIL_QUEUE_DIR) && !@mkdir(MAIL_QUEUE_DIR, 0755, true) && !is_dir(MAIL_QUEUE_DIR)) {
        return false;
    }

    $safeAttachments = [];
    foreach ($attachments as $attachment) {
        if (!is_array($attachment)) {
            continue;
        }
        $safeAttachments[] = [
            'path' => (string)($attachment['path'] ?? ''),
            'name' => (string)($attachment['name'] ?? ''),
            'mime' => (string)($attachment['mime'] ?? ''),
            'has_string' => is_string($attachment['string'] ?? null),
        ];
    }

    $payload = [
        'queued_at' => date('c'),
        'to' => $to,
        'to_name' => $toName,
        'subject' => $subject,
        'html' => $htmlContent,
        'text' => $textContent,
        'attachments' => $safeAttachments,
        'reason' => $reason,
    ];

    $fileName = date('Ymd_His') . '_' . substr(hash('sha256', $to . '|' . $subject . '|' . microtime(true)), 0, 12) . '.json';
    $filePath = MAIL_QUEUE_DIR . '/' . $fileName;
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (!is_string($encoded) || @file_put_contents($filePath, $encoded) === false) {
        return false;
    }

    logMailEvent('warning', 'Email queued in fallback mode', [
        'to' => $to,
        'subject' => $subject,
        'queue_file' => $fileName,
        'reason' => $reason,
    ]);

    return true;
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
    $password = preg_replace('/\s+/', '', (string)($smtp['password'] ?? '')) ?: '';

    if ($host === '') {
        return false;
    }

    $placeholderMarkers = ['your_', 'inserisci', 'example', 'nuotolibero.local'];
    $usernameLower = strtolower($username);
    $passwordLower = strtolower($password);
    foreach ($placeholderMarkers as $marker) {
        if (str_contains($usernameLower, $marker) || str_contains($passwordLower, $marker)) {
            return false;
        }
    }

    if ($auth && ($username === '' || $password === '')) {
        return false;
    }

    return true;
}

function sendEmail(
    string $to,
    string $toName,
    string $subject,
    string $htmlContent,
    string $textContent = '',
    array $attachments = []
): bool
{
    global $MAIL_CONFIG;

    if (!isMailConfigured()) {
        logMailEvent('warning', 'SMTP not configured, email not sent', [
            'to' => $to,
            'subject' => $subject,
        ]);
        return queueEmailFallback($to, $toName, $subject, $htmlContent, $textContent, $attachments, 'smtp_not_configured');
    }

    if (!class_exists(PHPMailer::class)) {
        logMailEvent('error', 'PHPMailer not available. Run composer install.', [
            'to' => $to,
            'subject' => $subject,
        ]);
        return queueEmailFallback($to, $toName, $subject, $htmlContent, $textContent, $attachments, 'phpmailer_missing');
    }

    try {
        $smtp = $MAIL_CONFIG['smtp'] ?? [];

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string)($smtp['host'] ?? '');
        $mail->Port = (int)($smtp['port'] ?? 587);
        $mail->SMTPAuth = !empty($smtp['auth']);
        $mail->Username = trim((string)($smtp['username'] ?? ''));
        $mail->Password = preg_replace('/\s+/', '', (string)($smtp['password'] ?? '')) ?: '';
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

        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }

            $filePath = (string)($attachment['path'] ?? '');
            $fileName = (string)($attachment['name'] ?? '');
            $mimeType = (string)($attachment['mime'] ?? '');
            $binaryData = $attachment['string'] ?? null;

            if (is_string($binaryData) && $binaryData !== '' && $fileName !== '') {
                if ($mimeType !== '') {
                    $mail->addStringAttachment($binaryData, $fileName, 'base64', $mimeType);
                } else {
                    $mail->addStringAttachment($binaryData, $fileName);
                }
                continue;
            }

            if ($filePath !== '' && is_file($filePath)) {
                if ($fileName !== '' && $mimeType !== '') {
                    $mail->addAttachment($filePath, $fileName, 'base64', $mimeType);
                } elseif ($fileName !== '') {
                    $mail->addAttachment($filePath, $fileName);
                } else {
                    $mail->addAttachment($filePath);
                }
            }
        }

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
        if (queueEmailFallback($to, $toName, $subject, $htmlContent, $textContent, $attachments, (string)$e->getMessage())) {
            return true;
        }
        return false;
    }
}

function buildBrandedEmail(string $title, string $bodyHtml, string $previewText = ''): string
{
    $preview = $previewText !== '' ? $previewText : $title;
    $safePreview = htmlspecialchars($preview, ENT_QUOTES, 'UTF-8');
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

    return '<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>'
        . '<body style="margin:0;padding:0;background:#f8f9fa;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">'
        . '<div style="max-width:640px;margin:0 auto;padding:24px;">'
        . '<div style="background:linear-gradient(135deg,#00a8e8,#0077b6);padding:18px 22px;border-radius:12px 12px 0 0;color:#ffffff;">'
        . '<div style="font-size:22px;font-weight:700;line-height:1.2;">' . SITE_NAME . '</div>'
        . '<div style="margin-top:4px;font-size:13px;opacity:0.95;">' . $safePreview . '</div>'
        . '</div>'
        . '<div style="background:#ffffff;padding:24px;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;">'
        . '<h2 style="margin:0 0 16px 0;color:#0077b6;font-size:20px;">' . $safeTitle . '</h2>'
        . $bodyHtml
        . '<hr style="border:none;border-top:1px solid #e5e7eb;margin:24px 0;">'
        . '<p style="margin:0;font-size:12px;color:#6b7280;">Messaggio automatico - ' . SITE_NAME . '</p>'
        . '</div></div></body></html>';
}

function sendBrandedEmail(
    string $to,
    string $toName,
    string $subject,
    string $title,
    string $bodyHtml,
    string $previewText = '',
    string $textContent = '',
    array $attachments = []
): bool {
    $html = buildBrandedEmail($title, $bodyHtml, $previewText);
    return sendEmail($to, $toName, $subject, $html, $textContent, $attachments);
}

function sendTemplateEmail(
    string $to,
    string $toName,
    string $subject,
    string $title,
    string $bodyHtml,
    string $previewText = '',
    string $textContent = '',
    array $attachments = []
): bool {
    return sendBrandedEmail($to, $toName, $subject, $title, $bodyHtml, $previewText, $textContent, $attachments);
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
            $days = (int)$row['giorni_alla_scadenza'];
            $body = '<p>Ciao <strong>' . htmlspecialchars((string)$row['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
                . '<p>il tuo pacchetto <strong>' . htmlspecialchars((string)$row['pacchetto_nome'], ENT_QUOTES, 'UTF-8') . '</strong> scadra tra <strong>' . $days . ' giorni</strong>.</p>'
                . '<p>Data scadenza: <strong>' . htmlspecialchars((string)$row['data_scadenza'], ENT_QUOTES, 'UTF-8') . '</strong></p>';

            sendTemplateEmail(
                (string)$row['email'],
                trim((string)$row['nome'] . ' ' . (string)$row['cognome']),
                'Promemoria scadenza pacchetto',
                'Pacchetto in scadenza',
                $body,
                'Promemoria scadenza pacchetto'
            );
        }
    } catch (Throwable $e) {
        error_log('dispatchPendingExpiryReminders error: ' . $e->getMessage());
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

function getDefaultOperationalScannerWindows(): array
{
    return [
        ['day' => 1, 'start' => '06:30', 'end' => '09:00'],
        ['day' => 1, 'start' => '13:00', 'end' => '14:00'],
        ['day' => 3, 'start' => '06:30', 'end' => '09:00'],
        ['day' => 3, 'start' => '13:00', 'end' => '14:00'],
        ['day' => 5, 'start' => '06:30', 'end' => '09:00'],
        ['day' => 5, 'start' => '13:00', 'end' => '14:00'],
    ];
}

function buildH24ScannerWindows(): array
{
    $windows = [];
    for ($day = 1; $day <= 7; $day++) {
        $windows[] = [
            'day' => $day,
            'start' => '00:00',
            'end' => '23:59',
        ];
    }
    return $windows;
}

function scannerTimeToMinutes(string $time): int
{
    [$hour, $minute] = array_map('intval', explode(':', $time, 2));
    return ($hour * 60) + $minute;
}

function normalizeScannerWindows(array $windows): array
{
    $normalized = [];
    foreach ($windows as $window) {
        if (!is_array($window)) {
            continue;
        }

        $day = (int)($window['day'] ?? 0);
        $start = trim((string)($window['start'] ?? ''));
        $end = trim((string)($window['end'] ?? ''));

        if ($day < 1 || $day > 7) {
            continue;
        }
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $start)) {
            continue;
        }
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $end)) {
            continue;
        }
        if (scannerTimeToMinutes($end) <= scannerTimeToMinutes($start)) {
            continue;
        }

        $key = $day . '|' . $start . '|' . $end;
        $normalized[$key] = [
            'day' => $day,
            'start' => $start,
            'end' => $end,
        ];
    }

    $rows = array_values($normalized);
    usort($rows, static function (array $a, array $b): int {
        if ((int)$a['day'] === (int)$b['day']) {
            return strcmp((string)$a['start'], (string)$b['start']);
        }
        return ((int)$a['day'] <=> (int)$b['day']);
    });

    return $rows;
}

function ensureOperationalSettingsTable(): void
{
    global $pdo;

    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'impostazioni_operative'");
        if ($exists && $exists->fetch()) {
            return;
        }

        $pdo->exec(
            'CREATE TABLE impostazioni_operative (
                id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
                scanner_enabled TINYINT(1) NOT NULL DEFAULT 1,
                h24_mode TINYINT(1) NOT NULL DEFAULT 0,
                schedule_json LONGTEXT NULL,
                updated_by CHAR(36) NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_impostazioni_operative_updated_by
                    FOREIGN KEY (updated_by) REFERENCES profili(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    } catch (Throwable $e) {
        error_log('ensureOperationalSettingsTable error: ' . $e->getMessage());
    }
}

function setOperationalSettingsCache(?array $settings): void
{
    $GLOBALS['__scanner_operational_settings_cache'] = $settings;
}

function getOperationalSettings(): array
{
    global $pdo;

    if (array_key_exists('__scanner_operational_settings_cache', $GLOBALS) && is_array($GLOBALS['__scanner_operational_settings_cache'])) {
        return $GLOBALS['__scanner_operational_settings_cache'];
    }

    $default = [
        'scanner_enabled' => true,
        'h24_mode' => false,
        'windows' => getDefaultOperationalScannerWindows(),
    ];

    try {
        ensureOperationalSettingsTable();

        $stmt = $pdo->query(
            'SELECT scanner_enabled, h24_mode, schedule_json
             FROM impostazioni_operative
             WHERE id = 1
             LIMIT 1'
        );
        $row = $stmt ? $stmt->fetch() : false;
        if (!$row) {
            setOperationalSettingsCache($default);
            return $default;
        }

        $decodedWindows = json_decode((string)($row['schedule_json'] ?? ''), true);
        if (!is_array($decodedWindows)) {
            $decodedWindows = [];
        }

        $settings = [
            'scanner_enabled' => (int)($row['scanner_enabled'] ?? 1) === 1,
            'h24_mode' => (int)($row['h24_mode'] ?? 0) === 1,
            'windows' => normalizeScannerWindows($decodedWindows),
        ];

        if ($settings['h24_mode']) {
            $settings['windows'] = buildH24ScannerWindows();
        } elseif (!$settings['windows']) {
            $settings['windows'] = getDefaultOperationalScannerWindows();
        }

        setOperationalSettingsCache($settings);
        return $settings;
    } catch (Throwable $e) {
        error_log('getOperationalSettings error: ' . $e->getMessage());
        setOperationalSettingsCache($default);
        return $default;
    }
}

function saveOperationalSettings(array $settings, ?string $updatedBy = null): array
{
    global $pdo;

    $scannerEnabled = !empty($settings['scanner_enabled']);
    $h24Mode = !empty($settings['h24_mode']);
    $windows = normalizeScannerWindows(is_array($settings['windows'] ?? null) ? $settings['windows'] : []);

    if ($h24Mode) {
        $windows = buildH24ScannerWindows();
    }

    $payload = [
        'scanner_enabled' => $scannerEnabled,
        'h24_mode' => $h24Mode,
        'windows' => $windows,
    ];

    ensureOperationalSettingsTable();

    $stmt = $pdo->prepare(
        'INSERT INTO impostazioni_operative (id, scanner_enabled, h24_mode, schedule_json, updated_by, updated_at)
         VALUES (1, ?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE
           scanner_enabled = VALUES(scanner_enabled),
           h24_mode = VALUES(h24_mode),
           schedule_json = VALUES(schedule_json),
           updated_by = VALUES(updated_by),
           updated_at = NOW()'
    );
    $stmt->execute([
        $scannerEnabled ? 1 : 0,
        $h24Mode ? 1 : 0,
        json_encode($windows, JSON_UNESCAPED_UNICODE),
        $updatedBy !== null && $updatedBy !== '' ? $updatedBy : null,
    ]);

    setOperationalSettingsCache($payload);
    return $payload;
}

function getIngressScheduleConfig(): array
{
    return getOperationalSettings();
}

function getIngressScheduleSummary(): string
{
    $config = getIngressScheduleConfig();
    if (empty($config['scanner_enabled'])) {
        return 'Scanner check-in disattivato';
    }
    if (!empty($config['h24_mode'])) {
        return 'Tutti i giorni - 00:00-23:59';
    }

    $windows = normalizeScannerWindows(is_array($config['windows'] ?? null) ? $config['windows'] : []);
    if (!$windows) {
        return 'Nessuna finestra oraria configurata';
    }

    $dayLabels = [
        1 => 'Lunedi',
        2 => 'Martedi',
        3 => 'Mercoledi',
        4 => 'Giovedi',
        5 => 'Venerdi',
        6 => 'Sabato',
        7 => 'Domenica',
    ];

    $grouped = [];
    foreach ($windows as $window) {
        $day = (int)$window['day'];
        $grouped[$day][] = (string)$window['start'] . '-' . (string)$window['end'];
    }

    $chunks = [];
    foreach ($grouped as $day => $slots) {
        $chunks[] = ($dayLabels[$day] ?? ('Giorno ' . $day)) . ' ' . implode(', ', $slots);
    }

    return implode('; ', $chunks);
}

function resolveIngressFasciaForDateTime(DateTimeInterface $dateTime): ?string
{
    $config = getIngressScheduleConfig();
    if (empty($config['scanner_enabled'])) {
        return null;
    }

    $isoDay = (int)$dateTime->format('N');
    $minutesNow = ((int)$dateTime->format('H') * 60) + (int)$dateTime->format('i');
    $windows = normalizeScannerWindows(is_array($config['windows'] ?? null) ? $config['windows'] : []);

    foreach ($windows as $window) {
        if ((int)$window['day'] !== $isoDay) {
            continue;
        }

        $startMinutes = scannerTimeToMinutes((string)$window['start']);
        $endMinutes = scannerTimeToMinutes((string)$window['end']);
        if ($minutesNow >= $startMinutes && $minutesNow <= $endMinutes) {
            return getFasciaOraria($dateTime->format('H:i'));
        }
    }

    return null;
}

function getCurrentIngressFasciaOraria(): ?string
{
    return resolveIngressFasciaForDateTime(new DateTimeImmutable('now'));
}

function getProjectSiteMode(): string
{
    $mode = strtolower(trim((string)(getenv('SITE_MODE') ?: 'full')));
    return in_array($mode, ['landing', 'full'], true) ? $mode : 'full';
}

function setProjectEnvValue(string $key, string $value): bool
{
    if (!preg_match('/^[A-Z0-9_]+$/', $key)) {
        return false;
    }

    $sanitizedValue = str_replace(["\r", "\n"], '', trim($value));
    $envPath = PROJECT_ROOT . '/.env';

    $lines = [];
    if (is_file($envPath)) {
        $existing = file($envPath, FILE_IGNORE_NEW_LINES);
        if (is_array($existing)) {
            $lines = $existing;
        }
    }

    $found = false;
    foreach ($lines as $index => $line) {
        $trim = trim((string)$line);
        if ($trim === '' || str_starts_with($trim, '#')) {
            continue;
        }

        $parts = explode('=', $trim, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $currentKey = trim((string)$parts[0]);
        if ($currentKey !== $key) {
            continue;
        }

        $lines[$index] = $key . '=' . $sanitizedValue;
        $found = true;
        break;
    }

    if (!$found) {
        $lines[] = $key . '=' . $sanitizedValue;
    }

    $content = implode(PHP_EOL, $lines);
    if ($content !== '' && !str_ends_with($content, PHP_EOL)) {
        $content .= PHP_EOL;
    }

    if (@file_put_contents($envPath, $content, LOCK_EX) === false) {
        return false;
    }

    putenv($key . '=' . $sanitizedValue);
    $_ENV[$key] = $sanitizedValue;
    $_SERVER[$key] = $sanitizedValue;

    return true;
}

function getFasciaOraria(string $ora): string
{
    $time = trim($ora);
    if ($time === '') {
        return 'mattina';
    }

    try {
        $dt = new DateTimeImmutable('today ' . $time);
    } catch (Throwable $e) {
        return 'mattina';
    }

    $minutes = ((int)$dt->format('H') * 60) + (int)$dt->format('i');
    return $minutes < (13 * 60) ? 'mattina' : 'pomeriggio';
}

function sanitizeInput($input)
{
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }

    return htmlspecialchars(strip_tags(trim((string)$input)), ENT_QUOTES, 'UTF-8');
}


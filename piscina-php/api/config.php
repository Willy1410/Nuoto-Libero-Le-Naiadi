<?php
/**
 * CONFIGURAZIONE DATABASE E CORS
 * File: api/config.php
 */

// Abilita visualizzazione errori (disabilitare in produzione)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurazione CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=UTF-8');

// Gestione preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configurazione database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Lascia vuoto per XAMPP default
define('DB_NAME', 'piscina_gestione');
define('DB_CHARSET', 'utf8mb4');

// Configurazione JWT
define('JWT_SECRET', 'LA_TUA_CHIAVE_SEGRETA_SUPER_SICURA_12345'); // CAMBIARE IN PRODUZIONE
define('JWT_EXPIRATION', 86400); // 24 ore in secondi

// Configurazione upload
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED_TYPES', ['pdf', 'jpg', 'jpeg', 'png']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Configurazione email (Brevo)
define('BREVO_API_KEY', ''); // Inserire chiave API Brevo
define('BREVO_FROM_EMAIL', 'noreply@piscina.it');
define('BREVO_FROM_NAME', 'Piscina Naiadi');

// Timezone
date_default_timezone_set('Europe/Rome');

// Connessione database
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore connessione database',
        'error' => $e->getMessage()
    ]);
    exit();
}

/**
 * Funzione per generare JWT
 */
function generateJWT($userId, $email, $role) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode([
        'user_id' => $userId,
        'email' => $email,
        'role' => $role,
        'iat' => time(),
        'exp' => time() + JWT_EXPIRATION
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Funzione per verificare JWT
 */
function verifyJWT($token) {
    if (!$token) {
        return null;
    }
    
    $tokenParts = explode('.', $token);
    if (count($tokenParts) !== 3) {
        return null;
    }
    
    $header = base64_decode($tokenParts[0]);
    $payload = base64_decode($tokenParts[1]);
    $signatureProvided = $tokenParts[2];
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, JWT_SECRET, true);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    if ($base64UrlSignature !== $signatureProvided) {
        return null;
    }
    
    $payloadData = json_decode($payload, true);
    
    if (!isset($payloadData['exp']) || $payloadData['exp'] < time()) {
        return null;
    }
    
    return $payloadData;
}

/**
 * Funzione per ottenere l'utente corrente
 */
function getCurrentUser() {
    $headers = getallheaders();
    $token = null;
    
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }
    }
    
    return verifyJWT($token);
}

/**
 * Funzione per richiedere autenticazione
 */
function requireAuth() {
    $user = getCurrentUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non autenticato']);
        exit();
    }
    return $user;
}

/**
 * Funzione per verificare ruolo minimo
 */
function requireRole($minLevel) {
    global $pdo;
    $user = requireAuth();
    
    $stmt = $pdo->prepare("SELECT r.livello FROM profili p JOIN ruoli r ON p.ruolo_id = r.id WHERE p.id = ?");
    $stmt->execute([$user['user_id']]);
    $profile = $stmt->fetch();
    
    if (!$profile || $profile['livello'] < $minLevel) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Accesso negato']);
        exit();
    }
    
    return $user;
}

/**
 * Funzione per logging attività
 */
function logActivity($userId, $azione, $descrizione = '', $tabella = '', $recordId = '') {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, azione, descrizione, tabella_riferimento, record_id, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$userId, $azione, $descrizione, $tabella, $recordId, $ip, $userAgent]);
}

/**
 * Funzione per generare codice QR univoco
 */
function generateQRCode($acquisto_id) {
    return 'PSC-' . $acquisto_id . '-' . time();
}

/**
 * Funzione per inviare email (Brevo)
 */
function sendEmail($to, $toName, $subject, $htmlContent) {
    if (empty(BREVO_API_KEY)) {
        return false; // API key non configurata
    }
    
    $url = 'https://api.brevo.com/v3/smtp/email';
    
    $data = [
        'sender' => [
            'email' => BREVO_FROM_EMAIL,
            'name' => BREVO_FROM_NAME
        ],
        'to' => [
            [
                'email' => $to,
                'name' => $toName
            ]
        ],
        'subject' => $subject,
        'htmlContent' => $htmlContent
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . BREVO_API_KEY,
        'content-type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}

/**
 * Funzione per validare email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Funzione per validare codice fiscale italiano
 */
function validateCodiceFiscale($cf) {
    return preg_match('/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/', strtoupper($cf));
}

/**
 * Funzione per calcolare fascia oraria
 */
function getFasciaOraria($ora) {
    $hour = (int)date('H', strtotime($ora));
    return ($hour < 14) ? 'mattina' : 'pomeriggio';
}

/**
 * Funzione per sanitizzare input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

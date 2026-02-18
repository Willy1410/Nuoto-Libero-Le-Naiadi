<?php
declare(strict_types=1);

/**
 * API autenticazione
 */

require_once __DIR__ . '/config.php';

$action = (string)($_GET['action'] ?? '');

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'me':
        handleMe();
        break;
    case 'update-profile':
        handleUpdateProfile();
        break;
    case 'change-password':
        handleChangePassword();
        break;
    case 'forgot-password':
        handleForgotPassword();
        break;
    case 'validate-reset-token':
        handleValidateResetToken();
        break;
    case 'reset-password':
        handleResetPassword();
        break;
    default:
        sendJson(400, ['success' => false, 'message' => 'Azione non valida']);
}

function handleRegister(): void
{
    global $pdo;

    enforceRateLimit('auth-register', 5, 900);

    $data = getJsonInput();

    $email = strtolower(sanitizeText((string)($data['email'] ?? ''), 255));
    $password = (string)($data['password'] ?? '');
    $nome = sanitizeText((string)($data['nome'] ?? ''), 100);
    $cognome = sanitizeText((string)($data['cognome'] ?? ''), 100);
    $telefono = sanitizeText((string)($data['telefono'] ?? ''), 30);
    $dataNascita = sanitizeText((string)($data['data_nascita'] ?? ''), 10);
    $indirizzo = sanitizeText((string)($data['indirizzo'] ?? ''), 255);
    $citta = sanitizeText((string)($data['citta'] ?? ''), 100);
    $cap = sanitizeText((string)($data['cap'] ?? ''), 10);
    $codiceFiscale = strtoupper(sanitizeText((string)($data['codice_fiscale'] ?? ''), 16));

    if ($email === '' || $password === '' || $nome === '' || $cognome === '') {
        sendJson(400, ['success' => false, 'message' => 'Compila tutti i campi obbligatori']);
    }

    if (!validateEmail($email)) {
        sendJson(400, ['success' => false, 'message' => 'Email non valida']);
    }

    if (!validatePasswordStrength($password)) {
        sendJson(400, ['success' => false, 'message' => 'Password troppo debole (minimo 8 caratteri)']);
    }

    if ($codiceFiscale !== '' && !validateCodiceFiscale($codiceFiscale)) {
        sendJson(400, ['success' => false, 'message' => 'Codice fiscale non valido']);
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM profili WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendJson(409, ['success' => false, 'message' => 'Email gia registrata']);
        }

        if ($codiceFiscale !== '') {
            $stmt = $pdo->prepare('SELECT id FROM profili WHERE codice_fiscale = ? LIMIT 1');
            $stmt->execute([$codiceFiscale]);
            if ($stmt->fetch()) {
                sendJson(409, ['success' => false, 'message' => 'Codice fiscale gia registrato']);
            }
        }

        $ruoloStmt = $pdo->query("SELECT id, nome, livello FROM ruoli WHERE nome = 'utente' LIMIT 1");
        $ruolo = $ruoloStmt->fetch();
        if (!$ruolo) {
            sendJson(500, ['success' => false, 'message' => 'Ruolo utente non configurato']);
        }

        $userId = generateUuid();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO profili
            (id, ruolo_id, email, password_hash, nome, cognome, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale, attivo, email_verificata)
            VALUES (?, ?, ?, ?, ?, ?, ?, NULLIF(?, ""), ?, ?, ?, NULLIF(?, ""), 1, 0)'
        );
        $stmt->execute([
            $userId,
            $ruolo['id'],
            $email,
            $hash,
            $nome,
            $cognome,
            $telefono,
            $dataNascita,
            $indirizzo,
            $citta,
            $cap,
            $codiceFiscale,
        ]);

        $token = generateJWT($userId, $email, (string)$ruolo['nome'], (int)$ruolo['livello']);

        logActivity($userId, 'registrazione', 'Registrazione utente', 'profili', $userId);

        $body = '<p>Ciao <strong>' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>la registrazione e stata completata correttamente.</p>'
            . '<p>Ora puoi accedere alla tua area riservata.</p>';
        sendTemplateEmail($email, $nome . ' ' . $cognome, 'Benvenuto in Nuoto Libero', 'Registrazione completata', $body, 'Benvenuto in Nuoto Libero');

        sendJson(201, [
            'success' => true,
            'message' => 'Registrazione completata',
            'token' => $token,
            'user' => [
                'id' => $userId,
                'email' => $email,
                'nome' => $nome,
                'cognome' => $cognome,
                'ruolo' => $ruolo['nome'],
                'livello' => (int)$ruolo['livello'],
            ],
        ]);
    } catch (Throwable $e) {
        error_log('register error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore durante la registrazione']);
    }
}

function handleLogin(): void
{
    global $pdo;

    enforceRateLimit('auth-login-ip', 40, 300);

    $data = getJsonInput();

    $email = strtolower(sanitizeText((string)($data['email'] ?? ''), 255));
    $password = (string)($data['password'] ?? '');

    if ($email === '' || $password === '') {
        sendJson(400, ['success' => false, 'message' => 'Email e password obbligatorie']);
    }

    if (!validateEmail($email)) {
        sendJson(400, ['success' => false, 'message' => 'Email non valida']);
    }

    enforceRateLimit('auth-login-identity', 15, 300, getClientIp() . '|' . $email);

    $cooldown = &$_SESSION['login_cooldown'];
    if (!is_array($cooldown)) {
        $cooldown = [];
    }

    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $cooldownKey = hash('sha256', $ip . '|' . $email);
    $entry = $cooldown[$cooldownKey] ?? ['attempts' => 0, 'blocked_until' => 0, 'first_attempt_at' => time()];

    if (($entry['blocked_until'] ?? 0) > time()) {
        $remaining = (int)$entry['blocked_until'] - time();
        sendJson(429, [
            'success' => false,
            'message' => 'Troppi tentativi. Riprova tra ' . max(1, $remaining) . ' secondi.',
        ]);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT p.id, p.email, p.password_hash, p.nome, p.cognome, p.attivo, p.email_verificata, p.force_password_change,
                    r.nome AS ruolo_nome, r.livello AS ruolo_livello
             FROM profili p
             JOIN ruoli r ON p.ruolo_id = r.id
             WHERE p.email = ?
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || (int)$user['attivo'] !== 1 || !password_verify($password, (string)$user['password_hash'])) {
            $attempts = (int)($entry['attempts'] ?? 0) + 1;
            $blockedUntil = 0;
            if ($attempts >= 5) {
                $blockedUntil = time() + 600;
            }
            $cooldown[$cooldownKey] = [
                'attempts' => $attempts,
                'blocked_until' => $blockedUntil,
                'first_attempt_at' => (int)($entry['first_attempt_at'] ?? time()),
            ];

            sendJson(401, ['success' => false, 'message' => 'Credenziali non valide']);
        }

        unset($cooldown[$cooldownKey]);

        $stmt = $pdo->prepare('UPDATE profili SET ultimo_accesso = NOW() WHERE id = ?');
        $stmt->execute([$user['id']]);

        $token = generateJWT(
            (string)$user['id'],
            (string)$user['email'],
            (string)$user['ruolo_nome'],
            (int)$user['ruolo_livello']
        );

        logActivity((string)$user['id'], 'login', 'Login eseguito', 'profili', (string)$user['id']);

        dispatchPendingExpiryReminders((string)$user['id']);

        sendJson(200, [
            'success' => true,
            'message' => 'Login effettuato',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'nome' => $user['nome'],
                'cognome' => $user['cognome'],
                'ruolo' => $user['ruolo_nome'],
                'livello' => (int)$user['ruolo_livello'],
                'email_verificata' => (bool)$user['email_verificata'],
                'force_password_change' => (bool)($user['force_password_change'] ?? false),
            ],
        ]);
    } catch (Throwable $e) {
        error_log('login error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore durante il login']);
    }
}

function handleLogout(): void
{
    session_regenerate_id(true);
    sendJson(200, ['success' => true, 'message' => 'Logout effettuato']);
}

function handleMe(): void
{
    global $pdo;

    $currentUser = requireAuth();

    try {
        $stmt = $pdo->prepare(
            'SELECT p.id, p.email, p.nome, p.cognome, p.telefono, p.data_nascita, p.indirizzo, p.citta, p.cap,
                    p.codice_fiscale, p.attivo, p.email_verificata, p.force_password_change, p.ultimo_accesso,
                    r.nome AS ruolo_nome, r.livello AS ruolo_livello
             FROM profili p
             JOIN ruoli r ON p.ruolo_id = r.id
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$currentUser['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJson(404, ['success' => false, 'message' => 'Utente non trovato']);
        }

        sendJson(200, ['success' => true, 'user' => $user]);
    } catch (Throwable $e) {
        error_log('me error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore recupero profilo']);
    }
}

function handleUpdateProfile(): void
{
    global $pdo;

    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if (!in_array($method, ['PATCH', 'POST'], true)) {
        sendJson(405, ['success' => false, 'message' => 'Metodo non consentito']);
    }

    $currentUser = requireAuth();
    enforceRateLimit('auth-update-profile', 25, 600, getClientIp() . '|' . (string)$currentUser['user_id']);

    $data = getJsonInput();

    try {
        $stmt = $pdo->prepare(
            'SELECT p.id, p.email, p.nome, p.cognome, p.telefono, p.data_nascita, p.indirizzo, p.citta, p.cap,
                    p.codice_fiscale, r.nome AS ruolo_nome, r.livello AS ruolo_livello
             FROM profili p
             JOIN ruoli r ON r.id = p.ruolo_id
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$currentUser['user_id']]);
        $existing = $stmt->fetch();
        if (!$existing) {
            sendJson(404, ['success' => false, 'message' => 'Utente non trovato']);
        }

        $email = array_key_exists('email', $data)
            ? strtolower(sanitizeText((string)$data['email'], 255))
            : (string)$existing['email'];
        $nome = array_key_exists('nome', $data)
            ? sanitizeText((string)$data['nome'], 100)
            : (string)$existing['nome'];
        $cognome = array_key_exists('cognome', $data)
            ? sanitizeText((string)$data['cognome'], 100)
            : (string)$existing['cognome'];
        $telefono = array_key_exists('telefono', $data)
            ? sanitizeText((string)$data['telefono'], 30)
            : (string)($existing['telefono'] ?? '');
        $dataNascita = array_key_exists('data_nascita', $data)
            ? sanitizeText((string)$data['data_nascita'], 10)
            : (string)($existing['data_nascita'] ?? '');
        $indirizzo = array_key_exists('indirizzo', $data)
            ? sanitizeText((string)$data['indirizzo'], 255)
            : (string)($existing['indirizzo'] ?? '');
        $citta = array_key_exists('citta', $data)
            ? sanitizeText((string)$data['citta'], 100)
            : (string)($existing['citta'] ?? '');
        $cap = array_key_exists('cap', $data)
            ? sanitizeText((string)$data['cap'], 10)
            : (string)($existing['cap'] ?? '');
        $codiceFiscale = array_key_exists('codice_fiscale', $data)
            ? strtoupper(sanitizeText((string)$data['codice_fiscale'], 16))
            : strtoupper((string)($existing['codice_fiscale'] ?? ''));

        if ($email === '' || $nome === '' || $cognome === '') {
            sendJson(400, ['success' => false, 'message' => 'Nome, cognome ed email sono obbligatori']);
        }
        if (!validateEmail($email)) {
            sendJson(400, ['success' => false, 'message' => 'Email non valida']);
        }
        if ($dataNascita !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNascita)) {
            sendJson(400, ['success' => false, 'message' => 'Data di nascita non valida']);
        }
        if ($codiceFiscale !== '' && !validateCodiceFiscale($codiceFiscale)) {
            sendJson(400, ['success' => false, 'message' => 'Codice fiscale non valido']);
        }

        $stmt = $pdo->prepare('SELECT id FROM profili WHERE email = ? AND id <> ? LIMIT 1');
        $stmt->execute([$email, $currentUser['user_id']]);
        if ($stmt->fetch()) {
            sendJson(409, ['success' => false, 'message' => 'Email gia in uso']);
        }

        if ($codiceFiscale !== '') {
            $stmt = $pdo->prepare('SELECT id FROM profili WHERE codice_fiscale = ? AND id <> ? LIMIT 1');
            $stmt->execute([$codiceFiscale, $currentUser['user_id']]);
            if ($stmt->fetch()) {
                sendJson(409, ['success' => false, 'message' => 'Codice fiscale gia in uso']);
            }
        }

        $stmt = $pdo->prepare(
            'UPDATE profili
             SET email = ?, nome = ?, cognome = ?, telefono = ?, data_nascita = ?, indirizzo = ?, citta = ?, cap = ?, codice_fiscale = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $email,
            $nome,
            $cognome,
            $telefono !== '' ? $telefono : null,
            $dataNascita !== '' ? $dataNascita : null,
            $indirizzo !== '' ? $indirizzo : null,
            $citta !== '' ? $citta : null,
            $cap !== '' ? $cap : null,
            $codiceFiscale !== '' ? $codiceFiscale : null,
            $currentUser['user_id'],
        ]);

        $token = generateJWT(
            (string)$currentUser['user_id'],
            $email,
            (string)$existing['ruolo_nome'],
            (int)$existing['ruolo_livello']
        );

        logActivity(
            (string)$currentUser['user_id'],
            'aggiornamento_profilo',
            'Aggiornamento dati profilo utente',
            'profili',
            (string)$currentUser['user_id']
        );

        sendJson(200, [
            'success' => true,
            'message' => 'Profilo aggiornato',
            'token' => $token,
            'user' => [
                'id' => $currentUser['user_id'],
                'email' => $email,
                'nome' => $nome,
                'cognome' => $cognome,
                'ruolo' => $existing['ruolo_nome'],
                'livello' => (int)$existing['ruolo_livello'],
            ],
            'profilo' => [
                'telefono' => $telefono !== '' ? $telefono : null,
                'data_nascita' => $dataNascita !== '' ? $dataNascita : null,
                'indirizzo' => $indirizzo !== '' ? $indirizzo : null,
                'citta' => $citta !== '' ? $citta : null,
                'cap' => $cap !== '' ? $cap : null,
                'codice_fiscale' => $codiceFiscale !== '' ? $codiceFiscale : null,
            ],
        ]);
    } catch (Throwable $e) {
        error_log('update-profile error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore aggiornamento profilo']);
    }
}

function handleChangePassword(): void
{
    global $pdo;

    $currentUser = requireAuth();
    $data = getJsonInput();

    $oldPassword = (string)($data['old_password'] ?? '');
    $newPassword = (string)($data['new_password'] ?? '');

    if ($oldPassword === '' || $newPassword === '') {
        sendJson(400, ['success' => false, 'message' => 'Vecchia e nuova password obbligatorie']);
    }

    if (!validatePasswordStrength($newPassword)) {
        sendJson(400, ['success' => false, 'message' => 'Nuova password troppo debole (minimo 8 caratteri)']);
    }

    try {
        $stmt = $pdo->prepare('SELECT password_hash FROM profili WHERE id = ? LIMIT 1');
        $stmt->execute([$currentUser['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($oldPassword, (string)$user['password_hash'])) {
            sendJson(401, ['success' => false, 'message' => 'Vecchia password non corretta']);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE profili SET password_hash = ?, force_password_change = 0 WHERE id = ?');
        $stmt->execute([$newHash, $currentUser['user_id']]);

        logActivity((string)$currentUser['user_id'], 'cambio_password', 'Password aggiornata', 'profili', (string)$currentUser['user_id']);

        sendJson(200, ['success' => true, 'message' => 'Password aggiornata']);
    } catch (Throwable $e) {
        error_log('change-password error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore aggiornamento password']);
    }
}

function handleForgotPassword(): void
{
    global $pdo;

    enforceRateLimit('auth-forgot-password', 8, 900);

    $data = getJsonInput();
    $email = strtolower(sanitizeText((string)($data['email'] ?? ''), 255));

    $generic = ['success' => true, 'message' => 'Se l\'email esiste, riceverai un link di reset password.'];

    if ($email === '' || !validateEmail($email)) {
        sendJson(200, $generic);
    }

    $cooldown = &$_SESSION['password_reset_cooldown'];
    if (!is_array($cooldown)) {
        $cooldown = [];
    }

    $cooldownKey = hash('sha256', ((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown')) . '|' . $email);
    $lastRequestAt = (int)($cooldown[$cooldownKey] ?? 0);
    if ($lastRequestAt > 0 && (time() - $lastRequestAt) < 120) {
        sendJson(200, $generic);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT p.id, p.nome, p.cognome, p.email
             FROM profili p
             WHERE p.email = ? AND p.attivo = 1
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        $cooldown[$cooldownKey] = time();

        if (!$user) {
            sendJson(200, $generic);
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $tokenId = generateUuid();

        $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
            ->execute([$user['id']]);

        $stmt = $pdo->prepare(
            'INSERT INTO password_reset_tokens
            (id, user_id, token_hash, expires_at, requested_ip, requested_user_agent)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE), ?, ?)'
        );
        $stmt->execute([
            $tokenId,
            $user['id'],
            $tokenHash,
            (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]);

        $resetLink = localAppBaseUrl() . '/reset-password.html?token=' . urlencode($token);

        $body = '<p>Ciao <strong>' . htmlspecialchars((string)$user['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>abbiamo ricevuto una richiesta di reset password.</p>'
            . '<p><a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:10px 16px;background:#00a8e8;color:#fff;text-decoration:none;border-radius:6px;">Imposta nuova password</a></p>'
            . '<p>Il link scade tra 60 minuti ed e utilizzabile una sola volta.</p>'
            . '<p>Se non hai richiesto il reset, ignora questa email.</p>';

        sendTemplateEmail(
            (string)$user['email'],
            trim((string)$user['nome'] . ' ' . (string)$user['cognome']),
            'Reset password Nuoto Libero',
            'Reset password richiesto',
            $body,
            'Link per reset password'
        );

        logActivity((string)$user['id'], 'richiesta_reset_password', 'Richiesta reset password', 'password_reset_tokens', $tokenId);

        sendJson(200, $generic);
    } catch (Throwable $e) {
        error_log('forgot-password error: ' . $e->getMessage());
        sendJson(200, $generic);
    }
}

function handleValidateResetToken(): void
{
    global $pdo;

    enforceRateLimit('auth-validate-reset-token', 60, 300);

    $token = sanitizeText((string)($_GET['token'] ?? ''), 128);
    if ($token === '') {
        sendJson(400, ['success' => false, 'valid' => false, 'message' => 'Token mancante']);
    }

    $tokenHash = hash('sha256', $token);

    try {
        $stmt = $pdo->prepare(
            'SELECT id
             FROM password_reset_tokens
             WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);

        if (!$stmt->fetch()) {
            sendJson(200, ['success' => true, 'valid' => false, 'message' => 'Token non valido o scaduto']);
        }

        sendJson(200, ['success' => true, 'valid' => true]);
    } catch (Throwable $e) {
        error_log('validate-reset-token error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'valid' => false, 'message' => 'Errore validazione token']);
    }
}

function handleResetPassword(): void
{
    global $pdo;

    enforceRateLimit('auth-reset-password', 10, 900);

    $data = getJsonInput();

    $token = sanitizeText((string)($data['token'] ?? ''), 128);
    $newPassword = (string)($data['new_password'] ?? '');

    if ($token === '' || $newPassword === '') {
        sendJson(400, ['success' => false, 'message' => 'Token e nuova password obbligatori']);
    }

    if (!validatePasswordStrength($newPassword)) {
        sendJson(400, ['success' => false, 'message' => 'Password troppo debole (minimo 8 caratteri)']);
    }

    $tokenHash = hash('sha256', $token);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT prt.id, prt.user_id, p.email, p.nome, p.cognome
             FROM password_reset_tokens prt
             JOIN profili p ON p.id = prt.user_id
             WHERE prt.token_hash = ?
               AND prt.used_at IS NULL
               AND prt.expires_at > NOW()
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();

        if (!$row) {
            $pdo->rollBack();
            sendJson(400, ['success' => false, 'message' => 'Token non valido o scaduto']);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE profili SET password_hash = ?, force_password_change = 0 WHERE id = ?')->execute([$newHash, $row['user_id']]);

        $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?')->execute([$row['id']]);

        $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$row['user_id']]);

        $pdo->commit();

        logActivity((string)$row['user_id'], 'reset_password', 'Password resettata via token', 'password_reset_tokens', (string)$row['id']);

        $body = '<p>Ciao <strong>' . htmlspecialchars((string)$row['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>la tua password e stata aggiornata correttamente.</p>'
            . '<p>Se non sei stato tu, contatta subito la segreteria.</p>';
        sendTemplateEmail(
            (string)$row['email'],
            trim((string)$row['nome'] . ' ' . (string)$row['cognome']),
            'Password aggiornata',
            'Password aggiornata con successo',
            $body
        );

        sendJson(200, ['success' => true, 'message' => 'Password aggiornata. Ora puoi accedere.']);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('reset-password error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore durante il reset password']);
    }
}

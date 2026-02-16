<?php
/**
 * API AUTENTICAZIONE
 * File: api/auth.php
 * 
 * Endpoints:
 * POST /api/auth.php?action=register - Registrazione nuovo utente
 * POST /api/auth.php?action=login - Login
 * POST /api/auth.php?action=logout - Logout (opzionale, client-side)
 * GET  /api/auth.php?action=me - Dati utente corrente
 * POST /api/auth.php?action=change-password - Cambio password
 */

require_once 'config.php';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegister();
        break;
    case 'login':
        handleLogin();
        break;
    case 'me':
        handleGetMe();
        break;
    case 'change-password':
        handleChangePassword();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Azione non valida']);
}

/**
 * REGISTRAZIONE NUOVO UTENTE
 */
function handleRegister() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validazione
    $required = ['email', 'password', 'nome', 'cognome'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Campo $field obbligatorio"]);
            return;
        }
    }
    
    $email = sanitizeInput($data['email']);
    $password = $data['password'];
    $nome = sanitizeInput($data['nome']);
    $cognome = sanitizeInput($data['cognome']);
    $telefono = sanitizeInput($data['telefono'] ?? '');
    $data_nascita = $data['data_nascita'] ?? null;
    $indirizzo = sanitizeInput($data['indirizzo'] ?? '');
    $citta = sanitizeInput($data['citta'] ?? '');
    $cap = sanitizeInput($data['cap'] ?? '');
    $codice_fiscale = strtoupper(sanitizeInput($data['codice_fiscale'] ?? ''));
    
    // Validazione email
    if (!validateEmail($email)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email non valida']);
        return;
    }
    
    // Validazione codice fiscale
    if ($codice_fiscale && !validateCodiceFiscale($codice_fiscale)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Codice fiscale non valido']);
        return;
    }
    
    // Verifica email esistente
    $stmt = $pdo->prepare("SELECT id FROM profili WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email già registrata']);
        return;
    }
    
    // Verifica codice fiscale esistente
    if ($codice_fiscale) {
        $stmt = $pdo->prepare("SELECT id FROM profili WHERE codice_fiscale = ?");
        $stmt->execute([$codice_fiscale]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Codice fiscale già registrato']);
            return;
        }
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    // Ottieni ID ruolo utente
    $stmt = $pdo->prepare("SELECT id FROM ruoli WHERE nome = 'utente'");
    $stmt->execute();
    $ruolo = $stmt->fetch();
    
    if (!$ruolo) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore configurazione ruoli']);
        return;
    }
    
    try {
        // Inserisci profilo
        $stmt = $pdo->prepare("
            INSERT INTO profili 
            (id, ruolo_id, email, password_hash, nome, cognome, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale, attivo, email_verificata)
            VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE, FALSE)
        ");
        
        $stmt->execute([
            $ruolo['id'],
            $email,
            $passwordHash,
            $nome,
            $cognome,
            $telefono,
            $data_nascita,
            $indirizzo,
            $citta,
            $cap,
            $codice_fiscale
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Recupera profilo completo
        $stmt = $pdo->prepare("
            SELECT p.*, r.nome as ruolo_nome, r.livello as ruolo_livello
            FROM profili p
            JOIN ruoli r ON p.ruolo_id = r.id
            WHERE p.email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Genera JWT
        $token = generateJWT($user['id'], $user['email'], $user['ruolo_nome']);
        
        // Log attività
        logActivity($user['id'], 'registrazione', "Nuovo utente registrato: $email", 'profili', $user['id']);
        
        // Invia email di benvenuto (opzionale)
        $htmlContent = "
            <h1>Benvenuto {$nome}!</h1>
            <p>La tua registrazione è stata completata con successo.</p>
            <p>Ora puoi accedere al sistema e completare il caricamento dei documenti obbligatori.</p>
        ";
        sendEmail($email, "$nome $cognome", 'Benvenuto alla Piscina Naiadi', $htmlContent);
        
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registrazione completata',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'nome' => $user['nome'],
                'cognome' => $user['cognome'],
                'ruolo' => $user['ruolo_nome'],
                'livello' => $user['ruolo_livello']
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore durante la registrazione', 'error' => $e->getMessage()]);
    }
}

/**
 * LOGIN
 */
function handleLogin() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $email = sanitizeInput($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    if (!$email || !$password) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Email e password obbligatori']);
        return;
    }
    
    try {
        // Recupera utente
        $stmt = $pdo->prepare("
            SELECT p.*, r.nome as ruolo_nome, r.livello as ruolo_livello
            FROM profili p
            JOIN ruoli r ON p.ruolo_id = r.id
            WHERE p.email = ? AND p.attivo = TRUE
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Credenziali non valide']);
            return;
        }
        
        // Aggiorna ultimo accesso
        $stmt = $pdo->prepare("UPDATE profili SET ultimo_accesso = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Genera JWT
        $token = generateJWT($user['id'], $user['email'], $user['ruolo_nome']);
        
        // Log attività
        logActivity($user['id'], 'login', "Login effettuato", 'profili', $user['id']);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Login effettuato',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'nome' => $user['nome'],
                'cognome' => $user['cognome'],
                'ruolo' => $user['ruolo_nome'],
                'livello' => $user['ruolo_livello'],
                'email_verificata' => (bool)$user['email_verificata']
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore durante il login', 'error' => $e->getMessage()]);
    }
}

/**
 * GET CURRENT USER
 */
function handleGetMe() {
    global $pdo;
    
    $currentUser = requireAuth();
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, r.nome as ruolo_nome, r.livello as ruolo_livello
            FROM profili p
            JOIN ruoli r ON p.ruolo_id = r.id
            WHERE p.id = ?
        ");
        $stmt->execute([$currentUser['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Utente non trovato']);
            return;
        }
        
        // Rimuovi password hash
        unset($user['password_hash']);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore recupero dati utente', 'error' => $e->getMessage()]);
    }
}

/**
 * CAMBIO PASSWORD
 */
function handleChangePassword() {
    global $pdo;
    
    $currentUser = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $oldPassword = $data['old_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    
    if (!$oldPassword || !$newPassword) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vecchia e nuova password obbligatorie']);
        return;
    }
    
    try {
        // Verifica vecchia password
        $stmt = $pdo->prepare("SELECT password_hash FROM profili WHERE id = ?");
        $stmt->execute([$currentUser['user_id']]);
        $user = $stmt->fetch();
        
        if (!password_verify($oldPassword, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Vecchia password non corretta']);
            return;
        }
        
        // Aggiorna password
        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE profili SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newHash, $currentUser['user_id']]);
        
        // Log attività
        logActivity($currentUser['user_id'], 'cambio_password', 'Password modificata', 'profili', $currentUser['user_id']);
        
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Password aggiornata']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore aggiornamento password', 'error' => $e->getMessage()]);
    }
}

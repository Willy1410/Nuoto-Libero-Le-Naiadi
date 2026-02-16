<?php
declare(strict_types=1);

/**
 * API amministrazione utenti e storico
 */

require_once __DIR__ . '/config.php';

$staff = requireRole(3);
$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = (string)($_GET['action'] ?? 'users');

if ($method === 'GET' && $action === 'users') {
    listUsers();
} elseif ($method === 'GET' && $action === 'user-detail') {
    getUserDetail();
} elseif ($method === 'POST' && $action === 'create-user') {
    createUser($staff);
} elseif ($method === 'PATCH' && $action === 'update-user') {
    updateUser($staff);
} elseif ($method === 'PATCH' && $action === 'toggle-user') {
    toggleUser($staff);
} elseif ($method === 'DELETE' && $action === 'delete-user') {
    deleteUser($staff);
} else {
    sendJson(400, ['success' => false, 'message' => 'Azione non valida']);
}

function listUsers(): void
{
    global $pdo;

    $search = sanitizeText((string)($_GET['q'] ?? ''), 120);
    $role = sanitizeText((string)($_GET['role'] ?? ''), 30);
    $active = sanitizeText((string)($_GET['active'] ?? ''), 5);
    $limit = max(1, min(200, (int)($_GET['limit'] ?? 100)));

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = '(p.nome LIKE ? OR p.cognome LIKE ? OR p.email LIKE ? OR p.telefono LIKE ?)';
        $searchLike = '%' . $search . '%';
        $params[] = $searchLike;
        $params[] = $searchLike;
        $params[] = $searchLike;
        $params[] = $searchLike;
    }

    if ($role !== '') {
        $where[] = 'r.nome = ?';
        $params[] = $role;
    }

    if ($active === '0' || $active === '1') {
        $where[] = 'p.attivo = ?';
        $params[] = (int)$active;
    }

    $sql = 'SELECT p.id, p.email, p.nome, p.cognome, p.telefono, p.attivo, p.ultimo_accesso, p.created_at,
                   r.nome AS ruolo, r.livello AS ruolo_livello,
                   COALESCE((SELECT SUM(a.ingressi_rimanenti) FROM acquisti a WHERE a.user_id = p.id AND a.stato_pagamento = "confirmed"), 0) AS ingressi_totali_rimanenti,
                   COALESCE((SELECT COUNT(*) FROM check_ins c WHERE c.user_id = p.id), 0) AS totale_checkin
            FROM profili p
            JOIN ruoli r ON r.id = p.ruolo_id';

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY p.created_at DESC LIMIT ' . $limit;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        sendJson(200, ['success' => true, 'users' => $stmt->fetchAll()]);
    } catch (Throwable $e) {
        error_log('listUsers error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento utenti']);
    }
}

function getUserDetail(): void
{
    global $pdo;

    $userId = sanitizeText((string)($_GET['id'] ?? ''), 36);
    if ($userId === '') {
        sendJson(400, ['success' => false, 'message' => 'ID utente mancante']);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT p.id, p.email, p.nome, p.cognome, p.telefono, p.data_nascita, p.indirizzo, p.citta, p.cap,
                    p.codice_fiscale, p.attivo, p.email_verificata, p.ultimo_accesso, p.created_at,
                    r.nome AS ruolo, r.livello AS ruolo_livello
             FROM profili p
             JOIN ruoli r ON r.id = p.ruolo_id
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJson(404, ['success' => false, 'message' => 'Utente non trovato']);
        }

        $stmt = $pdo->prepare(
            'SELECT a.id, a.data_acquisto, a.metodo_pagamento, a.stato_pagamento, a.riferimento_pagamento,
                    a.ingressi_rimanenti, a.importo_pagato, a.data_scadenza, a.data_conferma, a.qr_code,
                    p.nome AS pacchetto_nome, p.num_ingressi
             FROM acquisti a
             JOIN pacchetti p ON p.id = a.pacchetto_id
             WHERE a.user_id = ?
             ORDER BY a.data_acquisto DESC'
        );
        $stmt->execute([$userId]);
        $purchases = $stmt->fetchAll();

        $stmt = $pdo->prepare(
            'SELECT c.id, c.timestamp, c.fascia_oraria, c.note,
                    b.nome AS operatore_nome, b.cognome AS operatore_cognome,
                    p.nome AS pacchetto_nome
             FROM check_ins c
             JOIN profili b ON b.id = c.bagnino_id
             JOIN acquisti a ON a.id = c.acquisto_id
             JOIN pacchetti p ON p.id = a.pacchetto_id
             WHERE c.user_id = ?
             ORDER BY c.timestamp DESC
             LIMIT 250'
        );
        $stmt->execute([$userId]);
        $checkins = $stmt->fetchAll();

        $payments = [];
        foreach ($purchases as $purchase) {
            $payments[] = [
                'metodo' => $purchase['metodo_pagamento'],
                'importo' => (float)$purchase['importo_pagato'],
                'data' => $purchase['data_conferma'] ?: $purchase['data_acquisto'],
                'stato' => $purchase['stato_pagamento'],
                'riferimento' => $purchase['riferimento_pagamento'],
                'acquisto_id' => $purchase['id'],
            ];
        }

        sendJson(200, [
            'success' => true,
            'user' => $user,
            'pacchetti' => $purchases,
            'checkins' => $checkins,
            'pagamenti' => $payments,
        ]);
    } catch (Throwable $e) {
        error_log('getUserDetail error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento dettaglio utente']);
    }
}

function createUser(array $staff): void
{
    global $pdo;

    $data = getJsonInput();

    $email = strtolower(sanitizeText((string)($data['email'] ?? ''), 255));
    $password = (string)($data['password'] ?? '');
    $nome = sanitizeText((string)($data['nome'] ?? ''), 100);
    $cognome = sanitizeText((string)($data['cognome'] ?? ''), 100);
    $telefono = sanitizeText((string)($data['telefono'] ?? ''), 30);
    $ruolo = sanitizeText((string)($data['ruolo'] ?? 'utente'), 30);

    if ($email === '' || $password === '' || $nome === '' || $cognome === '') {
        sendJson(400, ['success' => false, 'message' => 'Compila i campi obbligatori']);
    }

    if (!validateEmail($email)) {
        sendJson(400, ['success' => false, 'message' => 'Email non valida']);
    }

    if (!validatePasswordStrength($password)) {
        sendJson(400, ['success' => false, 'message' => 'Password troppo debole (minimo 8 caratteri)']);
    }

    try {
        $stmt = $pdo->prepare('SELECT id FROM profili WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendJson(409, ['success' => false, 'message' => 'Email gia presente']);
        }

        $stmt = $pdo->prepare('SELECT id FROM ruoli WHERE nome = ? LIMIT 1');
        $stmt->execute([$ruolo]);
        $roleRow = $stmt->fetch();
        if (!$roleRow) {
            sendJson(400, ['success' => false, 'message' => 'Ruolo non valido']);
        }

        $userId = generateUuid();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO profili (id, ruolo_id, email, password_hash, nome, cognome, telefono, attivo, email_verificata)
             VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ""), 1, 1)'
        );
        $stmt->execute([$userId, $roleRow['id'], $email, $hash, $nome, $cognome, $telefono]);

        logActivity((string)$staff['user_id'], 'crea_utente', 'Creato utente: ' . $email, 'profili', $userId);

        sendJson(201, ['success' => true, 'message' => 'Utente creato', 'user_id' => $userId]);
    } catch (Throwable $e) {
        error_log('createUser error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore creazione utente']);
    }
}

function updateUser(array $staff): void
{
    global $pdo;

    $userId = sanitizeText((string)($_GET['id'] ?? ''), 36);
    if ($userId === '') {
        sendJson(400, ['success' => false, 'message' => 'ID utente mancante']);
    }

    $data = getJsonInput();

    $fields = [];
    $params = [];

    $allowed = [
        'nome' => 100,
        'cognome' => 100,
        'telefono' => 30,
        'indirizzo' => 255,
        'citta' => 100,
        'cap' => 10,
        'codice_fiscale' => 16,
    ];

    foreach ($allowed as $field => $maxLen) {
        if (array_key_exists($field, $data)) {
            $fields[] = $field . ' = NULLIF(?, "")';
            $value = sanitizeText((string)$data[$field], $maxLen);
            if ($field === 'codice_fiscale') {
                $value = strtoupper($value);
            }
            $params[] = $value;
        }
    }

    if (array_key_exists('email', $data)) {
        $email = strtolower(sanitizeText((string)$data['email'], 255));
        if ($email !== '' && !validateEmail($email)) {
            sendJson(400, ['success' => false, 'message' => 'Email non valida']);
        }
        $fields[] = 'email = NULLIF(?, "")';
        $params[] = $email;
    }

    if (array_key_exists('ruolo', $data)) {
        $roleName = sanitizeText((string)$data['ruolo'], 30);
        $stmt = $pdo->prepare('SELECT id FROM ruoli WHERE nome = ? LIMIT 1');
        $stmt->execute([$roleName]);
        $role = $stmt->fetch();
        if (!$role) {
            sendJson(400, ['success' => false, 'message' => 'Ruolo non valido']);
        }
        $fields[] = 'ruolo_id = ?';
        $params[] = $role['id'];
    }

    if (array_key_exists('password', $data)) {
        $password = (string)$data['password'];
        if ($password !== '') {
            if (!validatePasswordStrength($password)) {
                sendJson(400, ['success' => false, 'message' => 'Password troppo debole (minimo 8 caratteri)']);
            }
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
    }

    if (!$fields) {
        sendJson(400, ['success' => false, 'message' => 'Nessun campo da aggiornare']);
    }

    try {
        $params[] = $userId;
        $sql = 'UPDATE profili SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            sendJson(404, ['success' => false, 'message' => 'Utente non trovato o nessuna modifica']);
        }

        logActivity((string)$staff['user_id'], 'modifica_utente', 'Utente aggiornato', 'profili', $userId);

        sendJson(200, ['success' => true, 'message' => 'Utente aggiornato']);
    } catch (Throwable $e) {
        error_log('updateUser error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore aggiornamento utente']);
    }
}

function toggleUser(array $staff): void
{
    global $pdo;

    $userId = sanitizeText((string)($_GET['id'] ?? ''), 36);
    if ($userId === '') {
        sendJson(400, ['success' => false, 'message' => 'ID utente mancante']);
    }

    try {
        $stmt = $pdo->prepare('UPDATE profili SET attivo = IF(attivo = 1, 0, 1) WHERE id = ?');
        $stmt->execute([$userId]);

        if ($stmt->rowCount() === 0) {
            sendJson(404, ['success' => false, 'message' => 'Utente non trovato']);
        }

        logActivity((string)$staff['user_id'], 'toggle_utente', 'Attivo/disattivo utente', 'profili', $userId);

        sendJson(200, ['success' => true, 'message' => 'Stato utente aggiornato']);
    } catch (Throwable $e) {
        error_log('toggleUser error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore aggiornamento stato utente']);
    }
}

function deleteUser(array $staff): void
{
    global $pdo;

    if ((string)($_GET['confirm'] ?? '') !== 'yes') {
        sendJson(400, ['success' => false, 'message' => 'Conferma richiesta: aggiungi confirm=yes']);
    }

    $userId = sanitizeText((string)($_GET['id'] ?? ''), 36);
    if ($userId === '') {
        sendJson(400, ['success' => false, 'message' => 'ID utente mancante']);
    }

    if ($userId === (string)$staff['user_id']) {
        sendJson(400, ['success' => false, 'message' => 'Non puoi eliminare il tuo account']);
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM profili WHERE id = ?');
        $stmt->execute([$userId]);

        if ($stmt->rowCount() === 0) {
            sendJson(404, ['success' => false, 'message' => 'Utente non trovato']);
        }

        logActivity((string)$staff['user_id'], 'elimina_utente', 'Utente eliminato', 'profili', $userId);

        sendJson(200, ['success' => true, 'message' => 'Utente eliminato']);
    } catch (Throwable $e) {
        error_log('deleteUser error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore eliminazione utente']);
    }
}
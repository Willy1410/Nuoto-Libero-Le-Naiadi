<?php
declare(strict_types=1);

/**
 * API check-in e verifica QR
 */

require_once __DIR__ . '/config.php';

$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = (string)($_GET['action'] ?? '');

if ($method === 'POST' && $action === '') {
    registraCheckIn();
} elseif ($method === 'GET' && isset($_GET['qr'])) {
    verificaQR();
} elseif ($method === 'GET' && $action === 'history') {
    getHistory();
} elseif ($method === 'GET' && $action === 'today') {
    getTodayCheckIns();
} else {
    sendJson(404, ['success' => false, 'message' => 'Endpoint non trovato']);
}

function verificaQR(): void
{
    global $pdo;

    $qrCode = sanitizeText((string)($_GET['qr'] ?? ''), 120);
    if ($qrCode === '') {
        sendJson(400, ['success' => false, 'message' => 'Codice QR mancante']);
    }

    $authUser = getCurrentUser();
    $canCheckIn = false;
    $role = 'guest';

    if ($authUser && !empty($authUser['user_id'])) {
        $stmt = $pdo->prepare(
            'SELECT r.nome, r.livello
             FROM profili p
             JOIN ruoli r ON r.id = p.ruolo_id
             WHERE p.id = ? AND p.attivo = 1
             LIMIT 1'
        );
        $stmt->execute([$authUser['user_id']]);
        $profile = $stmt->fetch();

        if ($profile) {
            $role = (string)$profile['nome'];
            $canCheckIn = (int)$profile['livello'] >= 2;
        }
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT a.id, a.user_id, a.qr_code, a.ingressi_rimanenti, a.data_scadenza, a.stato_pagamento,
                    p.nome AS pacchetto_nome,
                    u.nome AS user_nome, u.cognome AS user_cognome, u.telefono AS user_telefono
             FROM acquisti a
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili u ON u.id = a.user_id
             WHERE a.qr_code = ?
             LIMIT 1'
        );
        $stmt->execute([$qrCode]);
        $purchase = $stmt->fetch();

        if (!$purchase) {
            sendJson(404, [
                'success' => false,
                'valid' => false,
                'message' => 'QR code non trovato',
                'can_checkin' => false,
                'role' => $role,
            ]);
        }

        if ($purchase['stato_pagamento'] !== 'confirmed') {
            sendJson(200, [
                'success' => true,
                'valid' => false,
                'message' => 'Pacchetto non ancora confermato',
                'can_checkin' => false,
                'role' => $role,
                'acquisto' => [
                    'id' => $purchase['id'],
                    'pacchetto_nome' => $purchase['pacchetto_nome'],
                    'ingressi_rimanenti' => (int)$purchase['ingressi_rimanenti'],
                    'data_scadenza' => $purchase['data_scadenza'],
                    'stato_pagamento' => $purchase['stato_pagamento'],
                ],
                'utente' => [
                    'nome' => $purchase['user_nome'],
                    'cognome' => $purchase['user_cognome'],
                    'telefono' => $purchase['user_telefono'],
                ],
            ]);
        }

        $today = date('Y-m-d');
        $isExpired = !empty($purchase['data_scadenza']) && $purchase['data_scadenza'] < $today;
        $hasEntries = (int)$purchase['ingressi_rimanenti'] > 0;

        $fascia = getFasciaOraria(date('H:i:s'));
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM check_ins
             WHERE acquisto_id = ? AND DATE(timestamp) = CURDATE() AND fascia_oraria = ?'
        );
        $stmt->execute([$purchase['id'], $fascia]);
        $alreadyCheckedInFascia = ((int)$stmt->fetch()['total']) > 0;

        $valid = !$isExpired && $hasEntries;
        $message = 'QR valido';
        if ($isExpired) {
            $message = 'Pacchetto scaduto';
        } elseif (!$hasEntries) {
            $message = 'Ingressi esauriti';
        } elseif ($alreadyCheckedInFascia) {
            $message = 'Check-in gia effettuato in questa fascia oraria';
            $valid = false;
        }

        sendJson(200, [
            'success' => true,
            'valid' => $valid,
            'message' => $message,
            'role' => $role,
            'can_checkin' => $canCheckIn && $valid,
            'read_only' => !$canCheckIn,
            'acquisto' => [
                'id' => $purchase['id'],
                'pacchetto_nome' => $purchase['pacchetto_nome'],
                'ingressi_rimanenti' => (int)$purchase['ingressi_rimanenti'],
                'data_scadenza' => $purchase['data_scadenza'],
                'stato_pagamento' => $purchase['stato_pagamento'],
            ],
            'utente' => [
                'nome' => $purchase['user_nome'],
                'cognome' => $purchase['user_cognome'],
                'telefono' => $purchase['user_telefono'],
            ],
        ]);
    } catch (Throwable $e) {
        error_log('verificaQR error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore verifica QR']);
    }
}

function registraCheckIn(): void
{
    global $pdo;

    $operator = requireRole(2);
    $data = getJsonInput();

    $qrCode = sanitizeText((string)($data['qr_code'] ?? ''), 120);
    $note = sanitizeText((string)($data['note'] ?? ''), 500);

    if ($qrCode === '') {
        sendJson(400, ['success' => false, 'message' => 'Codice QR mancante']);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT a.id, a.user_id, a.ingressi_rimanenti, a.data_scadenza, a.stato_pagamento,
                    u.nome, u.cognome, u.email,
                    p.nome AS pacchetto_nome
             FROM acquisti a
             JOIN profili u ON u.id = a.user_id
             JOIN pacchetti p ON p.id = a.pacchetto_id
             WHERE a.qr_code = ?
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute([$qrCode]);
        $purchase = $stmt->fetch();

        if (!$purchase) {
            $pdo->rollBack();
            sendJson(404, ['success' => false, 'message' => 'QR code non valido']);
        }

        if ($purchase['stato_pagamento'] !== 'confirmed') {
            $pdo->rollBack();
            sendJson(400, ['success' => false, 'message' => 'Pacchetto non confermato']);
        }

        if (!empty($purchase['data_scadenza']) && $purchase['data_scadenza'] < date('Y-m-d')) {
            $pdo->rollBack();
            sendJson(400, ['success' => false, 'message' => 'Pacchetto scaduto']);
        }

        if ((int)$purchase['ingressi_rimanenti'] <= 0) {
            $pdo->rollBack();
            sendJson(400, ['success' => false, 'message' => 'Ingressi esauriti']);
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM check_ins
             WHERE acquisto_id = ?
               AND timestamp >= DATE_SUB(NOW(), INTERVAL 20 SECOND)'
        );
        $stmt->execute([$purchase['id']]);
        if ((int)$stmt->fetch()['total'] > 0) {
            $pdo->rollBack();
            sendJson(409, ['success' => false, 'message' => 'Doppio scan rilevato. Attendere qualche secondo.']);
        }

        $fascia = getFasciaOraria(date('H:i:s'));
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM check_ins
             WHERE acquisto_id = ? AND DATE(timestamp) = CURDATE() AND fascia_oraria = ?'
        );
        $stmt->execute([$purchase['id'], $fascia]);
        if ((int)$stmt->fetch()['total'] > 0) {
            $pdo->rollBack();
            sendJson(400, ['success' => false, 'message' => 'Check-in gia effettuato in questa fascia oraria']);
        }

        $checkInId = generateUuid();
        $stmt = $pdo->prepare(
            'INSERT INTO check_ins (id, acquisto_id, user_id, bagnino_id, fascia_oraria, note)
             VALUES (?, ?, ?, ?, ?, NULLIF(?, ""))'
        );
        $stmt->execute([
            $checkInId,
            $purchase['id'],
            $purchase['user_id'],
            $operator['user_id'],
            $fascia,
            $note,
        ]);

        $newEntries = (int)$purchase['ingressi_rimanenti'] - 1;
        $stmt = $pdo->prepare('UPDATE acquisti SET ingressi_rimanenti = ? WHERE id = ?');
        $stmt->execute([$newEntries, $purchase['id']]);

        $pdo->commit();

        logActivity((string)$operator['user_id'], 'check_in', 'Check-in registrato per ' . $purchase['nome'] . ' ' . $purchase['cognome'], 'check_ins', $checkInId);

        maybeSendOneEntryReminder((string)$purchase['id'], $newEntries);
        maybeSendExpiryReminder((string)$purchase['id']);

        sendJson(201, [
            'success' => true,
            'message' => 'Check-in registrato con successo',
            'ingressi_rimanenti' => $newEntries,
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('registraCheckIn error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore registrazione check-in']);
    }
}

function getHistory(): void
{
    global $pdo;

    $currentUser = requireAuth();

    $targetUserId = (string)$currentUser['user_id'];
    $requestedUserId = sanitizeText((string)($_GET['user_id'] ?? ''), 36);

    if ($requestedUserId !== '') {
        try {
            $staff = requireRole(3);
            $targetUserId = $requestedUserId;
        } catch (Throwable $e) {
            // fallback to own history
            $targetUserId = (string)$currentUser['user_id'];
        }
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT c.id, c.timestamp, c.fascia_oraria, c.note,
                    a.qr_code,
                    p.nome AS pacchetto_nome,
                    b.nome AS bagnino_nome,
                    b.cognome AS bagnino_cognome
             FROM check_ins c
             JOIN acquisti a ON a.id = c.acquisto_id
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili b ON b.id = c.bagnino_id
             WHERE c.user_id = ?
             ORDER BY c.timestamp DESC
             LIMIT 250'
        );
        $stmt->execute([$targetUserId]);

        sendJson(200, ['success' => true, 'checkins' => $stmt->fetchAll()]);
    } catch (Throwable $e) {
        error_log('getHistory error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore recupero storico check-in']);
    }
}

function getTodayCheckIns(): void
{
    global $pdo;

    requireRole(2);

    try {
        $stmt = $pdo->query(
            'SELECT c.id, c.timestamp, c.fascia_oraria,
                    u.nome AS user_nome, u.cognome AS user_cognome, u.telefono AS user_telefono,
                    p.nome AS pacchetto_nome
             FROM check_ins c
             JOIN profili u ON u.id = c.user_id
             JOIN acquisti a ON a.id = c.acquisto_id
             JOIN pacchetti p ON p.id = a.pacchetto_id
             WHERE DATE(c.timestamp) = CURDATE()
             ORDER BY c.timestamp DESC'
        );

        $rows = $stmt->fetchAll();

        $mattina = 0;
        $pomeriggio = 0;
        foreach ($rows as $row) {
            if ($row['fascia_oraria'] === 'mattina') {
                $mattina++;
            } else {
                $pomeriggio++;
            }
        }

        sendJson(200, [
            'success' => true,
            'checkins' => $rows,
            'stats' => [
                'totale' => count($rows),
                'mattina' => $mattina,
                'pomeriggio' => $pomeriggio,
            ],
        ]);
    } catch (Throwable $e) {
        error_log('getTodayCheckIns error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento check-in di oggi']);
    }
}

function maybeSendOneEntryReminder(string $acquistoId, int $newEntries): void
{
    global $pdo;

    if ($newEntries !== 1 || wasNotificationSent($acquistoId, 'one_entry')) {
        return;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT a.id, a.data_scadenza, p.nome AS pacchetto_nome,
                    u.nome, u.cognome, u.email
             FROM acquisti a
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili u ON u.id = a.user_id
             WHERE a.id = ?
             LIMIT 1'
        );
        $stmt->execute([$acquistoId]);
        $row = $stmt->fetch();

        if (!$row) {
            return;
        }

        $body = '<p>Ciao <strong>' . htmlspecialchars((string)$row['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>ti segnaliamo che sul pacchetto <strong>' . htmlspecialchars((string)$row['pacchetto_nome'], ENT_QUOTES, 'UTF-8') . '</strong> e rimasto solo <strong>1 ingresso</strong>.</p>'
            . '<p>Scadenza pacchetto: <strong>' . htmlspecialchars((string)$row['data_scadenza'], ENT_QUOTES, 'UTF-8') . '</strong></p>';

        if (sendTemplateEmail(
            (string)$row['email'],
            trim((string)$row['nome'] . ' ' . (string)$row['cognome']),
            'Avviso ingressi residui',
            'Ti resta 1 ingresso',
            $body
        )) {
            markNotificationSent($acquistoId, 'one_entry');
        }
    } catch (Throwable $e) {
        error_log('maybeSendOneEntryReminder error: ' . $e->getMessage());
    }
}

function maybeSendExpiryReminder(string $acquistoId): void
{
    global $pdo;

    if (wasNotificationSent($acquistoId, 'expiry_7days')) {
        return;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT a.id, a.data_scadenza, DATEDIFF(a.data_scadenza, CURDATE()) AS giorni_alla_scadenza,
                    p.nome AS pacchetto_nome,
                    u.nome, u.cognome, u.email
             FROM acquisti a
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili u ON u.id = a.user_id
             WHERE a.id = ? AND a.stato_pagamento = "confirmed" AND a.data_scadenza IS NOT NULL
             LIMIT 1'
        );
        $stmt->execute([$acquistoId]);
        $row = $stmt->fetch();

        if (!$row) {
            return;
        }

        $days = (int)$row['giorni_alla_scadenza'];
        if ($days < 0 || $days > 7) {
            return;
        }

        $body = '<p>Ciao <strong>' . htmlspecialchars((string)$row['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>il tuo pacchetto <strong>' . htmlspecialchars((string)$row['pacchetto_nome'], ENT_QUOTES, 'UTF-8') . '</strong> scadra tra circa <strong>' . $days . ' giorni</strong>.</p>'
            . '<p>Data scadenza: <strong>' . htmlspecialchars((string)$row['data_scadenza'], ENT_QUOTES, 'UTF-8') . '</strong></p>';

        if (sendTemplateEmail(
            (string)$row['email'],
            trim((string)$row['nome'] . ' ' . (string)$row['cognome']),
            'Promemoria scadenza pacchetto',
            'Pacchetto in scadenza',
            $body
        )) {
            markNotificationSent($acquistoId, 'expiry_7days');
        }
    } catch (Throwable $e) {
        error_log('maybeSendExpiryReminder error: ' . $e->getMessage());
    }
}
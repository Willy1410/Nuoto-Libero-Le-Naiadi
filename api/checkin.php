<?php

/**
 * API check-in
 * POST /api/checkin.php
 * GET  /api/checkin.php?qr=...
 * GET  /api/checkin.php?action=history
 * GET  /api/checkin.php?action=today
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
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint non trovato']);
}

function requireCheckinOperator(): array
{
    $user = requireRole(2);
    $role = strtolower((string)($user['role'] ?? ''));
    if (!in_array($role, ['bagnino', 'admin'], true)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Operazione consentita solo a bagnino o admin',
        ]);
        exit();
    }

    return $user;
}

function parseIncomingQrToken(string $raw): string
{
    return extractQrTokenFromInput($raw);
}

function findUserByQrToken(string $qrToken, bool $forUpdate = false): ?array
{
    global $pdo;

    $sql = 'SELECT id, nome, cognome, telefono, qr_token
            FROM profili
            WHERE qr_token = ? AND attivo = 1
            LIMIT 1';
    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$qrToken]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function selectCheckinPurchaseForUser(string $userId, bool $forUpdate = false): ?array
{
    global $pdo;

    $sql = 'SELECT a.id, a.user_id, a.ingressi_rimanenti, a.data_scadenza,
                   p.nome AS pacchetto_nome
            FROM acquisti a
            JOIN pacchetti p ON p.id = a.pacchetto_id
            WHERE a.user_id = ?
              AND a.stato_pagamento = "confirmed"
              AND a.ingressi_rimanenti > 0
              AND (a.data_scadenza IS NULL OR a.data_scadenza >= CURDATE())
            ORDER BY
              CASE WHEN a.data_scadenza IS NULL THEN 1 ELSE 0 END ASC,
              a.data_scadenza ASC,
              COALESCE(a.data_conferma, a.data_acquisto) ASC
            LIMIT 1';
    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function getTotalRemainingEntriesForUser(string $userId): int
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(a.ingressi_rimanenti), 0) AS total
         FROM acquisti a
         WHERE a.user_id = ?
           AND a.stato_pagamento = "confirmed"
           AND (a.data_scadenza IS NULL OR a.data_scadenza >= CURDATE())'
    );
    $stmt->execute([$userId]);

    return (int)($stmt->fetch()['total'] ?? 0);
}

function hasCheckinInCurrentSlot(string $userId, string $fasciaOraria): bool
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS count
         FROM check_ins
         WHERE user_id = ?
           AND DATE(timestamp) = CURDATE()
           AND fascia_oraria = ?'
    );
    $stmt->execute([$userId, $fasciaOraria]);

    return (int)($stmt->fetch()['count'] ?? 0) > 0;
}

function verificaQR(): void
{
    requireCheckinOperator();
    $qrCodeRaw = sanitizeInput($_GET['qr'] ?? '');
    $qrToken = parseIncomingQrToken((string)$qrCodeRaw);

    if ($qrToken === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Codice QR non valido']);
        return;
    }

    try {
        $utente = findUserByQrToken($qrToken, false);
        if (!$utente) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'QR non valido',
                'can_checkin' => false,
            ]);
            return;
        }

        $fasciaCorrente = getCurrentIngressFasciaOraria();
        if ($fasciaCorrente === null) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Ingresso consentito solo in fascia attiva: ' . getIngressScheduleSummary(),
                'can_checkin' => false,
            ]);
            return;
        }

        $totalRemaining = getTotalRemainingEntriesForUser((string)$utente['id']);
        if ($totalRemaining <= 0) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Ingressi esauriti',
                'ingressi_rimanenti' => 0,
                'can_checkin' => false,
            ]);
            return;
        }

        if (hasCheckinInCurrentSlot((string)$utente['id'], $fasciaCorrente)) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Check-in gia effettuato in questa fascia oraria',
                'fascia_oraria' => $fasciaCorrente,
                'can_checkin' => false,
            ]);
            return;
        }

        $acquisto = selectCheckinPurchaseForUser((string)$utente['id'], false);
        if (!$acquisto) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Nessun pacchetto confermato disponibile',
                'can_checkin' => false,
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'valid' => true,
            'message' => 'QR valido',
            'can_checkin' => true,
            'acquisto' => [
                'id' => $acquisto['id'],
                'pacchetto_nome' => $acquisto['pacchetto_nome'],
                'ingressi_rimanenti' => $totalRemaining,
                'data_scadenza' => $acquisto['data_scadenza'],
                'qr_code' => $qrToken,
                'qr_url' => buildUserQrUrl($qrToken),
            ],
            'utente' => [
                'nome' => $utente['nome'],
                'cognome' => $utente['cognome'],
                'telefono' => $utente['telefono'],
            ],
        ]);
    } catch (Throwable $e) {
        error_log('verificaQR error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore verifica QR']);
    }
}

function registraCheckIn(): void
{
    global $pdo;

    $currentUser = requireCheckinOperator();
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }

    $qrCodeRaw = sanitizeInput($data['qr_code'] ?? '');
    $qrToken = parseIncomingQrToken((string)$qrCodeRaw);
    $note = sanitizeInput($data['note'] ?? '');

    if ($qrToken === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Codice QR non valido']);
        return;
    }

    $fasciaCorrente = getCurrentIngressFasciaOraria();
    if ($fasciaCorrente === null) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Check-in non consentito fuori orario. Fasce attive: ' . getIngressScheduleSummary(),
        ]);
        return;
    }

    try {
        $pdo->beginTransaction();

        $utente = findUserByQrToken($qrToken, true);
        if (!$utente) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'QR non valido']);
            return;
        }

        $userId = (string)$utente['id'];

        if (hasCheckinInCurrentSlot($userId, $fasciaCorrente)) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Check-in gia registrato in questa fascia oraria']);
            return;
        }

        $acquisto = selectCheckinPurchaseForUser($userId, true);
        if (!$acquisto) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ingressi esauriti o pacchetto non attivo']);
            return;
        }

        $checkinId = generateUuid();
        $stmt = $pdo->prepare(
            'INSERT INTO check_ins (id, acquisto_id, user_id, bagnino_id, fascia_oraria, note)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $checkinId,
            $acquisto['id'],
            $userId,
            $currentUser['user_id'],
            $fasciaCorrente,
            $note,
        ]);

        $stmt = $pdo->prepare(
            'UPDATE acquisti
             SET ingressi_rimanenti = ingressi_rimanenti - 1
             WHERE id = ? AND ingressi_rimanenti > 0'
        );
        $stmt->execute([$acquisto['id']]);

        if ($stmt->rowCount() < 1) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Check-in non completato, riprovare']);
            return;
        }

        $remaining = getTotalRemainingEntriesForUser($userId);

        logActivity(
            (string)$currentUser['user_id'],
            'check_in',
            'Check-in registrato [esito=success] per ' . (string)$utente['nome'] . ' ' . (string)$utente['cognome'],
            'check_ins',
            $checkinId
        );

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Check-in registrato con successo',
            'ingressi_rimanenti' => $remaining,
            'qr_code' => $qrToken,
            'qr_url' => buildUserQrUrl($qrToken),
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('registraCheckIn error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore registrazione check-in']);
    }
}

function getHistory(): void
{
    global $pdo;

    $currentUser = requireAuth();

    $stmt = $pdo->prepare(
        'SELECT c.*, COALESCE(NULLIF(a.qr_code, ""), up.qr_token) AS qr_code, p.nome AS pacchetto_nome,
                prof.nome AS bagnino_nome, prof.cognome AS bagnino_cognome
         FROM check_ins c
         JOIN acquisti a ON c.acquisto_id = a.id
         JOIN profili up ON up.id = c.user_id
         JOIN pacchetti p ON a.pacchetto_id = p.id
         JOIN profili prof ON c.bagnino_id = prof.id
         WHERE c.user_id = ?
         ORDER BY c.timestamp DESC
         LIMIT 100'
    );
    $stmt->execute([$currentUser['user_id']]);

    echo json_encode(['success' => true, 'checkins' => $stmt->fetchAll()]);
}

function getTodayCheckIns(): void
{
    global $pdo;

    requireCheckinOperator();

    $stmt = $pdo->query(
        'SELECT c.timestamp, c.fascia_oraria, c.note,
                prof.nome AS user_nome,
                prof.cognome AS user_cognome,
                prof.telefono AS user_telefono,
                p.nome AS pacchetto_nome
         FROM check_ins c
         JOIN profili prof ON c.user_id = prof.id
         JOIN acquisti a ON c.acquisto_id = a.id
         JOIN pacchetti p ON a.pacchetto_id = p.id
         WHERE DATE(c.timestamp) = CURDATE()
         ORDER BY c.timestamp DESC'
    );
    $checkins = $stmt->fetchAll();

    $mattina = 0;
    $pomeriggio = 0;
    foreach ($checkins as $checkin) {
        if ((string)$checkin['fascia_oraria'] === 'mattina') {
            $mattina++;
        } else {
            $pomeriggio++;
        }
    }

    echo json_encode([
        'success' => true,
        'checkins' => $checkins,
        'stats' => [
            'totale' => count($checkins),
            'mattina' => $mattina,
            'pomeriggio' => $pomeriggio,
        ],
    ]);
}

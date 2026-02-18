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

function verificaQR(): void
{
    global $pdo;

    requireRole(2);
    $qrCode = sanitizeInput($_GET['qr'] ?? '');

    if ($qrCode === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Codice QR mancante']);
        return;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT a.id, a.user_id, a.qr_code, a.stato_pagamento, a.ingressi_rimanenti, a.data_scadenza,
                    p.nome AS pacchetto_nome,
                    prof.nome AS user_nome,
                    prof.cognome AS user_cognome,
                    prof.telefono AS user_telefono
             FROM acquisti a
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili prof ON prof.id = a.user_id
             WHERE a.qr_code = ? AND a.stato_pagamento = \'confirmed\'
             LIMIT 1'
        );
        $stmt->execute([$qrCode]);
        $acquisto = $stmt->fetch();

        if (!$acquisto) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'QR non valido o acquisto non confermato',
            ]);
            return;
        }

        if (!empty($acquisto['data_scadenza']) && (string)$acquisto['data_scadenza'] < date('Y-m-d')) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Pacchetto scaduto',
                'data_scadenza' => $acquisto['data_scadenza'],
            ]);
            return;
        }

        if ((int)$acquisto['ingressi_rimanenti'] <= 0) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Ingressi esauriti',
                'ingressi_rimanenti' => 0,
            ]);
            return;
        }

        $fasciaCorrente = getCurrentIngressFasciaOraria();
        if ($fasciaCorrente === null) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Ingresso consentito solo in fascia attiva: ' . getIngressScheduleSummary(),
            ]);
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS count
             FROM check_ins
             WHERE acquisto_id = ?
               AND DATE(timestamp) = CURDATE()
               AND fascia_oraria = ?'
        );
        $stmt->execute([$acquisto['id'], $fasciaCorrente]);
        $alreadyChecked = (int)($stmt->fetch()['count'] ?? 0);

        if ($alreadyChecked > 0) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'Check-in gia effettuato in questa fascia oraria',
                'fascia_oraria' => $fasciaCorrente,
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'valid' => true,
            'message' => 'QR valido',
            'acquisto' => [
                'id' => $acquisto['id'],
                'pacchetto_nome' => $acquisto['pacchetto_nome'],
                'ingressi_rimanenti' => (int)$acquisto['ingressi_rimanenti'],
                'data_scadenza' => $acquisto['data_scadenza'],
                'qr_code' => $acquisto['qr_code'],
            ],
            'utente' => [
                'nome' => $acquisto['user_nome'],
                'cognome' => $acquisto['user_cognome'],
                'telefono' => $acquisto['user_telefono'],
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

    $currentUser = requireRole(2);
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }

    $qrCode = sanitizeInput($data['qr_code'] ?? '');
    $note = sanitizeInput($data['note'] ?? '');

    if ($qrCode === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Codice QR mancante']);
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

        $stmt = $pdo->prepare(
            'SELECT a.id, a.user_id, a.ingressi_rimanenti, a.data_scadenza,
                    prof.nome, prof.cognome
             FROM acquisti a
             JOIN profili prof ON prof.id = a.user_id
             WHERE a.qr_code = ? AND a.stato_pagamento = \'confirmed\'
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute([$qrCode]);
        $acquisto = $stmt->fetch();

        if (!$acquisto) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'QR non valido']);
            return;
        }

        if (!empty($acquisto['data_scadenza']) && (string)$acquisto['data_scadenza'] < date('Y-m-d')) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Pacchetto scaduto']);
            return;
        }

        if ((int)$acquisto['ingressi_rimanenti'] <= 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ingressi esauriti']);
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) AS count
             FROM check_ins
             WHERE acquisto_id = ?
               AND DATE(timestamp) = CURDATE()
               AND fascia_oraria = ?'
        );
        $stmt->execute([$acquisto['id'], $fasciaCorrente]);
        $alreadyChecked = (int)($stmt->fetch()['count'] ?? 0);

        if ($alreadyChecked > 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Check-in gia registrato in questa fascia oraria']);
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO check_ins (id, acquisto_id, user_id, bagnino_id, fascia_oraria, note)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            generateUuid(),
            $acquisto['id'],
            $acquisto['user_id'],
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

        $stmt = $pdo->prepare('SELECT ingressi_rimanenti FROM acquisti WHERE id = ? LIMIT 1');
        $stmt->execute([$acquisto['id']]);
        $remaining = (int)($stmt->fetch()['ingressi_rimanenti'] ?? 0);

        logActivity(
            (string)$currentUser['user_id'],
            'check_in',
            'Check-in registrato per ' . (string)$acquisto['nome'] . ' ' . (string)$acquisto['cognome'],
            'check_ins',
            (string)$acquisto['id']
        );

        $pdo->commit();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Check-in registrato con successo',
            'ingressi_rimanenti' => $remaining,
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
        'SELECT c.*, a.qr_code, p.nome AS pacchetto_nome,
                prof.nome AS bagnino_nome, prof.cognome AS bagnino_cognome
         FROM check_ins c
         JOIN acquisti a ON c.acquisto_id = a.id
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

    requireRole(2);

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



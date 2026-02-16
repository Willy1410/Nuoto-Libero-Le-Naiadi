<?php
declare(strict_types=1);

/**
 * API pacchetti e acquisti
 */

require_once __DIR__ . '/config.php';

$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = (string)($_GET['action'] ?? '');

if ($method === 'GET' && $action === '') {
    getPacchetti();
} elseif ($method === 'POST' && $action === '') {
    acquistaPacchetto();
} elseif ($method === 'GET' && $action === 'my-purchases') {
    getMyPurchases();
} elseif ($method === 'GET' && $action === 'pending') {
    getPendingPurchases();
} elseif ($method === 'GET' && $action === 'user-purchases') {
    getUserPurchases();
} elseif ($method === 'PATCH' && $action === 'confirm') {
    confirmPayment();
} elseif ($method === 'PATCH' && $action === 'cancel') {
    cancelPurchase();
} elseif ($method === 'POST' && $action === 'renew') {
    renewPackage();
} else {
    sendJson(404, ['success' => false, 'message' => 'Endpoint non trovato']);
}

function getPacchetti(): void
{
    global $pdo;

    try {
        $stmt = $pdo->query('SELECT * FROM pacchetti WHERE attivo = 1 ORDER BY ordine ASC, prezzo ASC');
        $packages = $stmt->fetchAll();

        sendJson(200, ['success' => true, 'pacchetti' => $packages]);
    } catch (Throwable $e) {
        error_log('getPacchetti error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento pacchetti']);
    }
}

function getMyPurchases(): void
{
    global $pdo;

    $currentUser = requireAuth();

    try {
        $stmt = $pdo->prepare(
            'SELECT a.*, p.nome AS pacchetto_nome, p.descrizione AS pacchetto_descrizione,
                    p.validita_giorni, p.num_ingressi
             FROM acquisti a
             JOIN pacchetti p ON a.pacchetto_id = p.id
             WHERE a.user_id = ?
             ORDER BY a.data_acquisto DESC'
        );
        $stmt->execute([$currentUser['user_id']]);
        $purchases = $stmt->fetchAll();

        sendJson(200, ['success' => true, 'acquisti' => $purchases]);
    } catch (Throwable $e) {
        error_log('getMyPurchases error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento acquisti']);
    }
}

function getPendingPurchases(): void
{
    global $pdo;

    requireRole(3);

    try {
        $stmt = $pdo->query(
            'SELECT a.id, a.user_id, a.pacchetto_id, a.data_acquisto, a.metodo_pagamento, a.riferimento_pagamento,
                    a.note_pagamento, a.importo_pagato, a.ingressi_rimanenti,
                    p.nome AS pacchetto_nome, p.validita_giorni,
                    u.nome AS user_nome, u.cognome AS user_cognome, u.email AS user_email
             FROM acquisti a
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili u ON u.id = a.user_id
             WHERE a.stato_pagamento = "pending"
             ORDER BY a.data_acquisto ASC'
        );

        sendJson(200, ['success' => true, 'acquisti' => $stmt->fetchAll()]);
    } catch (Throwable $e) {
        error_log('getPendingPurchases error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento acquisti pending']);
    }
}

function getUserPurchases(): void
{
    global $pdo;

    requireRole(3);
    $userId = sanitizeText((string)($_GET['user_id'] ?? ''), 36);

    if ($userId === '') {
        sendJson(400, ['success' => false, 'message' => 'user_id mancante']);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT a.*, p.nome AS pacchetto_nome, p.num_ingressi, p.validita_giorni
             FROM acquisti a
             JOIN pacchetti p ON p.id = a.pacchetto_id
             WHERE a.user_id = ?
             ORDER BY a.data_acquisto DESC'
        );
        $stmt->execute([$userId]);

        sendJson(200, ['success' => true, 'acquisti' => $stmt->fetchAll()]);
    } catch (Throwable $e) {
        error_log('getUserPurchases error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento storico acquisti']);
    }
}

function acquistaPacchetto(): void
{
    global $pdo;

    $currentUser = requireAuth();
    $data = getJsonInput();

    $pacchettoId = (int)($data['pacchetto_id'] ?? 0);
    $metodoPagamento = sanitizeText((string)($data['metodo_pagamento'] ?? 'bonifico'), 20);
    $riferimentoPagamento = sanitizeText((string)($data['riferimento_pagamento'] ?? ''), 255);
    $notePagamento = sanitizeText((string)($data['note_pagamento'] ?? ''), 1000);

    if ($pacchettoId <= 0) {
        sendJson(400, ['success' => false, 'message' => 'Pacchetto non valido']);
    }

    $allowedMethods = ['bonifico', 'contanti', 'carta', 'paypal', 'stripe'];
    if (!in_array($metodoPagamento, $allowedMethods, true)) {
        $metodoPagamento = 'bonifico';
    }

    try {
        $stmt = $pdo->prepare('SELECT * FROM pacchetti WHERE id = ? AND attivo = 1 LIMIT 1');
        $stmt->execute([$pacchettoId]);
        $package = $stmt->fetch();

        if (!$package) {
            sendJson(404, ['success' => false, 'message' => 'Pacchetto non trovato']);
        }

        $purchaseId = generateUuid();
        $qrCode = generateQRCode($purchaseId);
        $scadenza = date('Y-m-d', strtotime('+' . (int)$package['validita_giorni'] . ' days'));

        $stmt = $pdo->prepare(
            'INSERT INTO acquisti
            (id, user_id, pacchetto_id, metodo_pagamento, stato_pagamento, riferimento_pagamento, note_pagamento,
             qr_code, ingressi_rimanenti, data_scadenza, importo_pagato)
            VALUES (?, ?, ?, ?, "pending", NULLIF(?, ""), NULLIF(?, ""), ?, ?, ?, ?)'
        );
        $stmt->execute([
            $purchaseId,
            $currentUser['user_id'],
            $pacchettoId,
            $metodoPagamento,
            $riferimentoPagamento,
            $notePagamento,
            $qrCode,
            (int)$package['num_ingressi'],
            $scadenza,
            (float)$package['prezzo'],
        ]);

        logActivity((string)$currentUser['user_id'], 'acquisto_pacchetto', 'Nuovo acquisto: ' . $package['nome'], 'acquisti', $purchaseId);

        $stmt = $pdo->prepare('SELECT nome, cognome, email FROM profili WHERE id = ? LIMIT 1');
        $stmt->execute([$currentUser['user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            $body = '<p>Ciao <strong>' . htmlspecialchars((string)$user['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
                . '<p>abbiamo registrato il tuo acquisto: <strong>' . htmlspecialchars((string)$package['nome'], ENT_QUOTES, 'UTF-8') . '</strong>.</p>'
                . '<p>Importo: <strong>EUR ' . number_format((float)$package['prezzo'], 2, ',', '.') . '</strong></p>'
                . '<p>Codice univoco: <strong>' . htmlspecialchars($qrCode, ENT_QUOTES, 'UTF-8') . '</strong></p>'
                . '<p>Una volta confermato il pagamento, il pacchetto sara attivo.</p>';

            sendTemplateEmail(
                (string)$user['email'],
                trim((string)$user['nome'] . ' ' . (string)$user['cognome']),
                'Acquisto registrato - Nuoto Libero',
                'Acquisto registrato',
                $body
            );
        }

        sendJson(201, [
            'success' => true,
            'message' => 'Acquisto registrato con successo. In attesa di conferma pagamento.',
            'acquisto' => [
                'id' => $purchaseId,
                'qr_code' => $qrCode,
                'stato_pagamento' => 'pending',
                'data_scadenza' => $scadenza,
                'ingressi_rimanenti' => (int)$package['num_ingressi'],
                'importo_pagato' => (float)$package['prezzo'],
            ],
        ]);
    } catch (Throwable $e) {
        error_log('acquistaPacchetto error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore durante la creazione acquisto']);
    }
}

function confirmPayment(): void
{
    global $pdo;

    $staff = requireRole(3);
    $acquistoId = sanitizeText((string)($_GET['id'] ?? ''), 36);

    if ($acquistoId === '') {
        sendJson(400, ['success' => false, 'message' => 'ID acquisto mancante']);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT a.*, p.validita_giorni, p.nome AS pacchetto_nome,
                    u.email AS user_email, u.nome AS user_nome, u.cognome AS user_cognome
             FROM acquisti a
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili u ON u.id = a.user_id
             WHERE a.id = ?
             LIMIT 1'
        );
        $stmt->execute([$acquistoId]);
        $purchase = $stmt->fetch();

        if (!$purchase) {
            sendJson(404, ['success' => false, 'message' => 'Acquisto non trovato']);
        }

        if ($purchase['stato_pagamento'] === 'confirmed') {
            sendJson(400, ['success' => false, 'message' => 'Acquisto gia confermato']);
        }

        $qrCode = $purchase['qr_code'] ?: generateQRCode($acquistoId);
        $scadenza = $purchase['data_scadenza'] ?: date('Y-m-d', strtotime('+' . (int)$purchase['validita_giorni'] . ' days'));

        $stmt = $pdo->prepare(
            'UPDATE acquisti
             SET stato_pagamento = "confirmed", qr_code = ?, data_scadenza = ?, confermato_da = ?, data_conferma = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$qrCode, $scadenza, $staff['user_id'], $acquistoId]);

        logActivity((string)$staff['user_id'], 'conferma_pagamento', 'Pagamento confermato', 'acquisti', $acquistoId);

        $body = '<p>Ciao <strong>' . htmlspecialchars((string)$purchase['user_nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>il pagamento del pacchetto <strong>' . htmlspecialchars((string)$purchase['pacchetto_nome'], ENT_QUOTES, 'UTF-8') . '</strong> e stato confermato.</p>'
            . '<p>Codice QR: <strong>' . htmlspecialchars($qrCode, ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p>Ingressi disponibili: <strong>' . (int)$purchase['ingressi_rimanenti'] . '</strong></p>'
            . '<p>Scadenza: <strong>' . htmlspecialchars($scadenza, ENT_QUOTES, 'UTF-8') . '</strong></p>';

        sendTemplateEmail(
            (string)$purchase['user_email'],
            trim((string)$purchase['user_nome'] . ' ' . (string)$purchase['user_cognome']),
            'Pagamento confermato - Nuoto Libero',
            'Pacchetto attivo',
            $body
        );

        sendJson(200, [
            'success' => true,
            'message' => 'Pagamento confermato',
            'qr_code' => $qrCode,
            'data_scadenza' => $scadenza,
        ]);
    } catch (Throwable $e) {
        error_log('confirmPayment error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore conferma pagamento']);
    }
}

function cancelPurchase(): void
{
    global $pdo;

    $staff = requireRole(3);
    $acquistoId = sanitizeText((string)($_GET['id'] ?? ''), 36);

    if ($acquistoId === '') {
        sendJson(400, ['success' => false, 'message' => 'ID acquisto mancante']);
    }

    try {
        $stmt = $pdo->prepare('UPDATE acquisti SET stato_pagamento = "cancelled" WHERE id = ? AND stato_pagamento = "pending"');
        $stmt->execute([$acquistoId]);

        if ($stmt->rowCount() === 0) {
            sendJson(400, ['success' => false, 'message' => 'Nessun acquisto pending da annullare']);
        }

        logActivity((string)$staff['user_id'], 'annulla_acquisto', 'Acquisto annullato', 'acquisti', $acquistoId);

        sendJson(200, ['success' => true, 'message' => 'Acquisto annullato']);
    } catch (Throwable $e) {
        error_log('cancelPurchase error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore annullamento acquisto']);
    }
}

function renewPackage(): void
{
    global $pdo;

    $staff = requireRole(3);
    $data = getJsonInput();

    $userId = sanitizeText((string)($data['user_id'] ?? ''), 36);
    $pacchettoId = (int)($data['pacchetto_id'] ?? 0);
    $metodoPagamento = sanitizeText((string)($data['metodo_pagamento'] ?? 'contanti'), 20);
    $confermaImmediata = filter_var($data['conferma_immediata'] ?? true, FILTER_VALIDATE_BOOLEAN);

    if ($userId === '' || $pacchettoId <= 0) {
        sendJson(400, ['success' => false, 'message' => 'user_id e pacchetto_id sono obbligatori']);
    }

    try {
        $stmt = $pdo->prepare('SELECT id, nome, cognome, email FROM profili WHERE id = ? AND attivo = 1 LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            sendJson(404, ['success' => false, 'message' => 'Utente non trovato']);
        }

        $stmt = $pdo->prepare('SELECT * FROM pacchetti WHERE id = ? AND attivo = 1 LIMIT 1');
        $stmt->execute([$pacchettoId]);
        $package = $stmt->fetch();
        if (!$package) {
            sendJson(404, ['success' => false, 'message' => 'Pacchetto non trovato']);
        }

        $purchaseId = generateUuid();
        $qrCode = generateQRCode($purchaseId);
        $scadenza = date('Y-m-d', strtotime('+' . (int)$package['validita_giorni'] . ' days'));
        $status = $confermaImmediata ? 'confirmed' : 'pending';

        $stmt = $pdo->prepare(
            'INSERT INTO acquisti
            (id, user_id, pacchetto_id, metodo_pagamento, stato_pagamento, qr_code, ingressi_rimanenti, data_scadenza, confermato_da, data_conferma, importo_pagato)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, IF(? = "confirmed", NOW(), NULL), ?)'
        );
        $stmt->execute([
            $purchaseId,
            $userId,
            $pacchettoId,
            $metodoPagamento,
            $status,
            $qrCode,
            (int)$package['num_ingressi'],
            $scadenza,
            $confermaImmediata ? $staff['user_id'] : null,
            $status,
            (float)$package['prezzo'],
        ]);

        logActivity((string)$staff['user_id'], 'rinnovo_pacchetto', 'Rinnovo pacchetto utente', 'acquisti', $purchaseId);

        sendJson(201, [
            'success' => true,
            'message' => 'Pacchetto assegnato correttamente',
            'acquisto' => [
                'id' => $purchaseId,
                'stato_pagamento' => $status,
                'qr_code' => $qrCode,
                'data_scadenza' => $scadenza,
                'ingressi_rimanenti' => (int)$package['num_ingressi'],
            ],
        ]);
    } catch (Throwable $e) {
        error_log('renewPackage error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore rinnovo pacchetto']);
    }
}
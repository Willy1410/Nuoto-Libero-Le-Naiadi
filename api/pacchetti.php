<?php

/**
 * API pacchetti
 * GET    /api/pacchetti.php
 * POST   /api/pacchetti.php
 * GET    /api/pacchetti.php?action=my-purchases
 * GET    /api/pacchetti.php?action=pending
 * PATCH  /api/pacchetti.php?action=confirm&id=xxx
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
} elseif ($method === 'PATCH' && $action === 'confirm') {
    confirmPayment();
} else {
    respond(404, ['success' => false, 'message' => 'Endpoint non trovato']);
}

function respond(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

function paymentMethodLabel(string $method): string
{
    return match ($method) {
        'stripe', 'carta' => 'Carta di credito/debito',
        'paypal' => 'PayPal',
        'contanti', 'instore' => 'Pagamento in struttura',
        'bonifico' => 'Bonifico bancario',
        default => ucfirst($method),
    };
}

function normalizePaymentMethod(string $method): string
{
    $value = strtolower(trim($method));
    if ($value === 'instore') {
        return 'contanti';
    }

    return $value;
}

function isImmediatePaymentMethod(string $method): bool
{
    return in_array($method, ['paypal', 'stripe', 'carta'], true);
}

function buildAbsoluteUrl(string $path): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');

    $scriptDir = str_replace('\\', '/', (string)dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/api')));
    $scriptDir = rtrim($scriptDir, '/');
    if (substr($scriptDir, -4) === '/api') {
        $scriptDir = substr($scriptDir, 0, -4);
    }

    return $scheme . '://' . $host . $scriptDir . '/' . ltrim($path, '/');
}

function getPacchetti(): void
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT * FROM pacchetti WHERE attivo = TRUE ORDER BY ordine ASC, prezzo ASC');
    $stmt->execute();

    respond(200, ['success' => true, 'pacchetti' => $stmt->fetchAll()]);
}

function acquistaPacchetto(): void
{
    global $pdo;

    $currentUser = requireAuth();
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }

    $pacchettoId = (int)($data['pacchetto_id'] ?? 0);
    $metodoPagamento = normalizePaymentMethod((string)($data['metodo_pagamento'] ?? 'bonifico'));
    $riferimentoPagamento = sanitizeInput($data['riferimento_pagamento'] ?? '');
    $notePagamento = sanitizeInput($data['note_pagamento'] ?? '');

    $allowedMethods = ['bonifico', 'paypal', 'stripe', 'carta', 'contanti'];
    if (!in_array($metodoPagamento, $allowedMethods, true)) {
        respond(400, ['success' => false, 'message' => 'Metodo pagamento non supportato']);
    }

    if ($pacchettoId <= 0) {
        respond(400, ['success' => false, 'message' => 'Pacchetto non specificato']);
    }

    $stmt = $pdo->prepare('SELECT * FROM pacchetti WHERE id = ? AND attivo = TRUE LIMIT 1');
    $stmt->execute([$pacchettoId]);
    $pacchetto = $stmt->fetch();

    if (!$pacchetto) {
        respond(404, ['success' => false, 'message' => 'Pacchetto non trovato']);
    }

    $isImmediate = isImmediatePaymentMethod($metodoPagamento);
    $statoPagamento = $isImmediate ? 'confirmed' : 'pending';

    try {
        $acquistoId = generateUuid();
        $qrCode = $isImmediate ? generateQRCode($acquistoId) : null;
        $dataScadenza = $isImmediate
            ? date('Y-m-d', strtotime('+' . (int)$pacchetto['validita_giorni'] . ' days'))
            : null;

        $stmt = $pdo->prepare(
            'INSERT INTO acquisti
             (id, user_id, pacchetto_id, metodo_pagamento, stato_pagamento, riferimento_pagamento, note_pagamento, qr_code, ingressi_rimanenti, data_scadenza, confermato_da, data_conferma, importo_pagato)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $acquistoId,
            $currentUser['user_id'],
            $pacchettoId,
            $metodoPagamento,
            $statoPagamento,
            $riferimentoPagamento,
            $notePagamento,
            $qrCode,
            (int)$pacchetto['num_ingressi'],
            $dataScadenza,
            $isImmediate ? $currentUser['user_id'] : null,
            $isImmediate ? date('Y-m-d H:i:s') : null,
            (float)$pacchetto['prezzo'],
        ]);

        $stmt = $pdo->prepare(
            'SELECT a.*, p.nome AS pacchetto_nome, p.descrizione AS pacchetto_descrizione, p.num_ingressi, p.validita_giorni
             FROM acquisti a
             JOIN pacchetti p ON a.pacchetto_id = p.id
             WHERE a.id = ?
             LIMIT 1'
        );
        $stmt->execute([$acquistoId]);
        $acquisto = $stmt->fetch();

        logActivity(
            (string)$currentUser['user_id'],
            $isImmediate ? 'acquisto_confermato_automatico' : 'acquisto_pacchetto',
            'Acquisto: ' . (string)$pacchetto['nome'] . ' (' . $statoPagamento . ')',
            'acquisti',
            (string)$acquistoId
        );

        $stmt = $pdo->prepare('SELECT nome, cognome, email FROM profili WHERE id = ? LIMIT 1');
        $stmt->execute([$currentUser['user_id']]);
        $user = $stmt->fetch();

        $mailSent = false;
        if ($user && !empty($user['email'])) {
            $metodoLabel = paymentMethodLabel($metodoPagamento);
            $fullName = trim((string)$user['nome'] . ' ' . (string)$user['cognome']);

            if ($isImmediate) {
                $qrDownloadUrl = buildAbsoluteUrl('api/qr.php?action=download&acquisto_id=' . urlencode((string)$acquistoId));
                $body = '<p>Ciao <strong>' . htmlspecialchars((string)$user['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
                    . '<p>il tuo acquisto e stato confermato e il QR code e gia disponibile.</p>'
                    . '<p><strong>Pacchetto:</strong> ' . htmlspecialchars((string)$pacchetto['nome'], ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Importo:</strong> EUR ' . number_format((float)$pacchetto['prezzo'], 2, ',', '.') . '<br>'
                    . '<strong>Ingressi inclusi:</strong> ' . (int)$pacchetto['num_ingressi'] . '<br>'
                    . '<strong>Metodo di pagamento:</strong> ' . htmlspecialchars($metodoLabel, ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Codice QR:</strong> <code>' . htmlspecialchars((string)$qrCode, ENT_QUOTES, 'UTF-8') . '</code><br>'
                    . '<strong>Scadenza:</strong> ' . htmlspecialchars((string)$dataScadenza, ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<p><a href="' . htmlspecialchars($qrDownloadUrl, ENT_QUOTES, 'UTF-8') . '">Scarica QR in PDF</a></p>';

                $mailSent = sendBrandedEmail(
                    (string)$user['email'],
                    $fullName,
                    'Pagamento confermato e QR disponibile - Gli Squaletti',
                    'QR code disponibile',
                    $body,
                    'Pagamento confermato'
                );
            } else {
                $body = '<p>Ciao <strong>' . htmlspecialchars((string)$user['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
                    . '<p>abbiamo ricevuto la tua richiesta di acquisto pacchetto.</p>'
                    . '<p><strong>Pacchetto:</strong> ' . htmlspecialchars((string)$pacchetto['nome'], ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Importo:</strong> EUR ' . number_format((float)$pacchetto['prezzo'], 2, ',', '.') . '<br>'
                    . '<strong>Metodo di pagamento:</strong> ' . htmlspecialchars($metodoLabel, ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Riferimento ordine:</strong> <code>' . htmlspecialchars((string)$acquistoId, ENT_QUOTES, 'UTF-8') . '</code></p>'
                    . '<p>Il pacchetto risulta in <strong>attesa di conferma</strong>. Riceverai un aggiornamento appena il pagamento sara verificato.</p>';

                $mailSent = sendBrandedEmail(
                    (string)$user['email'],
                    $fullName,
                    'Conferma richiesta pacchetto - Gli Squaletti',
                    'Richiesta pacchetto ricevuta',
                    $body,
                    'Conferma richiesta pacchetto'
                );
            }

            if (!$mailSent) {
                logMailEvent('warning', 'Email acquisto non inviata', [
                    'acquisto_id' => $acquistoId,
                    'email' => $user['email'],
                ]);
            }
        }

        respond(201, [
            'success' => true,
            'message' => $isImmediate
                ? 'Pagamento confermato. QR code generato con successo.'
                : 'Richiesta registrata correttamente, in attesa di conferma.',
            'acquisto' => $acquisto,
            'mail_sent' => $mailSent,
            'requires_manual_confirmation' => !$isImmediate,
        ]);
    } catch (Throwable $e) {
        error_log('acquistaPacchetto error: ' . $e->getMessage());
        respond(500, ['success' => false, 'message' => 'Errore durante l\'acquisto']);
    }
}

function getMyPurchases(): void
{
    global $pdo;

    $currentUser = requireAuth();

    $stmt = $pdo->prepare(
        'SELECT a.*, p.nome AS pacchetto_nome, p.descrizione AS pacchetto_descrizione, p.validita_giorni, p.num_ingressi
         FROM acquisti a
         JOIN pacchetti p ON a.pacchetto_id = p.id
         WHERE a.user_id = ?
         ORDER BY a.data_acquisto DESC'
    );
    $stmt->execute([$currentUser['user_id']]);

    respond(200, ['success' => true, 'acquisti' => $stmt->fetchAll()]);
}

function getPendingPurchases(): void
{
    global $pdo;

    requireRole(3);

    $stmt = $pdo->query(
        'SELECT a.id, a.user_id, a.pacchetto_id, a.metodo_pagamento, a.stato_pagamento, a.riferimento_pagamento,
                a.note_pagamento, a.data_acquisto, a.importo_pagato,
                p.nome AS pacchetto_nome,
                prof.nome AS user_nome,
                prof.cognome AS user_cognome,
                prof.email AS user_email
         FROM acquisti a
         JOIN pacchetti p ON p.id = a.pacchetto_id
         JOIN profili prof ON prof.id = a.user_id
         WHERE a.stato_pagamento = \'pending\'
         ORDER BY a.data_acquisto ASC'
    );

    respond(200, ['success' => true, 'acquisti' => $stmt->fetchAll()]);
}

function confirmPayment(): void
{
    global $pdo;

    $currentUser = requireRole(3);
    $acquistoId = (string)($_GET['id'] ?? '');

    if ($acquistoId === '') {
        respond(400, ['success' => false, 'message' => 'ID acquisto non specificato']);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT a.*, p.validita_giorni, p.nome AS pacchetto_nome, p.num_ingressi
             FROM acquisti a
             JOIN pacchetti p ON a.pacchetto_id = p.id
             WHERE a.id = ?
             LIMIT 1'
        );
        $stmt->execute([$acquistoId]);
        $acquisto = $stmt->fetch();

        if (!$acquisto) {
            respond(404, ['success' => false, 'message' => 'Acquisto non trovato']);
        }

        if ((string)$acquisto['stato_pagamento'] === 'confirmed') {
            respond(400, ['success' => false, 'message' => 'Acquisto gia confermato']);
        }

        $qrCode = generateQRCode($acquistoId);
        $dataScadenza = date('Y-m-d', strtotime('+' . (int)$acquisto['validita_giorni'] . ' days'));

        $stmt = $pdo->prepare(
            'UPDATE acquisti
             SET stato_pagamento = \'confirmed\',
                 qr_code = ?,
                 data_scadenza = ?,
                 confermato_da = ?,
                 data_conferma = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$qrCode, $dataScadenza, $currentUser['user_id'], $acquistoId]);

        logActivity(
            (string)$currentUser['user_id'],
            'conferma_pagamento',
            'Pagamento confermato per acquisto ' . $acquistoId,
            'acquisti',
            $acquistoId
        );

        $stmt = $pdo->prepare('SELECT nome, cognome, email FROM profili WHERE id = ? LIMIT 1');
        $stmt->execute([$acquisto['user_id']]);
        $user = $stmt->fetch();

        $mailSent = false;
        if ($user && !empty($user['email'])) {
            $qrDownloadUrl = buildAbsoluteUrl('api/qr.php?action=download&acquisto_id=' . urlencode($acquistoId));
            $body = '<p>Ciao <strong>' . htmlspecialchars((string)$user['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
                . '<p>il tuo pagamento e stato <strong>confermato</strong> e il tuo QR e pronto.</p>'
                . '<p><strong>Pacchetto:</strong> ' . htmlspecialchars((string)$acquisto['pacchetto_nome'], ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Codice QR:</strong> <code>' . htmlspecialchars($qrCode, ENT_QUOTES, 'UTF-8') . '</code><br>'
                . '<strong>Ingressi disponibili:</strong> ' . (int)$acquisto['ingressi_rimanenti'] . '<br>'
                . '<strong>Data scadenza:</strong> ' . htmlspecialchars($dataScadenza, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p><a href="' . htmlspecialchars($qrDownloadUrl, ENT_QUOTES, 'UTF-8') . '">Scarica QR in PDF</a></p>';

            $mailSent = sendBrandedEmail(
                (string)$user['email'],
                trim((string)$user['nome'] . ' ' . (string)$user['cognome']),
                'Pagamento confermato e QR pronto - Gli Squaletti',
                'Pagamento confermato',
                $body,
                'Il tuo QR e pronto'
            );

            if (!$mailSent) {
                logMailEvent('warning', 'Email conferma pagamento non inviata', [
                    'acquisto_id' => $acquistoId,
                    'email' => $user['email'],
                ]);
            }
        }

        respond(200, [
            'success' => true,
            'message' => 'Pagamento confermato',
            'qr_code' => $qrCode,
            'mail_sent' => $mailSent,
        ]);
    } catch (Throwable $e) {
        error_log('confirmPayment error: ' . $e->getMessage());
        respond(500, ['success' => false, 'message' => 'Errore durante la conferma del pagamento']);
    }
}



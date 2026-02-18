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
} elseif ($method === 'GET' && $action === 'admin-list') {
    getAllPackages();
} elseif ($method === 'POST' && $action === 'admin-create-package') {
    createPackage();
} elseif ($method === 'POST' && $action === 'admin-assign-manual') {
    assignManualPackage();
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
        'contanti', 'instore' => 'Contributo in struttura',
        'bonifico' => 'Bonifico bancario',
        default => 'Contributo',
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
    return false;
}

function generateUniqueQrCode(string $acquistoId, int $maxAttempts = 12): string
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT id FROM acquisti WHERE qr_code = ? LIMIT 1');

    for ($i = 0; $i < $maxAttempts; $i++) {
        $candidate = generateQRCode($acquistoId);
        $stmt->execute([$candidate]);
        if (!$stmt->fetch()) {
            return $candidate;
        }
    }

    throw new RuntimeException('Impossibile generare un QR univoco');
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

function getAllPackages(): void
{
    global $pdo;

    requireRole(3);

    $stmt = $pdo->query(
        'SELECT id, nome, descrizione, num_ingressi, prezzo, validita_giorni, attivo, ordine, created_at, updated_at
         FROM pacchetti
         ORDER BY attivo DESC, ordine ASC, created_at DESC'
    );

    respond(200, ['success' => true, 'pacchetti' => $stmt->fetchAll()]);
}

function createPackage(): void
{
    global $pdo;

    $staff = requireRole(3);
    $data = getJsonInput();

    $nome = sanitizeText((string)($data['nome'] ?? ''), 120);
    $descrizione = sanitizeText((string)($data['descrizione'] ?? ''), 500);
    $numIngressi = (int)($data['num_ingressi'] ?? 0);
    $prezzo = (float)($data['prezzo'] ?? 0);
    $validita = (int)($data['validita_giorni'] ?? 0);
    $attivo = array_key_exists('attivo', $data) ? (int)((bool)$data['attivo']) : 1;
    $ordine = max(0, (int)($data['ordine'] ?? 0));

    if ($nome === '' || $numIngressi <= 0 || $validita <= 0 || $prezzo < 0) {
        respond(400, ['success' => false, 'message' => 'Dati pacchetto non validi']);
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO pacchetti (nome, descrizione, num_ingressi, prezzo, validita_giorni, attivo, ordine)
             VALUES (?, NULLIF(?, ""), ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$nome, $descrizione, $numIngressi, $prezzo, $validita, $attivo, $ordine]);

        $packageId = (int)$pdo->lastInsertId();

        logActivity(
            (string)$staff['user_id'],
            'crea_pacchetto',
            'Creato pacchetto: ' . $nome,
            'pacchetti',
            (string)$packageId
        );

        $stmt = $pdo->prepare('SELECT * FROM pacchetti WHERE id = ? LIMIT 1');
        $stmt->execute([$packageId]);

        respond(201, [
            'success' => true,
            'message' => 'Pacchetto creato',
            'pacchetto' => $stmt->fetch(),
        ]);
    } catch (Throwable $e) {
        error_log('createPackage error: ' . $e->getMessage());
        respond(500, ['success' => false, 'message' => 'Errore creazione pacchetto']);
    }
}

function assignManualPackage(): void
{
    global $pdo;

    $staff = requireRole(3);
    $data = getJsonInput();

    $userId = sanitizeText((string)($data['user_id'] ?? ''), 36);
    $packageMode = sanitizeText((string)($data['package_mode'] ?? 'existing'), 20);
    $metodoPagamento = normalizePaymentMethod((string)($data['metodo_pagamento'] ?? 'contanti'));
    $statoPagamento = sanitizeText((string)($data['stato_pagamento'] ?? 'confirmed'), 20);
    $riferimentoPagamento = sanitizeText((string)($data['riferimento_pagamento'] ?? ''), 255);
    $notePagamento = sanitizeText((string)($data['note_pagamento'] ?? ''), 1000);
    $sendEmail = !array_key_exists('send_email', $data) || (bool)$data['send_email'];
    $importoManuale = array_key_exists('importo_pagato', $data) ? (float)$data['importo_pagato'] : null;

    if ($userId === '' || !in_array($packageMode, ['existing', 'custom'], true)) {
        respond(400, ['success' => false, 'message' => 'Utente o modalita pacchetto non validi']);
    }

    $allowedMethods = ['bonifico', 'contanti'];
    if (!in_array($metodoPagamento, $allowedMethods, true)) {
        respond(400, ['success' => false, 'message' => 'Metodo pagamento non supportato']);
    }

    if (!in_array($statoPagamento, ['pending', 'confirmed', 'cancelled'], true)) {
        respond(400, ['success' => false, 'message' => 'Stato pagamento non valido']);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT p.id, p.nome, p.cognome, p.email
             FROM profili p
             JOIN ruoli r ON r.id = p.ruolo_id
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $userRow = $stmt->fetch();
        if (!$userRow) {
            respond(404, ['success' => false, 'message' => 'Utente non trovato']);
        }

        $pdo->beginTransaction();

        $pacchetto = null;
        if ($packageMode === 'existing') {
            $packageId = (int)($data['pacchetto_id'] ?? 0);
            if ($packageId <= 0) {
                $pdo->rollBack();
                respond(400, ['success' => false, 'message' => 'Pacchetto non specificato']);
            }

            $stmt = $pdo->prepare('SELECT * FROM pacchetti WHERE id = ? LIMIT 1');
            $stmt->execute([$packageId]);
            $pacchetto = $stmt->fetch();
            if (!$pacchetto) {
                $pdo->rollBack();
                respond(404, ['success' => false, 'message' => 'Pacchetto non trovato']);
            }
        } else {
            $custom = is_array($data['custom_package'] ?? null) ? $data['custom_package'] : [];

            $kind = sanitizeText((string)($custom['kind'] ?? 'personalizzato'), 20);
            $namePrefix = match ($kind) {
                'omaggio' => '[OMAGGIO] ',
                'gift' => '[BUONO REGALO] ',
                default => '[CUSTOM] ',
            };

            $nome = sanitizeText((string)($custom['nome'] ?? ''), 120);
            $descrizione = sanitizeText((string)($custom['descrizione'] ?? ''), 500);
            $numIngressi = (int)($custom['num_ingressi'] ?? 0);
            $prezzo = (float)($custom['prezzo'] ?? 0);
            $validita = (int)($custom['validita_giorni'] ?? 0);
            $listed = !empty($custom['listed']);

            if ($nome === '' || $numIngressi <= 0 || $validita <= 0 || $prezzo < 0) {
                $pdo->rollBack();
                respond(400, ['success' => false, 'message' => 'Dati pacchetto personalizzato non validi']);
            }

            $stmt = $pdo->prepare(
                'INSERT INTO pacchetti (nome, descrizione, num_ingressi, prezzo, validita_giorni, attivo, ordine)
                 VALUES (?, NULLIF(?, ""), ?, ?, ?, ?, 999)'
            );
            $stmt->execute([$namePrefix . $nome, $descrizione, $numIngressi, $prezzo, $validita, $listed ? 1 : 0]);

            $customPackageId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare('SELECT * FROM pacchetti WHERE id = ? LIMIT 1');
            $stmt->execute([$customPackageId]);
            $pacchetto = $stmt->fetch();
        }

        if (!$pacchetto) {
            $pdo->rollBack();
            respond(500, ['success' => false, 'message' => 'Pacchetto non disponibile']);
        }

        $acquistoId = generateUuid();
        $isConfirmed = $statoPagamento === 'confirmed';
        $qrCode = $isConfirmed ? generateUniqueQrCode($acquistoId) : null;
        $dataScadenza = $isConfirmed
            ? date('Y-m-d', strtotime('+' . (int)$pacchetto['validita_giorni'] . ' days'))
            : null;
        $importoPagato = $importoManuale !== null ? $importoManuale : (float)$pacchetto['prezzo'];
        $ingressiRimanenti = (int)$pacchetto['num_ingressi'];

        $stmt = $pdo->prepare(
            'INSERT INTO acquisti
             (id, user_id, pacchetto_id, metodo_pagamento, stato_pagamento, riferimento_pagamento, note_pagamento, qr_code, ingressi_rimanenti, data_scadenza, confermato_da, data_conferma, importo_pagato)
             VALUES (?, ?, ?, ?, ?, NULLIF(?, ""), NULLIF(?, ""), ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $acquistoId,
            $userId,
            $pacchetto['id'],
            $metodoPagamento,
            $statoPagamento,
            $riferimentoPagamento,
            $notePagamento,
            $qrCode,
            $ingressiRimanenti,
            $dataScadenza,
            $isConfirmed ? $staff['user_id'] : null,
            $isConfirmed ? date('Y-m-d H:i:s') : null,
            $importoPagato,
        ]);

        $pdo->commit();

        logActivity(
            (string)$staff['user_id'],
            'assegna_pacchetto_manuale',
            'Assegnato pacchetto a utente: ' . (string)$userRow['email'],
            'acquisti',
            $acquistoId
        );

        $mailSent = false;
        if ($sendEmail && !empty($userRow['email'])) {
            $subject = $isConfirmed
                ? 'Pacchetto assegnato e confermato - Gli Squaletti'
                : 'Pacchetto assegnato in attesa - Gli Squaletti';

            $body = '<p>Ciao <strong>' . htmlspecialchars((string)$userRow['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
                . '<p>ti e stato assegnato un nuovo pacchetto dalla segreteria.</p>'
                . '<p><strong>Pacchetto:</strong> ' . htmlspecialchars((string)$pacchetto['nome'], ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Ingressi:</strong> ' . (int)$pacchetto['num_ingressi'] . '<br>'
                . '<strong>Importo:</strong> EUR ' . number_format((float)$importoPagato, 2, ',', '.') . '<br>'
                . '<strong>Stato:</strong> ' . htmlspecialchars($statoPagamento, ENT_QUOTES, 'UTF-8') . '</p>';

            if ($isConfirmed && $qrCode) {
                $qrDownloadUrl = buildAbsoluteUrl('api/qr.php?action=download&acquisto_id=' . urlencode($acquistoId));
                $body .= '<p><strong>Codice QR:</strong> <code>' . htmlspecialchars($qrCode, ENT_QUOTES, 'UTF-8') . '</code><br>'
                    . '<a href="' . htmlspecialchars($qrDownloadUrl, ENT_QUOTES, 'UTF-8') . '">Scarica QR in PDF</a></p>';
            }

            $mailSent = sendBrandedEmail(
                (string)$userRow['email'],
                trim((string)$userRow['nome'] . ' ' . (string)$userRow['cognome']),
                $subject,
                'Nuovo pacchetto assegnato',
                $body,
                'Nuovo pacchetto assegnato'
            );
        }

        respond(201, [
            'success' => true,
            'message' => 'Pacchetto assegnato con successo',
            'acquisto_id' => $acquistoId,
            'qr_code' => $qrCode,
            'mail_sent' => $mailSent,
            'stato_pagamento' => $statoPagamento,
            'pacchetto' => $pacchetto,
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('assignManualPackage error: ' . $e->getMessage());
        respond(500, ['success' => false, 'message' => 'Errore assegnazione pacchetto']);
    }
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

    $allowedMethods = ['bonifico', 'contanti'];
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
        $qrCode = $isImmediate ? generateUniqueQrCode($acquistoId) : null;
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
                    . '<p>abbiamo ricevuto la tua richiesta di attivazione pacchetto ingressi.</p>'
                    . '<p><strong>Pacchetto:</strong> ' . htmlspecialchars((string)$pacchetto['nome'], ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Importo:</strong> EUR ' . number_format((float)$pacchetto['prezzo'], 2, ',', '.') . '<br>'
                    . '<strong>Metodo di pagamento:</strong> ' . htmlspecialchars($metodoLabel, ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Riferimento pratica:</strong> <code>' . htmlspecialchars((string)$acquistoId, ENT_QUOTES, 'UTF-8') . '</code></p>'
                    . '<p>Il pacchetto risulta in <strong>attesa di conferma</strong>. Riceverai un aggiornamento appena il pagamento sara verificato.</p>';

                $mailSent = sendBrandedEmail(
                    (string)$user['email'],
                    $fullName,
                    'Conferma richiesta pacchetto ingressi - Gli Squaletti',
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
        respond(500, ['success' => false, 'message' => 'Errore durante la richiesta pacchetto']);
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
                a.note_pagamento, a.data_acquisto, a.importo_pagato, a.qr_code,
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
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT a.*, p.validita_giorni, p.nome AS pacchetto_nome, p.num_ingressi
             FROM acquisti a
             JOIN pacchetti p ON a.pacchetto_id = p.id
             WHERE a.id = ?
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute([$acquistoId]);
        $acquisto = $stmt->fetch();

        if (!$acquisto) {
            $pdo->rollBack();
            respond(404, ['success' => false, 'message' => 'Acquisto non trovato']);
        }

        $alreadyConfirmed = (string)$acquisto['stato_pagamento'] === 'confirmed';
        $existingQr = trim((string)($acquisto['qr_code'] ?? ''));
        $dataScadenza = !empty($acquisto['data_scadenza'])
            ? (string)$acquisto['data_scadenza']
            : date('Y-m-d', strtotime('+' . (int)$acquisto['validita_giorni'] . ' days'));

        if ($alreadyConfirmed && $existingQr !== '') {
            $pdo->commit();
            respond(200, [
                'success' => true,
                'message' => 'Pagamento gia confermato',
                'qr_code' => $existingQr,
                'mail_sent' => false,
                'already_confirmed' => true,
            ]);
        }

        if ($alreadyConfirmed) {
            $qrCode = $existingQr !== '' ? $existingQr : generateUniqueQrCode($acquistoId);
        } else {
            // Per flusso ufficio/admin rigeneriamo sempre il QR al momento della conferma.
            $qrCode = generateUniqueQrCode($acquistoId);
        }
        $shouldNotify = !$alreadyConfirmed || $existingQr === '';

        $stmt = $pdo->prepare(
            'UPDATE acquisti
             SET stato_pagamento = \'confirmed\',
                 qr_code = ?,
                 data_scadenza = COALESCE(data_scadenza, ?),
                 confermato_da = COALESCE(confermato_da, ?),
                 data_conferma = COALESCE(data_conferma, NOW())
             WHERE id = ?'
        );
        $stmt->execute([$qrCode, $dataScadenza, $currentUser['user_id'], $acquistoId]);

        $pdo->commit();

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
        if ($shouldNotify && $user && !empty($user['email'])) {
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
            'message' => $alreadyConfirmed ? 'Pagamento gia confermato, QR ripristinato' : 'Pagamento confermato',
            'qr_code' => $qrCode,
            'mail_sent' => $mailSent,
            'already_confirmed' => $alreadyConfirmed,
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('confirmPayment error: ' . $e->getMessage());
        respond(500, ['success' => false, 'message' => 'Errore durante la conferma del pagamento']);
    }
}



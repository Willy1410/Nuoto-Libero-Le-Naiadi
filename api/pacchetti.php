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
} elseif ($method === 'PATCH' && $action === 'admin-update-package') {
    updatePackage();
} elseif ($method === 'PATCH' && $action === 'admin-toggle-package') {
    togglePackageVisibility();
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
        'contanti', 'instore', 'bonifico' => 'Finalizzazione in struttura',
        default => 'Finalizzazione in struttura',
    };
}

function normalizePaymentMethod(string $method): string
{
    $value = strtolower(trim($method));
    if ($value === 'instore' || $value === 'bonifico') {
        return 'contanti';
    }

    return $value;
}

function isImmediatePaymentMethod(string $method): bool
{
    return false;
}

function resolveUserStaticQrToken(string $userId): string
{
    $token = getOrCreateUserQrToken($userId);
    if ($token === '') {
        throw new RuntimeException('Impossibile recuperare qr_token utente');
    }
    return $token;
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

function packagesTableAvailable(): bool
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }

    global $pdo;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'packages'");
        $available = (bool)$stmt->fetch();
    } catch (Throwable $e) {
        $available = false;
    }

    return $available;
}

function defaultPackageValidityDays(): int
{
    return 365;
}

function acquistiHasIngressiTotaliColumn(): bool
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }

    global $pdo;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM acquisti LIKE 'ingressi_totali'");
        $available = (bool)$stmt->fetch();
    } catch (Throwable $e) {
        $available = false;
    }

    return $available;
}

function ingressiTotaliSelectSql(string $acquistiAlias = 'a', string $pacchettiAlias = 'p'): string
{
    if (acquistiHasIngressiTotaliColumn()) {
        return 'COALESCE(NULLIF(' . $acquistiAlias . '.ingressi_totali, 0), ' . $pacchettiAlias . '.num_ingressi)';
    }

    return $pacchettiAlias . '.num_ingressi';
}

function normalizeManagedPackageRow(array $row): array
{
    return [
        'id' => (int)$row['id'],
        'nome' => (string)$row['name'],
        'descrizione' => (string)($row['description'] ?? ''),
        'num_ingressi' => (int)$row['entries_count'],
        'prezzo' => (float)$row['price'],
        'attivo' => (int)$row['visible'],
        'validita_giorni' => isset($row['validita_giorni']) ? (int)$row['validita_giorni'] : defaultPackageValidityDays(),
        'legacy_pacchetto_id' => isset($row['legacy_pacchetto_id']) ? (int)$row['legacy_pacchetto_id'] : 0,
        'created_at' => $row['created_at'] ?? null,
        'updated_at' => $row['updated_at'] ?? null,
    ];
}

function fetchManagedPackages(bool $onlyVisible): array
{
    global $pdo;

    if (!packagesTableAvailable()) {
        $sql = 'SELECT id, nome, descrizione, num_ingressi, prezzo, validita_giorni, attivo, created_at, updated_at
                FROM pacchetti';
        if ($onlyVisible) {
            $sql .= ' WHERE attivo = 1';
        }
        $sql .= ' ORDER BY ordine ASC, prezzo ASC';

        $stmt = $pdo->query($sql);
        $rows = [];
        foreach ($stmt->fetchAll() as $legacy) {
            $rows[] = [
                'id' => (int)$legacy['id'],
                'nome' => (string)$legacy['nome'],
                'descrizione' => (string)($legacy['descrizione'] ?? ''),
                'num_ingressi' => (int)$legacy['num_ingressi'],
                'prezzo' => (float)$legacy['prezzo'],
                'attivo' => (int)$legacy['attivo'],
                'validita_giorni' => (int)$legacy['validita_giorni'],
                'legacy_pacchetto_id' => (int)$legacy['id'],
                'created_at' => $legacy['created_at'] ?? null,
                'updated_at' => $legacy['updated_at'] ?? null,
            ];
        }
        return $rows;
    }

    $sql = 'SELECT pkg.id, pkg.name, pkg.description, pkg.entries_count, pkg.price, pkg.visible,
                   pkg.legacy_pacchetto_id, pkg.created_at, pkg.updated_at,
                   COALESCE(lp.validita_giorni, ?) AS validita_giorni
            FROM packages pkg
            LEFT JOIN pacchetti lp ON lp.id = pkg.legacy_pacchetto_id';
    if ($onlyVisible) {
        $sql .= ' WHERE pkg.visible = 1';
    }
    $sql .= ' ORDER BY pkg.visible DESC, pkg.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([defaultPackageValidityDays()]);

    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[] = normalizeManagedPackageRow($row);
    }

    return $rows;
}

function fetchManagedPackageById(int $packageId): ?array
{
    global $pdo;

    if ($packageId <= 0) {
        return null;
    }

    if (!packagesTableAvailable()) {
        $stmt = $pdo->prepare(
            'SELECT id, nome, descrizione, num_ingressi, prezzo, validita_giorni, attivo, created_at, updated_at
             FROM pacchetti
             WHERE id = ?
             LIMIT 1'
        );
        $stmt->execute([$packageId]);
        $legacy = $stmt->fetch();
        if (!$legacy) {
            return null;
        }
        return [
            'id' => (int)$legacy['id'],
            'nome' => (string)$legacy['nome'],
            'descrizione' => (string)($legacy['descrizione'] ?? ''),
            'num_ingressi' => (int)$legacy['num_ingressi'],
            'prezzo' => (float)$legacy['prezzo'],
            'attivo' => (int)$legacy['attivo'],
            'validita_giorni' => (int)$legacy['validita_giorni'],
            'legacy_pacchetto_id' => (int)$legacy['id'],
            'created_at' => $legacy['created_at'] ?? null,
            'updated_at' => $legacy['updated_at'] ?? null,
        ];
    }

    $stmt = $pdo->prepare(
        'SELECT pkg.id, pkg.name, pkg.description, pkg.entries_count, pkg.price, pkg.visible,
                pkg.legacy_pacchetto_id, pkg.created_at, pkg.updated_at,
                COALESCE(lp.validita_giorni, ?) AS validita_giorni
         FROM packages pkg
         LEFT JOIN pacchetti lp ON lp.id = pkg.legacy_pacchetto_id
         WHERE pkg.id = ?
         LIMIT 1'
    );
    $stmt->execute([defaultPackageValidityDays(), $packageId]);
    $row = $stmt->fetch();
    if (!$row) {
        return null;
    }

    return normalizeManagedPackageRow($row);
}

function ensureLegacyPackage(array $managedPackage): array
{
    global $pdo;

    $legacyId = (int)($managedPackage['legacy_pacchetto_id'] ?? 0);
    if ($legacyId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM pacchetti WHERE id = ? LIMIT 1');
        $stmt->execute([$legacyId]);
        $legacy = $stmt->fetch();
        if ($legacy) {
            return $legacy;
        }
    }

    $stmt = $pdo->prepare(
        'INSERT INTO pacchetti (nome, descrizione, num_ingressi, prezzo, validita_giorni, attivo, ordine)
         VALUES (?, NULLIF(?, ""), ?, ?, ?, ?, 1)'
    );
    $stmt->execute([
        (string)$managedPackage['nome'],
        (string)($managedPackage['descrizione'] ?? ''),
        (int)$managedPackage['num_ingressi'],
        (float)$managedPackage['prezzo'],
        max(1, (int)($managedPackage['validita_giorni'] ?? defaultPackageValidityDays())),
        (int)$managedPackage['attivo'] === 1 ? 1 : 0,
    ]);

    $legacyId = (int)$pdo->lastInsertId();
    if (packagesTableAvailable() && (int)($managedPackage['id'] ?? 0) > 0) {
        $stmt = $pdo->prepare('UPDATE packages SET legacy_pacchetto_id = ? WHERE id = ?');
        $stmt->execute([$legacyId, (int)$managedPackage['id']]);
    }

    $stmt = $pdo->prepare('SELECT * FROM pacchetti WHERE id = ? LIMIT 1');
    $stmt->execute([$legacyId]);
    $legacy = $stmt->fetch();
    if (!$legacy) {
        throw new RuntimeException('Pacchetto legacy non disponibile');
    }

    return $legacy;
}

function getPacchetti(): void
{
    $packages = fetchManagedPackages(true);
    respond(200, ['success' => true, 'pacchetti' => $packages]);
}

function getAllPackages(): void
{
    requireRole(3);
    $packages = fetchManagedPackages(false);
    respond(200, ['success' => true, 'pacchetti' => $packages]);
}

function createPackage(): void
{
    global $pdo;

    $staff = requireRole(3);
    $data = getJsonInput();

    $nome = sanitizeText((string)($data['nome'] ?? ''), 100);
    $descrizione = sanitizeText((string)($data['descrizione'] ?? ''), 1000);
    $numIngressi = (int)($data['num_ingressi'] ?? 0);
    $prezzo = (float)($data['prezzo'] ?? 0);
    $visible = array_key_exists('attivo', $data) ? ((bool)$data['attivo'] ? 1 : 0) : 1;
    $validita = max(1, (int)($data['validita_giorni'] ?? defaultPackageValidityDays()));

    if ($nome === '' || $numIngressi <= 0 || $prezzo < 0) {
        respond(400, ['success' => false, 'message' => 'Dati pacchetto non validi']);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO pacchetti (nome, descrizione, num_ingressi, prezzo, validita_giorni, attivo, ordine)
             VALUES (?, NULLIF(?, ""), ?, ?, ?, ?, 1)'
        );
        $stmt->execute([$nome, $descrizione, $numIngressi, $prezzo, $validita, $visible]);
        $legacyId = (int)$pdo->lastInsertId();

        if (packagesTableAvailable()) {
            $stmt = $pdo->prepare(
                'INSERT INTO packages (name, description, entries_count, price, visible, legacy_pacchetto_id)
                 VALUES (?, NULLIF(?, ""), ?, ?, ?, ?)'
            );
            $stmt->execute([$nome, $descrizione, $numIngressi, $prezzo, $visible, $legacyId]);
            $packageId = (int)$pdo->lastInsertId();
        } else {
            $packageId = $legacyId;
        }

        $pdo->commit();

        logActivity(
            (string)$staff['user_id'],
            'crea_pacchetto',
            'Creato pacchetto: ' . $nome,
            packagesTableAvailable() ? 'packages' : 'pacchetti',
            (string)$packageId
        );

        $created = fetchManagedPackageById($packageId);

        respond(201, [
            'success' => true,
            'message' => 'Pacchetto creato',
            'pacchetto' => $created,
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('createPackage error: ' . $e->getMessage());
        respond(500, ['success' => false, 'message' => 'Errore creazione pacchetto']);
    }
}

function updatePackage(): void
{
    global $pdo;

    $staff = requireRole(3);
    $packageId = (int)($_GET['id'] ?? 0);
    if ($packageId <= 0) {
        respond(400, ['success' => false, 'message' => 'ID pacchetto non valido']);
    }

    $managed = fetchManagedPackageById($packageId);
    if (!$managed) {
        respond(404, ['success' => false, 'message' => 'Pacchetto non trovato']);
    }

    $data = getJsonInput();
    $nome = array_key_exists('nome', $data) ? sanitizeText((string)$data['nome'], 100) : (string)$managed['nome'];
    $descrizione = array_key_exists('descrizione', $data) ? sanitizeText((string)$data['descrizione'], 1000) : (string)$managed['descrizione'];
    $numIngressi = array_key_exists('num_ingressi', $data) ? (int)$data['num_ingressi'] : (int)$managed['num_ingressi'];
    $prezzo = array_key_exists('prezzo', $data) ? (float)$data['prezzo'] : (float)$managed['prezzo'];
    $visible = array_key_exists('attivo', $data) ? ((bool)$data['attivo'] ? 1 : 0) : (int)$managed['attivo'];
    $validita = array_key_exists('validita_giorni', $data)
        ? max(1, (int)$data['validita_giorni'])
        : max(1, (int)$managed['validita_giorni']);

    if ($nome === '' || $numIngressi <= 0 || $prezzo < 0) {
        respond(400, ['success' => false, 'message' => 'Dati pacchetto non validi']);
    }

    try {
        $pdo->beginTransaction();

        if (packagesTableAvailable()) {
            $stmt = $pdo->prepare(
                'UPDATE packages
                 SET name = ?, description = NULLIF(?, ""), entries_count = ?, price = ?, visible = ?
                 WHERE id = ?'
            );
            $stmt->execute([$nome, $descrizione, $numIngressi, $prezzo, $visible, $packageId]);
        }

        $legacy = ensureLegacyPackage($managed);
        $stmt = $pdo->prepare(
            'UPDATE pacchetti
             SET nome = ?, descrizione = NULLIF(?, ""), num_ingressi = ?, prezzo = ?, validita_giorni = ?, attivo = ?
             WHERE id = ?'
        );
        $stmt->execute([$nome, $descrizione, $numIngressi, $prezzo, $validita, $visible, $legacy['id']]);

        $pdo->commit();

        logActivity((string)$staff['user_id'], 'modifica_pacchetto', 'Modifica pacchetto ID ' . $packageId, 'packages', (string)$packageId);
        respond(200, ['success' => true, 'message' => 'Pacchetto aggiornato', 'pacchetto' => fetchManagedPackageById($packageId)]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('updatePackage error: ' . $e->getMessage());
        respond(500, ['success' => false, 'message' => 'Errore aggiornamento pacchetto']);
    }
}

function togglePackageVisibility(): void
{
    global $pdo;

    $staff = requireRole(3);
    $packageId = (int)($_GET['id'] ?? 0);
    if ($packageId <= 0) {
        respond(400, ['success' => false, 'message' => 'ID pacchetto non valido']);
    }

    $managed = fetchManagedPackageById($packageId);
    if (!$managed) {
        respond(404, ['success' => false, 'message' => 'Pacchetto non trovato']);
    }

    $newVisible = (int)$managed['attivo'] === 1 ? 0 : 1;

    try {
        $pdo->beginTransaction();

        if (packagesTableAvailable()) {
            $stmt = $pdo->prepare('UPDATE packages SET visible = ? WHERE id = ?');
            $stmt->execute([$newVisible, $packageId]);
        }

        $legacy = ensureLegacyPackage($managed);
        $stmt = $pdo->prepare('UPDATE pacchetti SET attivo = ? WHERE id = ?');
        $stmt->execute([$newVisible, $legacy['id']]);

        $pdo->commit();

        logActivity((string)$staff['user_id'], 'toggle_visibilita_pacchetto', 'Toggle visibilita pacchetto ID ' . $packageId, 'packages', (string)$packageId);
        respond(200, [
            'success' => true,
            'message' => $newVisible === 1 ? 'Pacchetto reso visibile' : 'Pacchetto nascosto',
            'pacchetto' => fetchManagedPackageById($packageId),
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('togglePackageVisibility error: ' . $e->getMessage());
        respond(500, ['success' => false, 'message' => 'Errore aggiornamento visibilita pacchetto']);
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

    $allowedMethods = ['contanti'];
    if (!in_array($metodoPagamento, $allowedMethods, true)) {
        respond(400, ['success' => false, 'message' => 'Canale finalizzazione non supportato']);
    }

    if (!in_array($statoPagamento, ['pending', 'confirmed', 'cancelled'], true)) {
        respond(400, ['success' => false, 'message' => 'Stato pratica non valido']);
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

            $managed = fetchManagedPackageById($packageId);
            if (!$managed) {
                $pdo->rollBack();
                respond(404, ['success' => false, 'message' => 'Pacchetto non trovato']);
            }

            $legacy = ensureLegacyPackage($managed);
            $pacchetto = $legacy;
            $pacchetto['nome'] = $managed['nome'];
            $pacchetto['descrizione'] = $managed['descrizione'];
            $pacchetto['num_ingressi'] = $managed['num_ingressi'];
            $pacchetto['prezzo'] = $managed['prezzo'];
            $pacchetto['validita_giorni'] = $managed['validita_giorni'];
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
        $qrCode = resolveUserStaticQrToken($userId);
        $dataScadenza = $isConfirmed
            ? date('Y-m-d', strtotime('+' . (int)$pacchetto['validita_giorni'] . ' days'))
            : null;
        $importoPagato = $importoManuale !== null ? $importoManuale : (float)$pacchetto['prezzo'];
        $ingressiRimanenti = (int)$pacchetto['num_ingressi'];

        if (acquistiHasIngressiTotaliColumn()) {
            $stmt = $pdo->prepare(
                'INSERT INTO acquisti
                 (id, user_id, pacchetto_id, metodo_pagamento, stato_pagamento, riferimento_pagamento, note_pagamento, qr_code, ingressi_rimanenti, ingressi_totali, data_scadenza, confermato_da, data_conferma, importo_pagato)
                 VALUES (?, ?, ?, ?, ?, NULLIF(?, ""), NULLIF(?, ""), ?, ?, ?, ?, ?, ?, ?)'
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
                $ingressiRimanenti,
                $dataScadenza,
                $isConfirmed ? $staff['user_id'] : null,
                $isConfirmed ? date('Y-m-d H:i:s') : null,
                $importoPagato,
            ]);
        } else {
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
        }

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
                ? 'Pacchetto assegnato e confermato - Nuoto libero Le Naiadi'
                : 'Pacchetto assegnato in attesa - Nuoto libero Le Naiadi';

            $body = '<p>Ciao <strong>' . htmlspecialchars((string)$userRow['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
                . '<p>ti e stato assegnato un nuovo pacchetto dalla segreteria.</p>'
                . '<p><strong>Pacchetto:</strong> ' . htmlspecialchars((string)$pacchetto['nome'], ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Ingressi:</strong> ' . (int)$pacchetto['num_ingressi'] . '<br>'
                . '<strong>Importo:</strong> EUR ' . number_format((float)$importoPagato, 2, ',', '.') . '<br>'
                . '<strong>Stato:</strong> ' . htmlspecialchars($statoPagamento, ENT_QUOTES, 'UTF-8') . '</p>';

            if ($qrCode !== '') {
                $qrDownloadUrl = buildAbsoluteUrl('api/qr.php?action=download&acquisto_id=' . urlencode($acquistoId));
                $body .= '<p><strong>Codice QR:</strong> <code>' . htmlspecialchars($qrCode, ENT_QUOTES, 'UTF-8') . '</code><br>'
                    . '<strong>Link QR statico:</strong> <a href="' . htmlspecialchars(buildUserQrUrl($qrCode), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(buildUserQrUrl($qrCode), ENT_QUOTES, 'UTF-8') . '</a><br>'
                    . '<a href="' . htmlspecialchars($qrDownloadUrl, ENT_QUOTES, 'UTF-8') . '">Scarica QR in PDF</a></p>';
                if (!$isConfirmed) {
                    $body .= '<p>Il QR personale e gia assegnato ed e sempre lo stesso. L\'accesso resta attivo dopo conferma pratica.</p>';
                }
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
    $metodoPagamento = normalizePaymentMethod((string)($data['metodo_pagamento'] ?? 'contanti'));
    $riferimentoPagamento = sanitizeInput($data['riferimento_pagamento'] ?? '');
    $notePagamento = sanitizeInput($data['note_pagamento'] ?? '');

    // Flusso pubblico: finalizzazione iscrizione in struttura (nessun pagamento online).
    $allowedMethods = ['contanti'];
    if (!in_array($metodoPagamento, $allowedMethods, true)) {
        respond(400, ['success' => false, 'message' => 'Canale finalizzazione non supportato.']);
    }

    if ($pacchettoId <= 0) {
        respond(400, ['success' => false, 'message' => 'Pacchetto non specificato']);
    }

    $managed = fetchManagedPackageById($pacchettoId);
    if (!$managed || (int)$managed['attivo'] !== 1) {
        respond(404, ['success' => false, 'message' => 'Pacchetto non trovato']);
    }
    $pacchetto = ensureLegacyPackage($managed);

    $isImmediate = isImmediatePaymentMethod($metodoPagamento);
    $statoPagamento = $isImmediate ? 'confirmed' : 'pending';

    try {
        $acquistoId = generateUuid();
        $qrCode = resolveUserStaticQrToken((string)$currentUser['user_id']);
        $dataScadenza = $isImmediate
            ? date('Y-m-d', strtotime('+' . (int)$pacchetto['validita_giorni'] . ' days'))
            : null;

        if (acquistiHasIngressiTotaliColumn()) {
            $stmt = $pdo->prepare(
                'INSERT INTO acquisti
                 (id, user_id, pacchetto_id, metodo_pagamento, stato_pagamento, riferimento_pagamento, note_pagamento, qr_code, ingressi_rimanenti, ingressi_totali, data_scadenza, confermato_da, data_conferma, importo_pagato)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $acquistoId,
                $currentUser['user_id'],
                $pacchetto['id'],
                $metodoPagamento,
                $statoPagamento,
                $riferimentoPagamento,
                $notePagamento,
                $qrCode,
                (int)$managed['num_ingressi'],
                (int)$managed['num_ingressi'],
                $dataScadenza,
                $isImmediate ? $currentUser['user_id'] : null,
                $isImmediate ? date('Y-m-d H:i:s') : null,
                (float)$managed['prezzo'],
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO acquisti
                 (id, user_id, pacchetto_id, metodo_pagamento, stato_pagamento, riferimento_pagamento, note_pagamento, qr_code, ingressi_rimanenti, data_scadenza, confermato_da, data_conferma, importo_pagato)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $acquistoId,
                $currentUser['user_id'],
                $pacchetto['id'],
                $metodoPagamento,
                $statoPagamento,
                $riferimentoPagamento,
                $notePagamento,
                $qrCode,
                (int)$managed['num_ingressi'],
                $dataScadenza,
                $isImmediate ? $currentUser['user_id'] : null,
                $isImmediate ? date('Y-m-d H:i:s') : null,
                (float)$managed['prezzo'],
            ]);
        }

        $ingressiTotaliExpr = ingressiTotaliSelectSql('a', 'p');
        $stmt = $pdo->prepare(
            'SELECT a.*, p.nome AS pacchetto_nome, p.descrizione AS pacchetto_descrizione, p.num_ingressi, p.validita_giorni,
                    ' . $ingressiTotaliExpr . ' AS ingressi_totali
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
                    . '<strong>Importo:</strong> EUR ' . number_format((float)$managed['prezzo'], 2, ',', '.') . '<br>'
                    . '<strong>Ingressi inclusi:</strong> ' . (int)$managed['num_ingressi'] . '<br>'
                    . '<strong>Canale finalizzazione:</strong> ' . htmlspecialchars($metodoLabel, ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Codice QR:</strong> <code>' . htmlspecialchars((string)$qrCode, ENT_QUOTES, 'UTF-8') . '</code><br>'
                    . '<strong>Link QR statico:</strong> <a href="' . htmlspecialchars(buildUserQrUrl((string)$qrCode), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(buildUserQrUrl((string)$qrCode), ENT_QUOTES, 'UTF-8') . '</a><br>'
                    . '<strong>Scadenza:</strong> ' . htmlspecialchars((string)$dataScadenza, ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<p><a href="' . htmlspecialchars($qrDownloadUrl, ENT_QUOTES, 'UTF-8') . '">Scarica QR in PDF</a></p>';

                $mailSent = sendBrandedEmail(
                    (string)$user['email'],
                    $fullName,
                    'Pratica confermata e QR disponibile - Nuoto libero Le Naiadi',
                    'QR code disponibile',
                    $body,
                    'Pratica confermata'
                );
            } else {
                $body = '<p>Ciao <strong>' . htmlspecialchars((string)$user['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
                    . '<p>abbiamo ricevuto la tua richiesta di attivazione pacchetto ingressi.</p>'
                    . '<p><strong>Pacchetto:</strong> ' . htmlspecialchars((string)$pacchetto['nome'], ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Importo:</strong> EUR ' . number_format((float)$managed['prezzo'], 2, ',', '.') . '<br>'
                    . '<strong>Canale finalizzazione:</strong> ' . htmlspecialchars($metodoLabel, ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Riferimento pratica:</strong> <code>' . htmlspecialchars((string)$acquistoId, ENT_QUOTES, 'UTF-8') . '</code><br>'
                    . '<strong>Codice QR personale (statico):</strong> <code>' . htmlspecialchars((string)$qrCode, ENT_QUOTES, 'UTF-8') . '</code><br>'
                    . '<strong>Link QR statico:</strong> <a href="' . htmlspecialchars(buildUserQrUrl((string)$qrCode), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(buildUserQrUrl((string)$qrCode), ENT_QUOTES, 'UTF-8') . '</a></p>'
                    . '<p>Il pacchetto risulta in <strong>attesa di conferma</strong>. Riceverai un aggiornamento appena la pratica sara completata in segreteria.</p>';

                $mailSent = sendBrandedEmail(
                    (string)$user['email'],
                    $fullName,
                    'Conferma richiesta pacchetto ingressi - Nuoto libero Le Naiadi',
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
                ? 'Pratica confermata. QR code generato con successo.'
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

    $ingressiTotaliExpr = ingressiTotaliSelectSql('a', 'p');
    $stmt = $pdo->prepare(
        'SELECT a.*, p.nome AS pacchetto_nome, p.descrizione AS pacchetto_descrizione, p.validita_giorni, p.num_ingressi,
                ' . $ingressiTotaliExpr . ' AS ingressi_totali
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
                COALESCE(NULLIF(a.qr_code, ""), prof.qr_token) AS qr_code,
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

        $ingressiTotaliExpr = ingressiTotaliSelectSql('a', 'p');
        $stmt = $pdo->prepare(
            'SELECT a.*, p.validita_giorni, p.nome AS pacchetto_nome, p.num_ingressi,
                    ' . $ingressiTotaliExpr . ' AS ingressi_totali
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
        $qrCode = resolveUserStaticQrToken((string)$acquisto['user_id']);
        $dataScadenza = !empty($acquisto['data_scadenza'])
            ? (string)$acquisto['data_scadenza']
            : date('Y-m-d', strtotime('+' . (int)$acquisto['validita_giorni'] . ' days'));

        if ($alreadyConfirmed && $existingQr === $qrCode) {
            $pdo->commit();
            respond(200, [
                'success' => true,
                'message' => 'Pratica gia confermata',
                'qr_code' => $qrCode,
                'mail_sent' => false,
                'already_confirmed' => true,
            ]);
        }

        $shouldNotify = !$alreadyConfirmed || $existingQr !== $qrCode;

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
            'conferma_pratica',
            'Pratica confermata per acquisto ' . $acquistoId,
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
                . '<p>la tua pratica e stata <strong>confermata</strong> e il tuo QR e pronto.</p>'
                . '<p><strong>Pacchetto:</strong> ' . htmlspecialchars((string)$acquisto['pacchetto_nome'], ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Codice QR:</strong> <code>' . htmlspecialchars($qrCode, ENT_QUOTES, 'UTF-8') . '</code><br>'
                . '<strong>Link QR statico:</strong> <a href="' . htmlspecialchars(buildUserQrUrl($qrCode), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(buildUserQrUrl($qrCode), ENT_QUOTES, 'UTF-8') . '</a><br>'
                . '<strong>Ingressi disponibili:</strong> ' . (int)$acquisto['ingressi_rimanenti'] . '<br>'
                . '<strong>Data scadenza:</strong> ' . htmlspecialchars($dataScadenza, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p><a href="' . htmlspecialchars($qrDownloadUrl, ENT_QUOTES, 'UTF-8') . '">Scarica QR in PDF</a></p>';

            $mailSent = sendBrandedEmail(
                (string)$user['email'],
                trim((string)$user['nome'] . ' ' . (string)$user['cognome']),
                'Pratica confermata e QR pronto - Nuoto libero Le Naiadi',
                'Pratica confermata',
                $body,
                'Il tuo QR e pronto'
            );

            if (!$mailSent) {
                logMailEvent('warning', 'Email conferma pratica non inviata', [
                    'acquisto_id' => $acquistoId,
                    'email' => $user['email'],
                ]);
            }
        }

        respond(200, [
            'success' => true,
            'message' => $alreadyConfirmed ? 'Pratica gia confermata, QR statico allineato' : 'Pratica confermata',
            'qr_code' => $qrCode,
            'mail_sent' => $mailSent,
            'already_confirmed' => $alreadyConfirmed,
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('confirmPayment error: ' . $e->getMessage());
        respond(500, ['success' => false, 'message' => 'Errore durante la conferma della pratica']);
    }
}


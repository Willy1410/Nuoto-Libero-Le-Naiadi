<?php
declare(strict_types=1);

/**
 * API amministrazione utenti e storico
 */

require_once __DIR__ . '/config.php';

$staff = requireRole(3);
ensureProfileUpdateRequestsTable();
$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = (string)($_GET['action'] ?? 'users');
$requestPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

if ($method === 'GET' && preg_match('#/admin/users/([a-zA-Z0-9\\-]{8,64})$#', $requestPath, $matches)) {
    $_GET['id'] = $matches[1];
    getUserDetail();
}

if ($method === 'GET' && $action === 'users') {
    listUsers();
} elseif ($method === 'GET' && $action === 'operational-settings') {
    getOperationalSettingsForStaff();
} elseif ($method === 'PATCH' && $action === 'save-operational-settings') {
    saveOperationalSettingsForStaff($staff);
} elseif ($method === 'PATCH' && $action === 'set-site-mode') {
    setSiteModeForStaff($staff);
} elseif ($method === 'GET' && $action === 'profile-update-requests') {
    listProfileUpdateRequests();
} elseif ($method === 'PATCH' && $action === 'review-profile-update-request') {
    reviewProfileUpdateRequest($staff);
} elseif ($method === 'GET' && $action === 'user-detail') {
    getUserDetail();
} elseif ($method === 'POST' && $action === 'send-doc-reminder') {
    sendDocumentReminder($staff);
} elseif ($method === 'POST' && $action === 'send-password-reset') {
    sendPasswordResetRequest($staff);
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

function ensureProfileUpdateRequestsTable(): void
{
    global $pdo;

    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }
    $bootstrapped = true;

    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'profile_update_requests'");
        if ($exists && $exists->fetch()) {
            return;
        }

        $pdo->exec(
            'CREATE TABLE profile_update_requests (
                id CHAR(36) PRIMARY KEY,
                user_id CHAR(36) NOT NULL,
                status ENUM("pending","approved","rejected") NOT NULL DEFAULT "pending",
                requested_changes_json LONGTEXT NOT NULL,
                current_snapshot_json LONGTEXT NULL,
                review_note TEXT NULL,
                reviewed_by CHAR(36) NULL,
                reviewed_at DATETIME NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_profile_update_requests_user
                    FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE,
                CONSTRAINT fk_profile_update_requests_reviewer
                    FOREIGN KEY (reviewed_by) REFERENCES profili(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec('CREATE INDEX idx_profile_update_requests_user ON profile_update_requests(user_id)');
        $pdo->exec('CREATE INDEX idx_profile_update_requests_status ON profile_update_requests(status)');
        $pdo->exec('CREATE INDEX idx_profile_update_requests_created_at ON profile_update_requests(created_at)');
    } catch (Throwable $e) {
        error_log('ensureProfileUpdateRequestsTable(admin) error: ' . $e->getMessage());
    }
}

function normalizeProfileFieldValue(string $field, string $value): string
{
    $clean = trim($value);
    if ($field === 'email') {
        return strtolower($clean);
    }
    if ($field === 'codice_fiscale') {
        return strtoupper($clean);
    }
    return $clean;
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

function getOperationalSettingsForStaff(): void
{
    try {
        $settings = getOperationalSettings();
        sendJson(200, [
            'success' => true,
            'site_mode' => getProjectSiteMode(),
            'scanner' => [
                'enabled' => !empty($settings['scanner_enabled']),
                'h24_mode' => !empty($settings['h24_mode']),
                'windows' => is_array($settings['windows'] ?? null) ? $settings['windows'] : [],
                'summary' => getIngressScheduleSummary(),
            ],
        ]);
    } catch (Throwable $e) {
        error_log('getOperationalSettingsForStaff error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento impostazioni operative']);
    }
}

function saveOperationalSettingsForStaff(array $staff): void
{
    enforceRateLimit('admin-save-operational-settings', 60, 900, getClientIp() . '|' . (string)$staff['user_id']);

    $data = getJsonInput();
    $scanner = is_array($data['scanner'] ?? null) ? $data['scanner'] : [];
    $scannerEnabled = !empty($scanner['enabled']);
    $h24Mode = !empty($scanner['h24_mode']);
    $windows = normalizeScannerWindows(is_array($scanner['windows'] ?? null) ? $scanner['windows'] : []);

    if ($scannerEnabled && !$h24Mode && !$windows) {
        sendJson(400, ['success' => false, 'message' => 'Configura almeno una finestra oraria quando lo scanner e attivo']);
    }

    try {
        $saved = saveOperationalSettings([
            'scanner_enabled' => $scannerEnabled,
            'h24_mode' => $h24Mode,
            'windows' => $windows,
        ], (string)$staff['user_id']);
    } catch (Throwable $e) {
        error_log('saveOperationalSettingsForStaff error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore salvataggio impostazioni scanner']);
    }

    logActivity(
        (string)$staff['user_id'],
        'aggiornamento_impostazioni_scanner',
        'Aggiornate impostazioni scanner QR',
        'impostazioni_operative',
        '1'
    );

    sendJson(200, [
        'success' => true,
        'message' => 'Impostazioni scanner aggiornate',
        'scanner' => [
            'enabled' => !empty($saved['scanner_enabled']),
            'h24_mode' => !empty($saved['h24_mode']),
            'windows' => is_array($saved['windows'] ?? null) ? $saved['windows'] : [],
            'summary' => getIngressScheduleSummary(),
        ],
    ]);
}

function setSiteModeForStaff(array $staff): void
{
    enforceRateLimit('admin-set-site-mode', 30, 900, getClientIp() . '|' . (string)$staff['user_id']);

    $data = getJsonInput();
    $mode = strtolower(sanitizeText((string)($data['mode'] ?? ''), 20));
    $confirm1 = !empty($data['confirm_step_one']);
    $confirm2 = !empty($data['confirm_step_two']);

    if (!in_array($mode, ['landing', 'full'], true)) {
        sendJson(400, ['success' => false, 'message' => 'Modalita sito non valida']);
    }
    if (!$confirm1 || !$confirm2) {
        sendJson(400, ['success' => false, 'message' => 'Conferma doppia obbligatoria per cambiare modalita sito']);
    }

    if (!setProjectEnvValue('SITE_MODE', $mode)) {
        sendJson(500, ['success' => false, 'message' => 'Impossibile aggiornare .env SITE_MODE']);
    }

    logActivity(
        (string)$staff['user_id'],
        'aggiornamento_site_mode',
        'SITE_MODE impostato a ' . $mode,
        'env',
        'SITE_MODE'
    );

    sendJson(200, [
        'success' => true,
        'message' => 'Modalita sito aggiornata a ' . $mode,
        'site_mode' => $mode,
    ]);
}

function listProfileUpdateRequests(): void
{
    global $pdo;

    $status = strtolower(sanitizeText((string)($_GET['status'] ?? ''), 20));
    $limit = max(1, min(200, (int)($_GET['limit'] ?? 100)));

    $where = '';
    $params = [];
    if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $where = 'WHERE r.status = ?';
        $params[] = $status;
    }

    $sql = 'SELECT r.id, r.user_id, r.status, r.requested_changes_json, r.current_snapshot_json, r.review_note,
                   r.created_at, r.reviewed_at,
                   u.nome AS user_nome, u.cognome AS user_cognome, u.email AS user_email,
                   rv.nome AS reviewer_nome, rv.cognome AS reviewer_cognome
            FROM profile_update_requests r
            JOIN profili u ON u.id = r.user_id
            LEFT JOIN profili rv ON rv.id = r.reviewed_by
            ' . $where . '
            ORDER BY FIELD(r.status, "pending", "approved", "rejected"), r.created_at DESC
            LIMIT ' . $limit;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $requests = [];
        foreach ($rows as $row) {
            $requests[] = [
                'id' => (string)$row['id'],
                'user_id' => (string)$row['user_id'],
                'status' => (string)$row['status'],
                'requested_changes' => json_decode((string)$row['requested_changes_json'], true) ?: [],
                'current_snapshot' => json_decode((string)$row['current_snapshot_json'], true) ?: [],
                'review_note' => (string)($row['review_note'] ?? ''),
                'created_at' => $row['created_at'],
                'reviewed_at' => $row['reviewed_at'],
                'user_nome' => (string)($row['user_nome'] ?? ''),
                'user_cognome' => (string)($row['user_cognome'] ?? ''),
                'user_email' => (string)($row['user_email'] ?? ''),
                'reviewer_nome' => (string)($row['reviewer_nome'] ?? ''),
                'reviewer_cognome' => (string)($row['reviewer_cognome'] ?? ''),
            ];
        }

        sendJson(200, ['success' => true, 'requests' => $requests]);
    } catch (Throwable $e) {
        error_log('listProfileUpdateRequests error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento richieste modifica dati']);
    }
}

function reviewProfileUpdateRequest(array $staff): void
{
    global $pdo;

    $requestId = sanitizeText((string)($_GET['id'] ?? ''), 36);
    if ($requestId === '') {
        sendJson(400, ['success' => false, 'message' => 'ID richiesta mancante']);
    }

    $data = getJsonInput();
    $status = strtolower(sanitizeText((string)($data['status'] ?? ''), 20));
    $reviewNote = sanitizeText((string)($data['review_note'] ?? ''), 2000);

    if (!in_array($status, ['approved', 'rejected'], true)) {
        sendJson(400, ['success' => false, 'message' => 'Stato revisione non valido']);
    }

    enforceRateLimit('admin-review-profile-request', 120, 900, getClientIp() . '|' . (string)$staff['user_id']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'SELECT r.*
             FROM profile_update_requests r
             WHERE r.id = ?
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();

        if (!$request) {
            $pdo->rollBack();
            sendJson(404, ['success' => false, 'message' => 'Richiesta non trovata']);
        }

        if ((string)$request['status'] !== 'pending') {
            $pdo->rollBack();
            sendJson(409, ['success' => false, 'message' => 'Richiesta gia revisionata']);
        }

        $targetUserId = (string)$request['user_id'];
        if ($targetUserId === '') {
            $pdo->rollBack();
            sendJson(400, ['success' => false, 'message' => 'Utente richiesta non valido']);
        }

        if ($status === 'approved') {
            $requestedChanges = json_decode((string)$request['requested_changes_json'], true);
            if (!is_array($requestedChanges) || !$requestedChanges) {
                $pdo->rollBack();
                sendJson(400, ['success' => false, 'message' => 'Payload modifica non valido']);
            }

            $profileStmt = $pdo->prepare(
                'SELECT id, email, nome, cognome, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale
                 FROM profili
                 WHERE id = ?
                 LIMIT 1
                 FOR UPDATE'
            );
            $profileStmt->execute([$targetUserId]);
            $profile = $profileStmt->fetch();
            if (!$profile) {
                $pdo->rollBack();
                sendJson(404, ['success' => false, 'message' => 'Utente collegato alla richiesta non trovato']);
            }

            $fieldMap = [
                'email' => 255,
                'nome' => 100,
                'cognome' => 100,
                'telefono' => 30,
                'data_nascita' => 10,
                'indirizzo' => 255,
                'citta' => 100,
                'cap' => 10,
                'codice_fiscale' => 16,
            ];

            $final = [];
            foreach ($fieldMap as $field => $maxLen) {
                $final[$field] = normalizeProfileFieldValue($field, (string)($profile[$field] ?? ''));
            }

            foreach ($requestedChanges as $field => $value) {
                if (!array_key_exists($field, $fieldMap)) {
                    continue;
                }
                $cleanValue = sanitizeText((string)$value, $fieldMap[$field]);
                $final[$field] = normalizeProfileFieldValue($field, $cleanValue);
            }

            if ($final['email'] === '' || $final['nome'] === '' || $final['cognome'] === '') {
                $pdo->rollBack();
                sendJson(400, ['success' => false, 'message' => 'Nome, cognome ed email sono obbligatori']);
            }
            if (!validateEmail($final['email'])) {
                $pdo->rollBack();
                sendJson(400, ['success' => false, 'message' => 'Email non valida']);
            }

            if ($final['data_nascita'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $final['data_nascita'])) {
                $pdo->rollBack();
                sendJson(400, ['success' => false, 'message' => 'Data di nascita non valida']);
            }

            if ($final['codice_fiscale'] !== '' && !validateCodiceFiscale($final['codice_fiscale'])) {
                $pdo->rollBack();
                sendJson(400, ['success' => false, 'message' => 'Codice fiscale non valido']);
            }

            $emailStmt = $pdo->prepare('SELECT id FROM profili WHERE email = ? AND id <> ? LIMIT 1');
            $emailStmt->execute([$final['email'], $targetUserId]);
            if ($emailStmt->fetch()) {
                $pdo->rollBack();
                sendJson(409, ['success' => false, 'message' => 'Email gia in uso']);
            }

            if ($final['codice_fiscale'] !== '') {
                $cfStmt = $pdo->prepare('SELECT id FROM profili WHERE codice_fiscale = ? AND id <> ? LIMIT 1');
                $cfStmt->execute([$final['codice_fiscale'], $targetUserId]);
                if ($cfStmt->fetch()) {
                    $pdo->rollBack();
                    sendJson(409, ['success' => false, 'message' => 'Codice fiscale gia in uso']);
                }
            }

            $updateProfileStmt = $pdo->prepare(
                'UPDATE profili
                 SET email = ?,
                     nome = ?,
                     cognome = ?,
                     telefono = NULLIF(?, ""),
                     data_nascita = NULLIF(?, ""),
                     indirizzo = NULLIF(?, ""),
                     citta = NULLIF(?, ""),
                     cap = NULLIF(?, ""),
                     codice_fiscale = NULLIF(?, "")
                 WHERE id = ?'
            );
            $updateProfileStmt->execute([
                $final['email'],
                $final['nome'],
                $final['cognome'],
                $final['telefono'],
                $final['data_nascita'],
                $final['indirizzo'],
                $final['citta'],
                $final['cap'],
                $final['codice_fiscale'],
                $targetUserId,
            ]);
        }

        $reviewStmt = $pdo->prepare(
            'UPDATE profile_update_requests
             SET status = ?,
                 review_note = NULLIF(?, ""),
                 reviewed_by = ?,
                 reviewed_at = NOW()
             WHERE id = ?'
        );
        $reviewStmt->execute([$status, $reviewNote, $staff['user_id'], $requestId]);

        $pdo->commit();

        $logAction = $status === 'approved' ? 'approvazione_modifica_dati' : 'rifiuto_modifica_dati';
        $logMessage = $status === 'approved'
            ? 'Richiesta modifica dati approvata'
            : 'Richiesta modifica dati rifiutata';

        logActivity((string)$staff['user_id'], $logAction, $logMessage, 'profile_update_requests', $requestId);
        logActivity($targetUserId, $logAction, $logMessage, 'profile_update_requests', $requestId);

        sendJson(200, [
            'success' => true,
            'message' => $status === 'approved'
                ? 'Richiesta approvata e profilo aggiornato'
                : 'Richiesta rifiutata',
            'request_id' => $requestId,
            'status' => $status,
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('reviewProfileUpdateRequest error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore revisione richiesta modifica dati']);
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
                    p.codice_fiscale, p.qr_token, p.attivo, p.email_verificata, p.ultimo_accesso, p.created_at,
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
                    a.ingressi_rimanenti, a.importo_pagato, a.data_scadenza, a.data_conferma,
                    COALESCE(NULLIF(a.qr_code, ""), pu.qr_token) AS qr_code,
                    p.nome AS pacchetto_nome, p.num_ingressi
             FROM acquisti a
             JOIN pacchetti p ON p.id = a.pacchetto_id
             JOIN profili pu ON pu.id = a.user_id
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

        $hasFileMime = false;
        $hasFileSize = false;
        try {
            $colMime = $pdo->query("SHOW COLUMNS FROM documenti_utente LIKE 'file_mime'");
            $hasFileMime = (bool)($colMime && $colMime->fetch());
        } catch (Throwable $e) {
            $hasFileMime = false;
        }
        try {
            $colSize = $pdo->query("SHOW COLUMNS FROM documenti_utente LIKE 'file_size'");
            $hasFileSize = (bool)($colSize && $colSize->fetch());
        } catch (Throwable $e) {
            $hasFileSize = false;
        }

        $fileMimeSelect = $hasFileMime ? 'd.file_mime' : 'NULL AS file_mime';
        $fileSizeSelect = $hasFileSize ? 'd.file_size' : 'NULL AS file_size';

        $stmt = $pdo->prepare(
            'SELECT d.id, d.tipo_documento_id, d.file_name, d.file_url, ' . $fileMimeSelect . ', ' . $fileSizeSelect . ', d.stato,
                    d.note_revisione, d.data_caricamento, d.data_revisione, d.scadenza,
                    t.nome AS tipo_nome, t.obbligatorio
             FROM documenti_utente d
             JOIN tipi_documento t ON t.id = d.tipo_documento_id
             WHERE d.user_id = ?
             ORDER BY d.data_caricamento DESC'
        );
        $stmt->execute([$userId]);
        $documents = $stmt->fetchAll();

        $missingDocs = getMissingRequiredDocuments($userId);

        $pendingDocs = 0;
        $approvedDocs = 0;
        foreach ($documents as $doc) {
            if ((string)$doc['stato'] === 'pending') {
                $pendingDocs++;
            } elseif ((string)$doc['stato'] === 'approved') {
                $approvedDocs++;
            }
        }

        $profileUpdateRequests = [];
        $profileUpdatesPending = 0;
        try {
            $reqStmt = $pdo->prepare(
                'SELECT id, status, requested_changes_json, review_note, created_at, reviewed_at
                 FROM profile_update_requests
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT 20'
            );
            $reqStmt->execute([$userId]);
            foreach ($reqStmt->fetchAll() as $reqRow) {
                if ((string)$reqRow['status'] === 'pending') {
                    $profileUpdatesPending++;
                }
                $profileUpdateRequests[] = [
                    'id' => (string)$reqRow['id'],
                    'status' => (string)$reqRow['status'],
                    'requested_changes' => json_decode((string)$reqRow['requested_changes_json'], true) ?: [],
                    'review_note' => (string)($reqRow['review_note'] ?? ''),
                    'created_at' => $reqRow['created_at'],
                    'reviewed_at' => $reqRow['reviewed_at'],
                ];
            }
        } catch (Throwable $e) {
            $profileUpdateRequests = [];
            $profileUpdatesPending = 0;
        }

        sendJson(200, [
            'success' => true,
            'user' => $user,
            'pacchetti' => $purchases,
            'checkins' => $checkins,
            'pagamenti' => $payments,
            'documenti' => $documents,
            'documenti_mancanti' => $missingDocs,
            'profile_update_requests' => $profileUpdateRequests,
            'summary' => [
                'totale_pacchetti' => count($purchases),
                'totale_checkin' => count($checkins),
                'totale_pagamenti' => count($payments),
                'documenti_pending' => $pendingDocs,
                'documenti_approved' => $approvedDocs,
                'documenti_mancanti' => count($missingDocs),
                'profile_updates_pending' => $profileUpdatesPending,
            ],
        ]);
    } catch (Throwable $e) {
        error_log('getUserDetail error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento dettaglio utente']);
    }
}

function getMissingRequiredDocuments(string $userId): array
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT t.id, t.nome
         FROM tipi_documento t
         WHERE t.obbligatorio = 1
           AND NOT EXISTS (
               SELECT 1
               FROM documenti_utente d
               WHERE d.user_id = ?
                 AND d.tipo_documento_id = t.id
                 AND d.stato = "approved"
           )
         ORDER BY t.ordine ASC, t.nome ASC'
    );
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function sendDocumentReminder(array $staff): void
{
    global $pdo;

    $userId = sanitizeText((string)($_GET['id'] ?? ''), 36);
    if ($userId === '') {
        sendJson(400, ['success' => false, 'message' => 'ID utente mancante']);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT p.id, p.nome, p.cognome, p.email
             FROM profili p
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $target = $stmt->fetch();

        if (!$target) {
            sendJson(404, ['success' => false, 'message' => 'Utente non trovato']);
        }

        $missingDocs = getMissingRequiredDocuments($userId);
        if (!$missingDocs) {
            sendJson(200, [
                'success' => true,
                'message' => 'Nessun documento mancante: promemoria non necessario',
                'mail_sent' => false,
            ]);
        }

        $missingListHtml = '<ul>';
        foreach ($missingDocs as $doc) {
            $missingListHtml .= '<li>' . htmlspecialchars((string)$doc['nome'], ENT_QUOTES, 'UTF-8') . '</li>';
        }
        $missingListHtml .= '</ul>';

        $baseUrl = localAppBaseUrl();
        $loginUrl = htmlspecialchars($baseUrl . '/login.php', ENT_QUOTES, 'UTF-8');
        $moduliUrl = htmlspecialchars($baseUrl . '/moduli.php', ENT_QUOTES, 'UTF-8');

        $body = '<p>Ciao <strong>' . htmlspecialchars((string)$target['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>per completare la tua posizione amministrativa risultano ancora mancanti i seguenti documenti obbligatori:</p>'
            . $missingListHtml
            . '<p>Puoi caricare i documenti accedendo alla tua area riservata.</p>'
            . '<p><a href="' . $loginUrl . '">Accedi all\'area riservata</a><br>'
            . '<a href="' . $moduliUrl . '">Consulta moduli e documenti</a></p>';

        $mailSent = sendBrandedEmail(
            (string)$target['email'],
            trim((string)$target['nome'] . ' ' . (string)$target['cognome']),
            'Promemoria documenti mancanti - Nuoto libero Le Naiadi',
            'Documenti mancanti',
            $body,
            'Promemoria documenti mancanti'
        );

        if (!$mailSent) {
            sendJson(500, [
                'success' => false,
                'message' => 'Invio promemoria non riuscito. Controlla logs/mail.log',
            ]);
        }

        logActivity(
            (string)$staff['user_id'],
            'invio_promemoria_documenti',
            'Promemoria documenti inviato',
            'profili',
            $userId
        );

        sendJson(200, [
            'success' => true,
            'message' => 'Promemoria documenti inviato con successo',
            'mail_sent' => true,
            'missing_count' => count($missingDocs),
        ]);
    } catch (Throwable $e) {
        error_log('sendDocumentReminder error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore invio promemoria documenti']);
    }
}

function createPasswordResetTokenForUser(string $userId, string $ipAddress = '', string $userAgent = ''): string
{
    global $pdo;

    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $tokenId = generateUuid();

    $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
        ->execute([$userId]);

    $stmt = $pdo->prepare(
        'INSERT INTO password_reset_tokens
        (id, user_id, token_hash, expires_at, requested_ip, requested_user_agent)
        VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 60 MINUTE), ?, ?)'
    );
    $stmt->execute([
        $tokenId,
        $userId,
        $tokenHash,
        $ipAddress,
        $userAgent,
    ]);

    return $token;
}

function sendResetEmailToUser(array $target, string $resetToken, string $subject, string $title, string $introText): bool
{
    $fullName = trim((string)$target['nome'] . ' ' . (string)$target['cognome']);
    $resetLink = localAppBaseUrl() . '/reset-password.php?token=' . urlencode($resetToken);

    $body = '<p>Ciao <strong>' . htmlspecialchars((string)$target['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
        . '<p>' . htmlspecialchars($introText, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:10px 16px;background:#00a8e8;color:#fff;text-decoration:none;border-radius:6px;">Imposta password</a></p>'
        . '<p>Il link scade tra 60 minuti ed e utilizzabile una sola volta.</p>';

    return sendTemplateEmail(
        (string)$target['email'],
        $fullName,
        $subject,
        $title,
        $body,
        'Impostazione password account'
    );
}

function sendPasswordResetRequest(array $staff): void
{
    global $pdo;

    enforceRateLimit('admin-send-password-reset', 80, 900, getClientIp() . '|' . (string)$staff['user_id']);

    $userId = sanitizeText((string)($_GET['id'] ?? ''), 36);
    if ($userId === '') {
        sendJson(400, ['success' => false, 'message' => 'ID utente mancante']);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT p.id, p.nome, p.cognome, p.email, p.attivo
             FROM profili p
             WHERE p.id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $target = $stmt->fetch();
        if (!$target || (int)$target['attivo'] !== 1) {
            sendJson(404, ['success' => false, 'message' => 'Utente non trovato o non attivo']);
        }

        $token = createPasswordResetTokenForUser(
            (string)$target['id'],
            (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            (string)($_SERVER['HTTP_USER_AGENT'] ?? '')
        );

        $sent = sendResetEmailToUser(
            $target,
            $token,
            'Richiesta cambio password - Nuoto libero Le Naiadi',
            'Cambio password richiesto',
            'La segreteria ti ha inviato un link per impostare una nuova password.'
        );

        logActivity(
            (string)$staff['user_id'],
            'invio_reset_password',
            'Invio reset password a ' . (string)$target['email'],
            'password_reset_tokens',
            (string)$target['id']
        );

        sendJson(200, [
            'success' => true,
            'message' => $sent
                ? 'Email reset password inviata'
                : 'Token creato ma invio email non riuscito (verifica configurazione SMTP/log)',
            'mail_sent' => $sent,
        ]);
    } catch (Throwable $e) {
        error_log('sendPasswordResetRequest error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore invio reset password']);
    }
}

function createUser(array $staff): void
{
    global $pdo;

    $data = getJsonInput();

    $email = strtolower(sanitizeText((string)($data['email'] ?? ''), 255));
    $nome = sanitizeText((string)($data['nome'] ?? ''), 100);
    $cognome = sanitizeText((string)($data['cognome'] ?? ''), 100);
    $telefono = sanitizeText((string)($data['telefono'] ?? ''), 30);
    $ruolo = sanitizeText((string)($data['ruolo'] ?? 'utente'), 30);

    if ($email === '' || $nome === '' || $cognome === '') {
        sendJson(400, ['success' => false, 'message' => 'Compila i campi obbligatori']);
    }

    if (!validateEmail($email)) {
        sendJson(400, ['success' => false, 'message' => 'Email non valida']);
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
        $tempPassword = bin2hex(random_bytes(12));
        $hash = buildSecurePasswordHash($tempPassword);

        $stmt = $pdo->prepare(
            'INSERT INTO profili (id, ruolo_id, email, password_hash, nome, cognome, telefono, attivo, email_verificata, force_password_change)
             VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ""), 1, 1, 0)'
        );
        $stmt->execute([$userId, $roleRow['id'], $email, $hash, $nome, $cognome, $telefono]);
        $qrToken = getOrCreateUserQrToken($userId);

        $token = createPasswordResetTokenForUser(
            $userId,
            (string)($_SERVER['REMOTE_ADDR'] ?? ''),
            (string)($_SERVER['HTTP_USER_AGENT'] ?? '')
        );

        $mailSent = sendResetEmailToUser(
            [
                'id' => $userId,
                'nome' => $nome,
                'cognome' => $cognome,
                'email' => $email,
            ],
            $token,
            'Attiva il tuo account - Nuoto libero Le Naiadi',
            'Imposta la tua password',
            'Il tuo account e stato creato dalla segreteria. Clicca il link per impostare la password di accesso.'
        );

        logActivity((string)$staff['user_id'], 'crea_utente', 'Creato utente: ' . $email, 'profili', $userId);

        sendJson(201, [
            'success' => true,
            'message' => $mailSent
                ? 'Utente creato e invito password inviato via email'
                : 'Utente creato, ma email invito non inviata (verifica configurazione SMTP/log)',
            'user_id' => $userId,
            'qr_token' => $qrToken,
            'mail_sent' => $mailSent,
        ]);
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
                sendJson(400, ['success' => false, 'message' => 'Password troppo debole (' . passwordPolicyHint() . ')']);
            }
            $fields[] = 'password_hash = ?';
            $params[] = buildSecurePasswordHash($password);
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


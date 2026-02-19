<?php
declare(strict_types=1);

/**
 * Flusso iscrizioni amministrative (pending -> approved/rejected)
 *
 * POST  /api/iscrizioni.php?action=submit      (pubblico)
 * GET   /api/iscrizioni.php?action=list        (admin/ufficio)
 * GET   /api/iscrizioni.php?action=detail&id=  (admin/ufficio)
 * PATCH /api/iscrizioni.php?action=review&id=  (admin/ufficio)
 */

require_once __DIR__ . '/config.php';

$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = (string)($_GET['action'] ?? '');

if ($method === 'POST' && $action === 'submit') {
    submitEnrollment();
} elseif ($method === 'GET' && $action === 'list') {
    listEnrollments();
} elseif ($method === 'GET' && $action === 'detail') {
    getEnrollmentDetail();
} elseif ($method === 'PATCH' && $action === 'review') {
    reviewEnrollment();
} else {
    sendJson(404, ['success' => false, 'message' => 'Endpoint non trovato']);
}

function enrollmentsTableAvailable(): bool
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }

    global $pdo;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'iscrizioni'");
        $available = (bool)$stmt->fetch();
    } catch (Throwable $e) {
        $available = false;
    }

    return $available;
}

function packagesTableAvailableLocal(): bool
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

function ensureEnrollmentsTable(): void
{
    if (!enrollmentsTableAvailable()) {
        sendJson(500, [
            'success' => false,
            'message' => 'Tabella iscrizioni non disponibile. Esegui migrazione MIGRATION_2026_02_18_USER_PACKAGES_ENROLLMENTS.sql',
        ]);
    }
}

function submitEnrollment(): void
{
    global $pdo;

    ensureEnrollmentsTable();
    enforceRateLimit('iscrizioni-submit', 12, 900);

    $data = getJsonInput();

    $nome = sanitizeText((string)($data['nome'] ?? $data['firstName'] ?? ''), 100);
    $cognome = sanitizeText((string)($data['cognome'] ?? $data['lastName'] ?? ''), 100);
    $email = strtolower(sanitizeText((string)($data['email'] ?? ''), 255));
    $telefono = sanitizeText((string)($data['telefono'] ?? $data['phone'] ?? ''), 30);
    $dataNascita = sanitizeText((string)($data['data_nascita'] ?? ''), 10);
    $indirizzo = sanitizeText((string)($data['indirizzo'] ?? ''), 255);
    $citta = sanitizeText((string)($data['citta'] ?? ''), 100);
    $cap = sanitizeText((string)($data['cap'] ?? ''), 10);
    $codiceFiscale = strtoupper(sanitizeText((string)($data['codice_fiscale'] ?? ''), 16));
    $note = sanitizeText((string)($data['note'] ?? ''), 1500);
    $termsAccepted = (bool)($data['terms_accept'] ?? $data['termsAccept'] ?? false);
    $privacyAccepted = (bool)($data['privacy_accept'] ?? $data['privacyAccept'] ?? false);

    if ($nome === '' || $cognome === '' || $email === '' || $telefono === '') {
        sendJson(400, ['success' => false, 'message' => 'Compila nome, cognome, email e telefono']);
    }
    if (!validateEmail($email)) {
        sendJson(400, ['success' => false, 'message' => 'Email non valida']);
    }
    if ($codiceFiscale !== '' && !validateCodiceFiscale($codiceFiscale)) {
        sendJson(400, ['success' => false, 'message' => 'Codice fiscale non valido']);
    }
    if (!$termsAccepted || !$privacyAccepted) {
        sendJson(400, ['success' => false, 'message' => 'Devi accettare termini e privacy']);
    }

    if ($dataNascita !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataNascita)) {
        sendJson(400, ['success' => false, 'message' => 'Data di nascita non valida']);
    }

    try {
        $duplicateStmt = $pdo->prepare(
            'SELECT id
             FROM iscrizioni
             WHERE stato = "pending"
               AND (
                    email = ?
                    OR (NULLIF(?, "") IS NOT NULL AND codice_fiscale = ?)
               )
             LIMIT 1'
        );
        $duplicateStmt->execute([$email, $codiceFiscale, $codiceFiscale]);
        if ($duplicateStmt->fetch()) {
            sendJson(409, [
                'success' => false,
                'message' => 'Esiste gia una richiesta iscrizione in attesa per questa anagrafica',
            ]);
        }

        $requestedPackageId = (int)($data['requested_package_id'] ?? 0);
        if ($requestedPackageId <= 0) {
            $requestedPackageId = resolveDefaultRequestedPackageId();
        } elseif (packagesTableAvailableLocal()) {
            $pkgStmt = $pdo->prepare('SELECT id FROM packages WHERE id = ? LIMIT 1');
            $pkgStmt->execute([$requestedPackageId]);
            if (!$pkgStmt->fetch()) {
                $requestedPackageId = resolveDefaultRequestedPackageId();
            }
        } else {
            $requestedPackageId = null;
        }
        $enrollmentId = generateUuid();

        $stmt = $pdo->prepare(
            'INSERT INTO iscrizioni
            (id, nome, cognome, email, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale, note, requested_package_id, stato)
             VALUES (?, ?, ?, ?, NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), ?, "pending")'
        );
        $stmt->execute([
            $enrollmentId,
            $nome,
            $cognome,
            $email,
            $telefono,
            $dataNascita,
            $indirizzo,
            $citta,
            $cap,
            $codiceFiscale,
            $note,
            $requestedPackageId,
        ]);

        logActivity(null, 'iscrizione_submit', 'Nuova iscrizione ricevuta: ' . $email, 'iscrizioni', $enrollmentId);

        $body = '<p>Ciao <strong>' . htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>abbiamo ricevuto la tua richiesta di iscrizione.</p>'
            . '<p><strong>Stato:</strong> pending<br>'
            . '<strong>Riferimento:</strong> <code>' . htmlspecialchars($enrollmentId, ENT_QUOTES, 'UTF-8') . '</code></p>'
            . '<p>La segreteria verifichera i dati e ti inviera credenziali + QR appena l\'iscrizione verra approvata.</p>';

        $mailSent = sendTemplateEmail(
            $email,
            trim($nome . ' ' . $cognome),
            'Richiesta iscrizione ricevuta - Nuoto libero Le Naiadi',
            'Richiesta iscrizione ricevuta',
            $body,
            'Richiesta iscrizione ricevuta'
        );

        sendJson(201, [
            'success' => true,
            'message' => 'Richiesta inviata. Ti contatteremo dopo la verifica.',
            'iscrizione_id' => $enrollmentId,
            'stato' => 'pending',
            'mail_sent' => $mailSent,
        ]);
    } catch (Throwable $e) {
        error_log('submitEnrollment error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore invio iscrizione']);
    }
}

function listEnrollments(): void
{
    global $pdo;

    ensureEnrollmentsTable();
    requireRole(3);

    $status = sanitizeText((string)($_GET['status'] ?? ''), 20);
    $params = [];

    $sql = 'SELECT i.id, i.nome, i.cognome, i.email, i.telefono, i.codice_fiscale, i.stato,
                   i.note, i.note_revisione, i.submitted_at, i.reviewed_at, i.approvato_user_id,
                   pkg.name AS package_name,
                   rv.nome AS revisore_nome, rv.cognome AS revisore_cognome
            FROM iscrizioni i
            LEFT JOIN packages pkg ON pkg.id = i.requested_package_id
            LEFT JOIN profili rv ON rv.id = i.revisionato_da';

    if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $sql .= ' WHERE i.stato = ?';
        $params[] = $status;
    }

    $sql .= ' ORDER BY FIELD(i.stato, "pending", "approved", "rejected"), i.submitted_at DESC';

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        sendJson(200, ['success' => true, 'iscrizioni' => $stmt->fetchAll()]);
    } catch (Throwable $e) {
        error_log('listEnrollments error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento iscrizioni']);
    }
}

function getEnrollmentDetail(): void
{
    global $pdo;

    ensureEnrollmentsTable();
    requireRole(3);

    $id = sanitizeText((string)($_GET['id'] ?? ''), 36);
    if ($id === '') {
        sendJson(400, ['success' => false, 'message' => 'ID iscrizione mancante']);
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT i.*, pkg.name AS package_name, pkg.entries_count, pkg.price,
                    rv.nome AS revisore_nome, rv.cognome AS revisore_cognome
             FROM iscrizioni i
             LEFT JOIN packages pkg ON pkg.id = i.requested_package_id
             LEFT JOIN profili rv ON rv.id = i.revisionato_da
             WHERE i.id = ?
             LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) {
            sendJson(404, ['success' => false, 'message' => 'Iscrizione non trovata']);
        }

        sendJson(200, ['success' => true, 'iscrizione' => $row]);
    } catch (Throwable $e) {
        error_log('getEnrollmentDetail error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore caricamento dettaglio iscrizione']);
    }
}

function reviewEnrollment(): void
{
    global $pdo;

    ensureEnrollmentsTable();
    $staff = requireRole(3);

    $enrollmentId = sanitizeText((string)($_GET['id'] ?? ''), 36);
    $data = getJsonInput();

    $newStatus = sanitizeText((string)($data['stato'] ?? ''), 20);
    $reviewNote = sanitizeText((string)($data['note_revisione'] ?? ''), 2000);

    if ($enrollmentId === '' || !in_array($newStatus, ['approved', 'rejected'], true)) {
        sendJson(400, ['success' => false, 'message' => 'Dati revisione non validi']);
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT * FROM iscrizioni WHERE id = ? LIMIT 1 FOR UPDATE');
        $stmt->execute([$enrollmentId]);
        $row = $stmt->fetch();

        if (!$row) {
            $pdo->rollBack();
            sendJson(404, ['success' => false, 'message' => 'Iscrizione non trovata']);
        }

        if ((string)$row['stato'] !== 'pending') {
            $pdo->rollBack();
            sendJson(409, ['success' => false, 'message' => 'Iscrizione gia revisionata']);
        }

        if ($newStatus === 'approved') {
            $approval = approveEnrollment($row, $staff, $reviewNote);

            $pdo->commit();
            $mailSent = sendActivationEmailWithAttachments($approval['activation_payload']);

            sendJson(200, [
                'success' => true,
                'message' => 'Iscrizione approvata e account attivato',
                'iscrizione_id' => $enrollmentId,
                'stato' => 'approved',
                'utente_id' => $approval['user_id'],
                'username' => $approval['username'],
                'temporary_password' => $approval['temporary_password'],
                'qr_code' => $approval['qr_code'],
                'mail_sent' => $mailSent,
            ]);
        }

        $stmt = $pdo->prepare(
            'UPDATE iscrizioni
             SET stato = "rejected",
                 note_revisione = NULLIF(?, ""),
                 revisionato_da = ?,
                 reviewed_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([$reviewNote, $staff['user_id'], $enrollmentId]);

        $pdo->commit();

        logActivity((string)$staff['user_id'], 'iscrizione_rejected', 'Iscrizione rifiutata: ' . (string)$row['email'], 'iscrizioni', $enrollmentId);

        $mailBody = '<p>Ciao <strong>' . htmlspecialchars((string)$row['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
            . '<p>la tua richiesta di iscrizione e stata rifiutata.</p>';
        if ($reviewNote !== '') {
            $mailBody .= '<p><strong>Motivo:</strong> ' . htmlspecialchars($reviewNote, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        $mailBody .= '<p>Per supporto puoi contattare la segreteria.</p>';

        $mailSent = sendTemplateEmail(
            (string)$row['email'],
            trim((string)$row['nome'] . ' ' . (string)$row['cognome']),
            'Esito richiesta iscrizione - Nuoto libero Le Naiadi',
            'Richiesta iscrizione rifiutata',
            $mailBody,
            'Esito richiesta iscrizione'
        );

        sendJson(200, [
            'success' => true,
            'message' => 'Iscrizione rifiutata',
            'iscrizione_id' => $enrollmentId,
            'stato' => 'rejected',
            'mail_sent' => $mailSent,
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('reviewEnrollment error: ' . $e->getMessage());
        sendJson(500, ['success' => false, 'message' => 'Errore revisione iscrizione']);
    }
}

function approveEnrollment(array $enrollment, array $staff, string $reviewNote): array
{
    global $pdo;

    $roleStmt = $pdo->query("SELECT id FROM ruoli WHERE nome = 'utente' LIMIT 1");
    $role = $roleStmt->fetch();
    if (!$role) {
        throw new RuntimeException('Ruolo utente non configurato');
    }

    $email = strtolower((string)$enrollment['email']);
    $codiceFiscale = strtoupper((string)($enrollment['codice_fiscale'] ?? ''));

    $existingUserStmt = $pdo->prepare(
        'SELECT p.id, p.email, p.codice_fiscale, r.nome AS ruolo_nome
         FROM profili p
         JOIN ruoli r ON r.id = p.ruolo_id
         WHERE p.email = ?
         LIMIT 1
         FOR UPDATE'
    );
    $existingUserStmt->execute([$email]);
    $existing = $existingUserStmt->fetch();

    $userId = '';
    $temporaryPassword = generateTemporaryPassword();
    $passwordHash = buildSecurePasswordHash($temporaryPassword);

    if ($codiceFiscale !== '') {
        $cfStmt = $pdo->prepare('SELECT id FROM profili WHERE codice_fiscale = ? LIMIT 1 FOR UPDATE');
        $cfStmt->execute([$codiceFiscale]);
        $cfRow = $cfStmt->fetch();
        if ($cfRow && (!$existing || (string)$cfRow['id'] !== (string)$existing['id'])) {
            throw new RuntimeException('Codice fiscale gia presente su altro account');
        }
    }

    if ($existing) {
        if ((string)$existing['ruolo_nome'] !== 'utente') {
            throw new RuntimeException('Email gia associata a ruolo non utente');
        }

        $userId = (string)$existing['id'];
        $updateStmt = $pdo->prepare(
            'UPDATE profili
             SET ruolo_id = ?,
                 password_hash = ?,
                 nome = ?,
                 cognome = ?,
                 telefono = NULLIF(?, ""),
                 data_nascita = NULLIF(?, ""),
                 indirizzo = NULLIF(?, ""),
                 citta = NULLIF(?, ""),
                 cap = NULLIF(?, ""),
                 codice_fiscale = NULLIF(?, ""),
                 attivo = 1,
                 email_verificata = 1,
                 stato_iscrizione = "approved",
                 force_password_change = 1
             WHERE id = ?'
        );
        $updateStmt->execute([
            $role['id'],
            $passwordHash,
            (string)$enrollment['nome'],
            (string)$enrollment['cognome'],
            (string)($enrollment['telefono'] ?? ''),
            (string)($enrollment['data_nascita'] ?? ''),
            (string)($enrollment['indirizzo'] ?? ''),
            (string)($enrollment['citta'] ?? ''),
            (string)($enrollment['cap'] ?? ''),
            $codiceFiscale,
            $userId,
        ]);
    } else {
        $userId = generateUuid();
        $insertStmt = $pdo->prepare(
            'INSERT INTO profili
            (id, ruolo_id, email, password_hash, nome, cognome, telefono, data_nascita, indirizzo, citta, cap, codice_fiscale, attivo, email_verificata, stato_iscrizione, force_password_change)
             VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), NULLIF(?, ""), 1, 1, "approved", 1)'
        );
        $insertStmt->execute([
            $userId,
            $role['id'],
            $email,
            $passwordHash,
            (string)$enrollment['nome'],
            (string)$enrollment['cognome'],
            (string)($enrollment['telefono'] ?? ''),
            (string)($enrollment['data_nascita'] ?? ''),
            (string)($enrollment['indirizzo'] ?? ''),
            (string)($enrollment['citta'] ?? ''),
            (string)($enrollment['cap'] ?? ''),
            $codiceFiscale,
        ]);
    }

    $package = resolveApprovalPackage((int)($enrollment['requested_package_id'] ?? 0));

    $acquistoId = generateUuid();
    $qrCode = getOrCreateUserQrToken($userId);
    $qrUrl = buildUserQrUrl($qrCode);
    $ingressiTotali = (int)$package['entries_count'] + 2; // 10 + 2 omaggio
    $notePagamento = trim('Iscrizione approvata. Include 2 ingressi omaggio validi 60 giorni. ' . $reviewNote);

    $insertPurchase = $pdo->prepare(
        'INSERT INTO acquisti
         (id, user_id, pacchetto_id, metodo_pagamento, stato_pagamento, riferimento_pagamento, note_pagamento, qr_code, ingressi_rimanenti, ingressi_totali, data_scadenza, confermato_da, data_conferma, importo_pagato)
         VALUES (?, ?, ?, "contanti", "confirmed", "ISCRIZIONE-STRUTTURA", NULLIF(?, ""), ?, ?, ?, ?, ?, NOW(), ?)'
    );
    $insertPurchase->execute([
        $acquistoId,
        $userId,
        $package['legacy_pacchetto_id'],
        $notePagamento,
        $qrCode,
        $ingressiTotali,
        $ingressiTotali,
        date('Y-m-d', strtotime('+' . (int)$package['validita_giorni'] . ' days')),
        $staff['user_id'],
        (float)$package['price'],
    ]);

    $updateEnrollment = $pdo->prepare(
        'UPDATE iscrizioni
         SET stato = "approved",
             note_revisione = NULLIF(?, ""),
             approvato_user_id = ?,
             revisionato_da = ?,
             reviewed_at = NOW()
         WHERE id = ?'
    );
    $updateEnrollment->execute([$reviewNote, $userId, $staff['user_id'], $enrollment['id']]);

    logActivity((string)$staff['user_id'], 'iscrizione_approved', 'Iscrizione approvata: ' . $email, 'iscrizioni', (string)$enrollment['id']);

    $activationPayload = [
        'user_id' => $userId,
        'nome' => (string)$enrollment['nome'],
        'cognome' => (string)$enrollment['cognome'],
        'email' => $email,
        'username' => $email,
        'temporary_password' => $temporaryPassword,
        'qr_code' => $qrCode,
        'qr_url' => $qrUrl,
        'acquisto_id' => $acquistoId,
        'package_name' => (string)$package['name'],
        'schedule_summary' => getIngressScheduleSummary(),
    ];

    return [
        'user_id' => $userId,
        'username' => $email,
        'temporary_password' => $temporaryPassword,
        'qr_code' => $qrCode,
        'activation_payload' => $activationPayload,
    ];
}

function resolveDefaultRequestedPackageId(): ?int
{
    global $pdo;

    try {
        if (packagesTableAvailableLocal()) {
            $stmt = $pdo->query('SELECT id FROM packages WHERE visible = 1 ORDER BY id ASC LIMIT 1');
            $row = $stmt->fetch();
            if ($row) {
                return (int)$row['id'];
            }
        }
    } catch (Throwable $e) {
        // fallback legacy below
    }

    return null;
}

function resolveApprovalPackage(int $requestedPackageId): array
{
    global $pdo;

    if (packagesTableAvailableLocal()) {
        if ($requestedPackageId > 0) {
            $stmt = $pdo->prepare(
                'SELECT pkg.id, pkg.name, pkg.description, pkg.entries_count, pkg.price, pkg.visible, pkg.legacy_pacchetto_id,
                        COALESCE(p.validita_giorni, 365) AS validita_giorni
                 FROM packages pkg
                 LEFT JOIN pacchetti p ON p.id = pkg.legacy_pacchetto_id
                 WHERE pkg.id = ?
                 LIMIT 1'
            );
            $stmt->execute([$requestedPackageId]);
            $row = $stmt->fetch();
            if ($row) {
                return ensureApprovalLegacyPackage($row);
            }
        }

        $stmt = $pdo->query(
            'SELECT pkg.id, pkg.name, pkg.description, pkg.entries_count, pkg.price, pkg.visible, pkg.legacy_pacchetto_id,
                    COALESCE(p.validita_giorni, 365) AS validita_giorni
             FROM packages pkg
             LEFT JOIN pacchetti p ON p.id = pkg.legacy_pacchetto_id
             WHERE pkg.visible = 1
             ORDER BY pkg.id ASC
             LIMIT 1'
        );
        $row = $stmt->fetch();
        if ($row) {
            return ensureApprovalLegacyPackage($row);
        }
    }

    $stmt = $pdo->query(
        'SELECT id, nome AS name, descrizione AS description, num_ingressi AS entries_count, prezzo AS price, attivo AS visible, validita_giorni
         FROM pacchetti
         WHERE attivo = 1
         ORDER BY ordine ASC, id ASC
         LIMIT 1'
    );
    $legacy = $stmt->fetch();
    if (!$legacy) {
        throw new RuntimeException('Nessun pacchetto disponibile per approvazione iscrizione');
    }

    return [
        'id' => (int)$legacy['id'],
        'name' => (string)$legacy['name'],
        'description' => (string)($legacy['description'] ?? ''),
        'entries_count' => (int)$legacy['entries_count'],
        'price' => (float)$legacy['price'],
        'visible' => (int)$legacy['visible'],
        'legacy_pacchetto_id' => (int)$legacy['id'],
        'validita_giorni' => (int)$legacy['validita_giorni'],
    ];
}

function ensureApprovalLegacyPackage(array $package): array
{
    global $pdo;

    $legacyId = (int)($package['legacy_pacchetto_id'] ?? 0);
    if ($legacyId > 0) {
        $legacyStmt = $pdo->prepare('SELECT id, validita_giorni FROM pacchetti WHERE id = ? LIMIT 1');
        $legacyStmt->execute([$legacyId]);
        $legacy = $legacyStmt->fetch();
        if ($legacy) {
            $package['legacy_pacchetto_id'] = (int)$legacy['id'];
            $package['validita_giorni'] = (int)$legacy['validita_giorni'];
            return $package;
        }
    }

    $insertLegacy = $pdo->prepare(
        'INSERT INTO pacchetti (nome, descrizione, num_ingressi, prezzo, validita_giorni, attivo, ordine)
         VALUES (?, NULLIF(?, ""), ?, ?, ?, 1, 1)'
    );
    $insertLegacy->execute([
        (string)$package['name'],
        (string)($package['description'] ?? ''),
        (int)$package['entries_count'],
        (float)$package['price'],
        max(1, (int)($package['validita_giorni'] ?? 365)),
    ]);

    $legacyId = (int)$pdo->lastInsertId();

    if (packagesTableAvailableLocal()) {
        $update = $pdo->prepare('UPDATE packages SET legacy_pacchetto_id = ? WHERE id = ?');
        $update->execute([$legacyId, (int)$package['id']]);
    }

    $package['legacy_pacchetto_id'] = $legacyId;
    $package['validita_giorni'] = max(1, (int)($package['validita_giorni'] ?? 365));

    return $package;
}

function generateTemporaryPassword(int $length = 12): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#';
    $max = strlen($alphabet) - 1;
    $out = '';

    for ($i = 0; $i < $length; $i++) {
        $out .= $alphabet[random_int(0, $max)];
    }

    return $out;
}

function sendActivationEmailWithAttachments(array $payload): bool
{
    $qrAttachment = buildQrPdfAttachment($payload);
    $instructionsAttachment = buildInstructionsPdfAttachment($payload);

    $attachments = [];
    if ($qrAttachment !== null) {
        $attachments[] = $qrAttachment;
    }
    if ($instructionsAttachment !== null) {
        $attachments[] = $instructionsAttachment;
    }

    $loginUrl = localAppBaseUrl() . '/login.php';

    $body = '<p>Ciao <strong>' . htmlspecialchars((string)$payload['nome'], ENT_QUOTES, 'UTF-8') . '</strong>,</p>'
        . '<p>la tua iscrizione e stata approvata. Il tuo account e ora attivo.</p>'
        . '<p><strong>Username:</strong> ' . htmlspecialchars((string)$payload['username'], ENT_QUOTES, 'UTF-8') . '<br>'
        . '<strong>Password temporanea:</strong> <code>' . htmlspecialchars((string)$payload['temporary_password'], ENT_QUOTES, 'UTF-8') . '</code><br>'
        . '<strong>QR Code:</strong> <code>' . htmlspecialchars((string)$payload['qr_code'], ENT_QUOTES, 'UTF-8') . '</code><br>'
        . '<strong>Link QR statico:</strong> <a href="' . htmlspecialchars((string)($payload['qr_url'] ?? ''), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string)($payload['qr_url'] ?? ''), ENT_QUOTES, 'UTF-8') . '</a><br>'
        . '<strong>Pacchetto attivato:</strong> ' . htmlspecialchars((string)$payload['package_name'], ENT_QUOTES, 'UTF-8') . ' + 2 ingressi omaggio</p>'
        . '<p>Al primo accesso dovrai cambiare la password.</p>'
        . '<p><a href="' . htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8') . '">Accedi all\'area riservata</a></p>';

    if ($attachments) {
        $body .= '<p>In allegato trovi il QR in PDF e la guida operativa.</p>';
    }

    return sendBrandedEmail(
        (string)$payload['email'],
        trim((string)$payload['nome'] . ' ' . (string)$payload['cognome']),
        'Iscrizione approvata: credenziali e QR - Nuoto libero Le Naiadi',
        'Iscrizione approvata',
        $body,
        'Iscrizione approvata e account attivo',
        '',
        $attachments
    );
}

function buildQrPdfAttachment(array $payload): ?array
{
    if (!class_exists('TCPDF')) {
        return null;
    }

    try {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Nuoto libero Le Naiadi');
        $pdf->SetAuthor('Segreteria');
        $pdf->SetTitle('QR Utente - ' . (string)$payload['qr_code']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'QR Utente Nuoto Libero', 0, 1, 'C');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', '', 11);
        $qrUrl = (string)($payload['qr_url'] ?? buildUserQrUrl((string)$payload['qr_code']));
        $pdf->Cell(0, 7, 'Cliente: ' . (string)$payload['nome'] . ' ' . (string)$payload['cognome'], 0, 1, 'L');
        $pdf->Cell(0, 7, 'Token QR: ' . (string)$payload['qr_code'], 0, 1, 'L');
        $pdf->MultiCell(0, 7, 'Link QR statico: ' . $qrUrl, 0, 'L');
        $pdf->Cell(0, 7, 'Pacchetto: ' . (string)$payload['package_name'] . ' + 2 ingressi omaggio', 0, 1, 'L');

        $style = [
            'border' => 1,
            'padding' => 2,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => [255, 255, 255],
        ];

        $pdf->write2DBarcode($qrUrl, 'QRCODE,H', 65, 90, 80, 80, $style, 'N');

        $pdf->SetY(180);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->MultiCell(0, 6, 'Presenta il QR al bagnino per confermare il check-in nelle fasce consentite.', 0, 'C');

        return [
            'string' => $pdf->Output('', 'S'),
            'name' => 'QR_' . preg_replace('/[^A-Za-z0-9\-]/', '-', (string)$payload['qr_code']) . '.pdf',
            'mime' => 'application/pdf',
        ];
    } catch (Throwable $e) {
        error_log('buildQrPdfAttachment error: ' . $e->getMessage());
        return null;
    }
}

function buildInstructionsPdfAttachment(array $payload): ?array
{
    if (!class_exists('TCPDF')) {
        return null;
    }

    try {
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('Nuoto libero Le Naiadi');
        $pdf->SetAuthor('Segreteria');
        $pdf->SetTitle('Istruzioni Utilizzo Servizio');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 15);
        $pdf->Cell(0, 10, 'Istruzioni Utilizzo Servizio Nuoto Libero', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 11);
        $pdf->MultiCell(0, 7, '1) Accesso al sito\n- URL: ' . localAppBaseUrl() . '\n- Login con username (email) e password temporanea.\n- Al primo accesso cambia subito la password.', 0, 'L');
        $pdf->Ln(2);

        $pdf->MultiCell(0, 7, '2) Uso QR Code\n- Mostra il QR al bagnino all\'ingresso.\n- Il check-in viene registrato automaticamente.\n- Conserva sempre il PDF QR sul telefono.', 0, 'L');
        $pdf->Ln(2);

        $pdf->MultiCell(0, 7, '3) Regole piscina\n- Rispetta indicazioni del personale.\n- Usa corsie e spazi in modo corretto.\n- Presenta documentazione medica valida quando richiesta.', 0, 'L');
        $pdf->Ln(2);

        $pdf->MultiCell(0, 7, '4) Orari ingresso\n- ' . getIngressScheduleSummary() . '\n- Fuori fascia oraria il check-in non e consentito.', 0, 'L');

        return [
            'string' => $pdf->Output('', 'S'),
            'name' => 'Istruzioni_Uso_QR_e_Account.pdf',
            'mime' => 'application/pdf',
        ];
    } catch (Throwable $e) {
        error_log('buildInstructionsPdfAttachment error: ' . $e->getMessage());
        return null;
    }
}


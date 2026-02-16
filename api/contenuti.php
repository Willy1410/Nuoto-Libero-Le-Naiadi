<?php

/**
 * API contenuti CMS
 * GET    /api/contenuti.php?page=index
 * GET    /api/contenuti.php?action=templates
 * GET    /api/contenuti.php?action=list&page=index
 * POST   /api/contenuti.php?action=save
 * DELETE /api/contenuti.php?action=delete&page=index&key=hero_title
 */

require_once __DIR__ . '/config.php';

$templatesPath = __DIR__ . '/../config/cms_map.php';
$cmsTemplates = file_exists($templatesPath) ? require $templatesPath : [];
if (!is_array($cmsTemplates)) {
    $cmsTemplates = [];
}

$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
$action = (string)($_GET['action'] ?? 'public');

if ($method === 'GET' && $action === 'public') {
    getPublicContent($cmsTemplates);
} elseif ($method === 'GET' && $action === 'templates') {
    getTemplates($cmsTemplates);
} elseif ($method === 'GET' && $action === 'list') {
    getList($cmsTemplates);
} elseif ($method === 'POST' && $action === 'save') {
    saveContent($cmsTemplates);
} elseif ($method === 'DELETE' && $action === 'delete') {
    deleteContent($cmsTemplates);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Endpoint non trovato']);
}

function getPublicContent(array $templates): void
{
    global $pdo;

    $page = sanitizeInput($_GET['page'] ?? 'index');
    if (!isset($templates[$page])) {
        echo json_encode(['success' => true, 'page' => $page, 'items' => []]);
        return;
    }

    $stmt = $pdo->prepare(
        'SELECT chiave, valore_testo, valore_html, tipo_campo
         FROM contenuti_sito
         WHERE sezione = ? AND modificabile = 1'
    );
    $stmt->execute([$page]);
    $rows = $stmt->fetchAll();

    $rowByKey = [];
    foreach ($rows as $row) {
        $rowByKey[(string)$row['chiave']] = $row;
    }

    $items = [];
    foreach ($templates[$page] as $template) {
        $key = (string)$template['key'];
        $row = $rowByKey[$key] ?? null;
        if (!$row) {
            continue;
        }

        $field = (string)($template['field'] ?? 'text');
        $value = $field === 'html' ? (string)($row['valore_html'] ?? '') : (string)($row['valore_testo'] ?? '');
        if ($value === '') {
            continue;
        }

        $items[] = [
            'key' => $key,
            'selector' => (string)$template['selector'],
            'field' => $field,
            'attribute' => (string)($template['attribute'] ?? ''),
            'value' => $value,
        ];
    }

    echo json_encode([
        'success' => true,
        'page' => $page,
        'items' => $items,
    ]);
}

function getTemplates(array $templates): void
{
    requireRole(3);

    echo json_encode([
        'success' => true,
        'pages' => array_keys($templates),
        'templates' => $templates,
    ]);
}

function getList(array $templates): void
{
    global $pdo;

    requireRole(3);

    $page = sanitizeInput($_GET['page'] ?? '');
    if ($page === '' || !isset($templates[$page])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Pagina non valida']);
        return;
    }

    $stmt = $pdo->prepare(
        'SELECT chiave, valore_testo, valore_html, tipo_campo, updated_at
         FROM contenuti_sito
         WHERE sezione = ?'
    );
    $stmt->execute([$page]);
    $rows = $stmt->fetchAll();

    $rowByKey = [];
    foreach ($rows as $row) {
        $rowByKey[(string)$row['chiave']] = $row;
    }

    $items = [];
    foreach ($templates[$page] as $template) {
        $key = (string)$template['key'];
        $field = (string)($template['field'] ?? 'text');
        $row = $rowByKey[$key] ?? null;
        $items[] = [
            'key' => $key,
            'label' => (string)$template['label'],
            'selector' => (string)$template['selector'],
            'field' => $field,
            'attribute' => (string)($template['attribute'] ?? ''),
            'value' => $field === 'html'
                ? (string)($row['valore_html'] ?? '')
                : (string)($row['valore_testo'] ?? ''),
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    echo json_encode([
        'success' => true,
        'page' => $page,
        'items' => $items,
    ]);
}

function saveContent(array $templates): void
{
    global $pdo;

    $currentUser = requireRole(3);
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }

    $page = sanitizeInput($data['page'] ?? '');
    $key = sanitizeInput($data['key'] ?? '');
    $value = trim((string)($data['value'] ?? ''));

    if ($page === '' || $key === '' || !isset($templates[$page])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parametro pagina/chiave non valido']);
        return;
    }

    $template = null;
    foreach ($templates[$page] as $item) {
        if ((string)$item['key'] === $key) {
            $template = $item;
            break;
        }
    }

    if (!$template) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Chiave non ammessa per questa pagina']);
        return;
    }

    $field = (string)($template['field'] ?? 'text');
    $valueText = $field === 'html' ? '' : $value;
    $valueHtml = $field === 'html' ? $value : '';

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO contenuti_sito (sezione, chiave, valore_testo, valore_html, tipo_campo, modificabile, ordine)
             VALUES (?, ?, ?, ?, ?, 1, 0)
             ON DUPLICATE KEY UPDATE
                valore_testo = VALUES(valore_testo),
                valore_html = VALUES(valore_html),
                tipo_campo = VALUES(tipo_campo),
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([$page, $key, $valueText, $valueHtml, $field]);

        logActivity(
            (string)$currentUser['user_id'],
            'cms_contenuto_salvato',
            'Contenuto aggiornato: ' . $page . '/' . $key,
            'contenuti_sito',
            $page . ':' . $key
        );

        echo json_encode([
            'success' => true,
            'message' => 'Contenuto salvato correttamente',
        ]);
    } catch (Throwable $e) {
        error_log('saveContent error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore salvataggio contenuto']);
    }
}

function deleteContent(array $templates): void
{
    global $pdo;

    $currentUser = requireRole(3);
    $page = sanitizeInput($_GET['page'] ?? '');
    $key = sanitizeInput($_GET['key'] ?? '');

    if ($page === '' || $key === '' || !isset($templates[$page])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parametro pagina/chiave non valido']);
        return;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM contenuti_sito WHERE sezione = ? AND chiave = ?');
        $stmt->execute([$page, $key]);

        logActivity(
            (string)$currentUser['user_id'],
            'cms_contenuto_eliminato',
            'Contenuto rimosso: ' . $page . '/' . $key,
            'contenuti_sito',
            $page . ':' . $key
        );

        echo json_encode(['success' => true, 'message' => 'Override contenuto eliminato']);
    } catch (Throwable $e) {
        error_log('deleteContent error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Errore eliminazione contenuto']);
    }
}



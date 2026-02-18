<?php
/**
 * API CMS Builder-ready
 *
 * GET    /api/cms.php?action=public-page&slug=home
 * GET    /api/cms.php?action=editor-pages
 * GET    /api/cms.php?action=editor-page&id=1|slug=home
 * POST   /api/cms.php?action=save-page
 * GET    /api/cms.php?action=revisions&page_id=1
 * GET    /api/cms.php?action=media&type=image|file
 * POST   /api/cms.php?action=upload-media&type=image|file
 * DELETE /api/cms.php?action=media&id=15
 * GET    /api/cms.php?action=settings          (admin)
 * POST   /api/cms.php?action=save-setting      (admin)
 * GET    /api/cms.php?action=builder-stub
 * POST   /api/cms.php?action=builder-webhook   (stub)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cms/CmsValidation.php';
require_once __DIR__ . '/cms/CmsStorage.php';
require_once __DIR__ . '/cms/CmsRepository.php';
require_once __DIR__ . '/cms/CmsService.php';
require_once __DIR__ . '/cms/BuilderAdapter.php';

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$action = (string)($_GET['action'] ?? 'public-page');

try {
    $service = new CmsService(new CmsRepository($pdo));
    $builderAdapter = new BuilderAdapter();

    if ($method === 'GET' && $action === 'public-page') {
        $slug = (string)($_GET['slug'] ?? '');
        $page = $service->getPublicPage($slug);
        sendJson(200, ['success' => true, 'page' => $page]);
    }

    if ($method === 'GET' && $action === 'editor-pages') {
        requireCmsEditorRole();
        $pages = $service->getEditorPages();
        sendJson(200, ['success' => true, 'pages' => $pages]);
    }

    if ($method === 'GET' && $action === 'editor-page') {
        requireCmsEditorRole();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $slug = isset($_GET['slug']) ? (string)$_GET['slug'] : null;
        $page = $service->getEditorPage($id, $slug);
        sendJson(200, ['success' => true, 'page' => $page]);
    }

    if ($method === 'POST' && $action === 'save-page') {
        $user = requireCmsEditorRole();
        $data = getJsonInput();
        $saved = $service->savePage($data, (string)$user['user_id']);
        sendJson(200, ['success' => true, 'page' => $saved]);
    }

    if ($method === 'GET' && $action === 'revisions') {
        requireCmsEditorRole();
        $pageId = (int)($_GET['page_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 30);
        $revisions = $service->listRevisions($pageId, $limit);
        sendJson(200, ['success' => true, 'revisions' => $revisions]);
    }

    if ($method === 'GET' && $action === 'media') {
        requireCmsEditorRole();
        $type = (string)($_GET['type'] ?? '');
        $limit = (int)($_GET['limit'] ?? 200);
        $media = $service->listMedia($type, $limit);
        sendJson(200, ['success' => true, 'media' => $media]);
    }

    if ($method === 'POST' && $action === 'upload-media') {
        $user = requireCmsEditorRole();
        $type = (string)($_GET['type'] ?? ($_POST['type'] ?? ''));

        if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
            sendJson(400, ['success' => false, 'message' => 'File mancante']);
        }

        $media = $service->uploadMedia($_FILES['file'], $type, (string)$user['user_id']);
        sendJson(201, ['success' => true, 'media' => $media]);
    }

    if ($method === 'DELETE' && $action === 'media') {
        $user = requireCmsEditorRole();
        $id = (int)($_GET['id'] ?? 0);
        $deleted = $service->deleteMedia($id, (string)$user['user_id']);
        sendJson(200, ['success' => true, 'deleted' => $deleted]);
    }

    if ($method === 'GET' && $action === 'settings') {
        requireCmsAdminRole();
        $settings = $service->getSettings();
        sendJson(200, ['success' => true, 'settings' => $settings]);
    }

    if ($method === 'POST' && $action === 'save-setting') {
        $user = requireCmsAdminRole();
        $data = getJsonInput();
        $key = (string)($data['setting_key'] ?? '');
        $value = $data['value'] ?? '';
        $saved = $service->saveSetting($key, $value, (string)$user['user_id']);
        sendJson(200, ['success' => true, 'setting' => $saved]);
    }

    if ($method === 'GET' && $action === 'builder-stub') {
        requireCmsEditorRole();
        $model = sanitizeText((string)($_GET['model'] ?? 'page'), 80);
        $urlPath = sanitizeText((string)($_GET['url_path'] ?? '/'), 255);
        $payload = $builderAdapter->fetchPublishedContent($model, $urlPath);
        sendJson(200, ['success' => true, 'builder' => $payload]);
    }

    if ($method === 'POST' && $action === 'builder-webhook') {
        $secret = trim((string)(getenv('BUILDER_WEBHOOK_SECRET') ?: ''));
        if ($secret !== '') {
            $provided = trim((string)($_SERVER['HTTP_X_BUILDER_SECRET'] ?? ''));
            if (!hash_equals($secret, $provided)) {
                sendJson(403, ['success' => false, 'message' => 'Webhook secret non valido']);
            }
        }

        $payload = getJsonInput();
        $result = $builderAdapter->handleWebhook($payload);
        sendJson(200, ['success' => true, 'webhook' => $result]);
    }

    sendJson(404, ['success' => false, 'message' => 'Azione CMS non valida']);
} catch (InvalidArgumentException $e) {
    sendJson(400, ['success' => false, 'message' => $e->getMessage()]);
} catch (RuntimeException $e) {
    sendJson(500, ['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('cms.php error: ' . $e->getMessage());
    sendJson(500, ['success' => false, 'message' => 'Errore CMS interno']);
}

function requireCmsEditorRole(): array
{
    $user = requireRole(3);
    $role = (string)($user['role'] ?? '');
    if (!in_array($role, ['admin', 'ufficio', 'segreteria'], true)) {
        sendJson(403, ['success' => false, 'message' => 'Accesso CMS non consentito']);
    }

    return $user;
}

function requireCmsAdminRole(): array
{
    $user = requireRole(5);
    if ((string)($user['role'] ?? '') !== 'admin') {
        sendJson(403, ['success' => false, 'message' => 'Solo admin']);
    }

    return $user;
}

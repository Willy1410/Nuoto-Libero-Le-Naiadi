<?php
final class CmsService
{
    private const MAX_UPLOAD_BYTES = 12 * 1024 * 1024;

    private CmsRepository $repository;

    public function __construct(CmsRepository $repository)
    {
        $this->repository = $repository;
        $this->repository->assertSchemaReady();
    }

    public function getPublicPage(string $slug): ?array
    {
        $slug = CmsValidation::sanitizeSlug($slug);
        if ($slug === '') {
            throw new InvalidArgumentException('Slug pagina mancante');
        }

        return $this->repository->findPublicPageBySlug($slug);
    }

    public function getEditorPages(): array
    {
        return $this->repository->listEditorPages();
    }

    public function getEditorPage(?int $id, ?string $slug): ?array
    {
        $slug = $slug !== null ? CmsValidation::sanitizeSlug($slug) : null;
        return $this->repository->findEditorPage($id, $slug);
    }

    public function savePage(array $data, string $userId): array
    {
        $payload = CmsValidation::normalizePagePayload($data);
        $saved = $this->repository->savePage($payload, $userId);

        logActivity(
            $userId,
            'cms_page_save',
            'CMS pagina salvata: ' . (string)$saved['slug'] . ' [' . (string)$saved['status'] . ']',
            'cms_pages',
            (string)$saved['id']
        );

        return $saved;
    }

    public function listRevisions(int $pageId, int $limit = 30): array
    {
        if ($pageId <= 0) {
            throw new InvalidArgumentException('page_id non valido');
        }

        return $this->repository->listRevisions($pageId, $limit);
    }

    public function listMedia(string $type = '', int $limit = 200): array
    {
        $normalizedType = '';
        if ($type !== '') {
            $normalizedType = CmsValidation::normalizeMediaType($type);
        }

        return $this->repository->listMedia($normalizedType, $limit);
    }

    public function uploadMedia(array $file, string $type, string $userId): array
    {
        $type = CmsValidation::normalizeMediaType($type);

        if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Errore upload file');
        }

        $tmpPath = (string)($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('Upload non valido');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_UPLOAD_BYTES) {
            throw new RuntimeException('File non valido: massimo 12MB');
        }

        $originalName = sanitizeText((string)($file['name'] ?? 'file'), 255);
        $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));

        $rules = $this->rulesByType($type);
        if (!isset($rules['ext'][$extension])) {
            throw new RuntimeException('Estensione non consentita per tipo ' . $type);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo ? (string)finfo_file($finfo, $tmpPath) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($detectedMime === '' || !in_array($detectedMime, $rules['mime'], true)) {
            throw new RuntimeException('MIME type non consentito');
        }

        $storage = CmsStorage::storeUploadedFile($tmpPath, $type, $extension);
        $sha256 = hash_file('sha256', $storage['absolute_path']) ?: '';

        $media = $this->repository->createMedia([
            'type' => $type,
            'storage_driver' => $storage['storage_driver'],
            'file_path' => $storage['relative_path'],
            'public_url' => $storage['public_url'],
            'original_name' => $originalName,
            'mime' => $detectedMime,
            'size_bytes' => $size,
            'sha256' => $sha256,
            'uploaded_by' => $userId,
        ]);

        logActivity(
            $userId,
            'cms_media_upload',
            'Upload media CMS: ' . $originalName,
            'cms_media',
            (string)$media['id']
        );

        return $media;
    }

    public function deleteMedia(int $id, string $userId): array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('ID media non valido');
        }

        $media = $this->repository->findMediaById($id);
        if (!$media) {
            throw new RuntimeException('Media non trovato');
        }

        $relativePath = (string)($media['file_path'] ?? '');
        $absolutePath = PROJECT_ROOT . '/' . ltrim($relativePath, '/');

        $deleted = $this->repository->deleteMediaById($id);
        if (!$deleted) {
            throw new RuntimeException('Impossibile eliminare media');
        }

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        logActivity(
            $userId,
            'cms_media_delete',
            'Media CMS eliminato: ' . (string)$media['original_name'],
            'cms_media',
            (string)$id
        );

        return $media;
    }

    public function getSettings(): array
    {
        return $this->repository->listSettings();
    }

    public function saveSetting(string $key, $value, string $userId): array
    {
        $key = CmsValidation::normalizeSettingKey($key);
        if ($key === '') {
            throw new InvalidArgumentException('Chiave setting non valida');
        }

        $valueJson = is_string($value)
            ? $this->normalizeSettingValueString($value)
            : (string)json_encode($value, JSON_UNESCAPED_UNICODE);

        $saved = $this->repository->upsertSetting($key, $valueJson, $userId);

        logActivity(
            $userId,
            'cms_setting_save',
            'Setting CMS aggiornato: ' . $key,
            'cms_settings',
            (string)$saved['id']
        );

        return $saved;
    }

    private function normalizeSettingValueString(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '""';
        }

        json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $trimmed;
        }

        return (string)json_encode($trimmed, JSON_UNESCAPED_UNICODE);
    }

    private function rulesByType(string $type): array
    {
        if ($type === 'image') {
            return [
                'ext' => [
                    'jpg' => true,
                    'jpeg' => true,
                    'png' => true,
                    'webp' => true,
                    'gif' => true,
                ],
                'mime' => [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'image/gif',
                ],
            ];
        }

        return [
            'ext' => [
                'pdf' => true,
                'doc' => true,
                'docx' => true,
                'zip' => true,
                'txt' => true,
            ],
            'mime' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'text/plain',
            ],
        ];
    }
}

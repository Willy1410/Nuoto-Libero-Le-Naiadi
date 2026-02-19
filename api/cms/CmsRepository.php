<?php
final class CmsRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function assertSchemaReady(): void
    {
        $required = ['cms_pages', 'cms_media', 'cms_revisions', 'cms_settings'];
        foreach ($required as $table) {
            $quotedTable = $this->pdo->quote($table);
            $stmt = $this->pdo->query('SHOW TABLES LIKE ' . $quotedTable);
            if (!$stmt->fetch()) {
                throw new RuntimeException('Schema CMS non pronto. Esegui db/MIGRATION_2026_02_18_CMS_BUILDER_READY.sql');
            }
        }
    }

    public function listEditorPages(): array
    {
        $stmt = $this->pdo->query(
            'SELECT p.id, p.slug, p.title, p.status, p.version_num, p.updated_at, p.published_at,
                    p.updated_by, u.nome AS updated_by_nome, u.cognome AS updated_by_cognome
             FROM cms_pages p
             LEFT JOIN profili u ON u.id = p.updated_by
             ORDER BY p.updated_at DESC'
        );

        return $stmt->fetchAll();
    }

    public function findPublicPageBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, slug, title, status, content_json, updated_at, published_at
             FROM cms_pages
             WHERE slug = ? AND status = "published"
             LIMIT 1'
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findEditorPage(?int $id, ?string $slug): ?array
    {
        if ($id !== null && $id > 0) {
            $stmt = $this->pdo->prepare(
                'SELECT p.*, u.nome AS updated_by_nome, u.cognome AS updated_by_cognome
                 FROM cms_pages p
                 LEFT JOIN profili u ON u.id = p.updated_by
                 WHERE p.id = ?
                 LIMIT 1'
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            return $row ?: null;
        }

        if ($slug !== null && $slug !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT p.*, u.nome AS updated_by_nome, u.cognome AS updated_by_cognome
                 FROM cms_pages p
                 LEFT JOIN profili u ON u.id = p.updated_by
                 WHERE p.slug = ?
                 LIMIT 1'
            );
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            return $row ?: null;
        }

        return null;
    }

    public function savePage(array $payload, string $userId): array
    {
        $this->pdo->beginTransaction();

        try {
            $current = null;
            if (!empty($payload['id'])) {
                $stmt = $this->pdo->prepare('SELECT * FROM cms_pages WHERE id = ? LIMIT 1 FOR UPDATE');
                $stmt->execute([(int)$payload['id']]);
                $current = $stmt->fetch() ?: null;
            }

            if ($current === null) {
                $stmt = $this->pdo->prepare('SELECT * FROM cms_pages WHERE slug = ? LIMIT 1 FOR UPDATE');
                $stmt->execute([(string)$payload['slug']]);
                $current = $stmt->fetch() ?: null;
            }

            if ($current) {
                $this->insertRevision(
                    (int)$current['id'],
                    (int)$current['version_num'],
                    (string)$current['status'],
                    (string)$current['content_json'],
                    $userId
                );

                $newVersion = (int)$current['version_num'] + 1;
                $publishedAt = (string)$payload['status'] === 'published'
                    ? ($current['published_at'] ?: date('Y-m-d H:i:s'))
                    : null;

                $stmt = $this->pdo->prepare(
                    'UPDATE cms_pages
                     SET slug = ?,
                         title = ?,
                         status = ?,
                         content_json = ?,
                         version_num = ?,
                         updated_by = ?,
                         updated_at = NOW(),
                         published_at = ?
                     WHERE id = ?'
                );
                $stmt->execute([
                    $payload['slug'],
                    $payload['title'],
                    $payload['status'],
                    $payload['content_json'],
                    $newVersion,
                    $userId,
                    $publishedAt,
                    (int)$current['id'],
                ]);

                $pageId = (int)$current['id'];
            } else {
                $publishedAt = (string)$payload['status'] === 'published' ? date('Y-m-d H:i:s') : null;

                $stmt = $this->pdo->prepare(
                    'INSERT INTO cms_pages (slug, title, status, content_json, version_num, updated_by, updated_at, published_at)
                     VALUES (?, ?, ?, ?, 1, ?, NOW(), ?)'
                );
                $stmt->execute([
                    $payload['slug'],
                    $payload['title'],
                    $payload['status'],
                    $payload['content_json'],
                    $userId,
                    $publishedAt,
                ]);

                $pageId = (int)$this->pdo->lastInsertId();
            }

            $this->pdo->commit();

            $saved = $this->findEditorPage($pageId, null);
            if (!$saved) {
                throw new RuntimeException('Impossibile rileggere pagina salvata');
            }

            return $saved;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function insertRevision(int $pageId, int $version, string $status, string $contentJson, string $userId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cms_revisions (page_id, version_num, status, content_json, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$pageId, $version, $status, $contentJson, $userId]);
    }

    public function listRevisions(int $pageId, int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));

        $stmt = $this->pdo->prepare(
            'SELECT r.id, r.page_id, r.version_num, r.status, r.created_at, r.created_by,
                    u.nome AS created_by_nome, u.cognome AS created_by_cognome
             FROM cms_revisions r
             LEFT JOIN profili u ON u.id = r.created_by
             WHERE r.page_id = ?
             ORDER BY r.version_num DESC
             LIMIT ' . $limit
        );
        $stmt->execute([$pageId]);

        return $stmt->fetchAll();
    }

    public function listMedia(string $type = '', int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));

        if ($type !== '') {
            $stmt = $this->pdo->prepare(
                'SELECT m.*, u.nome AS uploaded_by_nome, u.cognome AS uploaded_by_cognome
                 FROM cms_media m
                 LEFT JOIN profili u ON u.id = m.uploaded_by
                 WHERE m.type = ?
                 ORDER BY m.uploaded_at DESC
                 LIMIT ' . $limit
            );
            $stmt->execute([$type]);
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->query(
            'SELECT m.*, u.nome AS uploaded_by_nome, u.cognome AS uploaded_by_cognome
             FROM cms_media m
             LEFT JOIN profili u ON u.id = m.uploaded_by
             ORDER BY m.uploaded_at DESC
             LIMIT ' . $limit
        );

        return $stmt->fetchAll();
    }

    public function createMedia(array $media): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cms_media
            (type, storage_driver, file_path, public_url, original_name, mime, size_bytes, sha256, uploaded_by, uploaded_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $media['type'],
            $media['storage_driver'],
            $media['file_path'],
            $media['public_url'],
            $media['original_name'],
            $media['mime'],
            $media['size_bytes'],
            $media['sha256'],
            $media['uploaded_by'],
        ]);

        $mediaId = (int)$this->pdo->lastInsertId();

        $stmt = $this->pdo->prepare(
            'SELECT m.*, u.nome AS uploaded_by_nome, u.cognome AS uploaded_by_cognome
             FROM cms_media m
             LEFT JOIN profili u ON u.id = m.uploaded_by
             WHERE m.id = ?
             LIMIT 1'
        );
        $stmt->execute([$mediaId]);

        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Media creato ma non recuperabile');
        }

        return $row;
    }

    public function findMediaById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cms_media WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function deleteMediaById(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM cms_media WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function listSettings(): array
    {
        $stmt = $this->pdo->query(
            'SELECT s.id, s.setting_key, s.value_json, s.updated_at, s.updated_by,
                    u.nome AS updated_by_nome, u.cognome AS updated_by_cognome
             FROM cms_settings s
             LEFT JOIN profili u ON u.id = s.updated_by
             ORDER BY s.setting_key ASC'
        );

        return $stmt->fetchAll();
    }

    public function upsertSetting(string $key, string $valueJson, string $userId): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO cms_settings (setting_key, value_json, updated_by, updated_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
               value_json = VALUES(value_json),
               updated_by = VALUES(updated_by),
               updated_at = NOW()'
        );
        $stmt->execute([$key, $valueJson, $userId]);

        $stmt = $this->pdo->prepare(
            'SELECT s.id, s.setting_key, s.value_json, s.updated_at, s.updated_by,
                    u.nome AS updated_by_nome, u.cognome AS updated_by_cognome
             FROM cms_settings s
             LEFT JOIN profili u ON u.id = s.updated_by
             WHERE s.setting_key = ?
             LIMIT 1'
        );
        $stmt->execute([$key]);

        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Impossibile recuperare setting salvato');
        }

        return $row;
    }
}

<?php
final class CmsValidation
{
    public static function sanitizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\-]+/', '-', $value) ?? '';
        $value = preg_replace('/-+/', '-', $value) ?? '';
        $value = trim($value, '-');
        return mb_substr($value, 0, 140);
    }

    public static function sanitizeTitle(string $value): string
    {
        return sanitizeText($value, 200);
    }

    public static function normalizeStatus(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['draft', 'published'], true) ? $value : 'draft';
    }

    public static function normalizeContentJson($value): string
    {
        if (is_array($value)) {
            return (string)json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $raw = trim((string)$value);
        if ($raw === '') {
            return '{}';
        }

        json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('content_json non e un JSON valido');
        }

        return $raw;
    }

    public static function normalizePagePayload(array $data): array
    {
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $slug = self::sanitizeSlug((string)($data['slug'] ?? ''));
        $title = self::sanitizeTitle((string)($data['title'] ?? ''));
        $status = self::normalizeStatus((string)($data['status'] ?? 'draft'));
        $contentJson = self::normalizeContentJson($data['content_json'] ?? '{}');

        if ($slug === '' || $title === '') {
            throw new InvalidArgumentException('Slug e titolo sono obbligatori');
        }

        return [
            'id' => $id,
            'slug' => $slug,
            'title' => $title,
            'status' => $status,
            'content_json' => $contentJson,
        ];
    }

    public static function normalizeMediaType(string $value): string
    {
        $value = strtolower(trim($value));
        if (!in_array($value, ['image', 'file'], true)) {
            throw new InvalidArgumentException('Tipo media non valido');
        }

        return $value;
    }

    public static function normalizeSettingKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]/', '_', $value) ?? '';
        return mb_substr($value, 0, 120);
    }
}

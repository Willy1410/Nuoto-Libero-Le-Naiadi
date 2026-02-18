<?php
final class CmsStorage
{
    private const BASE_DIR = PROJECT_ROOT . '/uploads/cms';

    /**
     * Storage adapter locale. In futuro puo essere sostituito con S3/Cloud mantenendo stessa interfaccia.
     */
    public static function storeUploadedFile(string $tmpPath, string $category, string $extension): array
    {
        $category = $category === 'image' ? 'images' : 'files';
        $targetDir = self::BASE_DIR . '/' . $category;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $random = bin2hex(random_bytes(12));
        $filename = date('Ymd_His') . '_' . $random . '.' . $extension;
        $absolutePath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($tmpPath, $absolutePath)) {
            throw new RuntimeException('Errore salvataggio file su storage locale');
        }

        return [
            'storage_driver' => 'local',
            'absolute_path' => $absolutePath,
            'relative_path' => 'uploads/cms/' . $category . '/' . $filename,
            'public_url' => getAppBaseUrl() . '/uploads/cms/' . $category . '/' . rawurlencode($filename),
        ];
    }
}

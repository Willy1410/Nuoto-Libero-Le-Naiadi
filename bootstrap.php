<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';
appLoadEnvFile(__DIR__ . '/.env');

if (!function_exists('appSiteMode')) {
    function appSiteMode(): string
    {
        $mode = strtolower(appEnv('SITE_MODE', 'full'));
        return in_array($mode, ['full', 'landing'], true) ? $mode : 'full';
    }
}

if (!function_exists('appIsLandingMode')) {
    function appIsLandingMode(): bool
    {
        return appSiteMode() === 'landing';
    }
}

if (!function_exists('appBaseUrl')) {
    function appBaseUrl(): string
    {
        $fromEnv = appEnv('APP_BASE_URL', '');
        if ($fromEnv !== '') {
            return rtrim($fromEnv, '/');
        }

        $host = preg_replace('/[^a-zA-Z0-9\.\-:\[\]]/', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')) ?: 'localhost';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = preg_replace('#/[^/]+$#', '', $scriptName);
        $basePath = is_string($basePath) ? $basePath : '';

        return rtrim($scheme . '://' . $host . $basePath, '/');
    }
}

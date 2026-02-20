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

if (!function_exists('appLandingStaffBypassCookieName')) {
    function appLandingStaffBypassCookieName(): string
    {
        return 'nl_staff_access';
    }
}

if (!function_exists('appLandingFullAccessCookieName')) {
    function appLandingFullAccessCookieName(): string
    {
        return 'nl_fullsite_access';
    }
}

if (!function_exists('appLandingFullAccessRoleCookieName')) {
    function appLandingFullAccessRoleCookieName(): string
    {
        return 'nl_fullsite_role';
    }
}

if (!function_exists('appLandingFullAccessAllowedRoles')) {
    function appLandingFullAccessAllowedRoles(): array
    {
        return ['admin', 'ufficio', 'segreteria'];
    }
}

if (!function_exists('appNormalizeLandingRole')) {
    function appNormalizeLandingRole(string $role): string
    {
        $normalized = strtolower(trim($role));
        if ($normalized === 'segreteria') {
            return 'ufficio';
        }

        return $normalized;
    }
}

if (!function_exists('appLandingStaffBypassCookieOptions')) {
    function appLandingStaffBypassCookieOptions(int $expiresAt): array
    {
        return [
            'expires' => $expiresAt,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }
}

if (!function_exists('appGrantLandingStaffBypass')) {
    function appGrantLandingStaffBypass(int $ttlSeconds = 1800): void
    {
        $expiresAt = time() + max(60, $ttlSeconds);
        $name = appLandingStaffBypassCookieName();
        if (!headers_sent()) {
            setcookie($name, '1', appLandingStaffBypassCookieOptions($expiresAt));
        }
        $_COOKIE[$name] = '1';
    }
}

if (!function_exists('appClearLandingStaffBypass')) {
    function appClearLandingStaffBypass(): void
    {
        $name = appLandingStaffBypassCookieName();
        if (!headers_sent()) {
            setcookie($name, '', appLandingStaffBypassCookieOptions(time() - 3600));
        }
        unset($_COOKIE[$name]);
    }
}

if (!function_exists('appGrantLandingFullAccess')) {
    function appGrantLandingFullAccess(string $role, int $ttlSeconds = 1800): void
    {
        $normalizedRole = appNormalizeLandingRole($role);
        if (!in_array($normalizedRole, appLandingFullAccessAllowedRoles(), true)) {
            appClearLandingFullAccess();
            return;
        }

        $expiresAt = time() + max(60, $ttlSeconds);
        $accessName = appLandingFullAccessCookieName();
        $roleName = appLandingFullAccessRoleCookieName();
        if (!headers_sent()) {
            setcookie($accessName, '1', appLandingStaffBypassCookieOptions($expiresAt));
            setcookie($roleName, $normalizedRole, appLandingStaffBypassCookieOptions($expiresAt));
        }

        $_COOKIE[$accessName] = '1';
        $_COOKIE[$roleName] = $normalizedRole;
    }
}

if (!function_exists('appClearLandingFullAccess')) {
    function appClearLandingFullAccess(): void
    {
        $accessName = appLandingFullAccessCookieName();
        $roleName = appLandingFullAccessRoleCookieName();
        if (!headers_sent()) {
            setcookie($accessName, '', appLandingStaffBypassCookieOptions(time() - 3600));
            setcookie($roleName, '', appLandingStaffBypassCookieOptions(time() - 3600));
        }

        unset($_COOKIE[$accessName], $_COOKIE[$roleName]);
    }
}

if (!function_exists('appLandingFullAccessActive')) {
    function appLandingFullAccessActive(): bool
    {
        if (!appIsLandingMode()) {
            return false;
        }

        $hasAccessCookie = (string)($_COOKIE[appLandingFullAccessCookieName()] ?? '') === '1';
        if (!$hasAccessCookie) {
            return false;
        }

        $role = appNormalizeLandingRole((string)($_COOKIE[appLandingFullAccessRoleCookieName()] ?? ''));
        return in_array($role, appLandingFullAccessAllowedRoles(), true);
    }
}

if (!function_exists('appLandingStaffBypassActive')) {
    function appLandingStaffBypassActive(): bool
    {
        if (!appIsLandingMode()) {
            return false;
        }

        $requested = (string)($_GET['staff_access'] ?? '') === '1';
        if ($requested) {
            appGrantLandingStaffBypass();
            return true;
        }

        return (string)($_COOKIE[appLandingStaffBypassCookieName()] ?? '') === '1';
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

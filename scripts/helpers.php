<?php
declare(strict_types=1);

function loadEnvFile(string $envPath): void
{
    static $loaded = [];
    if (isset($loaded[$envPath])) {
        return;
    }

    if (!is_file($envPath) || !is_readable($envPath)) {
        $loaded[$envPath] = true;
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        $loaded[$envPath] = true;
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        if ($key === '') {
            continue;
        }

        $value = trim($parts[1]);
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) !== false) {
            continue;
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    $loaded[$envPath] = true;
}

function envValue(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || $value === null) {
        return $default;
    }

    return trim((string)$value);
}

function runCommand(string $command): int
{
    passthru($command, $exitCode);
    return (int)$exitCode;
}

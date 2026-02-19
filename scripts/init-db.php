<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once __DIR__ . '/helpers.php';

loadEnvFile($root . '/.env');

$dbHost = envValue('DB_HOST', '127.0.0.1');
$dbPort = envValue('DB_PORT', '3306');
$dbName = envValue('DB_NAME', 'nuoto_libero');
$dbUser = envValue('DB_USER', 'root');
$dbPass = envValue('DB_PASS', '');

$sqlPath = $root . '/db/CREATE_DATABASE_FROM_ZERO.sql';
if (!is_file($sqlPath)) {
    fwrite(STDERR, "Errore: SQL bootstrap non trovato ({$sqlPath}).\n");
    exit(1);
}

$sql = file_get_contents($sqlPath);
if (!is_string($sql) || trim($sql) === '') {
    fwrite(STDERR, "Errore: SQL bootstrap vuoto.\n");
    exit(1);
}

$dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbHost, $dbPort);

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec($sql);
    fwrite(STDOUT, "Database inizializzato con successo ({$dbName}).\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "Errore init DB: " . $e->getMessage() . "\n");
    exit(1);
}

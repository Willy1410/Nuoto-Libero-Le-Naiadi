<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once __DIR__ . '/helpers.php';

$envExample = $root . '/.env.example';
$envFile = $root . '/.env';

if (!is_file($envExample)) {
    fwrite(STDERR, "Errore: file .env.example non trovato in root.\n");
    exit(1);
}

if (is_file($envFile)) {
    fwrite(STDOUT, "OK: .env esiste gia, nessuna sovrascrittura.\n");
    exit(0);
}

if (!copy($envExample, $envFile)) {
    fwrite(STDERR, "Errore: impossibile creare .env da .env.example.\n");
    exit(1);
}

fwrite(STDOUT, "Creato .env da .env.example.\n");
exit(0);

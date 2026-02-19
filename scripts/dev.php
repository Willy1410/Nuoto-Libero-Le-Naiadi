<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once __DIR__ . '/helpers.php';

loadEnvFile($root . '/.env');

$host = envValue('DEV_HOST', '127.0.0.1');
$port = envValue('DEV_PORT', '8080');
$address = $host . ':' . $port;

fwrite(STDOUT, "Avvio server PHP su http://{$address}\n");
fwrite(STDOUT, "Docroot: {$root}\n");

$command = '"' . PHP_BINARY . '" -S ' . escapeshellarg($address) . ' -t ' . escapeshellarg($root);
$exit = runCommand($command);
exit($exit);

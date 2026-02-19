<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once __DIR__ . '/helpers.php';

$composerCmd = is_file($root . '/composer.phar')
    ? '"' . PHP_BINARY . '" "' . $root . '/composer.phar" install --no-interaction --prefer-dist'
    : 'composer install --no-interaction --prefer-dist';

fwrite(STDOUT, "Eseguo install dipendenze Composer...\n");
$exit = runCommand($composerCmd);
if ($exit !== 0) {
    fwrite(STDERR, "Install fallita con codice {$exit}.\n");
    exit($exit);
}

fwrite(STDOUT, "Install completata.\n");
exit(0);

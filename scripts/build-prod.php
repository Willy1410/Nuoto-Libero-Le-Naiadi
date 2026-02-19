<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once __DIR__ . '/helpers.php';

$phpFiles = [];
$jsFiles = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    $normalized = str_replace('\\', '/', $path);

    if (str_contains($normalized, '/vendor/') || str_contains($normalized, '/logs/') || str_contains($normalized, '/uploads/')) {
        continue;
    }

    if (str_ends_with($normalized, '.php')) {
        $phpFiles[] = $path;
    }
    if (str_ends_with($normalized, '.js')) {
        $jsFiles[] = $path;
    }
}

sort($phpFiles);
sort($jsFiles);

$errors = 0;

fwrite(STDOUT, "Verifica sintassi PHP...\n");
foreach ($phpFiles as $file) {
    $exit = runCommand('"' . PHP_BINARY . '" -l ' . escapeshellarg($file) . ' >NUL');
    if ($exit !== 0) {
        $errors++;
        fwrite(STDERR, "PHP lint KO: {$file}\n");
    }
}

$nodeAvailable = (runCommand('node -v >NUL 2>&1') === 0);
if ($nodeAvailable) {
    fwrite(STDOUT, "Verifica sintassi JS...\n");
    foreach ($jsFiles as $file) {
        $exit = runCommand('node --check ' . escapeshellarg($file) . ' >NUL');
        if ($exit !== 0) {
            $errors++;
            fwrite(STDERR, "JS check KO: {$file}\n");
        }
    }
} else {
    fwrite(STDOUT, "Node non disponibile: salto verifica JS.\n");
}

if ($errors > 0) {
    fwrite(STDERR, "Build prod check fallito. Errori: {$errors}\n");
    exit(1);
}

fwrite(STDOUT, "Build prod check completato con successo.\n");
exit(0);

<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

if (appIsLandingMode()) {
    header('Location: ../area-riservata.php', true, 302);
    exit;
}

header('Location: dashboard-cms-builder.php?legacy=1', true, 302);
exit;

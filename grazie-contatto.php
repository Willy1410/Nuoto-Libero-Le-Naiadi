<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$isLandingContext = appIsLandingMode() || strtolower((string)($_GET['from'] ?? '')) === 'landing';
$homeHref = $isLandingContext ? 'landing.php' : 'index.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Messaggio contatto inviato correttamente.">
    <meta name="robots" content="noindex,nofollow">
    <title>Grazie - Nuoto libero Le Naiadi</title>
    <link rel="icon" type="image/png" href="https://public.gensparkspace.com/api/files/s/s3WpPfgP">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(14,165,233,.18), transparent 40%),
                        linear-gradient(145deg, #0f172a, #0b6da8);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            color: #0f172a;
        }
        .card {
            width: min(640px, 100%);
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 22px 44px rgba(2, 6, 23, 0.3);
            padding: 34px 26px;
            text-align: center;
        }
        .icon {
            width: 82px;
            height: 82px;
            margin: 0 auto 14px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 34px;
            font-weight: 700;
        }
        h1 {
            margin: 0;
            font-size: 46px;
            color: #0f172a;
            line-height: 1.1;
        }
        .subtitle {
            margin: 8px 0 24px;
            font-size: 18px;
            color: #334155;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border-radius: 999px;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 700;
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            color: #fff;
            letter-spacing: .02em;
        }
    </style>
</head>
<body>
<main class="card" role="main">
    <div class="icon" aria-hidden="true">&#10148;</div>
    <h1>Grazie</h1>
    <p class="subtitle">Entro poche ore verrai ricontattato</p>
    <a class="btn" href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8'); ?>">TORNA ALLA HOME</a>
</main>
</body>
</html>


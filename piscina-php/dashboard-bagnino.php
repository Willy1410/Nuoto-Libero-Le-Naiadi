<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

if (appIsLandingMode() && !appLandingStaffBypassActive()) {
    header('Location: ../area-riservata.php', true, 302);
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il mio profilo - Nuoto libero Le Naiadi</title>
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --bg: #f3f6f9;
            --text: #1f2937;
            --muted: #64748b;
            --white: #ffffff;
            --danger: #dc2626;
            --warning: #d97706;
            --radius: 12px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Segoe UI", Arial, sans-serif; background: var(--bg); color: var(--text); }

        .header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            padding: 18px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .header h1 { font-size: 24px; }
        .header small { opacity: 0.9; }
        .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-danger { background: #ef4444; color: #fff; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-success { background: var(--primary); color: #fff; }
        .btn-warning { background: var(--warning); color: #fff; }

        .container { max-width: 1200px; margin: 20px auto; padding: 0 16px; }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .stat {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
            padding: 16px;
            text-align: center;
        }

        .stat h2 { color: var(--primary-dark); font-size: 30px; margin-bottom: 4px; }
        .stat p { color: var(--muted); font-size: 13px; }

        .card {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
            padding: 16px;
            margin-bottom: 16px;
        }

        .card h3 { margin-bottom: 12px; }

        .scanner-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        #reader {
            min-height: 290px;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            background: #f8fafc;
        }

        .manual-box {
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            padding: 12px;
        }

        input[type="text"] {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .alert {
            display: none;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            position: fixed;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            width: min(920px, calc(100% - 24px));
            z-index: 2200;
            border: 1px solid transparent;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.22);
        }
        .alert.ok { display: block; background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .alert.err { display: block; background: #fee2e2; color: #991b1b; border-color: #fecaca; }

        .user-box {
            margin-top: 12px;
            border: 1px solid #dbe2ea;
            border-radius: 10px;
            background: #f8fafc;
            padding: 12px;
            display: none;
        }

        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 10px 8px; text-align: left; font-size: 13px; }
        th { background: #f8fafc; }

        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-mattina { background: #dcfce7; color: #166534; }
        .badge-pomeriggio { background: #fef3c7; color: #92400e; }

        .actions-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }

        @media (max-width: 900px) {
            .scanner-grid { grid-template-columns: 1fr; }
            .alert {
                top: 8px;
                width: calc(100% - 16px);
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Il mio profilo</h1>
            <small id="userName"></small>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" id="homeBtn" type="button">Home</button>
            <button class="btn btn-danger" id="logoutBtn" type="button">Esci</button>
        </div>
    </div>

    <div class="container">
        <div id="alertBox" class="alert"></div>

        <div class="stats">
            <div class="stat">
                <h2 id="statTotale">0</h2>
                <p>Check-in oggi</p>
            </div>
            <div class="stat">
                <h2 id="statMattina">0</h2>
                <p>Mattina</p>
            </div>
            <div class="stat">
                <h2 id="statPomeriggio">0</h2>
                <p>Pomeriggio</p>
            </div>
        </div>

        <div class="card">
            <h3>Scansione QR</h3>
            <div class="scanner-grid">
                <div>
                    <div id="reader"></div>
                    <div class="actions-row">
                        <button class="btn btn-primary" id="startScannerBtn" onclick="startScanner()">Avvia camera</button>
                        <button class="btn btn-warning" onclick="stopScanner()">Ferma camera</button>
                        <button class="btn btn-success" onclick="triggerImageScan()">Scatta foto QR</button>
                    </div>
                    <input type="file" id="qrImageInput" accept="image/*" capture="environment" style="display:none;">
                </div>

                <div class="manual-box">
                    <p style="margin-bottom:8px; color:#64748b; font-size:13px;">Se la camera non e disponibile, inserisci il codice manualmente.</p>
                    <input type="text" id="qrInput" placeholder="Incolla token QR o URL /q/<token>">
                    <div class="actions-row">
                        <button class="btn btn-primary" onclick="verificaQR()">Verifica QR manuale</button>
                        <button class="btn" onclick="clearCurrent()">Pulisci</button>
                    </div>

                    <div id="userBox" class="user-box"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Check-in registrati oggi</h3>
            <div style="overflow:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Ora</th>
                            <th>Utente</th>
                            <th>Telefono</th>
                            <th>Pacchetto</th>
                            <th>Fascia</th>
                        </tr>
                    </thead>
                    <tbody id="todayBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const API_URL = '../api';
        const HOME_URL = <?= json_encode(appIsLandingMode() ? '../landing.php' : '../index.php', JSON_UNESCAPED_UNICODE); ?>;
        const token = localStorage.getItem('token');
        let user = null;
        try {
            user = JSON.parse(localStorage.getItem('user') || 'null');
        } catch (_) {
            user = null;
        }

        let scanner = null;
        let scannerRunning = false;
        let currentQrData = null;
        let lastScanText = '';
        let lastScanAt = 0;
        let checkinBusy = false;
        let verifyBusy = false;
        let scannerLibraryPromise = null;
        let alertTimer = null;
        let authRedirecting = false;

        if (!token || !user || user.ruolo !== 'bagnino') {
            window.location.href = '../login.php';
            throw new Error('Sessione non valida');
        }

        document.getElementById('userName').textContent = `${user.nome} ${user.cognome}`;

        function canUseLiveCamera() {
            const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
            return window.isSecureContext || isLocalhost;
        }

        function showAlert(message, type = 'ok') {
            const box = document.getElementById('alertBox');
            if (alertTimer) {
                clearTimeout(alertTimer);
                alertTimer = null;
            }
            box.className = `alert ${type === 'error' ? 'err' : 'ok'}`;
            box.textContent = message;
            alertTimer = setTimeout(() => {
                box.className = 'alert';
                box.textContent = '';
            }, type === 'error' ? 9000 : 5000);
        }

        function clearAuthStorage() {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            sessionStorage.clear();
        }

        function isUnauthorizedMessage(message) {
            const normalized = String(message || '').trim().toLowerCase();
            return normalized === 'non autenticato' || normalized.includes('sessione scaduta');
        }

        function forceRelogin(message = 'Sessione scaduta. Accedi di nuovo.') {
            if (authRedirecting) {
                return;
            }

            authRedirecting = true;
            stopScanner().catch(() => {});
            clearAuthStorage();
            showAlert(message, 'error');
            setTimeout(() => {
                window.location.href = '../login.php';
            }, 120);
        }

        function getErrorMessage(error) {
            if (!error) {
                return 'Errore sconosciuto';
            }
            if (typeof error === 'string') {
                return error;
            }
            if (error.message) {
                return error.message;
            }
            try {
                return JSON.stringify(error);
            } catch (_) {
                return String(error);
            }
        }

        function normalizeQrCode(rawValue) {
            const value = String(rawValue || '').trim();
            if (!value) {
                return '';
            }

            if (/^[A-Za-z0-9\-_]{16,128}$/.test(value)) {
                return value;
            }

            const pathMatch = value.match(/\/q\/([A-Za-z0-9\-_]{16,128})/i);
            if (pathMatch && pathMatch[1]) {
                return pathMatch[1];
            }

            try {
                const url = new URL(value);
                const fromPath = (url.pathname.match(/\/q\/([A-Za-z0-9\-_]{16,128})/i) || [])[1] || '';
                if (fromPath) {
                    return fromPath;
                }
                const fromParam = (url.searchParams.get('qr') || '').trim();
                if (fromParam !== '') {
                    return fromParam;
                }
            } catch (_) {
                // non e una URL valida, continua con fallback
            }

            const match = value.match(/((?:PSC|NL)-[A-Z0-9-]+)/i);
            if (match && match[1]) {
                return match[1];
            }

            return '';
        }

        async function fetchJson(url, options = {}) {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    ...(options.headers || {})
                }
            });
            const rawText = (await response.text()).replace(/^\uFEFF/, '').trim();

            let data = {};
            if (rawText !== '') {
                try {
                    data = JSON.parse(rawText);
                } catch (_) {
                    const looksHtml = /^<!doctype html/i.test(rawText) || rawText.startsWith('<html') || rawText.startsWith('<');
                    if (looksHtml) {
                        throw new Error('Risposta API non valida (HTML ricevuto). Verifica percorso API e sessione login.');
                    }
                    throw new Error('Risposta API non valida. Controlla i log backend.');
                }
            }

            if (response.status === 401 || isUnauthorizedMessage(data?.message)) {
                forceRelogin('Sessione scaduta. Accedi di nuovo.');
                throw new Error('Sessione scaduta. Accedi di nuovo.');
            }

            if (!response.ok || !data.success) {
                throw new Error(data.message || `Errore richiesta API (HTTP ${response.status})`);
            }
            return data;
        }

        function loadScript(src) {
            return new Promise((resolve) => {
                const existing = document.querySelector(`script[data-src="${src}"]`);
                if (existing) {
                    if (typeof Html5Qrcode !== 'undefined') {
                        resolve(true);
                        return;
                    }
                    existing.addEventListener('load', () => resolve(true), { once: true });
                    existing.addEventListener('error', () => resolve(false), { once: true });
                    return;
                }

                const script = document.createElement('script');
                script.src = src;
                script.async = true;
                script.setAttribute('data-src', src);
                script.onload = () => resolve(true);
                script.onerror = () => resolve(false);
                document.head.appendChild(script);
            });
        }

        function ensureScannerLibraryLoaded() {
            if (typeof Html5Qrcode !== 'undefined') {
                return Promise.resolve(true);
            }

            if (scannerLibraryPromise) {
                return scannerLibraryPromise;
            }

            scannerLibraryPromise = (async () => {
                const local = await loadScript('../assets/vendor/html5-qrcode/html5-qrcode.min.js');
                if (local && typeof Html5Qrcode !== 'undefined') {
                    return true;
                }

                const primary = await loadScript('https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js');
                if (primary && typeof Html5Qrcode !== 'undefined') {
                    return true;
                }

                const fallback = await loadScript('https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js');
                return fallback && typeof Html5Qrcode !== 'undefined';
            })();

            return scannerLibraryPromise;
        }

        function formatTime(value) {
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return '-';
            return date.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' });
        }

        function clearCurrent() {
            currentQrData = null;
            document.getElementById('qrInput').value = '';
            const box = document.getElementById('userBox');
            box.style.display = 'none';
            box.innerHTML = '';
        }

        async function verificaQR(options = {}) {
            const fromScanner = !!options.fromScanner;
            const stopScannerOnSuccess = !!options.stopScannerOnSuccess;
            const qrCode = normalizeQrCode(document.getElementById('qrInput').value);
            document.getElementById('qrInput').value = qrCode;
            if (!qrCode) {
                showAlert('Inserisci un codice QR valido.', 'error');
                return;
            }

            if (verifyBusy) {
                return;
            }
            verifyBusy = true;

            try {
                const data = await fetchJson(`${API_URL}/checkin.php?qr=${encodeURIComponent(qrCode)}`, { method: 'GET' });
                currentQrData = data;
                renderUserBox(data);

                if (stopScannerOnSuccess && scannerRunning) {
                    await stopScanner();
                }

                showAlert(fromScanner
                    ? 'QR riconosciuto e dati compilati. Conferma il check-in.'
                    : 'QR valido. Puoi confermare il check-in.');
            } catch (error) {
                currentQrData = null;
                document.getElementById('userBox').style.display = 'none';
                showAlert(error.message, 'error');
            } finally {
                verifyBusy = false;
            }
        }

        function renderUserBox(data) {
            const box = document.getElementById('userBox');
            box.style.display = 'block';
            box.innerHTML = `
                <p><strong>Cliente:</strong> ${data.utente.nome} ${data.utente.cognome}</p>
                <p><strong>Telefono:</strong> ${data.utente.telefono || '-'}</p>
                <p><strong>Pacchetto:</strong> ${data.acquisto.pacchetto_nome}</p>
                <p><strong>Codice QR:</strong> ${data.acquisto.qr_code}</p>
                <p><strong>Ingressi rimanenti:</strong> ${data.acquisto.ingressi_rimanenti}</p>
                <p><strong>Scadenza:</strong> ${data.acquisto.data_scadenza || '-'}</p>
                <div class="actions-row">
                    <button class="btn btn-success" id="confirmCheckinBtn" onclick="confermaCheckin()">Conferma check-in</button>
                    <button class="btn" onclick="clearCurrent()">Annulla</button>
                </div>
            `;
            box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        async function confermaCheckin() {
            if (!currentQrData || checkinBusy) {
                return;
            }

            const qrCode = normalizeQrCode(document.getElementById('qrInput').value);
            document.getElementById('qrInput').value = qrCode;
            if (!qrCode) {
                showAlert('Codice QR mancante.', 'error');
                return;
            }

            const button = document.getElementById('confirmCheckinBtn');
            checkinBusy = true;
            if (button) {
                button.disabled = true;
                button.textContent = 'Registrazione...';
            }

            try {
                const data = await fetchJson(`${API_URL}/checkin.php`, {
                    method: 'POST',
                    body: JSON.stringify({ qr_code: qrCode })
                });
                showAlert(`Check-in registrato. Ingressi rimanenti: ${data.ingressi_rimanenti}`);
                clearCurrent();
                await loadToday();
            } catch (error) {
                showAlert(error.message, 'error');
            } finally {
                checkinBusy = false;
                if (button) {
                    button.disabled = false;
                    button.textContent = 'Conferma check-in';
                }
            }
        }

        async function loadToday() {
            try {
                const data = await fetchJson(`${API_URL}/checkin.php?action=today`, { method: 'GET' });
                document.getElementById('statTotale').textContent = String(data.stats.totale || 0);
                document.getElementById('statMattina').textContent = String(data.stats.mattina || 0);
                document.getElementById('statPomeriggio').textContent = String(data.stats.pomeriggio || 0);

                const rows = Array.isArray(data.checkins) ? data.checkins : [];
                const body = document.getElementById('todayBody');
                if (!rows.length) {
                    body.innerHTML = '<tr><td colspan="5">Nessun check-in oggi.</td></tr>';
                    return;
                }

                body.innerHTML = rows.map(row => `
                    <tr>
                        <td>${formatTime(row.timestamp)}</td>
                        <td>${row.user_nome} ${row.user_cognome}</td>
                        <td>${row.user_telefono || '-'}</td>
                        <td>${row.pacchetto_nome}</td>
                        <td><span class="badge ${row.fascia_oraria === 'mattina' ? 'badge-mattina' : 'badge-pomeriggio'}">${row.fascia_oraria}</span></td>
                    </tr>
                `).join('');
            } catch (error) {
                showAlert(error.message, 'error');
            }
        }

        async function startScanner(options = {}) {
            const silent = !!options.silent;
            if (scannerRunning) {
                return;
            }

            const startButton = document.getElementById('startScannerBtn');
            startButton.disabled = true;
            startButton.textContent = 'Caricamento...';

            if (!canUseLiveCamera()) {
                startButton.disabled = false;
                startButton.textContent = 'Avvia camera';
                showAlert('Scanner live bloccato su HTTP mobile. Apri la dashboard bagnino in HTTPS per usare la camera.', 'error');
                return;
            }

            const libraryLoaded = await ensureScannerLibraryLoaded();
            if (!libraryLoaded) {
                startButton.disabled = false;
                startButton.textContent = 'Avvia camera';
                showAlert('Libreria scanner non disponibile. Usa inserimento manuale o foto QR.', 'error');
                return;
            }

            if (!scanner) {
                scanner = new Html5Qrcode('reader');
            }

            const onScan = async (decodedText) => {
                if (verifyBusy || checkinBusy) {
                    return;
                }
                const normalized = normalizeQrCode(decodedText);
                const now = Date.now();
                if (normalized === lastScanText && (now - lastScanAt) < 2500) {
                    return;
                }

                lastScanText = normalized;
                lastScanAt = now;
                document.getElementById('qrInput').value = normalized;
                await verificaQR({ fromScanner: true, stopScannerOnSuccess: true });
            };

            const config = { fps: 8, qrbox: { width: 240, height: 240 }, aspectRatio: 1.2 };

            try {
                await scanner.start({ facingMode: 'environment' }, config, onScan, () => {});
                scannerRunning = true;
                startButton.textContent = 'Camera attiva';
                startButton.disabled = true;
                if (!silent) {
                    showAlert('Camera avviata. Inquadra il QR da leggere.');
                }
                return;
            } catch (_) {
                // fallback su camera disponibile
            }

            try {
                const cameras = await Html5Qrcode.getCameras();
                if (!Array.isArray(cameras) || cameras.length === 0) {
                    throw new Error('Nessuna camera disponibile');
                }

                const preferred = cameras.find((cam) => {
                    const label = (cam.label || '').toLowerCase();
                    return label.includes('back') || label.includes('rear') || label.includes('environment') || label.includes('posteriore');
                }) || cameras[0];

                await scanner.start({ deviceId: { exact: preferred.id } }, config, onScan, () => {});
                scannerRunning = true;
                startButton.textContent = 'Camera attiva';
                startButton.disabled = true;
                if (!silent) {
                    showAlert('Camera avviata. Inquadra il QR da leggere.');
                }
            } catch (error) {
                startButton.disabled = false;
                startButton.textContent = 'Avvia camera';
                showAlert('Impossibile avviare la camera: ' + getErrorMessage(error), 'error');
            }
        }

        async function triggerImageScan() {
            const input = document.getElementById('qrImageInput');
            input.value = '';
            input.click();
        }

        async function stopScanner() {
            if (!scanner || !scannerRunning) {
                return;
            }

            try {
                await scanner.stop();
                await scanner.clear();
            } catch (_) {
            } finally {
                scannerRunning = false;
                scanner = null;
                document.getElementById('reader').innerHTML = '';
                document.getElementById('startScannerBtn').textContent = 'Avvia camera';
                document.getElementById('startScannerBtn').disabled = false;
            }
        }

        async function logout() {
            stopScanner();

            try {
                await fetch(`${API_URL}/auth.php?action=logout`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
            } catch (_) {
            }

            clearAuthStorage();
            window.location.href = '../login.php';
        }

        function goHome() {
            window.location.href = HOME_URL;
        }

        document.getElementById('qrInput').addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                verificaQR();
            }
        });

        document.getElementById('qrImageInput').addEventListener('change', async (event) => {
            const file = event.target.files && event.target.files[0];
            if (!file) {
                return;
            }

            try {
                if (scannerRunning) {
                    await stopScanner();
                }

                const libraryLoaded = await ensureScannerLibraryLoaded();
                if (!libraryLoaded) {
                    throw new Error('Libreria scanner non disponibile');
                }

                if (!scanner) {
                    scanner = new Html5Qrcode('reader');
                }

                const decodedText = await scanner.scanFile(file, true);
                const normalized = normalizeQrCode(decodedText);
                document.getElementById('qrInput').value = normalized;
                await verificaQR();
                showAlert('QR letto dalla foto. Controlla i dati e conferma il check-in.');
            } catch (error) {
                showAlert('Impossibile leggere il QR dalla foto: ' + getErrorMessage(error), 'error');
            } finally {
                event.target.value = '';
            }
        });

        loadToday();
        document.getElementById('homeBtn').addEventListener('click', goHome);
        document.getElementById('logoutBtn').addEventListener('click', () => {
            logout();
        });
        ensureScannerLibraryLoaded().then(async (ok) => {
            if (!ok) {
                showAlert('Scanner camera non disponibile: usa il campo manuale QR.', 'error');
                return;
            }

            if (!canUseLiveCamera()) {
                showAlert('Per scanner live su telefono apri la dashboard in HTTPS. In HTTP puoi usare solo inserimento manuale o foto.', 'error');
                return;
            }

            await startScanner({ silent: true });
        });
        setInterval(loadToday, 30000);

        window.startScanner = startScanner;
        window.stopScanner = stopScanner;
        window.verificaQR = verificaQR;
        window.confermaCheckin = confermaCheckin;
        window.clearCurrent = clearCurrent;
        window.triggerImageScan = triggerImageScan;
        window.logout = logout;
        window.goHome = goHome;
    </script>
</body>
</html>


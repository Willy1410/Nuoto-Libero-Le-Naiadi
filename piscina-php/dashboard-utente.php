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
            --primary: #0284c7;
            --primary-dark: #075985;
            --bg: #f1f5f9;
            --text: #0f172a;
            --muted: #64748b;
            --card: #ffffff;
            --line: #e2e8f0;
            --ok: #15803d;
            --warn: #d97706;
            --danger: #dc2626;
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
            gap: 12px;
            flex-wrap: wrap;
        }
        .header h1 { font-size: 24px; }
        .header small { opacity: 0.95; }
        .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .btn {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-secondary { background: #334155; color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }

        .container { max-width: 1060px; margin: 20px auto; padding: 0 16px; }

        .alert {
            display: none;
            margin-bottom: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
        }
        .alert.ok { display: block; background: #dcfce7; color: #166534; }
        .alert.err { display: block; background: #fee2e2; color: #991b1b; }

        .card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
            padding: 16px;
            margin-bottom: 16px;
        }
        .card h2 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #0f172a;
        }
        .muted { color: var(--muted); font-size: 13px; }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-top: 8px;
        }
        .status-item {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px;
            background: #fff;
        }
        .status-item h3 {
            font-size: 12px;
            margin-bottom: 6px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .02em;
        }
        .status-item p {
            font-size: 14px;
            font-weight: 600;
        }

        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 9px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-warn { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-neutral { background: #e2e8f0; color: #334155; }

        .qr-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 14px;
            align-items: center;
        }
        .qr-box {
            width: 220px;
            height: 220px;
            border: 1px solid var(--line);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
        }
        .qr-box img {
            width: 200px;
            height: 200px;
            object-fit: contain;
        }
        .qr-meta p { margin: 0 0 6px; font-size: 14px; }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .profile-item {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 9px;
            background: #fff;
        }
        .profile-item h3 {
            margin: 0 0 4px;
            font-size: 12px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .02em;
        }
        .profile-item p { font-size: 14px; font-weight: 600; word-break: break-word; }

        .profile-actions {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .requests-list {
            margin-top: 10px;
            display: grid;
            gap: 8px;
        }
        .request-row {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px;
            font-size: 13px;
            background: #fff;
        }

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 14px;
            z-index: 1000;
        }
        .modal.open { display: flex; }
        .modal-panel {
            width: 100%;
            max-width: 700px;
            max-height: 92vh;
            overflow: auto;
            background: #fff;
            border-radius: 12px;
            padding: 16px;
        }
        .modal h3 { margin-bottom: 10px; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .field label {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }
        .field input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 13px;
        }

        .modal-actions {
            margin-top: 12px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            flex-wrap: wrap;
        }

        @media (max-width: 860px) {
            .status-grid { grid-template-columns: 1fr; }
            .qr-layout { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .profile-grid { grid-template-columns: 1fr; }
            .qr-box { width: 100%; max-width: 220px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Il mio profilo</h1>
            <small id="userName">Utente</small>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" id="homeBtn" type="button">Home</button>
            <button class="btn btn-danger" id="logoutBtn" type="button">Esci</button>
        </div>
    </div>

    <div class="container">
        <div id="alertBox" class="alert"></div>

        <section class="card">
            <h2>Stato account</h2>
            <p class="muted">Qui trovi lo stato iscrizione e l'ultima richiesta di modifica dati.</p>
            <div class="status-grid">
                <div class="status-item">
                    <h3>Stato iscrizione</h3>
                    <p id="enrollmentStatus">-</p>
                </div>
                <div class="status-item">
                    <h3>Ultima richiesta modifica</h3>
                    <p id="lastProfileRequestStatus">Nessuna</p>
                </div>
                <div class="status-item">
                    <h3>Data ultima richiesta</h3>
                    <p id="lastProfileRequestDate">-</p>
                </div>
            </div>
        </section>

        <section class="card">
            <h2>Il tuo QR</h2>
            <div id="qrSection" class="muted">Caricamento QR in corso...</div>
        </section>

        <section class="card">
            <h2>Il mio profilo</h2>
            <p class="muted">Per modificare i dati invia una richiesta: sara approvata da ufficio/admin.</p>
            <div id="profileGrid" class="profile-grid"></div>
            <div class="profile-actions">
                <button class="btn btn-primary" id="openRequestModalBtn" type="button">Richiedi modifica dati</button>
            </div>
            <div id="profileRequestsList" class="requests-list"></div>
        </section>
    </div>

    <div id="profileRequestModal" class="modal" aria-hidden="true">
        <div class="modal-panel">
            <h3>Richiesta modifica dati</h3>
            <p class="muted" style="margin-bottom:10px;">Aggiorna solo i campi da modificare e invia la richiesta.</p>
            <form id="profileRequestForm">
                <div class="form-grid">
                    <div class="field">
                        <label for="rqNome">Nome *</label>
                        <input id="rqNome" maxlength="100" required>
                    </div>
                    <div class="field">
                        <label for="rqCognome">Cognome *</label>
                        <input id="rqCognome" maxlength="100" required>
                    </div>
                    <div class="field">
                        <label for="rqEmail">Email *</label>
                        <input id="rqEmail" type="email" maxlength="255" required>
                    </div>
                    <div class="field">
                        <label for="rqTelefono">Telefono</label>
                        <input id="rqTelefono" maxlength="30">
                    </div>
                    <div class="field">
                        <label for="rqDataNascita">Data di nascita</label>
                        <input id="rqDataNascita" type="date">
                    </div>
                    <div class="field">
                        <label for="rqCodiceFiscale">Codice fiscale</label>
                        <input id="rqCodiceFiscale" maxlength="16" style="text-transform:uppercase;">
                    </div>
                    <div class="field">
                        <label for="rqIndirizzo">Indirizzo</label>
                        <input id="rqIndirizzo" maxlength="255">
                    </div>
                    <div class="field">
                        <label for="rqCitta">Citta</label>
                        <input id="rqCitta" maxlength="100">
                    </div>
                    <div class="field">
                        <label for="rqCap">CAP</label>
                        <input id="rqCap" maxlength="10">
                    </div>
                </div>
                <div class="modal-actions">
                    <button class="btn btn-secondary" type="button" id="closeRequestModalBtn">Annulla</button>
                    <button class="btn btn-primary" type="submit" id="sendRequestBtn">Invia richiesta</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_URL = '../api';
        let token = localStorage.getItem('token');
        let user = null;
        let profile = null;
        let latestConfirmedPurchase = null;
        let activeQrImageSrc = '';

        try {
            user = JSON.parse(localStorage.getItem('user') || 'null');
        } catch (_) {
            user = null;
        }

        if (!token || !user) {
            window.location.href = '../login.php';
        }

        function byId(id) {
            return document.getElementById(id);
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function showAlert(message, type = 'ok') {
            const box = byId('alertBox');
            if (!box) return;
            if (!message) {
                box.className = 'alert';
                box.textContent = '';
                return;
            }
            box.className = `alert ${type === 'error' ? 'err' : 'ok'}`;
            box.textContent = message;
            if (type !== 'error') {
                setTimeout(() => {
                    box.className = 'alert';
                    box.textContent = '';
                }, 4500);
            }
        }

        async function apiJson(path, options = {}) {
            const response = await fetch(`${API_URL}/${path}`, {
                ...options,
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    ...(options.headers || {})
                }
            });

            const rawText = (await response.text()).replace(/^\uFEFF/, '');
            let data = {};
            if (rawText.trim() !== '') {
                try {
                    data = JSON.parse(rawText);
                } catch (_) {
                    data = { success: false, message: 'Risposta API non valida' };
                }
            }

            if (!response.ok || data.success === false) {
                throw new Error(data.message || 'Errore richiesta API');
            }
            return data;
        }

        function formatDate(value) {
            if (!value) return '-';
            const d = new Date(value);
            if (Number.isNaN(d.getTime())) return String(value);
            return d.toLocaleDateString('it-IT');
        }

        function formatDateTime(value) {
            if (!value) return '-';
            const d = new Date(value);
            if (Number.isNaN(d.getTime())) return String(value);
            return `${d.toLocaleDateString('it-IT')} ${d.toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' })}`;
        }

        function statusBadge(status) {
            const raw = String(status || '').toLowerCase();
            if (raw === 'approved' || raw === 'confirmed' || raw === 'active') {
                return '<span class="badge badge-ok">approvato</span>';
            }
            if (raw === 'pending') {
                return '<span class="badge badge-warn">pending</span>';
            }
            if (raw === 'rejected' || raw === 'cancelled') {
                return '<span class="badge badge-danger">rifiutato</span>';
            }
            return `<span class="badge badge-neutral">${escapeHtml(raw || '-')}</span>`;
        }

        function setHeaderUser() {
            const fullName = `${user?.nome || ''} ${user?.cognome || ''}`.trim() || (user?.email || 'Utente');
            byId('userName').textContent = fullName;
        }

        function populateProfileForm() {
            if (!profile) return;
            byId('rqNome').value = profile.nome || '';
            byId('rqCognome').value = profile.cognome || '';
            byId('rqEmail').value = profile.email || '';
            byId('rqTelefono').value = profile.telefono || '';
            byId('rqDataNascita').value = String(profile.data_nascita || '').slice(0, 10);
            byId('rqCodiceFiscale').value = String(profile.codice_fiscale || '').toUpperCase();
            byId('rqIndirizzo').value = profile.indirizzo || '';
            byId('rqCitta').value = profile.citta || '';
            byId('rqCap').value = profile.cap || '';
        }

        function renderProfileCards() {
            const grid = byId('profileGrid');
            if (!grid || !profile) return;

            const items = [
                ['Nome', profile.nome || '-'],
                ['Cognome', profile.cognome || '-'],
                ['Email', profile.email || '-'],
                ['Telefono', profile.telefono || '-'],
                ['Data di nascita', profile.data_nascita ? formatDate(profile.data_nascita) : '-'],
                ['Codice fiscale', profile.codice_fiscale || '-'],
                ['Indirizzo', profile.indirizzo || '-'],
                ['Citta', profile.citta || '-'],
                ['CAP', profile.cap || '-'],
            ];

            grid.innerHTML = items.map(([label, value]) => `
                <div class="profile-item">
                    <h3>${escapeHtml(label)}</h3>
                    <p>${escapeHtml(value)}</p>
                </div>
            `).join('');
        }

        function renderQrSection() {
            const section = byId('qrSection');
            if (!section) return;

            const qrToken = String(profile?.qr_token || latestConfirmedPurchase?.qr_code || '').trim();
            if (!latestConfirmedPurchase || !latestConfirmedPurchase.id || !qrToken) {
                activeQrImageSrc = '';
                section.innerHTML = '<p class="muted">Nessun QR disponibile. Verifica con la segreteria lo stato dell\'iscrizione.</p>';
                return;
            }

            const qrStaticUrl = `${window.location.origin}/q/${encodeURIComponent(qrToken)}`;

            section.innerHTML = `
                <div class="qr-layout">
                    <div class="qr-box" id="qrBox"><span class="muted">Generazione QR...</span></div>
                    <div class="qr-meta">
                        <p><strong>Codice QR statico:</strong> <code>${escapeHtml(qrToken)}</code></p>
                        <p><strong>Link QR statico:</strong> <a href="${escapeHtml(qrStaticUrl)}">${escapeHtml(qrStaticUrl)}</a></p>
                        <p><strong>Pacchetto:</strong> ${escapeHtml(latestConfirmedPurchase.pacchetto_nome || '-')}</p>
                        <p><strong>Scadenza:</strong> ${escapeHtml(formatDate(latestConfirmedPurchase.data_scadenza))}</p>
                        <p><strong>Ingressi rimanenti:</strong> ${escapeHtml(String(latestConfirmedPurchase.ingressi_rimanenti || 0))}</p>
                        <p style="margin-top:10px;">
                            <button class="btn btn-primary" id="downloadQrBtn" type="button">Scarica PDF QR</button>
                        </p>
                    </div>
                </div>
            `;

            const downloadBtn = byId('downloadQrBtn');
            if (downloadBtn) {
                downloadBtn.addEventListener('click', () => downloadQrPdf(latestConfirmedPurchase.id));
            }

            loadQrImage(latestConfirmedPurchase.id, qrToken).catch((error) => {
                showAlert(error.message || 'Errore generazione QR', 'error');
            });
        }

        async function loadQrImage(acquistoId, qrCode) {
            const response = await fetch(`${API_URL}/qr.php?action=svg&acquisto_id=${encodeURIComponent(acquistoId)}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            if (!response.ok) {
                throw new Error('Errore generazione QR');
            }

            const svgText = await response.text();
            if (!svgText || !svgText.includes('<svg')) {
                throw new Error('Formato QR non valido');
            }

            activeQrImageSrc = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svgText)}`;
            const qrBox = byId('qrBox');
            if (qrBox) {
                qrBox.innerHTML = `<img src="${activeQrImageSrc}" alt="QR ${escapeHtml(qrCode)}">`;
            }
        }

        async function downloadQrPdf(acquistoId) {
            const response = await fetch(`${API_URL}/qr.php?action=download&acquisto_id=${encodeURIComponent(acquistoId)}`, {
                method: 'GET',
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });

            if (!response.ok) {
                throw new Error('Errore download QR');
            }

            const blob = await response.blob();
            const blobUrl = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = `QR_${acquistoId}.pdf`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(blobUrl);
        }

        async function loadProfile() {
            const data = await apiJson('auth.php?action=me', { method: 'GET' });
            profile = data.user || {};

            user = {
                ...(user || {}),
                id: profile.id || user?.id,
                email: profile.email || user?.email,
                nome: profile.nome || user?.nome,
                cognome: profile.cognome || user?.cognome,
                ruolo: profile.ruolo_nome || user?.ruolo,
                livello: Number(profile.ruolo_livello || user?.livello || 1),
                qr_token: profile.qr_token || user?.qr_token || '',
            };
            localStorage.setItem('user', JSON.stringify(user));

            setHeaderUser();
            renderProfileCards();
            populateProfileForm();

            const enrollment = profile.stato_iscrizione || 'approved';
            byId('enrollmentStatus').innerHTML = statusBadge(enrollment);
        }

        async function loadProfileRequests() {
            const data = await apiJson('auth.php?action=profile-update-requests', { method: 'GET' });
            const rows = Array.isArray(data.requests) ? data.requests : [];
            const list = byId('profileRequestsList');

            if (!rows.length) {
                byId('lastProfileRequestStatus').textContent = 'Nessuna';
                byId('lastProfileRequestDate').textContent = '-';
                if (list) {
                    list.innerHTML = '<div class="request-row">Nessuna richiesta modifica dati inviata.</div>';
                }
                return;
            }

            const latest = rows[0];
            byId('lastProfileRequestStatus').innerHTML = statusBadge(latest.status || 'pending');
            byId('lastProfileRequestDate').textContent = formatDateTime(latest.created_at || '');

            if (!list) return;
            list.innerHTML = rows.slice(0, 5).map((row) => {
                const changes = row.changes && typeof row.changes === 'object'
                    ? Object.keys(row.changes).map((key) => `${key}: ${row.changes[key]}`).join(' | ')
                    : '-';
                return `
                    <div class="request-row">
                        <div><strong>Stato:</strong> ${statusBadge(row.status || '')}</div>
                        <div><strong>Data:</strong> ${escapeHtml(formatDateTime(row.created_at || ''))}</div>
                        <div><strong>Modifiche:</strong> ${escapeHtml(changes || '-')}</div>
                        ${row.review_note ? `<div><strong>Nota revisione:</strong> ${escapeHtml(row.review_note)}</div>` : ''}
                    </div>
                `;
            }).join('');
        }

        async function loadMyPurchases() {
            const data = await apiJson('pacchetti.php?action=my-purchases', { method: 'GET' });
            const rows = Array.isArray(data.acquisti) ? data.acquisti : [];
            const confirmed = rows
                .filter((row) => String(row.stato_pagamento || '').toLowerCase() === 'confirmed')
                .sort((a, b) => new Date(b.data_conferma || b.data_acquisto) - new Date(a.data_conferma || a.data_acquisto));

            latestConfirmedPurchase = confirmed[0] || null;
            renderQrSection();
        }

        function openRequestModal() {
            byId('profileRequestModal').classList.add('open');
            byId('profileRequestModal').setAttribute('aria-hidden', 'false');
            populateProfileForm();
        }

        function closeRequestModal() {
            byId('profileRequestModal').classList.remove('open');
            byId('profileRequestModal').setAttribute('aria-hidden', 'true');
        }

        async function submitProfileRequest(event) {
            event.preventDefault();
            const button = byId('sendRequestBtn');
            const original = button.textContent;

            const payload = {
                nome: byId('rqNome').value.trim(),
                cognome: byId('rqCognome').value.trim(),
                email: byId('rqEmail').value.trim().toLowerCase(),
                telefono: byId('rqTelefono').value.trim(),
                data_nascita: byId('rqDataNascita').value.trim(),
                codice_fiscale: byId('rqCodiceFiscale').value.trim().toUpperCase(),
                indirizzo: byId('rqIndirizzo').value.trim(),
                citta: byId('rqCitta').value.trim(),
                cap: byId('rqCap').value.trim(),
            };

            button.disabled = true;
            button.textContent = 'Invio in corso...';

            try {
                const data = await apiJson('auth.php?action=profile-update-request', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });

                closeRequestModal();
                showAlert(data.message || 'Richiesta inviata', 'ok');
                await Promise.all([loadProfile(), loadProfileRequests()]);
            } catch (error) {
                showAlert(error.message || 'Errore invio richiesta', 'error');
            } finally {
                button.disabled = false;
                button.textContent = original;
            }
        }

        async function logout() {
            activeQrImageSrc = '';

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

            localStorage.removeItem('token');
            localStorage.removeItem('user');
            sessionStorage.clear();
            window.location.href = '../login.php';
        }

        function goHome() {
            window.location.href = '../landing.php';
        }

        function bindEvents() {
            byId('logoutBtn').addEventListener('click', () => {
                logout();
            });
            byId('homeBtn').addEventListener('click', goHome);
            byId('openRequestModalBtn').addEventListener('click', openRequestModal);
            byId('closeRequestModalBtn').addEventListener('click', closeRequestModal);
            byId('profileRequestForm').addEventListener('submit', submitProfileRequest);

            byId('profileRequestModal').addEventListener('click', (event) => {
                if (event.target.id === 'profileRequestModal') {
                    closeRequestModal();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeRequestModal();
                }
            });
        }

        async function bootstrap() {
            setHeaderUser();
            bindEvents();
            await Promise.all([loadProfile(), loadProfileRequests(), loadMyPurchases()]);
        }

        bootstrap().catch((error) => {
            showAlert(error.message || 'Errore caricamento dashboard', 'error');
        });

        setInterval(() => {
            Promise.allSettled([loadProfileRequests(), loadMyPurchases()]);
        }, 45000);
    </script>
</body>
</html>


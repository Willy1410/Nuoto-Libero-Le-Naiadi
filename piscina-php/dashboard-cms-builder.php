<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

$landingModeActive = appIsLandingMode();
$homeDefaultUrl = $landingModeActive ? '../landing.php' : '../index.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="../apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../favicon-16x16.png">
    <link rel="manifest" href="../site.webmanifest">
    <link rel="shortcut icon" href="../favicon.ico">
    <title>CMS Builder-Ready - Nuoto libero Le Naiadi</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Arial, sans-serif; background: #f2f4f7; color: #1f2937; }
        .topbar {
            background: linear-gradient(135deg, #0f766e, #0ea5a0);
            color: #fff;
            padding: 14px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .topbar h1 { margin: 0; font-size: 22px; }
        .topbar small { opacity: 0.92; }
        .btn { border: 0; border-radius: 8px; padding: 9px 12px; font-weight: 600; cursor: pointer; }
        .btn-light { background: rgba(255,255,255,.18); color: #fff; }
        .btn-primary { background: #0ea5e9; color: #fff; }
        .btn-success { background: #15803d; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .container { max-width: 1320px; margin: 16px auto; padding: 0 12px; }
        .layout { display: grid; grid-template-columns: 330px 1fr; gap: 12px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(15, 23, 42, .08); padding: 12px; margin-bottom: 12px; }
        .card h2 { margin: 0 0 10px; font-size: 18px; }
        .list { max-height: 620px; overflow: auto; border: 1px solid #e2e8f0; border-radius: 8px; }
        .list-item { padding: 10px; border-bottom: 1px solid #e2e8f0; cursor: pointer; }
        .list-item:hover { background: #f8fafc; }
        .list-item.active { background: #ecfeff; border-left: 3px solid #0ea5e9; }
        .muted { color: #64748b; font-size: 12px; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .field { margin-bottom: 8px; }
        .field label { display: block; margin-bottom: 4px; font-size: 12px; font-weight: 700; color: #334155; }
        .field input, .field select, .field textarea {
            width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 9px; font-size: 13px;
        }
        .field textarea { min-height: 220px; font-family: Consolas, monospace; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .status { display: none; margin-top: 8px; border-radius: 8px; padding: 8px 10px; font-size: 13px; font-weight: 600; }
        .status.ok { display: block; background: #dcfce7; color: #166534; }
        .status.err { display: block; background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; font-size: 12px; text-align: left; }
        th { background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 7px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .badge-draft { background: #e2e8f0; color: #334155; }
        .badge-published { background: #dcfce7; color: #166534; }
        .right-grid { display: grid; grid-template-columns: 1fr; gap: 8px; }
        @media (max-width: 980px) {
            .layout { grid-template-columns: 1fr; }
            .row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div>
            <h1>CMS Builder-Ready</h1>
            <small id="whoami">Utente</small>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <?php if ($landingModeActive): ?>
                <button type="button" class="btn btn-light" id="homeLandingBtn">Home Landing</button>
                <button type="button" class="btn btn-light" id="homeIndexBtn">Home Index</button>
            <?php else: ?>
                <button type="button" class="btn btn-light" id="homeBtn">Home</button>
            <?php endif; ?>
            <button type="button" class="btn btn-light" id="backBtn">Torna dashboard</button>
            <button type="button" class="btn btn-danger" id="logoutBtn">Esci</button>
        </div>
    </div>

    <div class="container">
        <div id="statusBox" class="status"></div>
        <div class="layout">
            <div>
                <div class="card">
                    <h2>Pagine CMS</h2>
                    <div class="actions" style="margin-bottom:8px;">
                        <button class="btn btn-primary" id="newPageBtn" type="button">Nuova pagina</button>
                        <button class="btn btn-light" id="refreshPagesBtn" type="button">Aggiorna</button>
                    </div>
                    <div id="pagesList" class="list"></div>
                </div>

                <div class="card">
                    <h2>Revisioni</h2>
                    <div id="revisionsList" class="list" style="max-height:260px;"></div>
                </div>
            </div>

            <div class="right-grid">
                <div class="card">
                    <h2>Editor pagina</h2>
                    <div class="row">
                        <div class="field">
                            <label for="pageSlug">Slug</label>
                            <input id="pageSlug" placeholder="es. home">
                        </div>
                        <div class="field">
                            <label for="pageTitle">Titolo</label>
                            <input id="pageTitle" placeholder="Titolo pagina">
                        </div>
                    </div>
                    <div class="row">
                        <div class="field">
                            <label for="pageStatus">Stato</label>
                            <select id="pageStatus">
                                <option value="draft">draft</option>
                                <option value="published">published</option>
                            </select>
                        </div>
                        <div class="field">
                            <label>ID pagina</label>
                            <input id="pageId" readonly>
                        </div>
                    </div>
                    <div class="field">
                        <label for="pageContent">content_json</label>
                        <textarea id="pageContent" placeholder='{"hero": {"title": "..."}}'></textarea>
                    </div>
                    <div class="actions">
                        <button class="btn btn-success" type="button" id="saveDraftBtn">Salva bozza</button>
                        <button class="btn btn-primary" type="button" id="publishBtn">Pubblica</button>
                    </div>
                </div>

                <div class="card">
                    <h2>Media Library</h2>
                    <div class="row">
                        <div class="field">
                            <label for="mediaType">Tipo media</label>
                            <select id="mediaType">
                                <option value="image">image</option>
                                <option value="file">file</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="mediaFile">File</label>
                            <input id="mediaFile" type="file">
                        </div>
                    </div>
                    <div class="actions" style="margin-bottom:8px;">
                        <button class="btn btn-success" type="button" id="uploadMediaBtn">Upload media</button>
                        <button class="btn btn-light" type="button" id="refreshMediaBtn">Aggiorna media</button>
                    </div>
                    <div style="overflow:auto; max-height:300px; border:1px solid #e5e7eb; border-radius:8px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tipo</th>
                                    <th>Nome</th>
                                    <th>Mime</th>
                                    <th>KB</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody id="mediaBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card" id="settingsCard" style="display:none;">
                    <h2>Impostazioni CMS (solo admin)</h2>
                    <div class="row">
                        <div class="field">
                            <label for="settingKey">Chiave</label>
                            <input id="settingKey" value="homepage_notice">
                        </div>
                        <div class="field">
                            <label for="settingValue">Valore (JSON o testo)</label>
                            <input id="settingValue" placeholder="es. Benvenuto in piscina">
                        </div>
                    </div>
                    <div class="actions" style="margin-bottom:8px;">
                        <button class="btn btn-success" type="button" id="saveSettingBtn">Salva impostazione</button>
                        <button class="btn btn-light" type="button" id="loadSettingsBtn">Carica impostazioni</button>
                    </div>
                    <div style="overflow:auto; max-height:220px; border:1px solid #e5e7eb; border-radius:8px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Chiave</th>
                                    <th>Valore</th>
                                    <th>Aggiornato</th>
                                </tr>
                            </thead>
                            <tbody id="settingsBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h2>Builder.io Base</h2>
                    <p class="muted" style="margin-bottom:8px;">Test connessione contenuto pubblicato da Builder tramite API key in env.</p>
                    <div class="row">
                        <div class="field">
                            <label for="builderModelInput">Model</label>
                            <input id="builderModelInput" value="page">
                        </div>
                        <div class="field">
                            <label for="builderPathInput">URL path</label>
                            <input id="builderPathInput" value="/">
                        </div>
                    </div>
                    <div class="actions" style="margin-bottom:8px;">
                        <button class="btn btn-primary" type="button" id="testBuilderBtn">Test Builder.io</button>
                    </div>
                    <div style="overflow:auto; max-height:220px; border:1px solid #e5e7eb; border-radius:8px; padding:8px; font-size:12px; font-family:Consolas,monospace; background:#f8fafc;">
                        <pre id="builderResult" style="margin:0; white-space:pre-wrap;">Nessun test eseguito.</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/ui-modal.js"></script>
    <script>
        const API_URL = '../api/cms.php';
        const LANDING_MODE_ACTIVE = <?= $landingModeActive ? 'true' : 'false'; ?>;
        const HOME_DEFAULT_URL = <?= json_encode($homeDefaultUrl, JSON_UNESCAPED_UNICODE); ?>;
        const HOME_LANDING_URL = '../landing.php';
        const HOME_INDEX_URL = '../index.php';
        const token = localStorage.getItem('token');
        let user = null;
        try {
            user = JSON.parse(localStorage.getItem('user') || 'null');
        } catch (_) {
            user = null;
        }
        let authRedirecting = false;

        const allowedRoles = ['admin', 'ufficio', 'segreteria'];
        if (!token || !user || !allowedRoles.includes(user.ruolo)) {
            window.location.href = '../login.php';
            throw new Error('Sessione non valida');
        }

        let pages = [];
        let selectedPageId = 0;

        document.getElementById('whoami').textContent = `${user.nome || ''} ${user.cognome || ''} (${user.ruolo})`.trim();

        if (user.ruolo === 'admin') {
            document.getElementById('settingsCard').style.display = '';
        }

        function showStatus(message, type = 'ok') {
            const box = document.getElementById('statusBox');
            if (!message) {
                box.className = 'status';
                box.textContent = '';
                return;
            }
            box.className = `status ${type === 'error' ? 'err' : 'ok'}`;
            box.textContent = message;
        }

        function clearAuthStorage() {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            sessionStorage.clear();
        }

        function isUnauthorizedMessage(message) {
            const normalized = String(message || '').trim().toLowerCase();
            return normalized.includes('non autenticato') || normalized.includes('sessione scaduta');
        }

        function forceRelogin(message = 'Sessione scaduta. Accedi di nuovo.') {
            if (authRedirecting) {
                return;
            }

            authRedirecting = true;
            clearAuthStorage();
            showStatus(message, 'error');
            setTimeout(() => {
                window.location.href = '../login.php';
            }, 120);
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        async function apiJson(url, options = {}) {
            const response = await fetch(url, {
                ...options,
                headers: {
                    Authorization: `Bearer ${token}`,
                    ...(options.headers || {})
                }
            });

            const raw = (await response.text()).replace(/^\uFEFF/, '');
            let data = {};
            try {
                data = raw ? JSON.parse(raw) : {};
            } catch (_) {
                throw new Error('Risposta API non valida');
            }

            if (response.status === 401 || isUnauthorizedMessage(data?.message)) {
                forceRelogin('Sessione scaduta. Accedi di nuovo.');
                throw new Error('Sessione scaduta. Accedi di nuovo.');
            }

            if (!response.ok || data.success === false) {
                throw new Error(data.message || `Errore API ${response.status}`);
            }

            return data;
        }

        function renderPages() {
            const list = document.getElementById('pagesList');
            if (!pages.length) {
                list.innerHTML = '<div class="list-item">Nessuna pagina CMS</div>';
                return;
            }

            list.innerHTML = pages.map((row) => {
                const isActive = Number(row.id) === Number(selectedPageId);
                return `
                    <div class="list-item ${isActive ? 'active' : ''}" data-id="${row.id}">
                        <strong>${escapeHtml(row.title || row.slug)}</strong><br>
                        <span class="muted">/${escapeHtml(row.slug)} | v${escapeHtml(String(row.version_num || 1))}</span><br>
                        <span class="badge ${row.status === 'published' ? 'badge-published' : 'badge-draft'}">${escapeHtml(row.status)}</span>
                    </div>
                `;
            }).join('');

            list.querySelectorAll('.list-item[data-id]').forEach((node) => {
                node.addEventListener('click', () => {
                    const id = Number(node.getAttribute('data-id') || 0);
                    if (id > 0) {
                        loadPage(id).catch((error) => showStatus(error.message, 'error'));
                    }
                });
            });
        }

        function setPageForm(page) {
            document.getElementById('pageId').value = page?.id || '';
            document.getElementById('pageSlug').value = page?.slug || '';
            document.getElementById('pageTitle').value = page?.title || '';
            document.getElementById('pageStatus').value = page?.status || 'draft';
            document.getElementById('pageContent').value = page?.content_json || '{}';
            selectedPageId = Number(page?.id || 0);
            renderPages();
        }

        async function loadPages() {
            const data = await apiJson(`${API_URL}?action=editor-pages`);
            pages = Array.isArray(data.pages) ? data.pages : [];
            renderPages();

            if (selectedPageId > 0) {
                await loadPage(selectedPageId);
            }
        }

        async function loadPage(id) {
            const data = await apiJson(`${API_URL}?action=editor-page&id=${encodeURIComponent(String(id))}`);
            const page = data.page || null;
            if (!page) {
                showStatus('Pagina non trovata', 'error');
                return;
            }
            setPageForm(page);
            await loadRevisions(page.id);
        }

        async function savePage(status) {
            const payload = {
                id: Number(document.getElementById('pageId').value || 0),
                slug: document.getElementById('pageSlug').value.trim(),
                title: document.getElementById('pageTitle').value.trim(),
                status,
                content_json: document.getElementById('pageContent').value.trim()
            };

            const data = await apiJson(`${API_URL}?action=save-page`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            showStatus(`Pagina salvata: ${data.page.slug} [${data.page.status}]`, 'ok');
            setPageForm(data.page);
            await Promise.all([loadPages(), loadRevisions(Number(data.page.id || 0))]);
        }

        async function loadRevisions(pageId) {
            const list = document.getElementById('revisionsList');
            if (!pageId) {
                list.innerHTML = '<div class="list-item">Nessuna revisione</div>';
                return;
            }

            const data = await apiJson(`${API_URL}?action=revisions&page_id=${encodeURIComponent(String(pageId))}`);
            const revisions = Array.isArray(data.revisions) ? data.revisions : [];
            if (!revisions.length) {
                list.innerHTML = '<div class="list-item">Nessuna revisione disponibile</div>';
                return;
            }

            list.innerHTML = revisions.map((row) => `
                <div class="list-item">
                    <strong>v${escapeHtml(String(row.version_num || ''))}</strong>
                    <span class="badge ${row.status === 'published' ? 'badge-published' : 'badge-draft'}">${escapeHtml(row.status || '')}</span><br>
                    <span class="muted">${escapeHtml(row.created_at || '')}</span>
                </div>
            `).join('');
        }

        async function loadMedia() {
            const data = await apiJson(`${API_URL}?action=media`);
            const rows = Array.isArray(data.media) ? data.media : [];
            const body = document.getElementById('mediaBody');
            if (!rows.length) {
                body.innerHTML = '<tr><td colspan="6">Nessun media</td></tr>';
                return;
            }

            body.innerHTML = rows.map((row) => `
                <tr>
                    <td>${escapeHtml(String(row.id))}</td>
                    <td>${escapeHtml(row.type || '')}</td>
                    <td>${escapeHtml(row.original_name || '')}</td>
                    <td>${escapeHtml(row.mime || '')}</td>
                    <td>${escapeHtml(String(Math.round((Number(row.size_bytes || 0) / 1024) * 100) / 100))}</td>
                    <td>
                        <button class="btn btn-light" type="button" data-open="${escapeHtml(row.public_url || '')}">Apri</button>
                        <button class="btn btn-danger" type="button" data-delete-id="${escapeHtml(String(row.id))}">Elimina</button>
                    </td>
                </tr>
            `).join('');

            body.querySelectorAll('button[data-open]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const url = btn.getAttribute('data-open') || '';
                    if (url) {
                        window.open(url, '_blank', 'noopener');
                    }
                });
            });

            body.querySelectorAll('button[data-delete-id]').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const id = Number(btn.getAttribute('data-delete-id') || 0);
                    if (!id) return;
                    const confirmed = window.NuotoLiberoUI ? await window.NuotoLiberoUI.confirm('Eliminare questo media?', { title: 'Conferma eliminazione media' }) : true;
                    if (!confirmed) return;

                    try {
                        await apiJson(`${API_URL}?action=media&id=${encodeURIComponent(String(id))}`, {
                            method: 'DELETE'
                        });
                        showStatus('Media eliminato', 'ok');
                        await loadMedia();
                    } catch (error) {
                        showStatus(error.message, 'error');
                    }
                });
            });
        }

        async function uploadMedia() {
            const type = document.getElementById('mediaType').value;
            const input = document.getElementById('mediaFile');
            const file = input.files && input.files[0] ? input.files[0] : null;

            if (!file) {
                showStatus('Seleziona un file da caricare', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', type);

            await apiJson(`${API_URL}?action=upload-media&type=${encodeURIComponent(type)}`, {
                method: 'POST',
                body: formData
            });

            input.value = '';
            showStatus('Media caricato correttamente', 'ok');
            await loadMedia();
        }

        async function loadSettings() {
            if (user.ruolo !== 'admin') {
                return;
            }

            const data = await apiJson(`${API_URL}?action=settings`);
            const rows = Array.isArray(data.settings) ? data.settings : [];
            const body = document.getElementById('settingsBody');

            if (!rows.length) {
                body.innerHTML = '<tr><td colspan="3">Nessuna impostazione</td></tr>';
                return;
            }

            body.innerHTML = rows.map((row) => `
                <tr>
                    <td>${escapeHtml(row.setting_key || '')}</td>
                    <td>${escapeHtml(row.value_json || '')}</td>
                    <td>${escapeHtml(row.updated_at || '')}</td>
                </tr>
            `).join('');
        }

        async function saveSetting() {
            if (user.ruolo !== 'admin') {
                showStatus('Solo admin', 'error');
                return;
            }

            const key = document.getElementById('settingKey').value.trim();
            const value = document.getElementById('settingValue').value;

            await apiJson(`${API_URL}?action=save-setting`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ setting_key: key, value })
            });

            showStatus('Impostazione salvata', 'ok');
            await loadSettings();
        }

        async function testBuilderConnection() {
            const model = document.getElementById('builderModelInput').value.trim() || 'page';
            const path = document.getElementById('builderPathInput').value.trim() || '/';
            const data = await apiJson(`${API_URL}?action=builder-stub&model=${encodeURIComponent(model)}&url_path=${encodeURIComponent(path)}`);
            const output = document.getElementById('builderResult');
            output.textContent = JSON.stringify(data.builder || {}, null, 2);
            showStatus('Test Builder completato', 'ok');
        }

        function resetPageForm() {
            setPageForm({ id: '', slug: '', title: '', status: 'draft', content_json: '{}' });
            document.getElementById('revisionsList').innerHTML = '<div class="list-item">Nuova pagina (nessuna revisione)</div>';
        }

        document.getElementById('newPageBtn').addEventListener('click', resetPageForm);
        document.getElementById('refreshPagesBtn').addEventListener('click', () => loadPages().catch((error) => showStatus(error.message, 'error')));
        document.getElementById('saveDraftBtn').addEventListener('click', () => savePage('draft').catch((error) => showStatus(error.message, 'error')));
        document.getElementById('publishBtn').addEventListener('click', () => savePage('published').catch((error) => showStatus(error.message, 'error')));
        document.getElementById('uploadMediaBtn').addEventListener('click', () => uploadMedia().catch((error) => showStatus(error.message, 'error')));
        document.getElementById('refreshMediaBtn').addEventListener('click', () => loadMedia().catch((error) => showStatus(error.message, 'error')));
        document.getElementById('saveSettingBtn').addEventListener('click', () => saveSetting().catch((error) => showStatus(error.message, 'error')));
        document.getElementById('loadSettingsBtn').addEventListener('click', () => loadSettings().catch((error) => showStatus(error.message, 'error')));
        document.getElementById('testBuilderBtn').addEventListener('click', () => testBuilderConnection().catch((error) => showStatus(error.message, 'error')));

        document.getElementById('backBtn').addEventListener('click', () => {
            if (user.ruolo === 'admin') {
                window.location.href = 'dashboard-admin.php';
                return;
            }
            window.location.href = 'dashboard-ufficio.php';
        });

        const homeBtn = document.getElementById('homeBtn');
        if (homeBtn) {
            homeBtn.addEventListener('click', () => {
                window.location.href = HOME_DEFAULT_URL;
            });
        }

        const homeLandingBtn = document.getElementById('homeLandingBtn');
        if (homeLandingBtn) {
            homeLandingBtn.addEventListener('click', () => {
                window.location.href = HOME_LANDING_URL;
            });
        }

        const homeIndexBtn = document.getElementById('homeIndexBtn');
        if (homeIndexBtn) {
            homeIndexBtn.addEventListener('click', () => {
                window.location.href = HOME_INDEX_URL;
            });
        }

        document.getElementById('logoutBtn').addEventListener('click', async () => {
            try {
                await fetch('../api/auth.php?action=logout', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
            } catch (_) {
            }

            try {
                await fetch('../login.php?clear_staff_access=1', {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store'
                });
            } catch (_) {
            }

            clearAuthStorage();
            window.location.href = LANDING_MODE_ACTIVE ? HOME_LANDING_URL : HOME_DEFAULT_URL;
        });

        Promise.all([
            loadPages(),
            loadMedia(),
            loadSettings()
        ]).catch((error) => {
            if (authRedirecting) {
                return;
            }
            showStatus(error.message, 'error');
        });
    </script>
</body>
</html>



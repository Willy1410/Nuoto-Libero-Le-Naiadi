<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Contenuti - Gli Squaletti</title>
    <link rel="stylesheet" href="../assets/vendor/quill/quill.snow.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Segoe UI", Arial, sans-serif; background: #f2f4f7; color: #1f2937; }
        .header {
            background: linear-gradient(135deg, #0077b6, #00a8e8);
            color: #fff;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .header h1 { font-size: 22px; }
        .header small { display: block; opacity: 0.9; }
        .header-actions { display: flex; gap: 8px; }
        .btn {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-secondary { background: rgba(255,255,255,0.2); color: #fff; }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-open { background: #2563eb; color: #fff; }
        .btn-save-all { background: #15803d; color: #fff; }
        .btn-save { background: #16a34a; color: #fff; }
        .btn-reset { background: #f59e0b; color: #fff; }
        .btn-editor { background: #0ea5e9; color: #fff; }

        .container { max-width: 1280px; margin: 20px auto; padding: 0 16px; }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 16px;
            margin-bottom: 16px;
        }
        .toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .toolbar label { font-weight: 600; font-size: 14px; }
        .toolbar select,
        .toolbar input[type="text"] {
            min-width: 220px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 9px 10px;
            background: #fff;
        }
        .status {
            margin-top: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 14px;
            display: none;
        }
        .status.ok { display: block; background: #dcfce7; color: #166534; }
        .status.err { display: block; background: #fee2e2; color: #991b1b; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { text-align: left; background: #f8fafc; font-size: 13px; }
        td { font-size: 13px; }
        .key { font-family: Consolas, monospace; color: #475569; }
        .selector { font-family: Consolas, monospace; color: #334155; word-break: break-word; }
        .field-badge {
            display: inline-block;
            font-size: 11px;
            background: #dbeafe;
            color: #1d4ed8;
            border-radius: 999px;
            padding: 4px 8px;
        }
        textarea {
            width: 100%;
            min-height: 72px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 8px;
            font-size: 13px;
            resize: vertical;
        }
        .input-url {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
            font-size: 13px;
        }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .hint { color: #64748b; font-size: 12px; margin-top: 8px; }
        .empty { padding: 14px; color: #64748b; }
        .cms-layout {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 16px;
        }
        .row-active {
            background: #eff6ff;
            outline: 2px solid #60a5fa;
            outline-offset: -2px;
        }
        .preview-toolbar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 8px;
        }
        .preview-info {
            font-size: 12px;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
            margin-bottom: 8px;
        }
        .preview-frame {
            width: 100%;
            min-height: 680px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
        }

        .editor-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 1000;
        }
        .editor-overlay.open { display: flex; }
        .editor-modal {
            width: 100%;
            max-width: 880px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 36px rgba(15, 23, 42, 0.2);
            overflow: hidden;
        }
        .editor-modal header {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }
        .editor-modal header h4 { font-size: 16px; margin-bottom: 4px; }
        .editor-modal header p { font-size: 12px; color: #64748b; }
        .editor-body { padding: 14px 16px; }
        .editor-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 12px 16px 16px;
        }
        #richEditorHost {
            min-height: 220px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }
        #fallbackEditor {
            width: 100%;
            min-height: 220px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
            font-size: 14px;
            display: none;
        }

        @media (max-width: 900px) {
            .header { flex-direction: column; align-items: flex-start; }
            .cms-layout { grid-template-columns: 1fr; }
            th:nth-child(3), td:nth-child(3) { display: none; }
            th:nth-child(4), td:nth-child(4) { display: none; }
            .preview-frame { min-height: 420px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>CMS Contenuti Sito</h1>
            <small id="userInfo">Utente</small>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="goBack()">Torna dashboard</button>
            <button class="btn btn-danger" onclick="logout()">Esci</button>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="toolbar">
                <label for="pageSelect">Pagina:</label>
                <select id="pageSelect"></select>
                <button class="btn btn-open" id="openPageBtn" type="button">Apri pagina pubblica</button>
                <button class="btn btn-save-all" id="saveAllBtn" type="button">Salva campi visibili</button>
                <input type="text" id="filterInput" placeholder="Filtra per label/chiave...">
            </div>
            <div class="hint">Modifica i campi e salva. Clicca una riga per vedere subito in anteprima dove stai scrivendo. Se un valore viene svuotato, il sito usa il testo statico originale del file HTML.</div>
            <div id="statusBox" class="status"></div>
        </div>

        <div class="cms-layout">
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 20%;">Campo</th>
                            <th style="width: 16%;">Chiave</th>
                            <th style="width: 24%;">Selector</th>
                            <th style="width: 8%;">Tipo</th>
                            <th style="width: 22%;">Valore</th>
                            <th style="width: 10%;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody id="rowsBody">
                        <tr><td colspan="6" class="empty">Caricamento contenuti...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="preview-toolbar">
                    <button class="btn btn-open" id="reloadPreviewBtn" type="button">Ricarica anteprima</button>
                </div>
                <div id="previewInfo" class="preview-info">Seleziona un campo per evidenziare la sezione nella pagina.</div>
                <iframe id="previewFrame" class="preview-frame" title="Anteprima pagina pubblica"></iframe>
            </div>
        </div>
    </div>

    <div id="editorOverlay" class="editor-overlay">
        <div class="editor-modal" role="dialog" aria-modal="true" aria-labelledby="editorTitle">
            <header>
                <h4 id="editorTitle">Editor contenuto</h4>
                <p id="editorSubtitle">Modifica testo campo selezionato</p>
            </header>
            <div class="editor-body">
                <div id="richEditorHost"></div>
                <textarea id="fallbackEditor" placeholder="Inserisci il contenuto..."></textarea>
            </div>
            <div class="editor-footer">
                <button class="btn" type="button" id="editorCancelBtn">Annulla</button>
                <button class="btn btn-save" type="button" id="editorApplyBtn">Applica al campo</button>
            </div>
        </div>
    </div>

    <script src="../assets/vendor/quill/quill.min.js"></script>
    <script src="../js/ui-modal.js"></script>
    <script>
        const API_URL = '../api';
        const token = localStorage.getItem('token');
        const user = JSON.parse(localStorage.getItem('user') || 'null');

        const pageSelect = document.getElementById('pageSelect');
        const filterInput = document.getElementById('filterInput');
        const rowsBody = document.getElementById('rowsBody');
        const statusBox = document.getElementById('statusBox');
        const openPageBtn = document.getElementById('openPageBtn');
        const saveAllBtn = document.getElementById('saveAllBtn');
        const reloadPreviewBtn = document.getElementById('reloadPreviewBtn');
        const previewFrame = document.getElementById('previewFrame');
        const previewInfo = document.getElementById('previewInfo');
        const editorOverlay = document.getElementById('editorOverlay');
        const editorTitle = document.getElementById('editorTitle');
        const editorSubtitle = document.getElementById('editorSubtitle');
        const fallbackEditor = document.getElementById('fallbackEditor');

        let currentItems = [];
        let quillEditor = null;
        let activeEditorSafeKey = '';
        let activePreviewSafeKey = '';

        if (!token || !user || !['admin', 'ufficio', 'segreteria'].includes(user.ruolo)) {
            window.location.href = '../login.php';
        }

        document.getElementById('userInfo').textContent = `${user.nome} ${user.cognome} (${user.ruolo})`;

        function showStatus(message, type) {
            statusBox.className = `status ${type === 'error' ? 'err' : 'ok'}`;
            statusBox.textContent = message;
        }

        function clearStatus() {
            statusBox.className = 'status';
            statusBox.textContent = '';
        }

        async function apiFetch(url, options = {}) {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json',
                    ...(options.headers || {})
                }
            });

            const rawText = (await response.text()).replace(/^\uFEFF/, '');
            let data = {};
            try {
                data = rawText ? JSON.parse(rawText) : {};
            } catch (error) {
                throw new Error('Risposta API non valida');
            }

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Operazione non riuscita');
            }
            return data;
        }

        function resolvePublicPath(page) {
            if (page === 'index') {
                return '../index.php';
            }
            return `../${page}.php`;
        }

        function getItemBySafeKey(safeKey) {
            return currentItems.find(item => escapeAttr(item.key) === safeKey) || null;
        }

        function resolveRawKey(safeKey) {
            const item = getItemBySafeKey(safeKey);
            return item ? item.key : safeKey;
        }

        function getValueElement(safeKey) {
            return document.getElementById(`value_${safeKey}`);
        }

        function renderValueControl(item) {
            const safeKey = escapeAttr(item.key);
            const value = escapeHtml(item.value || '');

            if (item.field === 'url') {
                return `<input class="input-url" type="url" id="value_${safeKey}" value="${value}" placeholder="Inserisci URL (https://...)">`;
            }

            return `<textarea id="value_${safeKey}" placeholder="Valore override...">${value}</textarea>`;
        }

        function renderRows() {
            const query = filterInput.value.trim().toLowerCase();
            const list = currentItems.filter(item => {
                if (!query) return true;
                return item.label.toLowerCase().includes(query) || item.key.toLowerCase().includes(query);
            });

            if (!list.length) {
                rowsBody.innerHTML = '<tr><td colspan="6" class="empty">Nessun campo trovato.</td></tr>';
                return;
            }

            rowsBody.innerHTML = list.map(item => {
                const safeKey = escapeAttr(item.key);
                const rowClass = safeKey === activePreviewSafeKey ? 'row-active' : '';
                const editorButton = item.field === 'url'
                    ? ''
                    : `<button class="btn btn-editor" type="button" onclick="openRichEditor('${safeKey}')">Editor</button>`;

                return `
                    <tr data-safe-key="${safeKey}" class="${rowClass}">
                        <td>${escapeHtml(item.label)}</td>
                        <td class="key">${escapeHtml(item.key)}</td>
                        <td class="selector">${escapeHtml(item.selector)}</td>
                        <td><span class="field-badge">${escapeHtml(item.field)}</span></td>
                        <td>${renderValueControl(item)}</td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-open" type="button" onclick="focusItem('${safeKey}')">Vedi</button>
                                <button class="btn btn-save" type="button" onclick="saveItem('${safeKey}')">Salva</button>
                                <button class="btn btn-reset" type="button" onclick="clearItem('${safeKey}')">Reset</button>
                                ${editorButton}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        async function loadTemplates() {
            const data = await apiFetch(`${API_URL}/contenuti.php?action=templates`, { method: 'GET' });
            const pages = Object.keys(data.templates || {}).sort((a, b) => a.localeCompare(b));

            pageSelect.innerHTML = pages
                .map(page => `<option value="${escapeAttr(page)}">${escapeHtml(page)}</option>`)
                .join('');

            if (!pageSelect.value) {
                rowsBody.innerHTML = '<tr><td colspan="6" class="empty">Nessuna pagina configurata nel CMS.</td></tr>';
                return;
            }

            openPageBtn.onclick = () => {
                window.open(resolvePublicPath(pageSelect.value), '_blank');
            };

            await loadPage(pageSelect.value);
        }

        async function loadPage(page) {
            clearStatus();
            const data = await apiFetch(`${API_URL}/contenuti.php?action=list&page=${encodeURIComponent(page)}`, { method: 'GET' });
            currentItems = data.items || [];
            renderRows();
            await loadPreview(page);
            if (activePreviewSafeKey) {
                highlightPreviewItem(activePreviewSafeKey);
            } else if (currentItems.length) {
                focusItem(escapeAttr(currentItems[0].key), false);
            }
        }

        function getPreviewDocument() {
            if (!previewFrame || !previewFrame.contentWindow) return null;
            try {
                return previewFrame.contentWindow.document;
            } catch (error) {
                return null;
            }
        }

        function updatePreviewInfo(message) {
            if (previewInfo) {
                previewInfo.textContent = message;
            }
        }

        function ensurePreviewStyles(doc) {
            if (!doc || doc.getElementById('cms-preview-style')) return;
            const style = doc.createElement('style');
            style.id = 'cms-preview-style';
            style.textContent = `
                .cms-preview-target {
                    outline: 3px solid #0ea5e9 !important;
                    box-shadow: 0 0 0 4px rgba(14,165,233,0.25) !important;
                    scroll-margin-top: 80px !important;
                }
            `;
            doc.head.appendChild(style);
        }

        function clearPreviewHighlight(doc) {
            if (!doc) return;
            doc.querySelectorAll('.cms-preview-target').forEach((node) => node.classList.remove('cms-preview-target'));
        }

        function applyValueToPreviewNode(item, node, value) {
            if (!item || !node) return;
            if (item.field === 'url') {
                const attribute = item.attribute || 'href';
                if (value) {
                    node.setAttribute(attribute, value);
                } else {
                    node.removeAttribute(attribute);
                }
                return;
            }
            if (item.field === 'html') {
                node.innerHTML = value || '';
                return;
            }
            node.textContent = value || '';
        }

        function applyPreviewOverrides() {
            const doc = getPreviewDocument();
            if (!doc) return;

            currentItems.forEach((item) => {
                const safeKey = escapeAttr(item.key);
                const input = getValueElement(safeKey);
                const value = input ? input.value : item.value || '';
                try {
                    const nodes = doc.querySelectorAll(item.selector || '');
                    nodes.forEach((node) => applyValueToPreviewNode(item, node, value));
                } catch (error) {
                    // Selector non valido: ignora solo il campo problematico.
                }
            });
        }

        function highlightPreviewItem(safeKey) {
            const item = getItemBySafeKey(safeKey);
            const doc = getPreviewDocument();
            if (!item || !doc) return;

            ensurePreviewStyles(doc);
            clearPreviewHighlight(doc);

            let node = null;
            try {
                node = doc.querySelector(item.selector || '');
            } catch (error) {
                node = null;
            }
            if (!node) {
                updatePreviewInfo(`Campo selezionato: ${item.label}. Elemento non trovato con selector: ${item.selector}`);
                return;
            }

            node.classList.add('cms-preview-target');
            node.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
            updatePreviewInfo(`Stai modificando: ${item.label} | Selector: ${item.selector}`);
        }

        function focusItem(safeKey, doScroll = true) {
            activePreviewSafeKey = safeKey;
            rowsBody.querySelectorAll('tr[data-safe-key]').forEach((row) => {
                row.classList.toggle('row-active', row.getAttribute('data-safe-key') === safeKey);
            });
            highlightPreviewItem(safeKey);

            if (doScroll) {
                const row = rowsBody.querySelector(`tr[data-safe-key="${safeKey}"]`);
                if (row) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        async function loadPreview(page, forceReload = false) {
            if (!previewFrame) return;
            const targetSrc = resolvePublicPath(page);
            const currentSrc = previewFrame.getAttribute('data-page') || '';

            if (!forceReload && currentSrc === page && previewFrame.contentWindow) {
                applyPreviewOverrides();
                return;
            }

            previewFrame.setAttribute('data-page', page);

            await new Promise((resolve) => {
                previewFrame.onload = () => {
                    applyPreviewOverrides();
                    resolve();
                };
                previewFrame.src = `${targetSrc}?cms_preview=${Date.now()}`;
            });
        }

        async function saveItem(safeKey) {
            const page = pageSelect.value;
            const input = getValueElement(safeKey);
            if (!input) return;

            const rawKey = resolveRawKey(safeKey);

            try {
                await apiFetch(`${API_URL}/contenuti.php?action=save`, {
                    method: 'POST',
                    body: JSON.stringify({ page, key: rawKey, value: input.value })
                });
                showStatus(`Contenuto salvato: ${rawKey}`, 'ok');
                await loadPage(page);
            } catch (error) {
                showStatus(error.message, 'error');
            }
        }

        async function clearItem(safeKey) {
            const page = pageSelect.value;
            const rawKey = resolveRawKey(safeKey);

            const confirmed = window.GliSqualettiUI ? await window.GliSqualettiUI.confirm(`Eliminare override per "${rawKey}"?`, { title: "Conferma reset contenuto" }) : true;
            if (!confirmed) {
                return;
            }

            try {
                await apiFetch(`${API_URL}/contenuti.php?action=delete&page=${encodeURIComponent(page)}&key=${encodeURIComponent(rawKey)}`, {
                    method: 'DELETE'
                });
                showStatus(`Override rimosso: ${rawKey}`, 'ok');
                await loadPage(page);
            } catch (error) {
                showStatus(error.message, 'error');
            }
        }

        async function saveVisibleItems() {
            const rows = Array.from(rowsBody.querySelectorAll('tr[data-safe-key]'));
            if (!rows.length) {
                showStatus('Nessun campo visibile da salvare.', 'error');
                return;
            }

            saveAllBtn.disabled = true;
            saveAllBtn.textContent = 'Salvataggio...';

            let successCount = 0;
            let errorCount = 0;
            const page = pageSelect.value;

            for (const row of rows) {
                const safeKey = row.getAttribute('data-safe-key') || '';
                const input = getValueElement(safeKey);
                if (!safeKey || !input) {
                    continue;
                }

                try {
                    await apiFetch(`${API_URL}/contenuti.php?action=save`, {
                        method: 'POST',
                        body: JSON.stringify({ page, key: resolveRawKey(safeKey), value: input.value })
                    });
                    successCount += 1;
                } catch (error) {
                    console.error('saveVisibleItems error', error);
                    errorCount += 1;
                }
            }

            saveAllBtn.disabled = false;
            saveAllBtn.textContent = 'Salva campi visibili';

            if (errorCount > 0) {
                showStatus(`Salvati ${successCount} campi, errori su ${errorCount} campi.`, 'error');
            } else {
                showStatus(`Salvataggio completato: ${successCount} campi aggiornati.`, 'ok');
            }

            await loadPage(page);
        }

        function initRichEditor() {
            if (window.Quill) {
                quillEditor = new window.Quill('#richEditorHost', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, false] }],
                            ['bold', 'italic', 'underline'],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            ['link'],
                            ['clean']
                        ]
                    }
                });
                fallbackEditor.style.display = 'none';
                return;
            }

            document.getElementById('richEditorHost').style.display = 'none';
            fallbackEditor.style.display = 'block';
        }

        function closeRichEditor() {
            editorOverlay.classList.remove('open');
            activeEditorSafeKey = '';
        }

        function openRichEditor(safeKey) {
            const item = getItemBySafeKey(safeKey);
            const input = getValueElement(safeKey);

            if (!item || !input) {
                showStatus('Campo non disponibile.', 'error');
                return;
            }

            if (item.field === 'url') {
                showStatus('Per i link usa il campo URL diretto.', 'error');
                return;
            }

            activeEditorSafeKey = safeKey;
            focusItem(safeKey, false);
            editorTitle.textContent = item.label;
            editorSubtitle.textContent = `Chiave: ${item.key} | Tipo: ${item.field}`;

            if (quillEditor) {
                if (item.field === 'html') {
                    quillEditor.root.innerHTML = input.value || '';
                } else {
                    quillEditor.setText(input.value || '');
                }
            } else {
                fallbackEditor.value = input.value || '';
            }

            editorOverlay.classList.add('open');
        }

        function applyRichEditorValue() {
            if (!activeEditorSafeKey) {
                closeRichEditor();
                return;
            }

            const item = getItemBySafeKey(activeEditorSafeKey);
            const input = getValueElement(activeEditorSafeKey);
            if (!item || !input) {
                closeRichEditor();
                return;
            }

            if (quillEditor) {
                input.value = item.field === 'html'
                    ? quillEditor.root.innerHTML
                    : quillEditor.getText().replace(/\n$/, '');
            } else {
                input.value = fallbackEditor.value;
            }

            applyPreviewOverrides();
            focusItem(activeEditorSafeKey, false);
            closeRichEditor();
            showStatus('Editor avanzato applicato. Ricorda di salvare il campo.', 'ok');
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function escapeAttr(value) {
            return String(value).replace(/[^a-zA-Z0-9_\-]/g, '_');
        }

        function goBack() {
            if (user.ruolo === 'admin') {
                window.location.href = 'dashboard-admin.php';
                return;
            }
            window.location.href = 'dashboard-ufficio.php';
        }

        function logout() {
            localStorage.clear();
            window.location.href = '../login.php';
        }

        filterInput.addEventListener('input', renderRows);
        pageSelect.addEventListener('change', async () => {
            await loadPage(pageSelect.value);
        });
        saveAllBtn.addEventListener('click', saveVisibleItems);
        if (reloadPreviewBtn) {
            reloadPreviewBtn.addEventListener('click', async () => {
                await loadPreview(pageSelect.value, true);
                if (activePreviewSafeKey) {
                    highlightPreviewItem(activePreviewSafeKey);
                }
            });
        }
        rowsBody.addEventListener('click', (event) => {
            const row = event.target.closest('tr[data-safe-key]');
            if (!row) return;
            const safeKey = row.getAttribute('data-safe-key');
            if (safeKey) {
                focusItem(safeKey, false);
            }
        });
        rowsBody.addEventListener('input', (event) => {
            const target = event.target;
            if (!target || !target.id || !target.id.startsWith('value_')) return;
            applyPreviewOverrides();
            if (activePreviewSafeKey) {
                highlightPreviewItem(activePreviewSafeKey);
            }
        });
        document.getElementById('editorCancelBtn').addEventListener('click', closeRichEditor);
        document.getElementById('editorApplyBtn').addEventListener('click', applyRichEditorValue);
        editorOverlay.addEventListener('click', (event) => {
            if (event.target === editorOverlay) {
                closeRichEditor();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && editorOverlay.classList.contains('open')) {
                closeRichEditor();
            }
        });

        initRichEditor();

        loadTemplates().catch(error => {
            rowsBody.innerHTML = `<tr><td colspan="6" class="empty">Errore caricamento CMS: ${escapeHtml(error.message)}</td></tr>`;
            showStatus(error.message, 'error');
        });

        window.saveItem = saveItem;
        window.clearItem = clearItem;
        window.openRichEditor = openRichEditor;
        window.focusItem = focusItem;
        window.logout = logout;
        window.goBack = goBack;
    </script>
</body>
</html>





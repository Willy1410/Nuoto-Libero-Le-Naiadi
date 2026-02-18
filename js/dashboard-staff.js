
(function () {
    const config = window.DASHBOARD_CONFIG || {};
    const API_URL = config.apiUrl || '../api';
    const allowedRoles = Array.isArray(config.allowedRoles) && config.allowedRoles.length
        ? config.allowedRoles
        : ['admin'];

    const token = localStorage.getItem('token');
    let user = null;
    try {
        user = JSON.parse(localStorage.getItem('user') || 'null');
    } catch (_) {
        user = null;
    }

    if (!token || !user || !user.ruolo || !allowedRoles.includes(user.ruolo)) {
        window.location.href = '../login.html';
        return;
    }

    const state = {
        activeTab: 'overview',
        selectedUserId: '',
        userDetail: null,
        packages: [],
        reportDate: new Date().toISOString().slice(0, 10),
        exportPreview: {
            dataset: '',
            columns: [],
            rows: [],
            selectedColumns: [],
        },
    };

    function el(id) {
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

    function formatCurrency(value) {
        return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(Number(value || 0));
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

    function normalizeStatus(status) {
        const value = String(status || '').toLowerCase();
        if (value === 'confirmed' || value === 'approved' || value === 'active') {
            return { cls: 'badge-ok', text: value };
        }
        if (value === 'pending') {
            return { cls: 'badge-warn', text: value };
        }
        if (value === 'rejected' || value === 'cancelled' || value === 'inactive') {
            return { cls: 'badge-danger', text: value };
        }
        return { cls: 'badge-neutral', text: value || '-' };
    }

    function getExportDatasetLabel(dataset) {
        if (dataset === 'users') return 'Clienti';
        if (dataset === 'purchases') return 'Acquisti';
        if (dataset === 'checkins') return 'Check-in';
        return 'Dataset';
    }

    function setStatus(message, type) {
        const box = el('globalStatus');
        if (!box) return;
        if (!message) {
            box.className = 'status-box';
            box.textContent = '';
            return;
        }
        box.className = `status-box ${type === 'error' ? 'err' : 'ok'}`;
        box.textContent = message;
    }

    function openModal(id) {
        const node = el(id);
        if (!node) return;
        node.classList.add('open');
    }

    function closeModal(id) {
        const node = el(id);
        if (!node) return;
        node.classList.remove('open');
    }

    async function apiJson(path, options = {}) {
        const response = await fetch(`${API_URL}/${path}`, {
            ...options,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                ...(options.headers || {}),
            },
        });

        const rawText = (await response.text()).replace(/^\uFEFF/, '').trim();
        let data = {};
        if (rawText) {
            try {
                data = JSON.parse(rawText);
            } catch (_) {
                throw new Error('Risposta API non valida');
            }
        }

        if (!response.ok || data.success === false) {
            throw new Error(data.message || `Errore richiesta API (${response.status})`);
        }

        return data;
    }

    function parseFilename(disposition) {
        if (!disposition) return '';

        const utf8 = disposition.match(/filename\*=UTF-8''([^;]+)/i);
        if (utf8 && utf8[1]) {
            try {
                return decodeURIComponent(utf8[1]).replace(/"/g, '').trim();
            } catch (_) {
                return utf8[1].replace(/"/g, '').trim();
            }
        }

        const plain = disposition.match(/filename="?([^";]+)"?/i);
        return plain && plain[1] ? plain[1].trim() : '';
    }

    async function downloadPath(path, fallbackName) {
        const response = await fetch(`${API_URL}/${path}`, {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` },
        });

        if (!response.ok) {
            const raw = (await response.text()).replace(/^\uFEFF/, '').trim();
            try {
                const json = raw ? JSON.parse(raw) : {};
                throw new Error(json.message || 'Download non riuscito');
            } catch (_) {
                throw new Error('Download non riuscito');
            }
        }

        const blob = await response.blob();
        const fileName = parseFilename(response.headers.get('Content-Disposition')) || fallbackName;
        const blobUrl = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = blobUrl;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(blobUrl);
    }

    function switchTab(tab) {
        state.activeTab = tab;
        document.querySelectorAll('.tab-btn').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });
        document.querySelectorAll('.tab-pane').forEach((pane) => {
            pane.classList.toggle('active', pane.id === `tab-${tab}`);
        });

        loadActiveTabData().catch((error) => setStatus(error.message, 'error'));
    }
    async function loadStats() {
        const data = await apiJson('stats.php?action=dashboard', { method: 'GET' });
        const stats = data.stats || {};

        el('statUsers').textContent = String(stats.totale_utenti || 0);
        el('statCheckinToday').textContent = String(stats.checkin_oggi || 0);
        el('statCheckinMonth').textContent = String(stats.checkin_mese || 0);
        el('statRevenueMonth').textContent = formatCurrency(stats.incassi_mese || 0);
        el('statPendingPurchases').textContent = String(stats.acquisti_pending || 0);
        if (el('statPendingEnrollments')) {
            el('statPendingEnrollments').textContent = String(stats.iscrizioni_pending || 0);
        }
        el('statPendingDocs').textContent = String(stats.documenti_pending || 0);

        const rows = Array.isArray(data.ultimi_checkin) ? data.ultimi_checkin : [];
        const body = el('overviewBody');
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="6">Nessun check-in recente</td></tr>';
            return;
        }

        body.innerHTML = rows.map((row) => {
            const badge = normalizeStatus(row.fascia_oraria || '-');
            return `
                <tr>
                    <td>${escapeHtml(formatDateTime(row.timestamp))}</td>
                    <td>${escapeHtml(`${row.nome || ''} ${row.cognome || ''}`.trim())}</td>
                    <td>${escapeHtml(row.telefono || '-')}</td>
                    <td>${escapeHtml(row.pacchetto_nome || '-')}</td>
                    <td><code>${escapeHtml(row.qr_code || '-')}</code></td>
                    <td><span class="badge ${badge.cls}">${escapeHtml(badge.text)}</span></td>
                </tr>
            `;
        }).join('');
    }

    async function loadUsers() {
        const q = encodeURIComponent(el('userSearchInput').value.trim());
        const role = encodeURIComponent(el('userRoleFilter').value);
        const active = encodeURIComponent(el('userActiveFilter').value);
        const data = await apiJson(`admin.php?action=users&limit=200&q=${q}&role=${role}&active=${active}`, { method: 'GET' });
        const rows = Array.isArray(data.users) ? data.users : [];
        const body = el('usersBody');

        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="8">Nessun utente trovato</td></tr>';
            return;
        }

        body.innerHTML = rows.map((row) => {
            const activeBadge = row.attivo == 1
                ? '<span class="badge badge-ok">attivo</span>'
                : '<span class="badge badge-danger">disattivo</span>';
            return `
                <tr>
                    <td>${escapeHtml(`${row.nome || ''} ${row.cognome || ''}`.trim())}</td>
                    <td>${escapeHtml(row.email || '')}</td>
                    <td>${escapeHtml(row.ruolo || '')}</td>
                    <td>${activeBadge}</td>
                    <td>${escapeHtml(String(row.ingressi_totali_rimanenti || 0))}</td>
                    <td>${escapeHtml(String(row.totale_checkin || 0))}</td>
                    <td>${escapeHtml(formatDate(row.created_at))}</td>
                    <td>
                        <button class="btn btn-primary" data-action="view-user" data-id="${escapeHtml(row.id)}" type="button">Apri</button>
                        <button class="btn ${row.attivo == 1 ? 'btn-warn' : 'btn-ok'}" data-action="toggle-user" data-id="${escapeHtml(row.id)}" type="button">${row.attivo == 1 ? 'Disattiva' : 'Attiva'}</button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function loadPendingPurchases() {
        const data = await apiJson('pacchetti.php?action=pending', { method: 'GET' });
        const rows = Array.isArray(data.acquisti) ? data.acquisti : [];
        const body = el('pendingPurchasesBody');

        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="9">Nessun acquisto pending</td></tr>';
            return;
        }

        body.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(formatDate(row.data_acquisto))}</td>
                <td><button class="btn btn-primary" data-action="view-user" data-user-id="${escapeHtml(row.user_id)}" type="button">${escapeHtml(`${row.user_nome || ''} ${row.user_cognome || ''}`.trim())}</button></td>
                <td>${escapeHtml(row.pacchetto_nome || '-')}</td>
                <td>${escapeHtml(row.metodo_pagamento || '-')}</td>
                <td><span class="badge badge-warn">pending</span></td>
                <td>${escapeHtml(formatCurrency(row.importo_pagato || 0))}</td>
                <td>${escapeHtml(row.riferimento_pagamento || '-')}</td>
                <td><code>${escapeHtml(row.qr_code || '-')}</code></td>
                <td><button class="btn btn-ok" data-action="confirm-purchase" data-id="${escapeHtml(row.id)}" type="button">Conferma</button></td>
            </tr>
        `).join('');
    }

    async function loadPendingEnrollments() {
        const body = el('pendingEnrollmentsBody');
        if (!body) return;

        try {
            const data = await apiJson('iscrizioni.php?action=list&status=pending', { method: 'GET' });
            const rows = Array.isArray(data.iscrizioni) ? data.iscrizioni : [];

            if (!rows.length) {
                body.innerHTML = '<tr><td colspan="7">Nessuna iscrizione pending</td></tr>';
                return;
            }

            body.innerHTML = rows.map((row) => `
                <tr>
                    <td>${escapeHtml(formatDate(row.submitted_at))}</td>
                    <td>${escapeHtml(`${row.nome || ''} ${row.cognome || ''}`.trim())}</td>
                    <td>${escapeHtml(row.email || '-')}</td>
                    <td>${escapeHtml(row.telefono || '-')}</td>
                    <td>${escapeHtml(row.package_name || '10 Ingressi')}</td>
                    <td><span class="badge badge-warn">pending</span></td>
                    <td>
                        <button class="btn btn-ok" data-action="approve-enrollment" data-id="${escapeHtml(row.id)}" type="button">Approva</button>
                        <button class="btn btn-danger" data-action="reject-enrollment" data-id="${escapeHtml(row.id)}" type="button">Rifiuta</button>
                    </td>
                </tr>
            `).join('');
        } catch (error) {
            body.innerHTML = '<tr><td colspan="7">Errore caricamento iscrizioni</td></tr>';
        }
    }

    async function loadPendingDocuments() {
        const data = await apiJson('documenti.php?action=pending', { method: 'GET' });
        const rows = Array.isArray(data.documenti) ? data.documenti : [];
        const body = el('pendingDocsBody');

        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="7">Nessun documento pending</td></tr>';
            return;
        }

        body.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(formatDate(row.data_caricamento))}</td>
                <td><button class="btn btn-primary" data-action="view-user" data-user-id="${escapeHtml(row.user_id)}" type="button">${escapeHtml(`${row.user_nome || ''} ${row.user_cognome || ''}`.trim())}</button></td>
                <td>${escapeHtml(row.tipo_nome || '-')}</td>
                <td>${row.obbligatorio == 1 ? '<span class="badge badge-warn">si</span>' : '<span class="badge badge-neutral">no</span>'}</td>
                <td><span class="badge badge-warn">pending</span></td>
                <td><button class="btn btn-secondary" data-action="open-doc" data-id="${escapeHtml(row.id)}" data-url="${escapeHtml(row.file_url || '')}" data-name="${escapeHtml(row.file_name || 'documento')}" type="button">Apri</button></td>
                <td>
                    <button class="btn btn-ok" data-action="approve-doc" data-id="${escapeHtml(row.id)}" type="button">Approva</button>
                    <button class="btn btn-danger" data-action="reject-doc" data-id="${escapeHtml(row.id)}" type="button">Rifiuta</button>
                </td>
            </tr>
        `).join('');
    }

    async function loadDailyReport() {
        const date = el('reportDateInput').value || state.reportDate;
        state.reportDate = date;
        const data = await apiJson(`stats.php?action=report-daily&data=${encodeURIComponent(date)}`, { method: 'GET' });

        el('reportSummary').innerHTML = `
            <strong>Data:</strong> ${escapeHtml(date)}
            <br><strong>Totale check-in:</strong> ${escapeHtml(String(data.totale || 0))}
            <br><strong>Mattina:</strong> ${escapeHtml(String(data.mattina || 0))}
            <br><strong>Pomeriggio:</strong> ${escapeHtml(String(data.pomeriggio || 0))}
        `;

        const rows = Array.isArray(data.checkins) ? data.checkins : [];
        const body = el('reportBody');
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="7">Nessun check-in nel giorno selezionato</td></tr>';
            return;
        }

        body.innerHTML = rows.map((row) => `
            <tr>
                <td>${escapeHtml(new Date(row.timestamp).toLocaleTimeString('it-IT', { hour: '2-digit', minute: '2-digit' }))}</td>
                <td>${escapeHtml(`${row.nome || ''} ${row.cognome || ''}`.trim())}</td>
                <td>${escapeHtml(row.telefono || '-')}</td>
                <td>${escapeHtml(row.pacchetto_nome || '-')}</td>
                <td><code>${escapeHtml(row.qr_code || '-')}</code></td>
                <td>${escapeHtml(row.fascia_oraria || '-')}</td>
                <td>${escapeHtml(`${row.bagnino_nome || ''} ${row.bagnino_cognome || ''}`.trim())}</td>
            </tr>
        `).join('');
    }

    async function loadActiveTabData() {
        if (state.activeTab === 'overview') {
            await loadStats();
            return;
        }
        if (state.activeTab === 'users') {
            await Promise.all([loadUsers(), loadPackages()]);
            return;
        }
        if (state.activeTab === 'purchases') {
            await Promise.all([loadPendingEnrollments(), loadPendingPurchases()]);
            return;
        }
        if (state.activeTab === 'documents') {
            await loadPendingDocuments();
            return;
        }
        if (state.activeTab === 'report') {
            await loadDailyReport();
            return;
        }
        if (state.activeTab === 'export') {
            updateExportPreviewMeta();
            updateExportActionButtons();
        }
    }

    function getSelectedExportColumnsFromUi() {
        const checks = document.querySelectorAll('#exportColumnsOptions input[type="checkbox"][data-col-key]');
        const selected = [];
        checks.forEach((node) => {
            if (node.checked) {
                selected.push(node.getAttribute('data-col-key') || '');
            }
        });
        return selected.filter(Boolean);
    }

    function updateExportActionButtons() {
        const csvBtn = el('exportSelectedCsvBtn');
        const pdfBtn = el('exportSelectedPdfBtn');
        if (!csvBtn || !pdfBtn) return;

        const hasRows = Array.isArray(state.exportPreview.rows) && state.exportPreview.rows.length > 0;
        const hasCols = Array.isArray(state.exportPreview.selectedColumns) && state.exportPreview.selectedColumns.length > 0;
        const enabled = hasRows && hasCols;

        csvBtn.disabled = !enabled;
        pdfBtn.disabled = !enabled;
    }

    function updateExportPreviewMeta() {
        const box = el('exportPreviewMeta');
        if (!box) return;

        if (!state.exportPreview.dataset) {
            box.textContent = 'Carica l\'anteprima per vedere la griglia dati prima dell\'export.';
            return;
        }

        const rowsCount = Array.isArray(state.exportPreview.rows) ? state.exportPreview.rows.length : 0;
        const selectedCount = Array.isArray(state.exportPreview.selectedColumns) ? state.exportPreview.selectedColumns.length : 0;
        box.innerHTML = `
            <strong>Dataset:</strong> ${escapeHtml(getExportDatasetLabel(state.exportPreview.dataset))}
            <br><strong>Righe caricate:</strong> ${escapeHtml(String(rowsCount))}
            <br><strong>Colonne selezionate:</strong> ${escapeHtml(String(selectedCount))}
        `;
    }

    function renderExportColumnsOptions() {
        const wrap = el('exportColumnsOptions');
        if (!wrap) return;

        const columns = Array.isArray(state.exportPreview.columns) ? state.exportPreview.columns : [];
        const selected = new Set(state.exportPreview.selectedColumns || []);

        if (!columns.length) {
            wrap.innerHTML = '<p style="grid-column:1/-1; color:#64748b;">Nessun campo disponibile.</p>';
            updateExportActionButtons();
            return;
        }

        wrap.innerHTML = columns.map((column) => `
            <label style="display:flex; gap:8px; align-items:flex-start; border:1px solid #d1d5db; border-radius:8px; padding:8px; background:#fff;">
                <input type="checkbox" data-col-key="${escapeHtml(column.key || '')}" ${selected.has(column.key) ? 'checked' : ''}>
                <span>
                    <strong>${escapeHtml(column.label || column.key || '-')}</strong>
                    <br><small style="color:#64748b;">${escapeHtml(column.key || '')}</small>
                </span>
            </label>
        `).join('');

        wrap.querySelectorAll('input[type="checkbox"][data-col-key]').forEach((node) => {
            node.addEventListener('change', () => {
                state.exportPreview.selectedColumns = getSelectedExportColumnsFromUi();
                renderExportPreviewTable();
                updateExportPreviewMeta();
                updateExportActionButtons();
            });
        });

        updateExportActionButtons();
    }

    function renderExportPreviewTable() {
        const head = el('exportPreviewHead');
        const body = el('exportPreviewBody');
        if (!head || !body) return;

        const rows = Array.isArray(state.exportPreview.rows) ? state.exportPreview.rows : [];
        const columns = Array.isArray(state.exportPreview.columns) ? state.exportPreview.columns : [];
        const selectedSet = new Set(state.exportPreview.selectedColumns || []);
        const visibleColumns = columns.filter((col) => selectedSet.has(col.key));

        if (!rows.length) {
            head.innerHTML = '<th>Anteprima export</th>';
            body.innerHTML = '<tr><td>Nessun dato disponibile per l\'anteprima.</td></tr>';
            return;
        }

        if (!visibleColumns.length) {
            head.innerHTML = '<th>Anteprima export</th>';
            body.innerHTML = '<tr><td>Seleziona almeno una colonna da esportare.</td></tr>';
            return;
        }

        head.innerHTML = visibleColumns.map((col) => `<th>${escapeHtml(col.label || col.key || '')}</th>`).join('');
        const previewRows = rows.slice(0, 100);
        body.innerHTML = previewRows.map((row) => `
            <tr>
                ${visibleColumns.map((col) => `<td>${escapeHtml(row[col.key] ?? '-')}</td>`).join('')}
            </tr>
        `).join('');
    }

    function setAllExportColumns(enabled) {
        const checks = document.querySelectorAll('#exportColumnsOptions input[type="checkbox"][data-col-key]');
        checks.forEach((node) => {
            node.checked = !!enabled;
        });
        state.exportPreview.selectedColumns = getSelectedExportColumnsFromUi();
        renderExportPreviewTable();
        updateExportPreviewMeta();
        updateExportActionButtons();
    }

    async function loadExportPreview() {
        const dataset = (el('exportDatasetSelect') ? el('exportDatasetSelect').value : 'users') || 'users';
        const limitRaw = Number(el('exportPreviewLimit') ? el('exportPreviewLimit').value : 200);
        const limit = Number.isFinite(limitRaw) ? Math.max(50, Math.min(500, limitRaw)) : 200;

        const data = await apiJson(
            `stats.php?action=export-preview&dataset=${encodeURIComponent(dataset)}&limit=${encodeURIComponent(String(limit))}`,
            { method: 'GET' }
        );

        state.exportPreview.dataset = dataset;
        state.exportPreview.columns = Array.isArray(data.columns) ? data.columns : [];
        state.exportPreview.rows = Array.isArray(data.rows) ? data.rows : [];
        state.exportPreview.selectedColumns = state.exportPreview.columns.map((col) => col.key).filter(Boolean);

        renderExportColumnsOptions();
        renderExportPreviewTable();
        updateExportPreviewMeta();
        updateExportActionButtons();
    }

    async function handleExportSelected(format) {
        if (!state.exportPreview.dataset) {
            throw new Error('Carica prima l\'anteprima dati');
        }

        const selected = Array.isArray(state.exportPreview.selectedColumns)
            ? state.exportPreview.selectedColumns.filter(Boolean)
            : [];
        if (!selected.length) {
            throw new Error('Seleziona almeno una colonna da esportare');
        }

        const safeFormat = format === 'pdf' ? 'pdf' : 'csv';
        const path = `stats.php?action=export-custom&dataset=${encodeURIComponent(state.exportPreview.dataset)}&format=${safeFormat}&columns=${encodeURIComponent(selected.join(','))}`;
        const fallbackName = `export_${state.exportPreview.dataset}_${state.reportDate || new Date().toISOString().slice(0, 10)}.${safeFormat}`;

        await handleExport(path, fallbackName);
    }

    function initHeader() {
        if (config.theme) {
            document.body.setAttribute('data-theme', config.theme);
        }
        if (config.title && el('dashboardTitle')) {
            el('dashboardTitle').textContent = config.title;
        }
        const name = `${user.nome || ''} ${user.cognome || ''}`.trim() || user.email || 'Utente';
        if (el('userName')) {
            el('userName').textContent = `${name} (${user.ruolo})`;
        }
        if (el('reportDateInput')) {
            el('reportDateInput').value = state.reportDate;
        }
    }

    function closeAllModals() {
        document.querySelectorAll('.modal.open').forEach((node) => node.classList.remove('open'));
    }

    async function loadPackages(force = false) {
        if (!force && state.packages.length) {
            renderPackageOptions();
            return;
        }
        const data = await apiJson('pacchetti.php?action=admin-list', { method: 'GET' });
        state.packages = Array.isArray(data.pacchetti) ? data.pacchetti : [];
        renderPackageOptions();
    }

    function renderPackageOptions() {
        const select = el('apExistingPackage');
        if (select) {
            if (!state.packages.length) {
                select.innerHTML = '<option value="">Nessun pacchetto disponibile</option>';
            } else {
                select.innerHTML = state.packages.map((pkg) => `
                    <option value="${escapeHtml(String(pkg.id))}">
                        ${escapeHtml(pkg.nome || 'Pacchetto')} - ${escapeHtml(String(pkg.num_ingressi || 0))} ingressi - ${escapeHtml(formatCurrency(pkg.prezzo || 0))}${Number(pkg.attivo) === 1 ? '' : ' [non visibile]'}
                    </option>
                `).join('');
            }
        }

        renderPackagesManagement();
    }

    function renderPackagesManagement() {
        const body = el('packagesManageBody');
        if (!body) return;

        if (!state.packages.length) {
            body.innerHTML = '<tr><td colspan="5">Nessun pacchetto configurato</td></tr>';
            return;
        }

        body.innerHTML = state.packages.map((pkg) => {
            const visible = Number(pkg.attivo) === 1
                ? '<span class="badge badge-ok">visibile</span>'
                : '<span class="badge badge-neutral">nascosto</span>';
            return `
                <tr>
                    <td>${escapeHtml(pkg.nome || '-')}</td>
                    <td>${escapeHtml(String(pkg.num_ingressi || 0))}</td>
                    <td>${escapeHtml(formatCurrency(pkg.prezzo || 0))}</td>
                    <td>${visible}</td>
                    <td>
                        <button class="btn btn-secondary" data-action="edit-package" data-id="${escapeHtml(String(pkg.id))}" type="button">Modifica</button>
                        <button class="btn ${Number(pkg.attivo) === 1 ? 'btn-warn' : 'btn-ok'}" data-action="toggle-package" data-id="${escapeHtml(String(pkg.id))}" type="button">
                            ${Number(pkg.attivo) === 1 ? 'Nascondi' : 'Mostra'}
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderUserDetail(detail) {
        const userInfo = detail.user || {};
        const purchases = Array.isArray(detail.pacchetti) ? detail.pacchetti : [];
        const docs = Array.isArray(detail.documenti) ? detail.documenti : [];
        const checkins = Array.isArray(detail.checkins) ? detail.checkins : [];
        const missing = Array.isArray(detail.documenti_mancanti) ? detail.documenti_mancanti : [];
        const summary = detail.summary || {};
        const fullName = `${userInfo.nome || ''} ${userInfo.cognome || ''}`.trim();

        if (el('userDetailTitle')) {
            el('userDetailTitle').textContent = fullName ? `Dettaglio cliente - ${fullName}` : 'Dettaglio cliente';
        }

        if (el('userSummaryCards')) {
            const remainingEntries = purchases.reduce((acc, item) => acc + Number(item.ingressi_rimanenti || 0), 0);
            el('userSummaryCards').innerHTML = `
                <h4>${escapeHtml(fullName || 'Cliente')}</h4>
                <p><strong>Email:</strong> ${escapeHtml(userInfo.email || '-')}</p>
                <p><strong>Telefono:</strong> ${escapeHtml(userInfo.telefono || '-')}</p>
                <p><strong>Ruolo:</strong> ${escapeHtml(userInfo.ruolo || '-')}</p>
                <p><strong>Stato:</strong> ${Number(userInfo.attivo) === 1 ? 'attivo' : 'disattivo'}</p>
                <p><strong>Email verificata:</strong> ${Number(userInfo.email_verificata) === 1 ? 'si' : 'no'}</p>
                <hr style="border:none; border-top:1px solid #dbe2ea; margin:8px 0;">
                <p><strong>Ingressi disponibili:</strong> ${escapeHtml(String(remainingEntries))}</p>
                <p><strong>Pacchetti:</strong> ${escapeHtml(String(summary.totale_pacchetti || purchases.length || 0))}</p>
                <p><strong>Check-in:</strong> ${escapeHtml(String(summary.totale_checkin || checkins.length || 0))}</p>
                <p><strong>Pagamenti:</strong> ${escapeHtml(String(summary.totale_pagamenti || purchases.length || 0))}</p>
                <p><strong>Documenti pending:</strong> ${escapeHtml(String(summary.documenti_pending || 0))}</p>
                <p><strong>Documenti mancanti:</strong> ${escapeHtml(String(summary.documenti_mancanti || missing.length || 0))}</p>
                <p><strong>Registrazione:</strong> ${escapeHtml(formatDate(userInfo.created_at))}</p>
                <p><strong>Ultimo accesso:</strong> ${escapeHtml(formatDateTime(userInfo.ultimo_accesso))}</p>
            `;
        }

        if (el('missingDocsList')) {
            if (!missing.length) {
                el('missingDocsList').innerHTML = '<li>Nessun documento obbligatorio mancante</li>';
            } else {
                el('missingDocsList').innerHTML = missing.map((item) => `<li>${escapeHtml(item.nome || '-')}</li>`).join('');
            }
        }

        if (el('userPurchasesBody')) {
            if (!purchases.length) {
                el('userPurchasesBody').innerHTML = '<tr><td colspan="7">Nessun acquisto registrato</td></tr>';
            } else {
                el('userPurchasesBody').innerHTML = purchases.map((row) => {
                    const status = normalizeStatus(row.stato_pagamento || '-');
                    let actions = '';
                    if (String(row.stato_pagamento || '').toLowerCase() === 'pending') {
                        actions += `<button class="btn btn-ok" data-action="confirm-purchase" data-id="${escapeHtml(row.id)}" type="button">Conferma</button> `;
                    }
                    if (String(row.stato_pagamento || '').toLowerCase() === 'confirmed') {
                        actions += `<button class="btn btn-secondary" data-action="download-qr" data-acquisto-id="${escapeHtml(row.id)}" type="button">QR PDF</button>`;
                    }
                    if (!actions) actions = '-';

                    return `
                        <tr>
                            <td>${escapeHtml(formatDate(row.data_acquisto))}</td>
                            <td>${escapeHtml(row.pacchetto_nome || '-')}</td>
                            <td><span class="badge ${status.cls}">${escapeHtml(status.text)}</span></td>
                            <td>${escapeHtml(formatCurrency(row.importo_pagato || 0))}</td>
                            <td>${escapeHtml(String(row.ingressi_rimanenti || 0))}</td>
                            <td><code>${escapeHtml(row.qr_code || '-')}</code></td>
                            <td>${actions}</td>
                        </tr>
                    `;
                }).join('');
            }
        }

        if (el('userDocsBody')) {
            if (!docs.length) {
                el('userDocsBody').innerHTML = '<tr><td colspan="4">Nessun documento caricato</td></tr>';
            } else {
                el('userDocsBody').innerHTML = docs.map((row) => {
                    const status = normalizeStatus(row.stato || '-');
                    const fileAction = row.file_url
                        ? `<button class="btn btn-secondary" data-action="open-doc" data-url="${escapeHtml(row.file_url)}" data-name="${escapeHtml(row.file_name || 'documento')}" type="button">Apri</button>`
                        : '-';
                    return `
                        <tr>
                            <td>${escapeHtml(row.tipo_nome || '-')}</td>
                            <td><span class="badge ${status.cls}">${escapeHtml(status.text)}</span></td>
                            <td>${escapeHtml(formatDate(row.data_caricamento))}</td>
                            <td>${fileAction}</td>
                        </tr>
                    `;
                }).join('');
            }
        }

        if (el('userCheckinsBody')) {
            if (!checkins.length) {
                el('userCheckinsBody').innerHTML = '<tr><td colspan="4">Nessun check-in registrato</td></tr>';
            } else {
                el('userCheckinsBody').innerHTML = checkins.map((row) => `
                    <tr>
                        <td>${escapeHtml(formatDateTime(row.timestamp))}</td>
                        <td>${escapeHtml(row.fascia_oraria || '-')}</td>
                        <td>${escapeHtml(row.pacchetto_nome || '-')}</td>
                        <td>${escapeHtml(`${row.operatore_nome || ''} ${row.operatore_cognome || ''}`.trim() || '-')}</td>
                    </tr>
                `).join('');
            }
        }
    }

    async function openUserDetail(userId) {
        if (!userId) return;
        const data = await apiJson(`admin.php?action=user-detail&id=${encodeURIComponent(userId)}`, { method: 'GET' });
        state.selectedUserId = userId;
        state.userDetail = data;
        if (el('apUserId')) el('apUserId').value = userId;
        renderUserDetail(data);
        openModal('userDetailModal');
    }

    function setAssignModeUI() {
        const mode = el('apMode') ? el('apMode').value : 'existing';
        const existing = el('apExistingGroup');
        const custom = el('apCustomGroup');
        if (existing) existing.style.display = mode === 'existing' ? '' : 'none';
        if (custom) custom.style.display = mode === 'custom' ? '' : 'none';
    }

    function openDocument(url) {
        if (!url) {
            setStatus('URL documento non disponibile', 'error');
            return;
        }
        const popup = window.open(url, '_blank', 'noopener');
        if (!popup) {
            setStatus('Popup bloccato dal browser. Consenti i popup e riprova.', 'error');
        }
    }

    async function handleCreateUser(event) {
        event.preventDefault();
        const payload = {
            nome: (el('cuName') ? el('cuName').value : '').trim(),
            cognome: (el('cuSurname') ? el('cuSurname').value : '').trim(),
            email: (el('cuEmail') ? el('cuEmail').value : '').trim(),
            telefono: (el('cuPhone') ? el('cuPhone').value : '').trim(),
            password: el('cuPassword') ? el('cuPassword').value : '',
            ruolo: el('cuRole') ? el('cuRole').value : 'utente',
        };

        const data = await apiJson('admin.php?action=create-user', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        setStatus(data.message || 'Utente creato', 'ok');
        if (el('createUserForm')) el('createUserForm').reset();
        closeModal('createUserModal');
        await Promise.all([loadUsers(), loadStats()]);
    }

    async function handleCreatePackage(event) {
        event.preventDefault();
        const payload = {
            nome: (el('cpName') ? el('cpName').value : '').trim(),
            descrizione: (el('cpDescription') ? el('cpDescription').value : '').trim(),
            num_ingressi: Number(el('cpEntries') ? el('cpEntries').value : 0),
            prezzo: Number(el('cpPrice') ? el('cpPrice').value : 0),
            validita_giorni: Number(el('cpValidity') ? el('cpValidity').value : 0),
            ordine: Number(el('cpOrder') ? el('cpOrder').value : 0),
            attivo: Number(el('cpActive') ? el('cpActive').value : 1) === 1,
        };

        const data = await apiJson('pacchetti.php?action=admin-create-package', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        setStatus(data.message || 'Pacchetto creato', 'ok');
        if (el('createPackageForm')) el('createPackageForm').reset();
        closeModal('createPackageModal');
        await loadPackages(true);
    }

    async function handleAssignPackage(event) {
        event.preventDefault();
        const userId = (el('apUserId') ? el('apUserId').value : '').trim();
        if (!userId) {
            throw new Error('Utente non selezionato');
        }

        const mode = el('apMode') ? el('apMode').value : 'existing';
        const payload = {
            user_id: userId,
            package_mode: mode,
            metodo_pagamento: el('apPaymentMethod') ? el('apPaymentMethod').value : 'contanti',
            stato_pagamento: el('apPaymentStatus') ? el('apPaymentStatus').value : 'confirmed',
            riferimento_pagamento: (el('apPaymentReference') ? el('apPaymentReference').value : '').trim(),
            note_pagamento: (el('apPaymentNote') ? el('apPaymentNote').value : '').trim(),
            send_email: (el('apSendEmail') ? el('apSendEmail').value : '1') === '1',
        };

        const importoRaw = (el('apImporto') ? el('apImporto').value : '').trim();
        if (importoRaw !== '') {
            payload.importo_pagato = Number(importoRaw);
        }

        if (mode === 'existing') {
            payload.pacchetto_id = Number(el('apExistingPackage') ? el('apExistingPackage').value : 0);
        } else {
            payload.custom_package = {
                kind: el('apCustomKind') ? el('apCustomKind').value : 'personalizzato',
                nome: (el('apCustomName') ? el('apCustomName').value : '').trim(),
                descrizione: (el('apCustomDescription') ? el('apCustomDescription').value : '').trim(),
                num_ingressi: Number(el('apCustomEntries') ? el('apCustomEntries').value : 0),
                prezzo: Number(el('apCustomPrice') ? el('apCustomPrice').value : 0),
                validita_giorni: Number(el('apCustomValidity') ? el('apCustomValidity').value : 0),
                listed: Number(el('apCustomListed') ? el('apCustomListed').value : 0) === 1,
            };
        }

        const data = await apiJson('pacchetti.php?action=admin-assign-manual', {
            method: 'POST',
            body: JSON.stringify(payload),
        });

        setStatus(`${data.message || 'Pacchetto assegnato'}${data.qr_code ? ` | QR: ${data.qr_code}` : ''}`, 'ok');

        if (el('assignPackageForm')) el('assignPackageForm').reset();
        if (el('apUserId')) el('apUserId').value = userId;
        setAssignModeUI();
        closeModal('assignPackageModal');

        await Promise.all([loadStats(), loadPackages(true), loadPendingPurchases(), loadPendingEnrollments()]);
        await openUserDetail(userId);
    }

    async function handleToggleUser(userId) {
        if (!userId) return;
        if (!window.confirm('Confermare cambio stato utente?')) return;

        const data = await apiJson(`admin.php?action=toggle-user&id=${encodeURIComponent(userId)}`, {
            method: 'PATCH',
            body: JSON.stringify({}),
        });

        setStatus(data.message || 'Stato utente aggiornato', 'ok');
        await Promise.all([loadUsers(), loadStats()]);
    }

    async function handleConfirmPurchase(acquistoId) {
        if (!acquistoId) return;
        if (!window.confirm('Confermare pagamento e generare QR?')) return;

        const data = await apiJson(`pacchetti.php?action=confirm&id=${encodeURIComponent(acquistoId)}`, {
            method: 'PATCH',
            body: JSON.stringify({}),
        });

        setStatus(`${data.message || 'Pagamento confermato'}${data.qr_code ? ` | QR: ${data.qr_code}` : ''}`, 'ok');
        await Promise.all([loadStats(), loadPendingPurchases(), loadPendingEnrollments()]);
        if (state.selectedUserId) {
            await openUserDetail(state.selectedUserId);
        }
    }

    async function handleReviewEnrollment(enrollmentId, approve) {
        if (!enrollmentId) return;
        let note = '';
        if (!approve) {
            note = window.prompt('Motivo rifiuto iscrizione (facoltativo):', '') || '';
        }

        const data = await apiJson(`iscrizioni.php?action=review&id=${encodeURIComponent(enrollmentId)}`, {
            method: 'PATCH',
            body: JSON.stringify({
                stato: approve ? 'approved' : 'rejected',
                note_revisione: note,
            }),
        });

        setStatus(data.message || 'Iscrizione aggiornata', 'ok');
        await Promise.all([loadStats(), loadPendingEnrollments(), loadPendingPurchases(), loadUsers()]);
    }

    async function handleTogglePackage(packageId) {
        if (!packageId) return;
        const data = await apiJson(`pacchetti.php?action=admin-toggle-package&id=${encodeURIComponent(packageId)}`, {
            method: 'PATCH',
            body: JSON.stringify({}),
        });
        setStatus(data.message || 'Visibilita pacchetto aggiornata', 'ok');
        await loadPackages(true);
    }

    async function handleEditPackage(packageId) {
        if (!packageId) return;
        const pkg = state.packages.find((item) => String(item.id) === String(packageId));
        if (!pkg) {
            throw new Error('Pacchetto non trovato');
        }

        const nome = window.prompt('Nome pacchetto', pkg.nome || '');
        if (!nome || !nome.trim()) {
            return;
        }
        const entriesRaw = window.prompt('Numero ingressi', String(pkg.num_ingressi || 0));
        const priceRaw = window.prompt('Prezzo EUR', String(pkg.prezzo || 0));

        const numIngressi = Number(entriesRaw);
        const prezzo = Number(priceRaw);
        if (!Number.isFinite(numIngressi) || numIngressi <= 0 || !Number.isFinite(prezzo) || prezzo < 0) {
            throw new Error('Valori pacchetto non validi');
        }

        const payload = {
            nome: nome.trim(),
            descrizione: pkg.descrizione || '',
            num_ingressi: numIngressi,
            prezzo: prezzo,
            attivo: Number(pkg.attivo) === 1,
        };

        const data = await apiJson(`pacchetti.php?action=admin-update-package&id=${encodeURIComponent(packageId)}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
        });

        setStatus(data.message || 'Pacchetto aggiornato', 'ok');
        await loadPackages(true);
    }

    async function handleReviewDocument(documentId, approve) {
        if (!documentId) return;
        let note = '';
        if (!approve) {
            note = window.prompt('Motivo rifiuto (facoltativo):', '') || '';
        }

        const data = await apiJson(`documenti.php?action=review&id=${encodeURIComponent(documentId)}`, {
            method: 'PATCH',
            body: JSON.stringify({
                stato: approve ? 'approved' : 'rejected',
                note_revisione: note,
            }),
        });

        setStatus(data.message || 'Documento aggiornato', 'ok');
        await Promise.all([loadStats(), loadPendingDocuments()]);
        if (state.selectedUserId) {
            await openUserDetail(state.selectedUserId);
        }
    }

    async function handleSendReminder() {
        if (!state.selectedUserId) {
            throw new Error('Seleziona prima un cliente');
        }
        const data = await apiJson(`admin.php?action=send-doc-reminder&id=${encodeURIComponent(state.selectedUserId)}`, {
            method: 'POST',
            body: JSON.stringify({}),
        });
        setStatus(data.message || 'Promemoria inviato', 'ok');
        await openUserDetail(state.selectedUserId);
    }

    async function handleDownloadQr(acquistoId) {
        if (!acquistoId) {
            throw new Error('ID acquisto mancante');
        }
        await downloadPath(
            `qr.php?action=download&acquisto_id=${encodeURIComponent(acquistoId)}`,
            `QR_${acquistoId}.pdf`
        );
        setStatus('Download QR avviato', 'ok');
    }

    async function handleExport(path, fallbackName) {
        await downloadPath(path, fallbackName);
        setStatus('Download avviato', 'ok');
    }

    function bindModals() {
        document.querySelectorAll('[data-modal-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const id = button.getAttribute('data-modal-close');
                if (id) closeModal(id);
            });
        });

        document.querySelectorAll('.modal').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal(modal.id);
                }
            });
        });
    }

    function bindStaticEvents() {
        document.querySelectorAll('.tab-btn').forEach((button) => {
            button.addEventListener('click', () => switchTab(button.dataset.tab || 'overview'));
        });

        if (el('logoutBtn')) {
            el('logoutBtn').addEventListener('click', () => {
                localStorage.clear();
                window.location.href = '../login.html';
            });
        }

        if (el('goCmsBtn')) {
            el('goCmsBtn').addEventListener('click', () => {
                window.location.href = config.cmsUrl || 'dashboard-contenuti.html';
            });
        }

        if (el('goCmsBuilderBtn')) {
            el('goCmsBuilderBtn').addEventListener('click', () => {
                window.location.href = config.cmsBuilderUrl || 'dashboard-cms-builder.html';
            });
        }

        if (el('userSearchBtn')) {
            el('userSearchBtn').addEventListener('click', () => {
                loadUsers().catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('userSearchInput')) {
            el('userSearchInput').addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    loadUsers().catch((error) => setStatus(error.message, 'error'));
                }
            });
        }

        if (el('createUserBtn')) {
            el('createUserBtn').addEventListener('click', () => openModal('createUserModal'));
        }

        if (el('createPackageBtn')) {
            el('createPackageBtn').addEventListener('click', async () => {
                await loadPackages().catch(() => null);
                openModal('createPackageModal');
            });
        }

        if (el('createUserForm')) {
            el('createUserForm').addEventListener('submit', (event) => {
                handleCreateUser(event).catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('createPackageForm')) {
            el('createPackageForm').addEventListener('submit', (event) => {
                handleCreatePackage(event).catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('assignPackageForm')) {
            el('assignPackageForm').addEventListener('submit', (event) => {
                handleAssignPackage(event).catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('apMode')) {
            el('apMode').addEventListener('change', setAssignModeUI);
            setAssignModeUI();
        }

        if (el('openAssignBtn')) {
            el('openAssignBtn').addEventListener('click', async () => {
                if (!state.selectedUserId) {
                    setStatus('Seleziona prima un cliente', 'error');
                    return;
                }
                await loadPackages().catch((error) => setStatus(error.message, 'error'));
                if (el('apUserId')) el('apUserId').value = state.selectedUserId;
                openModal('assignPackageModal');
            });
        }

        if (el('sendReminderBtn')) {
            el('sendReminderBtn').addEventListener('click', () => {
                handleSendReminder().catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('loadReportBtn')) {
            el('loadReportBtn').addEventListener('click', () => {
                loadDailyReport().catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('reportDateInput')) {
            el('reportDateInput').addEventListener('change', () => {
                state.reportDate = el('reportDateInput').value || state.reportDate;
            });
        }

        if (el('downloadReportCsvBtn')) {
            el('downloadReportCsvBtn').addEventListener('click', () => {
                handleExport(
                    `stats.php?action=report-daily-csv&data=${encodeURIComponent(state.reportDate)}`,
                    `report_checkin_${state.reportDate}.csv`
                ).catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('downloadReportPdfBtn')) {
            el('downloadReportPdfBtn').addEventListener('click', () => {
                handleExport(
                    `stats.php?action=report-daily-pdf&data=${encodeURIComponent(state.reportDate)}`,
                    `report_checkin_${state.reportDate}.pdf`
                ).catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('loadExportPreviewBtn')) {
            el('loadExportPreviewBtn').addEventListener('click', () => {
                loadExportPreview()
                    .then(() => setStatus('Anteprima export caricata', 'ok'))
                    .catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('selectAllExportColumnsBtn')) {
            el('selectAllExportColumnsBtn').addEventListener('click', () => {
                setAllExportColumns(true);
            });
        }

        if (el('clearExportColumnsBtn')) {
            el('clearExportColumnsBtn').addEventListener('click', () => {
                setAllExportColumns(false);
            });
        }

        if (el('exportDatasetSelect')) {
            el('exportDatasetSelect').addEventListener('change', () => {
                state.exportPreview = {
                    dataset: '',
                    columns: [],
                    rows: [],
                    selectedColumns: [],
                };
                renderExportColumnsOptions();
                renderExportPreviewTable();
                updateExportPreviewMeta();
            });
        }

        if (el('exportSelectedCsvBtn')) {
            el('exportSelectedCsvBtn').addEventListener('click', () => {
                handleExportSelected('csv').catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('exportSelectedPdfBtn')) {
            el('exportSelectedPdfBtn').addEventListener('click', () => {
                handleExportSelected('pdf').catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('exportUsersCsvBtn')) {
            el('exportUsersCsvBtn').addEventListener('click', () => {
                handleExport('stats.php?action=export-users', `utenti_${state.reportDate}.csv`)
                    .catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('exportUsersPdfBtn')) {
            el('exportUsersPdfBtn').addEventListener('click', () => {
                handleExport('stats.php?action=export-users-pdf', `utenti_${state.reportDate}.pdf`)
                    .catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('exportPurchasesCsvBtn')) {
            el('exportPurchasesCsvBtn').addEventListener('click', () => {
                handleExport('stats.php?action=export-purchases', `acquisti_${state.reportDate}.csv`)
                    .catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('exportPurchasesPdfBtn')) {
            el('exportPurchasesPdfBtn').addEventListener('click', () => {
                handleExport('stats.php?action=export-purchases-pdf', `acquisti_${state.reportDate}.pdf`)
                    .catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('exportCheckinsCsvBtn')) {
            el('exportCheckinsCsvBtn').addEventListener('click', () => {
                handleExport('stats.php?action=export-checkins', `checkins_${state.reportDate}.csv`)
                    .catch((error) => setStatus(error.message, 'error'));
            });
        }

        if (el('exportCheckinsPdfBtn')) {
            el('exportCheckinsPdfBtn').addEventListener('click', () => {
                handleExport('stats.php?action=export-checkins-pdf', `checkins_${state.reportDate}.pdf`)
                    .catch((error) => setStatus(error.message, 'error'));
            });
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeAllModals();
            }
        });
    }

    function bindDynamicActions() {
        document.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-action]');
            if (!button) return;

            const action = button.getAttribute('data-action');
            const userId = button.getAttribute('data-user-id') || '';
            const id = button.getAttribute('data-id') || '';
            const acquistoId = button.getAttribute('data-acquisto-id') || '';
            const url = button.getAttribute('data-url') || '';

            if (action === 'view-user') {
                openUserDetail(userId || id).catch((error) => setStatus(error.message, 'error'));
                return;
            }

            if (action === 'toggle-user') {
                handleToggleUser(userId || id).catch((error) => setStatus(error.message, 'error'));
                return;
            }

            if (action === 'confirm-purchase') {
                handleConfirmPurchase(id).catch((error) => setStatus(error.message, 'error'));
                return;
            }

            if (action === 'approve-enrollment') {
                handleReviewEnrollment(id, true).catch((error) => setStatus(error.message, 'error'));
                return;
            }

            if (action === 'reject-enrollment') {
                handleReviewEnrollment(id, false).catch((error) => setStatus(error.message, 'error'));
                return;
            }

            if (action === 'approve-doc') {
                handleReviewDocument(id, true).catch((error) => setStatus(error.message, 'error'));
                return;
            }

            if (action === 'reject-doc') {
                handleReviewDocument(id, false).catch((error) => setStatus(error.message, 'error'));
                return;
            }

            if (action === 'open-doc') {
                openDocument(url);
                return;
            }

            if (action === 'download-qr') {
                handleDownloadQr(acquistoId || id).catch((error) => setStatus(error.message, 'error'));
                return;
            }

            if (action === 'toggle-package') {
                handleTogglePackage(id).catch((error) => setStatus(error.message, 'error'));
                return;
            }

            if (action === 'edit-package') {
                handleEditPackage(id).catch((error) => setStatus(error.message, 'error'));
            }
        });
    }

    async function bootstrap() {
        initHeader();
        bindModals();
        bindStaticEvents();
        bindDynamicActions();
        renderExportColumnsOptions();
        renderExportPreviewTable();
        updateExportPreviewMeta();
        updateExportActionButtons();
        await Promise.all([loadPackages().catch(() => null), loadStats()]);
    }

    bootstrap().catch((error) => {
        setStatus(error.message || 'Errore caricamento dashboard', 'error');
    });
})();

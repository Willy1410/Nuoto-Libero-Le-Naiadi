<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ufficio - Gli Squaletti</title>
    <style>
        :root {
            --accent: #dc2626;
            --accent-dark: #991b1b;
            --bg: #f3f5f9;
            --text: #1f2937;
            --muted: #64748b;
            --card: #ffffff;
            --line: #e5e7eb;
            --ok: #16a34a;
            --warn: #d97706;
            --danger: #dc2626;
            --radius: 12px;
        }

        body[data-theme="office"] {
            --accent: #f59e0b;
            --accent-dark: #d97706;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Segoe UI", Arial, sans-serif; background: var(--bg); color: var(--text); }

        .header {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #fff;
            padding: 18px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .header h1 { font-size: 24px; }
        .header small { opacity: 0.92; }
        .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .btn {
            border: none;
            border-radius: 8px;
            padding: 9px 13px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-secondary { background: #334155; color: #fff; }
        .btn-ok { background: var(--ok); color: #fff; }
        .btn-warn { background: var(--warn); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }

        .container { max-width: 1500px; margin: 20px auto; padding: 0 16px; }
        .card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.08);
            padding: 16px;
            margin-bottom: 16px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #fff;
            border-radius: 10px;
            padding: 14px;
            text-align: center;
        }
        .stat h3 { font-size: 28px; margin-bottom: 4px; }
        .stat p { font-size: 13px; opacity: 0.95; }

        .tabs { display: flex; gap: 6px; flex-wrap: wrap; border-bottom: 1px solid var(--line); padding-bottom: 8px; }
        .tab-btn {
            border: 1px solid var(--line);
            background: #fff;
            color: #334155;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .tab-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }
        .tab-pane { display: none; margin-top: 14px; }
        .tab-pane.active { display: block; }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }
        .toolbar input,
        .toolbar select {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 13px;
            min-width: 170px;
            background: #fff;
        }

        .status-box {
            display: none;
            margin-top: 8px;
            border-radius: 8px;
            padding: 9px 11px;
            font-size: 13px;
            font-weight: 600;
        }
        .status-box.ok { display: block; background: #dcfce7; color: #166534; }
        .status-box.err { display: block; background: #fee2e2; color: #991b1b; }

        .table-wrap {
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
        }
        table { width: 100%; border-collapse: collapse; min-width: 920px; }
        th, td { padding: 9px 8px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; font-size: 13px; }
        th { background: #f8fafc; font-weight: 700; }

        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-ok { background: #dcfce7; color: #166534; }
        .badge-warn { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-neutral { background: #e2e8f0; color: #334155; }

        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 1000;
        }
        .modal.open { display: flex; }
        .modal-panel {
            width: 100%;
            max-width: 980px;
            max-height: 92vh;
            overflow: auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 24px 48px rgba(15, 23, 42, 0.25);
            padding: 16px;
        }
        .modal-sm { max-width: 620px; }
        .modal h3 { margin-bottom: 10px; }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
        }
        .field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #334155;
        }
        .field input,
        .field select,
        .field textarea {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 13px;
            background: #fff;
        }
        .field textarea { min-height: 82px; resize: vertical; }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .split {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 10px;
        }
        .detail-block {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
            background: #f8fafc;
        }
        .detail-block h4 { margin-bottom: 8px; font-size: 14px; }
        .mini-list { list-style: none; padding-left: 0; }
        .mini-list li { padding: 4px 0; font-size: 13px; border-bottom: 1px dashed #dbe2ea; }

        @media (max-width: 1080px) {
            .split { grid-template-columns: 1fr; }
            .grid-3 { grid-template-columns: 1fr; }
            table { min-width: 760px; }
        }
        @media (max-width: 720px) {
            .grid-2 { grid-template-columns: 1fr; }
            .container { margin: 12px auto; padding: 0 10px; }
            .header { padding: 14px 12px; }
            .header h1 { font-size: 20px; }
            .header-actions { width: 100%; }
            .header-actions .btn { flex: 1 1 140px; }
            .tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                padding-bottom: 10px;
            }
            .tab-btn {
                flex: 0 0 auto;
                white-space: nowrap;
            }
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .stat h3 { font-size: 22px; }
            .toolbar {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }
            .toolbar input,
            .toolbar select,
            .toolbar .btn {
                width: 100%;
                min-width: 0;
            }
            table { min-width: 640px; }
            .modal { padding: 8px; }
            .modal-panel { padding: 12px; max-height: 95vh; }
            .modal-actions { justify-content: stretch; }
            .modal-actions .btn { flex: 1 1 140px; }
        }
        @media (max-width: 520px) {
            .toolbar { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .header-actions .btn { flex: 1 1 100%; }
        }
    </style>
</head>
<body data-theme="office">
    <div class="header">
        <div>
            <h1 id="dashboardTitle">Dashboard Ufficio</h1>
            <small id="userName">Utente</small>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" id="goCmsBtn" type="button">CMS Contenuti</button>
            <button class="btn btn-secondary" id="goCmsBuilderBtn" type="button">CMS Builder-ready</button>
            <button class="btn btn-danger" id="logoutBtn" type="button">Esci</button>
        </div>
    </div>

    <div class="container">
        <div class="stats-grid">
            <div class="stat"><h3 id="statUsers">0</h3><p>Utenti Attivi</p></div>
            <div class="stat"><h3 id="statCheckinToday">0</h3><p>Check-in Oggi</p></div>
            <div class="stat"><h3 id="statCheckinMonth">0</h3><p>Check-in Mese</p></div>
            <div class="stat"><h3 id="statRevenueMonth">EUR 0.00</h3><p>Incassi Mese</p></div>
            <div class="stat"><h3 id="statPendingPurchases">0</h3><p>Acquisti Pending</p></div>
            <div class="stat"><h3 id="statPendingEnrollments">0</h3><p>Iscrizioni Pending</p></div>
            <div class="stat"><h3 id="statPendingDocs">0</h3><p>Documenti Pending</p></div>
        </div>

        <div class="card">
            <div class="tabs">
                <button class="tab-btn active" data-tab="overview" type="button">Panoramica</button>
                <button class="tab-btn" data-tab="users" type="button">Clienti</button>
                <button class="tab-btn" data-tab="purchases" type="button">Acquisti</button>
                <button class="tab-btn" data-tab="documents" type="button">Documenti</button>
                <button class="tab-btn" data-tab="report" type="button">Report</button>
                <button class="tab-btn" data-tab="export" type="button">Export</button>
            </div>

            <div id="globalStatus" class="status-box"></div>

            <div id="tab-overview" class="tab-pane active">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Data/Ora</th>
                                <th>Utente</th>
                                <th>Telefono</th>
                                <th>Pacchetto</th>
                                <th>QR</th>
                                <th>Fascia</th>
                            </tr>
                        </thead>
                        <tbody id="overviewBody"></tbody>
                    </table>
                </div>
            </div>

            <div id="tab-users" class="tab-pane">
                <div class="toolbar">
                    <input id="userSearchInput" type="text" placeholder="Cerca per nome, email, telefono...">
                    <select id="userRoleFilter">
                        <option value="">Tutti i ruoli</option>
                        <option value="utente">Utente</option>
                        <option value="bagnino">Bagnino</option>
                        <option value="ufficio">Ufficio</option>
                        <option value="segreteria">Segreteria</option>
                        <option value="admin">Admin</option>
                    </select>
                    <select id="userActiveFilter">
                        <option value="">Tutti gli stati</option>
                        <option value="1">Attivi</option>
                        <option value="0">Disattivi</option>
                    </select>
                    <button class="btn btn-primary" id="userSearchBtn" type="button">Filtra</button>
                    <button class="btn btn-ok" id="createUserBtn" type="button">Nuovo utente</button>
                    <button class="btn btn-warn" id="createPackageBtn" type="button">Nuovo pacchetto</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Ruolo</th>
                                <th>Attivo</th>
                                <th>Ingressi</th>
                                <th>Check-in</th>
                                <th>Registrazione</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="usersBody"></tbody>
                    </table>
                </div>
                <div class="detail-block" style="margin-top:10px;">
                    <h4>Gestione pacchetti dinamici</h4>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Ingressi</th>
                                    <th>Prezzo</th>
                                    <th>Visibile</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody id="packagesManageBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="tab-purchases" class="tab-pane">
                <h4 style="margin-bottom:8px;">Iscrizioni da approvare</h4>
                <div class="table-wrap" style="margin-bottom:12px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Telefono</th>
                                <th>Pacchetto</th>
                                <th>Stato</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="pendingEnrollmentsBody"></tbody>
                    </table>
                </div>

                <h4 style="margin-bottom:8px;">Acquisti pending</h4>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Utente</th>
                                <th>Pacchetto</th>
                                <th>Metodo</th>
                                <th>Stato</th>
                                <th>Importo</th>
                                <th>Riferimento</th>
                                <th>QR</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="pendingPurchasesBody"></tbody>
                    </table>
                </div>
            </div>

            <div id="tab-documents" class="tab-pane">
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Utente</th>
                                <th>Tipo</th>
                                <th>Obbligatorio</th>
                                <th>Stato</th>
                                <th>File</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="pendingDocsBody"></tbody>
                    </table>
                </div>
            </div>

            <div id="tab-report" class="tab-pane">
                <div class="toolbar">
                    <input id="reportDateInput" type="date">
                    <button class="btn btn-primary" id="loadReportBtn" type="button">Carica report</button>
                    <button class="btn btn-ok" id="downloadReportCsvBtn" type="button">CSV giornaliero</button>
                    <button class="btn btn-secondary" id="downloadReportPdfBtn" type="button">PDF giornaliero</button>
                </div>
                <div id="reportSummary" class="detail-block"></div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Ora</th>
                                <th>Utente</th>
                                <th>Telefono</th>
                                <th>Pacchetto</th>
                                <th>QR</th>
                                <th>Fascia</th>
                                <th>Bagnino</th>
                            </tr>
                        </thead>
                        <tbody id="reportBody"></tbody>
                    </table>
                </div>
            </div>

            <div id="tab-export" class="tab-pane">
                <div class="detail-block">
                    <h4>Export guidato</h4>
                    <div class="toolbar">
                        <select id="exportDatasetSelect">
                            <option value="users">Clienti (utenti)</option>
                            <option value="purchases">Acquisti</option>
                            <option value="checkins">Check-in</option>
                        </select>
                        <select id="exportPreviewLimit">
                            <option value="100">Anteprima 100 righe</option>
                            <option value="200" selected>Anteprima 200 righe</option>
                            <option value="300">Anteprima 300 righe</option>
                            <option value="500">Anteprima 500 righe</option>
                        </select>
                        <button class="btn btn-primary" id="loadExportPreviewBtn" type="button">Carica anteprima</button>
                        <button class="btn btn-secondary" id="selectAllExportColumnsBtn" type="button">Seleziona colonne</button>
                        <button class="btn btn-secondary" id="clearExportColumnsBtn" type="button">Deseleziona tutto</button>
                    </div>

                    <div id="exportPreviewMeta" class="detail-block" style="margin-bottom:8px;">
                        Carica l'anteprima per vedere la griglia dati prima dell'export.
                    </div>

                    <div class="detail-block">
                        <h4 style="margin-bottom:6px;">Campi da esportare</h4>
                        <div id="exportColumnsOptions" class="grid-3"></div>
                    </div>

                    <div class="table-wrap" style="margin-top:8px;">
                        <table>
                            <thead>
                                <tr id="exportPreviewHead">
                                    <th>Anteprima export</th>
                                </tr>
                            </thead>
                            <tbody id="exportPreviewBody">
                                <tr><td>Nessuna anteprima caricata.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="toolbar" style="margin-top:10px;">
                        <button class="btn btn-ok" id="exportSelectedCsvBtn" type="button" disabled>Esporta selezione CSV</button>
                        <button class="btn btn-secondary" id="exportSelectedPdfBtn" type="button" disabled>Esporta selezione PDF</button>
                    </div>
                </div>

                <h4 style="margin:8px 0;">Export rapido completo</h4>
                <div class="toolbar">
                    <button class="btn btn-ok" id="exportUsersCsvBtn" type="button">Utenti CSV</button>
                    <button class="btn btn-secondary" id="exportUsersPdfBtn" type="button">Utenti PDF</button>
                    <button class="btn btn-ok" id="exportPurchasesCsvBtn" type="button">Acquisti CSV</button>
                    <button class="btn btn-secondary" id="exportPurchasesPdfBtn" type="button">Acquisti PDF</button>
                    <button class="btn btn-ok" id="exportCheckinsCsvBtn" type="button">Check-in CSV</button>
                    <button class="btn btn-secondary" id="exportCheckinsPdfBtn" type="button">Check-in PDF</button>
                </div>
                <p style="margin-top:8px; color: var(--muted); font-size:13px;">I CSV sono esportati con separatore `;` e BOM UTF-8 per apertura corretta in Excel.</p>
            </div>
        </div>
    </div>

    <div id="createUserModal" class="modal">
        <div class="modal-panel modal-sm">
            <h3>Crea nuovo utente</h3>
            <form id="createUserForm">
                <div class="grid-2">
                    <div class="field"><label for="cuName">Nome</label><input id="cuName" required></div>
                    <div class="field"><label for="cuSurname">Cognome</label><input id="cuSurname" required></div>
                    <div class="field"><label for="cuEmail">Email</label><input id="cuEmail" type="email" required></div>
                    <div class="field"><label for="cuPhone">Telefono</label><input id="cuPhone"></div>
                    <div class="field"><label for="cuPassword">Password</label><input id="cuPassword" type="password" minlength="8" required></div>
                    <div class="field">
                        <label for="cuRole">Ruolo</label>
                        <select id="cuRole">
                            <option value="utente">utente</option>
                            <option value="bagnino">bagnino</option>
                            <option value="ufficio">ufficio</option>
                            <option value="segreteria">segreteria</option>
                            <option value="admin">admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button class="btn" type="button" data-modal-close="createUserModal">Annulla</button>
                    <button class="btn btn-ok" type="submit">Crea utente</button>
                </div>
            </form>
        </div>
    </div>

    <div id="createPackageModal" class="modal">
        <div class="modal-panel modal-sm">
            <h3>Crea nuovo pacchetto</h3>
            <form id="createPackageForm">
                <div class="grid-2">
                    <div class="field"><label for="cpName">Nome pacchetto</label><input id="cpName" required></div>
                    <div class="field"><label for="cpEntries">Numero ingressi</label><input id="cpEntries" type="number" min="1" required></div>
                    <div class="field"><label for="cpPrice">Prezzo EUR</label><input id="cpPrice" type="number" min="0" step="0.01" required></div>
                    <div class="field"><label for="cpValidity">Validita giorni</label><input id="cpValidity" type="number" min="1" required></div>
                    <div class="field"><label for="cpOrder">Ordine</label><input id="cpOrder" type="number" min="0" value="0"></div>
                    <div class="field">
                        <label for="cpActive">Visibile nello shop</label>
                        <select id="cpActive">
                            <option value="1">Si</option>
                            <option value="0">No (solo assegnazione manuale)</option>
                        </select>
                    </div>
                </div>
                <div class="field" style="margin-top:8px;"><label for="cpDescription">Descrizione</label><textarea id="cpDescription"></textarea></div>
                <div class="modal-actions">
                    <button class="btn" type="button" data-modal-close="createPackageModal">Annulla</button>
                    <button class="btn btn-ok" type="submit">Crea pacchetto</button>
                </div>
            </form>
        </div>
    </div>

    <div id="userDetailModal" class="modal">
        <div class="modal-panel">
            <h3 id="userDetailTitle">Dettaglio cliente</h3>
            <div class="split">
                <div>
                    <div id="userSummaryCards" class="detail-block"></div>
                    <div class="detail-block">
                        <h4>Documenti mancanti</h4>
                        <ul id="missingDocsList" class="mini-list"></ul>
                        <div class="modal-actions" style="justify-content:flex-start;">
                            <button class="btn btn-primary" id="sendReminderBtn" type="button">Invia promemoria documenti</button>
                            <button class="btn btn-ok" id="openAssignBtn" type="button">Assegna pacchetto</button>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="detail-block">
                        <h4>Pacchetti / pagamenti / QR</h4>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Pacchetto</th>
                                        <th>Stato</th>
                                        <th>Importo</th>
                                        <th>Ingressi</th>
                                        <th>QR</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody id="userPurchasesBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="detail-block">
                        <h4>Documenti cliente</h4>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Stato</th>
                                        <th>Data</th>
                                        <th>Azioni</th>
                                    </tr>
                                </thead>
                                <tbody id="userDocsBody"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="detail-block">
                        <h4>Storico check-in</h4>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Data/Ora</th>
                                        <th>Fascia</th>
                                        <th>Pacchetto</th>
                                        <th>Operatore</th>
                                    </tr>
                                </thead>
                                <tbody id="userCheckinsBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn" type="button" data-modal-close="userDetailModal">Chiudi</button>
            </div>
        </div>
    </div>

    <div id="assignPackageModal" class="modal">
        <div class="modal-panel modal-sm">
            <h3>Assegna pacchetto manualmente</h3>
            <form id="assignPackageForm">
                <input id="apUserId" type="hidden">
                <div class="field">
                    <label for="apMode">Modalita pacchetto</label>
                    <select id="apMode">
                        <option value="existing">Pacchetto esistente</option>
                        <option value="custom">Pacchetto personalizzato / omaggio / buono regalo</option>
                    </select>
                </div>

                <div id="apExistingGroup" class="field" style="margin-top:8px;">
                    <label for="apExistingPackage">Pacchetto</label>
                    <select id="apExistingPackage"></select>
                </div>

                <div id="apCustomGroup" style="display:none; margin-top:8px;">
                    <div class="grid-2">
                        <div class="field">
                            <label for="apCustomKind">Tipo custom</label>
                            <select id="apCustomKind">
                                <option value="personalizzato">Personalizzato</option>
                                <option value="gift">Buono regalo</option>
                                <option value="omaggio">Omaggio/Gratuito</option>
                            </select>
                        </div>
                        <div class="field"><label for="apCustomName">Nome</label><input id="apCustomName"></div>
                        <div class="field"><label for="apCustomEntries">Ingressi</label><input id="apCustomEntries" type="number" min="1" value="1"></div>
                        <div class="field"><label for="apCustomPrice">Prezzo EUR</label><input id="apCustomPrice" type="number" min="0" step="0.01" value="0"></div>
                        <div class="field"><label for="apCustomValidity">Validita giorni</label><input id="apCustomValidity" type="number" min="1" value="30"></div>
                        <div class="field">
                            <label for="apCustomListed">Visibile nello shop</label>
                            <select id="apCustomListed">
                                <option value="0">No</option>
                                <option value="1">Si</option>
                            </select>
                        </div>
                    </div>
                    <div class="field" style="margin-top:8px;"><label for="apCustomDescription">Descrizione</label><textarea id="apCustomDescription"></textarea></div>
                </div>

                <div class="grid-2" style="margin-top:8px;">
                    <div class="field">
                        <label for="apPaymentStatus">Stato pagamento</label>
                        <select id="apPaymentStatus">
                            <option value="confirmed">Confermato (genera QR)</option>
                            <option value="pending">Pending</option>
                            <option value="cancelled">Annullato</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="apPaymentMethod">Metodo pagamento</label>
                        <select id="apPaymentMethod">
                            <option value="contanti">In struttura</option>
                            <option value="bonifico">Bonifico</option>
                        </select>
                    </div>
                    <div class="field"><label for="apImporto">Importo pagato override (facoltativo)</label><input id="apImporto" type="number" min="0" step="0.01"></div>
                    <div class="field"><label for="apPaymentReference">Riferimento pagamento</label><input id="apPaymentReference"></div>
                </div>
                <div class="field" style="margin-top:8px;"><label for="apPaymentNote">Note</label><textarea id="apPaymentNote"></textarea></div>
                <div class="field" style="margin-top:8px;">
                    <label for="apSendEmail">Invia email al cliente</label>
                    <select id="apSendEmail">
                        <option value="1">Si</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button class="btn" type="button" data-modal-close="assignPackageModal">Annulla</button>
                    <button class="btn btn-ok" type="submit">Assegna pacchetto</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.DASHBOARD_CONFIG = {
            theme: 'office',
            title: 'Dashboard Ufficio',
            allowedRoles: ['ufficio', 'segreteria', 'admin'],
            cmsUrl: 'dashboard-contenuti.php',
            cmsBuilderUrl: 'dashboard-cms-builder.php'
        };
    </script>
    <script src="../js/ui-modal.js"></script>
    <script src="../js/dashboard-staff.js"></script>
</body>
</html>




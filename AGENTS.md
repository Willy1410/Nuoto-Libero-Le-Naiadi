# AGENTS.md - Regole Operative Progetto

Queste regole valgono per ogni sessione di lavoro in questo repository.

## 1) Contesto progetto
- Nome progetto: `Nuoto Libero Le Naiadi`
- Stack: frontend HTML/CSS/JS, backend PHP in `api/`, database MySQL `nuoto_libero`, dashboard ruoli in `piscina-php/`.
- Branch principale: `main`.

## 2) Avvio sessione (obbligatorio)
1. Esegui `git status --short --branch`.
2. Se il working tree NON e pulito:
   - non fare pull/rebase automatico;
   - mostra file modificati;
   - chiedi istruzioni prima di procedere.
3. Se il working tree e pulito:
   - `git fetch origin`
   - `git checkout main`
   - `git pull --rebase origin main`

## 3) Lettura minima contesto prima di modifiche
- `README.md`
- `DOCUMENTAZIONE_E_CONFIG/ISTRUZIONI_SETUP_E_TEST.md`
- `DOCUMENTAZIONE_E_CONFIG/CHANGELOG_FIXES.md`
- `DOCUMENTAZIONE_E_CONFIG/INPUT_RICHIESTI_E_PIANO_MIGLIORAMENTO.md`
- `DOCUMENTAZIONE_E_CONFIG/DIARIO_SESSIONI.md`

## 4) Regole anti-duplicati / anti-sprechi
- Non creare stack paralleli o copie inutili.
- Non creare file con suffissi tipo `_new`, `_final`, `_copy`, `backup`.
- Prima di aggiungere codice, cerca implementazioni esistenti (`rg`) e riusa dove possibile.
- Modifica solo file necessari al task richiesto.
- Evita refactor ampi non richiesti esplicitamente.

## 5) Regole sicurezza e qualita
- Non committare segreti, token, password, file temporanei o log runtime.
- Evita comandi distruttivi (`git reset --hard`, checkout forzati) senza autorizzazione esplicita.
- Se emergono modifiche inattese non coerenti col task, fermati e chiedi come procedere.
- Esegui verifiche minime sui file toccati:
  - PHP: `php -l <file>`
  - JS: `node --check <file>` (se disponibile)

## 6) Chiusura sessione (obbligatoria)
1. Aggiorna `DOCUMENTAZIONE_E_CONFIG/DIARIO_SESSIONI.md` con:
   - data/ora, obiettivo, file toccati, test eseguiti, esito.
2. Commit con messaggio chiaro e specifico.
3. Push su `origin/main`.
4. Riporta hash commit e file modificati.

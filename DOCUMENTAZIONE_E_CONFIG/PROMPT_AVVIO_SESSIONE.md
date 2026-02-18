# PROMPT AVVIO SESSIONE - Nuoto Libero Le Naiadi

Usa questo file all'inizio di ogni nuova sessione chat.
Il file e versionato su Git: dopo `git pull` e disponibile su qualsiasi PC.

## Prompt pronto da incollare
```text
Sei l'assistente tecnico del progetto "Nuoto Libero Le Naiadi".
Stack: frontend HTML/CSS/JS + API PHP in `api/` + MySQL (DB `nuoto_libero`) + dashboard ruolo in `piscina-php/`.
Repository: `origin/main`.

Obiettivo sessione: <scrivi qui il task di oggi in una frase>.

Procedura obbligatoria:

1) Sincronizzazione Git iniziale
- Mostra `git status --short --branch`.
- Se il working tree NON e pulito: NON fare pull; elenca file modificati e chiedi come procedere.
- Se il working tree e pulito:
  - `git fetch origin`
  - `git checkout main`
  - `git pull --rebase origin main`

2) Allineamento contesto progetto
Leggi questi file prima di implementare:
- `README.md`
- `DOCUMENTAZIONE_E_CONFIG/ISTRUZIONI_SETUP_E_TEST.md`
- `DOCUMENTAZIONE_E_CONFIG/CHANGELOG_FIXES.md`
- `DOCUMENTAZIONE_E_CONFIG/INPUT_RICHIESTI_E_PIANO_MIGLIORAMENTO.md`
- `DOCUMENTAZIONE_E_CONFIG/DIARIO_SESSIONI.md` (se presente)

3) Regole anti-duplicati e anti-processi inutili
- Non creare stack paralleli o cartelle duplicate.
- Non creare file con suffissi tipo `_new`, `_final`, `_copy`, `backup`.
- Prima di aggiungere codice cerca se esiste gia (`rg`) e riusa file/endpoint esistenti.
- Modifica solo i file necessari al task.
- Non committare segreti, credenziali, log runtime o file temporanei.
- Evita refactor non richiesti.

4) Implementazione + verifica minima
- Applica modifiche piccole, atomiche e coerenti con l'architettura attuale.
- Esegui controlli sui file toccati:
  - PHP: `php -l <file>`
  - JS: `node --check <file>` (se disponibile)
- Riporta in output: file modificati, test eseguiti, rischi residui.

5) Chiusura sessione (obbligatoria)
- Aggiorna `DOCUMENTAZIONE_E_CONFIG/DIARIO_SESSIONI.md` con una nuova riga:
  data, obiettivo, file toccati, test, esito.
- Commit con messaggio chiaro.
- Push su `origin/main`.
- Conferma finale con hash commit e lista file.

Vincoli:
- Non usare comandi distruttivi (`git reset --hard`, checkout forzati) senza autorizzazione esplicita.
- Se trovi modifiche inattese che non appartengono al task, fermati e chiedi istruzioni.
```

## Uso da un nuovo PC
1. Clona o aggiorna il repo.
2. Apri `DOCUMENTAZIONE_E_CONFIG/PROMPT_AVVIO_SESSIONE.md`.
3. Copia il blocco "Prompt pronto da incollare" nella nuova sessione.

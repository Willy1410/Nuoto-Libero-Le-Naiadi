# REPORT EMAIL LANDING + ABBONAMENTI

Data: 2026-02-21
Progetto: Nuoto Libero Le Naiadi

## 1) Scopo modifica

Adeguare i due flussi pubblici:
- form landing (`landing.php` -> `api/contact.php`)
- flusso abbonamenti (`pacchetti.php` -> `api/iscrizioni.php?action=submit`)

in modo che:
- la mail arrivi sempre a `info@nuotoliberolenaiadi.it` (via `MAIL_ADMIN_EMAIL`);
- su Aruba Webmail il pulsante `Rispondi` indirizzi automaticamente la risposta all'email del cliente;
- la mail ricevuta mostri in modo visibile l'email cliente nel mittente (display name), senza cambiare il `from_email` tecnico del dominio.

## 2) File modificati

1. `api/config.php`
- esteso il layer mail con parametro opzionale `fromNameOverride`:
  - `sendEmail(...)`
  - `sendBrandedEmail(...)`
  - `sendTemplateEmail(...)`
  - `queueEmailFallback(...)`
- il `from_email` resta invariato (config SMTP), ma il nome mittente puo essere personalizzato per mostrare il cliente.
- mantenuto `Reply-To` opzionale (gia presente) per far funzionare correttamente il tasto `Rispondi` in Aruba.

2. `api/contact.php`
- mantenuta logica corrente e filtri antispam/validazioni.
- per richieste provenienti dalla landing (`form_source=landing`):
  - `Reply-To` impostato a email cliente;
  - nome mittente visibile impostato con formato:
    - `Landing - Nome Cognome (email_cliente)`.

3. `api/iscrizioni.php`
- nel submit pubblico abbonamenti:
  - aggiunta notifica amministrativa verso `MAIL_ADMIN_EMAIL` con dati cliente + dati abbonamento selezionato;
  - notifica amministrativa con `Reply-To` cliente;
  - nome mittente visibile:
    - `Abbonamenti - Nome Cognome (email_cliente)`.
- conferma al cliente mantenuta.
- aggiunta funzione helper:
  - `resolveEnrollmentPackageSummary(...)` per dettaglio pacchetto backend nel corpo mail admin.

4. `scripts/genera_report_email_landing_abbonamenti_pdf.php`
- nuovo script per generare il PDF report.

5. `DOCUMENTAZIONE_E_CONFIG/REPORT_EMAIL_LANDING_ABBONAMENTI.pdf`
- PDF finale richiesto.

## 3) Prima / Dopo

### Prima
- Landing: mail ricevuta ma risposta non sempre diretta al cliente.
- Abbonamenti: il submit pubblico non inviava mail amministrativa completa con Reply-To cliente.
- Mittente visibile: prevaleva il solo nome tecnico configurato.

### Dopo
- Landing: mail a `info@...` con `Reply-To = email cliente`.
- Abbonamenti: mail a `info@...` con:
  - dati utente,
  - dati abbonamento selezionato,
  - riferimento richiesta.
  - `Reply-To = email cliente`.
- In entrambi:
  - il tasto `Rispondi` in Aruba usa il cliente come destinatario;
  - il mittente visibile include anche email cliente nel display name.

Nota tecnica importante:
- lo storico del messaggio e l'oggetto in risposta sono gestiti dal client Aruba quando si usa il pulsante `Rispondi` della webmail.
- il backend imposta correttamente `Reply-To` per indirizzare la risposta al cliente.

## 4) Come verificare

1. Test Landing
- invia il form in `landing.php`.
- apri la mail su Aruba.
- verifica:
  - destinatario ricevuto: `info@nuotoliberolenaiadi.it`
  - header/log con `reply_to = email cliente`
  - mittente visibile contiene email cliente.
- clicca `Rispondi` su Aruba:
  - destinatario precompilato = email cliente.

2. Test Abbonamenti
- invia richiesta da `pacchetti.php`.
- verifica arrivo mail admin su `info@...` con:
  - nome/cognome/email/telefono cliente,
  - pacchetto selezionato e importi,
  - riferimento iscrizione.
- verifica `Reply-To` cliente.
- clicca `Rispondi` su Aruba:
  - destinatario precompilato = email cliente.

3. Verifica tecnica log
- file: `logs/mail.log`
- cercare eventi `Email sent` con:
  - `subject: [Contatti] ...` + `reply_to` cliente (landing)
  - `subject: [Abbonamenti] ...` + `reply_to` cliente (abbonamenti)


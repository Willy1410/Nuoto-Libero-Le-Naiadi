# REPORT MODIFICA EMAIL LANDING

Data report: 2026-02-21
Progetto: Nuoto Libero Le Naiadi
Obiettivo: fix risposta Aruba Webmail per form landing pubblico

## 1) Scopo della modifica

La landing pubblica (`landing.php`) invia le richieste a `info@nuotoliberolenaiadi.it` tramite endpoint `api/contact.php`.

Problema iniziale:
- la mail arrivava alla segreteria con mittente tecnico del sito;
- cliccando "Rispondi" in Aruba Webmail non si apriva automaticamente una risposta verso l'email del cliente compilata nel form.

Scopo raggiunto:
- mantenuto lo stesso destinatario amministrativo (`info@nuotoliberolenaiadi.it`);
- mantenuti gli stessi campi inviati dal form;
- aggiunto `Reply-To` con email cliente SOLO quando la richiesta proviene dalla landing;
- aggiunto in fondo alla mail landing un link/pulsante "Rispondi al cliente" (`mailto:`) per comodita operativa.

Impatto:
- su Aruba Webmail, usando "Rispondi" dalla mail ricevuta dal form landing, la risposta viene indirizzata al cliente;
- nessuna rimozione di controlli antispam/validazioni;
- form contatti standard (`contatti.php`) lasciato invariato nel comportamento Reply-To.

## 2) File toccati e modifica per file

1. `landing.php`
- aggiunto nel payload JSON inviato a `api/contact.php` il campo tecnico:
  - `form_source: 'landing'`
- nessuna modifica ai campi utente, validazioni o UX del form.

2. `api/contact.php`
- letto il nuovo campo `form_source` e introdotta la condizione:
  - `isLandingSource = (form_source === 'landing')`
- mantenuta logica esistente (honeypot, rate limit, validazioni campi e privacy).
- se la sorgente e landing:
  - aggiunto nel body email un blocco "Rispondi al cliente" con link `mailto:`.
- chiamata `sendTemplateEmail(...)` estesa per passare:
  - `replyToEmail = email cliente`
  - `replyToName = nominativo cliente`
  - SOLO per sorgente landing.

3. `api/config.php`
- esteso il layer mail in modo retrocompatibile con parametri opzionali:
  - `sendEmail(..., replyToEmail = '', replyToName = '')`
  - `sendBrandedEmail(..., replyToEmail = '', replyToName = '')`
  - `sendTemplateEmail(..., replyToEmail = '', replyToName = '')`
- dentro `sendEmail`:
  - aggiunto `addReplyTo(...)` PHPMailer se `replyToEmail` e valido.
- esteso fallback coda (`queueEmailFallback`) per tracciare anche `reply_to_*`.
- nessuna rottura su altri flussi, perche i nuovi parametri sono opzionali.

4. `scripts/genera_report_modifica_email_landing_pdf.php`
- nuovo script operativo per generare questo report in PDF.

5. `DOCUMENTAZIONE_E_CONFIG/REPORT_MODIFICA_EMAIL_LANDING.pdf`
- output PDF richiesto.

## 3) Prima / Dopo (ricezione e risposta Aruba Webmail)

Prima:
- Ricezione: mail correttamente ricevuta su `info@nuotoliberolenaiadi.it`.
- Risposta: clic su "Rispondi" non puntava automaticamente all'email cliente del form.

Dopo:
- Ricezione: invariata, la mail arriva sempre a `info@nuotoliberolenaiadi.it`.
- Risposta: clic su "Rispondi" usa `Reply-To` cliente (solo mail da landing), quindi Aruba prepara la risposta all'email del cliente.
- Extra: nella mail e presente anche "Rispondi al cliente" (`mailto:`) come alternativa rapida.

## 4) Checklist verifica operativa

1. Apri `landing.php` pubblica e invia un test con email cliente reale (es. tua seconda casella).
2. Verifica che la richiesta venga ricevuta su Aruba Webmail (`info@nuotoliberolenaiadi.it`).
3. Apri la mail ricevuta e clicca "Rispondi":
- atteso: destinatario precompilato = email cliente inserita nel form.
4. Nella stessa mail verifica presenza link/pulsante "Rispondi al cliente":
- atteso: apre nuova email verso il cliente (senza invio automatico).
5. Esegui un test dal form `contatti.php`:
- atteso: nessuna regressione invio;
- comportamento Reply-To standard invariato (non forzato come landing).
6. Verifica log tecnico locale:
- `logs/mail.log` contiene riga con `"reply_to":"email_cliente"` per test landing.

## 5) Test tecnico eseguito in questa sessione

Test endpoint landing:
- POST a `http://localhost/Nuoto-Libero-Le-Naiadi/api/contact.php` con `form_source=landing`
- Esito API: `success: true`
- Log: riga `Email sent` con `"reply_to":"cliente.test.replyto@example.com"`

Test endpoint contatti standard:
- POST a `api/contact.php` senza `form_source`
- Esito API: `success: true`
- Log: `reply_to:null` (conferma isolamento modifica al solo flusso landing)


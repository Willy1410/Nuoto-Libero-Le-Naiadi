# CONFIG_EMAIL.md

## File coinvolti
- `config/mail.php`
- `api/config.php`
- `api/contact.php`
- `api/bonifico-notify.php`
- `logs/mail.log`

## Configurazione SMTP Gmail (test locale)
In `config/mail.php` è già impostato:
- host: `smtp.gmail.com`
- port: `587`
- encryption: `tls`
- app password test: `yyvb ckzs zvpi rwdb`

Parametro da impostare:
- variabile ambiente `GMAIL_SMTP_USER` (la casella Gmail reale)

Esempio PowerShell:
```powershell
$env:GMAIL_SMTP_USER='tuacasella@gmail.com'
```

## Test invio
1. Compila form in `contatti.html`.
2. Invia notifica bonifico da `pacchetti.html` (sezione bonifico).
3. Verifica `logs/mail.log`.

## Note sicurezza
- solo ambiente locale
- non usare credenziali live in repository pubblici
- non condividere app password di produzione
# CONFIG_EMAIL.md

## File coinvolti
- `config/mail.php`
- `config/env.php`
- `api/config.php`
- `api/contact.php`
- `logs/mail.log`

## Configurazione SMTP (locale/test)
1. Copia `.env.example` in `.env` (se non esiste).
2. Imposta nel `.env`:
   - `MAIL_ENABLED=true`
   - `MAIL_FROM_EMAIL`
   - `MAIL_FROM_NAME`
   - `MAIL_ADMIN_EMAIL`
   - `MAIL_SMTP_HOST`
   - `MAIL_SMTP_PORT`
   - `MAIL_SMTP_USER`
   - `MAIL_SMTP_PASS`
   - `MAIL_SMTP_ENCRYPTION`

## Test invio
1. Compila il form in `contatti.php`.
2. Verifica risposta positiva lato UI.
3. Controlla `logs/mail.log`.

## Note sicurezza
- Non inserire credenziali reali nel repository.
- Usa sempre variabili ambiente (`.env` locale non tracciato).
- In produzione abilita SMTP solo con account dedicato.

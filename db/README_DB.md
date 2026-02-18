# README_DB.md

## File SQL principale
- `db/CREATE_DATABASE_FROM_ZERO.sql`
- Migrazione incrementale: `db/MIGRATION_2026_02_17_STORAGE_DB.sql`
- Migrazione incrementale: `db/MIGRATION_2026_02_18_CMS_BUILDER_READY.sql`

Script pronto per phpMyAdmin:
- crea database `nuoto_libero`
- crea tutte le tabelle necessarie
- applica indici e vincoli
- inserisce seed minimi e utenti test

## Tabelle principali
- `ruoli`
- `profili`
- `pacchetti`
- `acquisti`
- `check_ins`
- `documenti_utente`
- `moduli`
- `activity_log`
- `password_reset_tokens`
- `notifiche_email`

## Import rapido (XAMPP)
1. Apri `http://localhost/phpmyadmin`
2. Tab `SQL`
3. Esegui tutto il contenuto di `db/CREATE_DATABASE_FROM_ZERO.sql`
4. Se parti da un DB esistente, esegui anche:
   - `db/MIGRATION_2026_02_17_STORAGE_DB.sql`
   - `db/MIGRATION_2026_02_18_CMS_BUILDER_READY.sql`

## Utenti test seed
- admin@piscina.it / password123
- ufficio@piscina.it / password123
- bagnino@piscina.it / password123
- mario.rossi@email.it / password123
- laura.bianchi@email.it / password123
- giuseppe.verdi@email.it / password123
- anna.ferrari@email.it / password123

## Note operative
- Lo script include `DROP DATABASE IF EXISTS`: rieseguendolo resetti completamente i dati locali.
- Dopo import, verifica connessione DB in `api/config.php`.

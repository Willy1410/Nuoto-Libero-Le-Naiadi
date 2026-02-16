# DB SETUP (Riferimento rapido)

Per setup database completo usa:
- `db/CREATE_DATABASE_FROM_ZERO.sql`

Istruzioni complete:
- `db/README_DB.md`

## Passi minimi
1. Apri `http://localhost/phpmyadmin`.
2. Incolla/esegui `db/CREATE_DATABASE_FROM_ZERO.sql`.
3. Verifica in `api/config.php`:
   - host/user/pass/db coerenti con il tuo XAMPP
4. Testa login da `login.html` con credenziali test.

## Credenziali test
Vedi file:
- `DOCUMENTAZIONE_E_CONFIG/TEST_CREDENTIALS_LOCAL.txt`

# CONFIG PAGAMENTI STRIPE/PAYPAL + BONIFICO (TEST)

## File principale
- `config/payments.php`

## 1) Stripe (solo test)
Compila i placeholder:
- `publishable_key` -> `pk_test_...`
- `secret_key` -> `sk_test_...`
- `webhook_secret` -> `whsec_...`
- `mode` -> `test`

Nota:
- In questo progetto la parte UI e predisposta.
- Per un flusso server completo Stripe serve endpoint backend dedicato ai Payment Intent/webhook.

## 2) PayPal (solo sandbox)
Compila i placeholder:
- `client_id`
- `client_secret`
- `mode` -> `sandbox`
- `webhook_id`

Nota:
- Anche qui e una predisposizione di configurazione.
- Usa solo credenziali sandbox.

## 3) Bonifico
Configura la sezione `bonifico`:
- `intestatario`
- `iban`
- `banca`
- `causale_template`
- `email_conferma`

Uso applicativo:
- L'utente seleziona bonifico in `pacchetti.html`
- Compila riferimento + data
- Frontend invia `POST /api/bonifico-notify.php`
- Admin riceve email notifica (se SMTP configurato)

## 4) Cosa devi fare tu in locale
1. Inserire chiavi test Stripe/PayPal.
2. Inserire dati bonifico reali da mostrare all'utente (sezione test locale).
3. Configurare SMTP in `config/mail.php` per ricevere notifiche.

## 5) Cosa NON fare
- Non inserire chiavi live/produzione.
- Non pubblicare credenziali reali in repository.

# CONFIG_PAGAMENTI_STRIPE_PAYPAL.md

## File principale
- `config/payments.php`

## Stripe (solo test)
Compila placeholder:
- `publishable_key` -> `pk_test_...`
- `secret_key` -> `sk_test_...`
- `webhook_secret` -> `whsec_...`
- `mode` -> `test`

## PayPal (solo sandbox)
Compila placeholder:
- `client_id`
- `client_secret`
- `webhook_id`
- `mode` -> `sandbox`

## Bonifico
Configura sezione `bonifico`:
- `intestatario`
- `iban`
- `banca`
- `causale_template`
- `email_conferma`

Nel frontend l’utente vede i dati bonifico e può inviare notifica con:
- riferimento pagamento
- data bonifico

Endpoint usato:
- `POST /api/bonifico-notify.php`

## Regola chiavi
- usare solo chiavi test/sandbox
- non inserire chiavi live in locale o in commit
# Builder.io - Preparazione Integrazione

Questo progetto e stato predisposto per integrazione Builder.io senza collegamento attivo.

## Componenti preparati
- API CMS headless: `api/cms.php`
- Layer backend:
  - `api/cms/CmsValidation.php`
  - `api/cms/CmsService.php`
  - `api/cms/CmsRepository.php`
  - `api/cms/CmsStorage.php`
  - `api/cms/BuilderAdapter.php` (stub)
- Dashboard dedicata: `piscina-php/dashboard-cms-builder.html`
- Migrazione DB: `db/MIGRATION_2026_02_18_CMS_BUILDER_READY.sql`

## Dati che dovrai fornire per Builder.io
Quando vuoi procedere con l'integrazione reale, servono:
1. `Builder Public API Key`
2. `Builder Private API Key` (solo backend)
3. `Space ID` Builder
4. Nome modello/i contenuto (es. `page`, `section`)
5. URL webhook pubblicazione (es. `/api/cms.php?action=builder-webhook`)
6. Eventuale `Webhook secret` per validare chiamate
7. Regole di mapping componenti (es. hero, cards, faq, cta)

## Nota storage media
Attualmente storage `local` in `uploads/cms/*`.
La classe `CmsStorage` e pronta per sostituzione con adapter cloud (S3 o equivalente).

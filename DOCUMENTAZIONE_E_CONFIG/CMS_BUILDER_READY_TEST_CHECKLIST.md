# Checklist Test CMS Builder-Ready

## Prerequisiti
1. Eseguire migrazione: `db/MIGRATION_2026_02_18_CMS_BUILDER_READY.sql`
2. Login con utente `admin` oppure `ufficio`.

## Test accesso ruoli
1. `admin` apre `piscina-php/dashboard-cms-builder.html`: OK.
2. `ufficio` apre `piscina-php/dashboard-cms-builder.html`: OK.
3. `bagnino` o `utente` su stessa pagina: redirect login / accesso negato.

## Test API pagine
1. `POST /api/cms.php?action=save-page` con `status=draft`: salva bozza.
2. `POST /api/cms.php?action=save-page` con `status=published`: pubblica pagina.
3. `GET /api/cms.php?action=public-page&slug=<slug>`: restituisce solo pagine pubblicate.

## Test revisioni
1. Salvare stessa pagina 2+ volte.
2. `GET /api/cms.php?action=revisions&page_id=<id>` mostra versioni precedenti.

## Test media upload
1. Upload immagine valida (`jpg/png/webp/gif`) <= 12MB: OK.
2. Upload file valido (`pdf/doc/docx/zip/txt`) <= 12MB: OK.
3. Upload estensione/mime non consentito: errore bloccante.

## Test settings (admin)
1. `GET /api/cms.php?action=settings` con admin: OK.
2. `POST /api/cms.php?action=save-setting` con admin: OK.
3. Stesse chiamate con ufficio: accesso negato.

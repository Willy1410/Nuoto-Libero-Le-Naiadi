-- =====================================================
-- Nuoto Libero - MIGRATION_2026_02_17_STORAGE_DB
-- Obiettivo:
-- 1) salvare upload documenti/moduli direttamente su DB
-- 2) rimuovere dipendenza da path hardcoded /nuoto-libero
-- =====================================================

USE nuoto_libero;

ALTER TABLE documenti_utente
  ADD COLUMN IF NOT EXISTS file_mime VARCHAR(120) NULL AFTER file_name,
  ADD COLUMN IF NOT EXISTS file_size INT UNSIGNED NULL AFTER file_mime,
  ADD COLUMN IF NOT EXISTS file_blob LONGBLOB NULL AFTER file_size;

ALTER TABLE moduli
  ADD COLUMN IF NOT EXISTS file_data LONGBLOB NULL AFTER size;

-- Normalizzazione opzionale path legacy
UPDATE documenti_utente
SET file_url = REPLACE(file_url, '/nuoto-libero/uploads/', 'uploads/')
WHERE file_url LIKE '%/nuoto-libero/uploads/%';

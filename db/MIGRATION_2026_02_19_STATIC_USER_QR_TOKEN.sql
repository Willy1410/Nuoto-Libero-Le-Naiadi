-- =====================================================
-- MIGRATION 2026-02-19
-- QR statico per utente (token univoco su profili)
-- =====================================================

USE nuoto_libero;

SET @db_name := DATABASE();

-- 1) profili.qr_token (colonna dedicata, unica e persistente)
SET @has_profili_qr_token := (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'profili'
    AND COLUMN_NAME = 'qr_token'
);
SET @sql_add_profili_qr_token := IF(
  @has_profili_qr_token = 0,
  'ALTER TABLE profili ADD COLUMN qr_token VARCHAR(96) NULL AFTER email',
  'SELECT 1'
);
PREPARE stmt_add_profili_qr_token FROM @sql_add_profili_qr_token;
EXECUTE stmt_add_profili_qr_token;
DEALLOCATE PREPARE stmt_add_profili_qr_token;

-- 2) backfill token per utenti esistenti senza token
UPDATE profili
SET qr_token = LOWER(CONCAT(REPLACE(UUID(), '-', ''), REPLACE(UUID(), '-', '')))
WHERE qr_token IS NULL OR TRIM(qr_token) = '';

-- 3) vincolo univocita su qr_token
SET @has_unique_profili_qr_token := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'profili'
    AND COLUMN_NAME = 'qr_token'
    AND NON_UNIQUE = 0
);
SET @sql_add_unique_profili_qr_token := IF(
  @has_unique_profili_qr_token = 0,
  'ALTER TABLE profili ADD UNIQUE INDEX uq_profili_qr_token (qr_token)',
  'SELECT 1'
);
PREPARE stmt_add_unique_profili_qr_token FROM @sql_add_unique_profili_qr_token;
EXECUTE stmt_add_unique_profili_qr_token;
DEALLOCATE PREPARE stmt_add_unique_profili_qr_token;

-- 4) rimuove eventuale unique su acquisti.qr_code (deve poter ripetersi per utente)
SET @acquisti_qr_unique_idx := (
  SELECT INDEX_NAME
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'acquisti'
    AND COLUMN_NAME = 'qr_code'
    AND NON_UNIQUE = 0
    AND INDEX_NAME <> 'PRIMARY'
  ORDER BY INDEX_NAME
  LIMIT 1
);
SET @sql_drop_acquisti_qr_unique := IF(
  @acquisti_qr_unique_idx IS NULL,
  'SELECT 1',
  CONCAT('ALTER TABLE acquisti DROP INDEX `', REPLACE(@acquisti_qr_unique_idx, '`', '``'), '`')
);
PREPARE stmt_drop_acquisti_qr_unique FROM @sql_drop_acquisti_qr_unique;
EXECUTE stmt_drop_acquisti_qr_unique;
DEALLOCATE PREPARE stmt_drop_acquisti_qr_unique;

-- 5) indice non-unico su acquisti.qr_code (performance lookup/report)
SET @has_non_unique_acquisti_qr_idx := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'acquisti'
    AND COLUMN_NAME = 'qr_code'
    AND NON_UNIQUE = 1
);
SET @sql_add_non_unique_acquisti_qr_idx := IF(
  @has_non_unique_acquisti_qr_idx = 0,
  'CREATE INDEX idx_acquisti_qr_code ON acquisti(qr_code)',
  'SELECT 1'
);
PREPARE stmt_add_non_unique_acquisti_qr_idx FROM @sql_add_non_unique_acquisti_qr_idx;
EXECUTE stmt_add_non_unique_acquisti_qr_idx;
DEALLOCATE PREPARE stmt_add_non_unique_acquisti_qr_idx;

-- 6) allinea tutti gli acquisti al token statico utente
UPDATE acquisti a
JOIN profili p ON p.id = a.user_id
SET a.qr_code = p.qr_token
WHERE p.qr_token IS NOT NULL
  AND p.qr_token <> ''
  AND (a.qr_code IS NULL OR a.qr_code <> p.qr_token);

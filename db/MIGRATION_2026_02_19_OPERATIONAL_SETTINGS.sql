-- MIGRATION: impostazioni operative scanner QR
-- Data: 2026-02-19

USE nuoto_libero;

CREATE TABLE IF NOT EXISTS impostazioni_operative (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  scanner_enabled TINYINT(1) NOT NULL DEFAULT 1,
  h24_mode TINYINT(1) NOT NULL DEFAULT 0,
  schedule_json LONGTEXT NULL,
  updated_by CHAR(36) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_impostazioni_operative_updated_by
    FOREIGN KEY (updated_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO impostazioni_operative (id, scanner_enabled, h24_mode, schedule_json, updated_by)
VALUES (
  1,
  1,
  0,
  '[{"day":1,"start":"06:30","end":"09:00"},{"day":1,"start":"13:00","end":"14:00"},{"day":3,"start":"06:30","end":"09:00"},{"day":3,"start":"13:00","end":"14:00"},{"day":5,"start":"06:30","end":"09:00"},{"day":5,"start":"13:00","end":"14:00"}]',
  NULL
)
ON DUPLICATE KEY UPDATE
  scanner_enabled = VALUES(scanner_enabled),
  h24_mode = VALUES(h24_mode),
  schedule_json = VALUES(schedule_json);

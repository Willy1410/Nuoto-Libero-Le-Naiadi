-- =====================================================
-- MIGRATION 2026-02-19
-- Richieste modifica dati profilo (utente -> ufficio/admin)
-- =====================================================

USE nuoto_libero;

CREATE TABLE IF NOT EXISTS profile_update_requests (
  id CHAR(36) PRIMARY KEY,
  user_id CHAR(36) NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  requested_changes_json LONGTEXT NOT NULL,
  current_snapshot_json LONGTEXT NULL,
  review_note TEXT NULL,
  reviewed_by CHAR(36) NULL,
  reviewed_at DATETIME NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_update_requests_user FOREIGN KEY (user_id) REFERENCES profili(id) ON DELETE CASCADE,
  CONSTRAINT fk_profile_update_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @idx_user_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'profile_update_requests'
    AND INDEX_NAME = 'idx_profile_update_requests_user'
);
SET @idx_user_sql := IF(@idx_user_exists = 0, 'CREATE INDEX idx_profile_update_requests_user ON profile_update_requests(user_id)', 'SELECT 1');
PREPARE stmt_idx_user FROM @idx_user_sql;
EXECUTE stmt_idx_user;
DEALLOCATE PREPARE stmt_idx_user;

SET @idx_status_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'profile_update_requests'
    AND INDEX_NAME = 'idx_profile_update_requests_status'
);
SET @idx_status_sql := IF(@idx_status_exists = 0, 'CREATE INDEX idx_profile_update_requests_status ON profile_update_requests(status)', 'SELECT 1');
PREPARE stmt_idx_status FROM @idx_status_sql;
EXECUTE stmt_idx_status;
DEALLOCATE PREPARE stmt_idx_status;

SET @idx_created_exists := (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'profile_update_requests'
    AND INDEX_NAME = 'idx_profile_update_requests_created_at'
);
SET @idx_created_sql := IF(@idx_created_exists = 0, 'CREATE INDEX idx_profile_update_requests_created_at ON profile_update_requests(created_at)', 'SELECT 1');
PREPARE stmt_idx_created FROM @idx_created_sql;
EXECUTE stmt_idx_created;
DEALLOCATE PREPARE stmt_idx_created;

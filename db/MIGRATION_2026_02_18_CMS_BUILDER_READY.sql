-- =====================================================
-- Nuoto Libero - MIGRATION_2026_02_18_CMS_BUILDER_READY
-- Predisposizione CMS headless compatibile Builder.io
-- =====================================================

USE nuoto_libero;

CREATE TABLE IF NOT EXISTS cms_pages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(160) NOT NULL,
  title VARCHAR(200) NOT NULL,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  content_json LONGTEXT NOT NULL,
  version_num INT UNSIGNED NOT NULL DEFAULT 1,
  updated_by CHAR(36) NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  published_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cms_pages_slug (slug),
  KEY idx_cms_pages_status (status),
  KEY idx_cms_pages_updated_at (updated_at),
  CONSTRAINT fk_cms_pages_updated_by FOREIGN KEY (updated_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_media (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  type ENUM('image', 'file') NOT NULL,
  storage_driver VARCHAR(30) NOT NULL DEFAULT 'local',
  file_path VARCHAR(500) NOT NULL,
  public_url VARCHAR(500) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime VARCHAR(120) NOT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL,
  sha256 CHAR(64) NULL,
  uploaded_by CHAR(36) NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cms_media_type_uploaded (type, uploaded_at),
  CONSTRAINT fk_cms_media_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_revisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_id BIGINT UNSIGNED NOT NULL,
  version_num INT UNSIGNED NOT NULL,
  status ENUM('draft', 'published') NOT NULL DEFAULT 'draft',
  content_json LONGTEXT NOT NULL,
  created_by CHAR(36) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cms_revisions_page_version (page_id, version_num),
  CONSTRAINT fk_cms_revisions_page FOREIGN KEY (page_id) REFERENCES cms_pages(id) ON DELETE CASCADE,
  CONSTRAINT fk_cms_revisions_created_by FOREIGN KEY (created_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cms_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL,
  value_json LONGTEXT NOT NULL,
  updated_by CHAR(36) NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_cms_settings_key (setting_key),
  CONSTRAINT fk_cms_settings_updated_by FOREIGN KEY (updated_by) REFERENCES profili(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

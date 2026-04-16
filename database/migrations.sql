-- ============================================================
-- Migrations: activity_log, pc_history, pc_notes + custom fields
-- Run: docker compose exec -T db mariadb -u root -proot inventaire_pc < database/migrations.sql
-- ============================================================

-- Activity log (audit trail)
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `action` varchar(30) NOT NULL,
  `target_type` varchar(30) NOT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `target_label` varchar(200) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_log_user` (`user_id`),
  KEY `idx_log_action` (`action`),
  KEY `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PC history / changelog (no FK to pcs — survives PC deletion)
CREATE TABLE IF NOT EXISTS `pc_history` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pc_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `action` varchar(20) NOT NULL,
  `changes` JSON DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_history_pc` (`pc_id`),
  KEY `idx_history_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PC notes / incidents
CREATE TABLE IF NOT EXISTS `pc_notes` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pc_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notes_pc` (`pc_id`),
  FOREIGN KEY (`pc_id`) REFERENCES `pcs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extra custom fields: IP, MAC, Location
INSERT INTO `custom_fields` (`field_name`, `field_label`, `field_type`, `is_required`, `is_visible`, `display_order`)
VALUES
  ('ip_address',    'Adresse IP',    'text', 0, 1, 12),
  ('mac_address',   'Adresse MAC',   'text', 0, 1, 13),
  ('localisation',  'Localisation',  'text', 0, 1, 14)
ON DUPLICATE KEY UPDATE field_label = VALUES(field_label);

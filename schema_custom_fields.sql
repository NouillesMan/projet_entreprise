-- Table pour gérer les champs personnalisés
CREATE TABLE IF NOT EXISTS `custom_fields` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `field_name` varchar(50) NOT NULL,
  `field_label` varchar(100) NOT NULL,
  `field_type` enum('text','number','select','date','textarea') NOT NULL DEFAULT 'text',
  `field_options` text DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_field_name` (`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les champs existants par défaut
INSERT INTO `custom_fields` (`field_name`, `field_label`, `field_type`, `is_required`, `is_visible`, `display_order`) VALUES
('hostname', 'Hostname', 'text', 1, 1, 1),
('serial', 'Numéro de série', 'text', 1, 1, 2),
('marque', 'Marque', 'text', 1, 1, 3),
('modele', 'Modèle', 'text', 0, 1, 4),
('utilisateur', 'Utilisateur', 'text', 1, 1, 5),
('os', 'OS', 'text', 1, 1, 6),
('os_version', 'Version OS', 'text', 0, 1, 7),
('architecture', 'Architecture', 'select', 1, 1, 8),
('domaine', 'Domaine', 'text', 0, 1, 9),
('statut', 'Statut', 'select', 1, 1, 10),
('remarques', 'Remarques', 'textarea', 0, 1, 11)
ON DUPLICATE KEY UPDATE field_label = VALUES(field_label);

-- Table pour stocker les valeurs des champs personnalisés additionnels
CREATE TABLE IF NOT EXISTS `pc_custom_data` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pc_id` int(10) UNSIGNED NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `field_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pc_field` (`pc_id`, `field_name`),
  KEY `idx_pc_id` (`pc_id`),
  FOREIGN KEY (`pc_id`) REFERENCES `pcs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

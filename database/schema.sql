-- Minimal schema for the PC inventory app
-- Import this into the database you configured in config.php

CREATE TABLE IF NOT EXISTS `pcs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hostname` varchar(100) NOT NULL,
  `serial` varchar(100) NOT NULL,
  `marque` varchar(80) NOT NULL,
  `modele` varchar(120) DEFAULT NULL,
  `utilisateur` varchar(120) NOT NULL,
  `domaine` varchar(120) DEFAULT NULL,
  `os` varchar(80) NOT NULL,
  `os_version` varchar(80) DEFAULT NULL,
  `architecture` enum('x86','x64','arm64') NOT NULL,
  `statut` enum('En service','En stock','En réparation','Retiré') NOT NULL,
  `remarques` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pcs_serial` (`serial`),
  KEY `idx_pcs_hostname` (`hostname`),
  KEY `idx_pcs_utilisateur` (`utilisateur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

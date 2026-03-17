-- Table des utilisateurs avec système de permissions
-- Docker: docker compose exec -T db mariadb -u root -proot inventaire_pc < database/schema_user.sql

CREATE TABLE IF NOT EXISTS `users` (
  `id`            int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      varchar(80)  NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_admin`      tinyint(1)   NOT NULL DEFAULT 0,
  `can_view`      tinyint(1)   NOT NULL DEFAULT 1,
  `can_add`       tinyint(1)   NOT NULL DEFAULT 0,
  `can_edit`      tinyint(1)   NOT NULL DEFAULT 0,
  `can_delete`    tinyint(1)   NOT NULL DEFAULT 0,
  `created_at`    timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compte admin par défaut : admin / root
-- Le mot de passe est le hash bcrypt de "root"
INSERT INTO `users` (`username`, `password_hash`, `is_admin`, `can_view`, `can_add`, `can_edit`, `can_delete`)
VALUES (
  'admin',
  '$2y$10$8R14zEzGj5zf/.o04weCY.S4V5bp0RczwBoYSAGSxXmxBzchOv1/G',
  1, 1, 1, 1, 1
) ON DUPLICATE KEY UPDATE username = username;

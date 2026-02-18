-- Table pour les options des listes déroulantes (marque, modele, os, os_version)
-- Run once: mysql -u root -p inventaire_pc < schema_options.sql

CREATE TABLE IF NOT EXISTS `field_options` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `field_name` varchar(50) NOT NULL,
  `option_group` varchar(100) DEFAULT NULL,
  `option_value` varchar(255) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_field_name` (`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: Marques
INSERT INTO `field_options` (`field_name`, `option_group`, `option_value`, `display_order`) VALUES
('marque', NULL, 'Dell', 1),
('marque', NULL, 'HP', 2),
('marque', NULL, 'Lenovo', 3),
('marque', NULL, 'Asus', 4),
('marque', NULL, 'Acer', 5),
('marque', NULL, 'MSI', 6),
('marque', NULL, 'Apple', 7),
('marque', NULL, 'Microsoft', 8),
('marque', NULL, 'Toshiba', 9),
('marque', NULL, 'Samsung', 10),
('marque', NULL, 'Fujitsu', 11),
('marque', NULL, 'Autre', 12);

-- Seed: Modèles par marque
INSERT INTO `field_options` (`field_name`, `option_group`, `option_value`, `display_order`) VALUES
('modele', 'Dell', 'Latitude 7490', 1),
('modele', 'Dell', 'Latitude 7400', 2),
('modele', 'Dell', 'Latitude 5420', 3),
('modele', 'Dell', 'OptiPlex 7090', 4),
('modele', 'Dell', 'OptiPlex 5090', 5),
('modele', 'Dell', 'Precision 5560', 6),
('modele', 'Dell', 'XPS 15', 7),
('modele', 'Dell', 'XPS 13', 8),
('modele', 'HP', 'EliteBook 840 G8', 1),
('modele', 'HP', 'EliteBook 850 G7', 2),
('modele', 'HP', 'ProBook 450 G8', 3),
('modele', 'HP', 'ProDesk 600 G6', 4),
('modele', 'HP', 'ZBook 15 G8', 5),
('modele', 'HP', 'Pavilion 15', 6),
('modele', 'Lenovo', 'ThinkPad X1 Carbon Gen 9', 1),
('modele', 'Lenovo', 'ThinkPad T14 Gen 2', 2),
('modele', 'Lenovo', 'ThinkPad L15 Gen 2', 3),
('modele', 'Lenovo', 'ThinkCentre M920q', 4),
('modele', 'Lenovo', 'IdeaPad 3', 5),
('modele', 'Lenovo', 'Legion 5', 6),
('modele', 'Apple', 'MacBook Pro 16"', 1),
('modele', 'Apple', 'MacBook Pro 14"', 2),
('modele', 'Apple', 'MacBook Air M2', 3),
('modele', 'Apple', 'MacBook Air M1', 4),
('modele', 'Apple', 'iMac 24"', 5),
('modele', 'Apple', 'Mac mini M2', 6);

-- Seed: OS
INSERT INTO `field_options` (`field_name`, `option_group`, `option_value`, `display_order`) VALUES
('os', 'Windows', 'Windows 11', 1),
('os', 'Windows', 'Windows 10', 2),
('os', 'Windows', 'Windows Server 2022', 3),
('os', 'Windows', 'Windows Server 2019', 4),
('os', 'Windows', 'Windows Server 2016', 5),
('os', 'Linux', 'Ubuntu 22.04 LTS', 1),
('os', 'Linux', 'Ubuntu 20.04 LTS', 2),
('os', 'Linux', 'Debian 12', 3),
('os', 'Linux', 'Debian 11', 4),
('os', 'Linux', 'CentOS 8', 5),
('os', 'Linux', 'Red Hat Enterprise Linux 9', 6),
('os', 'Linux', 'Fedora 39', 7),
('os', 'macOS', 'macOS Sonoma 14', 1),
('os', 'macOS', 'macOS Ventura 13', 2),
('os', 'macOS', 'macOS Monterey 12', 3),
('os', 'macOS', 'macOS Big Sur 11', 4),
('os', 'Autre', 'Autre', 1);

-- Seed: Versions OS
INSERT INTO `field_options` (`field_name`, `option_group`, `option_value`, `display_order`) VALUES
('os_version', NULL, '23H2', 1),
('os_version', NULL, '22H2', 2),
('os_version', NULL, '21H2', 3),
('os_version', NULL, '20H2', 4),
('os_version', NULL, 'LTS', 5),
('os_version', NULL, 'Standard', 6),
('os_version', NULL, 'Datacenter', 7),
('os_version', NULL, 'Pro', 8),
('os_version', NULL, 'Enterprise', 9),
('os_version', NULL, 'Home', 10);

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2025 at 02:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inventaire pc`
--

-- --------------------------------------------------------

--
-- Table structure for table `licenses`
--

CREATE TABLE `licenses` (
  `id` int(10) UNSIGNED NOT NULL,
  `NAME` varchar(120) NOT NULL,
  `license_key` varchar(255) DEFAULT NULL,
  `vendor` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `networks`
--

CREATE TABLE `networks` (
  `id` int(10) UNSIGNED NOT NULL,
  `NAME` varchar(120) NOT NULL,
  `vlan` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pcs`
--

CREATE TABLE `pcs` (
  `id` int(10) UNSIGNED NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pcs`
--

INSERT INTO `pcs` (`id`, `hostname`, `serial`, `marque`, `modele`, `utilisateur`, `domaine`, `os`, `os_version`, `architecture`, `statut`, `remarques`, `created_at`, `updated_at`) VALUES
(68, 'enzo', '10000000000', 'hp', NULL, 'enzo', NULL, 'windows 10', NULL, 'x86', 'En service', 'sur un windows modifier atlas os', '2025-12-23 16:17:16', '2025-12-23 16:17:16'),
(69, 'jojo', '758645457', 'acer', NULL, 'jojo', NULL, 'ubuntu', NULL, 'arm64', 'En stock', 'pppppppp', '2025-12-23 16:27:05', '2025-12-23 16:27:05'),
(70, 'zdqsd', 'zfq', 'qzf', 'zd', 'zqf', 'zfqs', 'zfq', 'zf', 'x86', 'En service', 'zfsq', '2025-12-24 13:06:51', '2025-12-24 13:06:51');

-- --------------------------------------------------------

--
-- Table structure for table `pc_licenses`
--

CREATE TABLE `pc_licenses` (
  `pc_id` int(10) UNSIGNED NOT NULL,
  `license_id` int(10) UNSIGNED NOT NULL,
  `installed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pc_networks`
--

CREATE TABLE `pc_networks` (
  `pc_id` int(10) UNSIGNED NOT NULL,
  `network_id` int(10) UNSIGNED NOT NULL,
  `connected_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remarks`
--

CREATE TABLE `remarks` (
  `id` int(10) UNSIGNED NOT NULL,
  `pc_id` int(10) UNSIGNED NOT NULL,
  `author` varchar(120) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `licenses`
--
ALTER TABLE `licenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_licenses_name` (`NAME`);

--
-- Indexes for table `networks`
--
ALTER TABLE `networks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_networks_name` (`NAME`);

--
-- Indexes for table `pcs`
--
ALTER TABLE `pcs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_pcs_serial` (`serial`),
  ADD KEY `idx_pcs_hostname` (`hostname`),
  ADD KEY `idx_pcs_utilisateur` (`utilisateur`);

--
-- Indexes for table `pc_licenses`
--
ALTER TABLE `pc_licenses`
  ADD PRIMARY KEY (`pc_id`,`license_id`),
  ADD KEY `fk_pclicenses_license` (`license_id`);

--
-- Indexes for table `pc_networks`
--
ALTER TABLE `pc_networks`
  ADD PRIMARY KEY (`pc_id`,`network_id`),
  ADD KEY `fk_pcnetworks_network` (`network_id`);

--
-- Indexes for table `remarks`
--
ALTER TABLE `remarks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_remarks_pc` (`pc_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `licenses`
--
ALTER TABLE `licenses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `networks`
--
ALTER TABLE `networks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pcs`
--
ALTER TABLE `pcs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `remarks`
--
ALTER TABLE `remarks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pc_licenses`
--
ALTER TABLE `pc_licenses`
  ADD CONSTRAINT `fk_pclicenses_license` FOREIGN KEY (`license_id`) REFERENCES `licenses` (`id`),
  ADD CONSTRAINT `fk_pclicenses_pc` FOREIGN KEY (`pc_id`) REFERENCES `pcs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pc_networks`
--
ALTER TABLE `pc_networks`
  ADD CONSTRAINT `fk_pcnetworks_network` FOREIGN KEY (`network_id`) REFERENCES `networks` (`id`),
  ADD CONSTRAINT `fk_pcnetworks_pc` FOREIGN KEY (`pc_id`) REFERENCES `pcs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `remarks`
--
ALTER TABLE `remarks`
  ADD CONSTRAINT `fk_remarks_pc` FOREIGN KEY (`pc_id`) REFERENCES `pcs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Create database if it doesn't exist (Docker MySQL image usually does this based on MYSQL_DATABASE env var)
-- CREATE DATABASE IF NOT EXISTS phone_extensions CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE phone_extensions;

--
-- Table structure for table `sectors`
--
CREATE TABLE IF NOT EXISTS `sectors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sectors`
--
INSERT INTO `sectors` (`id`, `name`) VALUES
(1, 'Administração'),
(2, 'TI'),
(3, 'Recursos Humanos'),
(4, 'Financeiro'),
(5, 'Marketing'),
(6, 'Vendas');

--
-- Table structure for table `persons`
--
CREATE TABLE IF NOT EXISTS `persons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `persons`
--
INSERT INTO `persons` (`id`, `name`) VALUES
(1, 'Alice Silva'),
(2, 'Bruno Costa'),
(3, 'Carlos Dias'),
(4, 'Daniela Rocha');

--
-- Table structure for table `extensions`
--
CREATE TABLE IF NOT EXISTS `extensions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('Interno','Externo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sector_id` int NOT NULL,
  `person_id` int DEFAULT NULL,
  `status` enum('Vago','Atribuído') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Vago',
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`),
  KEY `sector_id` (`sector_id`),
  KEY `person_id` (`person_id`),
  CONSTRAINT `extensions_ibfk_1` FOREIGN KEY (`sector_id`) REFERENCES `sectors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `extensions_ibfk_2` FOREIGN KEY (`person_id`) REFERENCES `persons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `extensions`
--
-- Ramal 1001 (TI, Alice Silva, Interno)
-- Ramal 1002 (TI, Vago, Interno)
-- Ramal 2001 (RH, Bruno Costa, Interno)
-- Ramal 3001 (Financeiro, Vago, Externo)
INSERT INTO `extensions` (`id`, `number`, `type`, `sector_id`, `person_id`, `status`) VALUES
(1, '1001', 'Interno', 2, 1, 'Atribuído'),
(2, '1002', 'Interno', 2, NULL, 'Vago'),
(3, '2001', 'Interno', 3, 2, 'Atribuído'),
(4, '3001', 'Externo', 4, NULL, 'Vago');


--
-- Table structure for table `users`
--
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile` enum('Admin','Super-Admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--
-- Default Super-Admin: admin / admin_password
-- Hash for 'admin_password' is '$2y$10$5Y0N5Y.R8VTBmYVDRk805uLfo3P5nZERzL0DeVXY47877t87/gPcS' (can be regenerated with generate_password_hash.php)
-- Default Admin: test_admin / test_password
-- Hash for 'test_password' is '$2y$10$EGRgq8q01j8a8S.TMyb9y.otY63jN3eogMsrXRmy096PaAM03Vv3S'
INSERT INTO `users` (`id`, `username`, `password_hash`, `profile`) VALUES
(1, 'admin', '$2y$10$5Y0N5Y.R8VTBmYVDRk805uLfo3P5nZERzL0DeVXY47877t87/gPcS', 'Super-Admin'),
(2, 'test_admin', '$2y$10$EGRgq8q01j8a8S.TMyb9y.otY63jN3eogMsrXRmy096PaAM03Vv3S', 'Admin');

COMMIT;

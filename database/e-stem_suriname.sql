-- phpMyAdmin SQL Dump
-- version 5.2.2deb1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 25, 2025 at 08:22 PM
-- Server version: 8.4.5-0ubuntu0.1
-- PHP Version: 8.4.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e-stem_suriname`
--

-- --------------------------------------------------------

--
-- Table structure for table `districten`
--

CREATE TABLE `districten` (
  `DistrictID` int NOT NULL AUTO_INCREMENT,
  `DistrictName` varchar(255) NOT NULL,
  PRIMARY KEY (`DistrictID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `districten`
--

INSERT INTO `districten` (`DistrictName`) VALUES
('Paramaribo'),
('Wanica'),
('Nickerie'),
('Coronie'),
('Saramacca'),
('Commewijne'),
('Marowijne'),
('Para'),
('Brokopondo'),
('Sipaliwini');

-- --------------------------------------------------------

--
-- Table structure for table `resorts`
--

CREATE TABLE `resorts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `district_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `district_id` (`district_id`),
  CONSTRAINT `resorts_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districten` (`DistrictID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resorts`
--

INSERT INTO `resorts` (`name`, `district_id`) VALUES
-- Brokopondo
('Centrum', 8),
('Brownsweg', 8),
('Marchallkreek', 8),
('Klaaskreek', 8),
('Sarakreek', 8),
('Tapoeripa', 8),
('Kwakoegron', 8),
-- Commewijne
('Alkmaar', 5),
('Bakkie', 5),
('Tamanredjo', 5),
('Meerzorg', 5),
('Nieuw Amsterdam', 5),
('Margrita', 5),
-- Coronie
('Totness', 4),
('Johanna Maria', 4),
-- Marowijne
('Albina', 6),
('Moengo', 6),
('Moengotapoe', 6),
('Patamacca', 6),
('Wanhatti', 6),
('Galibi', 6),
('Stoelmanseiland', 6),
('Godo Olo', 6),
-- Nickerie
('Nieuw Nickerie', 3),
('Wageningen', 3),
('Corantijnpolder', 3),
('Oostelijke Polders', 3),
-- Para
('Zanderij', 7),
('Bigi Poika', 7),
('Onverdacht', 7),
('Sabakoe', 7),
('Carolina', 7),
('Oost', 7),
('Zuid', 7),
-- Paramaribo
('Beekhuizen', 1),
('Blauwgrond', 1),
('Centrum', 1),
('Flora', 1),
('Latour', 1),
('Livorno', 1),
('Munder', 1),
('Pontbuiten', 1),
('Rainville', 1),
('Tammenga', 1),
('Weg naar Zee', 1),
('Welgelegen', 1),
-- Saramacca
('Calcutta', 4),
('Groningen', 4),
('Kampong Baroe', 4),
('Tijgerkreek', 4),
('Jarikaba', 4),
('Wayamboweg', 4),
-- Sipaliwini
('Boven Coppename', 9),
('Boven Suriname', 9),
('Boven Saramacca', 9),
('Coeroeni', 9),
('Kabalebo', 9),
('Tapanahony', 9),
('Zuidwest', 9),
('Sipaliwini Savanna', 9),
-- Wanica
('Domburg', 2),
('Houttuin', 2),
('Koewarasan', 2),
('Kwatta', 2),
('Lelydorp', 2),
('Saramacca Polder', 2),
('De Nieuwe Grond', 2),
('Welgelegen', 2),
('Santo Boma', 2),
('Meerzorg', 2);

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `AdminID` bigint NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(255) NOT NULL,
  `LastName` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Status` enum('active','inactive') DEFAULT 'active',
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`AdminID`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`FirstName`, `LastName`, `Email`, `Password`, `Status`) VALUES
('Admin', 'User', 'admin@e-stem.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
('Test', 'Admin', 'testadmin@example.com', '$2y$12$6adH94daWhe3tvQn6zgRBu3Ah2D6WG/3a0VrVRBIEeQnkytlAj0Ye', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

CREATE TABLE `parties` (
  `PartyID` bigint NOT NULL AUTO_INCREMENT,
  `PartyName` varchar(255) NOT NULL,
  `Logo` varchar(255) DEFAULT NULL,
  `Description` text,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`PartyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`PartyName`, `Description`) VALUES
('Nationale Democratische Partij', 'NDP - Nationale Democratische Partij'),
('Vooruitstrevende Hervormings Partij', 'VHP - Vooruitstrevende Hervormings Partij'),
('Pertjajah Luhur', 'PL - Pertjajah Luhur'),
('Nationale Partij Suriname', 'NPS - Nationale Partij Suriname'),
('Algemene Bevrijdings- en Ontwikkelingspartij', 'ABOP - Algemene Bevrijdings- en Ontwikkelingspartij');

-- --------------------------------------------------------

--
-- Table structure for table `elections`
--

CREATE TABLE `elections` (
  `ElectionID` bigint NOT NULL AUTO_INCREMENT,
  `ElectionName` varchar(255) NOT NULL,
  `ElectionDate` date NOT NULL,
  `Status` enum('draft','active','upcoming','completed') DEFAULT 'draft',
  `StartDate` datetime NOT NULL,
  `EndDate` datetime NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ElectionID`),
  KEY `Status` (`Status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elections`
--

INSERT INTO `elections` (`ElectionName`, `ElectionDate`, `Status`, `StartDate`, `EndDate`) VALUES
('District Council Elections', '2025-05-15', 'active', '2025-05-10 00:00:00', '2025-05-20 23:59:59'),
('Algemene Verkiezingen 2025', '2025-05-25', 'upcoming', '2025-05-25 07:00:00', '2025-05-25 19:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `voters`
--

CREATE TABLE `voters` (
  `id` int NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `voter_code` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `district_id` int NOT NULL,
  `resort_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voter_code` (`voter_code`),
  UNIQUE KEY `id_number` (`id_number`),
  KEY `district_id` (`district_id`),
  KEY `resort_id` (`resort_id`),
  KEY `status` (`status`),
  CONSTRAINT `voters_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districten` (`DistrictID`),
  CONSTRAINT `voters_ibfk_2` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `CandidateID` bigint NOT NULL AUTO_INCREMENT,
  `CandidateTypeID` bigint DEFAULT NULL,
  `Name` varchar(255) NOT NULL,
  `PartyID` bigint NOT NULL,
  `DistrictID` int NOT NULL,
  `CandidateType` enum('DNA','RR') NOT NULL DEFAULT 'RR',
  `ElectionID` bigint NOT NULL,
  `Photo` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`CandidateID`),
  KEY `PartyID` (`PartyID`),
  KEY `DistrictID` (`DistrictID`),
  KEY `ElectionID` (`ElectionID`),
  KEY `CandidateType` (`CandidateType`),
  CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`PartyID`) REFERENCES `parties` (`PartyID`),
  CONSTRAINT `candidates_ibfk_2` FOREIGN KEY (`DistrictID`) REFERENCES `districten` (`DistrictID`),
  CONSTRAINT `candidates_ibfk_3` FOREIGN KEY (`ElectionID`) REFERENCES `elections` (`ElectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`Name`, `PartyID`, `DistrictID`, `CandidateType`, `ElectionID`, `Photo`) VALUES
('Sheila Ro', 2, 8, 'RR', 1, 'uploads/candidates/Sheila_Ro_68195163dbb83.png');

-- --------------------------------------------------------

--
-- Table structure for table `qrcodes`
--

CREATE TABLE `qrcodes` (
  `QRCodeID` bigint NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `ElectionID` bigint NOT NULL,
  `QRCode` varchar(32) NOT NULL,
  `Status` enum('active','used') NOT NULL DEFAULT 'active',
  `UsedAt` datetime DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`QRCodeID`),
  UNIQUE KEY `QRCode` (`QRCode`),
  KEY `UserID` (`UserID`),
  KEY `ElectionID` (`ElectionID`),
  KEY `Status` (`Status`),
  CONSTRAINT `qrcodes_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `voters` (`id`),
  CONSTRAINT `qrcodes_ibfk_2` FOREIGN KEY (`ElectionID`) REFERENCES `elections` (`ElectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voter_sessions`
--

CREATE TABLE `voter_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `voter_id` int NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `voter_id` (`voter_id`),
  KEY `expires_at` (`expires_at`),
  KEY `is_active` (`is_active`),
  CONSTRAINT `voter_sessions_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `voters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voter_logins`
--

CREATE TABLE `voter_logins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `voter_id` int NOT NULL,
  `login_time` timestamp DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `attempt_type` enum('qr_scan','manual') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `voter_id` (`voter_id`),
  KEY `login_time` (`login_time`),
  KEY `status` (`status`),
  KEY `attempt_type` (`attempt_type`),
  CONSTRAINT `voter_logins_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `voters` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `VoteID` bigint NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `CandidateID` bigint NOT NULL,
  `ElectionID` bigint NOT NULL,
  `QRCodeID` bigint NOT NULL,
  `TimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`VoteID`),
  KEY `UserID` (`UserID`),
  KEY `CandidateID` (`CandidateID`),
  KEY `ElectionID` (`ElectionID`),
  KEY `QRCodeID` (`QRCodeID`),
  CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `voters` (`id`),
  CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`CandidateID`) REFERENCES `candidates` (`CandidateID`),
  CONSTRAINT `votes_ibfk_3` FOREIGN KEY (`ElectionID`) REFERENCES `elections` (`ElectionID`),
  CONSTRAINT `votes_ibfk_4` FOREIGN KEY (`QRCodeID`) REFERENCES `qrcodes` (`QRCodeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voting_sessions`
--

CREATE TABLE `voting_sessions` (
  `SessionID` bigint NOT NULL AUTO_INCREMENT,
  `UserID` int DEFAULT NULL,
  `QRCodeID` bigint DEFAULT NULL,
  `StartTime` datetime DEFAULT NULL,
  `EndTime` datetime DEFAULT NULL,
  `Status` enum('active','completed','expired') DEFAULT 'active',
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`SessionID`),
  KEY `UserID` (`UserID`),
  KEY `QRCodeID` (`QRCodeID`),
  KEY `Status` (`Status`),
  CONSTRAINT `voting_sessions_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `voters` (`id`),
  CONSTRAINT `voting_sessions_ibfk_2` FOREIGN KEY (`QRCodeID`) REFERENCES `qrcodes` (`QRCodeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `NewsID` bigint NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) DEFAULT NULL,
  `Content` text,
  `AuthorID` bigint DEFAULT NULL,
  `Status` enum('draft','published','archived') DEFAULT 'draft',
  `FeaturedImage` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `DatePosted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`NewsID`),
  KEY `AuthorID` (`AuthorID`),
  KEY `Status` (`Status`),
  CONSTRAINT `news_ibfk_1` FOREIGN KEY (`AuthorID`) REFERENCES `admins` (`AdminID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `data` text,
  `access` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `access` (`access`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
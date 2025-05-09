-- phpMyAdmin SQL Dump
-- version 5.2.2deb1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 06, 2025 at 06:33 PM
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
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `AdminID` bigint NOT NULL,
  `FirstName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `LastName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`AdminID`, `FirstName`, `LastName`, `Email`, `Password`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Admin', 'User', 'admin@estem.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', '2025-03-24 14:11:28', '2025-03-24 14:11:28');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `CandidateID` bigint NOT NULL,
  `CandidateTypeID` bigint DEFAULT NULL,
  `Name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `PartyID` bigint NOT NULL,
  `DistrictID` int NOT NULL,
  `CandidateType` enum('DNA','RR') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'RR',
  `ElectionID` bigint NOT NULL,
  `Photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`CandidateID`, `CandidateTypeID`, `Name`, `PartyID`, `DistrictID`, `CandidateType`, `ElectionID`, `Photo`, `CreatedAt`, `UpdatedAt`) VALUES
(1, NULL, 'Sheila Ro', 2, 8, 'RR', 1, 'uploads/candidates/Sheila_Ro_68195163dbb83.png', '2025-05-05 23:44:15', '2025-05-06 00:01:39');

-- --------------------------------------------------------

--
-- Table structure for table `candidatetype`
--

CREATE TABLE `candidatetype` (
  `CandidateTypeID` bigint NOT NULL,
  `CandidateType` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Description` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `districten`
--

CREATE TABLE `districten` (
  `DistrictID` int NOT NULL,
  `DistrictName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `districten`
--

INSERT INTO `districten` (`DistrictID`, `DistrictName`) VALUES
(1, 'Paramaribo'),
(2, 'Wanica'),
(3, 'Nickerie'),
(4, 'Saramacca'),
(5, 'Commewijne'),
(6, 'Marowijne'),
(7, 'Coronie'),
(8, 'Brokopondo'),
(9, 'Sipaliwini');

-- --------------------------------------------------------

--
-- Table structure for table `elections`
--

CREATE TABLE `elections` (
  `ElectionID` bigint NOT NULL,
  `ElectionName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ElectionDate` date NOT NULL,
  `Status` enum('draft','active','upcoming','completed') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `StartDate` datetime NOT NULL,
  `EndDate` datetime NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elections`
--

INSERT INTO `elections` (`ElectionID`, `ElectionName`, `ElectionDate`, `Status`, `StartDate`, `EndDate`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'District Council Elections', '2025-05-15', 'active', '2025-05-10 00:00:00', '2025-05-20 23:59:59', '2025-05-05 13:23:55', '2025-05-05 13:23:55'),
(2, 'National Assembly Elections', '2025-06-10', 'upcoming', '2025-06-10 08:00:00', '2025-06-10 18:00:00', '2025-05-05 13:23:55', '2025-05-05 13:23:55'),
(3, 'School Board Elections', '2025-04-01', 'completed', '2025-04-01 08:00:00', '2025-04-01 18:00:00', '2025-05-05 13:23:55', '2025-05-05 13:23:55');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `NewsID` bigint NOT NULL,
  `Title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Content` text COLLATE utf8mb4_general_ci,
  `AuthorID` bigint DEFAULT NULL,
  `Status` enum('draft','published','archived') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `FeaturedImage` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `DatePosted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

CREATE TABLE `parties` (
  `PartyID` bigint NOT NULL,
  `PartyName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Logo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Description` TEXT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`PartyID`, `PartyName`, `Logo`, `CreatedAt`, `UpdatedAt`) VALUES
(2, 'Test', 'uploads/parties/68194d2dd5594.jpg', '2025-05-05 23:43:41', '2025-05-05 23:43:41');

-- --------------------------------------------------------

--
-- Table structure for table `qrcodes`
--

CREATE TABLE `qrcodes` (
  `QRCodeID` bigint NOT NULL,
  `UserID` bigint NOT NULL,
  `ElectionID` bigint NOT NULL,
  `QRCode` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `Status` enum('active','used') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `UsedAt` datetime DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `data` text,
  `access` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `data`, `access`) VALUES
('394743e81b89d08cd0e402ae40cadba3', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";error|s:113:\"Er is een fout opgetreden bij het ophalen van de district statistieken: SQLSTATE[HY093]: Invalid parameter number\";', 1746556337),
('d2619fc0c9f1c2ead8e309f546f95dea', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";error|s:113:\"Er is een fout opgetreden bij het ophalen van de district statistieken: SQLSTATE[HY093]: Invalid parameter number\";', 1746544301),
('e3c0c61eb7d7bf63c4d3c8cd0c523114', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";error_details|s:91:\"SQLSTATE[42S22]: Column not found: 1054 Unknown column \'e.Description\' in \'group statement\'\";error|s:113:\"Er is een fout opgetreden bij het ophalen van de district statistieken: SQLSTATE[HY093]: Invalid parameter number\";', 1746489699);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` bigint NOT NULL,
  `Voornaam` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Achternaam` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `DistrictID` int NOT NULL,
  `Role` enum('admin','voter') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'voter',
  `IDNumber` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Voornaam`, `Achternaam`, `Email`, `Password`, `DistrictID`, `Role`, `IDNumber`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Leonardo ', 'Ranoesendjojo', 'lranoesendjojo@gmail.com', '$2y$10$6A/OVST35tj1VRNYRzAdTeYgRkHjMnHSOvmt0CTK7cJMC1AVI.WOi', 5, 'voter', '12345678', 'active', '2025-03-24 13:45:29', '2025-03-24 13:45:29');

-- --------------------------------------------------------

--
-- Table structure for table `usertype`
--

CREATE TABLE `usertype` (
  `UTypeID` bigint NOT NULL,
  `UserType` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Permissions` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Description` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usertype`
--

INSERT INTO `usertype` (`UTypeID`, `UserType`, `Permissions`, `Description`) VALUES
(2, 'User', 'vote,view_results', 'Regular voter access'),
(3, 'Moderator', 'manage_candidates,manage_parties,view_results', 'Limited administrative access'),
(4, 'Admin', NULL, NULL),
(5, 'Admin', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `VoteID` bigint NOT NULL,
  `UserID` bigint NOT NULL,
  `CandidateID` bigint NOT NULL,
  `ElectionID` bigint NOT NULL,
  `QRCodeID` bigint NOT NULL,
  `TimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voting_sessions`
--

CREATE TABLE `voting_sessions` (
  `SessionID` bigint NOT NULL,
  `UserID` bigint DEFAULT NULL,
  `QRCodeID` bigint DEFAULT NULL,
  `StartTime` datetime DEFAULT NULL,
  `EndTime` datetime DEFAULT NULL,
  `Status` enum('active','completed','expired') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`AdminID`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`CandidateID`);

--
-- Indexes for table `parties`
--
ALTER TABLE `parties`
  ADD PRIMARY KEY (`PartyID`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `AdminID` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `CandidateID` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parties`
--
ALTER TABLE `parties`
  MODIFY `PartyID` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

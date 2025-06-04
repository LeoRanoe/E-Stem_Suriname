-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 04, 2025 at 12:01 PM
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
-- Database: `e-stem_suriname`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `AdminID` bigint(20) NOT NULL,
  `FirstName` varchar(255) NOT NULL,
  `LastName` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Status` enum('active','inactive') DEFAULT 'active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`AdminID`, `FirstName`, `LastName`, `Email`, `Password`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Admin', 'User', 'admin@e-stem.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', '2025-05-28 12:20:18', '2025-05-28 12:20:18'),
(2, 'Test', 'Admin', 'testadmin@example.com', '$2y$12$6adH94daWhe3tvQn6zgRBu3Ah2D6WG/3a0VrVRBIEeQnkytlAj0Ye', 'active', '2025-05-28 12:20:18', '2025-05-28 12:20:18');

-- --------------------------------------------------------

--
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `CandidateID` bigint(20) NOT NULL,
  `CandidateTypeID` bigint(20) DEFAULT NULL,
  `Name` varchar(255) NOT NULL,
  `PartyID` bigint(20) NOT NULL,
  `DistrictID` int(11) NOT NULL,
  `ResortID` int(11) DEFAULT NULL,
  `CandidateType` enum('DNA','RR') NOT NULL DEFAULT 'RR',
  `ElectionID` bigint(20) NOT NULL,
  `Photo` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates`
--

INSERT INTO `candidates` (`CandidateID`, `CandidateTypeID`, `Name`, `PartyID`, `DistrictID`, `ResortID`, `CandidateType`, `ElectionID`, `Photo`, `CreatedAt`, `UpdatedAt`) VALUES
(1, NULL, 'Sheila Ro', 2, 8, 122, 'RR', 1, 'uploads/candidates/Sheila_Ro_68195163dbb83.png', '2025-05-28 12:20:18', '2025-05-28 19:27:45');

-- --------------------------------------------------------

--
-- Table structure for table `districten`
--

CREATE TABLE `districten` (
  `DistrictID` int(11) NOT NULL,
  `DistrictName` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `districten`
--

INSERT INTO `districten` (`DistrictID`, `DistrictName`) VALUES
(1, 'Paramaribo'),
(2, 'Wanica'),
(3, 'Nickerie'),
(4, 'Coronie'),
(5, 'Saramacca'),
(6, 'Commewijne'),
(7, 'Marowijne'),
(8, 'Para'),
(9, 'Brokopondo'),
(10, 'Sipaliwini');

-- --------------------------------------------------------

--
-- Table structure for table `elections`
--

CREATE TABLE `elections` (
  `ElectionID` bigint(20) NOT NULL,
  `ElectionName` varchar(255) NOT NULL,
  `ElectionDate` date NOT NULL,
  `Status` enum('draft','active','upcoming','completed') DEFAULT 'draft',
  `ShowResults` tinyint(1) DEFAULT 0,
  `StartDate` datetime NOT NULL,
  `EndDate` datetime NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elections`
--

INSERT INTO `elections` (`ElectionID`, `ElectionName`, `ElectionDate`, `Status`, `ShowResults`, `StartDate`, `EndDate`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'District Council Elections', '2025-05-15', 'active', 1, '2025-05-10 00:00:00', '2025-05-20 23:59:59', '2025-05-28 12:20:18', '2025-05-28 18:48:05'),
(2, 'Algemene Verkiezingen 2025', '2025-05-25', 'upcoming', 1, '2025-05-25 07:00:00', '2025-05-25 19:00:00', '2025-05-28 12:20:18', '2025-05-28 18:48:05');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `NewsID` bigint(20) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Content` text DEFAULT NULL,
  `AuthorID` bigint(20) DEFAULT NULL,
  `Status` enum('draft','published','archived') DEFAULT 'draft',
  `FeaturedImage` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `DatePosted` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parties`
--

CREATE TABLE `parties` (
  `PartyID` bigint(20) NOT NULL,
  `PartyName` varchar(255) NOT NULL,
  `Logo` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`PartyID`, `PartyName`, `Logo`, `Description`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Nationale Democratische Partij', NULL, 'NDP - Nationale Democratische Partij', '2025-05-28 12:20:18', '2025-05-28 12:20:18'),
(2, 'Vooruitstrevende Hervormings Partij', NULL, 'VHP - Vooruitstrevende Hervormings Partij', '2025-05-28 12:20:18', '2025-05-28 12:20:18'),
(3, 'Pertjajah Luhur', NULL, 'PL - Pertjajah Luhur', '2025-05-28 12:20:18', '2025-05-28 12:20:18'),
(4, 'Nationale Partij Suriname', NULL, 'NPS - Nationale Partij Suriname', '2025-05-28 12:20:18', '2025-05-28 12:20:18'),
(5, 'Algemene Bevrijdings- en Ontwikkelingspartij', NULL, 'ABOP - Algemene Bevrijdings- en Ontwikkelingspartij', '2025-05-28 12:20:18', '2025-05-28 12:20:18');

-- --------------------------------------------------------

--
-- Table structure for table `qrcodes`
--

CREATE TABLE `qrcodes` (
  `QRCodeID` bigint(20) NOT NULL,
  `UserID` int(11) NOT NULL,
  `ElectionID` bigint(20) NOT NULL,
  `QRCode` varchar(32) NOT NULL,
  `Status` enum('active','used') NOT NULL DEFAULT 'active',
  `UsedAt` datetime DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `resorts`
--

CREATE TABLE `resorts` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `district_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `resorts`
--

INSERT INTO `resorts` (`id`, `name`, `district_id`) VALUES
(1, 'Centrum', 8),
(2, 'Brownsweg', 8),
(3, 'Marchallkreek', 8),
(4, 'Klaaskreek', 8),
(5, 'Sarakreek', 8),
(6, 'Tapoeripa', 8),
(7, 'Kwakoegron', 8),
(8, 'Alkmaar', 5),
(9, 'Bakkie', 5),
(10, 'Tamanredjo', 5),
(11, 'Meerzorg', 5),
(12, 'Nieuw Amsterdam', 5),
(13, 'Margrita', 5),
(14, 'Totness', 4),
(15, 'Johanna Maria', 4),
(16, 'Albina', 6),
(17, 'Moengo', 6),
(18, 'Moengotapoe', 6),
(19, 'Patamacca', 6),
(20, 'Wanhatti', 6),
(21, 'Galibi', 6),
(22, 'Stoelmanseiland', 6),
(23, 'Godo Olo', 6),
(24, 'Nieuw Nickerie', 3),
(25, 'Wageningen', 3),
(26, 'Corantijnpolder', 3),
(27, 'Oostelijke Polders', 3),
(28, 'Zanderij', 7),
(29, 'Bigi Poika', 7),
(30, 'Onverdacht', 7),
(31, 'Sabakoe', 7),
(32, 'Carolina', 7),
(33, 'Oost', 7),
(34, 'Zuid', 7),
(35, 'Beekhuizen', 1),
(36, 'Blauwgrond', 1),
(37, 'Centrum', 1),
(38, 'Flora', 1),
(39, 'Latour', 1),
(40, 'Livorno', 1),
(41, 'Munder', 1),
(42, 'Pontbuiten', 1),
(43, 'Rainville', 1),
(44, 'Tammenga', 1),
(45, 'Weg naar Zee', 1),
(46, 'Welgelegen', 1),
(47, 'Calcutta', 4),
(48, 'Groningen', 4),
(49, 'Kampong Baroe', 4),
(50, 'Tijgerkreek', 4),
(51, 'Jarikaba', 4),
(52, 'Wayamboweg', 4),
(53, 'Boven Coppename', 9),
(54, 'Boven Suriname', 9),
(55, 'Boven Saramacca', 9),
(56, 'Coeroeni', 9),
(57, 'Kabalebo', 9),
(58, 'Tapanahony', 9),
(59, 'Zuidwest', 9),
(60, 'Sipaliwini Savanna', 9),
(61, 'Domburg', 2),
(62, 'Houttuin', 2),
(63, 'Koewarasan', 2),
(64, 'Kwatta', 2),
(65, 'Lelydorp', 2),
(66, 'Saramacca Polder', 2),
(67, 'De Nieuwe Grond', 2),
(68, 'Welgelegen', 2),
(69, 'Santo Boma', 2),
(70, 'Meerzorg', 2),
(71, 'Blauwgrond', 1),
(72, 'Rainville', 1),
(73, 'Munder', 1),
(74, 'Centrum', 1),
(75, 'Beekhuizen', 1),
(76, 'Weg naar Zee', 1),
(77, 'Welgelegen', 1),
(78, 'Flora', 1),
(79, 'Latour', 1),
(80, 'Pontbuiten', 1),
(81, 'Livorno', 1),
(82, 'Tammenga', 1),
(83, 'Lelydorp', 2),
(84, 'Santo Boma', 2),
(85, 'Saramacca Polder', 2),
(86, 'Koewarasan', 2),
(87, 'De Nieuwe Grond', 2),
(88, 'Houttuin', 2),
(89, 'Kwatta', 2),
(90, 'Domburg', 2),
(91, 'Welgelegen', 2),
(92, 'Nieuw Nickerie', 3),
(93, 'Oostelijke Polders', 3),
(94, 'Wageningen', 3),
(95, 'Groot Henar', 3),
(96, 'Corantijnpolder', 3),
(97, 'Totness', 4),
(98, 'Johanna Maria', 4),
(99, 'Welgelegen', 4),
(100, 'Groningen', 5),
(101, 'Jarikaba', 5),
(102, 'Kampong Baroe', 5),
(103, 'Tijgerkreek', 5),
(104, 'Calcutta', 5),
(105, 'Wayamboweg', 5),
(106, 'Nieuw Amsterdam', 6),
(107, 'Alkmaar', 6),
(108, 'Tamanredjo', 6),
(109, 'Meerzorg', 6),
(110, 'Bakkie', 6),
(111, 'Margrita', 6),
(112, 'Albina', 7),
(113, 'Moengo', 7),
(114, 'Moengotapoe', 7),
(115, 'Patamacca', 7),
(116, 'Wanhatti', 7),
(117, 'Galibi', 7),
(118, 'Stoelmanseiland', 7),
(119, 'Godo Olo', 7),
(120, 'Onverwacht', 8),
(121, 'Zanderij', 8),
(122, 'Bigi Poika', 8),
(123, 'Sabakoe', 8),
(124, 'Carolina', 8),
(125, 'Oost', 8),
(126, 'Zuid', 8),
(127, 'Brokopondo Centrum', 9),
(128, 'Brownsweg', 9),
(129, 'Klaaskreek', 9),
(130, 'Kwakoegron', 9),
(131, 'Marchallkreek', 9),
(132, 'Sarakreek', 9),
(133, 'Tapoeripa', 9),
(134, 'Boven Suriname', 10),
(135, 'Boven Saramacca', 10),
(136, 'Boven Coesewijne', 10),
(137, 'Coeroeni', 10),
(138, 'Kabalebo', 10),
(139, 'Tapanahony', 10),
(140, 'Zuid', 10),
(141, 'Sipaliwini', 10);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(128) NOT NULL,
  `data` text DEFAULT NULL,
  `access` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `data`, `access`) VALUES
('5qikngoo1vb0fvqln7hcl2hp5t', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";error_message|s:28:\"Error loading dashboard data\";', 1748510860),
('ammbqbnl3dm4ko7a8h3gnp51eg', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";', 1748522906),
('ar21b9ad50gqgn7lelmeddd1v3', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";', 1748518511),
('gbppi5bj6i43koa0ar0h3vvpj5', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";error_message|s:28:\"Error loading dashboard data\";', 1748511027),
('nfbcveioav3urrme50g7t6qvbb', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";', 1748863747),
('runpg8ig5kiceuhgvr3s3d4d7h', '', 1749029250);

-- --------------------------------------------------------

--
-- Table structure for table `voters`
--

CREATE TABLE `voters` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `voter_code` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `district_id` int(11) NOT NULL,
  `resort_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `voters`
--

INSERT INTO `voters` (`id`, `first_name`, `last_name`, `id_number`, `voter_code`, `password`, `status`, `district_id`, `resort_id`, `created_at`, `updated_at`) VALUES
(1, 'John', 'Doe', '123456789', 'V9C629904', '$2y$10$MrY0.jweLJf9i6o3zB/OoeRXzFenSjgcv8S9QXcbfk9MbH8GDMPHC', 'active', 1, 1, '2025-05-28 18:49:04', '2025-05-28 18:49:04'),
(2, 'Jane', 'Smith', '987654321', 'VFBC3E151', '$2y$10$Tk4SWjUzz3BjXBQ7YEOAYecs4YFdYr1ha5VjexUNS3LkmQd9smmVi', 'active', 2, 5, '2025-05-28 18:49:04', '2025-05-28 18:49:04');

-- --------------------------------------------------------

--
-- Table structure for table `voter_logins`
--

CREATE TABLE `voter_logins` (
  `id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `status` enum('success','failed') NOT NULL,
  `attempt_type` enum('qr_scan','manual') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voter_sessions`
--

CREATE TABLE `voter_sessions` (
  `id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `session_id` varchar(128) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `votes`
--

CREATE TABLE `votes` (
  `VoteID` bigint(20) NOT NULL,
  `UserID` int(11) NOT NULL,
  `CandidateID` bigint(20) NOT NULL,
  `ElectionID` bigint(20) NOT NULL,
  `QRCodeID` bigint(20) NOT NULL,
  `TimeStamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `voting_sessions`
--

CREATE TABLE `voting_sessions` (
  `SessionID` bigint(20) NOT NULL,
  `UserID` int(11) DEFAULT NULL,
  `QRCodeID` bigint(20) DEFAULT NULL,
  `StartTime` datetime DEFAULT NULL,
  `EndTime` datetime DEFAULT NULL,
  `Status` enum('active','completed','expired') DEFAULT 'active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(11) NOT NULL,
  `voter_id` int(11) NOT NULL,
  `voucher_id` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `voter_id`, `voucher_id`, `password`, `used`, `created_at`) VALUES
(1, 1, 'VC2101C2ED', '$2y$10$Fqs/b/tsSJDnUJfFTMsaNOgPq1Und00dQjmFNomdeypf/DA.wT0OS', 0, '2025-05-28 21:33:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`AdminID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`CandidateID`),
  ADD KEY `PartyID` (`PartyID`),
  ADD KEY `DistrictID` (`DistrictID`),
  ADD KEY `ElectionID` (`ElectionID`),
  ADD KEY `CandidateType` (`CandidateType`),
  ADD KEY `candidates_ibfk_4` (`ResortID`);

--
-- Indexes for table `districten`
--
ALTER TABLE `districten`
  ADD PRIMARY KEY (`DistrictID`);

--
-- Indexes for table `elections`
--
ALTER TABLE `elections`
  ADD PRIMARY KEY (`ElectionID`),
  ADD KEY `Status` (`Status`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`NewsID`),
  ADD KEY `AuthorID` (`AuthorID`),
  ADD KEY `Status` (`Status`);

--
-- Indexes for table `parties`
--
ALTER TABLE `parties`
  ADD PRIMARY KEY (`PartyID`);

--
-- Indexes for table `qrcodes`
--
ALTER TABLE `qrcodes`
  ADD PRIMARY KEY (`QRCodeID`),
  ADD UNIQUE KEY `QRCode` (`QRCode`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `ElectionID` (`ElectionID`),
  ADD KEY `Status` (`Status`);

--
-- Indexes for table `resorts`
--
ALTER TABLE `resorts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `district_id` (`district_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `access` (`access`);

--
-- Indexes for table `voters`
--
ALTER TABLE `voters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voter_code` (`voter_code`),
  ADD UNIQUE KEY `id_number` (`id_number`),
  ADD KEY `district_id` (`district_id`),
  ADD KEY `resort_id` (`resort_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `voter_logins`
--
ALTER TABLE `voter_logins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voter_id` (`voter_id`),
  ADD KEY `login_time` (`login_time`),
  ADD KEY `status` (`status`),
  ADD KEY `attempt_type` (`attempt_type`);

--
-- Indexes for table `voter_sessions`
--
ALTER TABLE `voter_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `voter_id` (`voter_id`),
  ADD KEY `expires_at` (`expires_at`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `votes`
--
ALTER TABLE `votes`
  ADD PRIMARY KEY (`VoteID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `CandidateID` (`CandidateID`),
  ADD KEY `ElectionID` (`ElectionID`),
  ADD KEY `QRCodeID` (`QRCodeID`);

--
-- Indexes for table `voting_sessions`
--
ALTER TABLE `voting_sessions`
  ADD PRIMARY KEY (`SessionID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `QRCodeID` (`QRCodeID`),
  ADD KEY `Status` (`Status`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voucher_id` (`voucher_id`),
  ADD KEY `voter_id` (`voter_id`),
  ADD KEY `used` (`used`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `AdminID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `candidates`
--
ALTER TABLE `candidates`
  MODIFY `CandidateID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `districten`
--
ALTER TABLE `districten`
  MODIFY `DistrictID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `elections`
--
ALTER TABLE `elections`
  MODIFY `ElectionID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `NewsID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parties`
--
ALTER TABLE `parties`
  MODIFY `PartyID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `qrcodes`
--
ALTER TABLE `qrcodes`
  MODIFY `QRCodeID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `resorts`
--
ALTER TABLE `resorts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT for table `voters`
--
ALTER TABLE `voters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `voter_logins`
--
ALTER TABLE `voter_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `voter_sessions`
--
ALTER TABLE `voter_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `votes`
--
ALTER TABLE `votes`
  MODIFY `VoteID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `voting_sessions`
--
ALTER TABLE `voting_sessions`
  MODIFY `SessionID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`PartyID`) REFERENCES `parties` (`PartyID`),
  ADD CONSTRAINT `candidates_ibfk_2` FOREIGN KEY (`DistrictID`) REFERENCES `districten` (`DistrictID`),
  ADD CONSTRAINT `candidates_ibfk_3` FOREIGN KEY (`ElectionID`) REFERENCES `elections` (`ElectionID`),
  ADD CONSTRAINT `candidates_ibfk_4` FOREIGN KEY (`ResortID`) REFERENCES `resorts` (`id`);

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`AuthorID`) REFERENCES `admins` (`AdminID`);

--
-- Constraints for table `qrcodes`
--
ALTER TABLE `qrcodes`
  ADD CONSTRAINT `qrcodes_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `voters` (`id`),
  ADD CONSTRAINT `qrcodes_ibfk_2` FOREIGN KEY (`ElectionID`) REFERENCES `elections` (`ElectionID`);

--
-- Constraints for table `resorts`
--
ALTER TABLE `resorts`
  ADD CONSTRAINT `resorts_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districten` (`DistrictID`);

--
-- Constraints for table `voters`
--
ALTER TABLE `voters`
  ADD CONSTRAINT `voters_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `districten` (`DistrictID`),
  ADD CONSTRAINT `voters_ibfk_2` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`);

--
-- Constraints for table `voter_logins`
--
ALTER TABLE `voter_logins`
  ADD CONSTRAINT `voter_logins_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `voters` (`id`);

--
-- Constraints for table `voter_sessions`
--
ALTER TABLE `voter_sessions`
  ADD CONSTRAINT `voter_sessions_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `voters` (`id`);

--
-- Constraints for table `votes`
--
ALTER TABLE `votes`
  ADD CONSTRAINT `votes_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `voters` (`id`),
  ADD CONSTRAINT `votes_ibfk_2` FOREIGN KEY (`CandidateID`) REFERENCES `candidates` (`CandidateID`),
  ADD CONSTRAINT `votes_ibfk_3` FOREIGN KEY (`ElectionID`) REFERENCES `elections` (`ElectionID`),
  ADD CONSTRAINT `votes_ibfk_4` FOREIGN KEY (`QRCodeID`) REFERENCES `qrcodes` (`QRCodeID`);

--
-- Constraints for table `voting_sessions`
--
ALTER TABLE `voting_sessions`
  ADD CONSTRAINT `voting_sessions_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `voters` (`id`),
  ADD CONSTRAINT `voting_sessions_ibfk_2` FOREIGN KEY (`QRCodeID`) REFERENCES `qrcodes` (`QRCodeID`);

--
-- Constraints for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD CONSTRAINT `vouchers_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `voters` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

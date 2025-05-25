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
(1, 'Admin', 'User', 'admin@estem.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', '2025-03-24 14:11:28', '2025-03-24 14:11:28'),
(2, 'Test', 'Admin', 'testadmin@example.com', '$2y$12$6adH94daWhe3tvQn6zgRBu3Ah2D6WG/3a0VrVRBIEeQnkytlAj0Ye', 'active', '2025-05-09 15:27:03', '2025-05-09 15:27:03'),
(3, 'Test', 'Admin2', 'testadmin2@example.com', '$2y$12$r7nUQYE..LgA6Y8cmPvn0elbZ2Fkn4/vYhnRgsK6wbU7exBH8sWb6', 'active', '2025-05-09 16:11:49', '2025-05-09 16:11:49');

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
  `Description` text COLLATE utf8mb4_general_ci,
  `CreatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parties`
--

INSERT INTO `parties` (`PartyID`, `PartyName`, `Logo`, `Description`, `CreatedAt`, `UpdatedAt`) VALUES
(2, 'Test', 'uploads/parties/68194d2dd5594.jpg', NULL, '2025-05-05 23:43:41', '2025-05-05 23:43:41');

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
-- Table structure for table `resorts`
--

CREATE TABLE `resorts` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `district` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `resorts`
--

INSERT INTO `resorts` (`id`, `name`, `district`) VALUES
(1, 'Centrum', 'Brokopondo'),
(2, 'Brownsweg', 'Brokopondo'),
(3, 'Marchallkreek', 'Brokopondo'),
(4, 'Klaaskreek', 'Brokopondo'),
(5, 'Sarakreek', 'Brokopondo'),
(6, 'Tapoeripa', 'Brokopondo'),
(7, 'Kwakoegron', 'Brokopondo'),
(8, 'Alkmaar', 'Commewijne'),
(9, 'Bakkie', 'Commewijne'),
(10, 'Tamanredjo', 'Commewijne'),
(11, 'Meerzorg', 'Commewijne'),
(12, 'Nieuw Amsterdam', 'Commewijne'),
(13, 'Margrita', 'Commewijne'),
(14, 'Totness', 'Coronie'),
(15, 'Johanna Maria', 'Coronie'),
(16, 'Albina', 'Marowijne'),
(17, 'Moengo', 'Marowijne'),
(18, 'Moengotapoe', 'Marowijne'),
(19, 'Patamacca', 'Marowijne'),
(20, 'Wanhatti', 'Marowijne'),
(21, 'Galibi', 'Marowijne'),
(22, 'Stoelmanseiland', 'Marowijne'),
(23, 'Godo Olo', 'Marowijne'),
(24, 'Nieuw Nickerie', 'Nickerie'),
(25, 'Wageningen', 'Nickerie'),
(26, 'Corantijnpolder', 'Nickerie'),
(27, 'Oostelijke Polders', 'Nickerie'),
(28, 'Zanderij', 'Para'),
(29, 'Bigi Poika', 'Para'),
(30, 'Onverdacht', 'Para'),
(31, 'Sabakoe', 'Para'),
(32, 'Carolina', 'Para'),
(33, 'Oost', 'Para'),
(34, 'Zuid', 'Para'),
(35, 'Beekhuizen', 'Paramaribo'),
(36, 'Blauwgrond', 'Paramaribo'),
(37, 'Centrum', 'Paramaribo'),
(38, 'Flora', 'Paramaribo'),
(39, 'Latour', 'Paramaribo'),
(40, 'Livorno', 'Paramaribo'),
(41, 'Munder', 'Paramaribo'),
(42, 'Pontbuiten', 'Paramaribo'),
(43, 'Rainville', 'Paramaribo'),
(44, 'Tammenga', 'Paramaribo'),
(45, 'Weg naar Zee', 'Paramaribo'),
(46, 'Welgelegen', 'Paramaribo'),
(47, 'Calcutta', 'Saramacca'),
(48, 'Groningen', 'Saramacca'),
(49, 'Kampong Baroe', 'Saramacca'),
(50, 'Tijgerkreek', 'Saramacca'),
(51, 'Jarikaba', 'Saramacca'),
(52, 'Wayamboweg', 'Saramacca'),
(53, 'Boven Coppename', 'Sipaliwini'),
(54, 'Boven Suriname', 'Sipaliwini'),
(55, 'Boven Saramacca', 'Sipaliwini'),
(56, 'Coeroeni', 'Sipaliwini'),
(57, 'Kabalebo', 'Sipaliwini'),
(58, 'Tapanahony', 'Sipaliwini'),
(59, 'Zuidwest', 'Sipaliwini'),
(60, 'Sipaliwini Savanna', 'Sipaliwini'),
(61, 'Domburg', 'Wanica'),
(62, 'Houttuin', 'Wanica'),
(63, 'Koewarasan', 'Wanica'),
(64, 'Kwatta', 'Wanica'),
(65, 'Lelydorp', 'Wanica'),
(66, 'Saramacca Polder', 'Wanica'),
(67, 'De Nieuwe Grond', 'Wanica'),
(68, 'Welgelegen', 'Wanica'),
(69, 'Santo Boma', 'Wanica'),
(70, 'Meerzorg', 'Wanica'),
(71, 'Centrum', 'Brokopondo'),
(72, 'Brownsweg', 'Brokopondo'),
(73, 'Marchallkreek', 'Brokopondo'),
(74, 'Klaaskreek', 'Brokopondo'),
(75, 'Sarakreek', 'Brokopondo'),
(76, 'Tapoeripa', 'Brokopondo'),
(77, 'Kwakoegron', 'Brokopondo'),
(78, 'Alkmaar', 'Commewijne'),
(79, 'Bakkie', 'Commewijne'),
(80, 'Tamanredjo', 'Commewijne'),
(81, 'Meerzorg', 'Commewijne'),
(82, 'Nieuw Amsterdam', 'Commewijne'),
(83, 'Margrita', 'Commewijne'),
(84, 'Totness', 'Coronie'),
(85, 'Johanna Maria', 'Coronie'),
(86, 'Albina', 'Marowijne'),
(87, 'Moengo', 'Marowijne'),
(88, 'Moengotapoe', 'Marowijne'),
(89, 'Patamacca', 'Marowijne'),
(90, 'Wanhatti', 'Marowijne'),
(91, 'Galibi', 'Marowijne'),
(92, 'Stoelmanseiland', 'Marowijne'),
(93, 'Godo Olo', 'Marowijne'),
(94, 'Nieuw Nickerie', 'Nickerie'),
(95, 'Wageningen', 'Nickerie'),
(96, 'Corantijnpolder', 'Nickerie'),
(97, 'Oostelijke Polders', 'Nickerie'),
(98, 'Zanderij', 'Para'),
(99, 'Bigi Poika', 'Para'),
(100, 'Onverdacht', 'Para'),
(101, 'Sabakoe', 'Para'),
(102, 'Carolina', 'Para'),
(103, 'Oost', 'Para'),
(104, 'Zuid', 'Para'),
(105, 'Beekhuizen', 'Paramaribo'),
(106, 'Blauwgrond', 'Paramaribo'),
(107, 'Centrum', 'Paramaribo'),
(108, 'Flora', 'Paramaribo'),
(109, 'Latour', 'Paramaribo'),
(110, 'Livorno', 'Paramaribo'),
(111, 'Munder', 'Paramaribo'),
(112, 'Pontbuiten', 'Paramaribo'),
(113, 'Rainville', 'Paramaribo'),
(114, 'Tammenga', 'Paramaribo'),
(115, 'Weg naar Zee', 'Paramaribo'),
(116, 'Welgelegen', 'Paramaribo'),
(117, 'Calcutta', 'Saramacca'),
(118, 'Groningen', 'Saramacca'),
(119, 'Kampong Baroe', 'Saramacca'),
(120, 'Tijgerkreek', 'Saramacca'),
(121, 'Jarikaba', 'Saramacca'),
(122, 'Wayamboweg', 'Saramacca'),
(123, 'Boven Coppename', 'Sipaliwini'),
(124, 'Boven Suriname', 'Sipaliwini'),
(125, 'Boven Saramacca', 'Sipaliwini'),
(126, 'Coeroeni', 'Sipaliwini'),
(127, 'Kabalebo', 'Sipaliwini'),
(128, 'Tapanahony', 'Sipaliwini'),
(129, 'Zuidwest', 'Sipaliwini'),
(130, 'Sipaliwini Savanna', 'Sipaliwini'),
(131, 'Domburg', 'Wanica'),
(132, 'Houttuin', 'Wanica'),
(133, 'Koewarasan', 'Wanica'),
(134, 'Kwatta', 'Wanica'),
(135, 'Lelydorp', 'Wanica'),
(136, 'Saramacca Polder', 'Wanica'),
(137, 'De Nieuwe Grond', 'Wanica'),
(138, 'Welgelegen', 'Wanica'),
(139, 'Santo Boma', 'Wanica'),
(140, 'Meerzorg', 'Wanica'),
(141, 'Centrum', 'Brokopondo'),
(142, 'Brownsweg', 'Brokopondo'),
(143, 'Marchallkreek', 'Brokopondo'),
(144, 'Klaaskreek', 'Brokopondo'),
(145, 'Sarakreek', 'Brokopondo'),
(146, 'Tapoeripa', 'Brokopondo'),
(147, 'Kwakoegron', 'Brokopondo'),
(148, 'Alkmaar', 'Commewijne'),
(149, 'Bakkie', 'Commewijne'),
(150, 'Tamanredjo', 'Commewijne'),
(151, 'Meerzorg', 'Commewijne'),
(152, 'Nieuw Amsterdam', 'Commewijne'),
(153, 'Margrita', 'Commewijne'),
(154, 'Totness', 'Coronie'),
(155, 'Johanna Maria', 'Coronie'),
(156, 'Albina', 'Marowijne'),
(157, 'Moengo', 'Marowijne'),
(158, 'Moengotapoe', 'Marowijne'),
(159, 'Patamacca', 'Marowijne'),
(160, 'Wanhatti', 'Marowijne'),
(161, 'Galibi', 'Marowijne'),
(162, 'Stoelmanseiland', 'Marowijne'),
(163, 'Godo Olo', 'Marowijne'),
(164, 'Nieuw Nickerie', 'Nickerie'),
(165, 'Wageningen', 'Nickerie'),
(166, 'Corantijnpolder', 'Nickerie'),
(167, 'Oostelijke Polders', 'Nickerie'),
(168, 'Zanderij', 'Para'),
(169, 'Bigi Poika', 'Para'),
(170, 'Onverdacht', 'Para'),
(171, 'Sabakoe', 'Para'),
(172, 'Carolina', 'Para'),
(173, 'Oost', 'Para'),
(174, 'Zuid', 'Para'),
(175, 'Beekhuizen', 'Paramaribo'),
(176, 'Blauwgrond', 'Paramaribo'),
(177, 'Centrum', 'Paramaribo'),
(178, 'Flora', 'Paramaribo'),
(179, 'Latour', 'Paramaribo'),
(180, 'Livorno', 'Paramaribo'),
(181, 'Munder', 'Paramaribo'),
(182, 'Pontbuiten', 'Paramaribo'),
(183, 'Rainville', 'Paramaribo'),
(184, 'Tammenga', 'Paramaribo'),
(185, 'Weg naar Zee', 'Paramaribo'),
(186, 'Welgelegen', 'Paramaribo'),
(187, 'Calcutta', 'Saramacca'),
(188, 'Groningen', 'Saramacca'),
(189, 'Kampong Baroe', 'Saramacca'),
(190, 'Tijgerkreek', 'Saramacca'),
(191, 'Jarikaba', 'Saramacca'),
(192, 'Wayamboweg', 'Saramacca'),
(193, 'Boven Coppename', 'Sipaliwini'),
(194, 'Boven Suriname', 'Sipaliwini'),
(195, 'Boven Saramacca', 'Sipaliwini'),
(196, 'Coeroeni', 'Sipaliwini'),
(197, 'Kabalebo', 'Sipaliwini'),
(198, 'Tapanahony', 'Sipaliwini'),
(199, 'Zuidwest', 'Sipaliwini'),
(200, 'Sipaliwini Savanna', 'Sipaliwini'),
(201, 'Domburg', 'Wanica'),
(202, 'Houttuin', 'Wanica'),
(203, 'Koewarasan', 'Wanica'),
(204, 'Kwatta', 'Wanica'),
(205, 'Lelydorp', 'Wanica'),
(206, 'Saramacca Polder', 'Wanica'),
(207, 'De Nieuwe Grond', 'Wanica'),
(208, 'Welgelegen', 'Wanica'),
(209, 'Santo Boma', 'Wanica'),
(210, 'Meerzorg', 'Wanica'),
(211, 'Centrum', 'Brokopondo'),
(212, 'Brownsweg', 'Brokopondo'),
(213, 'Marchallkreek', 'Brokopondo'),
(214, 'Klaaskreek', 'Brokopondo'),
(215, 'Sarakreek', 'Brokopondo'),
(216, 'Tapoeripa', 'Brokopondo'),
(217, 'Kwakoegron', 'Brokopondo'),
(218, 'Alkmaar', 'Commewijne'),
(219, 'Bakkie', 'Commewijne'),
(220, 'Tamanredjo', 'Commewijne'),
(221, 'Meerzorg', 'Commewijne'),
(222, 'Nieuw Amsterdam', 'Commewijne'),
(223, 'Margrita', 'Commewijne'),
(224, 'Totness', 'Coronie'),
(225, 'Johanna Maria', 'Coronie'),
(226, 'Albina', 'Marowijne'),
(227, 'Moengo', 'Marowijne'),
(228, 'Moengotapoe', 'Marowijne'),
(229, 'Patamacca', 'Marowijne'),
(230, 'Wanhatti', 'Marowijne'),
(231, 'Galibi', 'Marowijne'),
(232, 'Stoelmanseiland', 'Marowijne'),
(233, 'Godo Olo', 'Marowijne'),
(234, 'Nieuw Nickerie', 'Nickerie'),
(235, 'Wageningen', 'Nickerie'),
(236, 'Corantijnpolder', 'Nickerie'),
(237, 'Oostelijke Polders', 'Nickerie'),
(238, 'Zanderij', 'Para'),
(239, 'Bigi Poika', 'Para'),
(240, 'Onverdacht', 'Para'),
(241, 'Sabakoe', 'Para'),
(242, 'Carolina', 'Para'),
(243, 'Oost', 'Para'),
(244, 'Zuid', 'Para'),
(245, 'Beekhuizen', 'Paramaribo'),
(246, 'Blauwgrond', 'Paramaribo'),
(247, 'Centrum', 'Paramaribo'),
(248, 'Flora', 'Paramaribo'),
(249, 'Latour', 'Paramaribo'),
(250, 'Livorno', 'Paramaribo'),
(251, 'Munder', 'Paramaribo'),
(252, 'Pontbuiten', 'Paramaribo'),
(253, 'Rainville', 'Paramaribo'),
(254, 'Tammenga', 'Paramaribo'),
(255, 'Weg naar Zee', 'Paramaribo'),
(256, 'Welgelegen', 'Paramaribo'),
(257, 'Calcutta', 'Saramacca'),
(258, 'Groningen', 'Saramacca'),
(259, 'Kampong Baroe', 'Saramacca'),
(260, 'Tijgerkreek', 'Saramacca'),
(261, 'Jarikaba', 'Saramacca'),
(262, 'Wayamboweg', 'Saramacca'),
(263, 'Boven Coppename', 'Sipaliwini'),
(264, 'Boven Suriname', 'Sipaliwini'),
(265, 'Boven Saramacca', 'Sipaliwini'),
(266, 'Coeroeni', 'Sipaliwini'),
(267, 'Kabalebo', 'Sipaliwini'),
(268, 'Tapanahony', 'Sipaliwini'),
(269, 'Zuidwest', 'Sipaliwini'),
(270, 'Sipaliwini Savanna', 'Sipaliwini'),
(271, 'Domburg', 'Wanica'),
(272, 'Houttuin', 'Wanica'),
(273, 'Koewarasan', 'Wanica'),
(274, 'Kwatta', 'Wanica'),
(275, 'Lelydorp', 'Wanica'),
(276, 'Saramacca Polder', 'Wanica'),
(277, 'De Nieuwe Grond', 'Wanica'),
(278, 'Welgelegen', 'Wanica'),
(279, 'Santo Boma', 'Wanica'),
(280, 'Meerzorg', 'Wanica');

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
('44936cda4882f138482a3162f380c059', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";', 1747909559),
('4b7ebf473a4765217c84aa9530ba250d', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";', 1748096288),
('5bce4cad59582736390f76d0bc36235e', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";', 1747935496),
('6d327b37b03e314db833d09d9976ba10', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";', 1748099459),
('ba050169bdcd81aed9f6b0054a263823', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";', 1748126405),
('f247bb6352780f2c5a1f9f46bd74ab13', 'AdminID|i:1;AdminName|s:9:\"Developer\";AdminEmail|s:15:\"dev@example.com\";AdminStatus|s:6:\"active\";', 1748203099);

-- --------------------------------------------------------

--
-- Table structure for table `voters`
--

CREATE TABLE `voters` (
  `id` int NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `district_id` int NOT NULL,
  `election_id` int DEFAULT NULL,
  `resort_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
-- Indexes for table `resorts`
--
ALTER TABLE `resorts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `voters`
--
ALTER TABLE `voters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resort_id` (`resort_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `AdminID` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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

--
-- AUTO_INCREMENT for table `resorts`
--
ALTER TABLE `resorts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=281;

--
-- AUTO_INCREMENT for table `voters`
--
ALTER TABLE `voters`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `voters`
--
ALTER TABLE `voters`
  ADD CONSTRAINT `voters_ibfk_1` FOREIGN KEY (`resort_id`) REFERENCES `resorts` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

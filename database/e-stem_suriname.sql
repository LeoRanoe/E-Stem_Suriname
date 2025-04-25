-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2025 at 03:38 PM
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
-- Table structure for table `candidates`
--

CREATE TABLE `candidates` (
  `CandidateID` bigint(20) NOT NULL AUTO_INCREMENT,
  `CandidateTypeID` bigint(20) DEFAULT NULL,
  `Name` varchar(255) NOT NULL,
  `PartyID` bigint(20) NOT NULL,
  `DistrictID` int(11) NOT NULL,
  `ElectionID` bigint(20) NOT NULL,
  `Photo` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `candidatetype`
--

CREATE TABLE `candidatetype` (
  `CandidateTypeID` bigint(20) NOT NULL,
  `CandidateType` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `ElectionID` bigint(20) NOT NULL,
  `ElectionName` varchar(255) NOT NULL,
  `ElectionDate` date NOT NULL,
  `Status` enum('draft','active','completed') DEFAULT 'draft',
  `StartDate` datetime NOT NULL,
  `EndDate` datetime NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `qrcodes`
--

CREATE TABLE `qrcodes` (
  `QRCodeID` bigint(20) NOT NULL,
  `UserID` bigint(20) NOT NULL,
  `ElectionID` bigint(20) NOT NULL,
  `QRCode` varchar(32) NOT NULL,
  `Status` enum('active','used') NOT NULL DEFAULT 'active',
  `UsedAt` datetime DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` bigint(20) NOT NULL,
  `Voornaam` varchar(255) NOT NULL,
  `Achternaam` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `DistrictID` int(11) NOT NULL,
  `Role` enum('admin','voter') NOT NULL DEFAULT 'voter',
  `IDNumber` varchar(255) NOT NULL,
  `Status` enum('active','inactive') DEFAULT 'active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Voornaam`, `Achternaam`, `Email`, `Password`, `DistrictID`, `Role`, `IDNumber`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Leonardo ', 'Ranoesendjojo', 'lranoesendjojo@gmail.com', '$2y$10$6A/OVST35tj1VRNYRzAdTeYgRkHjMnHSOvmt0CTK7cJMC1AVI.WOi', 5, 'voter', '12345678', 'active', '2025-03-24 13:45:29', '2025-03-24 13:45:29'),
(2, 'Admin', 'User', 'admin@estem.sr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'admin', '', 'active', '2025-03-24 14:11:28', '2025-03-24 14:11:28');

-- --------------------------------------------------------

--
-- Table structure for table `usertype`
--

CREATE TABLE `usertype` (
  `UTypeID` bigint(20) NOT NULL,
  `UserType` varchar(255) DEFAULT NULL,
  `Permissions` varchar(255) DEFAULT NULL,
  `Description` text DEFAULT NULL
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
  `VoteID` bigint(20) NOT NULL,
  `UserID` bigint(20) NOT NULL,
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
  `UserID` bigint(20) DEFAULT NULL,
  `QRCodeID` bigint(20) DEFAULT NULL,
  `StartTime` datetime DEFAULT NULL,
  `EndTime` datetime DEFAULT NULL,
  `Status` enum('active','completed','expired') DEFAULT 'active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `candidates`
--
ALTER TABLE `candidates`
  ADD PRIMARY KEY (`CandidateID`),
  ADD KEY `CandidateTypeID` (`CandidateTypeID`),
  ADD KEY `PartyID` (`PartyID`),
  ADD KEY `DistrictID` (`DistrictID`),
  ADD KEY `ElectionID` (`ElectionID`);

--
-- Indexes for table `candidatetype`
--
ALTER TABLE `candidatetype`
  ADD PRIMARY KEY (`CandidateTypeID`);

--
-- Indexes for table `districten`
--
ALTER TABLE `districten`
  ADD PRIMARY KEY (`DistrictID`);

--
-- Indexes for table `elections`
--
ALTER TABLE `elections`
  ADD PRIMARY KEY (`ElectionID`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`NewsID`),
  ADD KEY `AuthorID` (`AuthorID`);

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
  ADD KEY `ElectionID` (`ElectionID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `IDNumber` (`IDNumber`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `DistrictID` (`DistrictID`);

--
-- Indexes for table `usertype`
--
ALTER TABLE `usertype`
  ADD PRIMARY KEY (`UTypeID`);

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
  ADD KEY `QRCodeID` (`QRCodeID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `candidatetype`
--
ALTER TABLE `candidatetype`
  MODIFY `CandidateTypeID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `districten`
--
ALTER TABLE `districten`
  MODIFY `DistrictID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `elections`
--
ALTER TABLE `elections`
  MODIFY `ElectionID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `NewsID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parties`
--
ALTER TABLE `parties`
  MODIFY `PartyID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `qrcodes`
--
ALTER TABLE `qrcodes`
  MODIFY `QRCodeID` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `usertype`
--
ALTER TABLE `usertype`
  MODIFY `UTypeID` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- Constraints for dumped tables
--

--
-- Constraints for table `candidates`
--
ALTER TABLE `candidates`
  ADD CONSTRAINT `candidates_ibfk_1` FOREIGN KEY (`CandidateTypeID`) REFERENCES `candidatetype` (`CandidateTypeID`),
  ADD CONSTRAINT `candidates_ibfk_2` FOREIGN KEY (`PartyID`) REFERENCES `parties` (`PartyID`),
  ADD CONSTRAINT `candidates_ibfk_3` FOREIGN KEY (`DistrictID`) REFERENCES `districten` (`DistrictID`),
  ADD CONSTRAINT `candidates_ibfk_4` FOREIGN KEY (`ElectionID`) REFERENCES `elections` (`ElectionID`);

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`AuthorID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `qrcodes`
--
ALTER TABLE `qrcodes`
  ADD CONSTRAINT `qrcodes_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `qrcodes_ibfk_2` FOREIGN KEY (`ElectionID`) REFERENCES `elections` (`ElectionID`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`DistrictID`) REFERENCES `districten` (`DistrictID`);

--
-- Constraints for table `votes`

-- Add sample election data
INSERT INTO `elections` (`ElectionID`, `ElectionName`, `ElectionDate`, `Status`, `StartDate`, `EndDate`) VALUES
(1, 'Verkiezing 2025', '2025-05-25', 'active', '2025-05-25 08:00:00', '2025-05-25 18:00:00');
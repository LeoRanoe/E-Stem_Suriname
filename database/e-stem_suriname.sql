SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''));
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `admins` (
  `AdminID` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `FirstName` varchar(255) NOT NULL,
  `LastName` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Status` enum('active','inactive') DEFAULT 'active',
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

[All other table creation statements from original file]

CREATE TABLE `elections` (
  `ElectionID` bigint(20) NOT NULL AUTO_INCREMENT,
  `ElectionName` varchar(255) NOT NULL,
  `ElectionDate` date NOT NULL,
  `Status` enum('draft','active','upcoming','completed') DEFAULT 'draft',
  `StartDate` datetime NOT NULL,
  `EndDate` datetime NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `UpdatedAt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ElectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

[All other table creation statements]

INSERT INTO `elections` (`ElectionID`, `ElectionName`, `ElectionDate`, `Status`, `StartDate`, `EndDate`) VALUES
(1, 'District Council Elections', '2025-05-15', 'active', '2025-05-10 00:00:00', '2025-05-20 23:59:59'),
(2, 'National Assembly Elections', '2025-06-10', 'upcoming', '2025-06-10 08:00:00', '2025-06-10 18:00:00'),
(3, 'School Board Elections', '2025-04-01', 'completed', '2025-04-01 08:00:00', '2025-04-01 18:00:00');

[All other data inserts from original file]
-- Add ResortID column to candidates table
ALTER TABLE `candidates` ADD `ResortID` int NULL AFTER `DistrictID`;

-- Add foreign key constraint
ALTER TABLE `candidates` ADD CONSTRAINT `candidates_ibfk_4` FOREIGN KEY (`ResortID`) REFERENCES `resorts` (`id`);

-- Add ElectionID column to qrcodes table
ALTER TABLE `qrcodes` 
ADD COLUMN `ElectionID` bigint(20) NOT NULL AFTER `UserID`;

-- Add index for ElectionID
ALTER TABLE `qrcodes`
ADD KEY `ElectionID` (`ElectionID`);

-- Add foreign key constraint
ALTER TABLE `qrcodes`
ADD CONSTRAINT `qrcodes_ibfk_2` FOREIGN KEY (`ElectionID`) REFERENCES `elections` (`ElectionID`);

-- Insert a sample election if none exists
INSERT INTO `elections` (`ElectionName`, `ElectionDate`, `Status`, `StartDate`, `EndDate`) 
SELECT 'Verkiezing 2025', '2025-05-25', 'active', '2025-05-25 08:00:00', '2025-05-25 18:00:00'
WHERE NOT EXISTS (SELECT 1 FROM `elections` WHERE `Status` = 'active'); 
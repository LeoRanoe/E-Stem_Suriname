-- Add ShowResults column to elections table
ALTER TABLE elections
ADD COLUMN ShowResults TINYINT(1) NOT NULL DEFAULT 0;

-- Update existing elections to show results by default
UPDATE elections
SET ShowResults = 1
WHERE Status = 'completed'; 
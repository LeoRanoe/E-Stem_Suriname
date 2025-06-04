-- Add ShowResults column to elections table
ALTER TABLE elections ADD COLUMN ShowResults TINYINT(1) DEFAULT 0 AFTER Status;

-- Update existing elections to show results if they are completed
UPDATE elections SET ShowResults = 1 WHERE Status = 'completed' OR EndDate < NOW();

-- Add Description column to elections table
ALTER TABLE elections
ADD COLUMN Description TEXT DEFAULT NULL; 
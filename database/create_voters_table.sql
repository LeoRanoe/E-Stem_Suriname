-- Create the voters table as specified in the requirements
CREATE TABLE IF NOT EXISTS voters (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  has_voted BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add additional fields to match the existing structure
ALTER TABLE voters 
ADD COLUMN IF NOT EXISTS first_name VARCHAR(50) NOT NULL,
ADD COLUMN IF NOT EXISTS last_name VARCHAR(50) NOT NULL,
ADD COLUMN IF NOT EXISTS id_number VARCHAR(20) NOT NULL,
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active',
ADD COLUMN IF NOT EXISTS district_id INT NOT NULL,
ADD COLUMN IF NOT EXISTS resort_id INT NOT NULL,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add foreign key constraints if they don't exist
ALTER TABLE voters 
ADD CONSTRAINT IF NOT EXISTS voters_district_fk FOREIGN KEY (district_id) REFERENCES districten(DistrictID),
ADD CONSTRAINT IF NOT EXISTS voters_resort_fk FOREIGN KEY (resort_id) REFERENCES resorts(id);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_voters_code ON voters(code);
CREATE INDEX IF NOT EXISTS idx_voters_status ON voters(status);
CREATE INDEX IF NOT EXISTS idx_voters_has_voted ON voters(has_voted);

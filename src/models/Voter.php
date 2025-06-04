<?php

class Voter {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all voters with optional filtering
     * 
     * @param array $filters Optional filters
     * @return array List of voters
     */
    public function getAllVoters($filters = []) {
        $query = "
            SELECT v.id, v.first_name, v.last_name, v.id_number, v.voter_code, 
                   v.status, v.district_id, v.resort_id, v.created_at, v.updated_at,
                   d.DistrictName as district_name, r.name as resort_name
            FROM voters v
            LEFT JOIN districten d ON v.district_id = d.DistrictID
            LEFT JOIN resorts r ON v.resort_id = r.id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Apply filters if provided
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (v.first_name LIKE ? OR v.last_name LIKE ? OR v.id_number LIKE ? OR v.voter_code LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        if (!empty($filters['district_id'])) {
            $query .= " AND v.district_id = ?";
            $params[] = $filters['district_id'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND v.status = ?";
            $params[] = $filters['status'];
        }
        
        $query .= " ORDER BY v.last_name ASC, v.first_name ASC";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getAllVoters: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a single voter by ID
     * 
     * @param int $id Voter ID
     * @return array|false Voter data or false if not found
     */
    public function getVoterById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT v.*, d.DistrictName as district_name, r.name as resort_name
                FROM voters v
                LEFT JOIN districten d ON v.district_id = d.DistrictID
                LEFT JOIN resorts r ON v.resort_id = r.id
                WHERE v.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getVoterById: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new voter
     * 
     * @param array $data Voter data
     * @return int|false The new voter ID or false on failure
     */
    public function createVoter($data) {
        try {
            // Hash password if provided
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO voters (
                    first_name, last_name, id_number, voter_code, password, 
                    status, district_id, resort_id
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?
                )
            ");
            
            $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['id_number'],
                $data['voter_code'],
                $data['password'],
                $data['status'] ?? 'active',
                $data['district_id'],
                $data['resort_id']
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error in createVoter: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing voter
     * 
     * @param int $id Voter ID
     * @param array $data Updated voter data
     * @return bool Success status
     */
    public function updateVoter($id, $data) {
        try {
            $fields = [];
            $params = [];
            
            // Build dynamic update query based on provided data
            foreach ($data as $key => $value) {
                // Skip password if empty
                if ($key === 'password' && empty($value)) {
                    continue;
                }
                
                // Hash password if provided
                if ($key === 'password') {
                    $value = password_hash($value, PASSWORD_DEFAULT);
                }
                
                $fields[] = "$key = ?";
                $params[] = $value;
            }
            
            // Add voter ID to params
            $params[] = $id;
            
            $query = "UPDATE voters SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database error in updateVoter: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a voter
     * 
     * @param int $id Voter ID
     * @return bool Success status
     */
    public function deleteVoter($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM voters WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Database error in deleteVoter: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Import voters from CSV data
     * 
     * @param array $csvData Array of voter data from CSV
     * @return array Results with success count and errors
     */
    public function importFromCSV($csvData) {
        $results = [
            'success' => 0,
            'errors' => []
        ];
        
        $this->pdo->beginTransaction();
        
        try {
            foreach ($csvData as $index => $row) {
                // Skip header row if present
                if ($index === 0 && isset($row['first_name']) && $row['first_name'] === 'first_name') {
                    continue;
                }
                
                // Validate required fields
                if (empty($row['first_name']) || empty($row['last_name']) || 
                    empty($row['id_number']) || empty($row['district_id']) || 
                    empty($row['resort_id'])) {
                    $results['errors'][] = "Row " . ($index + 1) . ": Missing required fields";
                    continue;
                }
                
                // Check if voter with same ID number already exists
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM voters WHERE id_number = ?");
                $stmt->execute([$row['id_number']]);
                if ($stmt->fetchColumn() > 0) {
                    $results['errors'][] = "Row " . ($index + 1) . ": Voter with ID number {$row['id_number']} already exists";
                    continue;
                }
                
                // Generate voter code if not provided
                if (empty($row['voter_code'])) {
                    $row['voter_code'] = $this->generateVoterCode();
                }
                
                // Generate password if not provided
                if (empty($row['password'])) {
                    $row['password'] = $this->generateRandomPassword();
                    // Store the plain password temporarily for display to admin
                    $row['plain_password'] = $row['password'];
                }
                
                // Create the voter
                $voterId = $this->createVoter($row);
                
                if ($voterId) {
                    $results['success']++;
                    // Add to successful imports with the plain password for display
                    if (isset($row['plain_password'])) {
                        $results['imported'][] = [
                            'id' => $voterId,
                            'first_name' => $row['first_name'],
                            'last_name' => $row['last_name'],
                            'voter_code' => $row['voter_code'],
                            'password' => $row['plain_password']
                        ];
                    }
                } else {
                    $results['errors'][] = "Row " . ($index + 1) . ": Failed to import voter";
                }
            }
            
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in importFromCSV: " . $e->getMessage());
            $results['errors'][] = "Database error: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Generate a unique voter code
     * 
     * @return string Unique voter code
     */
    private function generateVoterCode() {
        $prefix = 'V';
        $code = $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        
        // Check if code already exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM voters WHERE voter_code = ?");
        $stmt->execute([$code]);
        
        // If code exists, generate a new one
        if ($stmt->fetchColumn() > 0) {
            return $this->generateVoterCode();
        }
        
        return $code;
    }
    
    /**
     * Generate a random password
     * 
     * @return string Random password
     */
    private function generateRandomPassword() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }
    
    /**
     * Get all districts
     * 
     * @return array List of districts
     */
    public function getAllDistricts() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM districten ORDER BY DistrictName ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getAllDistricts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get resorts by district ID
     * 
     * @param int $districtId District ID
     * @return array List of resorts in the district
     */
    public function getResortsByDistrict($districtId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id as ResortID, name as ResortName FROM resorts WHERE district_id = ? ORDER BY name ASC");
            $stmt->execute([$districtId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getResortsByDistrict: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total count of voters with optional filtering
     * 
     * @param array $filters Optional filters
     * @return int Total count of voters
     */
    public function getVoterCount($filters = []) {
        $query = "SELECT COUNT(*) FROM voters v WHERE 1=1";
        $params = [];
        
        // Apply filters if provided
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (v.first_name LIKE ? OR v.last_name LIKE ? OR v.id_number LIKE ? OR v.voter_code LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        if (!empty($filters['district_id'])) {
            $query .= " AND v.district_id = ?";
            $params[] = $filters['district_id'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND v.status = ?";
            $params[] = $filters['status'];
        }
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in getVoterCount: " . $e->getMessage());
            return 0;
        }
    }
    
    // This method is already defined above
}

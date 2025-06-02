<?php

class Voucher {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all vouchers with optional filtering
     * 
     * @param array $filters Optional filters
     * @return array List of vouchers
     */
    public function getAllVouchers($filters = []) {
        $query = "
            SELECT v.*, 
                   vt.first_name, vt.last_name, 
                   d.DistrictName as district_name,
                   r.name as resort_name
            FROM vouchers v
            LEFT JOIN voters vt ON v.voter_id = vt.id
            LEFT JOIN districten d ON vt.district_id = d.DistrictID
            LEFT JOIN resorts r ON vt.resort_id = r.id
            WHERE 1=1
        ";
        
        $params = [];
        
        // Apply filters if provided
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $query .= " AND (v.voucher_id LIKE ? OR vt.first_name LIKE ? OR vt.last_name LIKE ?)";
            $params = array_merge($params, [$search, $search, $search]);
        }
        
        if (isset($filters['used']) && $filters['used'] !== '') {
            $query .= " AND v.used = ?";
            $params[] = $filters['used'];
        }
        
        if (!empty($filters['district_id'])) {
            $query .= " AND vt.district_id = ?";
            $params[] = $filters['district_id'];
        }
        
        $query .= " ORDER BY v.created_at DESC";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getAllVouchers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a single voucher by ID
     * 
     * @param int $id Voucher ID
     * @return array|false Voucher data or false if not found
     */
    public function getVoucherById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT v.*, 
                       vt.first_name, vt.last_name, 
                       d.DistrictName as district_name,
                       r.name as resort_name
                FROM vouchers v
                LEFT JOIN voters vt ON v.voter_id = vt.id
                LEFT JOIN districten d ON vt.district_id = d.DistrictID
                LEFT JOIN resorts r ON vt.resort_id = r.id
                WHERE v.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getVoucherById: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get a voucher by voucher_id
     * 
     * @param string $voucherId The voucher ID
     * @return array|false Voucher data or false if not found
     */
    public function getVoucherByVoucherId($voucherId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT v.*, 
                       vt.first_name, vt.last_name, 
                       d.DistrictName as district_name,
                       r.name as resort_name
                FROM vouchers v
                LEFT JOIN voters vt ON v.voter_id = vt.id
                LEFT JOIN districten d ON vt.district_id = d.DistrictID
                LEFT JOIN resorts r ON vt.resort_id = r.id
                WHERE v.voucher_id = ?
            ");
            $stmt->execute([$voucherId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getVoucherByVoucherId: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new voucher
     * 
     * @param array $data Voucher data
     * @return int|false The new voucher ID or false on failure
     */
    public function createVoucher($data) {
        try {
            // Hash password if provided
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO vouchers (
                    voter_id, voucher_id, password, used, created_at
                ) VALUES (
                    ?, ?, ?, ?, NOW()
                )
            ");
            
            $stmt->execute([
                $data['voter_id'],
                $data['voucher_id'],
                $data['password'],
                $data['used'] ?? 0
            ]);
            
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Database error in createVoucher: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing voucher
     * 
     * @param int $id Voucher ID
     * @param array $data Updated voucher data
     * @return bool Success status
     */
    public function updateVoucher($id, $data) {
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
            
            // Add voucher ID to params
            $params[] = $id;
            
            $query = "UPDATE vouchers SET " . implode(", ", $fields) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database error in updateVoucher: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark a voucher as used
     * 
     * @param int $id Voucher ID
     * @return bool Success status
     */
    public function markVoucherAsUsed($id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE vouchers SET used = 1 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Database error in markVoucherAsUsed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a voucher
     * 
     * @param int $id Voucher ID
     * @return bool Success status
     */
    public function deleteVoucher($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM vouchers WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Database error in deleteVoucher: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate vouchers for multiple voters
     * 
     * @param array $voterIds Array of voter IDs
     * @return array Results with success count and errors
     */
    public function generateBulkVouchers($voterIds) {
        $results = [
            'success' => 0,
            'errors' => [],
            'vouchers' => []
        ];
        
        $this->pdo->beginTransaction();
        
        try {
            foreach ($voterIds as $voterId) {
                // Check if voter exists
                $stmt = $this->pdo->prepare("SELECT id, first_name, last_name FROM voters WHERE id = ?");
                $stmt->execute([$voterId]);
                $voter = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$voter) {
                    $results['errors'][] = "Voter with ID $voterId not found";
                    continue;
                }
                
                // Check if voucher already exists for this voter
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE voter_id = ?");
                $stmt->execute([$voterId]);
                if ($stmt->fetchColumn() > 0) {
                    $results['errors'][] = "Voter {$voter['first_name']} {$voter['last_name']} already has a voucher";
                    continue;
                }
                
                // Generate voucher ID and password
                $voucherId = $this->generateVoucherId();
                $password = $this->generateRandomPassword();
                
                // Create voucher
                $data = [
                    'voter_id' => $voterId,
                    'voucher_id' => $voucherId,
                    'password' => $password,
                    'used' => 0
                ];
                
                $newVoucherId = $this->createVoucher($data);
                
                if ($newVoucherId) {
                    $results['success']++;
                    $results['vouchers'][] = [
                        'id' => $newVoucherId,
                        'voter_id' => $voterId,
                        'voter_name' => $voter['first_name'] . ' ' . $voter['last_name'],
                        'voucher_id' => $voucherId,
                        'password' => $password // Plain password for display
                    ];
                } else {
                    $results['errors'][] = "Failed to create voucher for voter {$voter['first_name']} {$voter['last_name']}";
                }
            }
            
            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error in generateBulkVouchers: " . $e->getMessage());
            $results['errors'][] = "Database error: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Generate a single voucher for a voter
     * 
     * @param int $voterId Voter ID
     * @return array|false Voucher data or false on failure
     */
    public function generateSingleVoucher($voterId) {
        try {
            // Check if voter exists
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name FROM voters WHERE id = ?");
            $stmt->execute([$voterId]);
            $voter = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$voter) {
                return false;
            }
            
            // Generate voucher ID and password
            $voucherId = $this->generateVoucherId();
            $password = $this->generateRandomPassword();
            
            // Create voucher
            $data = [
                'voter_id' => $voterId,
                'voucher_id' => $voucherId,
                'password' => $password,
                'used' => 0
            ];
            
            $this->pdo->beginTransaction();
            
            // Delete existing vouchers for this voter
            $stmt = $this->pdo->prepare("DELETE FROM vouchers WHERE voter_id = ?");
            $stmt->execute([$voterId]);
            
            // Create new voucher
            $newVoucherId = $this->createVoucher($data);
            
            if ($newVoucherId) {
                $this->pdo->commit();
                return [
                    'id' => $newVoucherId,
                    'voter_id' => $voterId,
                    'voter_name' => $voter['first_name'] . ' ' . $voter['last_name'],
                    'voucher_id' => $voucherId,
                    'password' => $password // Plain password for display
                ];
            } else {
                $this->pdo->rollBack();
                return false;
            }
        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error in generateSingleVoucher: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify a voucher for login
     * 
     * @param string $voucherId Voucher ID
     * @param string $password Password
     * @return array|false Voter data if verified, false otherwise
     */
    public function verifyVoucher($voucherId, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT v.*, vt.* 
                FROM vouchers v
                JOIN voters vt ON v.voter_id = vt.id
                WHERE v.voucher_id = ? AND v.used = 0
            ");
            $stmt->execute([$voucherId]);
            $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$voucher) {
                return false;
            }
            
            // Verify password
            if (!password_verify($password, $voucher['password'])) {
                return false;
            }
            
            return $voucher;
        } catch (PDOException $e) {
            error_log("Database error in verifyVoucher: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate a unique voucher ID
     * 
     * @return string Unique voucher ID
     */
    private function generateVoucherId() {
        $prefix = 'VC';
        $code = $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        
        // Check if code already exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM vouchers WHERE voucher_id = ?");
        $stmt->execute([$code]);
        
        // If code exists, generate a new one
        if ($stmt->fetchColumn() > 0) {
            return $this->generateVoucherId();
        }
        
        return $code;
    }
    
    /**
     * Generate a random password
     * 
     * @return string Random password
     */
    private function generateRandomPassword() {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        for ($i = 0; $i < 6; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }
    
    /**
     * Check if vouchers table exists, create if not
     * 
     * @return bool Success status
     */
    public function ensureVouchersTableExists() {
        try {
            // Check if table exists
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'vouchers'");
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                // Create vouchers table
                $this->pdo->exec("
                    CREATE TABLE `vouchers` (
                      `id` int NOT NULL AUTO_INCREMENT,
                      `voter_id` int NOT NULL,
                      `voucher_id` varchar(20) NOT NULL,
                      `password` varchar(255) NOT NULL,
                      `used` tinyint(1) DEFAULT '0',
                      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `voucher_id` (`voucher_id`),
                      KEY `voter_id` (`voter_id`),
                      KEY `used` (`used`),
                      CONSTRAINT `vouchers_ibfk_1` FOREIGN KEY (`voter_id`) REFERENCES `voters` (`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
                ");
                
                return true;
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Database error in ensureVouchersTableExists: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify QR code data
     * 
     * @param string $qrData QR code data (voucher ID)
     * @return array|false Voter data if verified, false otherwise
     */
    public function verifyQRCode($qrData) {
        try {
            // Check if voucher exists and is not used
            $stmt = $this->pdo->prepare("
                SELECT v.*, vt.* 
                FROM vouchers v
                JOIN voters vt ON v.voter_id = vt.id
                WHERE v.voucher_id = ? AND v.used = 0
            ");
            $stmt->execute([$qrData]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Database error in verifyQRCode: " . $e->getMessage());
            return false;
        }
    }

}

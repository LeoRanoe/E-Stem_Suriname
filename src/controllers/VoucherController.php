<?php
/**
 * VoucherController - Handles voucher generation, verification, and management
 * 
 * Part of the E-Stem Suriname voting system
 */
class VoucherController {
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Generate a unique voucher code
     * 
     * @return string The generated voucher code
     */
    public function generateVoucherCode() {
        // Generate a unique voucher code (format: ABC123XYZ)
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $numbers = '123456789';
        
        do {
            $code = '';
            // First 3 characters (letters)
            for ($i = 0; $i < 3; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            
            // Middle 3 characters (numbers)
            for ($i = 0; $i < 3; $i++) {
                $code .= $numbers[random_int(0, strlen($numbers) - 1)];
            }
            
            // Last 3 characters (letters)
            for ($i = 0; $i < 3; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            
            // Check if code already exists
            $stmt = $this->pdo->prepare("SELECT id FROM voters WHERE code = ?");
            $stmt->execute([$code]);
        } while ($stmt->rowCount() > 0);
        
        return $code;
    }
    
    /**
     * Generate a secure password
     * 
     * @return string The generated password
     */
    public function generatePassword() {
        // Generate a secure password (format similar to 8J7f$L90)
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghijkmnopqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '$@#!%*?&';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Add 4 more random characters
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 0; $i < 4; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Shuffle the password
        $password = str_shuffle($password);
        
        return $password;
    }
    
    /**
     * Create a new voucher for a voter
     * 
     * @param int $voterId The ID of the voter
     * @return array|false The voucher data or false on failure
     */
    public function createVoucher($voterId) {
        try {
            // Check if voter exists
            $stmt = $this->pdo->prepare("SELECT id, first_name, last_name FROM voters WHERE id = ?");
            $stmt->execute([$voterId]);
            $voter = $stmt->fetch();
            
            if (!$voter) {
                return false;
            }
            
            // Generate voucher code and password
            $code = $this->generateVoucherCode();
            $password = $this->generatePassword();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Update voter record with voucher data
            $stmt = $this->pdo->prepare("
                UPDATE voters 
                SET code = ?, password = ? 
                WHERE id = ?
            ");
            $success = $stmt->execute([$code, $hashedPassword, $voterId]);
            
            if (!$success) {
                return false;
            }
            
            // Return voucher data
            return [
                'voter_id' => $voterId,
                'voter_name' => $voter['first_name'] . ' ' . $voter['last_name'],
                'code' => $code,
                'password' => $password,
                'qr_url' => BASE_URL . '/voter/index.php?code=' . $code
            ];
        } catch (PDOException $e) {
            error_log("Error creating voucher: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify a voucher
     * 
     * @param string $code The voucher code
     * @param string $password The voucher password
     * @return array|false The voter data or false on failure
     */
    public function verifyVoucher($code, $password) {
        try {
            // Get voter by voucher code
            $stmt = $this->pdo->prepare("
                SELECT id, password, has_voted, first_name, last_name 
                FROM voters 
                WHERE code = ? AND status = 'active'
            ");
            $stmt->execute([$code]);
            $voter = $stmt->fetch();
            
            if (!$voter) {
                return false;
            }
            
            // Verify password
            if (!password_verify($password, $voter['password'])) {
                return false;
            }
            
            // Check if already voted
            if ($voter['has_voted']) {
                return [
                    'error' => 'already_voted',
                    'message' => 'This voucher has already been used to vote.'
                ];
            }
            
            // Return voter data
            return [
                'voter_id' => $voter['id'],
                'voter_name' => $voter['first_name'] . ' ' . $voter['last_name'],
                'has_voted' => false
            ];
        } catch (PDOException $e) {
            error_log("Error verifying voucher: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark a voucher as used
     * 
     * @param int $voterId The ID of the voter
     * @return bool Success or failure
     */
    public function markVoucherAsUsed($voterId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE voters 
                SET has_voted = TRUE 
                WHERE id = ?
            ");
            return $stmt->execute([$voterId]);
        } catch (PDOException $e) {
            error_log("Error marking voucher as used: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get QR code URL
     * 
     * @param string $code The voucher code
     * @return string The URL for the QR code
     */
    public function getQRCodeUrl($code) {
        return BASE_URL . '/voter/index.php?code=' . urlencode($code);
    }
    
    /**
     * Get all vouchers with optional filters
     * 
     * @param array $filters Optional filters
     * @return array The vouchers
     */
    public function getAllVouchers($filters = []) {
        try {
            $sql = "
                SELECT v.id, v.code, v.has_voted, v.first_name, v.last_name,
                       d.DistrictName as district_name, r.name as resort_name,
                       v.created_at
                FROM voters v
                LEFT JOIN districten d ON v.district_id = d.DistrictID
                LEFT JOIN resorts r ON v.resort_id = r.id
                WHERE 1=1
            ";
            $params = [];
            
            // Apply filters
            if (!empty($filters['search'])) {
                $sql .= " AND (v.code LIKE ? OR v.first_name LIKE ? OR v.last_name LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (isset($filters['has_voted']) && $filters['has_voted'] !== '') {
                $sql .= " AND v.has_voted = ?";
                $params[] = (int)$filters['has_voted'];
            }
            
            if (!empty($filters['district_id'])) {
                $sql .= " AND v.district_id = ?";
                $params[] = $filters['district_id'];
            }
            
            $sql .= " ORDER BY v.created_at DESC";
            
            // Add limit and offset if provided
            if (isset($filters['limit']) && isset($filters['offset'])) {
                $sql .= " LIMIT ? OFFSET ?";
                $params[] = (int)$filters['limit'];
                $params[] = (int)$filters['offset'];
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error getting vouchers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get voucher count with optional filters
     * 
     * @param array $filters Optional filters
     * @return int The voucher count
     */
    public function getVoucherCount($filters = []) {
        try {
            $sql = "SELECT COUNT(*) FROM voters WHERE 1=1";
            $params = [];
            
            // Apply filters
            if (!empty($filters['search'])) {
                $sql .= " AND (code LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (isset($filters['has_voted']) && $filters['has_voted'] !== '') {
                $sql .= " AND has_voted = ?";
                $params[] = (int)$filters['has_voted'];
            }
            
            if (!empty($filters['district_id'])) {
                $sql .= " AND district_id = ?";
                $params[] = $filters['district_id'];
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting voucher count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Handle voucher-related actions
     */
    public function handleActions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create_voucher':
                $voterId = $_POST['voter_id'] ?? 0;
                $voucher = $this->createVoucher($voterId);
                if ($voucher) {
                    $_SESSION['success_message'] = "Voucher created successfully.";
                    $_SESSION['voucher_data'] = $voucher;
                } else {
                    $_SESSION['error_message'] = "Failed to create voucher.";
                }
                break;
                
            case 'delete_voucher':
                $voterId = $_POST['voter_id'] ?? 0;
                $success = $this->deleteVoucher($voterId);
                if ($success) {
                    $_SESSION['success_message'] = "Voucher deleted successfully.";
                } else {
                    $_SESSION['error_message'] = "Failed to delete voucher.";
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    
    /**
     * Delete a voucher
     * 
     * @param int $voterId The ID of the voter
     * @return bool Success or failure
     */
    public function deleteVoucher($voterId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE voters 
                SET code = NULL, password = NULL 
                WHERE id = ?
            ");
            return $stmt->execute([$voterId]);
        } catch (PDOException $e) {
            error_log("Error deleting voucher: " . $e->getMessage());
            return false;
        }
    }
}

<?php

require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../models/Voter.php';

class VoterController {
    private $voterModel;
    
    public function __construct() {
        global $pdo;
        $this->voterModel = new Voter($pdo);
    }
    
    /**
     * Handle all voter-related actions
     */
    public function handleActions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
            return;
        }
        
        try {
            switch ($_POST['action']) {
                case 'create':
                    $this->createVoter();
                    break;
                    
                case 'update':
                    $this->updateVoter();
                    break;
                    
                case 'delete':
                    $this->deleteVoter();
                    break;
                    
                case 'import':
                    $this->importVoters();
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
    }
    
    /**
     * Create a new voter
     */
    private function createVoter() {
        // Validate input
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $id_number = trim($_POST['id_number'] ?? '');
        $district_id = intval($_POST['district_id'] ?? 0);
        $resort_id = intval($_POST['resort_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($id_number) || empty($district_id) || empty($resort_id)) {
            throw new Exception('All required fields must be filled in.');
        }
        
        if (!empty($password) && $password !== $confirm_password) {
            throw new Exception('Passwords do not match.');
        }
        
        // Generate voter code if not provided
        $voter_code = trim($_POST['voter_code'] ?? '');
        if (empty($voter_code)) {
            // The model will generate a voter code
        }
        
        // Create voter
        $voterData = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'id_number' => $id_number,
            'voter_code' => $voter_code,
            'password' => $password,
            'district_id' => $district_id,
            'resort_id' => $resort_id,
            'status' => 'active'
        ];
        
        $voterId = $this->voterModel->createVoter($voterData);
        
        if ($voterId) {
            $_SESSION['success_message'] = "Voter successfully added.";
            $_SESSION['redirect'] = "admin/voters.php";
        } else {
            throw new Exception('Failed to add voter. Please try again.');
        }
    }
    
    /**
     * Update an existing voter
     */
    private function updateVoter() {
        $voter_id = intval($_POST['voter_id'] ?? 0);
        
        if ($voter_id <= 0) {
            throw new Exception('Invalid voter ID.');
        }
        
        // Validate input
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $id_number = trim($_POST['id_number'] ?? '');
        $district_id = intval($_POST['district_id'] ?? 0);
        $resort_id = intval($_POST['resort_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($id_number) || empty($district_id) || empty($resort_id)) {
            throw new Exception('All required fields must be filled in.');
        }
        
        if (!empty($password) && $password !== $confirm_password) {
            throw new Exception('Passwords do not match.');
        }
        
        // Update voter
        $voterData = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'id_number' => $id_number,
            'district_id' => $district_id,
            'resort_id' => $resort_id,
            'status' => $status
        ];
        
        // Only include password if provided
        if (!empty($password)) {
            $voterData['password'] = $password;
        }
        
        $success = $this->voterModel->updateVoter($voter_id, $voterData);
        
        if ($success) {
            $_SESSION['success_message'] = "Voter successfully updated.";
            $_SESSION['redirect'] = "admin/voters.php";
        } else {
            throw new Exception('Failed to update voter. Please try again.');
        }
    }
    
    /**
     * Delete a voter
     */
    private function deleteVoter() {
        $voter_id = intval($_POST['voter_id'] ?? 0);
        
        if ($voter_id <= 0) {
            throw new Exception('Invalid voter ID.');
        }
        
        $success = $this->voterModel->deleteVoter($voter_id);
        
        if ($success) {
            $_SESSION['success_message'] = "Voter successfully deleted.";
            $_SESSION['redirect'] = "admin/voters.php";
        } else {
            throw new Exception('Failed to delete voter. Please try again.');
        }
    }
    
    /**
     * Import voters from CSV
     */
    private function importVoters() {
        // Check if file was uploaded
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Please upload a valid CSV file.');
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        
        // Parse CSV
        $csvData = [];
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Get header row
            $header = fgetcsv($handle, 1000, ",");
            
            // Convert header to lowercase for consistency
            $header = array_map('strtolower', $header);
            
            // Check required columns
            $requiredColumns = ['first_name', 'last_name', 'id_number', 'district_id', 'resort_id'];
            $missingColumns = array_diff($requiredColumns, $header);
            
            if (!empty($missingColumns)) {
                fclose($handle);
                throw new Exception('CSV file is missing required columns: ' . implode(', ', $missingColumns));
            }
            
            // Read data rows
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) != count($header)) {
                    continue; // Skip rows with incorrect column count
                }
                
                $row = array_combine($header, $data);
                $csvData[] = $row;
            }
            fclose($handle);
        } else {
            throw new Exception('Failed to open CSV file.');
        }
        
        if (empty($csvData)) {
            throw new Exception('No data found in CSV file.');
        }
        
        // Import voters
        $result = $this->voterModel->importFromCSV($csvData);
        
        if ($result['success'] > 0) {
            $_SESSION['success_message'] = "Successfully imported {$result['success']} voters.";
            
            if (!empty($result['errors'])) {
                $_SESSION['warning_message'] = "There were {$result['errors']} errors during import. Check the logs for details.";
                // Log errors
                foreach ($result['errors'] as $error) {
                    error_log("CSV Import Error: " . $error);
                }
            }
            
            // Store imported voters in session for display
            if (!empty($result['imported'])) {
                $_SESSION['imported_voters'] = $result['imported'];
            }
            
            $_SESSION['redirect'] = "admin/voters.php";
        } else {
            throw new Exception('Failed to import voters: ' . implode(', ', $result['errors']));
        }
    }
    
    /**
     * Get all voters with optional filtering
     * 
     * @param array $filters Optional filters
     * @return array List of voters
     */
    public function getAllVoters($filters = []) {
        return $this->voterModel->getAllVoters($filters);
    }
    
    /**
     * Get voter by ID
     * 
     * @param int $voterId Voter ID
     * @return array|false Voter data or false if not found
     */
    public function getVoterById($voterId) {
        return $this->voterModel->getVoterById($voterId);
    }
    
    /**
     * Get resorts by district ID
     * 
     * @param int $districtId District ID
     * @return array List of resorts
     */
    public function getResortsByDistrict($districtId) {
        return $this->voterModel->getResortsByDistrict($districtId);
    }
    
    /**
     * Get total votes
     * 
     * @return int Total vote count
     */
    public function getTotalVotes() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM votes");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in getTotalVotes: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get total districts
     * 
     * @return int Total district count
     */
    public function getTotalDistricts() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM districten");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in getTotalDistricts: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get total active voters
     * 
     * @return int Total active voter count
     */
    public function getTotalActiveVoters() {
        global $pdo;
        try {
            $stmt = $pdo->query("
                SELECT COUNT(DISTINCT UserID) 
                FROM votes 
                WHERE TimeStamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in getTotalActiveVoters: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get all districts
     * 
     * @return array List of all districts
     */
    public function getAllDistricts() {
        return $this->voterModel->getAllDistricts();
    }
    
    // The getResortsByDistrict method is already defined above
    
    /**
     * Get total count of voters with optional filtering
     * 
     * @param array $filters Optional filters
     * @return int Total count of voters
     */
    public function getVoterCount($filters = []) {
        return $this->voterModel->getVoterCount($filters);
    }
}
?>
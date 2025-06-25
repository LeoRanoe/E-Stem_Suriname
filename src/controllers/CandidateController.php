<?php

require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/config.php';

class CandidateController {

    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function handleRequest() {
        // session_start(); // Removed: Session should be started by the view or entry script
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            $this->handlePostRequest();
        }
    }

    private function handlePostRequest() {
        try {
            switch ($_POST['action']) {
                case 'create':
                    $this->createCandidate();
                    $_SESSION['success_message'] = "Kandidaat is succesvol toegevoegd.";
                    break;
                case 'edit':
                    $this->editCandidate();
                    $_SESSION['success_message'] = "Kandidaat is succesvol bijgewerkt.";
                    break;
                case 'delete':
                    $this->deleteCandidate();
                    $_SESSION['success_message'] = "Kandidaat is succesvol verwijderd.";
                    break;
            }
        } catch (Exception $e) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                exit;
            }
            $_SESSION['error_message'] = $e->getMessage();
        }
        // For AJAX requests, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => $_SESSION['success_message']]);
            unset($_SESSION['success_message']);
            exit;
        }

        // Redirect back to the view for non-AJAX requests
        header("Location: " . BASE_URL . "/src/views/candidates.php");
        exit;
    }

    private function handleImageUpload() {
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            error_log("File upload detected. File info: " . print_r($_FILES['image'], true));
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                throw new Exception('Ongeldig bestandstype. Alleen JPG, PNG en GIF zijn toegestaan.');
            }

            if ($_FILES['image']['size'] > $max_size) {
                throw new Exception('Bestand is te groot. Maximum grootte is 5MB.');
            }

            $upload_dir = __DIR__ . '/../../uploads/candidates/';
            
            // Verify upload directory exists and is writable
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    error_log("Failed to create upload directory: " . $upload_dir);
                    throw new Exception('Kon upload directory niet aanmaken.');
                }
            }
            
            if (!is_writable($upload_dir)) {
                error_log("Upload directory not writable: " . $upload_dir . " Permissions: " . substr(sprintf('%o', fileperms($upload_dir)), -4));
                throw new Exception('Upload directory is niet beschrijfbaar.');
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $firstName = $_POST['firstName'] ?? '';
            $lastName = $_POST['lastName'] ?? '';
            $name = !empty($firstName) && !empty($lastName) ? $firstName . '_' . $lastName : ($_POST['name'] ?? 'candidate');
            $file_name = $name . '_' . uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            error_log("Attempting to move uploaded file to: " . $target_path);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                error_log("File moved successfully to: " . $target_path);
                $image_path = 'uploads/candidates/' . $file_name;
            } else {
                error_log("Failed to move uploaded file. Error: " . print_r(error_get_last(), true));
                throw new Exception('Kon de afbeelding niet uploaden.');
            }
        }
        return $image_path;
    }
    
    // Overload handleImageUpload for edit scenario
    private function handleImageUploadForEdit($candidate_id) {
        $stmt = $this->pdo->prepare("SELECT Photo FROM candidates WHERE CandidateID = ?"); // Changed ImagePath to Photo
        $stmt->execute([$candidate_id]);
        $current_image = $stmt->fetchColumn();
        
        $image_path = $current_image; // Keep current image by default

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
             // Use the general upload logic
             $new_image_path = $this->handleImageUpload(); 
             if ($new_image_path) {
                 // Delete old image if a new one is uploaded and the old one exists
                if ($current_image && file_exists(__DIR__ . '/../../' . $current_image)) {
                    unlink(__DIR__ . '/../../' . $current_image);
                }
                $image_path = $new_image_path;
             }
        }
        return $image_path;
    }


    private function createCandidate() {
        $name = ($_POST['firstName'] ?? '') . ' ' . ($_POST['lastName'] ?? ''); // Combine names
        $party_id = intval($_POST['party_id'] ?? 0);
        $election_id = intval($_POST['election_id'] ?? 0);
        $candidate_type = $_POST['candidate_type'] ?? 'RR';
        
        $district_id = ($candidate_type === 'RR') ? intval($_POST['district_id'] ?? 0) : null;
        $resort_id = ($candidate_type === 'RR') ? intval($_POST['resort_id'] ?? 0) : null;

        if (empty(trim($name)) || empty($election_id) || empty($party_id)) {
            throw new Exception('Vul alle verplichte velden in (Naam, Partij, Verkiezing).');
        }

        // For RR candidates, resort and district are required
        if ($candidate_type === 'RR' && (empty($district_id) || empty($resort_id))) {
            throw new Exception('Voor RR kandidaten zijn een district en resort verplicht.');
        }

        $image_path = $this->handleImageUpload();

        $stmt = $this->pdo->prepare("
            INSERT INTO candidates (Name, PartyID, ElectionID, DistrictID, ResortID, CandidateType, Photo, CreatedAt)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([trim($name), $party_id, $election_id, $district_id, $resort_id, $candidate_type, $image_path]);
    }

    private function editCandidate() {
        $candidate_id = intval($_POST['candidate_id']);
        $name = ($_POST['firstName'] ?? '') . ' ' . ($_POST['lastName'] ?? '');
        $party_id = intval($_POST['party_id'] ?? 0);
        $candidate_type = $_POST['candidate_type'] ?? 'RR';

        $district_id = ($candidate_type === 'RR') ? intval($_POST['district_id'] ?? 0) : null;
        $resort_id = ($candidate_type === 'RR') ? intval($_POST['resort_id'] ?? 0) : null;

        if (empty(trim($name)) || empty($party_id)) {
            throw new Exception('Vul alle verplichte velden in (Naam, Partij).');
        }

        // For RR candidates, resort and district are required
        if ($candidate_type === 'RR' && (empty($district_id) || empty($resort_id))) {
            throw new Exception('Voor RR kandidaten zijn een district en resort verplicht.');
        }

        $image_path = $this->handleImageUploadForEdit($candidate_id);

        $stmt = $this->pdo->prepare("
            UPDATE candidates
            SET Name = ?, PartyID = ?, DistrictID = ?, ResortID = ?, CandidateType = ?, Photo = ?
            WHERE CandidateID = ?
        ");
        error_log("Updating candidate with ID $candidate_id. New image path: " . ($image_path ?? 'null'));
        $stmt->execute([trim($name), $party_id, $district_id, $resort_id, $candidate_type, $image_path, $candidate_id]);
        
        // Verify update
        $stmt = $this->pdo->prepare("SELECT Photo FROM candidates WHERE CandidateID = ?");
        $stmt->execute([$candidate_id]);
        $updated_image = $stmt->fetchColumn();
        error_log("After update, candidate photo is: " . ($updated_image ?? 'null'));
    }

    private function deleteCandidate() {
        $candidate_id = intval($_POST['candidate_id']);
        
        // Get image path before deleting
        $stmt = $this->pdo->prepare("SELECT Photo FROM candidates WHERE CandidateID = ?"); // Changed ImagePath to Photo
        $stmt->execute([$candidate_id]);
        $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

        // Delete candidate
        $stmt = $this->pdo->prepare("DELETE FROM candidates WHERE CandidateID = ?");
        $stmt->execute([$candidate_id]);

        // Delete image file if exists
        if ($candidate && $candidate['Photo'] && file_exists(__DIR__ . '/../../' . $candidate['Photo'])) { // Changed ImagePath to Photo
            unlink(__DIR__ . '/../../' . $candidate['Photo']); // Changed ImagePath to Photo
        }
    }

    public function getCandidates($page = 1, $per_page = 10, $filters = []) {
        $offset = ($page - 1) * $per_page;
        try {
            $where = [];
            $params = [];

            // Build WHERE clauses based on filters
            if (!empty($filters['election_id'])) {
                $where[] = "c.ElectionID = ?";
                $params[] = $filters['election_id'];
            }
            if (!empty($filters['district_id'])) {
                $where[] = "c.DistrictID = ?";
                $params[] = $filters['district_id'];
            }
            if (!empty($filters['resort_id'])) {
                $where[] = "c.ResortID = ?";
                $params[] = $filters['resort_id'];
            }
            if (!empty($filters['party_id'])) {
                $where[] = "c.PartyID = ?";
                $params[] = $filters['party_id'];
            }
            if (!empty($filters['candidate_type'])) {
                $where[] = "c.CandidateType = ?";
                $params[] = $filters['candidate_type'];
            }

            $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

            $countSql = "SELECT COUNT(DISTINCT c.CandidateID) 
                         FROM candidates c 
                         $whereClause";
            
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $dataSql = "SELECT c.*, p.PartyName, e.ElectionName, d.DistrictName, r.name as ResortName
                        FROM candidates c
                        LEFT JOIN parties p ON c.PartyID = p.PartyID
                        LEFT JOIN elections e ON c.ElectionID = e.ElectionID
                        LEFT JOIN districten d ON c.DistrictID = d.DistrictID
                        LEFT JOIN resorts r ON c.ResortID = r.id
                        $whereClause
                        GROUP BY c.CandidateID
                        ORDER BY c.CreatedAt DESC
                        LIMIT ? OFFSET ?";

            $all_params = array_merge($params, [$per_page, $offset]);
            
            $stmt = $this->pdo->prepare($dataSql);
            $stmt->execute($all_params);

            $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'data' => $candidates,
                'total' => $total,
                'page' => $page,
                'per_page' => $per_page
            ];
            
        } catch (PDOException $e) {
            error_log("Database error fetching candidates: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Database error occurred: ' . $e->getMessage(),
                'data' => [],
                'total' => 0
            ];
        }
    }
    
    public function getTotalCandidatesCount($filters = []) {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['district_id'])) {
                $where[] = "DistrictID = ?";
                $params[] = $filters['district_id'];
            }
            if (!empty($filters['resort_id'])) {
                $where[] = "ResortID = ?";
                $params[] = $filters['resort_id'];
            }
            if (!empty($filters['party_id'])) {
                $where[] = "PartyID = ?";
                $params[] = $filters['party_id'];
            }
            if (!empty($filters['candidate_type'])) {
                $where[] = "CandidateType = ?";
                $params[] = $filters['candidate_type'];
            }

            $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
            $sql = "SELECT COUNT(*) FROM candidates $whereClause";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error fetching total candidates: " . $e->getMessage());
            return 0;
        }
    }

    public function getFormData() {
        $data = [
            'elections' => [],
            'parties' => [],
            'districts' => []
        ];
         try {
            $stmt = $this->pdo->query("SELECT ElectionID, ElectionName FROM elections WHERE Status = 'active' ORDER BY ElectionName");
            $data['elections'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $this->pdo->query("SELECT PartyID, PartyName FROM parties ORDER BY PartyName");
            $data['parties'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $this->pdo->query("SELECT DistrictID, DistrictName FROM districten ORDER BY DistrictName");
            $data['districts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Database error fetching form data: " . $e->getMessage());
            $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van formulier data.";
        }
        return $data;
    }
}

// Instantiate and handle request if accessed directly
$controller = new CandidateController();
$controller->handleRequest();

?>
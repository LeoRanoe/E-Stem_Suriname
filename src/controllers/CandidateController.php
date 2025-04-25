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
                case 'delete':
                    $this->deleteCandidate();
                    $_SESSION['success_message'] = "Kandidaat is succesvol verwijderd.";
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
        // Redirect back to the view after action
        header("Location: " . BASE_URL . "/src/views/candidates.php"); 
        exit;
    }

    private function handleImageUpload() {
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                throw new Exception('Ongeldig bestandstype. Alleen JPG, PNG en GIF zijn toegestaan.');
            }

            if ($_FILES['image']['size'] > $max_size) {
                throw new Exception('Bestand is te groot. Maximum grootte is 5MB.');
            }

            $upload_dir = __DIR__ . '/../../uploads/candidates/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            // Use separate variables for first and last name if available, otherwise use 'name'
            $firstName = $_POST['firstName'] ?? ''; // Assuming firstName is passed
            $lastName = $_POST['lastName'] ?? '';   // Assuming lastName is passed
            $name = !empty($firstName) && !empty($lastName) ? $firstName . '_' . $lastName : ($_POST['name'] ?? 'candidate');
            $file_name = $name . '_' . uniqid() . '.' . $file_extension; // Make filename more descriptive
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_path = 'uploads/candidates/' . $file_name; // Relative path for DB
            } else {
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
        $district_id = intval($_POST['district_id'] ?? 0); // Assuming district_id is needed
        // $description = $_POST['description'] ?? ''; // Description seems missing in modal form

        if (empty(trim($name)) || empty($election_id) || empty($party_id) || empty($district_id)) {
            throw new Exception('Vul alle verplichte velden in (Naam, Partij, Verkiezing, District).');
        }

        $image_path = $this->handleImageUpload();

        $stmt = $this->pdo->prepare("
            INSERT INTO candidates (Name, PartyID, ElectionID, DistrictID, Photo, CreatedAt) -- Changed ImagePath to Photo
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        // Note: Added DistrictID, removed Description as it wasn't in the modal
        $stmt->execute([trim($name), $party_id, $election_id, $district_id, $image_path]);
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

    public function getCandidates($page = 1, $per_page = 10) {
        $offset = ($page - 1) * $per_page;
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, p.PartyName, e.ElectionName, d.DistrictName,
                       COUNT(v.VoteID) as vote_count
                FROM candidates c
                LEFT JOIN parties p ON c.PartyID = p.PartyID
                LEFT JOIN elections e ON c.ElectionID = e.ElectionID
                LEFT JOIN districten d ON c.DistrictID = d.DistrictID -- Assuming DistrictID link
                LEFT JOIN votes v ON c.CandidateID = v.CandidateID
                GROUP BY c.CandidateID
                ORDER BY c.CreatedAt DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bindParam(1, $per_page, PDO::PARAM_INT);
            $stmt->bindParam(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error fetching candidates: " . $e->getMessage());
            $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de kandidaten.";
            return [];
        }
    }
    
    public function getTotalCandidatesCount() {
         try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM candidates");
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
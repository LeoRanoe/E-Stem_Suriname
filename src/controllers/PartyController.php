<?php

require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/config.php';

class PartyController {

    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function handleRequest() {
        // session_start(); // Removed: Session should be started by the view or entry script
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePostRequest();
        } elseif (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
            $this->handleDeleteRequest();
        }
    }

    private function handlePostRequest() {
        try {
            $party_name = $_POST['party_name'] ?? '';
            $party_id = isset($_POST['party_id']) ? intval($_POST['party_id']) : 0;

            if (empty($party_name)) {
                throw new Exception('Vul alle verplichte velden in.');
            }

            // Handle logo upload
            $logo_path = $this->handleLogoUpload($party_id);

            if ($party_id > 0) {
                // Update existing party
                $this->updateParty($party_id, $party_name, $logo_path);
                $_SESSION['success_message'] = "Partij is succesvol bijgewerkt.";
            } else {
                // Create new party
                $this->createParty($party_name, $logo_path);
                $_SESSION['success_message'] = "Partij is succesvol toegevoegd.";
            }

            header("Location: " . BASE_URL . "/src/views/parties.php"); // Redirect to the view
            exit;
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            // Redirect back to the form (or the view) to display the error
            header("Location: " . BASE_URL . "/src/views/parties.php" . ($party_id > 0 ? "?edit=" . $party_id : "")); 
            exit;
        }
    }

    private function handleDeleteRequest() {
        try {
            $party_id = intval($_GET['delete']);

            // Check if party has candidates
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM candidates WHERE PartyID = ?");
            $stmt->execute([$party_id]);
            $has_candidates = $stmt->fetchColumn() > 0;

            if ($has_candidates) {
                throw new Exception('Deze partij kan niet worden verwijderd omdat er nog kandidaten aan gekoppeld zijn.');
            }

            // Get logo path before deleting
            $stmt = $this->pdo->prepare("SELECT Logo FROM parties WHERE PartyID = ?");
            $stmt->execute([$party_id]);
            $logo_path = $stmt->fetchColumn();

            // Delete party
            $stmt = $this->pdo->prepare("DELETE FROM parties WHERE PartyID = ?");
            $stmt->execute([$party_id]);

            // Delete logo file if exists
            if ($logo_path && file_exists(__DIR__ . '/../../' . $logo_path)) {
                unlink(__DIR__ . '/../../' . $logo_path);
            }

            $_SESSION['success_message'] = "Partij is succesvol verwijderd.";
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
        header("Location: " . BASE_URL . "/src/views/parties.php"); // Redirect to the view
        exit;
    }

    private function handleLogoUpload($party_id = 0) {
        $current_logo = null;
        if ($party_id > 0) {
             $stmt = $this->pdo->prepare("SELECT Logo FROM parties WHERE PartyID = ?");
             $stmt->execute([$party_id]);
             $current_logo = $stmt->fetchColumn();
        }
       
        $logo_path = $current_logo; // Keep current logo by default

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['logo']['type'], $allowed_types)) {
                throw new Exception('Ongeldig bestandstype. Alleen JPG, PNG en GIF zijn toegestaan.');
            }

            if ($_FILES['logo']['size'] > $max_size) {
                throw new Exception('Bestand is te groot. Maximum grootte is 5MB.');
            }

            $upload_dir = __DIR__ . '/../../uploads/parties/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                 // Delete old logo if a new one is uploaded and the old one exists
                if ($current_logo && file_exists(__DIR__ . '/../../' . $current_logo)) {
                    unlink(__DIR__ . '/../../' . $current_logo);
                }
                $logo_path = 'uploads/parties/' . $file_name; // Relative path for DB
            } else {
                 throw new Exception('Kon het logo niet uploaden.');
            }
        }
        return $logo_path;
    }

    private function updateParty($party_id, $party_name, $logo_path) {
         $sql = "UPDATE parties SET PartyName = ?";
         $params = [$party_name];
         
         if ($logo_path !== null) { // Only update logo if a new one was uploaded or explicitly set
             $sql .= ", Logo = ?";
             $params[] = $logo_path;
         }
         
         $sql .= " WHERE PartyID = ?";
         $params[] = $party_id;

         $stmt = $this->pdo->prepare($sql);
         $stmt->execute($params);
    }

    private function createParty($party_name, $logo_path) {
        $stmt = $this->pdo->prepare("
            INSERT INTO parties (PartyName, Logo, CreatedAt)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$party_name, $logo_path]);
    }

    public function getPartiesData() {
        try {
            $stmt = $this->pdo->query("
                SELECT p.*, 
                       COUNT(c.CandidateID) as candidate_count,
                       GROUP_CONCAT(DISTINCT e.ElectionName) as elections
                FROM parties p
                LEFT JOIN candidates c ON p.PartyID = c.PartyID
                LEFT JOIN elections e ON c.ElectionID = e.ElectionID
                GROUP BY p.PartyID
                ORDER BY p.PartyName ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de partijen.";
            return [];
        }
    }

    public function getTotalCandidates() {
         try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM candidates");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
             $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van het totaal aantal kandidaten.";
            return 0;
        }
    }
}

// Instantiate and handle request if this file is accessed directly (though it shouldn't be)
// Typically, you'd have a router that directs requests to the appropriate controller method.
// For simplicity here, we handle POST/GET directly.
$controller = new PartyController();
$controller->handleRequest();

?>
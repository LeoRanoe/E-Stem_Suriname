<?php

require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/config.php';

class ElectionController {

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
                    $this->createElection();
                    $_SESSION['success_message'] = "Verkiezing is succesvol aangemaakt.";
                    break;
                case 'update':
                    $this->updateElection();
                     $_SESSION['success_message'] = "Verkiezing is succesvol bijgewerkt.";
                    break;
                case 'delete':
                    $this->deleteElection();
                     $_SESSION['success_message'] = "Verkiezing is succesvol verwijderd.";
                    break;
            }
        } catch (PDOException $e) {
            error_log("Election action failed: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
            $_SESSION['error_details'] = "Please check the election dates and try again.";
        } catch (Exception $e) {
            error_log("Election action failed: " . $e->getMessage());
            $_SESSION['error_message'] = $e->getMessage();
        }
        // Redirect back to the view after action
        header("Location: " . BASE_URL . "/src/views/elections.php"); 
        exit;
    }

    private function createElection() {
        $name = $_POST['name'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty($name) || empty($start_date) || empty($end_date)) {
            throw new Exception('Please fill all required fields.');
        }

        if (strtotime($start_date) >= strtotime($end_date)) {
            throw new Exception('End date must be after start date.');
        }

        if (strtotime($start_date) < time()) {
            throw new Exception('Start date cannot be in the past.');
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO elections (ElectionName, Description, StartDate, EndDate, Status, CreatedAt)
            VALUES (?, ?, ?, ?, 'active', NOW())
        ");
        $stmt->execute([$name, $description, $start_date, $end_date]);
    }

    private function updateElection() {
        $election_id = intval($_POST['election_id']);
        $name = $_POST['name'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $description = $_POST['description'] ?? '';
        $status = $_POST['status'] ?? 'active';

        if (empty($name) || empty($start_date) || empty($end_date)) {
            throw new Exception('Please fill all required fields.');
        }
            try {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM elections WHERE Status = 'upcoming'");
                $stmt->execute();
                return $stmt->fetchColumn();
            } catch (PDOException $e) {
                error_log("Error getting upcoming elections count: " . $e->getMessage());
                return 0;
            }
        }
    
        public function getCompletedElectionsCount() {
            try {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM elections WHERE Status = 'completed'");
                $stmt->execute();
                return $stmt->fetchColumn();
            } catch (PDOException $e) {
                error_log("Error getting completed elections count: " . $e->getMessage());
                return 0;
            }
        }
    
        public function getTotalVotesCount() {
            try {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM votes");
                return $stmt->fetchColumn();
            } catch (PDOException $e) {
                error_log("Error getting total votes count: " . $e->getMessage());
                return 0;
            }
        }
    
    private function deleteElection() {
         $election_id = intval($_POST['election_id']);
         // Add checks here if needed (e.g., prevent deletion if votes exist)
         $stmt = $this->pdo->prepare("DELETE FROM elections WHERE ElectionID = ?");
         $stmt->execute([$election_id]);
    }

    public function getElectionData() {
        $data = [
            'active_elections' => [],
            'upcoming_elections' => [],
            'completed_elections' => [],
            'total_votes' => 0,
        ];
        try {
            // Get active elections
            $stmt = $this->pdo->query("
                SELECT e.*,
                       COUNT(DISTINCT v.UserID) as vote_count,
                       COUNT(DISTINCT c.CandidateID) as candidate_count
                FROM elections e
                LEFT JOIN votes v ON e.ElectionID = v.ElectionID
                LEFT JOIN candidates c ON e.ElectionID = c.ElectionID
                WHERE e.StartDate <= NOW() AND e.EndDate >= NOW()
                GROUP BY e.ElectionID, e.ElectionName, e.Description, e.StartDate, e.EndDate, e.Status, e.CreatedAt
                ORDER BY e.StartDate DESC
            ");
            $data['active_elections'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get upcoming elections
            $stmt = $this->pdo->query("
                SELECT e.*,
                       COUNT(DISTINCT c.CandidateID) as candidate_count
                FROM elections e
                LEFT JOIN candidates c ON e.ElectionID = c.ElectionID
                WHERE e.StartDate > NOW()
                GROUP BY e.ElectionID, e.ElectionName, e.Description, e.StartDate, e.EndDate, e.Status, e.CreatedAt
                ORDER BY e.StartDate ASC
            ");
            $data['upcoming_elections'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get completed elections
            $stmt = $this->pdo->query("
                SELECT e.*,
                       COUNT(DISTINCT v.UserID) as vote_count,
                       COUNT(DISTINCT c.CandidateID) as candidate_count
                FROM elections e
                LEFT JOIN votes v ON e.ElectionID = v.ElectionID
                LEFT JOIN candidates c ON e.ElectionID = c.ElectionID
                WHERE e.EndDate < NOW()
                GROUP BY e.ElectionID, e.ElectionName, e.Description, e.StartDate, e.EndDate, e.Status, e.CreatedAt
                ORDER BY e.EndDate DESC
            ");
            $data['completed_elections'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total votes
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM votes");
            $data['total_votes'] = $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error fetching elections: " . $e->getMessage());
            if (isset($stmt)) {
                error_log("Failed query: " . $stmt->queryString);
            }
            $_SESSION['error_message'] = "Failed to load election data. Please try again later.";
            $_SESSION['error_details'] = $e->getMessage();
        }
        return $data;
    }

    public function getActiveElectionsCount() {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM elections WHERE Status = 'active'");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting active elections count: " . $e->getMessage());
            return 0;
        }
    }

    public function getUpcomingElectionsCount() {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM elections WHERE Status = 'upcoming'");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error getting upcoming elections count: " . $e->getMessage());
            return 0;
        }
    }

}

// Instantiate and handle request if accessed directly
$controller = new ElectionController();
$controller->handleRequest();

?>
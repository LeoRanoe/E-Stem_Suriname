<?php

require_once __DIR__ . '/../../include/db_connect.php'; // Corrected path

class VoterController {
    public function handleActions() {
        // session_start(); // Removed: Session should be started by the view or entry script
        global $pdo;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            try {
                switch ($_POST['action']) {
                    case 'create':
                        $voornaam = $_POST['voornaam'] ?? '';
                        $achternaam = $_POST['achternaam'] ?? '';
                        $email = $_POST['email'] ?? '';
                        $password = $_POST['password'] ?? '';
                        $confirm_password = $_POST['confirm_password'] ?? '';
                        $district_id = $_POST['district_id'] ?? '';
                        $id_number = $_POST['id_number'] ?? '';

                        if (empty($voornaam) || empty($achternaam) || empty($email) || empty($password) || empty($confirm_password) || empty($district_id) || empty($id_number)) {
                            throw new Exception('Vul alle verplichte velden in.');
                        }

                        if ($password !== $confirm_password) {
                            throw new Exception('Wachtwoorden komen niet overeen.');
                        }

                        if (strlen($password) < 8) {
                            throw new Exception('Wachtwoord moet minimaal 8 tekens lang zijn.');
                        }

                        // Check if email already exists
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Email = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetchColumn() > 0) {
                            throw new Exception('Dit e-mailadres is al in gebruik.');
                        }

                        // Check if ID number already exists
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE IDNumber = ?");
                        $stmt->execute([$id_number]);
                        if ($stmt->fetchColumn() > 0) {
                            throw new Exception('Dit ID nummer is al in gebruik.');
                        }

                        // Hash password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        $stmt = $pdo->prepare("
                            INSERT INTO users (Voornaam, Achternaam, Email, Password, DistrictID, Role, IDNumber, Status, CreatedAt)
                            VALUES (?, ?, ?, ?, ?, 'voter', ?, 'active', NOW())
                        ");
                        $stmt->execute([$voornaam, $achternaam, $email, $hashed_password, $district_id, $id_number]);
                        $_SESSION['success_message'] = "Stemmer is succesvol toegevoegd.";
                        break;

                    case 'update':
                        $user_id = intval($_POST['user_id']);
                        $voornaam = $_POST['voornaam'] ?? '';
                        $achternaam = $_POST['achternaam'] ?? '';
                        $email = $_POST['email'] ?? '';
                        $password = $_POST['password'] ?? '';
                        $confirm_password = $_POST['confirm_password'] ?? '';
                        $district_id = $_POST['district_id'] ?? '';
                        $id_number = $_POST['id_number'] ?? '';
                        $status = $_POST['status'] ?? 'active';

                        if (empty($voornaam) || empty($achternaam) || empty($email) || empty($district_id) || empty($id_number)) {
                            throw new Exception('Vul alle verplichte velden in.');
                        }

                        // Check if email already exists for other users
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Email = ? AND UserID != ?");
                        $stmt->execute([$email, $user_id]);
                        if ($stmt->fetchColumn() > 0) {
                            throw new Exception('Dit e-mailadres is al in gebruik.');
                        }

                        // Check if ID number already exists for other users
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE IDNumber = ? AND UserID != ?");
                        $stmt->execute([$id_number, $user_id]);
                        if ($stmt->fetchColumn() > 0) {
                            throw new Exception('Dit ID nummer is al in gebruik.');
                        }

                        // Update user data
                        if (!empty($password)) {
                            if ($password !== $confirm_password) {
                                throw new Exception('Wachtwoorden komen niet overeen.');
                            }

                            if (strlen($password) < 8) {
                                throw new Exception('Wachtwoord moet minimaal 8 tekens lang zijn.');
                            }

                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET Voornaam = ?, Achternaam = ?, Email = ?, Password = ?, DistrictID = ?, IDNumber = ?, Status = ?
                                WHERE UserID = ?
                            ");
                            $stmt->execute([$voornaam, $achternaam, $email, $hashed_password, $district_id, $id_number, $status, $user_id]);
                        } else {
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET Voornaam = ?, Achternaam = ?, Email = ?, DistrictID = ?, IDNumber = ?, Status = ?
                                WHERE UserID = ?
                            ");
                            $stmt->execute([$voornaam, $achternaam, $email, $district_id, $id_number, $status, $user_id]);
                        }
                        
                        $_SESSION['success_message'] = "Stemmer is succesvol bijgewerkt.";
                        break;

                    case 'delete':
                        $user_id = intval($_POST['user_id']);
                        $stmt = $pdo->prepare("DELETE FROM users WHERE UserID = ?");
                        $stmt->execute([$user_id]);
                        $_SESSION['success_message'] = "Stemmer is succesvol verwijderd.";
                        break;
                }
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
    }

    public function getVoters() {
        global $pdo;
        $voters = [];
        try {
            // Get voters with their vote counts
            $stmt = $pdo->query("
                SELECT u.UserID,
                       u.Voornaam,
                       u.Achternaam,
                       u.Email,
                       u.DistrictID,
                       u.Status,
                       u.CreatedAt,
                       u.IDNumber,
                       d.DistrictName,
                       COUNT(DISTINCT v.VoteID) as vote_count,
                       MAX(v.TimeStamp) as last_vote
                FROM users u
                LEFT JOIN districten d ON u.DistrictID = d.DistrictID
                LEFT JOIN votes v ON u.UserID = v.UserID
                WHERE u.Role = 'voter'
                GROUP BY u.UserID
                ORDER BY u.Voornaam ASC, u.Achternaam ASC
            ");
            $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de stemmers.";
        }
        return $voters;
    }

    public function getTotalVotes() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM votes");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van het totaal aantal stemmen.";
            return 0;
        }
    }

    public function getTotalDistricts() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM districten");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van het totaal aantal districten.";
            return 0;
        }
    }

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
            error_log("Database error: " . $e->getMessage());
            $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van het totaal aantal actieve stemmers.";
            return 0;
        }
    }
}
?>
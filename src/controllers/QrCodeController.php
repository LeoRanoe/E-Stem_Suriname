<?php
namespace App\Controllers;

use PDO;
use PDOException;

class QrCodeController
{
    private $db;

    public function __construct()
    {
        global $pdo;
        if (!isset($pdo)) {
            throw new \RuntimeException('Database connection not established');
        }
        $this->db = $pdo;
    }

    public function generateQrCode(int $userId, int $electionId): string
    {
        if ($userId <= 0 || $electionId <= 0) {
            throw new \InvalidArgumentException('Invalid user or election ID');
        }

        try {
            $code = $this->generateUniqueCode();
            $stmt = $this->db->prepare("INSERT INTO qrcodes (UserID, ElectionID, QRCode, Status) VALUES (?, ?, ?, 'active')");
            $stmt->execute([$userId, $electionId, $code]);
            return $code;
        } catch (PDOException $e) {
            error_log("QR code generation error: " . $e->getMessage());
            throw $e;
        }
    }

    public function generateBulkQrCodes(array $userIds, int $electionId): array
    {
        if (empty($userIds) || $electionId <= 0) {
            throw new \InvalidArgumentException('Invalid input parameters');
        }

        try {
            $codes = [];
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("INSERT INTO qrcodes (UserID, ElectionID, QRCode, Status) VALUES (?, ?, ?, 'active')");

            foreach ($userIds as $userId) {
                $code = $this->generateUniqueCode();
                $stmt->execute([$userId, $electionId, $code]);
                $codes[] = $code;
            }

            $this->db->commit();
            return $codes;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Bulk QR code generation error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getQrCodes(?string $status = null): array
    {
        try {
            $query = "SELECT * FROM qrcodes";
            $params = [];

            if ($status) {
                $query .= " WHERE Status = ?";
                $params[] = $status;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("QR code retrieval error: " . $e->getMessage());
            throw $e;
        }
    }

    public function revokeQrCode(int $qrCodeId): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE qrcodes SET Status = 'used' WHERE QRCodeID = ?");
            return $stmt->execute([$qrCodeId]);
        } catch (PDOException $e) {
            error_log("QR code revocation error: " . $e->getMessage());
            throw $e;
        }
    }

    public function deleteQrCode(int $qrCodeId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM qrcodes WHERE QRCodeID = ?");
            return $stmt->execute([$qrCodeId]);
        } catch (PDOException $e) {
            error_log("QR code deletion error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getQrCodeStats(): array
    {
        try {
            $stats = [];
            $stmt = $this->db->query("SELECT Status, COUNT(*) as count FROM qrcodes GROUP BY Status");
            $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $this->db->query("SELECT COUNT(DISTINCT UserID) as total_users FROM qrcodes");
            $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC);

            $stats['status_counts'] = $statusCounts;
            $stats['total_users'] = $totalUsers['total_users'] ?? 0;
            $stats['total_codes'] = array_sum(array_column($statusCounts, 'count'));

            return $stats;
        } catch (PDOException $e) {
            error_log("QR code stats error: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateUniqueCode(): string
    {
        return bin2hex(random_bytes(16));
    }
    /**
     * Import users from CSV and generate QR codes
     */
    public function importUsers(array $data): array
    {
        header('Content-Type: application/json');
        
        try {
            // Validate input
            if (empty($data['file']) || empty($data['election_id'])) {
                throw new \InvalidArgumentException("Missing required fields");
            }

            // Validate file
            $file = $data['file'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new \RuntimeException("File upload error: " . $file['error']);
            }

            // Parse CSV
            $csvData = file_get_contents($file['tmp_name']);
            $lines = explode("\n", trim($csvData));
            $header = str_getcsv(array_shift($lines));
            
            // Validate CSV columns
            $requiredColumns = ['Voornaam', 'Achternaam', 'Email', 'IDNumber', 'DistrictID'];
            foreach ($requiredColumns as $col) {
                if (!in_array($col, $header)) {
                    throw new \RuntimeException("Missing required column: $col");
                }
            }

            $this->db->beginTransaction();
            
            // Prepare statements
            $userStmt = $this->db->prepare("
                INSERT INTO users (Voornaam, Achternaam, Email, IDNumber, DistrictID, Password, Role, Status)
                VALUES (:voornaam, :achternaam, :email, :id_number, :district_id, :password, 'voter', 'active')
                ON DUPLICATE KEY UPDATE IDNumber = VALUES(IDNumber)
            ");
            
            $qrStmt = $this->db->prepare("
                INSERT INTO qrcodes (UserID, ElectionID, QRCode, Status)
                VALUES (:user_id, :election_id, :code, 'active')
            ");

            $count = 0;
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                $row = str_getcsv($line);
                if (count($row) !== count($header)) continue;
                
                $userData = array_combine($header, $row);
                
                // Generate random password
                $password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                
                // Insert/update user
                $userStmt->execute([
                    ':voornaam' => $userData['Voornaam'],
                    ':achternaam' => $userData['Achternaam'],
                    ':email' => $userData['Email'],
                    ':id_number' => $userData['IDNumber'],
                    ':district_id' => $userData['DistrictID'],
                    ':password' => $password
                ]);
                
                $userId = $this->db->lastInsertId();
                if (!$userId) {
                    // Get existing user ID if duplicate
                    $stmt = $this->db->prepare("SELECT UserID FROM users WHERE IDNumber = ?");
                    $stmt->execute([$userData['IDNumber']]);
                    $userId = $stmt->fetchColumn();
                }
                
                // Generate QR code
                $code = $this->generateUniqueCode();
                $qrStmt->execute([
                    ':user_id' => $userId,
                    ':election_id' => $data['election_id'],
                    ':code' => $code
                ]);
                
                $count++;
            }
            
            $this->db->commit();
            return [
                'success' => true,
                'count' => $count,
                'message' => "Successfully imported $count users"
            ];
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }
}
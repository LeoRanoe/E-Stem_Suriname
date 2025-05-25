<?php
namespace App\Controllers;

use PDO;
use PDOException;

class QrCodeController
{
    private PDO $db;
    private const ALLOWED_STATUSES = ['active', 'used', 'revoked'];

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
        $this->validateIds($userId, $electionId);

        try {
            $code = $this->generateUniqueCode();
            $stmt = $this->db->prepare("
                INSERT INTO qrcodes (UserID, ElectionID, QRCode, Status, CreatedAt) 
                VALUES (?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$userId, $electionId, $code]);
            return $code;
        } catch (PDOException $e) {
            error_log("QR code generation error: " . $e->getMessage());
            throw new \RuntimeException('Failed to generate QR code', 0, $e);
        }
    }

    public function generateBulkQrCodes(array $userIds, int $electionId): array
    {
        if (empty($userIds)) {
            throw new \InvalidArgumentException('User IDs array cannot be empty');
        }
        $this->validateIds(min($userIds), $electionId);

        try {
            $codes = [];
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                INSERT INTO qrcodes (UserID, ElectionID, QRCode, Status, CreatedAt) 
                VALUES (?, ?, ?, 'active', NOW())
            ");

            foreach ($userIds as $userId) {
                $code = $this->generateUniqueCode();
                $stmt->execute([(int)$userId, $electionId, $code]);
                $codes[] = $code;
            }

            $this->db->commit();
            return $codes;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Bulk QR code generation error: " . $e->getMessage());
            throw new \RuntimeException('Failed to generate bulk QR codes', 0, $e);
        }
    }

    public function getQrCodes(?string $status = null): array
    {
        try {
            $query = "SELECT * FROM qrcodes";
            $params = [];

            if ($status !== null) {
                if (!in_array($status, self::ALLOWED_STATUSES)) {
                    throw new \InvalidArgumentException('Invalid status value');
                }
                $query .= " WHERE Status = ?";
                $params[] = $status;
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("QR code retrieval error: " . $e->getMessage());
            throw new \RuntimeException('Failed to retrieve QR codes', 0, $e);
        }
    }

    public function revokeQrCode(int $qrCodeId): bool
    {
        if ($qrCodeId <= 0) {
            throw new \InvalidArgumentException('Invalid QR code ID');
        }

        try {
            $stmt = $this->db->prepare("
                UPDATE qrcodes 
                SET Status = 'revoked', 
                    UpdatedAt = NOW() 
                WHERE QRCodeID = ? 
                AND Status = 'active'
            ");
            return $stmt->execute([$qrCodeId]);
        } catch (PDOException $e) {
            error_log("QR code revocation error: " . $e->getMessage());
            throw new \RuntimeException('Failed to revoke QR code', 0, $e);
        }
    }

    public function deleteQrCode(int $qrCodeId): bool
    {
        if ($qrCodeId <= 0) {
            throw new \InvalidArgumentException('Invalid QR code ID');
        }

        try {
            $stmt = $this->db->prepare("DELETE FROM qrcodes WHERE QRCodeID = ?");
            return $stmt->execute([$qrCodeId]);
        } catch (PDOException $e) {
            error_log("QR code deletion error: " . $e->getMessage());
            throw new \RuntimeException('Failed to delete QR code', 0, $e);
        }
    }

    public function getQrCodeStats(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT
                    Status,
                    COUNT(*) as count
                FROM qrcodes
                GROUP BY Status
            ");
            error_log("QR code status counts query executed");
            $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $this->db->query("
                SELECT COUNT(DISTINCT UserID) as total_users
                FROM qrcodes
            ");
            error_log("Distinct user count query executed");
            $totalUsers = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'status_counts' => $statusCounts,
                'total_users' => $totalUsers['total_users'] ?? 0,
                'total_codes' => array_sum(array_column($statusCounts, 'count'))
            ];
        } catch (PDOException $e) {
            error_log("QR code stats error: " . $e->getMessage());
            throw new \RuntimeException('Failed to retrieve QR code statistics', 0, $e);
        }
    }

    /**
     * Handle CSV import for all supported tables
     */
    public function handleImport(): array
    {
        header('Content-Type: application/json');

        try {
            // Validate input
            if (empty($_FILES['file'])) {
                throw new \InvalidArgumentException("No file uploaded");
            }

            $file = $_FILES['file'];
            $targetTable = $_POST['target_table'] ?? null;

            error_log("Target table: " . ($targetTable ?? 'null'));
            if (empty($targetTable)) {
                error_log("Target table not specified");
                throw new \InvalidArgumentException("Target table not specified");
            }

            // Validate file upload
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new \RuntimeException("File upload error: " . $this->getUploadErrorMessage($file['error']));
            }

            // Validate file type
            $allowedMimeTypes = ['text/csv', 'application/vnd.ms-excel', 'text/plain', 'application/csv', 'text/x-csv'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedMimeTypes) && !preg_match('/\.csv$/i', $file['name'])) {
                throw new \RuntimeException("Invalid file type. Only CSV files are allowed.");
            }

            // Initialize ImportController and process the file
            $importController = new ImportController($this->db);
            error_log("Importing to table: $targetTable");
            $result = $importController->importFromTable($file['tmp_name'], $targetTable);
            error_log("Import result: " . json_encode($result));

            if (!empty($_POST['election_id']) && $targetTable === 'voters') {
                $electionId = (int)$_POST['election_id'];
                error_log("Generating QR codes for election ID: $electionId");
                $qrCodes = $this->generateBulkQrCodes(range(1, $result['imported']), $electionId);
                error_log(sprintf("Generated %d QR codes", count($qrCodes)));
                $result['qr_codes_generated'] = count($qrCodes);
                $result['message'] .= sprintf(", %d QR codes generated", count($qrCodes));
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Import users from CSV and generate QR codes (maintained for backward compatibility)
     */
    public function importUsers(): array
    {
        header('Content-Type: application/json');
        
        try {
            // Validate input
            if (empty($_FILES['file'])) {
                throw new \InvalidArgumentException("Missing file");
            }

            $file = $_FILES['file'];

            // Validate file upload
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new \RuntimeException("File upload error: " . $this->getUploadErrorMessage($file['error']));
            }

            // Validate file type
            $allowedMimeTypes = ['text/csv', 'application/vnd.ms-excel', 'application/octet-stream'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedMimeTypes) && !preg_match('/\.csv$/i', $file['name'])) {
                throw new \RuntimeException("Invalid file type. Only CSV files are allowed. Detected type: " . $fileType);
            }

            // Parse CSV
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle === false) {
                throw new \RuntimeException("Could not open file");
            }

            // Read and validate headers
            $headers = fgetcsv($handle);
            if ($headers === false) {
                throw new \RuntimeException("Empty CSV file");
            }

            $headers = array_map('trim', $headers);
            $requiredColumns = ['Voornaam', 'Achternaam', 'Email', 'IDNumber', 'DistrictID'];
            $missingColumns = array_diff($requiredColumns, $headers);
            
            if (!empty($missingColumns)) {
                throw new \RuntimeException("Missing required columns: " . implode(', ', $missingColumns));
            }

            // Map CSV columns to array keys
            $columnMap = array_flip($headers);

            $this->db->beginTransaction();
            
            // Prepare statements
            $userStmt = $this->db->prepare("
                INSERT INTO users (Voornaam, Achternaam, Email, IDNumber, DistrictID, Role, Status)
                VALUES (:voornaam, :achternaam, :email, :id_number, :district_id, 'voter', 'active')
                ON DUPLICATE KEY UPDATE 
                    Voornaam = VALUES(Voornaam),
                    Achternaam = VALUES(Achternaam),
                    Email = VALUES(Email),
                    DistrictID = VALUES(DistrictID)
            ");

            $electionId = $_POST['election_id'] ?? null;
            $count = ['success' => 0, 'skipped' => 0, 'error' => 0, 'qr_generated' => 0];
            $rowNumber = 1;
            $newUserIds = [];

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if (empty(array_filter($row))) {
                    continue; // Skip empty rows
                }

                try {
                    $data = array_map('trim', array_combine($headers, $row));
                    
                    // Basic validation
                    foreach ($requiredColumns as $col) {
                        if (empty($data[$col])) {
                            throw new \RuntimeException("Missing value for $col");
                        }
                    }

                    // Validate DistrictID
                    if (!is_numeric($data['DistrictID']) || $data['DistrictID'] <= 0) {
                        throw new \RuntimeException("Invalid DistrictID");
                    }

                    // Validate email
                    if (!filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
                        throw new \RuntimeException("Invalid email format");
                    }

                    // Insert user
                    $userStmt->execute([
                        ':voornaam' => $data['Voornaam'],
                        ':achternaam' => $data['Achternaam'],
                        ':email' => $data['Email'],
                        ':id_number' => $data['IDNumber'],
                        ':district_id' => (int)$data['DistrictID']
                    ]);

                    if ($userStmt->rowCount() > 0) {
                        $count['success']++;
                        $userId = $this->db->lastInsertId();
                        if ($userId && $electionId) {
                            $newUserIds[] = $userId;
                        }
                    } else {
                        $count['skipped']++;
                    }

                } catch (\Exception $e) {
                    $count['error']++;
                    error_log("CSV import error at row $rowNumber: " . $e->getMessage());
                }
            }

            // Generate QR codes for new users if election is selected
            if (!empty($newUserIds) && $electionId) {
                try {
                    $qrCodes = $this->generateBulkQrCodes($newUserIds, $electionId);
                    $count['qr_generated'] = count($qrCodes);
                } catch (\Exception $e) {
                    error_log("QR code generation error: " . $e->getMessage());
                }
            }

            fclose($handle);
            $this->db->commit();

            $message = sprintf(
                "Import completed. Success: %d, Skipped: %d, Errors: %d%s",
                $count['success'],
                $count['skipped'],
                $count['error'],
                isset($count['qr_generated']) ? sprintf(", QR Codes Generated: %d", $count['qr_generated']) : ""
            );

            return [
                'success' => true,
                'message' => $message,
                'count' => $count['success']
            ];

        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            if (isset($handle) && $handle !== false) {
                fclose($handle);
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get file upload error message
     */
    private function getUploadErrorMessage(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                return "The uploaded file exceeds the upload_max_filesize directive in php.ini";
            case UPLOAD_ERR_FORM_SIZE:
                return "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
            case UPLOAD_ERR_PARTIAL:
                return "The uploaded file was only partially uploaded";
            case UPLOAD_ERR_NO_FILE:
                return "No file was uploaded";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing a temporary folder";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk";
            case UPLOAD_ERR_EXTENSION:
                return "A PHP extension stopped the file upload";
            default:
                return "Unknown upload error";
        }
    }

    private function generateUniqueCode(): string
    {
        return bin2hex(random_bytes(32)); // Increased from 16 to 32 for better uniqueness
    }

    private function validateIds(int $userId, int $electionId): void
    {
        if ($userId <= 0 || $electionId <= 0) {
            throw new \InvalidArgumentException('Invalid user or election ID');
        }
    }
}
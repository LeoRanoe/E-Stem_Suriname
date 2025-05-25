<?php

namespace App\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PDO;
use PDOException;

class ImportController {
    protected $db;
    protected $supportedTables = [
        'voters', 'admins', 'candidates', 'districten', 'elections', 'parties', 'qrcodes'
    ];

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    /**
     * Get table columns from database
     */
    protected function getTableColumns(string $tableName): array {
        if (!in_array($tableName, $this->supportedTables)) {
            throw new \InvalidArgumentException(
                "Unsupported table: $tableName. Supported tables: " . 
                implode(', ', $this->supportedTables)
            );
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM $tableName");
            if ($stmt === false) {
                throw new \RuntimeException("Failed to query table columns");
            }
            
            $columns = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($row['Extra'] !== 'auto_increment') {
                    $columns[] = $row['Field'];
                }
            }
            return $columns;
        } catch (\Exception $e) {
            error_log("[ImportController] Error getting table columns: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate CSV file against table structure
     */
    protected function validateCsvForTable(string $filePath, string $tableName): array {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: $filePath");
        }

        error_log("[ImportController] Validating CSV for table: $tableName");
        
        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file contents");
        }

        $rows = array_map(fn($line) => str_getcsv($line, ',', '"', '\\'), explode("\n", $content));
        
        if (count($rows) < 2) {
            throw new \InvalidArgumentException("CSV file must contain at least one data row");
        }

        $headers = array_map('trim', array_shift($rows));
        $tableColumns = $this->getTableColumns($tableName);
        
        foreach ($headers as $header) {
            if (!in_array($header, $tableColumns)) {
                throw new \InvalidArgumentException("Invalid CSV header '$header' for table $tableName");
            }
        }

        // Additional validation for voters table
        if ($tableName === 'voters') {
            foreach ($rows as $i => $row) {
                if (empty(array_filter($row))) continue;
                
                // Validate id_number format
                if (!empty($row[2]) && !preg_match('/^SUR\d{6}$/', $row[2])) {
                    throw new \InvalidArgumentException("Row " . ($i+2) . ": Invalid ID number format - must be SUR followed by 6 digits");
                }
                
                // Validate district_id is numeric
                if (!empty($row[3]) && !is_numeric($row[3])) {
                    throw new \InvalidArgumentException("Row " . ($i+2) . ": District ID must be a number");
                }
                
                // Validate resort_id is numeric
                if (!empty($row[5]) && !is_numeric($row[5])) {
                    throw new \InvalidArgumentException("Row " . ($i+2) . ": Resort ID must be a number");
                }
                
                // Validate election_id is numeric or empty
                if (!empty($row[4]) && !is_numeric($row[4])) {
                    throw new \InvalidArgumentException("Row " . ($i+2) . ": Election ID must be a number or empty");
                }
            }
        }

        return ['headers' => $headers, 'rows' => $rows];
    }

    /**
     * Import data from CSV to specified table
     */
    public function importFromTable(string $filePath, string $tableName): array {
        error_log("[ImportController] Starting import to table: $tableName");
        
        $this->db->beginTransaction();
        $imported = 0;
        $errors = [];
        $startTime = microtime(true);

        try {
            $parsed = $this->validateCsvForTable($filePath, $tableName);
            error_log(sprintf(
                "[ImportController] CSV validated - %d headers, %d rows",
                count($parsed['headers']),
                count($parsed['rows'])
            ));
            
            $columns = implode(', ', $parsed['headers']);
            $placeholders = implode(', ', array_fill(0, count($parsed['headers']), '?'));
            
            $sql = "INSERT INTO $tableName ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);

            foreach ($parsed['rows'] as $index => $row) {
                if (empty(array_filter($row))) continue;
                
                try {
                    // Ensure row has same number of elements as headers
                    $data = array_slice(array_pad($row, count($parsed['headers']), null), 0, count($parsed['headers']));
                    $stmt->execute($data);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            $this->db->commit();
            $duration = round(microtime(true) - $startTime, 2);
            
            error_log(sprintf(
                "[ImportController] Import completed - %d rows in %s seconds",
                $imported,
                $duration
            ));
            
            return [
                'success' => true,
                'imported' => $imported,
                'errors' => $errors,
                'message' => sprintf(
                    "Successfully imported %d %s to %s table in %s seconds",
                    $imported,
                    $imported === 1 ? 'row' : 'rows',
                    $tableName,
                    $duration
                ),
                'duration' => $duration,
                'table' => $tableName
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'imported' => $imported,
                'errors' => array_merge($errors, [$e->getMessage()]),
                'message' => "Import failed: " . $e->getMessage()
            ];
        }
    }

    /**
     * Import voters from CSV (updated to use importFromTable)
     */
    public function importVoters(array $fileData): array {
        $filePath = $fileData['tmp_name'] ?? $fileData['file_path'] ?? null;
        if (!$filePath) {
            throw new \InvalidArgumentException("Missing file path");
        }
        
        return $this->importFromTable($filePath, 'voters');
    }

    /**
     * Import users from file (maintained for backward compatibility)
     */
    public function importUsers(array $data): array {
        try {
            if (empty($data['file'])) {
                throw new \InvalidArgumentException("Missing file data");
            }

            $file = $data['file'];
            $filePath = $file['tmp_name'];
            $electionId = $data['election_id'] ?? null;

            // For users, we need special handling to include password generation
            $this->db->beginTransaction();
            $result = $this->importFromTable($filePath, 'voters');
            
            if ($result['success'] && $electionId !== null) {
                $this->generateQrCodesForImported($electionId, $result['imported']);
            }

            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function generateQrCodesForImported(int $electionId, int $count): void {
        error_log("[ImportController] Generating $count QR codes for election $electionId");
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO qrcodes (UserID, ElectionID, QRCode, Status)
                SELECT id, :election_id, :code, 'active'
                FROM voters
                ORDER BY id DESC
                LIMIT :count
            ");
            
            if ($stmt === false) {
                throw new \RuntimeException("Failed to prepare QR code insert statement");
            }
            
            $stmt->bindValue(':election_id', $electionId, PDO::PARAM_INT);
            $stmt->bindValue(':count', $count, PDO::PARAM_INT);
            
            $generated = 0;
            for ($i = 0; $i < $count; $i++) {
                $code = $this->generateUniqueCode();
                $stmt->bindValue(':code', $code);
                if (!$stmt->execute()) {
                    error_log("[ImportController] Failed to generate QR code for index $i");
                    continue;
                }
                $generated++;
            }
            
            error_log("[ImportController] Generated $generated/$count QR codes");
            
            if ($generated < $count) {
                throw new \RuntimeException(
                    "Only generated $generated out of $count QR codes"
                );
            }
        } catch (\Exception $e) {
            error_log("[ImportController] QR code generation error: " . $e->getMessage());
            throw $e;
        }
    }

    protected function generateUniqueCode(): string {
        return bin2hex(random_bytes(16));
    }
}
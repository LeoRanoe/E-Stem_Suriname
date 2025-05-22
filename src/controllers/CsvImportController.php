<?php
/**
 * CSV Import Controller
 * 
 * Handles CSV import operations
 * 
 * @package QRCodeManagement
 */

namespace App\Controllers;

use PDO;
use PDOException;

class CsvImportController
{
    private $db;

    public function __construct()
    {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Import CSV file
     */
    public function import(array $data)
    {
        // Validate input
        if (empty($data['file']) || empty($data['csrf_token'])) {
            throw new \InvalidArgumentException("Missing required fields");
        }


        // Validate file
        $file = $data['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("File upload error");
        }

        // Parse CSV
        $csv = array_map('str_getcsv', file($file['tmp_name']));
        $header = array_shift($csv);
        $rows = [];
        foreach ($csv as $row) {
            $rows[] = array_combine($header, $row);
        }

        // Import to database
        try {
            $stmt = $this->db->prepare("
                INSERT INTO qr_codes (code, expires_at, status, created_at) 
                VALUES (:code, :expires_at, 'active', NOW())
            ");

            $this->db->beginTransaction();
            foreach ($rows as $row) {
                $stmt->execute([
                    ':code' => $row['code'],
                    ':expires_at' => $row['expires_at']
                ]);
            }
            $this->db->commit();

            return count($rows);
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("CSV Import Error: " . $e->getMessage());
            throw $e;
        }
    }
}
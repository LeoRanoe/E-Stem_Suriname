<?php
/**
 * Voucher Controller
 * 
 * Handles voucher generation and management
 * 
 * @package QRCodeManagement
 */

namespace App\Controllers;

use PDO;
use PDOException;

class VoucherController
{
    private $db;

    public function __construct()
    {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Generate voucher
     */
    public function generate(array $data)
    {
        // Validate input
        if (empty($data['voucher_code']) || empty($data['expiry_date'])) {
            throw new \InvalidArgumentException("Missing required field");
        }


        try {
            $stmt = $this->db->prepare("
                INSERT INTO vouchers (voucher_code, expiry_date, status, created_at) 
                VALUES (:voucher_code, :expiry_date, 'active', NOW())
            ");
            $stmt->execute([
                ':voucher_code' => $data['voucher_code'],
                ':expiry_date' => $data['expiry_date']
            ]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Voucher generation error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate voucher
     */
    public function validate(string $voucherCode)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM vouchers 
                WHERE voucher_code = :voucher_code 
                AND status = 'active'
                AND expiry_date > NOW()
            ");
            $stmt->execute([':voucher_code' => $voucherCode]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Voucher validation error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Deactivate voucher
     */
    public function deactivate(string $voucherCode)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE vouchers 
                SET status = 'used' 
                WHERE voucher_code = :voucher_code
            ");
            $stmt->execute([':voucher_code' => $voucherCode]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Voucher deactivation error: " . $e->getMessage());
            throw $e;
        }
    }
}
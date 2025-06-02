<?php

require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../models/Voucher.php';
require_once __DIR__ . '/../models/Voter.php';

class QrCodeController {
    private $voucherModel;
    private $voterModel;
    
    public function __construct() {
        global $pdo;
        $this->voucherModel = new Voucher($pdo);
        $this->voterModel = new Voter($pdo);
        
        // Ensure vouchers table exists
        $this->voucherModel->ensureVouchersTableExists();
    }
    
    /**
     * Handle all QR code-related actions
     */
    public function handleActions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
            return;
        }
        
        try {
            switch ($_POST['action']) {
                case 'generate_single':
                    $this->generateSingleQR();
                    break;
                    
                case 'generate_bulk':
                    $this->generateBulkQRs();
                    break;
                    
                case 'delete':
                    $this->deleteVoucher();
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
    }
    
    /**
     * Generate a single QR code for a voter
     */
    private function generateSingleQR() {
        $voter_id = intval($_POST['voter_id'] ?? 0);
        
        if ($voter_id <= 0) {
            throw new Exception('Invalid voter ID.');
        }
        
        // Generate voucher
        $voucher = $this->voucherModel->generateSingleVoucher($voter_id);
        
        if ($voucher) {
            $_SESSION['success_message'] = "QR code successfully generated.";
            $_SESSION['generated_voucher'] = $voucher;
            $_SESSION['redirect'] = "admin/qrcodes.php";
        } else {
            throw new Exception('Failed to generate QR code. Please try again.');
        }
    }
    
    /**
     * Generate QR codes in bulk for multiple voters
     */
    private function generateBulkQRs() {
        $voter_ids = $_POST['voter_ids'] ?? [];
        
        if (empty($voter_ids)) {
            throw new Exception('No voters selected.');
        }
        
        // Generate vouchers
        $result = $this->voucherModel->generateBulkVouchers($voter_ids);
        
        if ($result['success'] > 0) {
            $_SESSION['success_message'] = "Successfully generated {$result['success']} QR codes.";
            
            if (!empty($result['errors'])) {
                $_SESSION['warning_message'] = "There were " . count($result['errors']) . " errors during generation. Check the logs for details.";
                // Log errors
                foreach ($result['errors'] as $error) {
                    error_log("QR Generation Error: " . $error);
                }
            }
            
            // Store generated vouchers in session for display
            if (!empty($result['vouchers'])) {
                $_SESSION['generated_vouchers'] = $result['vouchers'];
            }
            
            $_SESSION['redirect'] = "admin/qrcodes.php";
        } else {
            throw new Exception('Failed to generate QR codes: ' . implode(', ', $result['errors']));
        }
    }
    
    /**
     * Delete a voucher
     */
    private function deleteVoucher() {
        $voucher_id = intval($_POST['voucher_id'] ?? 0);
        
        if ($voucher_id <= 0) {
            throw new Exception('Invalid voucher ID.');
        }
        
        $success = $this->voucherModel->deleteVoucher($voucher_id);
        
        if ($success) {
            $_SESSION['success_message'] = "Voucher successfully deleted.";
            $_SESSION['redirect'] = "admin/qrcodes.php";
        } else {
            throw new Exception('Failed to delete voucher. Please try again.');
        }
    }
    
    /**
     * Get all vouchers with optional filtering
     * 
     * @param array $filters Optional filters
     * @return array List of vouchers
     */
    public function getAllVouchers($filters = []) {
        return $this->voucherModel->getAllVouchers($filters);
    }
    
    /**
     * Get a single voucher by ID
     * 
     * @param int $id Voucher ID
     * @return array|false Voucher data or false if not found
     */
    public function getVoucherById($id) {
        return $this->voucherModel->getVoucherById($id);
    }
    
    /**
     * Get voters without vouchers
     * 
     * @return array List of voters without vouchers
     */
    public function getVotersWithoutVouchers() {
        global $pdo;
        try {
            $stmt = $pdo->query("
                SELECT v.* 
                FROM voters v
                LEFT JOIN vouchers vc ON v.id = vc.voter_id
                WHERE vc.id IS NULL
                ORDER BY v.last_name ASC, v.first_name ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getVotersWithoutVouchers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate QR code image
     * 
     * @param string $data QR code data
     * @return string Base64 encoded QR code image
     */
    public function generateQRCodeImage($data) {
        // Check if endroid/qr-code is available
        if (class_exists('Endroid\QrCode\QrCode')) {
            // Use endroid/qr-code library
            $qrCode = new \Endroid\QrCode\QrCode($data);
            $qrCode->setSize(300);
            $qrCode->setMargin(10);
            
            // Get QR code as data URI
            $dataUri = $qrCode->writeDataUri();
            return $dataUri;
        } else {
            // Fallback to a JavaScript library
            // Return the data to be used by qrious.js
            return htmlspecialchars($data);
        }
    }
    
    /**
     * Verify voucher for login
     * 
     * @param string $voucherId Voucher ID
     * @param string $password Password
     * @return array|false Voter data if verified, false otherwise
     */
    public function verifyVoucher($voucherId, $password) {
        return $this->voucherModel->verifyVoucher($voucherId, $password);
    }
}

<?php
require_once '../../include/config.php';
require_once '../../include/db_connect.php';
require_once '../models/Voucher.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get QR code data
$qr_data = $_POST['qr_data'] ?? '';

// Validate input
if (empty($qr_data)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'QR code data is required']);
    exit();
}

// Initialize model
$voucherModel = new Voucher($pdo);

// Verify QR code
$voter = $voucherModel->verifyQRCode($qr_data);

if ($voter) {
    // Set session variables
    $_SESSION['voter_id'] = $voter['id'];
    $_SESSION['voter_name'] = $voter['first_name'] . ' ' . $voter['last_name'];
    $_SESSION['voucher_id'] = $qr_data;
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'QR code verified successfully',
        'redirect' => BASE_URL . '/vote/ballot.php'
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid QR code']);
}

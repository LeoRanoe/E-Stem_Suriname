<?php
require_once '../../include/config.php';
require_once '../../include/db_connect.php';
require_once '../controllers/QrCodeController.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get login credentials
$voucher_id = $_POST['voucher_id'] ?? '';
$password = $_POST['password'] ?? '';

// Validate input
if (empty($voucher_id) || empty($password)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Voucher ID and password are required']);
    exit();
}

// Initialize controller
$qrController = new QrCodeController();

// Verify voucher
$voter = $qrController->verifyVoucher($voucher_id, $password);

if ($voter) {
    // Set session variables
    $_SESSION['voter_id'] = $voter['id'];
    $_SESSION['voter_name'] = $voter['first_name'] . ' ' . $voter['last_name'];
    $_SESSION['voucher_id'] = $voucher_id;
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Login successful',
        'redirect' => BASE_URL . '/vote/ballot.php'
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid voucher ID or password']);
}

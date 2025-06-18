<?php
require_once '../../include/config.php';
require_once '../../include/db_connect.php';
require_once '../../include/VoterAuth.php';

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

// Initialize VoterAuth
$voterAuth = new VoterAuth($pdo);

// Verify QR code
$voter = $voterAuth->verifyQRCode($qr_data);

if ($voter) {
    // Set session variables
    $_SESSION['voter_id'] = $voter['id'];
    $_SESSION['voter_name'] = $voter['first_name'] . ' ' . $voter['last_name'];
    $_SESSION['district_id'] = $voter['district_id'];
    $_SESSION['resort_id'] = $voter['resort_id'];
    $_SESSION['voucher_id'] = $qr_data;
    
    // Debug log
    error_log("QR Code verification successful for voter ID: {$voter['id']}, name: {$voter['first_name']} {$voter['last_name']}");
    
    // IMPORTANT: Do NOT mark voucher as used yet - that will happen after voting
    // Removed: $voterAuth->markVoucherAsUsed($qr_data);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'QR code verified successfully',
        'redirect' => BASE_URL . '/pages/voting/index.php'
    ]);
} else {
    error_log("QR Code verification failed: Invalid QR code: $qr_data");
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Ongeldige QR-code. Probeer opnieuw of log in met uw voucher ID.'
    ]);
}

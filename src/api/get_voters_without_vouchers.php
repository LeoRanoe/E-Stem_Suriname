<?php
require_once '../../include/config.php';
require_once '../../include/db_connect.php';
require_once '../../include/admin_auth.php';
require_once '../controllers/QrCodeController.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Initialize controller
$qrController = new QrCodeController();

// Get voters without vouchers
$voters = $qrController->getVotersWithoutVouchers();

// Ensure proper response format
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Return JSON response
echo json_encode([
    'success' => true, 
    'voters' => $voters,
    'count' => count($voters)
]);

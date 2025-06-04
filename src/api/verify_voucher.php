<?php
/**
 * API endpoint for verifying voter voucher credentials
 * 
 * Part of the E-Stem Suriname voting system
 */
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../src/controllers/VoucherController.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize controller
$voucherController = new VoucherController();

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Check if required parameters are present
if (empty($input['code']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Verify voucher
$result = $voucherController->verifyVoucher($input['code'], $input['password']);

if (!$result) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid voucher code or password']);
    exit;
}

if (isset($result['error'])) {
    http_response_code(403);
    echo json_encode($result);
    exit;
}

// Return success response
echo json_encode([
    'success' => true,
    'voter_id' => $result['voter_id'],
    'voter_name' => $result['voter_name']
]);

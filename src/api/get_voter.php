<?php
require_once '../../include/config.php';
require_once '../../include/db_connect.php';
require_once '../../include/admin_auth.php';
require_once '../controllers/VoterController.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get voter ID from request
$voter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($voter_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid voter ID']);
    exit();
}

// Initialize controller
$voterController = new VoterController();

// Get voter data
$voter = $voterController->getVoterById($voter_id);

if ($voter) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'voter' => $voter]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Voter not found']);
}

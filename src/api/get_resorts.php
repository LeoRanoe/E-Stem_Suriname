<?php
require_once '../../include/config.php';
require_once '../../include/db_connect.php';
require_once '../../include/admin_auth.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get district ID from request
$district_id = isset($_GET['district_id']) ? intval($_GET['district_id']) : 0;

if ($district_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid district ID']);
    exit();
}

try {
    global $pdo;
    
    // Get resorts by district directly from the database
    $stmt = $pdo->prepare("SELECT id as ResortID, name as ResortName FROM resorts WHERE district_id = ? ORDER BY name");
    $stmt->execute([$district_id]);
    $resorts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'resorts' => $resorts]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit();
}

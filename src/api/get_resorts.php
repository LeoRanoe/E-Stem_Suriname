<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

require_once '../../include/config.php';
require_once '../../include/db_connect.php';
require_once '../../include/admin_auth.php';

// Log the request
file_put_contents('resorts_api.log', '[' . date('Y-m-d H:i:s') . '] Request: ' . print_r($_GET, true) . "\n", FILE_APPEND);

try {
    // Check if admin is logged in
    if (!isAdminLoggedIn()) {
        throw new Exception('Unauthorized access');
    }

    // Get district ID from request
    $district_id = isset($_GET['district_id']) ? intval($_GET['district_id']) : 0;

    if ($district_id <= 0) {
        throw new Exception('Invalid district ID: ' . $district_id);
    }

    // Get database connection
    global $pdo;
    
    // Check if connection is valid
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get resorts by district
    $stmt = $pdo->prepare("SELECT id as ResortID, name as ResortName FROM resorts WHERE district_id = ? ORDER BY name");
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    
    $result = $stmt->execute([$district_id]);
    
    if ($result === false) {
        $error = $stmt->errorInfo();
        throw new Exception('Database query failed: ' . ($error[2] ?? 'Unknown error'));
    }
    
    $resorts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the response
    file_put_contents('resorts_api.log', '[' . date('Y-m-d H:i:s') . '] Response: ' . json_encode($resorts) . "\n", FILE_APPEND);
    
    echo json_encode([
        'success' => true, 
        'resorts' => $resorts,
        'count' => count($resorts)
    ]);
    
} catch (Exception $e) {
    // Log the error
    $errorMessage = 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
    file_put_contents('resorts_api.log', '[' . date('Y-m-d H:i:s') . '] ' . $errorMessage . "\n", FILE_APPEND);
    
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'error' => $errorMessage
    ]);
}

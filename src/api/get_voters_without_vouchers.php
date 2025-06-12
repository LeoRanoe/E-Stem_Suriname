<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

require_once '../../include/config.php';
require_once '../../include/db_connect.php';
require_once '../../include/admin_auth.php';

try {
    // Check if admin is logged in
    if (!isAdminLoggedIn()) {
        throw new Exception('Unauthorized access');
    }

    // Get database connection
    global $pdo;
    
    // Check if connection is valid
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get district filter if provided
    $district_id = isset($_GET['district_id']) ? intval($_GET['district_id']) : null;
    
    // Get voters without vouchers
    $query = "
        SELECT v.id, v.first_name, v.last_name, v.id_number, v.district_id, v.resort_id, 
               d.DistrictName as district_name, r.name as resort_name 
        FROM voters v
        LEFT JOIN vouchers vch ON v.id = vch.voter_id
        LEFT JOIN districten d ON v.district_id = d.DistrictID
        LEFT JOIN resorts r ON v.resort_id = r.id
        WHERE vch.id IS NULL AND v.status = 'active'
    ";
    
    $params = [];
    
    if ($district_id) {
        $query .= " AND v.district_id = ?";
        $params[] = $district_id;
    }
    
    $query .= " ORDER BY v.first_name, v.last_name";
    
    $stmt = $pdo->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement');
    }
    
    $result = $stmt->execute($params);
    
    if ($result === false) {
        $error = $stmt->errorInfo();
        throw new Exception('Database query failed: ' . ($error[2] ?? 'Unknown error'));
    }
    
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'voters' => $voters,
        'count' => count($voters)
    ]);
    
} catch (Exception $e) {
    // Send error response
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/admin_auth.php';

// Check if user is logged in and is admin
requireAdmin();

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'Invalid party ID.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$party_id = intval($_GET['id']);

try {
    global $pdo;
    $stmt = $pdo->prepare("SELECT PartyID, PartyName, Description, Logo FROM parties WHERE PartyID = ?");
    $stmt->execute([$party_id]);
    $party = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($party) {
        $response['success'] = true;
        $response['data'] = $party;
        $response['message'] = 'Party details fetched successfully.';
    } else {
        $response['message'] = 'Party not found.';
        http_response_code(404);
    }
} catch (PDOException $e) {
    error_log("Database error fetching party details: " . $e->getMessage());
    $response['message'] = 'Database error occurred.';
    http_response_code(500);
}

echo json_encode($response);

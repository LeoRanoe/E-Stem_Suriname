<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/admin_auth.php';

requireAdmin();

$candidate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($candidate_id === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid candidate ID.']);
    exit;
}

try {
    global $pdo;
    $stmt = $pdo->prepare("\n        SELECT c.CandidateID, c.Name, c.PartyID, c.ElectionID, c.DistrictID, c.ResortID, c.CandidateType, c.Photo,\n               p.PartyName, e.ElectionName, d.DistrictName, r.name as ResortName\n        FROM candidates c\n        LEFT JOIN parties p ON c.PartyID = p.PartyID\n        LEFT JOIN elections e ON c.ElectionID = e.ElectionID\n        LEFT JOIN districten d ON c.DistrictID = d.DistrictID\n        LEFT JOIN resorts r ON c.ResortID = r.id\n        WHERE c.CandidateID = ?\n    ");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($candidate) {
        // Split name into first and last name for the form
        $name_parts = explode(' ', $candidate['Name'], 2);
        $candidate['firstName'] = $name_parts[0];
        $candidate['lastName'] = $name_parts[1] ?? '';

        echo json_encode(['success' => true, 'data' => $candidate]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Candidate not found.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>

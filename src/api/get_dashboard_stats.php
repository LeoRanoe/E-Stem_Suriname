<?php
/**
 * API endpoint for getting dashboard statistics
 * 
 * Part of the E-Stem Suriname voting system
 */
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/admin_auth.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

try {
    // Get total voters
    $stmt = $pdo->query("SELECT COUNT(*) as count, status FROM voters GROUP BY status");
    $voter_status = ['active' => 0, 'inactive' => 0];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $voter_status[$row['status']] = (int)$row['count'];
    }
    $total_voters = $voter_status['active'] + $voter_status['inactive'];

    // Get total votes cast
    $stmt = $pdo->query("SELECT COUNT(DISTINCT UserID) as vote_count FROM votes");
    $total_votes = (int)$stmt->fetchColumn();

    // Calculate turnout percentage
    $turnout_percentage = $total_voters > 0 ? round(($total_votes / $total_voters) * 100, 1) : 0;

    // Get total QR codes generated
    $stmt = $pdo->query("SELECT COUNT(*) as qr_count FROM qrcodes");
    $total_qrcodes = (int)$stmt->fetchColumn();

    // Get active elections count
    $stmt = $pdo->query("SELECT COUNT(*) as active_count FROM elections WHERE Status = 'active'");
    $active_elections = (int)$stmt->fetchColumn();

    // Get votes per day for the last 7 days
    $stmt = $pdo->query("
        SELECT 
            DATE(TimeStamp) as date,
            COUNT(*) as count
        FROM votes
        WHERE TimeStamp >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(TimeStamp)
        ORDER BY date
    ");
    $votes_per_day = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get votes by district
    $stmt = $pdo->query("
        SELECT 
            d.DistrictName as district,
            COUNT(v.VoteID) as count
        FROM votes v
        JOIN voters vt ON v.UserID = vt.id
        JOIN districten d ON vt.district_id = d.DistrictID
        GROUP BY d.DistrictID, d.DistrictName
        ORDER BY count DESC
        LIMIT 10
    ");
    $votes_by_district = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return success response with stats
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_voters' => $total_voters,
            'total_votes' => $total_votes,
            'turnout_percentage' => $turnout_percentage,
            'total_qrcodes' => $total_qrcodes,
            'active_elections' => $active_elections,
            'voter_status' => $voter_status,
            'votes_per_day' => $votes_per_day,
            'votes_by_district' => $votes_by_district
        ]
    ]);
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>

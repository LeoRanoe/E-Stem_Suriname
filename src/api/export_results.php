<?php
require_once '../../include/config.php';
require_once '../../include/db_connect.php';
require_once '../../include/admin_auth.php';

// Ensure admin is logged in
requireAdmin();

// Get the requested format
$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

try {
    // Find the active election - using correct column 'ElectionName'
    $stmt = $pdo->query("SELECT ElectionID, ElectionName FROM elections WHERE Status = 'active' ORDER BY StartDate DESC LIMIT 1");
    $active_election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$active_election) {
        throw new Exception('No active election found.');
    }

    $election_id = $active_election['ElectionID'];
    $election_name = $active_election['ElectionName'] . ' - ' . date('Y-m-d');

    // Fetch detailed results - using correct table and column names from schema
    $stmt = $pdo->prepare("
        SELECT
            d.DistrictName as district,
            r.name as resort,
            p.PartyName as party,
            c.Name as candidate,
            COUNT(v.VoteID) as vote_count
        FROM votes v
        JOIN candidates c ON v.CandidateID = c.CandidateID
        JOIN parties p ON c.PartyID = p.PartyID
        JOIN voters u ON v.UserID = u.id
        JOIN resorts r ON u.resort_id = r.id
        JOIN districten d ON r.district_id = d.DistrictID
        WHERE v.ElectionID = ?
        GROUP BY d.DistrictName, r.name, p.PartyName, c.Name
        ORDER BY vote_count DESC
    ");
    $stmt->execute([$election_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch total votes for percentage calculation
    $stmt_total = $pdo->prepare("SELECT COUNT(VoteID) as total_votes FROM votes WHERE ElectionID = ?");
    $stmt_total->execute([$election_id]);
    $total_votes = $stmt_total->fetchColumn();

    if ($format === 'csv') {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $election_name) . '_results.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers to CSV
        fputcsv($output, ['District', 'Resort', 'Party', 'Candidate', 'Vote Count', '% of Total Votes']);
        
        // Add data to CSV
        if (!empty($results)) {
            foreach ($results as $row) {
                $percentage = $total_votes > 0 ? round(($row['vote_count'] / $total_votes) * 100, 2) : 0;
                $line = [
                    $row['district'],
                    $row['resort'],
                    $row['party'],
                    $row['candidate'],
                    $row['vote_count'],
                    $percentage . '%'
                ];

                fputcsv($output, $line);
            }
        }
        
        fclose($output);
        exit();
    } else {
        throw new Exception('Invalid format specified.');
    }

} catch (Exception $e) {
    // Handle errors gracefully
    error_log("Export Error: " . $e->getMessage());
    header('Content-Type: text/plain');
    http_response_code(500);
    echo 'Error: Could not generate export. Please check the logs.';
    exit();
}

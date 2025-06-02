<?php
require_once '../../include/config.php';
require_once '../../include/db_connect.php';
require_once '../../include/admin_auth.php';
require_once '../controllers/ResultsController.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Content-Type: text/plain');
    echo 'Unauthorized';
    exit();
}

// Get election ID from request
$election_id = isset($_POST['election_id']) ? intval($_POST['election_id']) : 0;

if ($election_id <= 0) {
    header('Content-Type: text/plain');
    echo 'Invalid election ID';
    exit();
}

// Initialize controller
$resultsController = new ResultsController();

// Get election details
$elections = $resultsController->getActiveElections();
$electionName = 'Election Results';

foreach ($elections as $election) {
    if ($election['ElectionID'] == $election_id) {
        $electionName = $election['ElectionName'] . ' - ' . date('Y-m-d', strtotime($election['ElectionDate']));
        break;
    }
}

// Generate CSV content
$csvContent = $resultsController->exportResultsToCSV($election_id);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $electionName) . '_results.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Output CSV content
echo $csvContent;

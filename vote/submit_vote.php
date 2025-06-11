<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';
require_once __DIR__ . '/../include/VoterAuth.php';

// Ensure the request is POST and JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Validate request
if (!$jsonData || json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['voter_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to vote']);
    exit;
}

// Initialize VoterAuth
$voterAuth = new VoterAuth($pdo);

// Check if voter has already voted
if ($voterAuth->hasVoted($_SESSION['voter_id'])) {
    echo json_encode(['success' => false, 'message' => 'You have already voted in this election']);
    exit;
}

// Get active election
try {
    $stmt = $pdo->query("SELECT ElectionID FROM elections WHERE Status = 'active' LIMIT 1");
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$election) {
        echo json_encode(['success' => false, 'message' => 'No active election found']);
        exit;
    }
    
    $electionId = $election['ElectionID'];
} catch (PDOException $e) {
    error_log("Error getting active election: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while retrieving election information']);
    exit;
}

// Validate candidate IDs
$dnaCandidateId = $data['dna_candidate_id'] ?? null;
$rrCandidateId = $data['rr_candidate_id'] ?? null;

if (!$dnaCandidateId && !$rrCandidateId) {
    echo json_encode(['success' => false, 'message' => 'You must select at least one candidate']);
    exit;
}

// Get voter's resort
try {
    $stmtVoter = $pdo->prepare("SELECT resort_id FROM voters WHERE id = ?");
    $stmtVoter->execute([$_SESSION['voter_id']]);
    $voter = $stmtVoter->fetch(PDO::FETCH_ASSOC);
    $voterResortId = $voter['resort_id'] ?? null;
    
    // If RR candidate is selected, validate it belongs to voter's resort
    if ($rrCandidateId) {
        $stmtCandidate = $pdo->prepare("SELECT ResortID FROM candidates WHERE CandidateID = ? AND CandidateType = 'RR'");
        $stmtCandidate->execute([$rrCandidateId]);
        $candidate = $stmtCandidate->fetch(PDO::FETCH_ASSOC);
        
        if (!$candidate || $candidate['ResortID'] != $voterResortId) {
            echo json_encode(['success' => false, 'message' => 'You can only vote for RR candidates from your own resort']);
            exit;
        }
    }
} catch (PDOException $e) {
    error_log("Error validating candidate resort: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while validating your vote']);
    exit;
}

try {
    // Begin transaction
    $pdo->beginTransaction();
    $voterId = $_SESSION['voter_id'];
    $timestamp = date('Y-m-d H:i:s');
    
    // Insert DNA vote if selected
    if ($dnaCandidateId) {
        $stmt = $pdo->prepare("
            INSERT INTO votes (UserID, CandidateID, ElectionID, TimeStamp, VoteType)
            VALUES (?, ?, ?, ?, 'DNA')
        ");
        $stmt->execute([$voterId, $dnaCandidateId, $electionId, $timestamp]);
    }
    
    // Insert RR vote if selected
    if ($rrCandidateId) {
        $stmt = $pdo->prepare("
            INSERT INTO votes (UserID, CandidateID, ElectionID, TimeStamp, VoteType)
            VALUES (?, ?, ?, ?, 'RR')
        ");
        $stmt->execute([$voterId, $rrCandidateId, $electionId, $timestamp]);
    }
    
    // Update voter status to mark as voted
    $stmt = $pdo->prepare("
        UPDATE voters 
        SET has_voted = 1, last_voted_at = ? 
        WHERE VoterID = ?
    ");
    $stmt->execute([$timestamp, $voterId]);
    
    // Log the vote
    $stmt = $pdo->prepare("
        INSERT INTO vote_logs (voter_id, election_id, timestamp, ip_address) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $voterId, 
        $electionId, 
        $timestamp, 
        $_SERVER['REMOTE_ADDR'] ?? null
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Set session flag
    $_SESSION['has_voted'] = true;
    
    echo json_encode([
        'success' => true, 
        'message' => 'Your vote has been successfully recorded',
        'timestamp' => $timestamp
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Error recording vote: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while recording your vote']);
}
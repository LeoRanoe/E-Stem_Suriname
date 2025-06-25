<?php
// Include configuration and database connection
require_once '../../include/config.php';
require_once '../../include/db_connect.php';

// Get voter ID from query parameter
$voterId = isset($_GET['voter_id']) ? (int)$_GET['voter_id'] : null;

if (!$voterId) {
    die("Error: No voter ID provided. Use ?voter_id=XXX in the URL.");
}

try {
    // Start a transaction
    $pdo->beginTransaction();
    
    // Delete votes for this voter
    $stmtVotes = $pdo->prepare("DELETE FROM votes WHERE UserID = ?");
    $stmtVotes->execute([$voterId]);
    $votesDeleted = $stmtVotes->rowCount();
    
    // Update QR codes status back to active
    $stmtQR = $pdo->prepare("UPDATE qrcodes SET Status = 'active', UsedAt = NULL WHERE UserID = ?");
    $stmtQR->execute([$voterId]);
    $qrCodesUpdated = $stmtQR->rowCount();
    
    // Reset voucher status
    $stmtVoucher = $pdo->prepare("UPDATE vouchers SET used = 0 WHERE voter_id = ?");
    $stmtVoucher->execute([$voterId]);
    $vouchersUpdated = $stmtVoucher->rowCount();
    
    // Commit the transaction
    $pdo->commit();
    
    // Clear session if it's for this voter
    session_start();
    if (isset($_SESSION['voter_id']) && $_SESSION['voter_id'] == $voterId) {
        unset($_SESSION['has_voted']);
        unset($_SESSION['message']);
    }
    
    // Output results
    echo "<h2>Voter Reset Complete</h2>";
    echo "<p>Deleted $votesDeleted votes for voter ID $voterId</p>";
    echo "<p>Reset $qrCodesUpdated QR codes to active</p>";
    echo "<p>Reset $vouchersUpdated vouchers to unused</p>";
    echo "<p><a href='../../voter/index.php'>Return to login page</a></p>";
    
    // Log the action
    error_log("Reset voter ID $voterId: Deleted $votesDeleted votes, reset $qrCodesUpdated QR codes, reset $vouchersUpdated vouchers");
    
} catch (PDOException $e) {
    // Rollback the transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    die("Database error: " . $e->getMessage());
} 
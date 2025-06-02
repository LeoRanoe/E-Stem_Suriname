<?php
require_once 'include/config.php';
require_once 'include/db_connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log("Logout - Starting logout process");
error_log("Logout - Session ID: " . session_id());
error_log("Logout - Session data before logout: " . print_r($_SESSION, true));

// Clear admin session
if (isset($_SESSION['AdminID'])) {
    error_log("Logout - Clearing admin session");
    unset($_SESSION['AdminID']);
    unset($_SESSION['AdminName']);
    unset($_SESSION['AdminEmail']);
    unset($_SESSION['AdminStatus']);
}

// Clear voter session
if (isset($_SESSION['VoterID'])) {
    error_log("Logout - Clearing voter session");
    
    // Update voter session in database if exists
    if (isset($_SESSION['VoterSessionID'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE voter_sessions 
                SET is_active = 0 
                WHERE id = :session_id AND voter_id = :voter_id
            ");
            $stmt->execute([
                'session_id' => $_SESSION['VoterSessionID'],
                'voter_id' => $_SESSION['VoterID']
            ]);
        } catch (PDOException $e) {
            error_log("Logout - Error updating voter session: " . $e->getMessage());
        }
    }
    
    unset($_SESSION['VoterID']);
    unset($_SESSION['VoterName']);
    unset($_SESSION['VoterSessionID']);
}

// Clear any other session data
session_unset();
session_destroy();

error_log("Logout - Session destroyed");

// Redirect to home page
header("Location: " . BASE_URL . "/index.php");
exit(); 
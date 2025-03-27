<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Get voter ID from URL
$voter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($voter_id <= 0) {
    $_SESSION['error_message'] = "Ongeldige stemmer ID.";
    header('Location: voters.php');
    exit;
}

try {
    // Check if voter exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE UserID = ?");
    $stmt->execute([$voter_id]);
    if ($stmt->fetchColumn() === 0) {
        throw new Exception("Stemmer niet gevonden.");
    }

    // Delete the voter
    $stmt = $pdo->prepare("DELETE FROM users WHERE UserID = ?");
    $stmt->execute([$voter_id]);

    $_SESSION['success_message'] = "Stemmer is succesvol verwijderd.";
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: voters.php');
exit; 
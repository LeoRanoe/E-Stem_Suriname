<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Get party ID from URL
$party_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($party_id === 0) {
    $_SESSION['error_message'] = "Ongeldige partij ID.";
    header('Location: parties.php');
    exit;
}

try {
    // Check if party has candidates
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE PartyID = ?");
    $stmt->execute([$party_id]);
    $has_candidates = $stmt->fetchColumn() > 0;

    if ($has_candidates) {
        throw new Exception('Deze partij kan niet worden verwijderd omdat er nog kandidaten aan gekoppeld zijn.');
    }

    // Get logo path before deleting
    $stmt = $pdo->prepare("SELECT Logo FROM parties WHERE PartyID = ?");
    $stmt->execute([$party_id]);
    $logo_path = $stmt->fetchColumn();

    // Delete party
    $stmt = $pdo->prepare("DELETE FROM parties WHERE PartyID = ?");
    $stmt->execute([$party_id]);

    // Delete logo file if exists
    if ($logo_path && file_exists('../' . $logo_path)) {
        unlink('../' . $logo_path);
    }

    $_SESSION['success_message'] = "Partij is succesvol verwijderd.";
} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
}

header('Location: parties.php');
exit; 
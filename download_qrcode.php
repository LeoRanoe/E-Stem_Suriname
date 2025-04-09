<?php
// Start output buffering
ob_start();

session_start();
require_once 'include/db_connect.php';
require_once 'include/auth.php';

// Check if user is logged in
requireLogin();

// Get the QR code data from the URL
$qr_data = $_GET['data'] ?? '';

if (empty($qr_data)) {
    header('HTTP/1.1 400 Bad Request');
    echo 'QR code data is required';
    exit;
}

// Check if the QR code exists in the database
try {
    $stmt = $pdo->prepare("
        SELECT q.QRCode, q.Status, CONCAT(u.Voornaam, ' ', u.Achternaam) as UserName
        FROM qrcodes q
        JOIN users u ON q.UserID = u.UserID
        WHERE q.QRCode = :qr_code
    ");
    $stmt->execute(['qr_code' => $qr_data]);
    $qr_info = $stmt->fetch();
    
    if (!$qr_info) {
        header('HTTP/1.1 404 Not Found');
        echo 'QR code not found';
        exit;
    }
} catch(PDOException $e) {
    error_log("Error checking QR code: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo 'An error occurred';
    exit;
}

// Generate the QR code URL
$qr_url = BASE_URL . '/scan_to_vote.php?token=' . $qr_data;
$qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qr_url) . "&size=300x300";

// Check if download is requested
if (isset($_GET['download']) && $_GET['download'] == 1) {
    // Set headers for download
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="qr_code_' . $qr_data . '.png"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output the QR code image
    echo file_get_contents($qr_image_url);
    exit;
}

// Otherwise, just display the QR code
header('Content-Type: image/png');
echo file_get_contents($qr_image_url);
exit; 
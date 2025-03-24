<?php
session_start();
require_once '../include/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['User ID'])) {
    header('Location: /E-Stem_Suriname/pages/login.php');
    exit();
}

// Check if user is admin
$stmt = $conn->prepare("
    SELECT ut.UserType 
    FROM users u 
    JOIN usertype ut ON u.UTypeID = ut.UTypeID 
    WHERE u.UserID = :userID
");
$stmt->execute(['userID' => $_SESSION['User ID']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['UserType'] !== 'Admin') {
    header('Location: /E-Stem_Suriname/index.php');
    exit();
}

// Get QR code ID
$qr_id = $_GET['id'] ?? '';
if (empty($qr_id)) {
    header('Location: /E-Stem_Suriname/admin/qr_codes.php');
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Update QR code status
    $stmt = $conn->prepare("
        UPDATE qrcodes 
        SET Status = 'revoked', 
            RevokedAt = NOW(),
            RevokedBy = :userID
        WHERE QRCodeID = :qr_id 
        AND Status = 'active'
    ");
    $stmt->execute([
        'userID' => $_SESSION['User ID'],
        'qr_id' => $qr_id
    ]);

    // Commit transaction
    $conn->commit();

    // Redirect back to QR codes page with success message
    $_SESSION['success_message'] = 'QR code is succesvol ingetrokken.';
    header('Location: /E-Stem_Suriname/admin/qr_codes.php');
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    // Log error
    error_log("Error revoking QR code: " . $e->getMessage());
    
    // Redirect back with error message
    $_SESSION['error_message'] = 'Er is een fout opgetreden bij het intrekken van de QR code.';
    header('Location: /E-Stem_Suriname/admin/qr_codes.php');
    exit();
} 
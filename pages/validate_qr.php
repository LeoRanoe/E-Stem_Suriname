<?php
session_start();
require_once '../include/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['User ID'])) {
    header('Location: /E-Stem_Suriname/pages/login.php');
    exit();
}

// Check if user is a voter
$stmt = $conn->prepare("
    SELECT ut.UserType 
    FROM users u 
    JOIN usertype ut ON u.UTypeID = ut.UTypeID 
    WHERE u.UserID = :userID
");
$stmt->execute(['userID' => $_SESSION['User ID']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['UserType'] !== 'Voter') {
    header('Location: /E-Stem_Suriname/index.php');
    exit();
}

// Handle QR code validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qr_code = $_POST['qr_code'] ?? '';
    
    if (empty($qr_code)) {
        $_SESSION['error_message'] = 'QR code is verplicht.';
        header('Location: /E-Stem_Suriname/pages/scan_qr.php');
        exit();
    }

    try {
        // Check if QR code exists and is valid
        $stmt = $conn->prepare("
            SELECT q.*, u.UserID, u.Voornaam, u.Achternaam
            FROM qrcodes q
            JOIN users u ON q.UserID = u.UserID
            WHERE q.QRCode = :qr_code
            AND q.Status = 'active'
            AND q.ExpiryDate > NOW()
        ");
        $stmt->execute(['qr_code' => $qr_code]);
        $qr = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$qr) {
            $_SESSION['error_message'] = 'Ongeldige of verlopen QR code.';
            header('Location: /E-Stem_Suriname/pages/scan_qr.php');
            exit();
        }

        if ($qr['UserID'] !== $_SESSION['User ID']) {
            $_SESSION['error_message'] = 'Deze QR code is niet voor u bestemd.';
            header('Location: /E-Stem_Suriname/pages/scan_qr.php');
            exit();
        }

        // Check if user has already voted
        $stmt = $conn->prepare("
            SELECT COUNT(*) as vote_count 
            FROM votes 
            WHERE UserID = :userID 
            AND ElectionID = (SELECT ElectionID FROM elections WHERE Status = 'active')
        ");
        $stmt->execute(['userID' => $_SESSION['User ID']]);
        $vote_count = $stmt->fetch(PDO::FETCH_ASSOC)['vote_count'];

        if ($vote_count > 0) {
            $_SESSION['error_message'] = 'U heeft al gestemd in deze verkiezing.';
            header('Location: /E-Stem_Suriname/index.php');
            exit();
        }

        // Mark QR code as used
        $stmt = $conn->prepare("
            UPDATE qrcodes 
            SET Status = 'used',
                UsedAt = NOW()
            WHERE QRCodeID = :qr_id
        ");
        $stmt->execute(['qr_id' => $qr['QRCodeID']]);

        // Set session variable for QR validation
        $_SESSION['qr_validated'] = true;
        $_SESSION['qr_code'] = $qr_code;

        // Redirect to voting page
        header('Location: /E-Stem_Suriname/pages/vote.php');
        exit();

    } catch (Exception $e) {
        error_log("Error validating QR code: " . $e->getMessage());
        $_SESSION['error_message'] = 'Er is een fout opgetreden bij het valideren van de QR code.';
        header('Location: /E-Stem_Suriname/pages/scan_qr.php');
        exit();
    }
}

// If not POST request, redirect to scan page
header('Location: /E-Stem_Suriname/pages/scan_qr.php');
exit(); 
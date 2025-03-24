<?php
session_start();
require_once '../db.php';
require_once '../vendor/autoload.php'; // For QR code generation

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE UserID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Generate unique QR code
$qr_data = json_encode([
    'user_id' => $user_id,
    'timestamp' => time(),
    'token' => bin2hex(random_bytes(32))
]);

// Create QR code
$qrCode = QrCode::create($qr_data)
    ->setSize(300)
    ->setMargin(10)
    ->setForegroundColor(['r' => 0, 'g' => 119, 'b' => 73]) // Suriname green
    ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255]);

// Create writer
$writer = new PngWriter();

// Generate QR code
$result = $writer->write($qrCode);

// Save QR code to database
$qr_code = bin2hex(random_bytes(16));
$stmt = $conn->prepare("INSERT INTO qr_codes (UserID, Code, ExpiresAt) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))");
$stmt->execute([$user_id, $qr_code]);

// Get QR code image as data URI
$dataUri = $result->getDataUri();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - E-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include '../include/nav.php'; ?>

    <main class="container mx-auto px-4 py-16">
        <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-lg p-8">
            <h1 class="text-3xl font-bold text-center mb-8">Uw QR Code voor Stemmen</h1>
            
            <div class="text-center mb-8">
                <p class="text-gray-600 mb-4">
                    Scan deze QR code met uw smartphone om te stemmen. Deze code is 24 uur geldig.
                </p>
                <div class="bg-white p-4 rounded-lg shadow-md inline-block">
                    <img src="<?= $dataUri ?>" alt="QR Code" class="w-64 h-64">
                </div>
            </div>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Let op: Deze QR code is persoonlijk en eenmalig bruikbaar. Deel deze code niet met anderen.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <a href="vote.php" class="block w-full text-center bg-suriname-green text-white py-3 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                    <i class="fas fa-vote-yea mr-2"></i> Ga naar Stemmen
                </a>
                <a href="dashboard.php" class="block w-full text-center bg-gray-100 text-gray-700 py-3 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                    <i class="fas fa-arrow-left mr-2"></i> Terug naar Dashboard
                </a>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
</body>
</html> 
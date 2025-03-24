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

// Get QR code details
$qr_id = $_GET['id'] ?? '';
if (empty($qr_id)) {
    header('Location: /E-Stem_Suriname/admin/qr_codes.php');
    exit();
}

$stmt = $conn->prepare("
    SELECT q.*, u.Voornaam, u.Achternaam, u.Email, d.Name as District
    FROM qrcodes q
    JOIN users u ON q.UserID = u.UserID
    JOIN districts d ON u.DistrictID = d.DistrictID
    WHERE q.QRCodeID = :qr_id
");
$stmt->execute(['qr_id' => $qr_id]);
$qr = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$qr) {
    header('Location: /E-Stem_Suriname/admin/qr_codes.php');
    exit();
}

// Generate QR code image
require_once '../vendor/autoload.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$qrCode = QrCode::create($qr['QRCode'])
    ->setSize(300)
    ->setMargin(10);

$writer = new PngWriter();
$result = $writer->write($qrCode);
$qrImage = $result->getDataUri();
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Details - E-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'suriname': {
                            'green': '#007749',
                            'dark-green': '#006241',
                            'red': '#C8102E',
                            'dark-red': '#a50d26',
                        },
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-gray-50">
    <?php include '../include/nav.php'; ?>

    <div class="min-h-screen pt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">QR Code Details</h1>
                    <a href="/E-Stem_Suriname/admin/qr_codes.php" 
                        class="text-suriname-green hover:text-suriname-dark-green flex items-center space-x-2">
                        <i class="fas fa-arrow-left"></i>
                        <span>Terug naar overzicht</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- QR Code Image -->
                    <div class="flex flex-col items-center space-y-4">
                        <img src="<?= $qrImage ?>" alt="QR Code" class="w-64 h-64">
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Scan deze QR code om te stemmen</p>
                            <p class="text-xs text-gray-400 mt-1">Code: <?= htmlspecialchars($qr['QRCode']) ?></p>
                        </div>
                        <div class="flex space-x-4">
                            <a href="<?= $qrImage ?>" download="qr_code.png" 
                                class="bg-suriname-green text-white px-4 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-300 flex items-center space-x-2">
                                <i class="fas fa-download"></i>
                                <span>Download</span>
                            </a>
                            <?php if ($qr['Status'] === 'active'): ?>
                                <a href="/E-Stem_Suriname/admin/revoke_qr.php?id=<?= $qr['QRCodeID'] ?>" 
                                    class="bg-suriname-red text-white px-4 py-2 rounded-lg hover:bg-suriname-dark-red transition-colors duration-300 flex items-center space-x-2"
                                    onclick="return confirm('Weet u zeker dat u deze QR code wilt intrekken?')">
                                    <i class="fas fa-ban"></i>
                                    <span>Intrekken</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- User Details -->
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Gebruikersgegevens</h2>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Naam</label>
                                    <p class="text-gray-900"><?= htmlspecialchars($qr['Voornaam'] . ' ' . $qr['Achternaam']) ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">E-mail</label>
                                    <p class="text-gray-900"><?= htmlspecialchars($qr['Email']) ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">District</label>
                                    <p class="text-gray-900"><?= htmlspecialchars($qr['District']) ?></p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">QR Code Status</h2>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Status</label>
                                    <p class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?= $qr['Status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $qr['Status'] === 'active' ? 'Actief' : 'Ingetrokken' ?>
                                    </p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Gegenereerd op</label>
                                    <p class="text-gray-900"><?= date('d-m-Y H:i', strtotime($qr['CreatedAt'])) ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Vervalt op</label>
                                    <p class="text-gray-900"><?= date('d-m-Y H:i', strtotime($qr['ExpiryDate'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>
</body>
</html> 
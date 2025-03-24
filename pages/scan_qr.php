<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in
requireLogin();

// Check if user is a voter
$currentUser = getCurrentUser();
if ($currentUser['Role'] !== 'voter') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

// Handle QR code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_code'])) {
    try {
        $qr_code = trim($_POST['qr_code']);
        
        // Validate QR code
        $stmt = $pdo->prepare("
            SELECT q.*, vs.Status as session_status
            FROM qrcodes q
            LEFT JOIN voting_sessions vs ON q.QRCodeID = vs.QRCodeID
            WHERE q.Code = ? AND q.Status = 'active'
        ");
        $stmt->execute([$qr_code]);
        $qr_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$qr_data) {
            throw new Exception('Ongeldige QR code.');
        }

        if ($qr_data['session_status'] === 'active') {
            throw new Exception('Deze QR code is al in gebruik.');
        }

        // Check if user has already voted
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as vote_count 
            FROM votes 
            WHERE UserID = ? AND ElectionID = ?
        ");
        $stmt->execute([$_SESSION['User ID'], $qr_data['ElectionID']]);
        $vote_count = $stmt->fetch(PDO::FETCH_ASSOC)['vote_count'];

        if ($vote_count > 0) {
            throw new Exception('U heeft al gestemd in deze verkiezing.');
        }

        // Start voting session
        $stmt = $pdo->prepare("
            INSERT INTO voting_sessions (QRCodeID, UserID, Status, StartTime)
            VALUES (?, ?, 'active', NOW())
        ");
        $stmt->execute([$qr_data['QRCodeID'], $_SESSION['User ID']]);

        // Redirect to voting page
        header('Location: ' . BASE_URL . '/pages/vote.php?qr=' . urlencode($qr_code));
        exit();

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scannen - <?= SITE_NAME ?></title>
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

    <main class="container mx-auto px-4 py-16">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">QR Code Scannen</h1>
                <p class="mt-2 text-gray-600">Scan uw QR code om te stemmen</p>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <p><?= $_SESSION['error_message'] ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <form method="POST" class="space-y-6">
                    <div class="space-y-4">
                        <div>
                            <label for="qr_code" class="block text-sm font-medium text-gray-700">
                                QR Code
                            </label>
                            <div class="mt-1">
                                <input type="text" 
                                       name="qr_code" 
                                       id="qr_code" 
                                       required
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm"
                                       placeholder="Voer uw QR code in">
                            </div>
                        </div>

                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        U heeft 30 minuten de tijd om uw stem uit te brengen nadat u de QR code heeft ingevoerd.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-suriname-green text-white px-6 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                            <i class="fas fa-qrcode mr-2"></i> QR Code Verifiëren
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
</body>
</html> 
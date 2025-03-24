<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in
if (!isset($_SESSION['User ID'])) {
    $_SESSION['error_message'] = "U moet ingelogd zijn om te stemmen.";
    header("Location: " . BASE_URL . "/pages/login.php");
    exit;
}

// If QR code is already verified, redirect to vote page
if (isset($_SESSION['QRVerified']) && $_SESSION['QRVerified']) {
    header("Location: " . BASE_URL . "/pages/vote.php");
    exit;
}

// Handle QR code verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_code'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM qr_codes 
            WHERE Code = :code 
            AND IsUsed = 0
        ");
        $stmt->execute(['code' => $_POST['qr_code']]);
        $qr_code = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($qr_code) {
            // Mark QR code as used
            $stmt = $pdo->prepare("
                UPDATE qr_codes 
                SET IsUsed = 1, 
                    UsedBy = :user_id, 
                    UsedAt = NOW() 
                WHERE Code = :code
            ");
            $stmt->execute([
                'code' => $_POST['qr_code'],
                'user_id' => $_SESSION['User ID']
            ]);

            // Set QR verification in session
            $_SESSION['QRVerified'] = true;
            $_SESSION['success_message'] = "QR code succesvol geverifieerd. U kunt nu stemmen.";
            header("Location: " . BASE_URL . "/pages/vote.php");
            exit;
        } else {
            $_SESSION['error_message'] = "Ongeldige of reeds gebruikte QR code.";
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error_message'] = "Er is een fout opgetreden bij het verifiëren van de QR code.";
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stemmen - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
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
                            'yellow': '#FFD700',
                            'white': '#FFFFFF',
                            'black': '#000000'
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
                <h1 class="text-3xl font-bold text-suriname-green">Stemmen</h1>
                <p class="mt-2 text-gray-600">Scan uw QR code om te verifiëren dat u stemgerechtigd bent</p>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-suriname-red text-red-700 p-4 mb-6 rounded-md">
                    <p><?= $_SESSION['error_message'] ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg p-6 border-2 border-suriname-green">
                <div class="mb-6">
                    <div id="reader" class="rounded-lg overflow-hidden border-2 border-suriname-green"></div>
                </div>

                <form id="qr-form" method="POST" class="hidden">
                    <input type="hidden" name="qr_code" id="qr_code">
                </form>

                <div class="text-center">
                    <p class="text-gray-600 mb-4">
                        Of voer de QR code handmatig in:
                    </p>
                    <div class="flex justify-center space-x-4">
                        <input type="text" 
                               id="manual-qr" 
                               class="rounded-md border-2 border-suriname-green shadow-sm focus:border-suriname-green focus:ring-suriname-green"
                               placeholder="Voer QR code in">
                        <button type="button" 
                                onclick="submitManualQR()"
                                class="bg-suriname-green text-white px-6 py-2 rounded-md hover:bg-suriname-dark-green transition-colors duration-200 shadow-md">
                            <i class="fas fa-check mr-2"></i>
                            Verifiëren
                        </button>
                    </div>
                </div>

                <div class="mt-6 text-center text-sm text-gray-500">
                    <p><i class="fas fa-info-circle mr-2"></i>Zorg ervoor dat uw QR code goed zichtbaar is in het scherm</p>
                </div>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>

    <script>
        function onScanSuccess(decodedText, decodedResult) {
            // Stop scanning
            html5QrCode.clear();
            
            // Submit the form
            document.getElementById('qr_code').value = decodedText;
            document.getElementById('qr-form').submit();
        }

        function onScanFailure(error) {
            // Handle scan failure
            console.warn(`QR code scanning failed: ${error}`);
        }

        let html5QrCode = new Html5Qrcode("reader");
        let config = { fps: 10, qrbox: { width: 250, height: 250 } };

        html5QrCode.start(
            { facingMode: "environment" },
            config,
            onScanSuccess,
            onScanFailure
        ).catch((err) => {
            console.error(`Failed to start QR scanner: ${err}`);
        });

        function submitManualQR() {
            const manualQR = document.getElementById('manual-qr').value.trim();
            if (manualQR) {
                document.getElementById('qr_code').value = manualQR;
                document.getElementById('qr-form').submit();
            }
        }
    </script>
</body>
</html>
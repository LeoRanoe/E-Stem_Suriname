<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';
require_once '../include/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['error'] = "U moet ingelogd zijn om te kunnen stemmen.";
    header("Location: ../login.php");
    exit;
}

// Handle QR code scanning
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_FILES['qr_image'])) {
            $file = $_FILES['qr_image'];
            
            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Er is een fout opgetreden bij het uploaden van de QR code.');
            }
            
            // Create temporary URL for the uploaded file
            $tempUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/temp/' . basename($file['tmp_name']);
            move_uploaded_file($file['tmp_name'], '../temp/' . basename($file['tmp_name']));
            
            // Call QR code reading API
            $apiUrl = 'https://api.qrserver.com/v1/read-qr-code/?fileurl=' . urlencode($tempUrl);
            $response = file_get_contents($apiUrl);
            $qrData = json_decode($response, true);
            
            if (!$qrData || empty($qrData[0]['symbol'][0]['data'])) {
                throw new Exception('Kon de QR code niet lezen. Probeer opnieuw.');
            }
            
            $qrCode = $qrData[0]['symbol'][0]['data'];
            
            // Verify QR code in database
            $stmt = $pdo->prepare("
                SELECT q.*, e.ElectionName, u.UserID 
                FROM qrcodes q
                JOIN elections e ON q.ElectionID = e.ElectionID
                JOIN users u ON q.UserID = u.UserID
                WHERE q.QRCode = ? AND q.Status = 'active'
            ");
            $stmt->execute([$qrCode]);
            $qrInfo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$qrInfo) {
                throw new Exception('Ongeldige of reeds gebruikte QR code.');
            }
            
            // Check if user has already voted
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM votes 
                WHERE UserID = ? AND ElectionID = ?
            ");
            $stmt->execute([$qrInfo['UserID'], $qrInfo['ElectionID']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('U heeft al gestemd voor deze verkiezing.');
            }
            
            // Mark QR code as used
            $stmt = $pdo->prepare("
                UPDATE qrcodes 
                SET Status = 'used', UsedAt = NOW() 
                WHERE QRCodeID = ?
            ");
            $stmt->execute([$qrInfo['QRCodeID']]);
            
            // Store in session that user can now vote
            $_SESSION['can_vote'] = true;
            $_SESSION['election_id'] = $qrInfo['ElectionID'];
            $_SESSION['success'] = "QR code gevalideerd. U kunt nu stemmen.";
            
            // Clean up temporary file
            unlink('../temp/' . basename($file['tmp_name']));
            
            header("Location: ../vote.php");
            exit;
            
        } else {
            throw new Exception('Geen QR code geüpload.');
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: scan_qr.php");
        exit;
    }
}

// Get current election
$stmt = $pdo->query("
    SELECT ElectionID, ElectionName 
    FROM elections 
    WHERE Status = 'active' 
    ORDER BY CreatedAt DESC 
    LIMIT 1
");
$currentElection = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Scannen - E-Stem Suriname</title>
    <link href="../css/tailwind.css" rel="stylesheet">
    <link href="../css/custom.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js" crossorigin="anonymous"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    QR Code Scannen
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    <?php if ($currentElection): ?>
                        Voor: <?php echo htmlspecialchars($currentElection['ElectionName']); ?>
                    <?php else: ?>
                        Geen actieve verkiezing
                    <?php endif; ?>
                </p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if ($currentElection): ?>
                <form class="mt-8 space-y-6" method="POST" enctype="multipart/form-data">
                    <div class="rounded-md shadow-sm -space-y-px">
                        <div class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 rounded-lg">
                            <div class="mb-4">
                                <i class="fas fa-qrcode text-4xl text-gray-400"></i>
                            </div>
                            <div class="space-y-1 text-center">
                                <div class="text-sm text-gray-600">
                                    <label for="qr_image" class="relative cursor-pointer bg-white rounded-md font-medium text-suriname-green hover:text-suriname-dark-green">
                                        <span>Upload een QR code</span>
                                        <input id="qr_image" name="qr_image" type="file" accept="image/*" class="sr-only" required>
                                    </label>
                                </div>
                                <p class="text-xs text-gray-500">
                                    PNG, JPG, GIF tot 10MB
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-suriname-green hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-qrcode"></i>
                            </span>
                            QR Code Scannen
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center text-gray-600">
                    Er is momenteel geen actieve verkiezing.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Preview uploaded image
        document.getElementById('qr_image').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('img');
                    preview.src = e.target.result;
                    preview.classList.add('mt-4', 'mx-auto', 'max-w-xs');
                    
                    const existingPreview = document.querySelector('.qr-preview');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    preview.classList.add('qr-preview');
                    document.querySelector('form').appendChild(preview);
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html> 
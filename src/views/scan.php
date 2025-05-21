<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';
require_once '../include/config.php';
require_once '../controller/QrCodeController.php';
use App\Controller\QrCodeController;

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get current user's data
$currentUser = getCurrentUser();

// Initialize QR Code Controller
$qrController = new QrCodeController($pdo);

// Handle QR code verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_token'])) {
    $qrToken = filter_input(INPUT_POST, 'qr_token', FILTER_SANITIZE_STRING);
    
    try {
        $qrCode = $qrController->validateQrCode($qrToken);
        
        if ($qrCode) {
            $hasVoted = $qrController->hasUserVoted($currentUser['UserID'], $qrCode['ElectionID']);
            
            if ($hasVoted) {
                $_SESSION['error'] = "You have already voted in this election.";
            } else {
                $qrController->markQrCodeAsUsed($qrCode['QRCodeID']);
                $_SESSION['election_id'] = $qrCode['ElectionID'];
                header("Location: vote.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid or expired QR code.";
        }
    } catch (Exception $e) {
        error_log("QR code verification error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while verifying the QR code.";
    }
    
    header("Location: scan.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Stem Suriname - Scan QR Code</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Add QR Scanner library -->
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>
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
                    animation: {
                        'fade-in-up': 'fadeInUp 0.5s ease-out',
                        'slide-in': 'slideIn 0.5s ease-out',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(-20px)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' },
                        },
                    },
                },
            },
        }
    </script>
    <style>
        #qr-reader {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        #qr-reader__dashboard {
            padding: 1rem;
        }
        #qr-reader__scan_region {
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        #qr-reader__scan_region > img {
            width: 100%;
            height: auto;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-50 via-green-50 to-emerald-100">
    <?php include '../include/nav.php'; ?>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-r from-suriname-green to-suriname-dark-green text-white py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-3xl md:text-4xl font-bold mb-4 animate-fade-in-up">
                    Scan Your QR Code
                </h1>
                <p class="text-lg mb-6 text-emerald-50 animate-fade-in-up">
                    Scan your QR code or enter it manually to proceed with voting
                </p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="max-w-2xl mx-auto mb-8 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative animate-fade-in-up">
                    <span class="block sm:inline"><?= htmlspecialchars($_SESSION['error']) ?></span>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="max-w-2xl mx-auto mb-8 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative animate-fade-in-up">
                    <span class="block sm:inline"><?= htmlspecialchars($_SESSION['success']) ?></span>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- QR Scanner -->
                            <div>
                                <h2 class="text-xl font-semibold mb-4 text-suriname-green">
                                    <i class="fas fa-qrcode mr-2"></i>Scan QR Code
                                </h2>
                                <div id="qr-reader"></div>
                                <div id="qr-reader-results"></div>
                                <div class="mt-4">
                                    <button id="startButton" class="bg-suriname-green text-white py-2 px-4 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                                        Start Camera
                                    </button>
                                    <button id="stopButton" class="bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 transition-colors duration-200 ml-2" style="display: none;">
                                        Stop Camera
                                    </button>
                                </div>
                                <p class="text-gray-600 text-sm mt-4">
                                    Position your QR code within the scanner frame to scan.
                                </p>
                            </div>

                            <!-- Manual Entry -->
                            <div>
                                <h2 class="text-xl font-semibold mb-4 text-suriname-green">
                                    <i class="fas fa-keyboard mr-2"></i>Manual Entry
                                </h2>
                                <form method="POST" action="scan.php" class="space-y-4">
                                    <div>
                                        <label for="qr_token" class="block text-sm font-medium text-gray-700 mb-1">
                                            Enter QR Code
                                        </label>
                                        <input type="text" 
                                               name="qr_token" 
                                               id="qr_token" 
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-suriname-green focus:border-suriname-green"
                                               placeholder="Enter your QR code here"
                                               required>
                                    </div>
                                    <button type="submit" 
                                            class="w-full bg-suriname-green text-white py-2 px-4 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                                        Submit QR Code
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="mt-8 border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold mb-4 text-suriname-green">
                                <i class="fas fa-info-circle mr-2"></i>Instructions
                            </h3>
                            <ol class="list-decimal list-inside space-y-2 text-gray-600">
                                <li>Make sure you have received your QR code from the election officials.</li>
                                <li>You can either scan the QR code using your camera or enter it manually.</li>
                                <li>If scanning, click "Start Camera" and allow camera access when prompted.</li>
                                <li>Position your QR code within the scanner frame.</li>
                                <li>If entering manually, type the code carefully to avoid errors.</li>
                                <li>Once verified, you will be redirected to the voting page.</li>
                                <li>If you encounter any issues, please contact the election officials.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include '../include/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let scanner = null;
        const startButton = document.getElementById('startButton');
        const stopButton = document.getElementById('stopButton');
        const qrReader = document.getElementById('qr-reader');
        const qrReaderResults = document.getElementById('qr-reader-results');

        startButton.addEventListener('click', function() {
            // Create new scanner instance
            scanner = new Instascan.Scanner({ 
                video: document.createElement('video'),
                mirror: false,
                scanPeriod: 5
            });

            // Add scan listener
            scanner.addListener('scan', function(content) {
                console.log('QR Code scanned:', content);
                // Stop scanner
                scanner.stop();
                startButton.style.display = 'inline-block';
                stopButton.style.display = 'none';
                
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'scan.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'qr_token';
                input.value = content;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            });

            // Start scanner
            Instascan.Camera.getCameras().then(function(cameras) {
                if (cameras.length > 0) {
                    // Use the first available camera
                    scanner.start(cameras[0]);
                    startButton.style.display = 'none';
                    stopButton.style.display = 'inline-block';
                } else {
                    console.error('No cameras found.');
                    alert('No cameras found. Please use manual entry instead.');
                }
            }).catch(function(e) {
                console.error('Error accessing camera:', e);
                alert('Error accessing camera. Please use manual entry instead.');
            });

            // Add video element to the page
            qrReader.innerHTML = '';
            qrReader.appendChild(scanner.video);
        });

        stopButton.addEventListener('click', function() {
            if (scanner) {
                scanner.stop();
                startButton.style.display = 'inline-block';
                stopButton.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html> 
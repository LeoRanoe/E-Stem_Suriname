<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and database connection
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';

// Check if already logged in
if (isset($_SESSION['voter_id'])) {
    // Redirect to voting page
    header("Location: " . BASE_URL . "/pages/voting/index.php");
    exit();
}

// Initialize error and success messages
$error_msg = "";
$success_msg = "";

// Check for error or success messages in session
if (isset($_SESSION['login_error'])) {
    $error_msg = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

if (isset($_SESSION['login_success'])) {
    $success_msg = $_SESSION['login_success'];
    unset($_SESSION['login_success']);
}

// Fetch active elections
try {
    $election_stmt = $pdo->query("SELECT * FROM elections WHERE Status = 'active' ORDER BY ElectionDate DESC");
    $active_elections = $election_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $active_elections = [];
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiezer Login - E-Stem Suriname</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
    <!-- QR Code Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .login-container {
            background: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .form-input:focus {
            border-color: #007749;
            box-shadow: 0 0 0 3px rgba(0, 119, 73, 0.2);
        }
        .login-btn {
            background: linear-gradient(135deg, #007749 0%, #006241 100%);
            transition: all 0.3s ease;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 119, 73, 0.3);
        }
        .qr-container {
            border: 2px solid #007749;
            border-radius: 0.5rem;
            overflow: hidden;
            position: relative;
        }
        .qr-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.03);
            pointer-events: none;
            z-index: 10;
        }
        .qr-scanner-border {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 2px solid #007749;
            border-radius: 10px;
            box-shadow: 0 0 0 4000px rgba(0, 0, 0, 0.3);
            z-index: 20;
            pointer-events: none;
            animation: pulse 2s infinite;
        }
        .qr-scanner-line {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: #007749;
            animation: scan 2s infinite;
            z-index: 30;
            pointer-events: none;
        }
        @keyframes scan {
            0% { top: 0; }
            50% { top: 100%; }
            100% { top: 0; }
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(0, 119, 73, 0.4), 0 0 0 4000px rgba(0, 0, 0, 0.3); }
            70% { box-shadow: 0 0 0 10px rgba(0, 119, 73, 0), 0 0 0 4000px rgba(0, 0, 0, 0.3); }
            100% { box-shadow: 0 0 0 0 rgba(0, 119, 73, 0), 0 0 0 4000px rgba(0, 0, 0, 0.3); }
        }
        .method-title {
            color: #007749;
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        .method-description {
            color: #4b5563;
            margin-bottom: 1rem;
        }
        .info-panel {
            background: linear-gradient(135deg, #007749 0%, #006241 100%);
        }
        .input-group {
            position: relative;
        }
        .input-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: #007749;
        }
        .input-field {
            padding-left: 2.5rem !important;
            transition: all 0.3s ease;
        }
        .input-field:focus {
            transform: translateY(-2px);
        }
        #qr-reader {
            width: 100%;
            max-width: 100%;
            overflow: hidden;
            position: relative;
            min-height: 250px;
        }
        #qr-reader img {
            width: 100%;
            height: auto;
        }
        #qr-reader video {
            width: 100%;
            height: auto;
            background: #f0f0f0;
        }
        #qr-reader__dashboard {
            padding: 5px !important;
        }
        #qr-reader__status {
            display: none !important;
        }
        #qr-reader__dashboard_section_csr button {
            background: #007749 !important;
            color: white !important;
            border-radius: 4px !important;
            padding: 8px 15px !important;
            font-family: 'Poppins', sans-serif !important;
            border: none !important;
        }
        #qr-reader__dashboard_section_fsr input {
            background: white !important;
            color: #333 !important;
            border: 1px solid #007749 !important;
            border-radius: 4px !important;
            padding: 8px 15px !important;
            font-family: 'Poppins', sans-serif !important;
        }
        #qr-reader__dashboard_section_fsr button {
            background: #007749 !important;
            color: white !important;
            border-radius: 4px !important;
            padding: 8px 15px !important;
            font-family: 'Poppins', sans-serif !important;
            border: none !important;
        }
        #qr-reader__dashboard_section_swaplink {
            color: #007749 !important;
            font-family: 'Poppins', sans-serif !important;
        }
        .qr-btn {
            background: #007749 !important;
            color: white !important;
            border-radius: 4px !important;
            padding: 8px 15px !important;
            font-family: 'Poppins', sans-serif !important;
            border: none !important;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-gradient-to-br from-emerald-50 via-green-50 to-emerald-100">
    <?php include '../include/nav.php'; ?>
    
    <div class="flex-grow flex items-center justify-center p-4">
        <div class="w-full max-w-6xl">
            <div class="flex flex-col md:flex-row login-container rounded-xl overflow-hidden">
                <!-- Left Column: Login Form -->
                <div class="w-full md:w-3/5 p-8 md:p-12">
                    <div class="mb-8 text-center">
                        <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="E-Stem Suriname Logo" class="h-16 mx-auto mb-4">
                        <h1 class="text-2xl font-bold text-gray-800">Welkom terug</h1>
                        <p class="text-gray-600 mt-2">Log in om uw stem uit te brengen</p>
                    </div>
                    
                    <?php if (!empty($error_msg)): ?>
                    <div class="bg-suriname-red bg-opacity-10 text-suriname-red px-4 py-3 rounded-lg mb-6">
                        <p><?= htmlspecialchars($error_msg) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_msg)): ?>
                    <div class="bg-suriname-green bg-opacity-10 text-suriname-green px-4 py-3 rounded-lg mb-6">
                        <p><?= htmlspecialchars($success_msg) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- QR Code Scanner (Default) -->
                        <div class="qr-login-section order-1 md:order-1">
                            <h2 class="method-title flex items-center">
                                <i class="fas fa-qrcode text-suriname-green mr-2"></i>
                                QR Code Scannen
                            </h2>
                            <p class="method-description">Plaats uw QR-code voor de camera om snel in te loggen</p>
                            <div class="qr-container mb-4 relative" style="height: 300px;">
                                <div id="qr-reader" class="w-full"></div>
                                <div class="qr-overlay"></div>
                                <div class="qr-scanner-border">
                                    <div class="qr-scanner-line"></div>
                                </div>
                                <div id="qr-status-overlay" class="absolute bottom-0 left-0 right-0 bg-suriname-green bg-opacity-80 text-white p-2 text-center text-sm rounded-b-lg">
                                    Scanner wordt geladen...
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button id="restart-scanner" class="qr-btn flex items-center justify-center">
                                    <i class="fas fa-sync-alt mr-2"></i> Scanner herstarten
                                </button>
                                <button id="toggle-camera" class="qr-btn flex items-center justify-center bg-gray-600">
                                    <i class="fas fa-camera-rotate mr-2"></i> Camera wisselen
                                </button>
                            </div>
                        </div>
                        
                        <!-- Manual Login Form -->
                        <div class="manual-login-section order-2 md:order-2">
                            <h2 class="method-title flex items-center">
                                <i class="fas fa-keyboard text-suriname-green mr-2"></i>
                                Handmatig Inloggen
                            </h2>
                            <p class="method-description">Gebruik uw Voucher ID en wachtwoord om in te loggen</p>
                            <form id="login-form" action="<?= BASE_URL ?>/src/api/verify_voter.php" method="post" class="space-y-4">
                                <div class="input-group">
                                    <label for="voucher_id" class="block text-sm font-medium text-gray-700 mb-1">Voucher ID</label>
                                    <div class="relative">
                                        <div class="input-icon">
                                            <i class="fas fa-id-card text-suriname-green"></i>
                                        </div>
                                        <input type="text" id="voucher_id" name="voucher_id" class="input-field w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-suriname-green focus:border-transparent" placeholder="Voer uw voucher ID in" required>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Wachtwoord</label>
                                    <div class="relative">
                                        <div class="input-icon">
                                            <i class="fas fa-lock text-suriname-green"></i>
                                        </div>
                                        <input type="password" id="password" name="password" class="input-field w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-suriname-green focus:border-transparent" placeholder="Voer uw wachtwoord in" required>
                                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-suriname-green focus:outline-none">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <button type="submit" class="login-btn w-full text-white py-3 px-4 rounded-lg focus:outline-none flex items-center justify-center">
                                        <i class="fas fa-sign-in-alt mr-2"></i> Inloggen
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="mt-8 text-center text-sm text-gray-600">
                        <p>Heeft u problemen met inloggen? Neem contact op met de verkiezingscommissie.</p>
                    </div>
                </div>
                
                <!-- Right Column: Information -->
                <div class="w-full md:w-2/5 bg-suriname-green p-8 md:p-12 text-white flex flex-col justify-between">
                    <div>
                        <h2 class="text-3xl font-bold mb-6">Welkom bij E-Stem Suriname</h2>
                        <p class="mb-4">Het digitale stemsysteem voor de verkiezingen in Suriname.</p>
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <svg class="h-6 w-6 mr-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Veilig en betrouwbaar stemmen</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-6 w-6 mr-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Eenvoudig te gebruiken interface</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="h-6 w-6 mr-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Transparant verkiezingsproces</span>
                            </li>
                        </ul>
                        
                        <?php if (!empty($active_elections)): ?>
                        <div class="mt-8">
                            <h3 class="text-xl font-semibold mb-3">Actieve Verkiezingen</h3>
                            <ul class="space-y-2">
                                <?php foreach ($active_elections as $election): ?>
                                <li class="bg-white bg-opacity-10 p-3 rounded">
                                    <div class="font-medium"><?= htmlspecialchars($election['ElectionName']) ?></div>
                                    <div class="text-sm opacity-80">
                                        <i class="fas fa-calendar-alt mr-1"></i> 
                                        <?= date('d F Y', strtotime($election['ElectionDate'])) ?>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-8">
                        <p class="text-sm opacity-80">© <?= date('Y') ?> E-Stem Suriname. Alle rechten voorbehouden.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../include/footer.php'; ?>

    <script>
        // QR Code Scanner
        const qrReader = document.getElementById('qr-reader');
        const qrStatus = document.getElementById('qr-status-overlay');
        let html5QrCode;
        let currentCamera = 'environment'; // Default to back camera
        
        function onScanSuccess(decodedText) {
            qrStatus.textContent = "QR-code succesvol gescand! Bezig met verifiëren...";
            qrStatus.classList.remove('bg-red-600', 'bg-gray-600');
            qrStatus.classList.add('bg-suriname-green');
            
            // Show animation
            qrStatus.classList.add('animate-pulse');
            
            // Stop scanning after a successful scan
            if (html5QrCode) {
                html5QrCode.stop().catch(error => {
                    console.error("Error stopping scanner:", error);
                });
            }
            
            // Send the QR code data to the server for verification
            fetch('<?= BASE_URL ?>/src/api/verify_qr_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'qr_data=' + encodeURIComponent(decodedText)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    qrStatus.textContent = "Verificatie succesvol! U wordt doorgestuurd...";
                    qrStatus.classList.remove('bg-red-600');
                    qrStatus.classList.add('bg-suriname-green');
                    
                    // Add success animation
                    document.querySelector('.qr-container').classList.add('ring-4', 'ring-suriname-green', 'ring-opacity-50');
                    
                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = data.redirect || '<?= BASE_URL ?>/pages/voting/index.php';
                    }, 1000);
                } else {
                    qrStatus.textContent = data.message || "Ongeldige QR-code. Probeer opnieuw.";
                    qrStatus.classList.remove('bg-suriname-green', 'bg-gray-600');
                    qrStatus.classList.add('bg-red-600');
                    
                    // Remove animation
                    qrStatus.classList.remove('animate-pulse');
                    
                    // Restart scanner after error
                    setTimeout(() => {
                        startScanner();
                    }, 2000);
                }
            })
            .catch(error => {
                qrStatus.textContent = "Er is een fout opgetreden. Probeer opnieuw.";
                qrStatus.classList.remove('bg-suriname-green', 'bg-gray-600');
                qrStatus.classList.add('bg-red-600');
                qrStatus.classList.remove('animate-pulse');
                console.error('Error:', error);
                
                // Restart scanner after error
                setTimeout(() => {
                    startScanner();
                }, 2000);
            });
        }

        function onScanFailure(error) {
            // Don't update UI for every scan failure
            console.warn(`QR scan error: ${error}`);
        }

        function startScanner() {
            // Clean up existing instance
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().catch(err => console.log("Error stopping scanner:", err));
            }
            
            // Initialize scanner
            html5QrCode = new Html5Qrcode("qr-reader");
            
            const config = { 
                fps: 10, 
                qrbox: { width: 200, height: 200 },
                aspectRatio: 1.0,
                disableFlip: false
            };
            
            qrStatus.textContent = "Camera wordt gestart...";
            qrStatus.classList.remove('bg-suriname-green', 'bg-red-600');
            qrStatus.classList.add('bg-gray-600');
            
            html5QrCode.start(
                { facingMode: currentCamera }, // Use the selected camera
                config,
                onScanSuccess,
                onScanFailure
            ).then(() => {
                qrStatus.textContent = "Scanner actief. Plaats uw QR-code voor de camera.";
                qrStatus.classList.remove('bg-gray-600');
                qrStatus.classList.add('bg-suriname-green', 'bg-opacity-80');
            }).catch((err) => {
                qrStatus.textContent = "Kon de camera niet starten. Controleer uw camera-instellingen.";
                qrStatus.classList.remove('bg-gray-600', 'bg-suriname-green');
                qrStatus.classList.add('bg-red-600');
                console.error("Camera start error:", err);
            });
        }

        // Initialize QR scanner when page loads
        window.addEventListener('load', () => {
            setTimeout(startScanner, 1000); // Slight delay to ensure DOM is fully loaded
        });
        
        // Restart scanner button
        document.getElementById('restart-scanner').addEventListener('click', function() {
            startScanner();
        });
        
        // Toggle camera button
        document.getElementById('toggle-camera').addEventListener('click', function() {
            currentCamera = currentCamera === 'environment' ? 'user' : 'environment';
            this.classList.add('animate-pulse');
            setTimeout(() => {
                this.classList.remove('animate-pulse');
            }, 500);
            startScanner();
        });

        // Manual login form handling
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const voucherId = document.getElementById('voucher_id').value;
            const password = document.getElementById('password').value;
            
            if (!voucherId || !password) {
                e.preventDefault();
                alert('Vul alstublieft zowel uw Voucher ID als wachtwoord in.');
            }
        });
        
        // Password visibility toggle
        document.getElementById('toggle-password').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html> 
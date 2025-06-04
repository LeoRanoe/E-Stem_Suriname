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
    
    <!-- Include centralized styles -->
    <?php include_once __DIR__ . '/../include/styles.php'; ?>
    
    <!-- Additional CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/suriname-style.css">
    
    <!-- QR Code Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        .login-container {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .login-btn {
            background: linear-gradient(135deg, #007847 0%, #006241 100%);
            transition: all 0.3s ease;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 119, 73, 0.3);
        }
        .qr-container {
            border: 2px solid #007847;
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
            border: 2px solid #007847;
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
            background: #007847;
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
        .input-group {
            position: relative;
        }
        .input-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: #007847;
        }
        .input-field {
            padding-left: 2.5rem !important;
            transition: all 0.3s ease;
            @apply sr-input;
        }
        .input-field:focus {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 font-sans">
    <!-- Subtle pattern background -->
    <div class="fixed inset-0 z-0 pointer-events-none sr-bg-dots opacity-30"></div>
    
    <?php include '../include/nav.php'; ?>
    
    <div class="flex-grow flex items-center justify-center p-4 relative z-10">
        <div class="w-full max-w-6xl">
            <div class="flex flex-col md:flex-row login-container rounded-xl overflow-hidden">
                <!-- Left Column: Login Form -->
                <div class="w-full md:w-3/5 p-8 md:p-12 bg-white">
                    <div class="mb-8 text-center">
                        <img src="<?= BASE_URL ?>/assets/Images/logo.png" alt="E-Stem Suriname Logo" class="h-16 mx-auto mb-4">
                        <h1 class="text-2xl font-bold text-gray-800">Welkom terug</h1>
                        <p class="text-gray-600 mt-2">Log in om uw stem uit te brengen</p>
                    </div>
                    
                    <?php if (!empty($error_msg)): ?>
                        <div class="sr-alert sr-alert-danger mb-6">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?= $error_msg ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_msg)): ?>
                        <div class="sr-alert sr-alert-success mb-6">
                            <i class="fas fa-check-circle mr-2"></i>
                            <?= $success_msg ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Login Tabs -->
                    <div class="mb-6">
                        <div class="flex border-b border-gray-200">
                            <button onclick="showTab('id-login')" id="id-tab" class="py-2 px-4 font-medium text-suriname-green border-b-2 border-suriname-green">
                                <i class="fas fa-id-card mr-2"></i> ID Login
                            </button>
                            <button onclick="showTab('qr-login')" id="qr-tab" class="py-2 px-4 font-medium text-gray-500 hover:text-suriname-green">
                                <i class="fas fa-qrcode mr-2"></i> QR Login
                            </button>
                        </div>
                    </div>
                    
                    <!-- ID Login Form -->
                    <div id="id-login" class="tab-content animate-fade-in">
                        <h2 class="text-suriname-green font-semibold text-lg mb-4">Login met ID-kaart Nummer</h2>
                        <p class="text-gray-600 mb-4">Voer uw ID-kaartnummer en wachtwoord in om toegang te krijgen tot uw stem account.</p>
                        
                        <form action="login_process.php" method="post" class="space-y-4">
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-id-card"></i>
                                </span>
                                <input type="text" name="id_number" class="input-field w-full p-3 border rounded-lg focus:outline-none focus:border-suriname-green" placeholder="ID-kaart Nummer" required>
                            </div>
                            
                            <div class="input-group">
                                <span class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" name="password" class="input-field w-full p-3 border rounded-lg focus:outline-none focus:border-suriname-green" placeholder="Wachtwoord" required>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <label class="flex items-center text-sm text-gray-600">
                                    <input type="checkbox" name="remember" class="mr-2 h-4 w-4 text-suriname-green border-gray-300 rounded focus:ring-suriname-green">
                                    Onthoud mij
                                </label>
                                <a href="reset_password.php" class="text-sm text-suriname-green hover:underline">Wachtwoord vergeten?</a>
                            </div>
                            
                            <button type="submit" name="id_login" class="sr-button sr-button-primary w-full">
                                <i class="fas fa-sign-in-alt mr-2"></i> Inloggen
                            </button>
                        </form>
                        
                        <div class="mt-6 text-center">
                            <p class="text-sm text-gray-600">Heeft u nog geen account?</p>
                            <a href="register.php" class="text-suriname-green hover:underline font-medium">Registreer nu</a>
                        </div>
                    </div>
                    
                    <!-- QR Login Section -->
                    <div id="qr-login" class="tab-content hidden animate-fade-in">
                        <h2 class="text-suriname-green font-semibold text-lg mb-4">Login met QR Code</h2>
                        <p class="text-gray-600 mb-4">Scan de QR code die u hebt ontvangen om direct in te loggen op uw stem account.</p>
                        
                        <div class="qr-container rounded-lg mb-4">
                            <div id="qr-reader" class="w-full"></div>
                            <div class="qr-overlay"></div>
                            <div class="qr-scanner-border"></div>
                            <div class="qr-scanner-line"></div>
                        </div>
                        
                        <div class="text-center">
                            <button onclick="startScanner()" class="sr-button sr-button-primary">
                                <i class="fas fa-camera mr-2"></i> Start Scanner
                            </button>
                        </div>
                        
                        <div class="mt-6 text-sm text-gray-600 text-center">
                            <p>Scan de QR code die u per e-mail of SMS hebt ontvangen.</p>
                            <p class="mt-2">Werkt de scanner niet? <a href="#" class="text-suriname-green hover:underline">Klik hier voor hulp</a></p>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Info Panel -->
                <div class="w-full md:w-2/5 bg-suriname-green p-8 md:p-12 text-white relative overflow-hidden">
                    <!-- Decorative background pattern -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute inset-0 sr-pattern-dots"></div>
                    </div>
                    
                    <div class="relative z-10">
                        <h2 class="text-2xl font-bold mb-6">E-Stem Suriname</h2>
                        
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold mb-3 flex items-center">
                                <i class="fas fa-shield-alt mr-3 text-suriname-yellow"></i>
                                Veilig Stemmen
                            </h3>
                            <p class="text-gray-100 opacity-90">Uw stem is beveiligd met geavanceerde encryptie. Niemand kan zien op wie u hebt gestemd.</p>
                        </div>
                        
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold mb-3 flex items-center">
                                <i class="fas fa-check-circle mr-3 text-suriname-yellow"></i>
                                Gemakkelijk Proces
                            </h3>
                            <p class="text-gray-100 opacity-90">Het stemproces is eenvoudig en intuïtief. U kunt binnen enkele minuten uw stem uitbrengen.</p>
                        </div>
                        
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold mb-3 flex items-center">
                                <i class="fas fa-clock mr-3 text-suriname-yellow"></i>
                                Stem Overal
                            </h3>
                            <p class="text-gray-100 opacity-90">Stem vanaf elke locatie met een internetverbinding, zonder in de rij te hoeven staan.</p>
                        </div>
                        
                        <?php if (!empty($active_elections)): ?>
                            <div class="mt-12 p-4 bg-white bg-opacity-10 rounded-lg">
                                <h3 class="font-semibold mb-2">Actieve Verkiezingen</h3>
                                <ul class="space-y-2">
                                    <?php foreach(array_slice($active_elections, 0, 2) as $election): ?>
                                        <li class="flex items-center">
                                            <i class="fas fa-vote-yea mr-2"></i>
                                            <?= htmlspecialchars($election['ElectionName']) ?> - <?= date('d/m/Y', strtotime($election['ElectionDate'])) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php if (count($active_elections) > 2): ?>
                                    <p class="text-sm mt-2 text-right">
                                        <a href="#" class="text-suriname-yellow hover:underline">Bekijk alle <?= count($active_elections) ?> verkiezingen</a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../include/footer.php'; ?>
    
    <script>
        // Tab switching functionality
        function showTab(tabId) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.add('hidden'));
            
            const tabButtons = document.querySelectorAll('[id$="-tab"]');
            tabButtons.forEach(button => {
                button.classList.remove('text-suriname-green', 'border-b-2', 'border-suriname-green');
                button.classList.add('text-gray-500');
            });
            
            document.getElementById(tabId).classList.remove('hidden');
            
            const activeButton = document.getElementById(tabId.replace('login', 'tab'));
            activeButton.classList.remove('text-gray-500');
            activeButton.classList.add('text-suriname-green', 'border-b-2', 'border-suriname-green');
        }
        
        // QR Code Scanner functionality
        let html5QrCode;
        
        function startScanner() {
            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    console.log('Scanner stopped');
                }).catch(err => {
                    console.error('Error stopping scanner:', err);
                });
            }
            
            html5QrCode = new Html5Qrcode("qr-reader");
            const config = { fps: 10, qrbox: { width: 200, height: 200 } };
            
            html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess, onScanFailure)
                .catch(err => {
                    console.error('Error starting scanner:', err);
                });
        }
        
        function onScanSuccess(decodedText, decodedResult) {
            console.log(`QR Code detected: ${decodedText}`);
            
            // Stop scanning
            if (html5QrCode.isScanning) {
                html5QrCode.stop().then(() => {
                    console.log('Scanner stopped after successful scan');
                    
                    // Process the QR code data
                    processQrCode(decodedText);
                });
            }
        }
        
        function onScanFailure(error) {
            // console.warn(`QR scan error: ${error}`);
        }
        
        function processQrCode(qrData) {
            // Create a form and submit the QR data
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'qr_login_process.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'qr_data';
            input.value = qrData;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html> 
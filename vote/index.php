<?php
require_once '../include/config.php';
require_once '../include/db_connect.php';
require_once '../src/controllers/QrCodeController.php';
require_once '../src/models/Vote.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize controllers
$qrController = new QrCodeController();
$voteModel = new Vote($pdo);

// Check if already logged in
if (isset($_SESSION['voter_id'])) {
    // Redirect to ballot page
    header("Location: " . BASE_URL . "/vote/ballot.php");
    exit();
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voucher_id = $_POST['voucher_id'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($voucher_id) || empty($password)) {
        $error = 'Please enter both voucher ID and password.';
    } else {
        // Verify voucher
        $voter = $qrController->verifyVoucher($voucher_id, $password);
        
        if ($voter) {
            // Set session variables
            $_SESSION['voter_id'] = $voter['id'];
            $_SESSION['voter_name'] = $voter['first_name'] . ' ' . $voter['last_name'];
            $_SESSION['voucher_id'] = $voter['id'];
            
            // Log the login attempt
            logLoginAttempt($voter['id'], 'success', $_POST['login_method'] ?? 'manual');
            
            // Redirect to ballot page
            header("Location: " . BASE_URL . "/vote/ballot.php");
            exit();
        } else {
            $error = 'Invalid voucher ID or password, or voucher has already been used.';
            
            // Log the failed login attempt
            logLoginAttempt(0, 'failed', $_POST['login_method'] ?? 'manual');
        }
    }
}

/**
 * Log login attempt
 * 
 * @param int $voter_id Voter ID
 * @param string $status Status (success/failed)
 * @param string $attempt_type Attempt type (qr_scan/manual)
 */
function logLoginAttempt($voter_id, $status, $attempt_type) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO voter_logins (voter_id, login_time, ip_address, status, attempt_type)
            VALUES (?, NOW(), ?, ?, ?)
        ");
        $stmt->execute([$voter_id, $_SERVER['REMOTE_ADDR'], $status, $attempt_type]);
    } catch (PDOException $e) {
        error_log("Error logging login attempt: " . $e->getMessage());
    }
}

// Get active elections
$activeElections = $voteModel->getActiveElections();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Login - E-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        body {
            background-color: #f3f4f6;
            font-family: 'Arial', sans-serif;
        }
        
        .login-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .suriname-flag {
            background: linear-gradient(to bottom, 
                #007749 33.33%, 
                #ffffff 33.33%, 
                #ffffff 66.66%, 
                #C8102E 66.66%);
        }
        
        #qr-reader {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }
        
        #qr-reader__scan_region {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .tab-active {
            background-color: #007749;
            color: white;
        }
        
        .tab-inactive {
            background-color: #e5e7eb;
            color: #4b5563;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <div class="suriname-flag h-8 w-12 mr-3"></div>
                <h1 class="text-2xl font-bold text-gray-800">E-Stem Suriname</h1>
            </div>
            <div>
                <select id="language-selector" class="bg-gray-100 border border-gray-300 text-gray-700 py-1 px-2 rounded text-sm">
                    <option value="nl">Nederlands</option>
                    <option value="en">English</option>
                </select>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="login-container bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Tabs -->
            <div class="flex">
                <button id="manual-tab" class="flex-1 py-3 px-4 text-center font-medium tab-active">
                    <i class="fas fa-keyboard mr-2"></i> Manual Login
                </button>
                <button id="qr-tab" class="flex-1 py-3 px-4 text-center font-medium tab-inactive">
                    <i class="fas fa-qrcode mr-2"></i> Scan QR Code
                </button>
            </div>
            
            <!-- Tab Content -->
            <div class="p-6">
                <!-- Manual Login Form -->
                <div id="manual-login" class="tab-content">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Login with Voucher ID</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                            <p><?= htmlspecialchars($error) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($activeElections)): ?>
                        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" role="alert">
                            <p>There are no active elections at this time. Please check back later.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="login_method" value="manual">
                            
                            <div class="mb-4">
                                <label for="voucher_id" class="block text-gray-700 text-sm font-bold mb-2">Voucher ID</label>
                                <input type="text" id="voucher_id" name="voucher_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            
                            <div class="mb-6">
                                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                                <input type="password" id="password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <button type="submit" class="bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-200">
                                    Login to Vote
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                
                <!-- QR Scanner -->
                <div id="qr-scanner" class="tab-content hidden">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Scan QR Code</h2>
                    
                    <?php if (empty($activeElections)): ?>
                        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" role="alert">
                            <p>There are no active elections at this time. Please check back later.</p>
                        </div>
                    <?php else: ?>
                        <div id="qr-reader"></div>
                        
                        <div id="qr-result" class="mt-4 hidden">
                            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                                <p>QR code detected! Please enter your password to continue.</p>
                            </div>
                            
                            <form method="POST" action="" id="qr-form">
                                <input type="hidden" name="login_method" value="qr_scan">
                                <input type="hidden" id="qr-voucher-id" name="voucher_id" value="">
                                
                                <div class="mb-6">
                                    <label for="qr-password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                                    <input type="password" id="qr-password" name="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <button type="submit" class="bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-200">
                                        Login to Vote
                                    </button>
                                    <button type="button" id="cancel-qr-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-200">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Instructions -->
            <div class="bg-gray-50 p-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Instructions</h3>
                <ul class="list-disc list-inside text-gray-600 space-y-1">
                    <li>Enter your voucher ID and password provided to you.</li>
                    <li>Alternatively, scan the QR code on your voter card.</li>
                    <li>Each voucher can only be used once.</li>
                    <li>If you encounter any issues, please contact the election officials.</li>
                </ul>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>&copy; <?= date('Y') ?> E-Stem Suriname. All rights reserved.</p>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="hover:text-gray-300 transition-colors duration-200">Privacy Policy</a>
                    <a href="#" class="hover:text-gray-300 transition-colors duration-200">Terms of Service</a>
                    <a href="#" class="hover:text-gray-300 transition-colors duration-200">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching
            const manualTab = document.getElementById('manual-tab');
            const qrTab = document.getElementById('qr-tab');
            const manualLogin = document.getElementById('manual-login');
            const qrScanner = document.getElementById('qr-scanner');
            
            manualTab.addEventListener('click', function() {
                manualTab.classList.add('tab-active');
                manualTab.classList.remove('tab-inactive');
                qrTab.classList.add('tab-inactive');
                qrTab.classList.remove('tab-active');
                
                manualLogin.classList.remove('hidden');
                qrScanner.classList.add('hidden');
                
                // Stop QR scanner if it's running
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop();
                }
            });
            
            qrTab.addEventListener('click', function() {
                qrTab.classList.add('tab-active');
                qrTab.classList.remove('tab-inactive');
                manualTab.classList.add('tab-inactive');
                manualTab.classList.remove('tab-active');
                
                qrScanner.classList.remove('hidden');
                manualLogin.classList.add('hidden');
                
                // Start QR scanner
                startQrScanner();
            });
            
            // QR code scanner
            let html5QrCode;
            
            function startQrScanner() {
                const qrReader = document.getElementById('qr-reader');
                const qrResult = document.getElementById('qr-result');
                
                if (!html5QrCode) {
                    html5QrCode = new Html5Qrcode("qr-reader");
                }
                
                const config = { fps: 10, qrbox: { width: 250, height: 250 } };
                
                html5QrCode.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText) => {
                        // QR code detected
                        console.log(`QR Code detected: ${decodedText}`);
                        
                        // Stop scanning
                        html5QrCode.stop();
                        
                        // Show result form
                        qrReader.classList.add('hidden');
                        qrResult.classList.remove('hidden');
                        
                        // Set voucher ID in form
                        document.getElementById('qr-voucher-id').value = decodedText;
                        
                        // Focus on password field
                        document.getElementById('qr-password').focus();
                    },
                    (errorMessage) => {
                        // Error or no QR code detected yet
                        console.log(`QR Code scanning error: ${errorMessage}`);
                    }
                ).catch((err) => {
                    console.error(`Error starting QR Code scanner: ${err}`);
                });
            }
            
            // Cancel QR login
            document.getElementById('cancel-qr-btn').addEventListener('click', function() {
                const qrReader = document.getElementById('qr-reader');
                const qrResult = document.getElementById('qr-result');
                
                // Show scanner again
                qrResult.classList.add('hidden');
                qrReader.classList.remove('hidden');
                
                // Reset form
                document.getElementById('qr-form').reset();
                
                // Start scanning again
                startQrScanner();
            });
            
            // Language selector (placeholder functionality)
            document.getElementById('language-selector').addEventListener('change', function() {
                const language = this.value;
                console.log(`Language changed to: ${language}`);
                // In a real implementation, this would change the UI language
            });
        });
    </script>
</body>
</html>

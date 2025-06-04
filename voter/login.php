<?php
require_once __DIR__ . '/../include/db_connect.php';
require_once __DIR__ . '/../include/VoterAuth.php';

session_start();

// Initialize VoterAuth
$voterAuth = new VoterAuth($db);

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voterCode = $_POST['voter_code'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        $result = $voterAuth->verifyVoter($voterCode, $password);
        if ($result) {
            // Create session
            $sessionId = $voterAuth->createSession($result['voter_id']);
            $_SESSION['voter_session'] = $sessionId;
            $_SESSION['voter_name'] = $result['first_name'] . ' ' . $result['last_name'];
            
            // Redirect to voting page
            header('Location: vote.php');
            exit;
        } else {
            $error = "Invalid voter code or password";
        }
    } catch (Exception $e) {
        $error = "An error occurred. Please try again.";
        error_log("Login error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Login - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Voter Login</h1>
                <p class="text-gray-600">Scan your QR code or enter your credentials</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- QR Scanner -->
            <div class="mb-6">
                <div id="qr-reader" class="w-full"></div>
                <div id="qr-reader-results" class="mt-2 text-center text-sm text-gray-600"></div>
            </div>

            <!-- Login Form -->
            <form method="POST" class="space-y-4">
                <div>
                    <label for="voter_code" class="block text-sm font-medium text-gray-700">Voter Code</label>
                    <input type="text" id="voter_code" name="voter_code" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50"
                           placeholder="Enter your voter code">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="password" name="password" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50"
                           placeholder="Enter your password">
                </div>

                <button type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-suriname-green hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green">
                    Login
                </button>
            </form>
        </div>
    </div>

    <script>
        // QR Scanner initialization
        const html5QrCode = new Html5Qrcode("qr-reader");
        const qrCodeSuccessCallback = (decodedText, decodedResult) => {
            // Stop scanning
            html5QrCode.stop();
            
            // Fill the voter code field
            document.getElementById('voter_code').value = decodedText;
            
            // Show success message
            const resultsDiv = document.getElementById('qr-reader-results');
            resultsDiv.innerHTML = '<span class="text-green-600">QR Code scanned successfully!</span>';
            
            // Focus on password field
            document.getElementById('password').focus();
        };

        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        
        // Start scanner
        html5QrCode.start(
            { facingMode: "environment" },
            config,
            qrCodeSuccessCallback,
            (errorMessage) => {
                // Handle scan error
                console.error(errorMessage);
            }
        );

        // Debug logging
        const debugLog = (message, type = 'info') => {
            console.log(`[${type.toUpperCase()}] ${message}`);
        };

        // Form submission handling
        document.querySelector('form').addEventListener('submit', (e) => {
            const voterCode = document.getElementById('voter_code').value;
            const password = document.getElementById('password').value;

            if (!voterCode || !password) {
                e.preventDefault();
                debugLog('Please fill in all fields', 'error');
                return;
            }

            debugLog(`Attempting login for voter code: ${voterCode}`, 'info');
        });
    </script>
</body>
</html> 
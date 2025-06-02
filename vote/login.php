<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';
require_once __DIR__ . '/../include/VoterAuth.php';

// Initialize voter authentication
$voterAuth = new VoterAuth($pdo);

$error = '';
$qrCode = $_GET['code'] ?? '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voterCode = $_POST['voter_code'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($voterCode) || empty($password)) {
        $error = 'Voer uw stemcode en wachtwoord in.';
    } else {
        try {
            // Verify voter credentials
            $stmt = $pdo->prepare("SELECT id, password, has_voted, first_name, last_name FROM voters WHERE voter_code = ? AND status = 'active'");
            $stmt->execute([$voterCode]);
            $voter = $stmt->fetch();
            
            if ($voter && password_verify($password, $voter['password'])) {
                if ($voter['has_voted']) {
                    $error = 'U heeft al gestemd. Elke kiezer mag slechts één keer stemmen.';
                } else {
                    // Create voter session
                    $_SESSION['voter_id'] = $voter['id'];
                    $_SESSION['voter_name'] = $voter['first_name'] . ' ' . $voter['last_name'];
                    $_SESSION['voter_code'] = $voterCode;
                    
                    // Log successful login
                    $stmt = $pdo->prepare("
                        INSERT INTO voter_logins (voter_id, status, attempt_type, ip_address)
                        VALUES (?, 'success', 'manual', ?)
                    ");
                    $stmt->execute([$voter['id'], $_SERVER['REMOTE_ADDR']]);
                    
                    // Redirect to ballot page
                    header('Location: ' . BASE_URL . '/vote/ballot.php');
                    exit;
                }
            } else {
                // Log failed login attempt if voter exists
                if ($voter) {
                    $stmt = $pdo->prepare("
                        INSERT INTO voter_logins (voter_id, status, attempt_type, ip_address)
                        VALUES (?, 'failed', 'manual', ?)
                    ");
                    $stmt->execute([$voter['id'], $_SERVER['REMOTE_ADDR']]);
                }
                
                $error = 'Ongeldige stemcode of wachtwoord.';
            }
        } catch (PDOException $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'Er is een fout opgetreden. Probeer het later opnieuw.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stemmen - E-Stem Suriname</title>
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
                        },
                    },
                },
            },
        }
    </script>
    <style>
        .suriname-flag-gradient {
            background: linear-gradient(to bottom, 
                #007749 0%, #007749 20%, 
                #FFFFFF 20%, #FFFFFF 40%, 
                #C8102E 40%, #C8102E 60%, 
                #FFFFFF 60%, #FFFFFF 80%, 
                #007749 80%, #007749 100%);
        }
        
        #qr-reader {
            width: 100%;
            max-width: 300px;
            margin: 0 auto;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-suriname-green text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <span class="text-2xl font-bold">🇸🇷 E-Stem Suriname</span>
            </div>
            <div>
                <span class="text-sm">Onafhankelijke Kiesraad (OKR)</span>
            </div>
        </div>
    </header>

    <main class="flex-grow container mx-auto p-4 md:p-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="suriname-flag-gradient h-3"></div>
            <div class="p-6">
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-2">Welkom bij E-Stem Suriname</h1>
                    <p class="text-gray-600">Log in met uw stemvoucher om te stemmen</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                        <p><?= htmlspecialchars($error) ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label for="voter_code" class="block text-sm font-medium text-gray-700 mb-1">Uw unieke stemcode</label>
                        <input type="text" id="voter_code" name="voter_code" value="<?= htmlspecialchars($qrCode) ?>" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-suriname-green" required>
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Uw wachtwoord</label>
                        <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-suriname-green" required>
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-2 px-4 rounded-md transition duration-200">
                            Inloggen om te stemmen
                        </button>
                    </div>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 text-center">Of scan uw QR-code</h2>
                    
                    <div class="text-center mb-4">
                        <button id="start-scanner-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md transition duration-200">
                            <i class="fas fa-qrcode mr-2"></i> QR-code scannen
                        </button>
                    </div>
                    
                    <div id="qr-reader" class="hidden"></div>
                    
                    <div class="mt-4 text-sm text-gray-600">
                        <p class="mb-2"><strong>Let op:</strong> Zelfs na het scannen van de QR-code moet u nog steeds uw wachtwoord invoeren.</p>
                        <p>Uw stemvoucher is strikt persoonlijk. Misbruik is strafbaar.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white p-4 mt-8">
        <div class="container mx-auto text-center text-sm">
            <p>&copy; <?= date('Y') ?> E-Stem Suriname. Alle rechten voorbehouden.</p>
            <p class="mt-1">Een initiatief van de Onafhankelijke Kiesraad (OKR) 🇸🇷</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startScannerBtn = document.getElementById('start-scanner-btn');
            const qrReader = document.getElementById('qr-reader');
            const voterCodeInput = document.getElementById('voter_code');
            let scanner = null;
            
            startScannerBtn.addEventListener('click', function() {
                if (qrReader.classList.contains('hidden')) {
                    qrReader.classList.remove('hidden');
                    startScannerBtn.textContent = 'Scanner sluiten';
                    startScannerBtn.innerHTML = '<i class="fas fa-times mr-2"></i> Scanner sluiten';
                    
                    // Initialize QR scanner
                    scanner = new Html5Qrcode("qr-reader");
                    const config = { fps: 10, qrbox: { width: 250, height: 250 } };
                    
                    scanner.start(
                        { facingMode: "environment" },
                        config,
                        (decodedText) => {
                            console.log(`QR Code detected: ${decodedText}`);
                            voterCodeInput.value = decodedText;
                            
                            // Stop scanner after successful scan
                            scanner.stop().then(() => {
                                qrReader.classList.add('hidden');
                                startScannerBtn.innerHTML = '<i class="fas fa-qrcode mr-2"></i> QR-code scannen';
                                scanner = null;
                            });
                        },
                        (errorMessage) => {
                            // Handle scan error silently
                            console.log(`QR Code scan error: ${errorMessage}`);
                        }
                    );
                } else {
                    if (scanner) {
                        scanner.stop().then(() => {
                            qrReader.classList.add('hidden');
                            startScannerBtn.innerHTML = '<i class="fas fa-qrcode mr-2"></i> QR-code scannen';
                            scanner = null;
                        });
                    }
                }
            });
        });
    </script>
</body>
</html>

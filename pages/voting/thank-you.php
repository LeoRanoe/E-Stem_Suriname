<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and VoterAuth
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/VoterAuth.php';

// Get voter ID and election ID
$voterId = $_SESSION['voter_id'] ?? null;
$voterName = $_SESSION['voter_name'] ?? '';

// Initialize VoterAuth
$voterAuth = new VoterAuth($pdo);

// Determine if voter has actually voted (by checking the database)
$hasVoted = false;
if ($voterId) {
    $hasVoted = $voterAuth->hasVoted($voterId);
}

// Get message from session if available
$message = isset($_SESSION['message']) ? $_SESSION['message'] : "";

// Determine the correct message
if ($hasVoted) {
    $title = "Bedankt Voor Uw Stem!";
    if (empty($message)) {
        $message = "Uw stem is succesvol uitgebracht! Bedankt voor uw deelname.";
    }
    $shouldLogout = true;
} else {
    $title = "U heeft nog niet gestemd";
    if (empty($message)) {
        $message = "U bent succesvol ingelogd, maar u heeft nog niet gestemd.";
    }
    $shouldLogout = false;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - E-Stem Suriname</title>
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
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .thankyou-container {
            background: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .thankyou-icon {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0px);
            }
        }
        .action-btn {
            background: linear-gradient(135deg, #007749 0%, #006241 100%);
            transition: all 0.3s ease;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 119, 73, 0.3);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-gradient-to-br from-emerald-50 via-green-50 to-emerald-100">
    <?php include '../../include/nav.php'; ?>
    
    <div class="flex-grow flex items-center justify-center p-4">
        <div class="thankyou-container w-full max-w-lg rounded-xl overflow-hidden text-center p-8">
            <div class="mb-6">
                <div class="w-24 h-24 bg-suriname-green bg-opacity-10 rounded-full flex items-center justify-center mx-auto mb-4 thankyou-icon">
                    <?php if ($hasVoted): ?>
                    <i class="fas fa-vote-yea text-5xl text-suriname-green"></i>
                    <?php else: ?>
                    <i class="fas fa-info-circle text-5xl text-suriname-green"></i>
                    <?php endif; ?>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($title) ?></h1>
                <p class="text-gray-600"><?= htmlspecialchars($message) ?></p>
            </div>
            
            <?php if ($hasVoted): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-blue-800 text-sm">
                    <i class="fas fa-info-circle mr-2"></i>
                    U kunt slechts één keer stemmen per verkiezing. Uw stem is al geregistreerd en zal worden meegeteld in de verkiezingsresultaten.
                </p>
            </div>
            
            <div class="mb-6">
                <p class="text-gray-700 font-medium">Wat u nog kunt doen:</p>
                <ul class="text-left text-gray-600 mt-2 space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-suriname-green mt-1 mr-2"></i>
                        <span>Bekijk de verkiezingsinformatie op onze website</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-suriname-green mt-1 mr-2"></i>
                        <span>Volg de resultaten na sluiting van de verkiezing</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-arrow-right text-suriname-green mt-1 mr-2"></i>
                        <span>Moedig anderen aan om te stemmen</span>
                    </li>
                </ul>
            </div>
            
            <a href="<?= BASE_URL ?>" class="action-btn inline-block text-white py-3 px-6 rounded-lg font-medium">
                <i class="fas fa-home mr-2"></i>Terug naar Home
            </a>
            <?php else: ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-yellow-800 text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    U bent ingelogd, maar heeft nog niet gestemd. Wilt u nu stemmen?
                </p>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?= BASE_URL ?>/pages/voting/index.php" class="action-btn inline-block text-white py-3 px-6 rounded-lg font-medium">
                    <i class="fas fa-vote-yea mr-2"></i>Ga naar stemmen
                </a>
                <a href="<?= BASE_URL ?>/src/api/clear_session.php" class="bg-gray-500 hover:bg-gray-600 inline-block text-white py-3 px-6 rounded-lg font-medium">
                    <i class="fas fa-sign-out-alt mr-2"></i>Uitloggen
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../../include/footer.php'; ?>
    
    <?php if ($shouldLogout): ?>
    <script>
        // Auto redirect after 30 seconds
        setTimeout(function() {
            window.location.href = "<?= BASE_URL ?>";
        }, 30000);
    </script>
    <?php endif; ?>
</body>
</html> 
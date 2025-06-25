<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_connect.php';

// Check if user has voted
if (!isset($_SESSION['vote_success']) || $_SESSION['vote_success'] !== true) {
    // Redirect to voting page if not voted
    header("Location: " . BASE_URL . "/pages/voting/index.php");
    exit();
}

// Get success message
$success_message = isset($_SESSION['vote_message']) ? $_SESSION['vote_message'] : "Uw stem is succesvol uitgebracht!";

// Mark session as having voted
$_SESSION['has_voted'] = true;
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stem Succesvol Uitgebracht - E-Stem Suriname</title>
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
        .success-container {
            background: white;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .success-icon {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(0, 119, 73, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 20px rgba(0, 119, 73, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(0, 119, 73, 0);
            }
        }
        .return-btn {
            background: linear-gradient(135deg, #007749 0%, #006241 100%);
            transition: all 0.3s ease;
        }
        .return-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 119, 73, 0.3);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col bg-gradient-to-br from-emerald-50 via-green-50 to-emerald-100">
    <?php include '../../include/nav.php'; ?>
    
    <div class="flex-grow flex items-center justify-center p-4">
        <div class="success-container w-full max-w-lg rounded-xl overflow-hidden text-center p-8">
            <div class="mb-6">
                <div class="w-24 h-24 bg-suriname-green bg-opacity-10 rounded-full flex items-center justify-center mx-auto mb-4 success-icon">
                    <i class="fas fa-check-circle text-5xl text-suriname-green"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Stem Succesvol Uitgebracht!</h1>
                <p class="text-gray-600"><?= htmlspecialchars($success_message) ?></p>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <p class="text-green-800 text-sm">
                    <i class="fas fa-info-circle mr-2"></i>
                    Uw stem is geregistreerd en zal worden meegeteld in de verkiezingsresultaten. Dank u voor uw deelname aan het democratische proces.
                </p>
            </div>
            
            <div class="mb-6">
                <p class="text-gray-700 font-medium">Wat gebeurt er nu?</p>
                <ul class="text-left text-gray-600 mt-2 space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-check text-suriname-green mt-1 mr-2"></i>
                        <span>Uw stem is opgeslagen in ons beveiligde systeem</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-suriname-green mt-1 mr-2"></i>
                        <span>U kunt niet opnieuw stemmen voor deze verkiezing</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check text-suriname-green mt-1 mr-2"></i>
                        <span>De resultaten worden bekend gemaakt na sluiting van de verkiezing</span>
                    </li>
                </ul>
            </div>
            
            <a href="<?= BASE_URL ?>" class="return-btn inline-block text-white py-3 px-6 rounded-lg font-medium">
                <i class="fas fa-home mr-2"></i>Terug naar Home
            </a>
        </div>
    </div>
    
    <?php include '../../include/footer.php'; ?>
    
    <script>
        // Auto redirect after 30 seconds
        setTimeout(function() {
            window.location.href = "<?= BASE_URL ?>";
        }, 30000);
    </script>
</body>
</html> 
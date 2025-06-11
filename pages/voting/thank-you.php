<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/VoterAuth.php';

// Check if voter is logged in
if (!isset($_SESSION['voter_id'])) {
    // Redirect to login page
    header("Location: " . BASE_URL . "/voter/index.php");
    exit();
}

// Initialize VoterAuth
$voterAuth = new VoterAuth($pdo);

// Verify the user has voted (for security)
$hasVoted = $voterAuth->hasVoted($_SESSION['voter_id']) || isset($_SESSION['has_voted']);

// Get message from session if exists
$message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
unset($_SESSION['message']); // Clear message after use

// Get voter info
$stmtUser = $pdo->prepare("SELECT first_name, last_name FROM voters WHERE id = ?");
$stmtUser->execute([$_SESSION['voter_id']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
$userFullName = $user ? $user['first_name'] . " " . $user['last_name'] : "Guest";

// Get active election info
try {
    $stmt = $pdo->query("SELECT ElectionName FROM elections WHERE Status = 'active' LIMIT 1");
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    $electionName = $election ? $election['ElectionName'] : 'Current Election';
} catch (PDOException $e) {
    $electionName = 'Current Election';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Voting - e-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body { 
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc; 
        }
        
        .checkmark-circle {
            width: 150px;
            height: 150px;
            position: relative;
            display: inline-block;
            vertical-align: top;
            margin-left: auto;
            margin-right: auto;
        }
        .checkmark-circle .background {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: #007749;
            position: absolute;
        }
        .checkmark-circle .checkmark {
            border-radius: 5px;
        }
        .checkmark-circle .checkmark.draw:after {
            animation-delay: 300ms;
            animation-duration: 1s;
            animation-timing-function: ease;
            animation-name: checkmark;
            transform: scaleX(-1) rotate(135deg);
            animation-fill-mode: forwards;
        }
        .checkmark-circle .checkmark:after {
            opacity: 0;
            height: 75px;
            width: 37.5px;
            transform-origin: left top;
            border-right: 15px solid white;
            border-top: 15px solid white;
            content: '';
            left: 25px;
            top: 75px;
            position: absolute;
        }

        @keyframes checkmark {
            0% {
                height: 0;
                width: 0;
                opacity: 1;
            }
            20% {
                height: 0;
                width: 37.5px;
                opacity: 1;
            }
            40% {
                height: 75px;
                width: 37.5px;
                opacity: 1;
            }
            100% {
                height: 75px;
                width: 37.5px;
                opacity: 1;
            }
        }
    </style>
</head>
<body>
<!-- Header -->
<header class="bg-white shadow-sm py-4 mb-6">
    <div class="container mx-auto px-4 flex justify-between items-center max-w-6xl">
        <div class="flex space-x-2">
            <a href="<?= BASE_URL ?>" class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700">Home</a>
            <a href="<?= BASE_URL ?>/pages/voting" class="px-4 py-2 rounded-md bg-suriname-green text-white">Voting</a>
            <a href="<?= BASE_URL ?>/pages/results.php" class="px-4 py-2 rounded-md bg-gray-100 hover:bg-gray-200 text-gray-700">Results</a>
        </div>
        <a href="<?= BASE_URL ?>/include/logout.php" class="px-4 py-2 rounded-md bg-suriname-red text-white hover:bg-suriname-dark-red transition-colors">
            <i class="fas fa-sign-out-alt mr-2"></i>Log Out
        </a>
    </div>
</header>

<main class="container mx-auto px-4 py-8 max-w-4xl">
    <div class="bg-white p-8 rounded-xl shadow-md text-center">
        <?php if ($hasVoted): ?>
            <div class="checkmark-circle mb-6">
                <div class="background"></div>
                <div class="checkmark draw"></div>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Thank You for Voting!</h1>
            <p class="text-xl text-suriname-green font-medium mb-2"><?= htmlspecialchars($userFullName) ?></p>
            <p class="text-gray-600 mb-8">Your vote in the <?= htmlspecialchars($electionName) ?> has been successfully recorded.</p>
            
            <div class="bg-gray-50 p-6 rounded-lg mb-8 max-w-lg mx-auto">
                <h2 class="font-semibold text-lg text-gray-700 mb-3">What's Next?</h2>
                <ul class="space-y-3 text-gray-600 text-left">
                    <li class="flex items-start">
                        <i class="fas fa-chart-bar text-suriname-green mt-1 mr-3"></i>
                        <span>You can view the live results as they come in on the <a href="<?= BASE_URL ?>/pages/results.php" class="text-suriname-green hover:underline">Results Page</a>.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-shield-alt text-suriname-green mt-1 mr-3"></i>
                        <span>Your vote has been securely recorded and cannot be changed or viewed by anyone.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-clipboard-check text-suriname-green mt-1 mr-3"></i>
                        <span>The system has marked you as having voted, so you won't be able to vote again in this election.</span>
                    </li>
                </ul>
            </div>
            
            <div class="flex justify-center space-x-4">
                <a href="<?= BASE_URL ?>" class="px-6 py-3 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors">
                    <i class="fas fa-home mr-2"></i>Return Home
                </a>
                <a href="<?= BASE_URL ?>/pages/results.php" class="px-6 py-3 bg-suriname-green text-white rounded-md hover:bg-suriname-dark-green transition-colors">
                    <i class="fas fa-chart-pie mr-2"></i>View Results
                </a>
            </div>
        <?php else: ?>
            <div class="text-suriname-red text-6xl mb-6">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-800 mb-4">Access Restricted</h1>
            <p class="text-gray-600 mb-8">
                <?= $message ?? "You haven't cast your vote yet. Please go to the voting page to cast your vote." ?>
            </p>
            
            <div class="flex justify-center">
                <a href="<?= BASE_URL ?>/pages/voting" class="px-6 py-3 bg-suriname-green text-white rounded-md hover:bg-suriname-dark-green transition-colors">
                    <i class="fas fa-vote-yea mr-2"></i>Go to Voting Page
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<footer class="container mx-auto px-4 py-6 mt-12 border-t border-gray-200 text-center text-gray-500 text-sm max-w-6xl">
    <p>&copy; <?= date('Y') ?> E-Stem Suriname. All rights reserved.</p>
</footer>
</body>
</html> 
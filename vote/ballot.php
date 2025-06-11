<?php
require_once '../include/config.php';
require_once '../include/db_connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['voter_id'])) {
    // Redirect to login page
    header("Location: " . BASE_URL . "/voter/index.php");
    exit;
}

// Get voter information from session
$voter_id = $_SESSION['voter_id'];
$voter_name = $_SESSION['voter_name'] ?? 'Voter';
$voucher_id = $_SESSION['voucher_id'] ?? '';

// This is a placeholder for the actual ballot page
// In a real implementation, you would fetch candidates, parties, etc.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Ballot - E-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .header-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="header-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <i class="fas fa-vote-yea text-3xl mr-3"></i>
                    <h1 class="text-2xl font-bold">E-Stem Suriname</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm">Welcome,</p>
                        <p class="font-semibold"><?= htmlspecialchars($voter_name) ?></p>
                    </div>
                    <a href="<?= BASE_URL ?>/logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-all duration-200">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Your Voting Ballot</h2>
            <p class="text-gray-600 mb-6">This is a placeholder for the actual voting ballot. In a real implementation, you would see candidates and parties to vote for.</p>
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Voter Information</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Voter ID: <?= htmlspecialchars($voter_id) ?></p>
                            <p>Voucher ID: <?= htmlspecialchars($voucher_id) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Placeholder for voting options -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all duration-200">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-gray-500"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">Candidate <?= $i ?></h3>
                                <p class="text-sm text-gray-500">Party <?= $i ?></p>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button class="w-full bg-suriname-green hover:bg-suriname-dark-green text-white py-2 rounded-lg transition-all duration-200">
                                <i class="fas fa-check-circle mr-2"></i> Vote
                            </button>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>&copy; <?= date('Y') ?> E-Stem Suriname. All rights reserved.</p>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-gray-300 hover:text-white"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // This is a placeholder for the actual voting functionality
        document.addEventListener('DOMContentLoaded', function() {
            const voteButtons = document.querySelectorAll('button');
            
            voteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    alert('This is a placeholder. In a real implementation, your vote would be recorded.');
                });
            });
        });
    </script>
</body>
</html>
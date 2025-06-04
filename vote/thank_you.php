<?php
require_once '../include/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - E-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
            font-family: 'Arial', sans-serif;
        }
        
        .suriname-flag {
            background: linear-gradient(to bottom, 
                #007749 33.33%, 
                #ffffff 33.33%, 
                #ffffff 66.66%, 
                #C8102E 66.66%);
        }
        
        @keyframes confetti {
            0% { transform: translateY(0) rotate(0); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background-color: #f00;
            animation: confetti 5s ease-in-out infinite;
        }
        
        .confetti:nth-child(2n) {
            background-color: #0f0;
            animation-delay: 0.2s;
            animation-duration: 4s;
        }
        
        .confetti:nth-child(3n) {
            background-color: #00f;
            animation-delay: 0.4s;
            animation-duration: 4.5s;
        }
        
        .confetti:nth-child(4n) {
            background-color: #ff0;
            animation-delay: 0.6s;
            animation-duration: 5.5s;
        }
        
        .confetti:nth-child(5n) {
            background-color: #f0f;
            animation-delay: 0.8s;
            animation-duration: 6s;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Confetti Animation -->
    <div id="confetti-container"></div>
    
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <div class="suriname-flag h-8 w-12 mr-3"></div>
                <h1 class="text-2xl font-bold text-gray-800">E-Stem Suriname</h1>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8 flex items-center justify-center">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden max-w-lg w-full text-center p-8">
            <div class="mb-6">
                <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 mb-4">
                    <i class="fas fa-check-circle text-green-600 text-4xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Thank You for Voting!</h2>
                <p class="text-gray-600 mb-6">Your vote has been successfully recorded.</p>
                
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 text-left" role="alert">
                    <p class="text-blue-700">Your participation in this democratic process is important. Thank you for making your voice heard.</p>
                </div>
                
                <div class="mt-8">
                    <a href="<?= BASE_URL ?>" class="inline-block bg-gray-800 hover:bg-gray-900 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition-colors duration-200">
                        Return to Homepage
                    </a>
                </div>
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
            const confettiContainer = document.getElementById('confetti-container');
            const confettiCount = 100;
            
            // Create confetti elements
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.classList.add('confetti');
                
                // Randomize confetti properties
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.animationDelay = Math.random() * 5 + 's';
                confetti.style.animationDuration = Math.random() * 3 + 3 + 's';
                
                // Add different shapes
                if (i % 3 === 0) {
                    confetti.style.borderRadius = '50%';
                } else if (i % 3 === 1) {
                    confetti.style.width = '7px';
                    confetti.style.height = '14px';
                } else {
                    confetti.style.transform = 'rotate(45deg)';
                }
                
                confettiContainer.appendChild(confetti);
            }
        });
    </script>
</body>
</html>

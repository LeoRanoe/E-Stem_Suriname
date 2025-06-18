<?php
require_once '../include/config.php';
require_once '../include/db_connect.php';
require_once '../include/admin_auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    error_log("Login page - Starting new session");
    session_start();
}

error_log("Login page - Session ID: " . session_id());
error_log("Login page - Current session data: " . print_r($_SESSION, true));

// Check if already logged in
if (isAdminLoggedIn()) {
    error_log("Login page - Admin already logged in, redirecting to index");
    header("Location: " . BASE_URL . "/admin/index.php");
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Login page - Processing login form submission");
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
        error_log("Login page - Empty email or password");
    } else {
        try {
            error_log("Login page - Attempting to authenticate admin: " . $email);
            $admin = authenticateAdmin($email, $password);
            
            if ($admin) {
                error_log("Login page - Authentication successful for admin: " . $email);
                loginAdmin($admin);
                
                // Verify session was set correctly
                if (isset($_SESSION['AdminID'])) {
                    error_log("Login page - Session verified, redirecting to index");
                    header("Location: " . BASE_URL . "/admin/index.php");
                    exit();
                } else {
                    error_log("Login page - Session not set after login");
                    $error = "Login successful but session not set. Please try again.";
                }
            } else {
                error_log("Login page - Authentication failed for admin: " . $email);
                $error = "Invalid email or password";
            }
        } catch (Exception $e) {
            error_log("Login page - Error during authentication: " . $e->getMessage());
            $error = "An error occurred during login. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'suriname-green': '#007847',
                        'suriname-green-dark': '#006238',
                        'suriname-red': '#C8102E',
                        'suriname-red-dark': '#a50d26',
                        'suriname-yellow': '#FFD100',
                        'suriname-yellow-dark': '#E6BC00',
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                    boxShadow: {
                        'suriname': '0 4px 10px rgba(0, 120, 71, 0.1), 0 8px 20px rgba(0, 120, 71, 0.05)',
                        'suriname-lg': '0 10px 25px rgba(0, 120, 71, 0.2), 0 15px 40px rgba(0, 120, 71, 0.1)',
                    },
                }
            }
        }
    </script>
    <style>
        .sr-flag-pattern {
            background: linear-gradient(to bottom,
                #007847 0%,
                #007847 20%,
                #FFFFFF 20%,
                #FFFFFF 40%,
                #C8102E 40%,
                #C8102E 60%,
                #FFFFFF 60%,
                #FFFFFF 80%,
                #007847 80%,
                #007847 100%
            );
        }
        
        .sr-bg-diagonal {
            background: repeating-linear-gradient(
                45deg,
                rgba(0, 120, 71, 0.03),
                rgba(0, 120, 71, 0.03) 10px,
                rgba(0, 120, 71, 0.06) 10px,
                rgba(0, 120, 71, 0.06) 20px
            );
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #007847, #C8102E, #FFD100);
            border-radius: 8px 8px 0 0;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 font-sans flex items-center justify-center">
    <!-- Subtle pattern background -->
    <div class="fixed inset-0 z-0 pointer-events-none sr-bg-diagonal opacity-30"></div>
    
    <div class="w-full max-w-md mx-4 relative z-10">
        <div class="text-center mb-8">
            <img src="../assets/Images/logo.png" alt="E-Stem Suriname Logo" class="h-24 mx-auto">
        </div>
        
        <div class="bg-white rounded-lg shadow-suriname-lg p-8 relative login-card">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold text-gray-800">Admin Login</h2>
                <p class="mt-2 text-sm text-gray-600">Inloggen bij het E-Stem Suriname beheerderspaneel</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="p-4 mb-6 text-sm font-medium text-suriname-red-dark bg-suriname-red bg-opacity-10 rounded-lg border-l-4 border-suriname-red flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-suriname-red" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form class="space-y-6" method="POST">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mailadres</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-envelope text-suriname-green"></i>
                        </div>
                        <input id="email" name="email" type="email" required 
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-suriname-green focus:border-suriname-green transition-all duration-200"
                            placeholder="admin@example.com">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Wachtwoord</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-lock text-suriname-green"></i>
                        </div>
                        <input id="password" name="password" type="password" required 
                            class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-suriname-green focus:border-suriname-green transition-all duration-200"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center">
                    <input id="remember-me" name="remember-me" type="checkbox" 
                        class="h-4 w-4 text-suriname-green focus:ring-suriname-green border-gray-300 rounded">
                    <label for="remember-me" class="ml-2 block text-sm text-gray-700">
                        Remember me
                    </label>
                </div>

                <div>
                    <button type="submit" 
                        class="w-full flex justify-center items-center px-4 py-2 text-sm font-medium text-white bg-suriname-green rounded-md hover:bg-suriname-green-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green transition-all duration-200 transform hover:-translate-y-1 hover:shadow-md">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Inloggen
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-600">
                    © <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
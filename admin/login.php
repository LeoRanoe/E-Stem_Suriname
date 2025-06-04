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
    <title>Admin Login - E-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --green-main: #10b981;
            --green-dark: #059669;
            --green-light: #34d399;
            --navy-blue: #1e3a8a;
            --navy-blue-dark: #172554;
            --navy-blue-light: #3b82f6;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .login-container {
            background: linear-gradient(135deg, var(--green-main) 0%, var(--green-dark) 100%);
        }
        
        .form-container {
            backdrop-filter: blur(10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: var(--green-main);
        }
        
        .input-field {
            padding-left: 2.5rem !important;
            transition: all 0.3s ease;
        }
        
        .input-field:focus {
            transform: translateY(-2px);
        }
        
        .submit-btn {
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
        }
        
        .logo-container {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100">
    <div class="flex items-center justify-center min-h-screen login-container">
        <div class="w-full max-w-md p-1 mx-4 bg-white bg-opacity-10 rounded-xl shadow-lg">
            <div class="p-8 bg-white rounded-xl shadow-lg border border-gray-200 form-container">
                <div class="text-center logo-container">
                    <div class="flex justify-center mb-4">
                        <div class="p-4 rounded-full bg-suriname-green/10">
                            <i class="fas fa-vote-yea text-suriname-green text-4xl"></i>
                        </div>
                    </div>
                    <h2 class="mt-2 text-3xl font-bold text-gray-800">Admin Login</h2>
                    <p class="mt-2 text-sm text-gray-600">Inloggen bij het E-Stem Suriname beheerderspaneel</p>
                </div>

                <?php if (isset($error)): ?>
                    <div class="p-4 mt-6 text-sm font-medium text-red-800 bg-red-100 rounded-lg border-l-4 border-red-500 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form class="mt-8 space-y-6" method="POST">
                    <div class="space-y-5">
                        <div class="input-group">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mailadres</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input id="email" name="email" type="email" required class="w-full px-10 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-suriname-green focus:border-transparent" placeholder="admin@example.com">
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Wachtwoord</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-lock text-gray-400"></i>
                                </div>
                                <input id="password" name="password" type="password" required class="w-full px-10 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-suriname-green focus:border-transparent" placeholder="••••••••">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox" 
                                class="w-4 h-4 text-suriname-green border-gray-300 rounded focus:ring-suriname-green">
                            <label for="remember-me" class="block ml-2 text-sm text-gray-700">
                                Remember me
                            </label>
                        </div>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="p-3 text-sm text-suriname-red bg-suriname-red/10 rounded-lg">
                            <p><?= htmlspecialchars($error) ?></p>
                        </div>
                    <?php endif; ?>

                    <div>
                        <button type="submit" class="w-full px-4 py-2 text-sm font-medium text-white bg-suriname-green rounded-lg hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green transition-all duration-200">
                            Inloggen
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-600">
                        © <?= date('Y') ?> E-Stem Suriname. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
session_start();
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/admin_auth.php';

// Check if admin is already logged in
if (isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            $admin = authenticateAdmin($email, $password);
            
            if ($admin) {
                loginAdmin($admin);
                header('Location: ' . BASE_URL . '/admin/index.php');
                exit();
            } else {
                $error = "Invalid credentials";
            }
        } catch(PDOException $e) {
            error_log("Admin Login Error: " . $e->getMessage());
            $error = "An error occurred. Please try again later.";
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
</head>
<body class="min-h-screen bg-gray-100">
    <div class="flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8 space-y-8 bg-white rounded-lg shadow-md">
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-gray-900">
                    Admin Login
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Access the administration panel
                </p>
            </div>

            <?php if (isset($error)): ?>
                <div class="p-4 text-sm text-red-700 bg-red-100 rounded-lg">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST">
                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                        <div class="mt-1">
                            <input id="email" name="email" type="email" required
                                class="block w-full px-3 py-2 placeholder-gray-400 border border-gray-300 rounded-md shadow-sm appearance-none focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="admin@example.com">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="mt-1">
                            <input id="password" name="password" type="password" required
                                class="block w-full px-3 py-2 placeholder-gray-400 border border-gray-300 rounded-md shadow-sm appearance-none focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="relative flex justify-center w-full px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md group hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-sign-in-alt"></i>
                        </span>
                        Sign in
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
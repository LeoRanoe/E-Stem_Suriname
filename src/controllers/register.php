<?php
session_start();
require '../db.php'; // Include your database connection file

// Check if user has completed step 1
if (!isset($_SESSION['register_data'])) {
    header("Location: register_step1.php");
    exit;
}

$error = ""; // Initialize error message

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $terms = isset($_POST['terms']) ? true : false;

    try {
        // Validate inputs
        if (!$terms) {
            $error = "U moet akkoord gaan met de gebruiksvoorwaarden.";
        } elseif ($password !== $confirm_password) {
            $error = "Wachtwoorden komen niet overeen.";
        } elseif (strlen($password) < 8) {
            $error = "Wachtwoord moet minimaal 8 tekens lang zijn.";
        } else {
            // Get data from session
            $register_data = $_SESSION['register_data'];
            
            // Hash password and insert user into the database
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $role = "User"; // Default role
            
            $insert_stmt = $conn->prepare("INSERT INTO Users (Voornaam, Achternaam, Email, Password, District, Role, IDNumber) VALUES (:voornaam, :achternaam, :email, :password, :district, :role, :id_number)");
            $insert_stmt->bindParam(':voornaam', $register_data['voornaam'], PDO::PARAM_STR);
            $insert_stmt->bindParam(':achternaam', $register_data['achternaam'], PDO::PARAM_STR);
            $insert_stmt->bindParam(':email', $register_data['email'], PDO::PARAM_STR);
            $insert_stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $insert_stmt->bindParam(':district', $register_data['district_id'], PDO::PARAM_INT);
            $insert_stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $insert_stmt->bindParam(':id_number', $register_data['id_number'], PDO::PARAM_STR);

            if ($insert_stmt->execute()) {
                // Clear registration data from session
                unset($_SESSION['register_data']);
                header("Location: login.php");
                exit;
            } else {
                $error = "Registratie mislukt. Probeer het opnieuw.";
            }
        }
    } catch (PDOException $e) {
        $error = "Database fout: " . $e->getMessage();
    }
}
include '../include/nav.php'; // Corrected path to nav.php
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registratie Stap 2 - E-Stem Suriname</title>
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
                    animation: {
                        'fade-in-up': 'fadeInUp 0.5s ease-out',
                        'slide-in': 'slideIn 0.5s ease-out',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(-20px)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' },
                        },
                    },
                },
            },
        }
    </script>
    <style>
        .wave-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M54.627 0l.83.828-1.415 1.415L51.8 0h2.827zM5.373 0l-.83.828L5.96 2.243 8.2 0H5.374zM48.97 0l3.657 3.657-1.414 1.414L46.143 0h2.828zM11.03 0L7.372 3.657 8.787 5.07 13.857 0H11.03zm32.284 0L49.8 6.485 48.384 7.9l-7.9-7.9h2.83zM16.686 0L10.2 6.485 11.616 7.9l7.9-7.9h-2.83zM22.343 0L13.857 8.485 15.272 9.9l7.9-7.9h-.83zm5.657 0L19.514 8.485 20.93 9.9l8.485-8.485h-1.415zM32.372 0L22.343 10.03 23.758 11.444l10.03-10.03h-1.415zm-1.414 0L19.514 11.444l1.414 1.414 11.444-11.444h-1.414zM32.372 0L42.4 10.03l-1.414 1.414L30.958 1.415 32.37 0z' fill='%23007749' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-50 via-green-50 to-emerald-100">
    <?php include '../include/nav.php'; ?>

    <!-- Main Content -->
    <main class="container mx-auto px-4 pt-32 pb-16">
        <div class="max-w-6xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden animate-fade-in-up">
                <div class="flex flex-col lg:flex-row">
                    <!-- Info Section -->
                    <div class="lg:w-1/2 bg-gradient-to-br from-suriname-green to-suriname-dark-green p-8 lg:p-12 text-white animate-slide-in">
                        <div class="max-w-md mx-auto">
                            <div class="flex items-center space-x-3 mb-6">
                                <i class="fas fa-crow text-3xl"></i>
                                <h1 class="text-3xl font-bold">E-Stem Suriname</h1>
                            </div>
                            <h2 class="text-2xl font-semibold mb-4">Stap 2: Wachtwoord Aanmaken</h2>
                            <p class="text-lg mb-8 text-emerald-50">
                                Maak een veilig wachtwoord aan om uw account te beschermen.
                                Kies een sterk wachtwoord dat u makkelijk kunt onthouden.
                            </p>
                            <div class="space-y-4">
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-lock text-emerald-200 mt-1"></i>
                                    <div>
                                        <h3 class="font-semibold text-emerald-50">Veilig Wachtwoord</h3>
                                        <p class="text-emerald-100">Minimaal 8 tekens, letters en cijfers</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-shield-alt text-emerald-200 mt-1"></i>
                                    <div>
                                        <h3 class="font-semibold text-emerald-50">Versleuteling</h3>
                                        <p class="text-emerald-100">Uw wachtwoord wordt veilig opgeslagen</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-check-circle text-emerald-200 mt-1"></i>
                                    <div>
                                        <h3 class="font-semibold text-emerald-50">Verificatie</h3>
                                        <p class="text-emerald-100">Bevestig uw wachtwoord voor extra veiligheid</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Password Form Section -->
                    <div class="lg:w-1/2 p-8 lg:p-12">
                        <div class="max-w-md mx-auto">
                            <h2 class="text-2xl font-bold text-gray-900 mb-8">Wachtwoord Instellen</h2>
                            <?php if (isset($error)): ?>
                                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-circle text-red-500"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm text-red-700"><?= $error ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <form method="POST" action="" class="space-y-6">
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Wachtwoord</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-lock text-gray-400"></i>
                                        </div>
                                        <input type="password" id="password" name="password" required
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-suriname-green focus:border-suriname-green transition-colors duration-200"
                                            placeholder="••••••••">
                                    </div>
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Bevestig Wachtwoord</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-lock text-gray-400"></i>
                                        </div>
                                        <input type="password" id="confirm_password" name="confirm_password" required
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-suriname-green focus:border-suriname-green transition-colors duration-200"
                                            placeholder="••••••••">
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" id="terms" name="terms" required
                                        class="h-4 w-4 text-suriname-green focus:ring-suriname-green border-gray-300 rounded">
                                    <label for="terms" class="ml-2 block text-sm text-gray-700">
                                        Ik ga akkoord met de <a href="#" class="text-suriname-green hover:text-suriname-dark-green">gebruiksvoorwaarden</a>
                                    </label>
                                </div>
                                <button type="submit"
                                    class="w-full flex justify-center items-center space-x-2 bg-gradient-to-r from-suriname-green to-suriname-dark-green text-white px-6 py-3 rounded-lg hover:from-suriname-dark-green hover:to-suriname-green transition-all duration-300 transform hover:-translate-y-1 shadow-md hover:shadow-lg group">
                                    <i class="fas fa-check transform group-hover:scale-110 transition-transform duration-300"></i>
                                    <span>Registratie Voltooien</span>
                                </button>
                            </form>
                            <div class="mt-6 text-center">
                                <p class="text-sm text-gray-600">
                                    Wilt u terug naar de vorige stap?
                                    <a href="register_step1.php" class="font-medium text-suriname-green hover:text-suriname-dark-green transition-colors duration-200">
                                        Klik hier
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
</body>
</html>
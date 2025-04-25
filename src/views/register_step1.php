<?php
session_start();
require '../db.php';

// Fetch districts from the database
try {
    $district_stmt = $conn->query("SELECT DistrictID, DistrictName FROM Districten");
    $districts = $district_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voornaam = $_POST['voornaam'];
    $achternaam = $_POST['achternaam'];
    $email = $_POST['email'];
    $id_number = $_POST['id_number'];
    $district_id = $_POST['district'];

    try {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Ongeldig e-mailadres formaat.";
        } else {
            // Check if email or ID Number already exists
            $stmt = $conn->prepare("SELECT UserID FROM Users WHERE Email = :email OR IDNumber = :id_number");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':id_number', $id_number, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = "E-mailadres of ID Nummer is al geregistreerd.";
            } else {
                // Store data in session for next step
                $_SESSION['register_data'] = [
                    'voornaam' => $voornaam,
                    'achternaam' => $achternaam,
                    'email' => $email,
                    'id_number' => $id_number,
                    'district_id' => $district_id
                ];
                
                // Redirect to password creation page
                header("Location: register.php");
                exit;
            }
        }
    } catch (PDOException $e) {
        $error = "Database fout: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registratie Stap 1 - E-Stem Suriname</title>
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
                            <h2 class="text-2xl font-semibold mb-4">Stap 1: Persoonlijke Gegevens</h2>
                            <p class="text-lg mb-8 text-emerald-50">
                                Vul uw persoonlijke gegevens in om te beginnen met het registratieproces.
                                Uw gegevens worden veilig opgeslagen en beschermd.
                            </p>
                            <div class="space-y-4">
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-user-shield text-emerald-200 mt-1"></i>
                                    <div>
                                        <h3 class="font-semibold text-emerald-50">Veilige Registratie</h3>
                                        <p class="text-emerald-100">Uw gegevens worden versleuteld opgeslagen</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-check-circle text-emerald-200 mt-1"></i>
                                    <div>
                                        <h3 class="font-semibold text-emerald-50">Verificatie</h3>
                                        <p class="text-emerald-100">Uw identiteit wordt geverifieerd</p>
                                    </div>
                                </div>
                                <div class="flex items-start space-x-3">
                                    <i class="fas fa-lock text-emerald-200 mt-1"></i>
                                    <div>
                                        <h3 class="font-semibold text-emerald-50">Wachtwoord</h3>
                                        <p class="text-emerald-100">In de volgende stap maakt u een veilig wachtwoord aan</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Registration Form Section -->
                    <div class="lg:w-1/2 p-8 lg:p-12">
                        <div class="max-w-md mx-auto">
                            <h2 class="text-2xl font-bold text-gray-900 mb-8">Persoonlijke Gegevens</h2>
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
                                    <label for="voornaam" class="block text-sm font-medium text-gray-700 mb-2">Voornaam</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                        <input type="text" id="voornaam" name="voornaam" required
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-suriname-green focus:border-suriname-green transition-colors duration-200"
                                            placeholder="Uw voornaam">
                                    </div>
                                </div>
                                <div>
                                    <label for="achternaam" class="block text-sm font-medium text-gray-700 mb-2">Achternaam</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-user text-gray-400"></i>
                                        </div>
                                        <input type="text" id="achternaam" name="achternaam" required
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-suriname-green focus:border-suriname-green transition-colors duration-200"
                                            placeholder="Uw achternaam">
                                    </div>
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">E-mailadres</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-envelope text-gray-400"></i>
                                        </div>
                                        <input type="email" id="email" name="email" required
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-suriname-green focus:border-suriname-green transition-colors duration-200"
                                            placeholder="uw@email.com">
                                    </div>
                                </div>
                                <div>
                                    <label for="id_number" class="block text-sm font-medium text-gray-700 mb-2">ID Nummer</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-id-card text-gray-400"></i>
                                        </div>
                                        <input type="text" id="id_number" name="id_number" required
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-suriname-green focus:border-suriname-green transition-colors duration-200"
                                            placeholder="Uw ID nummer">
                                    </div>
                                </div>
                                <div>
                                    <label for="district" class="block text-sm font-medium text-gray-700 mb-2">District</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="fas fa-map-marker-alt text-gray-400"></i>
                                        </div>
                                        <select id="district" name="district" required
                                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-suriname-green focus:border-suriname-green transition-colors duration-200 bg-white">
                                            <option value="" disabled selected>Selecteer een district</option>
                                            <?php foreach ($districts as $district): ?>
                                                <option value="<?= htmlspecialchars($district['DistrictID']) ?>">
                                                    <?= htmlspecialchars($district['DistrictName']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit"
                                    class="w-full flex justify-center items-center space-x-2 bg-gradient-to-r from-suriname-green to-suriname-dark-green text-white px-6 py-3 rounded-lg hover:from-suriname-dark-green hover:to-suriname-green transition-all duration-300 transform hover:-translate-y-1 shadow-md hover:shadow-lg group">
                                    <i class="fas fa-arrow-right transform group-hover:scale-110 transition-transform duration-300"></i>
                                    <span>Volgende Stap</span>
                                </button>
                            </form>
                            <div class="mt-6 text-center">
                                <p class="text-sm text-gray-600">
                                    Heeft u al een account?
                                    <a href="login.php" class="font-medium text-suriname-green hover:text-suriname-dark-green transition-colors duration-200">
                                        Log hier in
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
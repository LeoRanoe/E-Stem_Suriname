<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Get voter ID from URL
$voter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($voter_id <= 0) {
    $_SESSION['error_message'] = "Ongeldige stemmer ID.";
    header('Location: voters.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $voornaam = $_POST['voornaam'] ?? '';
        $achternaam = $_POST['achternaam'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $district_id = $_POST['district_id'] ?? '';
        $id_number = $_POST['id_number'] ?? '';
        $status = $_POST['status'] ?? 'active';

        if (empty($voornaam) || empty($achternaam) || empty($email) || empty($district_id) || empty($id_number)) {
            throw new Exception('Vul alle verplichte velden in.');
        }

        // Check if email already exists for other users
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Email = ? AND UserID != ?");
        $stmt->execute([$email, $voter_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Dit e-mailadres is al in gebruik.');
        }

        // Check if ID number already exists for other users
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE IDNumber = ? AND UserID != ?");
        $stmt->execute([$id_number, $voter_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Dit ID nummer is al in gebruik.');
        }

        // Update user data
        if (!empty($password)) {
            if ($password !== $confirm_password) {
                throw new Exception('Wachtwoorden komen niet overeen.');
            }

            if (strlen($password) < 8) {
                throw new Exception('Wachtwoord moet minimaal 8 tekens lang zijn.');
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET Voornaam = ?, Achternaam = ?, Email = ?, Password = ?, DistrictID = ?, IDNumber = ?, Status = ?
                WHERE UserID = ?
            ");
            $stmt->execute([$voornaam, $achternaam, $email, $hashed_password, $district_id, $id_number, $status, $voter_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET Voornaam = ?, Achternaam = ?, Email = ?, DistrictID = ?, IDNumber = ?, Status = ?
                WHERE UserID = ?
            ");
            $stmt->execute([$voornaam, $achternaam, $email, $district_id, $id_number, $status, $voter_id]);
        }
        
        $_SESSION['success_message'] = "Stemmer is succesvol bijgewerkt.";
        header('Location: voters.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Fetch voter data
try {
    $stmt = $pdo->prepare("
        SELECT u.*, d.DistrictName
        FROM users u
        LEFT JOIN districten d ON u.DistrictID = d.DistrictID
        WHERE u.UserID = ? AND u.Role = 'voter'
    ");
    $stmt->execute([$voter_id]);
    $voter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voter) {
        $_SESSION['error_message'] = "Stemmer niet gevonden.";
        header('Location: voters.php');
        exit;
    }

    // Fetch all districts for dropdown
    $districts = $pdo->query("SELECT * FROM districten ORDER BY DistrictName")->fetchAll();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de stemmergegevens.";
    header('Location: voters.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stemmer Bewerken - E-Stem Suriname</title>
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
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php require_once 'components/nav.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 ml-64 p-6 overflow-y-auto">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Stemmer Bewerken</h2>

                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                            <span class="block sm:inline"><?= $_SESSION['error_message'] ?></span>
                            <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <?php unset($_SESSION['error_message']); ?>
                    <?php endif; ?>

                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="voornaam">
                                Voornaam
                            </label>
                            <input type="text" name="voornaam" id="voornaam" required
                                   value="<?= htmlspecialchars($voter['Voornaam']) ?>"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="achternaam">
                                Achternaam
                            </label>
                            <input type="text" name="achternaam" id="achternaam" required
                                   value="<?= htmlspecialchars($voter['Achternaam']) ?>"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                Email
                            </label>
                            <input type="email" name="email" id="email" required
                                   value="<?= htmlspecialchars($voter['Email']) ?>"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                                Nieuw Wachtwoord (optioneel)
                            </label>
                            <input type="password" name="password" id="password"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                                Bevestig Nieuw Wachtwoord
                            </label>
                            <input type="password" name="confirm_password" id="confirm_password"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="district_id">
                                District
                            </label>
                            <select name="district_id" id="district_id" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="">Selecteer een district</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?= $district['DistrictID'] ?>" 
                                            <?= $district['DistrictID'] == $voter['DistrictID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($district['DistrictName']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="id_number">
                                ID Nummer
                            </label>
                            <input type="text" name="id_number" id="id_number" required
                                   value="<?= htmlspecialchars($voter['IDNumber']) ?>"
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>

                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                                Status
                            </label>
                            <select name="status" id="status" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="active" <?= $voter['Status'] === 'active' ? 'selected' : '' ?>>Actief</option>
                                <option value="inactive" <?= $voter['Status'] === 'inactive' ? 'selected' : '' ?>>Inactief</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-end space-x-4">
                            <a href="voters.php" 
                               class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded transition-all duration-300 transform hover:scale-105">
                                Annuleren
                            </a>
                            <button type="submit" 
                                    class="bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-2 px-4 rounded transition-all duration-300 transform hover:scale-105">
                                Opslaan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 
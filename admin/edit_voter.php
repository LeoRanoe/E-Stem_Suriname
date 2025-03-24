<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Get voter ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    $_SESSION['error_message'] = "Ongeldige stemmer ID.";
    header("Location: voters.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $status = $_POST['status'] ?? 'active';

        if (empty($name) || empty($email)) {
            throw new Exception('Vul alle verplichte velden in.');
        }

        // Check if email already exists for other users
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Email = ? AND UserID != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Dit e-mailadres is al in gebruik.');
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
                SET Name = ?, Email = ?, Password = ?, Status = ?
                WHERE UserID = ?
            ");
            $stmt->execute([$name, $email, $hashed_password, $status, $user_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET Name = ?, Email = ?, Status = ?
                WHERE UserID = ?
            ");
            $stmt->execute([$name, $email, $status, $user_id]);
        }
        
        $_SESSION['success_message'] = "Stemmer is succesvol bijgewerkt.";
        header("Location: voters.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get voter data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE UserID = ? AND UserType = 'voter'");
    $stmt->execute([$user_id]);
    $voter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voter) {
        $_SESSION['error_message'] = "Stemmer niet gevonden.";
        header("Location: voters.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de stemmer.";
    header("Location: voters.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stemmer Bewerken - <?= SITE_NAME ?></title>
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
<body class="bg-gray-50">
    <?php include 'nav.php'; ?>

    <main class="container mx-auto px-4 py-16">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Stemmer Bewerken</h1>
                <p class="mt-2 text-gray-600">Bewerk de gegevens van de stemmer</p>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <p><?= $_SESSION['error_message'] ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Naam
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   value="<?= htmlspecialchars($voter['Name']) ?>"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                E-mailadres
                            </label>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   value="<?= htmlspecialchars($voter['Email']) ?>"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Nieuw Wachtwoord (optioneel)
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                            <p class="mt-1 text-sm text-gray-500">
                                Laat dit veld leeg om het huidige wachtwoord te behouden.
                            </p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                                Bevestig Nieuw Wachtwoord
                            </label>
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password" 
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">
                                Status
                            </label>
                            <select name="status" 
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                                <option value="active" <?= $voter['Status'] === 'active' ? 'selected' : '' ?>>
                                    Actief
                                </option>
                                <option value="inactive" <?= $voter['Status'] === 'inactive' ? 'selected' : '' ?>>
                                    Inactief
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="voters.php" 
                           class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                            <i class="fas fa-times mr-2"></i> Annuleren
                        </a>
                        <button type="submit" 
                                class="bg-suriname-green text-white px-6 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                            <i class="fas fa-save mr-2"></i> Opslaan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
</body>
</html> 
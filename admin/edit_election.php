<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Get election ID from URL
$election_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($election_id === 0) {
    $_SESSION['error_message'] = "Ongeldige verkiezing ID.";
    header("Location: elections.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $description = $_POST['description'] ?? '';
        $status = $_POST['status'] ?? 'active';
        $show_results = isset($_POST['show_results']) ? 1 : 0;

        if (empty($name) || empty($start_date) || empty($end_date)) {
            throw new Exception('Vul alle verplichte velden in.');
        }

        $stmt = $pdo->prepare("
            UPDATE elections 
            SET ElectionName = ?, Description = ?, StartDate = ?, EndDate = ?, Status = ?, ShowResults = ?
            WHERE ElectionID = ?
        ");
        $stmt->execute([$name, $description, $start_date, $end_date, $status, $show_results, $election_id]);
        
        $_SESSION['success_message'] = "Verkiezing is succesvol bijgewerkt.";
        header("Location: elections.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get election data
try {
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE ElectionID = ?");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        $_SESSION['error_message'] = "Verkiezing niet gevonden.";
        header("Location: elections.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de verkiezing.";
    header("Location: elections.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verkiezing Bewerken - <?= SITE_NAME ?></title>
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
                <h1 class="text-3xl font-bold text-gray-900">Verkiezing Bewerken</h1>
                <p class="mt-2 text-gray-600">Bewerk de instellingen van de verkiezing</p>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <p><?= $_SESSION['error_message'] ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Naam Verkiezing
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   value="<?= htmlspecialchars($election['ElectionName']) ?>"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        </div>

                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">
                                Start Datum
                            </label>
                            <input type="datetime-local" 
                                   name="start_date" 
                                   id="start_date" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($election['StartDate'])) ?>"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">
                                Eind Datum
                            </label>
                            <input type="datetime-local" 
                                   name="end_date" 
                                   id="end_date" 
                                   value="<?= date('Y-m-d\TH:i', strtotime($election['EndDate'])) ?>"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">
                                Status
                            </label>
                            <select name="status" 
                                    id="status" 
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                                <option value="active" <?= $election['Status'] === 'active' ? 'selected' : '' ?>>Actief</option>
                                <option value="inactive" <?= $election['Status'] === 'inactive' ? 'selected' : '' ?>>Inactief</option>
                            </select>
                        </div>

                        <div>
                            <label for="show_results" class="block text-sm font-medium text-gray-700">
                                Toon Resultaten
                            </label>
                            <div class="mt-1">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" name="show_results" id="show_results" <?= $election['ShowResults'] ? 'checked' : '' ?>
                                           class="rounded border-gray-300 text-suriname-green shadow-sm focus:border-suriname-green focus:ring-suriname-green">
                                    <span class="ml-2 text-sm text-gray-600">Maak resultaten publiek zichtbaar</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">
                            Beschrijving
                        </label>
                        <textarea name="description" 
                                  id="description" 
                                  rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm"><?= htmlspecialchars($election['Description']) ?></textarea>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="elections.php" 
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
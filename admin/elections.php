<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Handle election actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create':
                $name = $_POST['name'] ?? '';
                $start_date = $_POST['start_date'] ?? '';
                $end_date = $_POST['end_date'] ?? '';
                $description = $_POST['description'] ?? '';

                if (empty($name) || empty($start_date) || empty($end_date)) {
                    throw new Exception('Vul alle verplichte velden in.');
                }

                $stmt = $pdo->prepare("
                    INSERT INTO elections (ElectionName, Description, StartDate, EndDate, Status, CreatedAt)
                    VALUES (?, ?, ?, ?, 'active', NOW())
                ");
                $stmt->execute([$name, $description, $start_date, $end_date]);
                $_SESSION['success_message'] = "Verkiezing is succesvol aangemaakt.";
                break;

            case 'update':
                $election_id = intval($_POST['election_id']);
                $name = $_POST['name'] ?? '';
                $start_date = $_POST['start_date'] ?? '';
                $end_date = $_POST['end_date'] ?? '';
                $description = $_POST['description'] ?? '';
                $status = $_POST['status'] ?? 'active';

                if (empty($name) || empty($start_date) || empty($end_date)) {
                    throw new Exception('Vul alle verplichte velden in.');
                }

                $stmt = $pdo->prepare("
                    UPDATE elections 
                    SET ElectionName = ?, Description = ?, StartDate = ?, EndDate = ?, Status = ?
                    WHERE ElectionID = ?
                ");
                $stmt->execute([$name, $description, $start_date, $end_date, $status, $election_id]);
                $_SESSION['success_message'] = "Verkiezing is succesvol bijgewerkt.";
                break;

            case 'delete':
                $election_id = intval($_POST['election_id']);
                $stmt = $pdo->prepare("DELETE FROM elections WHERE ElectionID = ?");
                $stmt->execute([$election_id]);
                $_SESSION['success_message'] = "Verkiezing is succesvol verwijderd.";
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all elections with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) FROM elections");
    $total_elections = $stmt->fetchColumn();
    $total_pages = ceil($total_elections / $per_page);

    // Get elections for current page
    $stmt = $pdo->prepare("
        SELECT e.*, 
               COUNT(DISTINCT c.CandidateID) as candidate_count,
               COUNT(DISTINCT v.VoteID) as vote_count
        FROM elections e
        LEFT JOIN candidates c ON e.ElectionID = c.ElectionID
        LEFT JOIN votes v ON e.ElectionID = v.ElectionID
        GROUP BY e.ElectionID
        ORDER BY e.CreatedAt DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$per_page, $offset]);
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de verkiezingen.";
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verkiezingen Beheren - <?= SITE_NAME ?></title>
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
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Verkiezingen Beheren</h1>
                <p class="mt-2 text-gray-600">Beheer verkiezingen en hun instellingen</p>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    <p><?= $_SESSION['success_message'] ?></p>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <p><?= $_SESSION['error_message'] ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Create Election Form -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Nieuwe Verkiezing Aanmaken</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="create">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Naam Verkiezing
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
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
                                <option value="active">Actief</option>
                                <option value="inactive">Inactief</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">
                            Beschrijving
                        </label>
                        <textarea name="description" 
                                  id="description" 
                                  rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-suriname-green text-white px-6 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                            <i class="fas fa-plus mr-2"></i> Verkiezing Aanmaken
                        </button>
                    </div>
                </form>
            </div>

            <!-- Elections List -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Verkiezingen Overzicht</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Naam
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Start Datum
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Eind Datum
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kandidaten
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Stemmen
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acties
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($elections as $election): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($election['ElectionName']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $election['Status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $election['Status'] === 'active' ? 'Actief' : 'Inactief' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d-m-Y H:i', strtotime($election['StartDate'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d-m-Y H:i', strtotime($election['EndDate'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $election['candidate_count'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $election['vote_count'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-3">
                                            <a href="edit_election.php?id=<?= $election['ElectionID'] ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('Weet u zeker dat u deze verkiezing wilt verwijderen?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="election_id" value="<?= $election['ElectionID'] ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-4 flex justify-center">
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?= $i ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50
                                          <?= $i === $page ? 'z-10 bg-suriname-green border-suriname-green text-white' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
</body>
</html> 
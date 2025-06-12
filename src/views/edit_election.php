<?php
session_start();
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/admin_auth.php';

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
        $description = $_POST['description'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';

        if (empty($name) || empty($start_date) || empty($end_date)) {
            throw new Exception('Vul alle verplichte velden in.');
        }

        if (strtotime($end_date) <= strtotime($start_date)) {
            throw new Exception('Einddatum moet na de startdatum liggen.');
        }

        $stmt = $pdo->prepare("
            UPDATE elections 
            SET ElectionName = ?, StartDate = ?, EndDate = ?
            WHERE ElectionID = ?
        ");
        $stmt->execute([$name, $start_date, $end_date, $election_id]);
        
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

// Start output buffering
ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Verkiezing Bewerken</h1>
        <a href="elections.php"
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-2"></i>
            Terug naar overzicht
        </a>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
            <p><?= $_SESSION['error_message'] ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <form method="POST" class="space-y-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">
                    Naam Verkiezing
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="<?= htmlspecialchars($election['ElectionName'] ?? '') ?>"
                       required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">
                    Beschrijving
                </label>
                <textarea name="description" 
                          id="description" 
                          rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm"><?= htmlspecialchars($election['Description'] ?? '') ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700">
                        Startdatum
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
                        Einddatum
                    </label>
                    <input type="datetime-local" 
                           name="end_date" 
                           id="end_date" 
                           value="<?= date('Y-m-d\TH:i', strtotime($election['EndDate'])) ?>"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" 
                        class="bg-suriname-green text-white px-6 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>
                    Wijzigingen Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once __DIR__ . '/../../admin/components/layout.php';
?>

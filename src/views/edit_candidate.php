------------<?php
session_start();
require_once __DIR__ . '/../../include/db_connect.php'; // Corrected path
require_once __DIR__ . '/../../include/admin_auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Get candidate ID from URL
$candidate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($candidate_id === 0) {
    $_SESSION['error_message'] = "Ongeldige kandidaat ID.";
    header("Location: candidates.php");
    exit;
}

// Initialize controller
require_once __DIR__ . '/../../src/controllers/CandidateController.php';
$controller = new CandidateController();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add action and candidate_id to POST data for controller
        $_POST['action'] = 'edit';
        $_POST['candidate_id'] = $candidate_id;
        
        // Let controller handle the request
        $controller->handleRequest();
        
        // Redirect handled by controller
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all parties and districts
try {
    $stmt = $pdo->query("SELECT PartyID, PartyName FROM parties ORDER BY PartyName");
    $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT DistrictID, DistrictName FROM districten ORDER BY DistrictName");
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de partijen.";
    header("Location: candidates.php");
    exit;
}

// Get candidate data
try {
    $stmt = $pdo->prepare("
        SELECT c.*, e.ElectionName, p.PartyName, p.PartyID, d.DistrictID, d.DistrictName
        FROM candidates c
        LEFT JOIN elections e ON c.ElectionID = e.ElectionID
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN districten d ON c.DistrictID = d.DistrictID
        WHERE c.CandidateID = ?
    ");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidate) {
        $_SESSION['error_message'] = "Kandidaat niet gevonden.";
        header("Location: candidates.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de kandidaat.";
    header("Location: candidates.php");
    exit;
}

// Start output buffering
ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Kandidaat Bewerken</h1>
        <a href="candidates.php" 
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
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Naam Kandidaat
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="<?= htmlspecialchars($candidate['Name']) ?>"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                </div>

                <div>
                    <label for="party_id" class="block text-sm font-medium text-gray-700">
                        Partij
                    </label>
                    <select name="party_id" 
                            id="party_id"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        <option value="">Selecteer een partij</option>
                        <?php foreach ($parties as $party): ?>
                            <option value="<?= $party['PartyID'] ?>" 
                                    <?= $candidate['PartyID'] == $party['PartyID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($party['PartyName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="election" class="block text-sm font-medium text-gray-700">
                        Verkiezing
                    </label>
                    <input type="text"
                           id="election"
                           value="<?= htmlspecialchars($candidate['ElectionName']) ?>"
                           disabled
                           class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm sm:text-sm">
                </div>

                <div>
                    <label for="district_id" class="block text-sm font-medium text-gray-700">
                        District
                    </label>
                    <select name="district_id"
                            id="district_id"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        <option value="">Selecteer een district</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?= $district['DistrictID'] ?>"
                                    <?= $candidate['DistrictID'] == $district['DistrictID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($district['DistrictName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">
                        Foto
                    </label>
                    <?php if ($candidate['Photo']): ?>
                        <div class="mt-2 mb-2">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($candidate['Photo']) ?>" 
                                 alt="<?= htmlspecialchars($candidate['Name']) ?>"
                                 class="h-24 w-24 rounded-full object-cover">
                        </div>
                    <?php endif; ?>
                    <input type="file" 
                           name="image" 
                           id="image" 
                           accept="image/jpeg,image/png,image/gif"
                           class="mt-1 block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-md file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-suriname-green file:text-white
                                  hover:file:bg-suriname-dark-green">
                    <input type="hidden" name="current_image" value="<?= htmlspecialchars($candidate['Photo']) ?>">
                    <input type="hidden" name="firstName" value="<?= explode(' ', $candidate['Name'])[0] ?? '' ?>">
                    <input type="hidden" name="lastName" value="<?= explode(' ', $candidate['Name'])[1] ?? '' ?>">
                    <p class="mt-1 text-sm text-gray-500">Maximaal 5MB. JPG, PNG of GIF.</p>
                </div>
            </div>

            <!-- Removed Description Field -->

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
require_once __DIR__ . '/../../admin/components/layout.php'; // Corrected path
?>
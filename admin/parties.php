<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';
require_once '../include/config.php';

// Check if user is logged in and is admin
requireAdmin();

// Handle form submission for creating/updating party
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $party_name = $_POST['party_name'] ?? '';
        $party_description = $_POST['party_description'] ?? '';
        $party_id = isset($_POST['party_id']) ? intval($_POST['party_id']) : 0;

        if (empty($party_name)) {
            throw new Exception('Vul alle verplichte velden in.');
        }

        // Handle logo upload
        $logo_path = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['logo']['type'], $allowed_types)) {
                throw new Exception('Ongeldig bestandstype. Alleen JPG, PNG en GIF zijn toegestaan.');
            }

            if ($_FILES['logo']['size'] > $max_size) {
                throw new Exception('Bestand is te groot. Maximum grootte is 5MB.');
            }

            $upload_dir = '../uploads/parties/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                $logo_path = 'uploads/parties/' . $file_name;
            }
        }

        if ($party_id > 0) {
            // Update existing party
            if ($logo_path) {
                // Get current logo to delete after update
                $stmt = $pdo->prepare("SELECT Logo FROM parties WHERE PartyID = ?");
                $stmt->execute([$party_id]);
                $old_logo = $stmt->fetchColumn();

                $stmt = $pdo->prepare("
                    UPDATE parties 
                    SET PartyName = ?, Description = ?, Logo = ?
                    WHERE PartyID = ?
                ");
                $stmt->execute([$party_name, $party_description, $logo_path, $party_id]);

                // Delete old logo if exists
                if ($old_logo && file_exists('../' . $old_logo)) {
                    unlink('../' . $old_logo);
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE parties 
                    SET PartyName = ?, Description = ?
                    WHERE PartyID = ?
                ");
                $stmt->execute([$party_name, $party_description, $party_id]);
            }
            $_SESSION['success_message'] = "Partij is succesvol bijgewerkt.";
        } else {
            // Create new party
            $stmt = $pdo->prepare("
                INSERT INTO parties (PartyName, Description, Logo)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$party_name, $party_description, $logo_path]);
            $_SESSION['success_message'] = "Partij is succesvol toegevoegd.";
        }

        header("Location: parties.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Handle party deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $party_id = intval($_GET['delete']);

        // Check if party has candidates
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE PartyID = ?");
        $stmt->execute([$party_id]);
        $has_candidates = $stmt->fetchColumn() > 0;

        if ($has_candidates) {
            throw new Exception('Deze partij kan niet worden verwijderd omdat er nog kandidaten aan gekoppeld zijn.');
        }

        // Get logo path before deleting
        $stmt = $pdo->prepare("SELECT Logo FROM parties WHERE PartyID = ?");
        $stmt->execute([$party_id]);
        $logo_path = $stmt->fetchColumn();

        // Delete party
        $stmt = $pdo->prepare("DELETE FROM parties WHERE PartyID = ?");
        $stmt->execute([$party_id]);

        // Delete logo file if exists
        if ($logo_path && file_exists('../' . $logo_path)) {
            unlink('../' . $logo_path);
        }

        $_SESSION['success_message'] = "Partij is succesvol verwijderd.";
        header("Location: parties.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Start output buffering
ob_start();

// Initialize variables
$parties = [];
$total_candidates = 0;

// Fetch parties data
try {
    $stmt = $pdo->query("
        SELECT p.*, 
               COUNT(c.CandidateID) as candidate_count,
               GROUP_CONCAT(DISTINCT e.ElectionName) as elections
        FROM parties p
        LEFT JOIN candidates c ON p.PartyID = c.PartyID
        LEFT JOIN elections e ON c.ElectionID = e.ElectionID
        GROUP BY p.PartyID
        ORDER BY p.PartyName ASC
    ");
    $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total candidates
    $stmt = $pdo->query("SELECT COUNT(*) FROM candidates");
    $total_candidates = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de partijen.";
}
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Totaal Partijen</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format(count($parties)) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-flag text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Totaal Kandidaten</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_candidates) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-user-tie text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Gemiddeld Kandidaten per Partij</p>
                <p class="text-2xl font-bold text-suriname-green">
                    <?= count($parties) > 0 ? number_format($total_candidates / count($parties), 1) : '0' ?>
                </p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-chart-pie text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>
</div>

<!-- Add New Party Button -->
<div class="mb-6">
    <button onclick="document.getElementById('newPartyModal').classList.remove('hidden')" 
            class="bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-2 px-4 rounded transition-all duration-300 transform hover:scale-105">
        <i class="fas fa-plus mr-2"></i>Nieuwe Partij
    </button>
</div>

<!-- Parties Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Afkorting</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaten</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verkiezingen</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($parties as $party): ?>
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <img src="<?= $party['LogoURL'] ?? 'https://via.placeholder.com/40' ?>" 
                             alt="<?= htmlspecialchars($party['PartyName']) ?>" 
                             class="h-10 w-10 rounded-full object-cover transform hover:scale-110 transition-transform duration-200">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($party['PartyName']) ?></p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="text-sm text-gray-500"><?= htmlspecialchars($party['Abbreviation']) ?></p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                            <?= number_format($party['candidate_count']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <p class="text-sm text-gray-500"><?= $party['elections'] ? htmlspecialchars($party['elections']) : 'Geen' ?></p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="edit_party.php?id=<?= $party['PartyID'] ?>" 
                           class="text-suriname-green hover:text-suriname-dark-green mr-3 transition-colors duration-200">
                            <i class="fas fa-edit transform hover:scale-110 transition-transform duration-200"></i>
                        </a>
                        <a href="delete_party.php?id=<?= $party['PartyID'] ?>" 
                           class="text-suriname-red hover:text-suriname-dark-red transition-colors duration-200"
                           onclick="return confirm('Weet u zeker dat u deze partij wilt verwijderen?')">
                            <i class="fas fa-trash transform hover:scale-110 transition-transform duration-200"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- New Party Modal -->
<div id="newPartyModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="add_party.php" method="POST" enctype="multipart/form-data">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="partyName">
                            Naam Partij
                        </label>
                        <input type="text" name="partyName" id="partyName" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="abbreviation">
                            Afkorting
                        </label>
                        <input type="text" name="abbreviation" id="abbreviation" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="logo">
                            Logo
                        </label>
                        <input type="file" name="logo" id="logo" accept="image/*"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-suriname-green text-base font-medium text-white hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 transform hover:scale-105">
                        Opslaan
                    </button>
                    <button type="button" 
                            onclick="document.getElementById('newPartyModal').classList.add('hidden')"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 transform hover:scale-105">
                        Annuleren
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once 'components/layout.php';
?> 
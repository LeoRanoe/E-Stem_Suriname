<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Handle candidate actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create':
                $name = $_POST['name'] ?? '';
                $party_id = intval($_POST['party_id'] ?? 0);
                $election_id = intval($_POST['election_id']);
                $description = $_POST['description'] ?? '';

                if (empty($name) || empty($election_id) || empty($party_id)) {
                    throw new Exception('Vul alle verplichte velden in.');
                }

                // Handle image upload
                $image_path = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB

                    if (!in_array($_FILES['image']['type'], $allowed_types)) {
                        throw new Exception('Ongeldig bestandstype. Alleen JPG, PNG en GIF zijn toegestaan.');
                    }

                    if ($_FILES['image']['size'] > $max_size) {
                        throw new Exception('Bestand is te groot. Maximum grootte is 5MB.');
                    }

                    $upload_dir = '../uploads/candidates/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $file_name = uniqid() . '.' . $file_extension;
                    $target_path = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                        $image_path = 'uploads/candidates/' . $file_name;
                    }
                }

                $stmt = $pdo->prepare("
                    INSERT INTO candidates (Name, PartyID, ElectionID, Description, ImagePath, CreatedAt)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $party_id, $election_id, $description, $image_path]);
                $_SESSION['success_message'] = "Kandidaat is succesvol toegevoegd.";
                break;

            case 'delete':
                $candidate_id = intval($_POST['candidate_id']);
                
                // Get image path before deleting
                $stmt = $pdo->prepare("SELECT ImagePath FROM candidates WHERE CandidateID = ?");
                $stmt->execute([$candidate_id]);
                $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

                // Delete candidate
                $stmt = $pdo->prepare("DELETE FROM candidates WHERE CandidateID = ?");
                $stmt->execute([$candidate_id]);

                // Delete image file if exists
                if ($candidate && $candidate['ImagePath'] && file_exists('../' . $candidate['ImagePath'])) {
                    unlink('../' . $candidate['ImagePath']);
                }

                $_SESSION['success_message'] = "Kandidaat is succesvol verwijderd.";
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all elections for the dropdown
try {
    $stmt = $pdo->query("SELECT ElectionID, ElectionName FROM elections WHERE Status = 'active' ORDER BY ElectionName");
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de verkiezingen.";
}

// Get all parties for the dropdown
try {
    $stmt = $pdo->query("SELECT PartyID, PartyName FROM parties ORDER BY PartyName");
    $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de partijen.";
}

// Get all districts for the dropdown
try {
    $stmt = $pdo->query("SELECT DistrictID, DistrictName FROM districten ORDER BY DistrictName");
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de districten.";
}

// Get all candidates with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) FROM candidates");
    $total_candidates = $stmt->fetchColumn();
    $total_pages = ceil($total_candidates / $per_page);

    // Get candidates for current page
    $stmt = $pdo->prepare("
        SELECT c.*, e.ElectionName, p.PartyName,
               COUNT(v.VoteID) as vote_count
        FROM candidates c
        LEFT JOIN elections e ON c.ElectionID = e.ElectionID
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID
        GROUP BY c.CandidateID
        ORDER BY c.CreatedAt DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$per_page, $offset]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de kandidaten.";
    $candidates = [];
    $total_pages = 0;
}

// Start output buffering
ob_start();

// Fetch candidates data
try {
    $stmt = $pdo->query("
        SELECT c.*, p.PartyName, e.ElectionName 
        FROM candidates c
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN elections e ON c.ElectionID = e.ElectionID
        ORDER BY c.CreatedAt DESC
    ");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de kandidaten.";
}
?>

<!-- Add New Candidate Button -->
<div class="mb-6">
    <button onclick="document.getElementById('newCandidateModal').classList.remove('hidden')" 
            class="bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-2 px-4 rounded">
        <i class="fas fa-plus mr-2"></i>Nieuwe Kandidaat
    </button>
</div>

<!-- Candidates Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Naam</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verkiezing</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($candidates as $candidate): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <img src="<?= $candidate['Photo'] ?? 'https://via.placeholder.com/40' ?>" 
                             alt="<?= htmlspecialchars($candidate['Name']) ?>" 
                             class="h-10 w-10 rounded-full">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?= htmlspecialchars($candidate['Name']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?= htmlspecialchars($candidate['PartyName']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?= htmlspecialchars($candidate['ElectionName']) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            Actief
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="edit_candidate.php?id=<?= $candidate['CandidateID'] ?>" 
                           class="text-suriname-green hover:text-suriname-dark-green mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete_candidate.php?id=<?= $candidate['CandidateID'] ?>" 
                           class="text-suriname-red hover:text-suriname-dark-red" 
                           onclick="return confirm('Weet u zeker dat u deze kandidaat wilt verwijderen?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- New Candidate Modal -->
<div id="newCandidateModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="add_candidate.php" method="POST" enctype="multipart/form-data">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="firstName">
                            Voornaam
                        </label>
                        <input type="text" name="firstName" id="firstName" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="lastName">
                            Achternaam
                        </label>
                        <input type="text" name="lastName" id="lastName" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="party_id">
                            Partij
                        </label>
                        <select name="party_id" id="party_id" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Selecteer een partij</option>
                            <?php foreach ($parties as $party): ?>
                                <option value="<?= $party['PartyID'] ?>">
                                    <?= htmlspecialchars($party['PartyName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="election_id">
                            Verkiezing
                        </label>
                        <select name="election_id" id="election_id" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Selecteer een verkiezing</option>
                            <?php foreach ($elections as $election): ?>
                                <option value="<?= $election['ElectionID'] ?>">
                                    <?= htmlspecialchars($election['ElectionName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="district_id">
                            District
                        </label>
                        <select name="district_id" id="district_id" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Selecteer een district</option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?= $district['DistrictID'] ?>">
                                    <?= htmlspecialchars($district['DistrictName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="photo">
                            Foto
                        </label>
                        <input type="file" name="photo" id="photo" accept="image/*"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <p class="text-sm text-gray-500 mt-1">Maximaal 5MB. Toegestane formaten: JPG, PNG, GIF</p>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-suriname-green text-base font-medium text-white hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 transform hover:scale-105">
                        Opslaan
                    </button>
                    <button type="button" 
                            onclick="document.getElementById('newCandidateModal').classList.add('hidden')"
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
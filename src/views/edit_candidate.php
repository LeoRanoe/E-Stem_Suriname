<?php
session_start();
require_once __DIR__ . '/../../include/db_connect.php';
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
        $_POST['action'] = 'edit';
        $_POST['candidate_id'] = $candidate_id;
        $controller->handleRequest();
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
        SELECT c.*, e.ElectionName, p.PartyName, p.PartyID, d.DistrictID, d.DistrictName, r.name as ResortName
        FROM candidates c
        LEFT JOIN elections e ON c.ElectionID = e.ElectionID
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN districten d ON c.DistrictID = d.DistrictID
        LEFT JOIN resorts r ON c.ResortID = r.id
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

<!-- Main Content -->
<div class="p-6 sm:p-8 space-y-6">
    <!-- Page Header -->
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900">Kandidaat Bewerken</h1>
        <a href="candidates.php" 
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>
            Terug naar overzicht
        </a>
    </div>

    <!-- Error Message -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?= $_SESSION['error_message'] ?></p>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <form method="POST" enctype="multipart/form-data" class="divide-y divide-gray-200">
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Naam Kandidaat
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="<?= htmlspecialchars($candidate['Name']) ?>"
                               required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green">
                    </div>

                    <!-- Party -->
                    <div>
                        <label for="party_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Partij
                        </label>
                        <select name="party_id" 
                                id="party_id"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green">
                            <option value="">Selecteer een partij</option>
                            <?php foreach ($parties as $party): ?>
                                <option value="<?= $party['PartyID'] ?>" 
                                        <?= $candidate['PartyID'] == $party['PartyID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($party['PartyName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Election (readonly) -->
                    <div>
                        <label for="election" class="block text-sm font-medium text-gray-700 mb-1">
                            Verkiezing
                        </label>
                        <input type="text"
                               id="election"
                               value="<?= htmlspecialchars($candidate['ElectionName']) ?>"
                               disabled
                               class="w-full rounded-md border-gray-300 bg-gray-50 shadow-sm">
                    </div>

                    <!-- Candidate Type -->
                    <div>
                        <label for="candidate_type" class="block text-sm font-medium text-gray-700 mb-1">
                            Kandidaat Type
                        </label>
                        <select name="candidate_type"
                                id="candidate_type"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green">
                            <option value="DNA" <?= ($candidate['CandidateType'] ?? 'RR') === 'DNA' ? 'selected' : '' ?>>DNA</option>
                            <option value="RR" <?= ($candidate['CandidateType'] ?? 'RR') === 'RR' ? 'selected' : '' ?>>RR</option>
                        </select>
                    </div>

                    <!-- District -->
                    <div>
                        <label for="district_id" class="block text-sm font-medium text-gray-700 mb-1">
                            District
                        </label>
                        <select name="district_id"
                                id="district_id"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green">
                            <option value="">Selecteer een district</option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?= $district['DistrictID'] ?>"
                                        <?= $candidate['DistrictID'] == $district['DistrictID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($district['DistrictName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Resort (for RR candidates) -->
                    <div id="resort_selection_container" style="<?= ($candidate['CandidateType'] === 'RR') ? '' : 'display: none;' ?>">
                        <label for="resort_id" class="block text-sm font-medium text-gray-700 mb-1">
                            Resort
                        </label>
                        <select name="resort_id"
                                id="resort_id"
                                <?= ($candidate['CandidateType'] === 'RR') ? 'required' : '' ?>
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green">
                            <option value="">Selecteer een resort</option>
                            <!-- Resorts will be loaded via JavaScript -->
                        </select>
                    </div>

                    <!-- Photo -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Foto
                        </label>
                        <?php if ($candidate['Photo']): ?>
                            <div class="mb-3">
                                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($candidate['Photo']) ?>" 
                                     alt="<?= htmlspecialchars($candidate['Name']) ?>"
                                     class="h-24 w-24 rounded-full object-cover border-2 border-gray-200">
                            </div>
                        <?php endif; ?>
                        <input type="file" 
                               name="image" 
                               id="image" 
                               accept="image/jpeg,image/png,image/gif"
                               class="block w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-md file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-suriname-green file:text-white
                                      hover:file:bg-suriname-dark-green">
                        <input type="hidden" name="current_image" value="<?= htmlspecialchars($candidate['Photo']) ?>">
                        <input type="hidden" name="firstName" value="<?= explode(' ', $candidate['Name'])[0] ?? '' ?>">
                        <input type="hidden" name="lastName" value="<?= explode(' ', $candidate['Name'])[1] ?? '' ?>">
                        <p class="mt-1 text-xs text-gray-500">Maximaal 5MB. JPG, PNG of GIF.</p>
                    </div>
                </div>
            </div>

            <!-- Form Footer -->
            <div class="px-6 py-4 bg-gray-50 text-right">
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-suriname-green hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green">
                    <i class="fas fa-save mr-2"></i>
                    Wijzigingen Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript for Resort Selection -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const candidateTypeSelect = document.getElementById('candidate_type');
    const districtSelect = document.getElementById('district_id');
    const resortContainer = document.getElementById('resort_selection_container');
    const resortSelect = document.getElementById('resort_id');
    
    // Function to toggle resort selection visibility
    function toggleResortSelection() {
        if (candidateTypeSelect.value === 'RR') {
            resortContainer.style.display = '';
            resortSelect.required = true;
            // Load resorts if district is selected
            if (districtSelect.value) {
                loadResorts(districtSelect.value);
            }
        } else {
            resortContainer.style.display = 'none';
            resortSelect.required = false;
        }
    }
    
    // Function to load resorts based on district
    function loadResorts(districtId) {
        // Clear current options except the first one
        while (resortSelect.options.length > 1) {
            resortSelect.remove(1);
        }
        
        if (!districtId) return;
        
        // Add loading option
        const loadingOption = document.createElement('option');
        loadingOption.text = 'Laden...';
        loadingOption.disabled = true;
        resortSelect.add(loadingOption);
        
        // Fetch resorts from API
        fetch(`<?= BASE_URL ?>/src/api/get_resorts.php?district_id=${districtId}`)
            .then(response => response.json())
            .then(data => {
                // Remove loading option
                resortSelect.remove(resortSelect.options.length - 1);
                
                if (data.success && data.resorts && data.resorts.length > 0) {
                    // Add resort options
                    data.resorts.forEach(resort => {
                        const option = document.createElement('option');
                        option.value = resort.ResortID;
                        option.text = resort.ResortName;
                        // Check if this resort is the one currently assigned to the candidate
                        if (resort.ResortID == <?= json_encode($candidate['ResortID'] ?? 0) ?>) {
                            option.selected = true;
                        }
                        resortSelect.add(option);
                    });
                } else {
                    const noResortsOption = document.createElement('option');
                    noResortsOption.text = 'Geen resorts gevonden';
                    noResortsOption.disabled = true;
                    resortSelect.add(noResortsOption);
                }
            })
            .catch(error => {
                console.error('Error fetching resorts:', error);
                // Remove loading option and add error option
                resortSelect.remove(resortSelect.options.length - 1);
                const errorOption = document.createElement('option');
                errorOption.text = 'Fout bij het laden van resorts';
                errorOption.disabled = true;
                resortSelect.add(errorOption);
            });
    }
    
    // Set initial state
    toggleResortSelection();
    
    // Event listeners
    candidateTypeSelect.addEventListener('change', toggleResortSelection);
    districtSelect.addEventListener('change', function() {
        if (candidateTypeSelect.value === 'RR') {
            loadResorts(this.value);
        }
    });
    
    // Load resorts if RR is selected and district has a value
    if (candidateTypeSelect.value === 'RR' && districtSelect.value) {
        loadResorts(districtSelect.value);
    }
});
</script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once __DIR__ . '/../../admin/components/layout.php';
?>
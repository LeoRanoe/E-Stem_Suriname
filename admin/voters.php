<?php
require_once '../include/config.php';
require_once '../include/db_connect.php';
require_once '../include/admin_auth.php';
require_once '../src/controllers/VoterController.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: " . BASE_URL . "/admin/login.php");
    exit();
}

// Initialize controller
$voterController = new VoterController();

// Handle form submissions
$voterController->handleActions();

// Pagination settings
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get voters with optional filters
$search = $_GET['search'] ?? '';
$district_id = $_GET['district_id'] ?? '';
$resort_id = $_GET['resort_id'] ?? '';
$status = $_GET['status'] ?? '';

$filters = [
    'search' => $search,
    'district_id' => $district_id,
    'resort_id' => $resort_id,
    'status' => $status,
    'limit' => $perPage,
    'offset' => $offset
];

// Get voters and total count for pagination
$voters = $voterController->getAllVoters($filters);
$totalVoters = $voterController->getVoterCount($filters);
$totalPages = ceil($totalVoters / $perPage);

// Get districts for filter dropdown
$districts = $voterController->getAllDistricts();

// Check if we have imported voters to display
$importedVoters = $_SESSION['imported_voters'] ?? [];
unset($_SESSION['imported_voters']);

// Page title
$pageTitle = "Kiezersbeheer";

// Start output buffering
ob_start();
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Title -->
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Kiezersbeheer</h1>
        <div class="flex space-x-2">
            <button id="add-voter-btn" class="bg-suriname-green hover:bg-suriname-dark-green text-white px-4 py-2 rounded-lg transition-all duration-200 flex items-center">
                <i class="fas fa-plus mr-2"></i> Nieuwe Kiezer
            </button>
            <button id="import-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-all duration-200 flex items-center">
                <i class="fas fa-file-import mr-2"></i> Importeren
            </button>
        </div>
    </div>
    
    <!-- Display imported voters if any -->
    <?php if (!empty($importedVoters)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Succes!</strong>
            <span class="block sm:inline">De volgende kiezers zijn succesvol geïmporteerd:</span>
            <div class="mt-3 overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 border">Naam</th>
                            <th class="px-4 py-2 border">Kiezerscode</th>
                            <th class="px-4 py-2 border">Wachtwoord</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($importedVoters as $voter): ?>
                            <tr>
                                <td class="px-4 py-2 border"><?= htmlspecialchars($voter['first_name'] . ' ' . $voter['last_name']) ?></td>
                                <td class="px-4 py-2 border"><?= htmlspecialchars($voter['voter_code']) ?></td>
                                <td class="px-4 py-2 border"><?= htmlspecialchars($voter['password']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-sm">Bewaar deze gegevens op een veilige plaats. De wachtwoorden worden niet meer getoond.</p>
        </div>
    <?php endif; ?>
    
    <!-- Filter Form -->
    <form method="GET" class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Filters</h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div>
                    <label for="filter_search" class="block text-sm font-medium text-gray-700 mb-1">Zoeken</label>
                    <input type="text" id="filter_search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Naam, ID of kiezerscode" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                </div>
                <div>
                    <label for="filter_district_id" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                    <select id="filter_district_id" name="district_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                        <option value="">Alle districten</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?= $district['DistrictID'] ?>" <?= $district_id == $district['DistrictID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($district['DistrictName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_resort_id" class="block text-sm font-medium text-gray-700 mb-1">Resort</label>
                    <select id="filter_resort_id" name="resort_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                        <option value="">Selecteer district</option>
                    </select>
                </div>
                <div>
                    <label for="filter_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="filter_status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                        <option value="">Alle statussen</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actief</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactief</option>
                    </select>
                </div>
                <div class="flex justify-start">
                    <button type="submit" class="bg-suriname-green hover:bg-suriname-dark-green text-white px-4 py-2 rounded-lg transition-all duration-200 w-full">
                        <i class="fas fa-search mr-2"></i> Zoeken
                    </button>
                </div>
            </div>
        </div>
    </form>
    
    <!-- Voters Table -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Alle Kiezers</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Naam</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Nummer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kiezerscode</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resort</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($voters)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">Geen kiezers gevonden</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($voters as $voter): ?>
                            <tr class="hover-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($voter['first_name'] . ' ' . $voter['last_name']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($voter['id_number']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($voter['voter_code']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($voter['district_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($voter['resort_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $voter['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= ucfirst($voter['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <button class="edit-voter-btn text-blue-600 hover:text-blue-900" data-id="<?= $voter['id'] ?>" title="Bewerken">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="delete-voter-btn text-red-600 hover:text-red-900" data-id="<?= $voter['id'] ?>" title="Verwijderen">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Voter Modal -->
<div id="voter-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-xl font-semibold text-gray-900" id="modal-title">Nieuwe Kiezer Toevoegen</h3>
            <button id="close-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="voter-form" method="POST" action="" class="mt-4">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="voter_id" id="modal_voter_id" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="modal_first_name" class="block text-sm font-medium text-gray-700 mb-1">Voornaam</label>
                    <input type="text" id="modal_first_name" name="first_name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                </div>
                <div>
                    <label for="modal_last_name" class="block text-sm font-medium text-gray-700 mb-1">Achternaam</label>
                    <input type="text" id="modal_last_name" name="last_name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="modal_id_number" class="block text-sm font-medium text-gray-700 mb-1">ID Nummer</label>
                    <input type="text" id="modal_id_number" name="id_number" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                </div>
                <div>
                    <label for="modal_voter_code" class="block text-sm font-medium text-gray-700 mb-1">Kiezerscode (optioneel)</label>
                    <input type="text" id="modal_voter_code" name="voter_code" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="mb-4">
                    <label for="modal_district_id" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                    <select id="modal_district_id" name="district_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                        <option value="">Selecteer een district</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?= $district['DistrictID'] ?>"><?= htmlspecialchars($district['DistrictName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="modal_resort_id" class="block text-sm font-medium text-gray-700 mb-1">Resort</label>
                    <select id="modal_resort_id" name="resort_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                        <option value="">Selecteer eerst een district</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="modal_password" class="block text-sm font-medium text-gray-700 mb-1">Wachtwoord</label>
                    <input type="password" id="modal_password" name="password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <p class="text-xs text-gray-500 mt-1">Laat leeg om huidig wachtwoord te behouden (bij bewerken)</p>
                </div>
                <div>
                    <label for="modal_confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Bevestig Wachtwoord</label>
                    <input type="password" id="modal_confirm_password" name="confirm_password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                </div>
            </div>
            
            <div id="status-container" class="mb-4 hidden">
                <label for="modal_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="modal_status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <option value="active">Actief</option>
                    <option value="inactive">Inactief</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" id="cancel-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    Annuleren
                </button>
                <button type="submit" class="bg-suriname-green hover:bg-suriname-dark-green text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    <span id="submit-text">Kiezer Opslaan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-xl font-semibold text-gray-900">Kiezers importeren uit CSV</h3>
            <button id="close-import-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="import-form" method="POST" action="" enctype="multipart/form-data" class="mt-4">
            <input type="hidden" name="action" value="import">
            
            <div class="mb-4">
                <label for="csv_file" class="block text-sm font-medium text-gray-700 mb-1">CSV Bestand</label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
            </div>
            
            <div class="bg-blue-50 p-4 rounded-lg mb-4">
                <h4 class="text-sm font-semibold text-blue-800 mb-2">CSV Formaat Vereisten:</h4>
                <ul class="text-xs text-blue-700 list-disc list-inside">
                    <li>Verplichte kolommen: first_name, last_name, id_number, district_id, resort_id</li>
                    <li>Optionele kolommen: voter_code, password, status</li>
                    <li>Als password niet is opgegeven, wordt er een willekeurig wachtwoord gegenereerd</li>
                    <li>Als voter_code niet is opgegeven, wordt er een unieke code gegenereerd</li>
                    <li>district_id en resort_id moeten geldige ID's uit de database zijn</li>
                </ul>
            </div>
            
            <div class="mt-4 mb-4">
                <a href="<?= BASE_URL ?>/templates/voters_import_template.csv" download class="text-blue-600 hover:text-blue-800 text-sm">
                    <i class="fas fa-download mr-1"></i> Download voorbeeldsjabloon
                </a>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" id="cancel-import-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    Annuleren
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    Importeren
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-xl bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Kiezer verwijderen</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Weet u zeker dat u deze kiezer wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.
                </p>
            </div>
            <form id="delete-form" method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="voter_id" id="delete-voter-id" value="">
                
                <div class="flex justify-center space-x-3 mt-3">
                    <button type="button" id="cancel-delete-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                        Annuleren
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                        Verwijderen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
const CURRENT_RESORT_ID = '<?= $resort_id ?>';
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const voterModal = document.getElementById('voter-modal');
    const importModal = document.getElementById('import-modal');
    const deleteModal = document.getElementById('delete-modal');
    const addVoterBtn = document.getElementById('add-voter-btn');
    const importVotersBtn = document.getElementById('import-btn');
    const closeModalBtn = document.getElementById('close-modal');
    const closeImportModalBtn = document.getElementById('close-import-modal');
    const cancelBtn = document.getElementById('cancel-btn');
    const cancelImportBtn = document.getElementById('cancel-import-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const voterForm = document.getElementById('voter-form');
    
    // Modal form elements
    const modalDistrictSelect = document.getElementById('modal_district_id');
    const modalResortSelect = document.getElementById('modal_resort_id');
    const statusContainer = document.getElementById('status-container');
    
    // Filter elements
    const filterDistrictSelect = document.getElementById('filter_district_id');
    const filterResortSelect = document.getElementById('filter_resort_id');

    const editBtns = document.querySelectorAll('.edit-voter-btn');
    const deleteBtns = document.querySelectorAll('.delete-voter-btn');
    
    // Show add voter modal
    addVoterBtn.addEventListener('click', function() {
        document.getElementById('modal-title').textContent = 'Nieuwe Kiezer Toevoegen';
        document.getElementById('form-action').value = 'create';
        document.getElementById('submit-text').textContent = 'Kiezer Opslaan';
        voterForm.reset();
        modalResortSelect.innerHTML = '<option value="">Selecteer eerst een district</option>';
        statusContainer.classList.add('hidden');
        voterModal.classList.remove('hidden');
    });
    
    // Show import modal
    importVotersBtn.addEventListener('click', function() {
        importModal.classList.remove('hidden');
    });
    
    // Close modals
    closeModalBtn.addEventListener('click', function() {
        voterModal.classList.add('hidden');
    });
    
    closeImportModalBtn.addEventListener('click', function() {
        importModal.classList.add('hidden');
    });
    
    cancelBtn.addEventListener('click', function() {
        voterModal.classList.add('hidden');
    });
    
    cancelImportBtn.addEventListener('click', function() {
        importModal.classList.add('hidden');
    });
    
    cancelDeleteBtn.addEventListener('click', function() {
        deleteModal.classList.add('hidden');
    });
    
    // Edit voter
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const voterId = this.dataset.id;
            
            // Fetch voter data via AJAX
            fetch(`${BASE_URL}/src/api/get_voter.php?id=${voterId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const voter = data.voter;
                        
                        document.getElementById('modal-title').textContent = 'Kiezer Bewerken';
                        document.getElementById('form-action').value = 'update';
                        document.getElementById('submit-text').textContent = 'Wijzigingen Opslaan';
                        document.getElementById('modal_voter_id').value = voter.id;
                        document.getElementById('modal_first_name').value = voter.first_name;
                        document.getElementById('modal_last_name').value = voter.last_name;
                        document.getElementById('modal_id_number').value = voter.id_number;
                        document.getElementById('modal_voter_code').value = voter.voter_code;
                        modalDistrictSelect.value = voter.district_id;
                        
                        // Load resorts for the selected district
                        loadResorts(voter.district_id, modalResortSelect, voter.resort_id);
                        
                        document.getElementById('modal_status').value = voter.status;
                        statusContainer.classList.remove('hidden');
                        
                        // Clear password fields for edit
                        document.getElementById('modal_password').value = '';
                        document.getElementById('modal_confirm_password').value = '';
                        
                        voterModal.classList.remove('hidden');
                    } else {
                        alert('Fout bij laden van kiezergegevens: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Er is een fout opgetreden bij het laden van de kiezergegevens.');
                });
        });
    });
    
    // Delete voter
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const voterId = this.dataset.id;
            document.getElementById('delete-voter-id').value = voterId;
            deleteModal.classList.remove('hidden');
        });
    });
    
    // Load resorts for modal when district changes
    modalDistrictSelect.addEventListener('change', function() {
        const districtId = this.value;
        if (districtId) {
            loadResorts(districtId, modalResortSelect);
        } else {
            modalResortSelect.innerHTML = '<option value="">Selecteer eerst een district</option>';
        }
    });

    // Load resorts for filter when district changes
    filterDistrictSelect.addEventListener('change', function() {
        const districtId = this.value;
        if (districtId) {
            loadResorts(districtId, filterResortSelect);
        } else {
            filterResortSelect.innerHTML = '<option value="">Selecteer district</option>';
        }
    });

    // On page load, if a filter district is selected, load its resorts
    if (filterDistrictSelect.value) {
        loadResorts(filterDistrictSelect.value, filterResortSelect, CURRENT_RESORT_ID);
    }
    
    // Function to load resorts by district
    function loadResorts(districtId, resortSelectElement, selectedResortId = null) {
        const defaultText = resortSelectElement.id === 'modal_resort_id' 
            ? 'Selecteer een resort' 
            : 'Alle resorts';

        resortSelectElement.innerHTML = `<option value="">Laden...</option>`;
        resortSelectElement.disabled = true;

        fetch(`${BASE_URL}/src/api/get_resorts.php?district_id=${districtId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                resortSelectElement.disabled = false;
                if (data.success) {
                    resortSelectElement.innerHTML = `<option value="">${defaultText}</option>`;
                    
                    data.resorts.forEach(resort => {
                        const option = document.createElement('option');
                        option.value = resort.ResortID;
                        option.textContent = resort.ResortName;
                        
                        if (selectedResortId && resort.ResortID == selectedResortId) {
                            option.selected = true;
                        }
                        
                        resortSelectElement.appendChild(option);
                    });
                } else {
                    resortSelectElement.innerHTML = `<option value="">Geen resorts</option>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resortSelectElement.disabled = false;
                resortSelectElement.innerHTML = '<option value="">Laden mislukt</option>';
            });
    }
});
</script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout
include_once 'components/layout.php';
?>

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
$status = $_GET['status'] ?? '';

$filters = [
    'search' => $search,
    'district_id' => $district_id,
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Zoeken</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Naam, ID of kiezerscode" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
            </div>
            <div>
                <label for="district_id" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                <select id="district_id" name="district_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <option value="">Alle districten</option>
                    <?php foreach ($districts as $district): ?>
                        <option value="<?= $district['DistrictID'] ?>" <?= $district_id == $district['DistrictID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($district['DistrictName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <option value="">Alle statussen</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actief</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactief</option>
                </select>
            </div>
            <div class="flex justify-end mt-4">
                <button type="submit" class="bg-suriname-green hover:bg-suriname-dark-green text-white px-4 py-2 rounded-lg transition-all duration-200">
                    <i class="fas fa-search mr-2"></i> Zoeken
                </button>
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
                                        <button class="generate-qr-btn text-green-600 hover:text-green-900" data-id="<?= $voter['id'] ?>" title="QR Code genereren">
                                            <i class="fas fa-qrcode"></i>
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
            <h3 class="text-xl font-semibold text-gray-900" id="modal-title">Add New Voter</h3>
            <button id="close-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="voter-form" method="POST" action="" class="mt-4">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="voter_id" id="voter-id" value="">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                    <input type="text" id="first_name" name="first_name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                </div>
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="id_number" class="block text-sm font-medium text-gray-700 mb-1">ID Number</label>
                    <input type="text" id="id_number" name="id_number" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                </div>
                <div>
                    <label for="voter_code" class="block text-sm font-medium text-gray-700 mb-1">Voter Code (optional)</label>
                    <input type="text" id="voter_code" name="voter_code" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="mb-4">
                    <label for="district_id" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                    <select id="district_id" name="district_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                        <option value="">Selecteer een district</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?= $district['DistrictID'] ?>"><?= htmlspecialchars($district['DistrictName']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="resort_id" class="block text-sm font-medium text-gray-700 mb-1">Resort</label>
                    <select id="resort_id" name="resort_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                        <option value="">Selecteer eerst een district</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to keep current password (for editing)</p>
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                </div>
            </div>
            
            <div id="status-container" class="mb-4 hidden">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status" name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" id="cancel-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    Cancel
                </button>
                <button type="submit" class="bg-suriname-green hover:bg-suriname-dark-green text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    <span id="submit-text">Save Voter</span>
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
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Delete Voter</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete this voter? This action cannot be undone.
                </p>
            </div>
            <form id="delete-form" method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="voter_id" id="delete-voter-id" value="">
                
                <div class="flex justify-center space-x-3 mt-3">
                    <button type="button" id="cancel-delete-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                        Cancel
                    </button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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
    const districtSelect = document.getElementById('district_id');
    const resortSelect = document.getElementById('resort_id');
    const statusContainer = document.getElementById('status-container');
    const editBtns = document.querySelectorAll('.edit-voter-btn');
    const deleteBtns = document.querySelectorAll('.delete-voter-btn');
    const generateQrBtns = document.querySelectorAll('.generate-qr-btn');
    
    // Show add voter modal
    addVoterBtn.addEventListener('click', function() {
        document.getElementById('modal-title').textContent = 'Add New Voter';
        document.getElementById('form-action').value = 'create';
        document.getElementById('submit-text').textContent = 'Save Voter';
        voterForm.reset();
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
                        
                        document.getElementById('modal-title').textContent = 'Edit Voter';
                        document.getElementById('form-action').value = 'update';
                        document.getElementById('submit-text').textContent = 'Update Voter';
                        document.getElementById('voter-id').value = voter.id;
                        document.getElementById('first_name').value = voter.first_name;
                        document.getElementById('last_name').value = voter.last_name;
                        document.getElementById('id_number').value = voter.id_number;
                        document.getElementById('voter_code').value = voter.voter_code;
                        document.getElementById('district_id').value = voter.district_id;
                        
                        // Load resorts for the selected district
                        loadResorts(voter.district_id, voter.resort_id);
                        
                        document.getElementById('status').value = voter.status;
                        statusContainer.classList.remove('hidden');
                        
                        // Clear password fields for edit
                        document.getElementById('password').value = '';
                        document.getElementById('confirm_password').value = '';
                        
                        voterModal.classList.remove('hidden');
                    } else {
                        alert('Error loading voter data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while loading voter data.');
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
    
    // Generate QR code
    generateQrBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const voterId = this.dataset.id;
            
            // Redirect to QR code generation page
            window.location.href = `${BASE_URL}/admin/qrcodes.php?generate_for=${voterId}`;
        });
    });
    
    // Load resorts when district changes
    districtSelect.addEventListener('change', function() {
        const districtId = this.value;
        if (districtId) {
            loadResorts(districtId);
        } else {
            resortSelect.innerHTML = '<option value="">Select District First</option>';
        }
    });
    
    // Function to load resorts by district
    function loadResorts(districtId, selectedResortId = null) {
        fetch(`${BASE_URL}/src/api/get_resorts.php?district_id=${districtId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resortSelect.innerHTML = '<option value="">Selecteer een resort</option>';
                    
                    data.resorts.forEach(resort => {
                        const option = document.createElement('option');
                        option.value = resort.ResortID;
                        option.textContent = resort.ResortName;
                        
                        if (selectedResortId && resort.ResortID == selectedResortId) {
                            option.selected = true;
                        }
                        
                        resortSelect.appendChild(option);
                    });
                } else {
                    resortSelect.innerHTML = '<option value="">Geen resorts gevonden</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resortSelect.innerHTML = '<option value="">Fout bij het laden van resorts</option>';
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

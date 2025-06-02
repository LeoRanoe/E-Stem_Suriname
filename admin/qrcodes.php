<?php
require_once '../include/config.php';
require_once '../include/db_connect.php';
require_once '../include/admin_auth.php';
require_once '../src/controllers/QrCodeController.php';
require_once '../src/controllers/VoterController.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header("Location: " . BASE_URL . "/admin/login.php");
    exit();
}

// Initialize controllers
$qrController = new QrCodeController();
$voterController = new VoterController();

// Handle form submissions
$qrController->handleActions();

// Get vouchers with optional filters
$search = $_GET['search'] ?? '';
$used = isset($_GET['used']) ? $_GET['used'] : '';
$district_id = $_GET['district_id'] ?? '';

$filters = [
    'search' => $search,
    'used' => $used,
    'district_id' => $district_id
];

$vouchers = $qrController->getAllVouchers($filters);
$districts = $voterController->getAllDistricts();

// Check if we need to generate a QR for a specific voter
$generateFor = $_GET['generate_for'] ?? null;
$voterForQR = null;
if ($generateFor) {
    $voterForQR = $voterController->getVoterById($generateFor);
}

// Page title
$pageTitle = "QR Voucher Management";

// Start output buffering
ob_start();
?>

<!-- Main Content -->
<div class="container px-6 py-8 mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">QR Voucher Management</h1>
        <div class="flex space-x-2">
            <button id="generate-single-btn" class="bg-suriname-green hover:bg-suriname-dark-green text-white px-4 py-2 rounded-lg transition-all duration-200 flex items-center">
                <i class="fas fa-qrcode mr-2"></i> Generate Single QR
            </button>
            <button id="generate-bulk-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-all duration-200 flex items-center">
                <i class="fas fa-qrcode mr-2"></i> Generate Bulk QRs
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form action="" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Voucher ID or Voter Name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
            </div>
            
            <div class="w-48">
                <label for="district_id" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                <select id="district_id" name="district_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <option value="">All Districts</option>
                    <?php foreach ($districts as $district): ?>
                        <option value="<?= $district['DistrictID'] ?>" <?= $district_id == $district['DistrictID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($district['DistrictName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="w-48">
                <label for="used" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="used" name="used" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <option value="">All Statuses</option>
                    <option value="0" <?= $used === '0' ? 'selected' : '' ?>>Unused</option>
                    <option value="1" <?= $used === '1' ? 'selected' : '' ?>>Used</option>
                </select>
            </div>
            
            <div>
                <button type="submit" class="bg-suriname-green hover:bg-suriname-dark-green text-white px-4 py-2 rounded-lg transition-all duration-200">
                    <i class="fas fa-search mr-2"></i> Filter
                </button>
            </div>
            
            <?php if (!empty($search) || $used !== '' || !empty($district_id)): ?>
                <div>
                    <a href="<?= BASE_URL ?>/admin/qrcodes.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-all duration-200 inline-block">
                        <i class="fas fa-times mr-2"></i> Clear
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Vouchers Table -->
    <div class="bg-white overflow-hidden shadow-md rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Voucher ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Voter</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resort</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($vouchers)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No vouchers found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vouchers as $voucher): ?>
                            <tr class="hover-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($voucher['voucher_id']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars($voucher['first_name'] . ' ' . $voucher['last_name']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($voucher['district_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($voucher['resort_name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $voucher['used'] == 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $voucher['used'] == 0 ? 'Unused' : 'Used' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">
                                        <?= date('M d, Y', strtotime($voucher['created_at'])) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <button class="view-qr-btn text-blue-600 hover:text-blue-900" data-id="<?= $voucher['id'] ?>" data-voucher="<?= htmlspecialchars($voucher['voucher_id']) ?>">
                                            <i class="fas fa-qrcode"></i>
                                        </button>
                                        <?php if ($voucher['used'] == 0): ?>
                                            <button class="delete-voucher-btn text-red-600 hover:text-red-900" data-id="<?= $voucher['id'] ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Generate Single QR Modal -->
<div id="single-qr-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-xl font-semibold text-gray-900">Generate QR Code</h3>
            <button id="close-single-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="single-qr-form" method="POST" action="" class="mt-4">
            <input type="hidden" name="action" value="generate_single">
            
            <div class="mb-4">
                <label for="voter_id" class="block text-sm font-medium text-gray-700 mb-1">Select Voter</label>
                <select id="voter_id" name="voter_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <option value="">Select a voter</option>
                    <!-- Options will be loaded via AJAX -->
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" id="cancel-single-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    Cancel
                </button>
                <button type="submit" class="bg-suriname-green hover:bg-suriname-dark-green text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    Generate QR
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Generate Bulk QR Modal -->
<div id="bulk-qr-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-xl font-semibold text-gray-900">Generate Bulk QR Codes</h3>
            <button id="close-bulk-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="bulk-qr-form" method="POST" action="" class="mt-4">
            <input type="hidden" name="action" value="generate_bulk">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Voters</label>
                <div class="max-h-60 overflow-y-auto border rounded-md p-2">
                    <div id="voter-checkboxes" class="space-y-2">
                        <!-- Checkboxes will be loaded via AJAX -->
                        <p class="text-gray-500 text-sm">Loading voters...</p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" id="cancel-bulk-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    Cancel
                </button>
                <button type="submit" class="bg-suriname-green hover:bg-suriname-dark-green text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    Generate QR Codes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View QR Modal -->
<div id="view-qr-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-xl font-semibold text-gray-900">QR Code</h3>
            <button id="close-view-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mt-4 text-center">
            <div id="qr-code-container" class="mb-4">
                <!-- QR code will be displayed here -->
            </div>
            
            <div class="mb-4">
                <p class="text-sm text-gray-700">Voucher ID: <span id="qr-voucher-id" class="font-semibold"></span></p>
            </div>
            
            <div class="flex justify-center space-x-3 mt-6">
                <button type="button" id="print-qr-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
                <button type="button" id="download-qr-btn" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                    <i class="fas fa-download mr-2"></i> Download
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Delete Voucher</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Are you sure you want to delete this voucher? This action cannot be undone.
                </p>
            </div>
            <form id="delete-form" method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="voucher_id" id="delete-voucher-id" value="">
                
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

<!-- Include QR code library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>

<!-- Set global variables for JavaScript -->
<script>
// Define global variables for the QR code functionality
window.BASE_URL = '<?= BASE_URL ?>';
window.generateFor = <?= json_encode($generateFor) ?>;
window.autoOpenSingleQrModal = <?= json_encode($generateFor !== null) ?>;
console.log('QR Code variables initialized:', {
    BASE_URL: window.BASE_URL,
    generateFor: window.generateFor,
    autoOpenSingleQrModal: window.autoOpenSingleQrModal
});
</script>

<!-- Include the external QR code JavaScript file -->
<script src="<?= BASE_URL ?>/admin/qrcodes.js"></script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout
include_once 'components/layout.php';
?>

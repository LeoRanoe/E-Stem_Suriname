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
            <button id="download-all-btn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-all duration-200 flex items-center">
                <i class="fas fa-download mr-2"></i> Download All QRs
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by ID or voter name" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
            </div>
            <div>
                <label for="district_id" class="block text-sm font-medium text-gray-700 mb-1">District</label>
                <select name="district_id" id="district_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <option value="">All Districts</option>
                    <?php foreach ($districts as $district): ?>
                        <option value="<?= $district['DistrictID'] ?>" <?= $district_id == $district['DistrictID'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($district['DistrictName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="used" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="used" id="used" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <option value="">All Statuses</option>
                    <option value="0" <?= $used === '0' ? 'selected' : '' ?>>Unused</option>
                    <option value="1" <?= $used === '1' ? 'selected' : '' ?>>Used</option>
                </select>
            </div>
            <div class="md:col-span-3 flex justify-end">
                <button type="submit" class="bg-suriname-green hover:bg-suriname-dark-green text-white px-4 py-2 rounded-lg transition-all duration-200">
                    <i class="fas fa-search mr-2"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Vouchers Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">QR Vouchers</h2>
        </div>
        
        <!-- Display success message if any -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 mx-4 mt-4">
                <p><?= $_SESSION['success_message'] ?></p>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <!-- Display error message if any -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 mx-4 mt-4">
                <p><?= $_SESSION['error_message'] ?></p>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Bulk Actions -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center space-x-2">
                <input type="checkbox" id="select-all-vouchers" class="h-4 w-4 text-suriname-green focus:ring-suriname-green border-gray-300 rounded">
                <label for="select-all-vouchers" class="text-sm font-medium text-gray-700">Select All</label>
                <button id="bulk-print-btn" class="ml-4 bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hidden">
                    <i class="fas fa-print mr-1"></i> Print Selected
                </button>
                <button id="bulk-download-btn" class="ml-2 bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hidden">
                    <i class="fas fa-download mr-1"></i> Download Selected
                </button>
                <button id="bulk-delete-btn" class="ml-2 bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hidden">
                    <i class="fas fa-trash mr-1"></i> Delete Selected
                </button>
            </div>
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-8">
                            <span class="sr-only">Select</span>
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Voucher ID
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Voter
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            District
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Created
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($vouchers)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                No vouchers found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vouchers as $voucher): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="voucher_ids[]" value="<?= $voucher['id'] ?>" 
                                           data-voucher-id="<?= $voucher['voucher_id'] ?>"
                                           class="voucher-checkbox h-4 w-4 text-suriname-green focus:ring-suriname-green border-gray-300 rounded">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($voucher['voucher_id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($voucher['first_name'] . ' ' . $voucher['last_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($voucher['district_name'] ?? 'Unknown') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $voucher['used'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' ?>">
                                        <?= $voucher['used'] ? 'Used' : 'Unused' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('Y-m-d H:i', strtotime($voucher['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="view-qr-btn text-blue-600 hover:text-blue-900" data-voucher="<?= htmlspecialchars($voucher['voucher_id']) ?>" title="View QR Code">
                                        <i class="fas fa-qrcode"></i>
                                    </button>
                                    <button class="delete-voucher-btn text-red-600 hover:text-red-900 ml-3" data-id="<?= $voucher['id'] ?>" title="Delete Voucher">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Single QR Modal -->
<div id="single-qr-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-medium text-gray-900">Generate QR Code for Voter</h3>
            <button id="close-single-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="single-qr-form" method="POST" action="">
            <input type="hidden" name="action" value="generate_single">
            
            <div class="mt-4">
                <label for="voter_id" class="block text-sm font-medium text-gray-700 mb-1">Select Voter</label>
                <select id="voter_id" name="voter_id" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                    <option value="">Select a voter</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" id="cancel-single-btn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-suriname-green text-white rounded-md hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-suriname-green focus:ring-opacity-50">
                    Generate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk QR Modal -->
<div id="bulk-qr-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-medium text-gray-900">Generate Bulk QR Codes</h3>
            <button id="close-bulk-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="bulk-qr-form" method="POST" action="">
            <input type="hidden" name="action" value="generate_bulk">
            
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Voters</label>
                <div class="border rounded-md p-3 max-h-60 overflow-y-auto" id="voter-checkboxes">
                    <p class="text-gray-500 text-sm">Loading voters...</p>
                </div>
            </div>
            
            <div class="mt-4 text-sm text-gray-500">
                <p>You can filter voters by district using the dropdown below:</p>
                <div class="mt-2">
                    <select id="filter-district" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring focus:ring-suriname-green focus:ring-opacity-50">
                        <option value="">All Districts</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?= $district['DistrictID'] ?>">
                                <?= htmlspecialchars($district['DistrictName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mt-4">
                <div class="flex items-center">
                    <input type="checkbox" id="select-all-voters" class="h-4 w-4 text-suriname-green focus:ring-suriname-green border-gray-300 rounded">
                    <label for="select-all-voters" class="ml-2 block text-sm text-gray-900">Select All Voters</label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" id="cancel-bulk-btn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-suriname-green text-white rounded-md hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-suriname-green focus:ring-opacity-50">
                    Generate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View QR Modal -->
<div id="view-qr-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-medium text-gray-900">QR Code</h3>
            <button id="close-view-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mt-4 text-center">
            <p class="text-sm text-gray-500 mb-2">Voucher ID: <span id="qr-voucher-id"></span></p>
            <div id="qr-code-container" class="mx-auto my-4"></div>
            <div class="flex justify-center space-x-3 mt-4">
                <button id="print-qr-btn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                    <i class="fas fa-print mr-2"></i> Print
                </button>
                <button id="download-qr-btn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
                    <i class="fas fa-download mr-2"></i> Download
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Delete Voucher</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">Are you sure you want to delete this voucher? This action cannot be undone.</p>
            </div>
            <form id="delete-form" method="POST" action="">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="voucher_id" id="delete-voucher-id" value="">
                
                <div class="flex justify-center space-x-3 mt-3">
                    <button type="button" id="cancel-delete-btn" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk QR Download Modal -->
<div id="bulk-download-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-lg font-medium text-gray-900">Download QR Codes</h3>
            <button id="close-bulk-download-modal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mt-4 text-center">
            <div id="bulk-download-status" class="mb-4 text-blue-600">
                <i class="fas fa-spinner fa-spin mr-2"></i> Preparing QR codes for download...
            </div>
            <div id="bulk-download-progress" class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                <div class="bg-suriname-green h-2.5 rounded-full" style="width: 0%"></div>
            </div>
            <div id="bulk-download-complete" class="hidden">
                <p class="text-green-600 mb-4"><i class="fas fa-check-circle mr-2"></i> QR codes are ready for download!</p>
                <a id="bulk-download-link" href="#" class="px-4 py-2 bg-suriname-green text-white rounded-md hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-suriname-green focus:ring-opacity-50">
                    <i class="fas fa-download mr-2"></i> Download ZIP File
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Set global variables for JavaScript
window.BASE_URL = '<?= BASE_URL ?>';
window.generateFor = <?= $generateFor ? $generateFor : 'null' ?>;
window.autoOpenSingleQrModal = <?= $generateFor ? 'true' : 'false' ?>;

console.log('QR Code variables initialized:', {
    BASE_URL: window.BASE_URL,
    generateFor: window.generateFor,
    autoOpenSingleQrModal: window.autoOpenSingleQrModal
});
</script>

<!-- Include the external QR code JavaScript file -->
<script src="<?= BASE_URL ?>/assets/js/qrcodes.js"></script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout
include_once 'components/layout.php';
?>

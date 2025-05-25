<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/../../include/admin_auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../src/controllers/QrCodeController.php';
require_once __DIR__ . '/../../src/controllers/ElectionController.php';

requireAdmin();

// Initialize controller with namespace
$qrCodeController = new \App\Controllers\QrCodeController();

// Get QR code data
$qrCodes = $qrCodeController->getQrCodes();
$stats = $qrCodeController->getQrCodeStats();

// Start output buffering
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Management</title>
    <!-- Remove CDN reference and use local build -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'suriname-green': '#2e7d32',
                        'suriname-dark-green': '#1b5e20'
                    }
                }
            }
        }
    </script>
</head>
<body>
    <?php include __DIR__ . '/../../admin/components/nav.php'; ?>
    
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">QR Code Management</h1>
            <p class="text-gray-600">Overzicht en beheer van alle QR codes.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                    <i class="fas fa-qrcode text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Totaal QR Codes</p>
                    <p class="text-3xl font-semibold text-suriname-green mt-1"><?= $stats['total_codes'] ?></p>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-lg bg-green-100 text-green-600">
                    <i class="fas fa-check-circle text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Actief</p>
                    <p class="text-3xl font-semibold text-green-600 mt-1">
                        <?= $stats['status_counts'][0]['count'] ?? 0 ?>
                    </p>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-lg bg-blue-100 text-blue-600">
                    <i class="fas fa-exchange-alt text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Gebruikt</p>
                    <p class="text-3xl font-semibold text-blue-600 mt-1">
                        <?= $stats['status_counts'][1]['count'] ?? 0 ?>
                    </p>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-lg bg-gray-100 text-gray-600">
                    <i class="fas fa-clock text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Verlopen</p>
                    <p class="text-3xl font-semibold text-gray-600 mt-1">
                        <?= $stats['status_counts'][2]['count'] ?? 0 ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 mb-8">
            <div class="border-b border-gray-200 pb-4 mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Filters</h2>
            </div>
            <form class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                    <input type="text" id="search" name="search" 
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green">
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select id="status" name="status" 
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green">
                        <option value="all">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="used">Used</option>
                    </select>
                </div>
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700">From Date</label>
                    <input type="date" id="date_from" name="date_from" 
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green">
                </div>
                <div class="flex items-end">
                    <button type="submit" 
                            class="bg-suriname-green hover:bg-suriname-dark-green text-white font-semibold py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green">
                        Apply
                    </button>
                </div>
            </form>
        </div>

        <!-- QR Codes Table -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">QR Codes</h2>
                <div class="flex space-x-2">
                    <button id="generateQrBtn"
                            class="bg-suriname-green hover:bg-suriname-dark-green text-white font-semibold py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green flex items-center">
                        <i class="fas fa-plus mr-2"></i>Generate QR Codes
                    </button>
                    <button id="importUsersBtn"
                            class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 flex items-center">
                        <i class="fas fa-file-csv mr-2"></i>Import CSV
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Election ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($qrCodes as $qrCode): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($qrCode['QRCodeID']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?= htmlspecialchars(substr($qrCode['QRCode'], 0, 8)) ?>...
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($qrCode['UserID']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= htmlspecialchars($qrCode['ElectionID']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    <?= $qrCode['Status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                       ($qrCode['Status'] === 'used' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') ?>">
                                    <?= htmlspecialchars(ucfirst($qrCode['Status'])) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?= date('d M Y', strtotime($qrCode['CreatedAt'])) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <a href="#" class="text-blue-600 hover:text-blue-800 transition-colors duration-150" title="View">
                                    <i class="fas fa-eye fa-fw"></i>
                                </a>
                                <a href="#" class="text-gray-600 hover:text-gray-800 transition-colors duration-150" title="Export">
                                    <i class="fas fa-download fa-fw"></i>
                                </a>
                                <a href="#" class="text-red-600 hover:text-red-800 transition-colors duration-150 confirm-delete" title="Delete">
                                    <i class="fas fa-trash fa-fw"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- New QR Code Modal -->
    <div id="qrModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-700 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-suriname-green/10 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-qrcode text-suriname-green text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Generate New QR Codes
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="electionId" class="block text-sm font-medium text-gray-700">Election</label>
                                    <select id="electionId" name="election_id" required
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm">
                                        <option value="">Select Election</option>
                                        <!-- Options will be loaded dynamically -->
                                    </select>
                                </div>
                                <div>
                                    <label for="userCount" class="block text-sm font-medium text-gray-700">Number of Codes</label>
                                    <input type="number" id="userCount" name="user_count" min="1" max="1000" value="1"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirmGenerateBtn"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-suriname-green text-base font-medium text-white hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-150">
                        Generate
                    </button>
                    <button type="button" id="cancelQrModalBtn"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-150">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Include modal component -->
    <?php include __DIR__ . '/components/modal.php'; ?>
    <!-- Import Modal -->
    <div id="importModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="importModalTitle" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-700 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-file-csv text-blue-600 text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="importModalTitle">
                                Import Data from CSV
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="targetTable" class="block text-sm font-medium text-gray-700">Target Table</label>
                                    <select id="targetTable" name="target_table" required data-testid="target-table-select"
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Select Table</option>
                                        <option value="voters">Voters</option>
                                        <option value="admins">Admins</option>
                                        <option value="candidates">Candidates</option>
                                        <option value="districten">Districts</option>
                                        <option value="elections">Elections</option>
                                        <option value="parties">Parties</option>
                                        <option value="qrcodes">QR Codes</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="csvFile" class="block text-sm font-medium text-gray-700">Select CSV File</label>
                                    <input type="file" id="csvFile" name="csv_file" accept=".csv" required
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <p class="mt-2 text-sm text-gray-500">
                                        CSV must match the structure of the selected table
                                    </p>
                                </div>
                                <div id="statusMessages" class="hidden bg-gray-100 p-4 rounded-md">
                                    <p id="importStatus" class="font-medium"></p>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                                        <div id="importProgress" class="bg-blue-600 h-2.5 rounded-full" style="width:0%"></div>
                                    </div>
                                </div>
                                <div id="importProgress" class="hidden">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div id="progressBar" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
                                    </div>
                                    <p id="progressText" class="text-sm text-gray-600 mt-2">0% Complete</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirmImportBtn"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-150">
                        Import
                    </button>
                    <button type="button" id="cancelImportBtn"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Load required JavaScript libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script src="../../assets/js/qrcodes.js"></script>
    
    <script>
    $(document).ready(function() {
        // Enhanced debug logging
        console.log('QR Codes page initialized');
        
        // Log all form submissions
        $('form').on('submit', function(e) {
            console.log('Form submitted:', {
                formId: $(this).attr('id') || 'unknown',
                formData: $(this).serialize()
            });
        });
        
        // Debug: Log selected table value changes
        $('#targetTable').on('change', function() {
            console.log('Selected table:', $(this).val());
        });
        
        // Handle CSV import form submission
        $('#confirmImportBtn').on('click', function() {
            console.log('Starting CSV import process');
            const formData = new FormData();
            
            // Log form validation
            console.log('Validating import form...');
            const fileInput = $('#csvFile')[0];
            const targetTable = $('#targetTable').val();
            
            if (!targetTable) {
                $('#statusMessages').removeClass('hidden');
                $('#importStatus').html('<span class="text-red-600 font-bold">Error:</span> Please select a target table');
                return;
            }
            
            if (fileInput.files.length === 0) {
                $('#statusMessages').removeClass('hidden');
                $('#importStatus').html('<span class="text-red-600 font-bold">Error:</span> Please select a CSV file');
                return;
            }
            
            // Debug: Log form data before submission
            console.log('Form data:', {
                targetTable: targetTable,
                fileName: fileInput.files[0]?.name,
                fileSize: fileInput.files[0]?.size
            });
            
            // Add all required fields to FormData
            formData.append('file', fileInput.files[0]);
            formData.append('target_table', targetTable);
            formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
            formData.append('import_type', 'bulk');
            
            $('#statusMessages').removeClass('hidden');
            $('#importStatus').html('<span class="text-blue-600 font-bold">Status:</span> Uploading and processing CSV file...');
            
            console.log('Submitting import request to endpoint: ../../src/ajax/import-user.php');
            $.ajax({
                url: '../../src/ajax/import-user.php', // Verified correct endpoint
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            $('#importProgress').css('width', percentComplete + '%');
                            $('#importStatus').text(`Uploading: ${Math.round(percentComplete)}% complete`);
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        $('#importStatus').html(`<span class="text-green-600 font-bold">Success:</span> ${response.message}`);
                        $('#importProgress').addClass('bg-green-600');
                        
                        // Smooth table refresh using AJAX
                        $.get('../../src/ajax/get-qrcodes.php', function(data) {
                            $('tbody').html(data);
                            $('#importModal').addClass('hidden');
                        }).fail(function() {
                            // Fallback to full reload if AJAX fails
                            setTimeout(() => {
                                $('#importModal').addClass('hidden');
                                location.reload();
                            }, 2000);
                        });
                    } else {
                        $('#importStatus').html(`<span class="text-red-600 font-bold">Error:</span> ${response.message}`);
                        $('#importProgress').addClass('bg-red-600');
                        
                        // Show detailed error in modal if available
                        if (response.details) {
                            showModal('Import Error', response.details);
                        }
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Upload failed';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMsg = response.message;
                        }
                    } catch (e) {}
                    $('#importStatus').text('Error: ' + errorMsg);
                    $('#importProgress').addClass('bg-red-600');
                }
            });
        });
    });
    </script>
</body>
</body>
</html>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once __DIR__ . '/../../admin/components/layout.php';
?>
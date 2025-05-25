<?php
/**
 * Modal Component for QR Code Management
 * 
 * Handles import and generation modals
 * 
 * @package QRCodeManagement
 */

// Security check
if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}
?>
<!-- Import Users Modal -->
<div class="fixed z-10 inset-0 overflow-y-auto hidden" id="importModal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Import Users</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500">Select a CSV file containing voter data to import.</p>
                    <div class="mt-4">
                        <label for="importElectionSelect" class="block text-sm font-medium text-gray-700 mb-2">Select Election (Optional)</label>
                        <select id="importElectionSelect" name="election_id" class="w-full rounded-md border border-gray-300 shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">No Election Selected</option>
                            <?php
                            // Fetch active elections
                            $stmt = $pdo->query("SELECT ElectionID, ElectionName FROM elections WHERE Status = 'active' ORDER BY ElectionName");
                            while ($row = $stmt->fetch()) {
                                echo '<option value="' . htmlspecialchars($row['ElectionID']) . '">' . htmlspecialchars($row['ElectionName']) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">If an election is selected, QR codes will be generated for imported users.</p>
                    </div>
                    <input type="file" id="csvFile" name="import_file" accept=".csv" class="mt-4 block w-full text-sm text-gray-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-blue-50 file:text-blue-700
                        hover:file:bg-blue-100">
                    <p class="mt-2 text-sm text-gray-500">CSV must contain these columns: Voornaam,Achternaam,Email,IDNumber,DistrictID</p>
                    <div class="bg-gray-50 p-4 rounded-md mt-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Example CSV format:</h4>
                        <code class="text-xs text-gray-600 block whitespace-pre">Voornaam,Achternaam,Email,IDNumber,DistrictID
John,Doe,john@example.com,12345,1
Jane,Smith,jane@example.com,67890,2</code>
                    </div>
                </div>
            </div>
            <div id="importError" class="hidden px-4 py-3 bg-red-50 text-red-700 text-sm rounded-md"></div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" id="confirmImportBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                    <i class="fas fa-file-import mr-2"></i> Import
                </button>
                <button type="button" id="cancelImportBtn" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CSRF Token for modal forms -->
<input type="hidden" id="csrfToken" name="csrfToken" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
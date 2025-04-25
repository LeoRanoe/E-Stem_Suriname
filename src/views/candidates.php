<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../controllers/CandidateController.php';

// Check if user is logged in and is admin
requireAdmin();

$controller = new CandidateController();

// Get data for the view
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10; // Or get from config
$candidates = $controller->getCandidates($page, $per_page);
$total_candidates = $controller->getTotalCandidatesCount();
$total_pages = ceil($total_candidates / $per_page);
$formData = $controller->getFormData();
$elections = $formData['elections'];
$parties = $formData['parties'];
$districts = $formData['districts'];


// Start output buffering
ob_start();
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($candidates)): ?>
                 <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        Geen kandidaten gevonden
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($candidates as $candidate): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php
                                $imageSrc = 'https://via.placeholder.com/40'; // Default placeholder
                                if (isset($candidate['Photo']) && !empty(trim($candidate['Photo']))) { // Changed ImagePath to Photo
                                    // Trim whitespace, remove leading/trailing slashes, then urlencode path segments
                                    $trimmedPath = trim($candidate['Photo'], " \t\n\r\0\x0B/"); // Changed ImagePath to Photo
                                    $pathSegments = explode('/', $trimmedPath);
                                    $encodedPath = implode('/', array_map('rawurlencode', $pathSegments));
                                    // Ensure BASE_URL ends with a slash and path doesn't start with one
                                    $imageSrc = rtrim(BASE_URL, '/') . '/' . $encodedPath;
                                }
                            ?>
                            <img src="<?= htmlspecialchars($imageSrc) ?>"
                                 alt="<?= htmlspecialchars($candidate['Name'] ?? 'Unknown Candidate') ?>"
                                 class="h-10 w-10 rounded-full object-cover">
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
                            <?= htmlspecialchars($candidate['DistrictName'] ?? 'N/A') ?> <!-- Display district name -->
                        </td>
                         <td class="px-6 py-4 whitespace-nowrap">
                             <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                <?= number_format($candidate['vote_count']) ?>
                             </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="<?= BASE_URL ?>/src/views/edit_candidate.php?id=<?= $candidate['CandidateID'] ?>"
                               class="text-suriname-green hover:text-suriname-dark-green mr-3">
                                <i class="fas fa-edit"></i>
                            </a>
                             <form action="<?= BASE_URL ?>/src/controllers/CandidateController.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="candidate_id" value="<?= $candidate['CandidateID'] ?>">
                                <button type="submit"
                                        class="text-suriname-red hover:text-suriname-dark-red"
                                        onclick="return confirm('Weet u zeker dat u deze kandidaat wilt verwijderen?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <!-- Pagination (Optional) -->
     <?php if ($total_pages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200">
            <nav class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Pagina <?= $page ?> van <?= $total_pages ?>
                </div>
                <div>
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Vorige</a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="ml-2 px-3 py-1 border border-gray-300 rounded-md text-sm hover:bg-gray-50">Volgende</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- New Candidate Modal -->
<div id="newCandidateModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="<?= BASE_URL ?>/src/controllers/CandidateController.php" method="POST" enctype="multipart/form-data">
                 <input type="hidden" name="action" value="create">
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
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="image"> <!-- Changed from 'photo' -->
                            Foto
                        </label>
                        <input type="file" name="image" id="image" accept="image/*"
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
require_once __DIR__ . '/../../admin/components/layout.php'; 
?>
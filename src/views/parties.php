<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../controllers/PartyController.php';

// Check if user is logged in and is admin
requireAdmin();

$controller = new PartyController();
$parties = $controller->getPartiesData();
$total_candidates = $controller->getTotalCandidates();

// Start output buffering
ob_start();
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaten</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($parties)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                        Geen partijen gevonden
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($parties as $party): ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <?php if ($party['Logo']): ?>
                                        <img class="h-10 w-10 rounded-full object-cover transform hover:scale-110 transition-transform duration-200" 
                                             src="<?= BASE_URL ?>/<?= htmlspecialchars($party['Logo']); ?>" 
                                             alt="<?php echo htmlspecialchars($party['PartyName']); ?>">
                                    <?php else: ?>
                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <span class="text-gray-500 text-sm font-bold">
                                                <?php echo strtoupper(substr($party['PartyName'], 0, 1)); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($party['PartyName']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                <?php echo number_format($party['candidate_count']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="<?= BASE_URL ?>/src/views/edit_party.php?id=<?php echo $party['PartyID']; ?>" 
                               class="text-suriname-green hover:text-suriname-dark-green mr-3 transition-colors duration-200">
                                <i class="fas fa-edit transform hover:scale-110 transition-transform duration-200"></i>
                            </a>
                            <a href="?delete=<?php echo $party['PartyID']; ?>" 
                               onclick="return confirm('Weet u zeker dat u deze partij wilt verwijderen?')"
                               class="text-suriname-red hover:text-suriname-dark-red transition-colors duration-200">
                                <i class="fas fa-trash transform hover:scale-110 transition-transform duration-200"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- New Party Modal -->
<div id="newPartyModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="<?= BASE_URL ?>/src/controllers/PartyController.php" method="POST" enctype="multipart/form-data">
                 <input type="hidden" name="action" value="create"> <!-- Assuming controller handles actions -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="party_name">
                            Naam Partij
                        </label>
                        <input type="text" name="party_name" id="party_name" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="logo">
                            Logo
                        </label>
                        <input type="file" name="logo" id="logo" accept="image/*"
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
// Adjust the path according to your final structure
require_once __DIR__ . '/../../admin/components/layout.php'; 
?>
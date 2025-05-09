<?php
require_once __DIR__ . '/../../include/admin_auth.php';
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

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Partijen Beheer</h1>
        <p class="text-gray-600">Overzicht en beheer van alle politieke partijen.</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-green/10">
                <i class="fas fa-flag text-2xl text-suriname-green"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Totaal Partijen</p>
                <p class="text-3xl font-semibold text-suriname-green mt-1"><?= number_format(count($parties)) ?></p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-green/10">
                <i class="fas fa-user-tie text-2xl text-suriname-green"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Totaal Kandidaten</p>
                <p class="text-3xl font-semibold text-suriname-green mt-1"><?= number_format($total_candidates) ?></p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-green/10">
                <i class="fas fa-chart-pie text-2xl text-suriname-green"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Gemiddeld Kandidaten per Partij</p>
                <p class="text-3xl font-semibold text-suriname-green mt-1">
                    <?= count($parties) > 0 ? number_format($total_candidates / count($parties), 1) : '0' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Add New Party Button -->
    <div class="mb-6 flex justify-end">
        <button onclick="document.getElementById('newPartyModal').classList.remove('hidden')"
                class="bg-suriname-green hover:bg-suriname-dark-green text-white font-semibold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center">
            <i class="fas fa-plus mr-2"></i>Nieuwe Partij Toevoegen
        </button>
    </div>

    <!-- Parties Table -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Alle Partijen</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaten</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beschrijving</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($parties)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                Geen partijen gevonden
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($parties as $party): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <?php if ($party['Logo']): ?>
                                                <img class="h-10 w-10 rounded-full object-cover"
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
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/20 text-suriname-green">
                                        <?php echo number_format($party['candidate_count']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 truncate" style="max-width: 200px;" title="<?= htmlspecialchars($party['Description'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($party['Description'] ?? 'N.v.t.'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    <a href="<?= BASE_URL ?>/src/views/edit_party.php?id=<?php echo $party['PartyID']; ?>"
                                       class="text-blue-600 hover:text-blue-800 transition-colors duration-150" title="Bewerk Partij">
                                        <i class="fas fa-edit fa-fw"></i>
                                    </a>
                                    <a href="?delete=<?php echo $party['PartyID']; ?>"
                                       onclick="return confirm('Weet u zeker dat u deze partij wilt verwijderen?')"
                                       class="text-red-600 hover:text-red-800 transition-colors duration-150" title="Verwijder Partij">
                                        <i class="fas fa-trash fa-fw"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- New Party Modal -->
    <div id="newPartyModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title-party" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-700 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <!-- This element is to trick the browser into centering the modal contents. -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="<?= BASE_URL ?>/src/controllers/PartyController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-suriname-green/10 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-plus text-suriname-green text-xl"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title-party">
                                    Nieuwe Partij Toevoegen
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700" for="party_name">
                                            Naam Partij
                                        </label>
                                        <input type="text" name="party_name" id="party_name" required
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm">
                                   </div>
                                   <div>
                                       <label class="block text-sm font-medium text-gray-700" for="description_new">
                                           Beschrijving
                                       </label>
                                       <textarea name="description" id="description_new" rows="3"
                                                 class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm"></textarea>
                                   </div>
                                   <div>
                                       <label class="block text-sm font-medium text-gray-700" for="logo_new">
                                           Logo
                                       </label>
                                       <input type="file" name="logo" id="logo_new" accept="image/*"
                                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm">
                                       <p class="text-xs text-gray-500 mt-1">Maximaal 5MB. Toegestane formaten: JPG, PNG, GIF</p>
                                   </div>
                               </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-suriname-green text-base font-medium text-white hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-150">
                            Opslaan
                        </button>
                        <button type="button"
                                onclick="document.getElementById('newPartyModal').classList.add('hidden')"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-150">
                            Annuleren
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> <!-- End container -->

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
// Adjust the path according to your final structure
require_once __DIR__ . '/../../admin/components/layout.php';
?>
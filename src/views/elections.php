<?php
require_once __DIR__ . '/../../include/auth.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../controllers/ElectionController.php';

// Check if user is logged in and is admin
requireAdmin();

$controller = new ElectionController();
$electionData = $controller->getElectionData();

$active_elections = $electionData['active_elections'];
$upcoming_elections = $electionData['upcoming_elections'];
$completed_elections = $electionData['completed_elections'];
$total_votes = $electionData['total_votes'];

// Start output buffering
ob_start();
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Actieve Verkiezingen</p>
                <p class="text-2xl font-bold text-suriname-green"><?= count($active_elections) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-vote-yea text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Aankomende Verkiezingen</p>
                <p class="text-2xl font-bold text-suriname-green"><?= count($upcoming_elections) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-calendar-alt text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Afgeronde Verkiezingen</p>
                <p class="text-2xl font-bold text-suriname-green"><?= count($completed_elections) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-check-circle text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Totaal Stemmen</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_votes) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-chart-bar text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>
</div>

<!-- Add New Election Button -->
<div class="mb-6">
    <button onclick="document.getElementById('newElectionModal').classList.remove('hidden')" 
            class="bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-2 px-4 rounded transition-all duration-300 transform hover:scale-105">
        <i class="fas fa-plus mr-2"></i>Nieuwe Verkiezing
    </button>
</div>

<!-- Active Elections -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Actieve Verkiezingen</h2>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verkiezing</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaten</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($active_elections)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Geen actieve verkiezingen gevonden
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($active_elections as $election): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($election['ElectionName']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($election['Description']) ?></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-900">
                                    <?= date('d-m-Y', strtotime($election['StartDate'])) ?> tot
                                    <?= date('d-m-Y', strtotime($election['EndDate'])) ?>
                                </p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                    <?= $election['candidate_count'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                    <?= number_format($election['vote_count']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Actief
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="<?= BASE_URL ?>/admin/edit_election.php?id=<?= $election['ElectionID'] ?>" 
                                   class="text-suriname-green hover:text-suriname-dark-green mr-3 transition-colors duration-200">
                                    <i class="fas fa-edit transform hover:scale-110 transition-transform duration-200"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/admin/results.php?id=<?= $election['ElectionID'] ?>" 
                                   class="text-suriname-green hover:text-suriname-dark-green transition-colors duration-200">
                                    <i class="fas fa-chart-pie transform hover:scale-110 transition-transform duration-200"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Upcoming Elections -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Aankomende Verkiezingen</h2>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verkiezing</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaten</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($upcoming_elections)): ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                            Geen aankomende verkiezingen gevonden
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($upcoming_elections as $election): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($election['ElectionName']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($election['Description']) ?></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-900">
                                    <?= date('d-m-Y', strtotime($election['StartDate'])) ?> tot
                                    <?= date('d-m-Y', strtotime($election['EndDate'])) ?>
                                </p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                    <?= $election['candidate_count'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Aankomend
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="<?= BASE_URL ?>/admin/edit_election.php?id=<?= $election['ElectionID'] ?>" 
                                   class="text-suriname-green hover:text-suriname-dark-green mr-3 transition-colors duration-200">
                                    <i class="fas fa-edit transform hover:scale-110 transition-transform duration-200"></i>
                                </a>
                                <form action="<?= BASE_URL ?>/src/controllers/ElectionController.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="election_id" value="<?= $election['ElectionID'] ?>">
                                    <button type="submit" 
                                            class="text-suriname-red hover:text-suriname-dark-red transition-colors duration-200"
                                            onclick="return confirm('Weet u zeker dat u deze verkiezing wilt verwijderen?')">
                                        <i class="fas fa-trash transform hover:scale-110 transition-transform duration-200"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Completed Elections -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Afgeronde Verkiezingen</h2>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verkiezing</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaten</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($completed_elections)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Geen afgeronde verkiezingen gevonden
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($completed_elections as $election): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($election['ElectionName']) ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($election['Description']) ?></p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm text-gray-900">
                                    <?= date('d-m-Y', strtotime($election['StartDate'])) ?> tot
                                    <?= date('d-m-Y', strtotime($election['EndDate'])) ?>
                                </p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                    <?= $election['candidate_count'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                    <?= number_format($election['vote_count']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Afgerond
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="<?= BASE_URL ?>/admin/results.php?id=<?= $election['ElectionID'] ?>" 
                                   class="text-suriname-green hover:text-suriname-dark-green transition-colors duration-200">
                                    <i class="fas fa-chart-pie transform hover:scale-110 transition-transform duration-200"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Election Modal -->
<div id="newElectionModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="<?= BASE_URL ?>/src/controllers/ElectionController.php" method="POST">
                 <input type="hidden" name="action" value="create">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                            Naam Verkiezing
                        </label>
                        <input type="text" name="name" id="name" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="description">
                            Beschrijving
                        </label>
                        <textarea name="description" id="description" rows="3" required
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="start_date">
                                Start Datum
                            </label>
                            <input type="date" name="start_date" id="start_date" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="end_date">
                                Eind Datum
                            </label>
                            <input type="date" name="end_date" id="end_date" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-suriname-green text-base font-medium text-white hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 transform hover:scale-105">
                        Opslaan
                    </button>
                    <button type="button" 
                            onclick="document.getElementById('newElectionModal').classList.add('hidden')"
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
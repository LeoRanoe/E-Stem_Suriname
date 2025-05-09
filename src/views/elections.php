+<?php
// Verify includes are working
if (!@require_once __DIR__ . '/../../include/admin_auth.php') {
    die('Failed to load admin authentication');
}
if (!@require_once __DIR__ . '/../../include/config.php') {
    die('Failed to load configuration');
}
if (!@require_once __DIR__ . '/../../include/db_connect.php') {
    die('Failed to load database connection');
}

// Check if user is logged in and is admin
requireAdmin();

// Start output buffering
ob_start();

try {
    // Get current date for status calculations
    $currentDate = date('Y-m-d');

    // Fetch all elections with candidate and vote counts
    $stmt = $pdo->prepare("
        SELECT
            e.ElectionID,
            e.ElectionName,
            e.StartDate,
            e.EndDate,
            e.Status,
            e.CreatedAt,
            e.UpdatedAt,
            COUNT(DISTINCT c.CandidateID) as candidate_count,
            COUNT(DISTINCT v.VoteID) as vote_count
        FROM elections e
        LEFT JOIN candidates c ON e.ElectionID = c.ElectionID
        LEFT JOIN votes v ON e.ElectionID = v.ElectionID
        GROUP BY e.ElectionID
        ORDER BY
            CASE
                WHEN e.Status = 'active' THEN 1
                WHEN e.Status = 'upcoming' THEN 2
                ELSE 3
            END,
            e.StartDate ASC
    ");
    $stmt->execute();
    $allElections = $stmt->fetchAll();

    // Categorize elections by status
    $active_elections = [];
    $upcoming_elections = [];
    $completed_elections = [];
    $total_votes = 0;

    foreach ($allElections as $election) {
        $total_votes += $election['vote_count'];
        
        if ($election['Status'] === 'active') {
            $active_elections[] = $election;
        } elseif ($election['Status'] === 'upcoming') {
            $upcoming_elections[] = $election;
        } else {
            $completed_elections[] = $election;
        }
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        die("Database error: " . $e->getMessage());
    } else {
        die("Er is een fout opgetreden bij het ophalen van verkiezingsgegevens.");
    }
}
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Verkiezingen Beheer</h1>
        <p class="text-gray-600">Overzicht en beheer van alle verkiezingen.</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-vote-yea text-2xl text-green-600"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Actieve Verkiezingen</p>
                <p class="text-3xl font-bold text-gray-800"><?= count($active_elections) ?></p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-calendar-alt text-2xl text-blue-600"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Aankomende Verkiezingen</p>
                <p class="text-3xl font-bold text-gray-800"><?= count($upcoming_elections) ?></p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 bg-gray-100 rounded-full">
                <i class="fas fa-check-circle text-2xl text-gray-600"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Afgeronde Verkiezingen</p>
                <p class="text-3xl font-bold text-gray-800"><?= count($completed_elections) ?></p>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 bg-purple-100 rounded-full">
                <i class="fas fa-chart-bar text-2xl text-purple-600"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Totaal Stemmen</p>
                <p class="text-3xl font-bold text-gray-800"><?= number_format($total_votes) ?></p>
            </div>
        </div>
    </div>

    <!-- Add New Election Button -->
    <div class="mb-6 flex justify-end">
        <button id="openNewElectionModalBtn"
                class="bg-suriname-green hover:bg-suriname-dark-green text-white font-semibold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center">
            <i class="fas fa-plus mr-2"></i>Nieuwe Verkiezing Toevoegen
        </button>
    </div>

    <!-- Election Tables Section -->
    <div class="space-y-8">
        <?php
        function display_election_table($title, $elections, $status_type, $base_url) {
            $status_classes = [
                'active' => 'bg-green-100 text-green-700',
                'upcoming' => 'bg-blue-100 text-blue-700',
                'completed' => 'bg-gray-100 text-gray-700',
            ];
            $status_text = [
                'active' => 'Actief',
                'upcoming' => 'Aankomend',
                'completed' => 'Afgerond',
            ];
        ?>
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($title) ?></h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verkiezing</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaten</th>
                                <?php if ($status_type !== 'upcoming'): ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                                <?php endif; ?>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($elections)): ?>
                                <tr>
                                    <td colspan="<?= ($status_type !== 'upcoming') ? '6' : '5' ?>" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        Geen <?= strtolower(htmlspecialchars($title)) ?> gevonden.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($elections as $election): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($election['ElectionName']) ?></div>
                                            <div class="text-xs text-gray-500">ID: <?= htmlspecialchars($election['ElectionID']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('d M Y', strtotime($election['StartDate'])) ?> - <?= date('d M Y', strtotime($election['EndDate'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/20 text-suriname-green">
                                                <?= $election['candidate_count'] ?>
                                            </span>
                                        </td>
                                        <?php if ($status_type !== 'upcoming'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-700">
                                                <?= number_format($election['vote_count']) ?>
                                            </span>
                                        </td>
                                        <?php endif; ?>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_classes[$status_type] ?>">
                                                <?= $status_text[$status_type] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <?php if ($status_type === 'active' || $status_type === 'upcoming'): ?>
                                                <a href="<?= $base_url ?>/admin/edit_election.php?id=<?= $election['ElectionID'] ?>" 
                                                   class="text-blue-600 hover:text-blue-800 transition-colors duration-150" title="Bewerk Verkiezing">
                                                    <i class="fas fa-edit fa-fw"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($status_type === 'active' || $status_type === 'completed'): ?>
                                                <a href="<?= $base_url ?>/admin/results.php?id=<?= $election['ElectionID'] ?>" 
                                                   class="text-green-600 hover:text-green-800 transition-colors duration-150" title="Bekijk Resultaten">
                                                    <i class="fas fa-chart-pie fa-fw"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($status_type === 'upcoming'): ?>
                                                <form action="<?= $base_url ?>/src/controllers/ElectionController.php" method="POST" class="inline-block">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="election_id" value="<?= $election['ElectionID'] ?>">
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-800 transition-colors duration-150 confirm-delete-election"
                                                            title="Verwijder Verkiezing">
                                                        <i class="fas fa-trash fa-fw"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php
        } // End of display_election_table function

        display_election_table('Actieve Verkiezingen', $active_elections, 'active', BASE_URL);
        display_election_table('Aankomende Verkiezingen', $upcoming_elections, 'upcoming', BASE_URL);
        display_election_table('Afgeronde Verkiezingen', $completed_elections, 'completed', BASE_URL);
        ?>
    </div>
</div> <!-- End container -->

<!-- New Election Modal -->
<div id="newElectionModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-700 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="<?= BASE_URL ?>/src/controllers/ElectionController.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-suriname-green/10 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-plus text-suriname-green text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Nieuwe Verkiezing Aanmaken
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Naam Verkiezing</label>
                                    <input type="text" name="name" id="name" required
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm">
                                </div>
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">Beschrijving</label>
                                    <textarea name="description" id="description" rows="3" required
                                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm"></textarea>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Datum</label>
                                        <input type="date" name="start_date" id="start_date" required
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="end_date" class="block text-sm font-medium text-gray-700">Eind Datum</label>
                                        <input type="date" name="end_date" id="end_date" required
                                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm">
                                    </div>
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
                    <button type="button" id="cancelNewElectionModalBtn"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-150">
                        Annuleren
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/elections.js"></script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
// Adjust the path according to your final structure
require_once __DIR__ . '/../../admin/components/layout.php';
?>
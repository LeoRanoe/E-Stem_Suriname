<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Start output buffering
ob_start();

// Initialize variables
$elections = [];
$selected_election = null;
$election_results = [];
$total_votes = 0;
$total_voters = 0;
$voter_turnout = 0;

try {
    // Get all elections
    $stmt = $pdo->query("
        SELECT e.*, 
               COUNT(DISTINCT v.UserID) as vote_count,
               COUNT(DISTINCT c.CandidateID) as candidate_count
        FROM elections e
        LEFT JOIN votes v ON e.ElectionID = v.ElectionID
        LEFT JOIN candidates c ON e.ElectionID = c.ElectionID
        GROUP BY e.ElectionID
        ORDER BY e.EndDate DESC
    ");
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get selected election
    if (isset($_GET['election']) && !empty($_GET['election'])) {
        $stmt = $pdo->prepare("
            SELECT e.*, 
                   COUNT(DISTINCT v.UserID) as vote_count,
                   (SELECT COUNT(*) FROM users WHERE Role = 'voter') as total_voters
            FROM elections e
            LEFT JOIN votes v ON e.ElectionID = v.ElectionID
            WHERE e.ElectionID = ?
            GROUP BY e.ElectionID
        ");
        $stmt->execute([$_GET['election']]);
        $selected_election = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($selected_election) {
            // Calculate voter turnout
            $total_votes = $selected_election['vote_count'];
            $total_voters = $selected_election['total_voters'];
            $voter_turnout = $total_voters > 0 ? ($total_votes / $total_voters) * 100 : 0;

            // Get results per candidate
            $stmt = $pdo->prepare("
                SELECT c.*,
                       p.PartyName,
                       p.Abbreviation as PartyAbbreviation,
                       COUNT(v.VoteID) as vote_count,
                       (COUNT(v.VoteID) / (SELECT COUNT(*) FROM votes WHERE ElectionID = ?) * 100) as vote_percentage
                FROM candidates c
                LEFT JOIN parties p ON c.PartyID = p.PartyID
                LEFT JOIN votes v ON c.CandidateID = v.CandidateID
                WHERE c.ElectionID = ?
                GROUP BY c.CandidateID
                ORDER BY vote_count DESC
            ");
            $stmt->execute([$_GET['election'], $_GET['election']]);
            $election_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get results per district
            $stmt = $pdo->prepare("
                SELECT d.DistrictName,
                       COUNT(v.VoteID) as vote_count,
                       (COUNT(v.VoteID) / (SELECT COUNT(*) FROM votes WHERE ElectionID = ?) * 100) as vote_percentage
                FROM districts d
                LEFT JOIN users u ON d.DistrictID = u.DistrictID
                LEFT JOIN votes v ON u.UserID = v.UserID AND v.ElectionID = ?
                GROUP BY d.DistrictID
                ORDER BY vote_count DESC
            ");
            $stmt->execute([$_GET['election'], $_GET['election']]);
            $district_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de resultaten.";
}
?>

<!-- Election Selector -->
<div class="mb-8">
    <form action="" method="GET" class="flex gap-4">
        <select name="election" 
                onchange="this.form.submit()"
                class="flex-1 shadow appearance-none border rounded py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
            <option value="">Selecteer een verkiezing</option>
            <?php foreach ($elections as $election): ?>
                <option value="<?= $election['ElectionID'] ?>" 
                        <?= isset($_GET['election']) && $_GET['election'] == $election['ElectionID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($election['ElectionName']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($selected_election): ?>
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Totaal Stemmen</p>
                    <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_votes) ?></p>
                </div>
                <div class="p-3 bg-suriname-green/10 rounded-full">
                    <i class="fas fa-vote-yea text-2xl text-suriname-green"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Unieke Stemmers</p>
                    <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_voters) ?></p>
                </div>
                <div class="p-3 bg-suriname-green/10 rounded-full">
                    <i class="fas fa-users text-2xl text-suriname-green"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Opkomst</p>
                    <p class="text-2xl font-bold text-suriname-green"><?= number_format($voter_turnout, 1) ?>%</p>
                </div>
                <div class="p-3 bg-suriname-green/10 rounded-full">
                    <i class="fas fa-chart-line text-2xl text-suriname-green"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Chart -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Resultaten per Kandidaat</h3>
            <canvas id="resultsChart"></canvas>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Resultaten per District</h3>
            <canvas id="districtChart"></canvas>
        </div>
    </div>

    <!-- Results Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($election_results as $result): ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <img class="h-10 w-10 rounded-full object-cover transform hover:scale-110 transition-transform duration-200" 
                                         src="<?= $result['ProfileImage'] ?? 'https://via.placeholder.com/40' ?>" 
                                         alt="<?= htmlspecialchars($result['FirstName']) ?>">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($result['FirstName'] . ' ' . $result['LastName']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                <?= htmlspecialchars($result['PartyAbbreviation']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                <?= number_format($result['vote_count']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2">
                                    <div class="bg-suriname-green h-2.5 rounded-full" style="width: <?= number_format($result['vote_percentage'], 1) ?>%"></div>
                                </div>
                                <span class="text-sm text-gray-500"><?= number_format($result['vote_percentage'], 1) ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Prepare data for charts
        const candidateData = {
            labels: <?= json_encode(array_map(function($r) { 
                return $r['FirstName'] . ' ' . $r['LastName'] . ' (' . $r['PartyAbbreviation'] . ')'; 
            }, $election_results)) ?>,
            datasets: [{
                label: 'Stemmen',
                data: <?= json_encode(array_map(function($r) { return $r['vote_count']; }, $election_results)) ?>,
                backgroundColor: 'rgba(0, 119, 73, 0.2)',
                borderColor: '#007749',
                borderWidth: 1
            }]
        };

        const districtData = {
            labels: <?= json_encode(array_map(function($r) { return $r['DistrictName']; }, $district_results)) ?>,
            datasets: [{
                label: 'Stemmen',
                data: <?= json_encode(array_map(function($r) { return $r['vote_count']; }, $district_results)) ?>,
                backgroundColor: 'rgba(0, 119, 73, 0.2)',
                borderColor: '#007749',
                borderWidth: 1
            }]
        };

        // Create charts
        new Chart(document.getElementById('resultsChart'), {
            type: 'bar',
            data: candidateData,
            options: {
                responsive: true,
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('districtChart'), {
            type: 'pie',
            data: districtData,
            options: {
                responsive: true,
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                },
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    </script>
<?php else: ?>
    <div class="bg-white rounded-lg shadow-md p-6 text-center">
        <i class="fas fa-chart-bar text-6xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">Selecteer een verkiezing om de resultaten te bekijken</p>
    </div>
<?php endif; ?>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once 'components/layout.php';
?> 
<?php
// Start output buffering
ob_start();

session_start();
require_once '../include/db_connect.php';
require_once '../include/admin_auth.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit();
}

// Initialize variables
$user_count = 0;
$election_count = 0;
$candidate_count = 0;
$recent_votes = [];
$votes_per_day = [];
$votes_by_district = [];
$voter_stats = [
    'active_voters' => 0,
    'inactive_voters' => 0,
    'voters_who_voted' => 0,
    'total_voters' => 0,
];

// Fetch dashboard data
try {
    // Get total users (voters)
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users WHERE Role = 'voter'");
    $user_count = $stmt->fetchColumn();

    // Get total elections
    $stmt = $pdo->query("SELECT COUNT(*) as election_count FROM elections");
    $election_count = $stmt->fetchColumn();

    // Get total candidates
    $stmt = $pdo->query("SELECT COUNT(*) as candidate_count FROM candidates");
    $candidate_count = $stmt->fetchColumn();

    // Get recent activity
    $stmt = $pdo->query("
        SELECT 'vote' as type, v.Timestamp as date, 
               CONCAT(u.Voornaam, ' ', u.Achternaam) as user_name,
               e.ElectionName
        FROM votes v
        JOIN users u ON v.UserID = u.UserID
        JOIN elections e ON v.ElectionID = e.ElectionID
        ORDER BY v.Timestamp DESC
        LIMIT 5
    ");
    $recent_votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get votes per day for the last 30 days
    $stmt = $pdo->query("
        SELECT DATE(v.Timestamp) as date, COUNT(*) as vote_count
        FROM votes v
        WHERE v.Timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(v.Timestamp)
        ORDER BY date ASC
    ");
    $votes_per_day = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get votes by district
    $stmt = $pdo->query("
        SELECT d.DistrictName, COUNT(v.VoteID) as vote_count
        FROM districten d
        LEFT JOIN users u ON d.DistrictID = u.DistrictID
        LEFT JOIN votes v ON u.UserID = v.UserID
        GROUP BY d.DistrictID, d.DistrictName
        ORDER BY vote_count DESC
    ");
    $votes_by_district = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get voter statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_voters,
            SUM(CASE WHEN Status = 'active' THEN 1 ELSE 0 END) as active_voters,
            SUM(CASE WHEN Status = 'inactive' THEN 1 ELSE 0 END) as inactive_voters,
            (SELECT COUNT(DISTINCT UserID) FROM votes) as voters_who_voted
        FROM users 
        WHERE Role = 'voter'
    ");
    $voter_stats_result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($voter_stats_result) {
        // Cast all values to integers
        $voter_stats = array_merge($voter_stats, [
            'total_voters' => (int)$voter_stats_result['total_voters'],
            'active_voters' => (int)$voter_stats_result['active_voters'],
            'inactive_voters' => (int)$voter_stats_result['inactive_voters'],
            'voters_who_voted' => (int)$voter_stats_result['voters_who_voted']
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de dashboardgegevens.";
}
?>

<!-- Debug Output Commented Out-->
<!-- <div class="fixed bottom-4 right-4 bg-white p-4 rounded-lg shadow-xl z-50 max-w-md max-h-96 overflow-auto">
    <h3 class="font-bold text-lg mb-2">Voter Stats Debug</h3>
    <pre class="text-xs"><?= htmlspecialchars(print_r([
        'voter_stats' => $voter_stats,
        'chart_condition' => [
            'total_voters' => $voter_stats['total_voters'],
            'active_voters' => $voter_stats['active_voters'],
            'inactive_voters' => $voter_stats['inactive_voters'],
            'voters_who_voted' => $voter_stats['voters_who_voted'],
            'condition_result' => $voter_stats['total_voters'] == 0 || ($voter_stats['active_voters'] == 0 && $voter_stats['inactive_voters'] == 0 && $voter_stats['voters_who_voted'] == 0),
            'has_any_data' => $voter_stats['active_voters'] > 0 || $voter_stats['inactive_voters'] > 0 || $voter_stats['voters_who_voted'] > 0
        ],
        'chart_rendering' => [
            'canvas_exists' => true,
            'show_fallback' => !($voter_stats['active_voters'] > 0 || $voter_stats['inactive_voters'] > 0 || $voter_stats['voters_who_voted'] > 0)
        ]
    ], true)) ?></pre>
</div> -->

<!-- Hidden inputs for chart data -->
<input type="hidden" id="activeVoters" value="<?= htmlspecialchars((string)$voter_stats['active_voters']) ?>">
<input type="hidden" id="inactiveVoters" value="<?= htmlspecialchars((string)$voter_stats['inactive_voters']) ?>">
<input type="hidden" id="votersWhoVoted" value="<?= htmlspecialchars((string)$voter_stats['voters_who_voted']) ?>">
<input type="hidden" id="voteDates" value="<?= htmlspecialchars(json_encode(array_column($votes_per_day, 'date'))) ?>">
<input type="hidden" id="voteCounts" value="<?= htmlspecialchars(json_encode(array_column($votes_per_day, 'vote_count'))) ?>">
<input type="hidden" id="districts" value="<?= htmlspecialchars(json_encode(array_column($votes_by_district, 'DistrictName'))) ?>">
<input type="hidden" id="districtVotes" value="<?= htmlspecialchars(json_encode(array_column($votes_by_district, 'vote_count'))) ?>">

<!-- Main Content -->
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
        <p class="text-gray-600">Overzicht van systeemstatistieken en activiteit.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Stemmers</p>
                <p class="text-3xl font-semibold text-suriname-green mt-1"><?= number_format($user_count) ?></p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                <i class="fas fa-vote-yea text-2xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Verkiezingen</p>
                <p class="text-3xl font-semibold text-suriname-green mt-1"><?= number_format($election_count) ?></p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                <i class="fas fa-user-tie text-2xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Kandidaten</p>
                <p class="text-3xl font-semibold text-suriname-green mt-1"><?= number_format($candidate_count) ?></p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                <i class="fas fa-chart-line text-2xl"></i>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500">Opkomst</p>
                <p class="text-3xl font-semibold text-suriname-green mt-1">
                    <?= $voter_stats['total_voters'] > 0 ?
                        number_format(($voter_stats['voters_who_voted'] / $voter_stats['total_voters']) * 100, 1) : 0 ?>%
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Activity -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
            <div class="border-b border-gray-200 pb-4 mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Recente Activiteit</h2>
            </div>
            <div class="space-y-4 max-h-96 overflow-y-auto">
                <?php if (!empty($recent_votes)): ?>
                    <?php foreach ($recent_votes as $vote): ?>
                        <div class="flex items-start p-3 hover:bg-gray-50 rounded-lg transition-colors duration-150">
                            <div class="flex-shrink-0 mr-3 mt-1">
                                <div class="w-10 h-10 bg-suriname-green/10 rounded-full flex items-center justify-center text-suriname-green">
                                    <i class="fas fa-vote-yea"></i>
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800 truncate"><?= htmlspecialchars($vote['user_name']) ?></p>
                                <p class="text-sm text-gray-600">
                                    Heeft gestemd in: <span class="font-medium"><?= htmlspecialchars($vote['ElectionName']) ?></span>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    <?= date('d M Y, H:i', strtotime($vote['date'])) ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-info-circle text-3xl mb-2 text-gray-400"></i>
                        <p>Geen recente activiteit gevonden.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Voter Status Chart -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
            <div class="border-b border-gray-200 pb-4 mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Status Stemmers</h2>
            </div>
            <div class="h-72 flex justify-center items-center">
                <?php if (!($voter_stats['active_voters'] > 0 || $voter_stats['inactive_voters'] > 0 || $voter_stats['voters_who_voted'] > 0)): ?>
                    <div class="text-center py-8 text-gray-500 h-full flex flex-col justify-center items-center">
                        <i class="fas fa-info-circle text-3xl mb-2 text-gray-400"></i>
                        <p>Geen recente activiteit gevonden.</p>
                    </div>
                <?php else: ?>
                    <canvas id="voterStatusChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- Votes per Day Chart -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
            <div class="border-b border-gray-200 pb-4 mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Stemmen per Dag</h2>
            </div>
            <div class="h-72 flex justify-center items-center">
                <?php if (empty($votes_per_day)): ?>
                    <div class="text-center py-8 text-gray-500 h-full flex flex-col justify-center items-center">
                        <i class="fas fa-info-circle text-3xl mb-2 text-gray-400"></i>
                        <p>Geen recente activiteit gevonden.</p>
                    </div>
                <?php else: ?>
                    <canvas id="votesPerDayChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- Votes by District Chart -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200">
            <div class="border-b border-gray-200 pb-4 mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Stemmen per District</h2>
            </div>
            <div class="h-72 flex justify-center items-center">
                <?php
                $all_district_votes_zero = true;
                if (!empty($votes_by_district)) {
                    foreach ($votes_by_district as $district) {
                        if ($district['vote_count'] > 0) {
                            $all_district_votes_zero = false;
                            break;
                        }
                    }
                }
                if (empty($votes_by_district) || $all_district_votes_zero): ?>
                    <div class="text-center py-8 text-gray-500 h-full flex flex-col justify-center items-center">
                        <i class="fas fa-info-circle text-3xl mb-2 text-gray-400"></i>
                        <p>Geen recente activiteit gevonden.</p>
                    </div>
                <?php else: ?>
                    <canvas id="votesByDistrictChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once 'components/layout.php';
?>
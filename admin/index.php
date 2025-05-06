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
$voter_stats = [];

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
    $voter_stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de dashboardgegevens.";
}
?>

<!-- Dashboard Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Stemmers</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($user_count) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-users text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Verkiezingen</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($election_count) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-vote-yea text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Kandidaten</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($candidate_count) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-user-tie text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Opkomst</p>
                <p class="text-2xl font-bold text-suriname-green">
                    <?= $voter_stats['total_voters'] > 0 ? 
                        number_format(($voter_stats['voters_who_voted'] / $voter_stats['total_voters']) * 100, 1) : 0 ?>%
                </p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-chart-line text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Recente Activiteit</h3>
        <div class="space-y-4">
            <?php if (!empty($recent_votes)): ?>
                <?php foreach ($recent_votes as $vote): ?>
                    <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg transform hover:scale-102 transition-all duration-200">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-suriname-green/10 rounded-full flex items-center justify-center">
                                <i class="fas fa-vote-yea text-suriname-green"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($vote['user_name']) ?></p>
                            <p class="text-sm text-gray-500">
                                Heeft gestemd in: <?= htmlspecialchars($vote['ElectionName']) ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                <?= date('d-m-Y H:i', strtotime($vote['date'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-gray-500 py-4">
                    <i class="fas fa-info-circle mb-2 text-2xl"></i>
                    <p>Geen recente activiteit gevonden</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Voter Status Chart -->
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Status Stemmers</h3>
        <canvas id="voterStatusChart"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Votes per Day Chart -->
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Stemmen per Dag</h3>
        <canvas id="votesPerDayChart"></canvas>
    </div>

    <!-- Votes by District Chart -->
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Stemmen per District</h3>
        <canvas id="votesByDistrictChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Voter Status Chart
    const voterStatusCtx = document.getElementById('voterStatusChart').getContext('2d');
    new Chart(voterStatusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Actief', 'Inactief', 'Gestemd'],
            datasets: [{
                data: [
                    <?= $voter_stats['active_voters'] ?>,
                    <?= $voter_stats['inactive_voters'] ?>,
                    <?= $voter_stats['voters_who_voted'] ?>
                ],
                backgroundColor: [
                    '#007749', // suriname-green
                    '#C8102E', // suriname-red
                    '#006241'  // suriname-dark-green
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Votes per Day Chart
    const votesPerDayCtx = document.getElementById('votesPerDayChart').getContext('2d');
    new Chart(votesPerDayCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($votes_per_day, 'date')) ?>,
            datasets: [{
                label: 'Aantal Stemmen',
                data: <?= json_encode(array_column($votes_per_day, 'vote_count')) ?>,
                borderColor: '#007749',
                backgroundColor: 'rgba(0, 119, 73, 0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Votes by District Chart
    const votesByDistrictCtx = document.getElementById('votesByDistrictChart').getContext('2d');
    new Chart(votesByDistrictCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($votes_by_district, 'DistrictName')) ?>,
            datasets: [{
                label: 'Aantal Stemmen',
                data: <?= json_encode(array_column($votes_by_district, 'vote_count')) ?>,
                backgroundColor: '#007749',
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
</script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once 'components/layout.php';
?> 
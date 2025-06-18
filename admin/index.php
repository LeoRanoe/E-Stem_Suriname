<?php
require_once __DIR__ . '/../include/admin_auth.php';
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';

// Check if user is logged in and is admin
requireAdmin();

// Get dashboard statistics
try {
    // Get total voters
    $stmt = $pdo->query("SELECT COUNT(*) as voter_count FROM voters WHERE status = 'active'");
    $total_voters = $stmt->fetchColumn();

    // Get total votes cast
    $stmt = $pdo->query("SELECT COUNT(DISTINCT UserID) as vote_count FROM votes");
    $total_votes = $stmt->fetchColumn();

    // Calculate turnout percentage
    $turnout_percentage = $total_voters > 0 ? round(($total_votes / $total_voters) * 100, 1) : 0;

    // Get total QR codes generated
    $stmt = $pdo->query("SELECT COUNT(*) as qr_count FROM qrcodes");
    $total_qrcodes = $stmt->fetchColumn();

    // Get total active vouchers
    $stmt = $pdo->query("SELECT COUNT(*) as voucher_count FROM vouchers WHERE status = 'active'");
    $total_active_vouchers = $stmt->fetchColumn();

    // Get active elections count
    $stmt = $pdo->query("SELECT COUNT(*) as active_count FROM elections WHERE Status = 'active'");
    $active_elections = $stmt->fetchColumn();

    // Determine election status text
    $election_status_text = $active_elections > 0 ? 'Running' : 'Closed';

    // Get recent voter activity (last 5 logins)
    $stmt = $pdo->query("
        SELECT vl.*, v.first_name, v.last_name, v.voter_code
        FROM voter_logins vl
        JOIN voters v ON vl.voter_id = v.id
        ORDER BY vl.login_time DESC
        LIMIT 5
    ");
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get votes by district for the last 24 hours
    $stmt = $pdo->query("
        SELECT d.DistrictName, COUNT(v.VoteID) as vote_count
        FROM votes v
        JOIN voters vt ON v.UserID = vt.id
        JOIN districten d ON vt.district_id = d.DistrictID
        WHERE v.TimeStamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY d.DistrictID, d.DistrictName
        ORDER BY vote_count DESC
    ");
    $recent_district_votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Chart Data: Voter Status
    $voter_status_data = $pdo->query("SELECT status, COUNT(*) as count FROM voters GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

    // Chart Data: Votes per District
    $district_votes_data = [];
    $active_election_id_stmt = $pdo->query("SELECT ElectionID FROM elections WHERE status = 'active' LIMIT 1");
    $active_election_id = $active_election_id_stmt->fetchColumn();

    if ($active_election_id) {
        $district_votes_stmt = $pdo->prepare("
            SELECT d.DistrictName, COUNT(v.VoteID) as vote_count
            FROM districten d
            LEFT JOIN voters vt ON d.DistrictID = vt.district_id
            LEFT JOIN votes v ON vt.id = v.UserID AND v.ElectionID = ?
            GROUP BY d.DistrictID, d.DistrictName
            ORDER BY d.DistrictName ASC
        ");
        $district_votes_stmt->execute([$active_election_id]);
        $district_votes_data = $district_votes_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    // Log error and continue with empty data
    error_log("Dashboard error: " . $e->getMessage());
    $total_voters = 0;
    $total_votes = 0;
    $turnout_percentage = 0;
    $total_qrcodes = 0;
    $total_active_vouchers = 0;
    $active_elections = 0;
    $election_status_text = 'Unknown';
    $recent_activity = [];
    $recent_district_votes = [];
    $voter_status_data = [];
    $district_votes_data = [];
}

// Page title
$pageTitle = "Admin Dashboard";

// Start output buffering
ob_start();
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Title & Welcome Message -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
        <p class="mt-2 text-sm text-gray-600">Welkom, <?= htmlspecialchars($_SESSION['AdminName']) ?>! Hier is een overzicht van de verkiezing.</p>
    </div>

    <!-- Quick Actions -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <a href="voters.php" class="bg-white p-4 rounded-xl shadow-lg border border-gray-200 flex flex-col items-center justify-center transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-full bg-suriname-green/10 text-suriname-green mb-2">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <span class="font-semibold text-gray-800">Manage Voters</span>
            </a>
            <a href="results.php" class="bg-white p-4 rounded-xl shadow-lg border border-gray-200 flex flex-col items-center justify-center transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-full bg-suriname-red/10 text-suriname-red mb-2">
                    <i class="fas fa-chart-bar text-2xl"></i>
                </div>
                <span class="font-semibold text-gray-800">View Results</span>
            </a>
            <a href="results.php#export" class="bg-white p-4 rounded-xl shadow-lg border border-gray-200 flex flex-col items-center justify-center transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-full bg-suriname-green/10 text-suriname-green mb-2">
                    <i class="fas fa-download text-2xl"></i>
                </div>
                <span class="font-semibold text-gray-800">Export Reports</span>
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Registered Voters -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Registered Voters</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= number_format($total_voters) ?></h3>
            </div>
        </div>

        <!-- Active Vouchers -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-red/10 text-suriname-red">
                <i class="fas fa-ticket-alt text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Active Vouchers</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= number_format($total_active_vouchers) ?></h3>
            </div>
        </div>
        
        <!-- Total Votes Cast -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                <i class="fas fa-vote-yea text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Votes Cast</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= number_format($total_votes) ?></h3>
            </div>
        </div>

        <!-- Current Election Status -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg <?= $active_elections > 0 ? 'bg-green-500/20 text-green-600' : 'bg-red-500/20 text-red-600' ?>">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Current Election Status</p>
                <h3 class="text-2xl font-bold <?= $active_elections > 0 ? 'text-green-600' : 'text-red-600' ?>"><?= $election_status_text ?></h3>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-xl shadow-lg border">
            <div class="card-header">Kiezer Status</div>
            <div class="p-4"><canvas id="voter-status-chart"></canvas></div>
        </div>
        <div class="bg-white rounded-xl shadow-lg border">
            <div class="card-header">Stemmen per District</div>
            <div class="p-4"><canvas id="district-votes-chart"></canvas></div>
        </div>
    </div>

    <!-- Activity and District Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Recente Activiteit</h2>
            </div>
            <div class="p-6">
                <?php if (empty($recent_activity)): ?>
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="p-3 rounded-full bg-gray-100 mb-4">
                            <i class="fas fa-info-circle text-gray-400 text-xl"></i>
                        </div>
                        <p class="text-gray-500">Geen recente activiteit gevonden</p>
                    </div>
                <?php else: ?>
                    <div class="flow-root">
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($recent_activity as $activity): ?>
                            <li class="py-4">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="p-2 rounded-full <?= $activity['status'] === 'success' ? 'bg-suriname-green/10 text-suriname-green' : 'bg-suriname-red/10 text-suriname-red' ?>">
                                            <i class="fas <?= $activity['attempt_type'] === 'qr_scan' ? 'fa-qrcode' : 'fa-keyboard' ?>"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            <?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?= $activity['attempt_type'] === 'qr_scan' ? 'QR Code Scan' : 'Handmatige Login' ?> - 
                                            <?= date('d M Y H:i', strtotime($activity['login_time'])) ?>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $activity['status'] === 'success' ? 'bg-suriname-green/10 text-suriname-green' : 'bg-suriname-red/10 text-suriname-red' ?>">
                                            <?= $activity['status'] === 'success' ? 'Succes' : 'Mislukt' ?>
                                        </span>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- District Stats -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Stemmen per District (Laatste 24u)</h2>
            </div>
            <div class="p-6">
                <?php if (empty($recent_district_votes)): ?>
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="p-3 rounded-full bg-gray-100 mb-4">
                            <i class="fas fa-info-circle text-gray-400 text-xl"></i>
                        </div>
                        <p class="text-gray-500">Geen stemmen geregistreerd in de afgelopen 24 uur</p>
                    </div>
                <?php else: ?>
                    <div class="flow-root">
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($recent_district_votes as $district): ?>
                            <li class="py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            <?= htmlspecialchars($district['DistrictName']) ?>
                                        </p>
                                    </div>
                                    <div class="ml-4 flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-suriname-green/10 text-suriname-green">
                                            <?= number_format($district['vote_count']) ?> stemmen
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-suriname-green h-2 rounded-full" style="width: <?= min(($district['vote_count'] / max(array_column($recent_district_votes, 'vote_count'))) * 100, 100) ?>%"></div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Prepare data for charts
$voter_status_labels = json_encode(array_keys($voter_status_data));
$voter_status_values = json_encode(array_values($voter_status_data));
$district_votes_labels = json_encode(array_column($district_votes_data, 'DistrictName'));
$district_votes_values = json_encode(array_column($district_votes_data, 'vote_count'));

$pageScript = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    // Helper to render chart or show message
    function renderChart(ctx, type, labels, data, chartLabel, colors) {
        if (!ctx) return;
        const allZero = data.every(item => item === 0);
        if (allZero && data.length === 0) {
            ctx.parentElement.innerHTML = '<div class="text-center py-8 text-gray-500">Geen data beschikbaar.</div>';
            return;
        }
        new Chart(ctx, {
            type: type,
            data: { labels: labels, datasets: [{ label: chartLabel, data: data, backgroundColor: colors, borderColor: colors, fill: type === 'line' ? true : false, tension: 0.1 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
        });
    }

    // Render Charts
    const voterStatusColors = ['#007749', '#C8102E', '#FFC72C'];
    renderChart(document.getElementById('voter-status-chart'), 'pie', $voter_status_labels, $voter_status_values, 'Kiezer Status', voterStatusColors);
    
    const districtColors = ['#C8102E', '#007749', '#FFC72C', '#0033A0', '#6A2E9A', '#F15A29', '#00AEEF', '#8CC63F', '#662D91', '#EC008C'];
    renderChart(document.getElementById('district-votes-chart'), 'pie', $district_votes_labels, $district_votes_values, 'Stemmen per District', districtColors);
});
JS;

include __DIR__ . '/components/layout.php';
?>


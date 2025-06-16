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

    // Get active elections count
    $stmt = $pdo->query("SELECT COUNT(*) as active_count FROM elections WHERE Status = 'active'");
    $active_elections = $stmt->fetchColumn();

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

} catch (PDOException $e) {
    // Log error and continue with empty data
    error_log("Dashboard error: " . $e->getMessage());
    $total_voters = 0;
    $total_votes = 0;
    $turnout_percentage = 0;
    $total_qrcodes = 0;
    $active_elections = 0;
    $recent_activity = [];
    $recent_district_votes = [];
}

// Page title
$pageTitle = "Admin Dashboard";

// Start output buffering
ob_start();
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
        <p class="mt-2 text-sm text-gray-600">Overzicht van verkiezingsstatistieken en recente activiteit</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Voters -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Totaal Kiezers</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= number_format($total_voters) ?></h3>
            </div>
        </div>

        <!-- Votes Cast -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-red/10 text-suriname-red">
                <i class="fas fa-vote-yea text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Uitgebrachte Stemmen</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= number_format($total_votes) ?></h3>
            </div>
        </div>

        <!-- Turnout Percentage -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                <i class="fas fa-chart-pie text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">Opkomstpercentage</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= $turnout_percentage ?>%</h3>
            </div>
        </div>

        <!-- QR Codes Generated -->
        <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
            <div class="p-3 rounded-lg bg-suriname-red/10 text-suriname-red">
                <i class="fas fa-qrcode text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500">QR Codes Gegenereerd</p>
                <h3 class="text-2xl font-bold text-gray-800"><?= number_format($total_qrcodes) ?></h3>
            </div>
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

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Voter Status Chart -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Kiezersstatus</h2>
            </div>
            <div class="p-6">
                <div class="h-64">
                    <canvas id="voter-status-chart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Votes Per Day Chart -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Stemmen per Dag</h2>
            </div>
            <div class="p-6">
                <div class="h-64">
                    <canvas id="votes-per-day-chart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Votes By District Chart -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Stemmen per District</h2>
            </div>
            <div class="p-6">
                <div class="h-64">
                    <canvas id="votes-by-district-chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="mt-8 bg-red-50 border-l-4 border-red-400 p-6 rounded-r-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-lg font-medium text-red-800">Gevarenzone</h3>
                <div class="mt-2 text-sm text-red-700">
                    <p>Deze acties zijn onomkeerbaar. Wees alstublieft zeker voordat u doorgaat.</p>
                </div>
                <div class="mt-4">
                    <div class="-mx-2 -my-1.5 flex">
                        <a href="#" onclick="confirmDeleteVoters(event, '<?= BASE_URL ?>/admin/utils/delete_voters.php')" class="btn-hover bg-red-600 text-white font-bold py-2 px-4 rounded-lg inline-flex items-center transition-all duration-300 hover:bg-red-700 hover:shadow-lg">
                            <i class="fas fa-trash-alt mr-2"></i>
                            Verwijder Alle Kiezers
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();

// Include the layout with the content
include_once __DIR__ . '/components/layout.php';
?>

<!-- JavaScript is included in the layout.php file -->

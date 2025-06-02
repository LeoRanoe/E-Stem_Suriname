<?php
require_once __DIR__ . '/../include/admin_auth.php';
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';

// Check if user is logged in and is admin
requireAdmin();

// Get election results data
try {
    // Get active election
    $stmt = $pdo->query("
        SELECT e.*, 
               COUNT(DISTINCT v.UserID) as total_votes,
               COUNT(DISTINCT c.CandidateID) as total_candidates
        FROM elections e
        LEFT JOIN votes v ON e.ElectionID = v.ElectionID
        LEFT JOIN candidates c ON e.ElectionID = c.ElectionID
        WHERE e.Status = 'active'
        GROUP BY e.ElectionID
        LIMIT 1
    ");
    $active_election = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($active_election) {
        // Get total registered voters
        $stmt = $pdo->query("SELECT COUNT(*) FROM voters WHERE status = 'active'");
        $total_voters = $stmt->fetchColumn();

        // Calculate turnout percentage
        $turnout_percentage = $total_voters > 0 ? round(($active_election['total_votes'] / $total_voters) * 100, 1) : 0;

        // Get votes by district
        $stmt = $pdo->prepare("
            SELECT 
                d.DistrictID,
                d.DistrictName,
                COUNT(DISTINCT v.UserID) as vote_count,
                COUNT(DISTINCT vt.id) as total_voters_in_district,
                ROUND((COUNT(DISTINCT v.UserID) / COUNT(DISTINCT vt.id)) * 100, 1) as district_turnout
            FROM districten d
            LEFT JOIN voters vt ON d.DistrictID = vt.district_id
            LEFT JOIN votes v ON vt.id = v.UserID AND v.ElectionID = ?
            GROUP BY d.DistrictID, d.DistrictName
            ORDER BY vote_count DESC
        ");
        $stmt->execute([$active_election['ElectionID']]);
        $district_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get votes by party
        $stmt = $pdo->prepare("
            SELECT 
                p.PartyID,
                p.PartyName,
                COUNT(v.VoteID) as vote_count,
                ROUND((COUNT(v.VoteID) / ?) * 100, 1) as vote_percentage
            FROM political_parties p
            LEFT JOIN candidates c ON p.PartyID = c.PartyID
            LEFT JOIN votes v ON c.CandidateID = v.CandidateID AND v.ElectionID = ?
            GROUP BY p.PartyID, p.PartyName
            ORDER BY vote_count DESC
        ");
        $stmt->execute([$active_election['total_votes'] > 0 ? $active_election['total_votes'] : 1, $active_election['ElectionID']]);
        $party_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent votes
        $stmt = $pdo->prepare("
            SELECT 
                v.TimeStamp as timestamp,
                d.DistrictName as district,
                p.PartyName as party,
                CASE WHEN v.IsValid = 1 THEN 'valid' ELSE 'invalid' END as status
            FROM votes v
            JOIN voters vt ON v.UserID = vt.id
            JOIN districten d ON vt.district_id = d.DistrictID
            JOIN candidates c ON v.CandidateID = c.CandidateID
            JOIN political_parties p ON c.PartyID = p.PartyID
            WHERE v.ElectionID = ?
            ORDER BY v.TimeStamp DESC
            LIMIT 10
        ");
        $stmt->execute([$active_election['ElectionID']]);
        $recent_votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check for anomalies (duplicate votes, unusual patterns)
        $anomalies = [];
        
        // Check for duplicate votes
        $stmt = $pdo->prepare("
            SELECT 
                vt.first_name, 
                vt.last_name, 
                COUNT(v.VoteID) as vote_count,
                MAX(v.TimeStamp) as last_vote_time
            FROM votes v
            JOIN voters vt ON v.UserID = vt.id
            WHERE v.ElectionID = ?
            GROUP BY v.UserID
            HAVING COUNT(v.VoteID) > 1
        ");
        $stmt->execute([$active_election['ElectionID']]);
        $duplicate_votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($duplicate_votes as $duplicate) {
            $anomalies[] = [
                'type' => 'Duplicate Vote',
                'description' => "Voter {$duplicate['first_name']} {$duplicate['last_name']} has {$duplicate['vote_count']} votes recorded",
                'detected_at' => $duplicate['last_vote_time']
            ];
        }
        
        // Check for unusual voting patterns (e.g., rapid voting from same IP)
        $stmt = $pdo->prepare("
            SELECT 
                v.IPAddress,
                COUNT(v.VoteID) as vote_count,
                MIN(v.TimeStamp) as first_vote,
                MAX(v.TimeStamp) as last_vote
            FROM votes v
            WHERE v.ElectionID = ? AND v.IPAddress IS NOT NULL
            GROUP BY v.IPAddress
            HAVING COUNT(v.VoteID) > 5 AND TIMESTAMPDIFF(MINUTE, MIN(v.TimeStamp), MAX(v.TimeStamp)) < 30
        ");
        $stmt->execute([$active_election['ElectionID']]);
        $unusual_patterns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($unusual_patterns as $pattern) {
            $anomalies[] = [
                'type' => 'Unusual Voting Pattern',
                'description' => "IP {$pattern['IPAddress']} has {$pattern['vote_count']} votes in a short time period",
                'detected_at' => $pattern['last_vote']
            ];
        }
    }
} catch (PDOException $e) {
    // Log error and continue with empty data
    error_log("Results page error: " . $e->getMessage());
    $active_election = null;
    $district_results = [];
    $party_results = [];
    $recent_votes = [];
    $anomalies = [];
}

// Page title
$pageTitle = "Verkiezingsresultaten";

// Start output buffering
ob_start();
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Verkiezingsresultaten</h1>
        <p class="mt-2 text-sm text-gray-600">Live monitoring van verkiezingsvoortgang en resultaten</p>
    </div>

    <?php if (isset($active_election)): ?>
        <!-- Election Status -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-800"><?= htmlspecialchars($active_election['Name']) ?></h2>
                    <span class="px-3 py-1 text-xs rounded-full bg-suriname-green/10 text-suriname-green">
                        Actief
                    </span>
                </div>
            </div>
            <div class="p-6">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Total Votes -->
                    <div class="bg-white p-4 rounded-xl shadow border border-gray-200 transition-all duration-300 hover:shadow-md">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                                <i class="fas fa-vote-yea text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Totaal Stemmen</p>
                                <p class="text-xl font-bold text-gray-800"><?= number_format($active_election['total_votes']) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Turnout -->
                    <div class="bg-white p-4 rounded-xl shadow border border-gray-200 transition-all duration-300 hover:shadow-md">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 rounded-lg bg-suriname-red/10 text-suriname-red">
                                <i class="fas fa-chart-pie text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Opkomstpercentage</p>
                                <p class="text-xl font-bold text-gray-800"><?= $turnout_percentage ?>%</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Candidates -->
                    <div class="bg-white p-4 rounded-xl shadow border border-gray-200 transition-all duration-300 hover:shadow-md">
                        <div class="flex items-center space-x-4">
                            <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Kandidaten</p>
                                <p class="text-xl font-bold text-gray-800"><?= number_format($active_election['total_candidates']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <div class="mb-2">
                    <div class="flex justify-between mb-1">
                        <span class="text-sm font-medium text-gray-700">Opkomstvoortgang</span>
                        <span class="text-sm font-medium text-gray-700"><?= $turnout_percentage ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-suriname-green h-2.5 rounded-full" style="width: <?= min($turnout_percentage, 100) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Party Results Chart -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Resultaten per Partij</h2>
                </div>
                <div class="p-6">
                    <div class="h-64">
                        <canvas id="party-results-chart"></canvas>
                    </div>
                </div>
            </div>

            <!-- District Results Chart -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Resultaten per District</h2>
                </div>
                <div class="p-6">
                    <div class="h-64">
                        <canvas id="district-results-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- District Breakdown -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Uitsplitsing per District</h2>
                </div>
                <div class="p-6">
                    <div class="flow-root">
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($district_results as $district): ?>
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
                                <div class="mt-2">
                                    <div class="flex justify-between mb-1">
                                        <span class="text-xs font-medium text-gray-500">Opkomst: <?= $district['district_turnout'] ?>%</span>
                                        <span class="text-xs font-medium text-gray-500"><?= number_format($district['vote_count']) ?> / <?= number_format($district['total_voters_in_district']) ?></span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-suriname-green h-2 rounded-full" style="width: <?= min($district['district_turnout'], 100) ?>%"></div>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Anomalies -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Mogelijke Onregelmatigheden</h2>
                </div>
                <div class="p-6">
                    <?php if (empty($anomalies)): ?>
                        <div class="flex flex-col items-center justify-center py-6 text-center">
                            <div class="p-3 rounded-full bg-suriname-green/10 text-suriname-green mb-4">
                                <i class="fas fa-check-circle text-2xl"></i>
                            </div>
                            <p class="text-gray-500">Geen onregelmatigheden gedetecteerd in de stemgegevens</p>
                        </div>
                    <?php else: ?>
                        <div class="flow-root">
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($anomalies as $anomaly): ?>
                                <li class="py-4">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="p-2 rounded-full bg-suriname-red/10 text-suriname-red">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate">
                                                <?= htmlspecialchars($anomaly['type']) ?>
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                <?= htmlspecialchars($anomaly['description']) ?>
                                            </p>
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-suriname-red/10 text-suriname-red">
                                                <?= date('d M Y H:i', strtotime($anomaly['detected_at'])) ?>
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
        </div>

        <!-- Party Breakdown -->
        <div class="mt-8 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Uitsplitsing per Partij</h2>
            </div>
            <div class="p-6">
                <?php if (empty($party_results)): ?>
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="p-3 rounded-full bg-gray-100 mb-4">
                            <i class="fas fa-info-circle text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-500">Geen partijresultaten beschikbaar</p>
                    </div>
                <?php else: ?>
                    <div class="flow-root">
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($party_results as $party): ?>
                            <li class="py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            <?= htmlspecialchars($party['PartyName']) ?>
                                        </p>
                                    </div>
                                    <div class="ml-4 flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-suriname-green/10 text-suriname-green">
                                            <?= number_format($party['vote_count']) ?> stemmen (<?= $party['vote_percentage'] ?>%)
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-suriname-green h-2 rounded-full" style="width: <?= min($party['vote_percentage'], 100) ?>%"></div>
                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-8 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Recente Stemmen</h2>
            </div>
            <div class="p-6">
                <?php if (empty($recent_votes)): ?>
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="p-3 rounded-full bg-gray-100 mb-4">
                            <i class="fas fa-info-circle text-gray-400 text-2xl"></i>
                        </div>
                        <p class="text-gray-500">Geen recente stemmen om weer te geven</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tijd</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_votes as $vote): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d M Y H:i', strtotime($vote['timestamp'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($vote['district']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($vote['party']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $vote['status'] === 'valid' ? 'bg-suriname-green/10 text-suriname-green' : 'bg-suriname-red/10 text-suriname-red' ?>">
                                            <?= $vote['status'] === 'valid' ? 'Geldig' : 'Ongeldig' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- No Active Election -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 text-center">
            <div class="flex flex-col items-center justify-center py-6 text-center">
                <div class="p-4 rounded-full bg-gray-100 mb-4">
                    <i class="fas fa-info-circle text-gray-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">Geen Actieve Verkiezing</h3>
                <p class="text-gray-500">Er is momenteel geen actieve verkiezing om resultaten voor weer te geven.</p>
                <p class="text-gray-500 mt-4">Kom later terug of neem contact op met de beheerder.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout with the content
include_once __DIR__ . '/components/layout.php';
?>

<!-- Results page JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Party Results Chart
    const partyResultsCtx = document.getElementById('party-results-chart');
    if (partyResultsCtx) {
        const partyLabels = <?= json_encode(array_column($party_results ?? [], 'PartyName')) ?>;
        const partyData = <?= json_encode(array_column($party_results ?? [], 'vote_count')) ?>;
        const partyColors = [
            '#007749', '#C8102E', '#4F46E5', '#F59E0B', '#8B5CF6',
            '#EC4899', '#14B8A6', '#F97316', '#06B6D4', '#6366F1'
        ];
        
        new Chart(partyResultsCtx, {
            type: 'doughnut',
            data: {
                labels: partyLabels,
                datasets: [{
                    data: partyData,
                    backgroundColor: partyColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }

    // District Results Chart
    const districtResultsCtx = document.getElementById('district-results-chart');
    if (districtResultsCtx) {
        const districtLabels = <?= json_encode(array_column($district_results ?? [], 'DistrictName')) ?>;
        const districtData = <?= json_encode(array_column($district_results ?? [], 'vote_count')) ?>;
        
        new Chart(districtResultsCtx, {
            type: 'bar',
            data: {
                labels: districtLabels,
                datasets: [{
                    label: 'Stemmen',
                    data: districtData,
                    backgroundColor: '#007749',
                    borderColor: '#006241',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>

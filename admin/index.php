<?php
// Enable full error reporting to diagnose issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';
require_once __DIR__ . '/../include/admin_auth.php';

// This is an admin-only page.
requireAdmin();

// --- 1. GET ACTIVE ELECTION ---
$stmt = $pdo->query("SELECT ElectionID, ElectionName, ElectionDate, EndDate FROM elections WHERE LOWER(TRIM(Status)) = 'active' ORDER BY StartDate DESC LIMIT 1");
$active_election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$active_election) {
    // Render a "No Active Election" message if none is found
    $pageTitle = "Geen Verkiezing";
    ob_start();
    ?>
    <div class="container mx-auto p-8 text-center bg-white rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Geen Actieve Verkiezing</h2>
        <p class="text-gray-600">Er is momenteel geen actieve verkiezing geconfigureerd.</p>
        <a href="elections.php" class="mt-6 inline-block bg-suriname-green text-white px-6 py-2 rounded-lg shadow hover:bg-suriname-dark-green">Beheer Verkiezingen</a>
    </div>
    <?php
    $content = ob_get_clean();
    include __DIR__ . '/components/layout.php';
    exit;
}

// --- 2. FETCH ALL DATA ---
$election_id = $active_election['ElectionID'];
$electionName = $active_election['ElectionName'];
$electionDate = date('d F Y', strtotime($active_election['ElectionDate']));
$fatal_error = null;
$recent_votes = [];
$anomalies = [];

try {
    // Overall Stats
    $stmt_total_votes = $pdo->prepare("SELECT COUNT(VoteID) FROM votes WHERE ElectionID = ?");
    $stmt_total_votes->execute([$election_id]);
    $total_votes = $stmt_total_votes->fetchColumn();

    $stmt_voters = $pdo->query("SELECT COUNT(id) FROM voters WHERE status = 'active'");
    $total_voters = $stmt_voters->fetchColumn();
    
    // Calculate actual voter turnout (divide total votes by 2 since each voter casts 2 votes)
    $actual_voters = ceil($total_votes / 2); // Round up to account for any incomplete voting
    $turnout_percentage = $total_voters > 0 ? min(100, round(($actual_voters / $total_voters) * 100, 1)) : 0;

    // Party Results (for chart)
    $stmt_party = $pdo->prepare("SELECT p.PartyName, COUNT(v.VoteID) as vote_count FROM parties p LEFT JOIN candidates c ON p.PartyID = c.PartyID LEFT JOIN votes v ON c.CandidateID = v.CandidateID AND v.ElectionID = ? GROUP BY p.PartyID, p.PartyName ORDER BY vote_count DESC");
    $stmt_party->execute([$election_id]);
    $party_results = $stmt_party->fetchAll(PDO::FETCH_ASSOC);
    $leading_party = (!empty($party_results) && $party_results[0]['vote_count'] > 0) ? $party_results[0]['PartyName'] : 'N/A';

    // District Results (for chart)
    $stmt_district = $pdo->prepare("SELECT d.DistrictName, COUNT(v.VoteID) as vote_count FROM districten d LEFT JOIN voters vt ON d.DistrictID = vt.district_id LEFT JOIN votes v ON vt.id = v.UserID AND v.ElectionID = ? GROUP BY d.DistrictID, d.DistrictName ORDER BY d.DistrictName ASC");
    $stmt_district->execute([$election_id]);
    $district_results = $stmt_district->fetchAll(PDO::FETCH_ASSOC);

    // Fetch DNA candidates
    $stmt_dna = $pdo->prepare("
        SELECT c.CandidateID, c.Name, p.PartyName, d.DistrictName, c.PartyID, c.DistrictID,
               p.Logo as party_logo, COUNT(v.VoteID) AS vote_count
        FROM candidates c
        JOIN parties p ON c.PartyID = p.PartyID
        JOIN districten d ON c.DistrictID = d.DistrictID
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID
        WHERE c.CandidateType = 'DNA' AND c.ElectionID = ?
        GROUP BY c.CandidateID
        ORDER BY vote_count DESC
        LIMIT 50
    ");
    $stmt_dna->execute([$election_id]);
    $dna_results = $stmt_dna->fetchAll(PDO::FETCH_ASSOC);

    // Fetch RR candidates
    $stmt_rr = $pdo->prepare("
        SELECT c.CandidateID, c.Name, p.PartyName, d.DistrictName, c.PartyID, c.DistrictID,
               c.ResortID, r.name as ResortName, p.Logo as party_logo, COUNT(v.VoteID) AS vote_count
        FROM candidates c
        JOIN parties p ON c.PartyID = p.PartyID
        JOIN districten d ON c.DistrictID = d.DistrictID
        LEFT JOIN resorts r ON c.ResortID = r.id
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID
        WHERE c.CandidateType = 'RR' AND c.ElectionID = ?
        GROUP BY c.CandidateID
        ORDER BY vote_count DESC
        LIMIT 50
    ");
    $stmt_rr->execute([$election_id]);
    $resorts_results = $stmt_rr->fetchAll(PDO::FETCH_ASSOC);

    // Get list of districts for filter
    $stmt = $pdo->query("SELECT DISTINCT DistrictID, DistrictName FROM districten ORDER BY DistrictName");
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Admin-only data
    $stmt_recent = $pdo->prepare("SELECT v.TimeStamp as ts, d.DistrictName as district, p.PartyName as party, c.Name as candidate FROM votes v JOIN voters vt ON v.UserID = vt.id JOIN districten d ON vt.district_id = d.DistrictID JOIN candidates c ON v.CandidateID = c.CandidateID JOIN parties p ON c.PartyID = p.PartyID WHERE v.ElectionID = ? ORDER BY v.TimeStamp DESC LIMIT 5");
    $stmt_recent->execute([$election_id]);
    $recent_votes = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

    $stmt_anomalies = $pdo->prepare("SELECT vt.first_name, vt.last_name, COUNT(v.VoteID) as vote_count FROM votes v JOIN voters vt ON v.UserID = vt.id WHERE v.ElectionID = ? GROUP BY v.UserID, vt.first_name, vt.last_name HAVING COUNT(v.VoteID) > 2");
    $stmt_anomalies->execute([$election_id]);
    $anomalies = $stmt_anomalies->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Graceful error handling
    error_log("Admin dashboard critical error: " . $e->getMessage());
    $fatal_error = "Kon de pagina niet laden. Controleer de logs.";
}

// --- 3. RENDER PAGE ---
$pageTitle = "Admin Dashboard";
ob_start();

if (isset($fatal_error)): ?>
    <div class="container mx-auto p-8 text-center bg-red-100 text-red-700 rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold">Kritieke Fout</h2>
        <p><?= $fatal_error ?></p>
    </div>
<?php else: ?>
<style>
    .stat-card h3, .stat-card p { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    table td { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .tab-button { padding: 0.75rem 1rem; font-weight: 500; cursor: pointer; border-bottom: 3px solid transparent; color: #6b7280; }
    .tab-button.active { border-bottom-color: #007749; color: #111827; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
    .progress-bar { background: linear-gradient(90deg, #007749 0%, #00995D 100%); }
</style>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" id="dashboard-page">
    <!-- Dashboard Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2 flex items-center"><i class="fas fa-gauge-high mr-3 text-suriname-green"></i>Admin Dashboard</h1>
        <p class="text-gray-700">Welkom bij het beheer van de verkiezingen. Overzicht van statistieken en snelle acties.</p>
    </div>

    <!-- Snelle Acties -->
    <div class="mb-10">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center"><i class="fas fa-bolt mr-2 text-suriname-green"></i>Snelle acties</h2>
        <div class="flex flex-wrap gap-4">
            <a href="elections.php" class="flex items-center bg-white shadow rounded-xl px-6 py-4 text-gray-900 font-semibold hover:bg-gray-100 transition btn-hover"><i class="fas fa-calendar-check mr-2 text-suriname-green"></i>Nieuwe Verkiezing</a>
            <a href="voters.php" class="flex items-center bg-white shadow rounded-xl px-6 py-4 text-gray-900 font-semibold hover:bg-gray-100 transition btn-hover"><i class="fas fa-users mr-2 text-suriname-green"></i>Kiezers beheren</a>
            <a href="candidates.php" class="flex items-center bg-white shadow rounded-xl px-6 py-4 text-gray-900 font-semibold hover:bg-gray-100 transition btn-hover"><i class="fas fa-user-tie mr-2 text-suriname-green"></i>Kandidaten beheren</a>
            <a href="results.php" class="flex items-center bg-white shadow rounded-xl px-6 py-4 text-gray-900 font-semibold hover:bg-gray-100 transition btn-hover"><i class="fas fa-chart-bar mr-2 text-suriname-green"></i>Resultaten</a>
        </div>
    </div>

    <!-- Statistieken -->
    <div class="mb-10">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center"><i class="fas fa-chart-pie mr-2 text-suriname-green"></i>Statistieken</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-8 text-gray-900">
            <div class="stat-card bg-white p-6 rounded-xl shadow hover:bg-gray-100 transition border flex flex-col items-center"><i class="fas fa-users fa-2x mb-2 text-suriname-green"></i><p>Kiezers</p><h3 class="text-2xl font-bold"><?= number_format($total_voters) ?></h3></div>
            <div class="stat-card bg-white p-6 rounded-xl shadow hover:bg-gray-100 transition border flex flex-col items-center"><i class="fas fa-user-tie fa-2x mb-2 text-suriname-green"></i><p>Kandidaten</p><h3 class="text-2xl font-bold"><?= count($dna_results) + count($resorts_results) ?></h3></div>
            <div class="stat-card bg-white p-6 rounded-xl shadow hover:bg-gray-100 transition border flex flex-col items-center"><i class="fas fa-vote-yea fa-2x mb-2 text-suriname-green"></i><p>Stemmen</p><h3 class="text-2xl font-bold"><?= number_format($total_votes) ?></h3></div>
            <div class="stat-card bg-white p-6 rounded-xl shadow hover:bg-gray-100 transition border flex flex-col items-center"><i class="fas fa-trophy fa-2x mb-2 text-suriname-green"></i><p>Leidende Partij</p><h3 class="text-2xl font-bold truncate" title="<?= htmlspecialchars($leading_party) ?>"><?= htmlspecialchars($leading_party) ?></h3></div>
        </div>
    </div>

    <!-- Overige secties (zoals tabbladen) -->
    <div class="mb-10">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center"><i class="fas fa-list-ul mr-2 text-suriname-green"></i>Overzicht</h2>
        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex -mb-px">
                <button class="tab-button active" data-target="#overview-panel"><i class="fas fa-tachometer-alt mr-2"></i>Overzicht</button>
                <button class="tab-button" data-target="#candidates-panel"><i class="fas fa-users mr-2"></i>Kandidaten</button>
                <button class="tab-button" data-target="#charts-panel"><i class="fas fa-chart-pie mr-2"></i>Grafieken</button>
                <button class="tab-button" data-target="#activity-panel"><i class="fas fa-history mr-2"></i>Activiteit</button>
            </nav>
        </div>

        <!-- Tab Panels -->
        <div id="overview-panel" class="tab-panel active">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Resultaten per Partij</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach($party_results as $index => $party): ?>
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <div class="font-semibold text-gray-800 mb-2"><?= htmlspecialchars($party['PartyName']) ?></div>
                        <div class="h-2 bg-gray-200 rounded-full mb-2"><div class="h-full progress-bar" style="width: <?= ($total_votes > 0) ? ($party['vote_count'] / $total_votes) * 100 : 0 ?>%;"></div></div>
                        <div class="flex justify-between text-sm"><span><?= number_format($party['vote_count']) ?> stemmen</span><span><?= round(($party['vote_count'] / max($total_votes, 1)) * 100, 1) ?>%</span></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="candidates-panel" class="tab-panel">
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-3"><i class="fas fa-landmark mr-2"></i>Resultaten DNA</h2>
                    <div class="overflow-x-auto border rounded-lg"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kandidaat</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Partij</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">District</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stemmen</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><?php foreach ($dna_results as $c): ?><tr><td class="px-4 py-2"><?= htmlspecialchars($c['Name']) ?></td><td class="px-4 py-2"><?= htmlspecialchars($c['PartyName']) ?></td><td class="px-4 py-2"><?= htmlspecialchars($c['DistrictName']) ?></td><td class="px-4 py-2 font-medium"><?= $c['vote_count'] ?></td></tr><?php endforeach; ?></tbody></table></div>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-3"><i class="fas fa-building mr-2"></i>Resultaten Resortsraden</h2>
                    <div class="overflow-x-auto border rounded-lg"><table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kandidaat</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Partij</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">District</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Resort</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stemmen</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><?php foreach ($resorts_results as $c): ?><tr><td class="px-4 py-2"><?= htmlspecialchars($c['Name']) ?></td><td class="px-4 py-2"><?= htmlspecialchars($c['PartyName']) ?></td><td class="px-4 py-2"><?= htmlspecialchars($c['DistrictName']) ?></td><td class="px-4 py-2"><?= htmlspecialchars($c['ResortName'] ?? 'Onbekend') ?></td><td class="px-4 py-2 font-medium"><?= $c['vote_count'] ?></td></tr><?php endforeach; ?></tbody></table></div>
                </div>
            </div>
        </div>

        <div id="charts-panel" class="tab-panel">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white p-4 rounded-lg shadow-sm border"><h3 class="font-semibold text-lg mb-2">Stemmen per Partij</h3><div style="height: 400px;"><canvas id="party-chart"></canvas></div></div>
                <div class="bg-white p-4 rounded-lg shadow-sm border"><h3 class="font-semibold text-lg mb-2">Stemmen per District</h3><div style="height: 400px;"><canvas id="district-chart"></canvas></div></div>
            </div>
        </div>

        <div id="activity-panel" class="tab-panel">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-xl shadow-lg border"><div class="p-4 font-semibold border-b">Recente Activiteit</div><div class="p-4"><?php if(empty($recent_votes)): ?><p class="text-center text-gray-500">Geen recente stemmen.</p><?php else: ?><ul class="divide-y divide-gray-200"><?php foreach($recent_votes as $v): ?><li><?= date('H:i', strtotime($v['ts'])) ?>: Stem op <strong><?= htmlspecialchars($v['candidate']) ?></strong></li><?php endforeach; ?></ul><?php endif; ?></div></div>
                <div class="bg-white rounded-xl shadow-lg border"><div class="p-4 font-semibold border-b">Anomalie Detectie</div><div class="p-4"><?php if(empty($anomalies)): ?><p class="text-center text-gray-500">Geen afwijkingen.</p><?php else: ?><ul class="divide-y divide-gray-200"><?php foreach($anomalies as $a): ?><li class="text-red-600">Kiezer <?= htmlspecialchars($a['first_name']) ?> heeft <?= $a['vote_count'] ?> stemmen.</li><?php endforeach; ?></ul><?php endif; ?></div></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();

// --- 4. JAVASCRIPT & STYLES ---
$party_labels = json_encode(array_column($party_results, 'PartyName'));
$party_data = json_encode(array_column($party_results, 'vote_count'));
$district_labels = json_encode(array_column($district_results, 'DistrictName'));
$district_data = json_encode(array_column($district_results, 'vote_count'));

$pageScript = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab-button');
    const panels = document.querySelectorAll('.tab-panel');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            const targetPanel = document.querySelector(tab.dataset.target);
            if (targetPanel) targetPanel.classList.add('active');
            if (tab.dataset.target === '#charts-panel' && !tab.dataset.chartRendered) {
                renderCharts();
                tab.dataset.chartRendered = true;
            }
        });
    });

    function renderCharts() {
        const partyCtx = document.getElementById('party-chart').getContext('2d');
        new Chart(partyCtx, { type: 'bar', data: { labels: $party_labels, datasets: [{ label: 'Stemmen', data: $party_data, backgroundColor: '#007749' }] }, options: { responsive: true, maintainAspectRatio: false } });
        const districtCtx = document.getElementById('district-chart').getContext('2d');
        new Chart(districtCtx, { type: 'doughnut', data: { labels: $district_labels, datasets: [{ label: 'Stemmen', data: $district_data, backgroundColor: ['#007749', '#C8102E', '#FFC72C', '#0033A0'] }] }, options: { responsive: true, maintainAspectRatio: false } });
    }
    // Initially render charts if the tab is active
    if (document.querySelector('.tab-button[data-target="#charts-panel"]')?.classList.contains('active')) {
        renderCharts();
        document.querySelector('.tab-button[data-target="#charts-panel"]').dataset.chartRendered = true;
    }
});
JS;

include __DIR__ . '/components/layout.php';
?>


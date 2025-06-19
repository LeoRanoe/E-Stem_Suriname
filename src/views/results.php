<?php
// Enable full error reporting to diagnose issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_connect.php';

// --- 1. GET ACTIVE ELECTION ---
$stmt = $pdo->query("SELECT ElectionID, ElectionName, ElectionDate, EndDate FROM elections WHERE LOWER(TRIM(Status)) = 'active' ORDER BY StartDate DESC LIMIT 1");
$active_election = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = "Verkiezingsresultaten";
include __DIR__ . '/../../include/header.php';

if (!$active_election) {
    echo '<main class="container mx-auto p-4 md:p-8"><div class="text-center bg-white rounded-xl shadow-lg p-8"><h2 class="text-2xl font-bold text-gray-800 mb-4">Geen Actieve Verkiezing</h2><p class="text-gray-600">Er is momenteel geen actieve verkiezing geconfigureerd.</p></div></main>';
    include __DIR__ . '/../../include/footer.php';
    exit;
}

// --- 2. FETCH ALL DATA ---
$election_id = $active_election['ElectionID'];
$electionName = $active_election['ElectionName'];
$electionDate = date('d F Y', strtotime($active_election['ElectionDate']));
$fatal_error = null;

try {
    // Overall Stats
    $stmt_total_votes = $pdo->prepare("SELECT COUNT(VoteID) FROM votes WHERE ElectionID = ?");
    $stmt_total_votes->execute([$election_id]);
    $total_votes = $stmt_total_votes->fetchColumn();

    $stmt_voters = $pdo->query("SELECT COUNT(id) FROM voters WHERE status = 'active'");
    $total_voters = $stmt_voters->fetchColumn();
    
    $actual_voters = ceil($total_votes / 2);
    $turnout_percentage = $total_voters > 0 ? min(100, round(($actual_voters / $total_voters) * 100, 1)) : 0;

    // Party Results
    $stmt_party = $pdo->prepare("SELECT p.PartyName, COUNT(v.VoteID) as vote_count FROM parties p LEFT JOIN candidates c ON p.PartyID = c.PartyID LEFT JOIN votes v ON c.CandidateID = v.CandidateID AND v.ElectionID = ? GROUP BY p.PartyID, p.PartyName ORDER BY vote_count DESC");
    $stmt_party->execute([$election_id]);
    $party_results = $stmt_party->fetchAll(PDO::FETCH_ASSOC);
    $leading_party = (!empty($party_results) && $party_results[0]['vote_count'] > 0) ? $party_results[0]['PartyName'] : 'N/A';
    
    // District Results (for chart)
    $stmt_district = $pdo->prepare("SELECT d.DistrictName, COUNT(v.VoteID) as vote_count FROM districten d LEFT JOIN voters vt ON d.DistrictID = vt.district_id LEFT JOIN votes v ON vt.id = v.UserID AND v.ElectionID = ? GROUP BY d.DistrictID, d.DistrictName ORDER BY d.DistrictName ASC");
    $stmt_district->execute([$election_id]);
    $district_results = $stmt_district->fetchAll(PDO::FETCH_ASSOC);

    // DNA candidates
    $stmt_dna = $pdo->prepare("SELECT c.Name, p.PartyName, d.DistrictName, COUNT(v.VoteID) AS vote_count FROM candidates c JOIN parties p ON c.PartyID = p.PartyID JOIN districten d ON c.DistrictID = d.DistrictID LEFT JOIN votes v ON c.CandidateID = v.CandidateID WHERE c.CandidateType = 'DNA' AND c.ElectionID = ? GROUP BY c.CandidateID ORDER BY vote_count DESC LIMIT 50");
    $stmt_dna->execute([$election_id]);
    $dna_results = $stmt_dna->fetchAll(PDO::FETCH_ASSOC);

    // RR candidates
    $stmt_rr = $pdo->prepare("SELECT c.Name, p.PartyName, d.DistrictName, r.name as ResortName, COUNT(v.VoteID) AS vote_count FROM candidates c JOIN parties p ON c.PartyID = p.PartyID JOIN districten d ON c.DistrictID = d.DistrictID LEFT JOIN resorts r ON c.ResortID = r.id LEFT JOIN votes v ON c.CandidateID = v.CandidateID WHERE c.CandidateType = 'RR' AND c.ElectionID = ? GROUP BY c.CandidateID ORDER BY vote_count DESC LIMIT 50");
    $stmt_rr->execute([$election_id]);
    $resorts_results = $stmt_rr->fetchAll(PDO::FETCH_ASSOC);
    
    $districts = $pdo->query("SELECT DISTINCT DistrictID, DistrictName FROM districten ORDER BY DistrictName")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Public results page error: " . $e->getMessage());
    $fatal_error = "Kon de resultaten niet laden. Probeer het later opnieuw.";
}

?>
<main class="container mx-auto p-4 md:p-8">
<?php if ($fatal_error): ?>
    <div class="text-center bg-red-100 text-red-700 rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold">Fout</h2>
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

<div id="results-page">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between mb-6 gap-4">
        <div>
            <h1 class="text-3xl font-bold">Resultaten: <?= htmlspecialchars($electionName) ?></h1>
            <p class="text-sm text-gray-500">Laatst bijgewerkt: <?= date('d-m-Y H:i:s') ?></p>
        </div>
    </div>

    <!-- Top Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 text-gray-800">
        <div class="stat-card bg-white p-4 rounded-lg shadow-sm border"><p>Totaal Stemmen</p><h3 class="text-xl font-bold"><?= number_format($total_votes) ?></h3></div>
        <div class="stat-card bg-white p-4 rounded-lg shadow-sm border"><p>Opkomst</p><h3 class="text-xl font-bold"><?= $turnout_percentage ?>%</h3></div>
        <div class="stat-card bg-white p-4 rounded-lg shadow-sm border"><p>Leidende Partij</p><h3 class="text-xl font-bold truncate" title="<?= htmlspecialchars($leading_party) ?>"><?= htmlspecialchars($leading_party) ?></h3></div>
        <div class="stat-card bg-white p-4 rounded-lg shadow-sm border"><p>Deelnemende Partijen</p><h3 class="text-xl font-bold"><?= count($party_results) ?></h3></div>
    </div>
    
    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
        <nav class="flex -mb-px">
            <button class="tab-button active" data-target="#overview-panel"><i class="fas fa-tachometer-alt mr-2"></i>Overzicht</button>
            <button class="tab-button" data-target="#candidates-panel"><i class="fas fa-users mr-2"></i>Kandidaten</button>
            <button class="tab-button" data-target="#charts-panel"><i class="fas fa-chart-pie mr-2"></i>Grafieken</button>
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
</div>
<?php endif; ?>
</main>

<?php
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
        const partyCtx = document.getElementById('party-chart')?.getContext('2d');
        if (partyCtx) new Chart(partyCtx, { type: 'bar', data: { labels: $party_labels, datasets: [{ label: 'Stemmen', data: $party_data, backgroundColor: '#007749' }] }, options: { responsive: true, maintainAspectRatio: false } });
        
        const districtCtx = document.getElementById('district-chart')?.getContext('2d');
        if (districtCtx) new Chart(districtCtx, { type: 'doughnut', data: { labels: $district_labels, datasets: [{ label: 'Stemmen', data: $district_data, backgroundColor: ['#007749', '#C8102E', '#FFC72C', '#0033A0'] }] }, options: { responsive: true, maintainAspectRatio: false } });
    }
    
    // Initially render charts if the tab is active
    if (document.querySelector('.tab-button[data-target="#charts-panel"]')?.classList.contains('active')) {
        renderCharts();
        document.querySelector('.tab-button[data-target="#charts-panel"]').dataset.chartRendered = true;
    } else if(document.querySelector('.tab-button.active[data-target="#overview-panel"]')) {
        // If overview is active by default, still check if chart tab needs rendering on first click
    }
});
JS;

// Add Chart.js and the page-specific script
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>';
if (!empty($pageScript)) {
    echo '<script>' . $pageScript . '</script>';
}

include __DIR__ . '/../../include/footer.php';
?>
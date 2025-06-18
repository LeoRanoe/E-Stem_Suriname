<?php
// Enable full error reporting to diagnose issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';
require_once __DIR__ . '/../include/admin_auth.php';
requireAdmin();

// --- 1. GET ACTIVE ELECTION ---
$stmt = $pdo->query("SELECT ElectionID, ElectionName FROM elections WHERE LOWER(TRIM(Status)) = 'active' ORDER BY StartDate DESC LIMIT 1");
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
try {
    // Overall Stats
    $stmt_total_votes = $pdo->prepare("SELECT COUNT(VoteID) FROM votes WHERE ElectionID = ?");
    $stmt_total_votes->execute([$election_id]);
    $total_votes = $stmt_total_votes->fetchColumn();

    $stmt_voters = $pdo->query("SELECT COUNT(id) FROM voters WHERE status = 'active'");
    $total_voters = $stmt_voters->fetchColumn();
    $turnout_percentage = $total_voters > 0 ? round(($total_votes / $total_voters) * 100, 1) : 0;

    // Party Results (for chart)
    $stmt_party = $pdo->prepare("SELECT p.PartyName, COUNT(v.VoteID) as vote_count FROM parties p LEFT JOIN candidates c ON p.PartyID = c.PartyID LEFT JOIN votes v ON c.CandidateID = v.CandidateID AND v.ElectionID = ? GROUP BY p.PartyID, p.PartyName ORDER BY vote_count DESC");
    $stmt_party->execute([$election_id]);
    $party_results = $stmt_party->fetchAll(PDO::FETCH_ASSOC);
    $leading_party = (!empty($party_results) && $party_results[0]['vote_count'] > 0) ? $party_results[0]['PartyName'] : 'N/A';

    // District Results (for chart)
    $stmt_district = $pdo->prepare("SELECT d.DistrictName, COUNT(v.VoteID) as vote_count FROM districten d LEFT JOIN voters vt ON d.DistrictID = vt.district_id LEFT JOIN votes v ON vt.id = v.UserID AND v.ElectionID = ? GROUP BY d.DistrictID, d.DistrictName ORDER BY d.DistrictName ASC");
    $stmt_district->execute([$election_id]);
    $district_results = $stmt_district->fetchAll(PDO::FETCH_ASSOC);

    // Detailed Results (for table)
    $stmt_detailed = $pdo->prepare("SELECT d.DistrictName as district, r.name as resort, p.PartyName as party, c.Name as candidate, COUNT(v.VoteID) as vote_count FROM votes v JOIN candidates c ON v.CandidateID = c.CandidateID JOIN parties p ON c.PartyID = p.PartyID JOIN voters u ON v.UserID = u.id JOIN resorts r ON u.resort_id = r.id JOIN districten d ON u.district_id = d.DistrictID WHERE v.ElectionID = ? GROUP BY d.DistrictName, r.name, p.PartyName, c.Name ORDER BY vote_count DESC");
    $stmt_detailed->execute([$election_id]);
    $detailed_results = $stmt_detailed->fetchAll(PDO::FETCH_ASSOC);

    // Data for Filters
    $stmt_geo = $pdo->query("SELECT d.DistrictID, d.DistrictName, r.id as ResortID, r.name as ResortName FROM districten d JOIN resorts r ON d.DistrictID = r.district_id ORDER BY d.DistrictName, r.name");
    $geo_data_raw = $stmt_geo->fetchAll(PDO::FETCH_ASSOC);
    $geo_data = [];
    foreach ($geo_data_raw as $row) { $geo_data[$row['DistrictName']][] = $row['ResortName']; }
    $all_parties = array_unique(array_column($detailed_results, 'party'));
    sort($all_parties);

    // Recent Votes & Anomalies
    $stmt_recent = $pdo->prepare("SELECT v.TimeStamp as ts, d.DistrictName as district, p.PartyName as party, c.Name as candidate FROM votes v JOIN voters vt ON v.UserID = vt.id JOIN districten d ON vt.district_id = d.DistrictID JOIN candidates c ON v.CandidateID = c.CandidateID JOIN parties p ON c.PartyID = p.PartyID WHERE v.ElectionID = ? ORDER BY v.TimeStamp DESC LIMIT 5");
    $stmt_recent->execute([$election_id]);
    $recent_votes = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

    $stmt_anomalies = $pdo->prepare("SELECT vt.first_name, vt.last_name, COUNT(v.VoteID) as vote_count FROM votes v JOIN voters vt ON v.UserID = vt.id WHERE v.ElectionID = ? GROUP BY v.UserID, vt.first_name, vt.last_name HAVING COUNT(v.VoteID) > 1");
    $stmt_anomalies->execute([$election_id]);
    $anomalies = $stmt_anomalies->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Graceful error handling
    error_log("Results page critical error: " . $e->getMessage());
    $fatal_error = "Kon de pagina niet laden. Controleer de logs.";
}

// --- 3. RENDER PAGE ---
$pageTitle = "Verkiezingsresultaten";
ob_start();

if (isset($fatal_error)): ?>
    <div class="container mx-auto p-8 text-center bg-red-100 text-red-700 rounded-xl shadow-lg">
        <h2 class="text-2xl font-bold">Kritieke Fout</h2>
        <p><?= $fatal_error ?></p>
    </div>
<?php else: ?>
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8" id="results-page">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between mb-6 gap-4 print:hidden">
        <div>
            <h1 class="text-3xl font-bold">Verkiezingsresultaten: <?= htmlspecialchars($active_election['ElectionName']) ?></h1>
            <p class="text-sm text-gray-500">Laatst bijgewerkt: <?= date('d-m-Y H:i:s') ?></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="<?= BASE_URL ?>/src/api/export_results.php?format=csv" class="btn-primary"><i class="fas fa-file-csv mr-2"></i>Export CSV</a>
            <button id="print-button" class="btn-secondary"><i class="fas fa-print mr-2"></i>Print / PDF</button>
        </div>
    </div>

    <!-- Top Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 text-gray-800">
        <div class="stat-card"><p>Totaal Stemmen</p><h3 class="stat-value"><?= number_format($total_votes) ?></h3></div>
        <div class="stat-card"><p>Opkomst</p><h3 class="stat-value"><?= $turnout_percentage ?>%</h3></div>
        <div class="stat-card"><p>Leidende Partij</p><h3 class="stat-value"><?= htmlspecialchars($leading_party) ?></h3></div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-xl shadow-lg border"><div class="card-header">Resultaten per Partij</div><div class="p-4"><canvas id="party-results-chart"></canvas></div></div>
        <div class="bg-white rounded-xl shadow-lg border"><div class="card-header">Resultaten per District</div><div class="p-4"><canvas id="district-results-chart"></canvas></div></div>
    </div>

    <!-- Activity & Anomalies -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-xl shadow-lg border">
            <div class="card-header">Recente Activiteit</div>
            <div class="p-4">
                <?php if (empty($recent_votes)): ?> <p class="text-center text-gray-500 py-4">Geen recente stemmen.</p> <?php else: ?>
                <ul class="divide-y divide-gray-200">
                    <?php foreach($recent_votes as $v): ?>
                    <li class="py-2"><span class="text-xs text-gray-400"><?= date('H:i:s', strtotime($v['ts'])) ?>:</span> Stem op <strong><?= htmlspecialchars($v['candidate']) ?></strong> (<?= htmlspecialchars($v['party']) ?>) in <?= htmlspecialchars($v['district']) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg border">
            <div class="card-header">Anomalie Detectie</div>
             <div class="p-4">
                <?php if (empty($anomalies)): ?> <p class="text-center text-gray-500 py-4">Geen afwijkingen gedetecteerd.</p> <?php else: ?>
                <ul class="divide-y divide-gray-200">
                    <?php foreach($anomalies as $a): ?>
                    <li class="py-2 text-sm text-red-600"><i class="fas fa-exclamation-triangle mr-2"></i><strong>Meerdere Stemmen:</strong> Kiezer <?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?> heeft <strong><?= $a['vote_count'] ?></strong> stemmen uitgebracht.</li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Detailed Results Table -->
    <div class="bg-white rounded-xl shadow-lg border overflow-hidden">
        <div class="card-header flex-wrap justify-between items-center gap-4 print:hidden">
            <h2 class="text-xl font-semibold">Gedetailleerde Resultaten</h2>
            <div class="flex items-center gap-2 flex-wrap">
                <select id="district-filter" class="form-select"><option value="">Alle Districten</option><?php foreach (array_keys($geo_data) as $d):?><option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option><?php endforeach; ?></select>
                <select id="resort-filter" class="form-select"><option value="">Alle Resorts</option></select>
                <select id="party-filter" class="form-select"><option value="">Alle Partijen</option><?php foreach ($all_parties as $p):?><option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option><?php endforeach; ?></select>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full" id="results-table">
                <thead class="bg-gray-50">
                    <tr><th>District</th><th>Resort</th><th>Partij</th><th>Kandidaat</th><th>Stemmen</th></tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($detailed_results)): ?> <tr><td colspan="5" class="p-4 text-center text-gray-500">Geen stemmen tot nu toe.</td></tr> <?php else: ?>
                    <?php foreach ($detailed_results as $r): ?>
                        <tr data-district="<?= htmlspecialchars($r['district']) ?>" data-resort="<?= htmlspecialchars($r['resort']) ?>" data-party="<?= htmlspecialchars($r['party']) ?>">
                            <td><?= htmlspecialchars($r['district']) ?></td>
                            <td><?= htmlspecialchars($r['resort']) ?></td>
                            <td><?= htmlspecialchars($r['party']) ?></td>
                            <td><?= htmlspecialchars($r['candidate']) ?></td>
                            <td class="font-semibold"><?= number_format($r['vote_count']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// --- 4. JAVASCRIPT & STYLES ---
$party_labels = json_encode(array_column($party_results, 'PartyName'));
$party_data = json_encode(array_column($party_results, 'vote_count'));
$district_labels = json_encode(array_column($district_results, 'DistrictName'));
$district_data = json_encode(array_column($district_results, 'vote_count'));
$geo_data_json = json_encode($geo_data);

$pageScript = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    const geoData = $geo_data_json;

    // Helper to render chart or show message
    function renderChart(ctx, type, labels, data, chartLabel, colors) {
        const allZero = data.every(item => item === 0);
        if (allZero) {
            ctx.parentElement.innerHTML = '<div class="text-center py-8 text-gray-500">Nog geen stemmen ontvangen.</div>';
            return;
        }
        new Chart(ctx, {
            type: type,
            data: { labels: labels, datasets: [{ label: chartLabel, data: data, backgroundColor: colors }] },
            options: { responsive: true, maintainAspectRatio: false, scales: type === 'bar' ? { y: { beginAtZero: true } } : {} }
        });
    }

    // Render Charts
    renderChart(document.getElementById('party-results-chart'), 'bar', $party_labels, $party_data, 'Stemmen per Partij', '#007749');
    const districtColors = ['#C8102E', '#007749', '#FFC72C', '#0033A0', '#6A2E9A', '#F15A29', '#00AEEF', '#8CC63F', '#662D91', '#EC008C'];
    renderChart(document.getElementById('district-results-chart'), 'pie', $district_labels, $district_data, 'Stemmen per District', districtColors);

    // Dynamic Filter Logic
    const districtFilter = document.getElementById('district-filter');
    const resortFilter = document.getElementById('resort-filter');
    const partyFilter = document.getElementById('party-filter');
    const tableRows = document.querySelectorAll('#results-table tbody tr');

    districtFilter.addEventListener('change', () => {
        const selectedDistrict = districtFilter.value;
        resortFilter.innerHTML = '<option value="">Alle Resorts</option>'; // Reset
        if (selectedDistrict && geoData[selectedDistrict]) {
            geoData[selectedDistrict].forEach(resort => {
                resortFilter.add(new Option(resort, resort));
            });
        }
        filterTable();
    });

    function filterTable() {
        const districtVal = districtFilter.value;
        const resortVal = resortFilter.value;
        const partyVal = partyFilter.value;

        tableRows.forEach(row => {
            const rowDistrict = row.dataset.district;
            const rowResort = row.dataset.resort;
            const rowParty = row.dataset.party;
            
            const show = 
                (!districtVal || rowDistrict === districtVal) &&
                (!resortVal || rowResort === resortVal) &&
                (!partyVal || rowParty === partyVal);
            
            row.style.display = show ? '' : 'none';
        });
    }

    resortFilter.addEventListener('change', filterTable);
    partyFilter.addEventListener('change', filterTable);
    
    // Print functionality
    document.getElementById('print-button')?.addEventListener('click', () => window.print());
});
JS;

$content = ob_get_clean();
include __DIR__ . '/components/layout.php';
?> 
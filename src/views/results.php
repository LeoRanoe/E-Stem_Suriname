<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_connect.php';

// Optimized function to fetch election results with simplified query and LIMIT
function fetchElectionResults($pdo, $election_type, $limit = 50) { // Increased limit for better client-side search
    $sql = "
        SELECT c.CandidateID, c.Name, p.PartyName, d.DistrictName, r.name as ResortName,
               COUNT(v.VoteID) AS vote_count
        FROM candidates c
        JOIN parties p ON c.PartyID = p.PartyID
        JOIN districten d ON c.DistrictID = d.DistrictID
        LEFT JOIN resorts r ON c.ResortID = r.id
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID
        WHERE c.CandidateType = ?
        GROUP BY c.CandidateID
        ORDER BY vote_count DESC
        LIMIT ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$election_type, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle simple filtering if requested
$district_filter = isset($_GET['district']) ? trim($_GET['district']) : '';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : ''; // Kept for server-side if needed

// Fetch only summary data - no heavy queries
try {
    // Get active election info
    $stmt = $pdo->query("SELECT ElectionName, ElectionDate FROM elections WHERE Status = 'active' LIMIT 1");
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    $electionName = $election ? $election['ElectionName'] : 'Huidige Verkiezing';
    $electionDate = $election ? date('d F Y', strtotime($election['ElectionDate'])) : date('d F Y');
    
    // Get total votes count
    $stmt = $pdo->query("SELECT COUNT(*) FROM votes");
    $totalVotes = $stmt->fetchColumn();
    
    // Get registered voters count for participation percentage
    $stmt = $pdo->query("SELECT COUNT(*) FROM voters WHERE status = 'active'");
    $totalVoters = $stmt->fetchColumn();
    $participation = $totalVoters > 0 ? round(($totalVotes / $totalVoters) * 100, 1) : 0;
    
    // Get top parties with vote counts for chart
    $stmt_party = $pdo->query("
        SELECT p.PartyName, COUNT(v.VoteID) as vote_count 
        FROM parties p 
        JOIN candidates c ON p.PartyID = c.PartyID 
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID 
        GROUP BY p.PartyID 
        ORDER BY vote_count DESC
    ");
    $partyResults = $stmt_party->fetchAll(PDO::FETCH_ASSOC);

    // Get results by district for chart
    $stmt_district = $pdo->query("
        SELECT d.DistrictName, COUNT(v.VoteID) as vote_count
        FROM votes v
        JOIN candidates c ON v.CandidateID = c.CandidateID
        JOIN districten d ON c.DistrictID = d.DistrictID
        GROUP BY d.DistrictID, d.DistrictName
        ORDER BY vote_count DESC
    ");
    $districtResults = $stmt_district->fetchAll(PDO::FETCH_ASSOC);
    
    // Get list of districts for filter
    $stmt = $pdo->query("SELECT DISTINCT DistrictID, DistrictName FROM districten ORDER BY DistrictName");
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch limited results for both election types
    $dna_results = fetchElectionResults($pdo, 'DNA');
    $resorts_results = fetchElectionResults($pdo, 'RR');
    
} catch (PDOException $e) {
    error_log("Database error in results.php: " . $e->getMessage());
    // Initialize all variables to empty arrays or 0 to prevent errors
    $dna_results = $resorts_results = $partyResults = $districtResults = $districts = [];
    $totalVotes = $totalVoters = $participation = 0;
    $electionName = 'Huidige Verkiezing';
    $electionDate = date('d F Y');
}

// Prepare data for charts
$party_chart_labels = json_encode(array_column($partyResults, 'PartyName'));
$party_chart_data = json_encode(array_column($partyResults, 'vote_count'));
$district_chart_labels = json_encode(array_column($districtResults, 'DistrictName'));
$district_chart_data = json_encode(array_column($districtResults, 'vote_count'));

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Verkiezingsresultaten - E-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body { 
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc; 
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #007749 0%, #00995D 100%);
        }
        
        .winner-badge {
            background-color: #007749;
            color: white;
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            margin-left: 0.5rem;
        }

        /* Tab Styles */
        .tab-button {
            padding: 0.75rem 1rem;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            color: #6b7280; /* gray-500 */
        }
        .tab-button.active {
            border-bottom-color: #007749; /* suriname-green */
            color: #111827; /* gray-900 */
        }
        .tab-panel {
            display: none;
        }
        .tab-panel.active {
            display: block;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'suriname': {
                            'green': '#007749',
                            'dark-green': '#006241',
                        },
                    },
                },
            },
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Live search functionality
            const searchInput = document.getElementById('candidate-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const term = this.value.toLowerCase().trim();
                    document.querySelectorAll('.candidate-row').forEach(row => {
                        const name = row.dataset.name.toLowerCase();
                        const party = row.dataset.party.toLowerCase();
                        row.style.display = (name.includes(term) || party.includes(term)) ? '' : 'none';
                    });
                });
            }
            
            // District filter submission
            const districtFilter = document.getElementById('district-filter');
            if(districtFilter) {
                districtFilter.addEventListener('change', function() {
                    this.form.submit();
                });
            }

            // Tab functionality
            const tabs = document.querySelectorAll('.tab-button');
            const panels = document.querySelectorAll('.tab-panel');
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    panels.forEach(p => p.classList.remove('active'));
                    
                    tab.classList.add('active');
                    const targetPanel = document.querySelector(tab.dataset.target);
                    if(targetPanel) {
                        targetPanel.classList.add('active');
                    }

                    // Render chart only when its tab is shown for the first time
                    if (tab.dataset.target === '#charts-panel' && !tab.dataset.chartRendered) {
                        renderCharts();
                        tab.dataset.chartRendered = true;
                    }
                });
            });

            // Chart rendering function
            function renderCharts() {
                // Party Results Chart
                const partyCtx = document.getElementById('party-chart').getContext('2d');
                new Chart(partyCtx, {
                    type: 'bar',
                    data: {
                        labels: <?= $party_chart_labels ?>,
                        datasets: [{
                            label: 'Stemmen per Partij',
                            data: <?= $party_chart_data ?>,
                            backgroundColor: '#007749',
                            borderRadius: 4,
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });

                // District Results Chart
                const districtCtx = document.getElementById('district-chart').getContext('2d');
                new Chart(districtCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?= $district_chart_labels ?>,
                        datasets: [{
                            label: 'Stemmen per District',
                            data: <?= $district_chart_data ?>,
                            backgroundColor: ['#007749', '#C8102E', '#FFC72C', '#0033A0', '#6A2E9A', '#F15A29'],
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }
        });
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include '../../include/nav.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-6 max-w-7xl">
        <div class="bg-white p-6 rounded-xl shadow-md mb-6">
            <div class="flex justify-between items-start mb-4 flex-wrap gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-suriname-green mb-1"><?= htmlspecialchars($electionName) ?></h1>
                    <p class="text-gray-600"><i class="fas fa-calendar-alt mr-2"></i><?= $electionDate ?></p>
                </div>
                
                <div class="flex flex-wrap gap-2 items-center">
                    <form method="get" id="district-form" class="flex items-center gap-2">
                        <select name="district" id="district-filter" class="form-input border border-gray-300 rounded-md p-2 text-sm">
                            <option value="">Alle Districten</option>
                            <?php foreach($districts as $district): ?>
                                <option value="<?= htmlspecialchars($district['DistrictName']) ?>" <?= $district_filter == $district['DistrictName'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($district['DistrictName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <div class="relative">
                        <input type="text" placeholder="Zoek kandidaat of partij..." 
                               class="form-input border border-gray-300 rounded-md p-2 pl-8 text-sm" id="candidate-search">
                        <i class="fas fa-search absolute left-2.5 top-2.5 text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex -mb-px">
                    <button class="tab-button active" data-target="#overview-panel"><i class="fas fa-tachometer-alt mr-2"></i>Overzicht</button>
                    <button class="tab-button" data-target="#results-panel"><i class="fas fa-users mr-2"></i>Kandidaten</button>
                    <button class="tab-button" data-target="#charts-panel"><i class="fas fa-chart-pie mr-2"></i>Grafieken</button>
                </nav>
            </div>

            <!-- Tab Panels -->
            <div id="overview-panel" class="tab-panel active">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Verkiezingsoverzicht</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                            <div class="text-gray-500 mb-1 text-sm">Totaal Stemmen</div>
                            <div class="text-2xl font-bold text-suriname-green"><?= number_format($totalVotes) ?></div>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                            <div class="text-gray-500 mb-1 text-sm">Opkomst</div>
                            <div class="text-2xl font-bold text-suriname-green"><?= $participation ?>%</div>
                            <div class="text-xs text-gray-500"><?= number_format($totalVotes) ?> van <?= number_format($totalVoters) ?> kiezers</div>
                        </div>
                        <?php if(!empty($partyResults) && isset($partyResults[0])): ?>
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                            <div class="text-gray-500 mb-1 text-sm">Leidende Partij</div>
                            <div class="text-xl font-bold text-suriname-green flex items-center">
                                <?= htmlspecialchars($partyResults[0]['PartyName']) ?>
                            </div>
                            <div class="text-sm text-gray-500"><?= number_format($partyResults[0]['vote_count']) ?> stemmen</div>
                        </div>
                        <?php endif; ?>
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                            <div class="text-gray-500 mb-1 text-sm">Deelnemende Partijen</div>
                            <div class="text-2xl font-bold text-suriname-green"><?= count($partyResults) ?></div>
                        </div>
                    </div>
                    
                    <h3 class="text-lg font-semibold text-gray-800 mt-6 mb-3">Resultaten per Partij</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach($partyResults as $index => $party): ?>
                        <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                            <div class="font-semibold text-gray-800 mb-2 flex items-center">
                                <?= htmlspecialchars($party['PartyName']) ?>
                                <?php if($index === 0 && $party['vote_count'] > 0): ?>
                                    <span class="winner-badge"><i class="fas fa-trophy mr-1"></i> Leidend</span>
                                <?php endif; ?>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden mb-2">
                                <div class="h-full progress-bar" style="width: <?= ($totalVotes > 0) ? ($party['vote_count'] / $totalVotes) * 100 : 0 ?>%;"></div>
                            </div>
                            <div class="flex justify-between items-center text-sm">
                                <span class="font-medium"><?= number_format($party['vote_count']) ?> stemmen</span>
                                <?php if($totalVotes > 0): ?>
                                    <span class="text-gray-500"><?= round(($party['vote_count'] / $totalVotes) * 100, 1) ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div id="results-panel" class="tab-panel">
                <!-- DNA Results Section -->
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-3"><i class="fas fa-landmark mr-2 text-suriname-green"></i>Resultaten De Nationale Assemblée</h2>
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaat</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $filtered_dna_results_exist = false; ?>
                                <?php foreach ($dna_results as $index => $candidate): 
                                    if (!empty($district_filter) && $candidate['DistrictName'] != $district_filter) continue;
                                    $filtered_dna_results_exist = true;
                                ?>
                                <tr class="hover:bg-gray-50 candidate-row" data-name="<?= htmlspecialchars($candidate['Name']) ?>" data-party="<?= htmlspecialchars($candidate['PartyName']) ?>">
                                    <td class="px-4 py-3 whitespace-nowrap"><div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($candidate['Name']) ?></div></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><div class="text-sm text-suriname-green"><?= htmlspecialchars($candidate['PartyName']) ?></div></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><div class="text-sm text-gray-500"><?= htmlspecialchars($candidate['DistrictName']) ?></div></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><div class="text-sm font-medium"><?= $candidate['vote_count'] ?></div></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(!$filtered_dna_results_exist): ?>
                                <tr><td colspan="4" class="px-4 py-4 text-center text-gray-500">Geen resultaten gevonden voor deze selectie.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Resorts Results Section -->
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-3"><i class="fas fa-building mr-2 text-suriname-green"></i>Resultaten Resortsraden</h2>
                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaat</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resort</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $filtered_rr_results_exist = false; ?>
                                <?php foreach ($resorts_results as $index => $candidate): 
                                    if (!empty($district_filter) && $candidate['DistrictName'] != $district_filter) continue;
                                    $filtered_rr_results_exist = true;
                                ?>
                                <tr class="hover:bg-gray-50 candidate-row" data-name="<?= htmlspecialchars($candidate['Name']) ?>" data-party="<?= htmlspecialchars($candidate['PartyName']) ?>">
                                    <td class="px-4 py-3 whitespace-nowrap"><div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($candidate['Name']) ?></div></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><div class="text-sm text-suriname-green"><?= htmlspecialchars($candidate['PartyName']) ?></div></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><div class="text-sm text-gray-500"><?= htmlspecialchars($candidate['DistrictName']) ?></div></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><div class="text-sm text-gray-500"><?= htmlspecialchars($candidate['ResortName'] ?? 'Onbekend') ?></div></td>
                                    <td class="px-4 py-3 whitespace-nowrap"><div class="text-sm font-medium"><?= $candidate['vote_count'] ?></div></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if(!$filtered_rr_results_exist): ?>
                                <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">Geen resultaten gevonden voor deze selectie.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="charts-panel" class="tab-panel">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <h3 class="font-semibold text-lg mb-2">Stemmen per Partij</h3>
                        <div style="height: 400px;"><canvas id="party-chart"></canvas></div>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <h3 class="font-semibold text-lg mb-2">Stemmen per District</h3>
                        <div style="height: 400px;"><canvas id="district-chart"></canvas></div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>

    <?php include '../../include/footer.php'; ?>
</body>
</html>
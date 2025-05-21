<?php
session_start();
require_once __DIR__ . '/../include/db_connect.php';
require_once __DIR__ . '/../include/auth.php'; // Assuming this handles user authentication

// Check if user is logged in and has voted
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    // Redirect to login or show message
    header("Location: login.php");
    exit;
}

// Check if user has voted
$stmt_vote_check = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE UserID = ?");
$stmt_vote_check->execute([$user_id]);
$has_voted = $stmt_vote_check->fetchColumn() > 0;

if (!$has_voted) {
    $message = "U kunt de resultaten pas zien nadat u heeft gestemd.";
}

function fetchElectionResults($pdo, $election_type) {
    // Fetch candidates and votes for the given election type (e.g., 'DNA' or 'RR')
    $sql = "
        SELECT c.CandidateID, c.Name, c.PartyID, p.PartyName, c.DistrictID, d.DistrictName, c.Photo,
               COUNT(v.VoteID) AS vote_count
        FROM candidates c
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN districten d ON c.DistrictID = d.DistrictID
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID
        WHERE c.CandidateType = ?
        GROUP BY c.CandidateID, c.Name, c.PartyID, p.PartyName, c.DistrictID, d.DistrictName, c.Photo
        ORDER BY vote_count DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$election_type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$dna_results = [];
$resorts_results = [];
if ($has_voted) {
    $dna_results = fetchElectionResults($pdo, 'DNA');
    $resorts_results = fetchElectionResults($pdo, 'RR');
}

// Prepare data for charts
function prepareChartData($results) {
    $labels = [];
    $votes = [];
    foreach ($results as $candidate) {
        $labels[] = htmlspecialchars($candidate['Name']);
        $votes[] = (int)$candidate['vote_count'];
    }
    return ['labels' => $labels, 'votes' => $votes];
}

$dna_chart_data = prepareChartData($dna_results);
$resorts_chart_data = prepareChartData($resorts_results);

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Resultaten Stemmen</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 p-5">
    <nav class="flex bg-gray-200 rounded-md p-3 mb-5">
        <a href="#" class="mr-5 font-bold text-gray-600 hover:text-green-700">Overzicht</a>
        <a href="#" class="mr-auto font-bold bg-green-200 text-green-800 rounded px-3 py-1">Resultaten</a>
        <button class="bg-green-200 text-green-800 font-bold rounded px-4 py-2">Logged In</button>
    </nav>

    <header>
        <h1 class="text-2xl mb-1">Hello, <span id="username" class="font-semibold"><?= htmlspecialchars($_SESSION['username'] ?? 'Gebruiker') ?></span>.</h1>
        <p class="text-gray-600 mb-5">Hier ziet u de resultaten van de verkiezingen.</p>
    </header>

    <?php if (!$has_voted): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-10" role="alert">
            <strong class="font-bold">Info:</strong>
            <span class="block sm:inline"><?= $message ?></span>
        </div>
    <?php else: ?>

    <div class="flex justify-between bg-white p-5 rounded-lg shadow mb-10">
        <div class="flex-1 mx-3 text-center">
            <h3 class="text-lg font-semibold text-green-700 mb-3">Jouw stem</h3>
            <?php foreach ($dna_results as $candidate): ?>
                <div>
                    <img src="<?= htmlspecialchars($candidate['Photo'] ?: 'https://via.placeholder.com/80') ?>" alt="<?= htmlspecialchars($candidate['Name']) ?>" class="mx-auto rounded-full mb-2" />
                    <div><strong><?= htmlspecialchars($candidate['Name']) ?></strong></div>
                    <div><?= htmlspecialchars($candidate['PartyName']) ?></div>
                    <div><?= htmlspecialchars($candidate['DistrictName']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="flex-1 mx-3">
            <h3 class="text-lg font-semibold text-green-700 mb-3 text-center">Kleine nieuws sectie</h3>
            <div class="flex justify-around mt-2">
                <div class="max-w-[45%]">
                    <h4 class="font-semibold mb-1">Lorem ipsum</h4>
                    <p class="text-gray-600 text-sm">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                </div>
                <div class="max-w-[45%]">
                    <h4 class="font-semibold mb-1">Lorem ipsum</h4>
                    <p class="text-gray-600 text-sm">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                </div>
            </div>
        </div>
    </div>

    <hr class="border-gray-300 my-8" />

    <section class="bg-white p-5 rounded-lg shadow mb-10" id="dna-results">
        <h2 class="text-green-700 text-xl mb-3">Resultaten van De Nationale Assemblée</h2>
        <div class="mb-4">
            <label for="dna-filter" class="mr-2 font-semibold">Filter:</label>
            <select id="dna-filter" class="border border-gray-300 rounded px-3 py-1 text-sm">
                <option value="all">Alle Politieke Partijen</option>
                <?php
                $dna_parties = array_unique(array_map(function($c) { return $c['PartyName']; }, $dna_results));
                foreach ($dna_parties as $party) {
                    echo '<option value="' . htmlspecialchars($party) . '">' . htmlspecialchars($party) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="max-w-3xl mx-auto">
            <canvas id="dnaChart"></canvas>
        </div>
    </section>

    <section class="bg-white p-5 rounded-lg shadow mb-10" id="resorts-results">
        <h2 class="text-green-700 text-xl mb-3">Resultaten van Resortsraden</h2>
        <div class="mb-4">
            <label for="resorts-filter" class="mr-2 font-semibold">Filter:</label>
            <select id="resorts-filter" class="border border-gray-300 rounded px-3 py-1 text-sm">
                <option value="all">Alle Resortsraden</option>
                <?php
                $resorts_parties = array_unique(array_map(function($c) { return $c['PartyName']; }, $resorts_results));
                foreach ($resorts_parties as $party) {
                    echo '<option value="' . htmlspecialchars($party) . '">' . htmlspecialchars($party) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="max-w-3xl mx-auto">
            <canvas id="resortsChart"></canvas>
        </div>
    </section>

    <div class="bg-green-200 text-green-800 font-bold rounded-lg max-w-3xl mx-auto py-3 text-center mb-10">Kandidaten met de meeste stemmen</div>

    <div class="flex max-w-3xl mx-auto justify-between">
        <div class="flex-1 bg-white p-5 rounded-lg shadow mx-2" id="dna-top-candidates">
            <h3 class="text-lg font-semibold mb-4">De Nationale Assemblée</h3>
            <?php foreach ($dna_results as $candidate): ?>
                <div class="candidate-item flex items-center mb-3">
                    <img src="<?= htmlspecialchars($candidate['Photo'] ?: 'https://via.placeholder.com/50') ?>" alt="<?= htmlspecialchars($candidate['Name']) ?>" class="mr-3 rounded-full" />
                    <div>
                        <div class="candidate-name font-semibold"><?= htmlspecialchars($candidate['Name']) ?></div>
                        <div class="candidate-party text-sm text-gray-600"><?= htmlspecialchars($candidate['PartyName']) ?></div>
                        <div class="progress-bar-container bg-gray-200 rounded h-2 mt-1 w-48">
                            <div class="progress-bar bg-green-600 h-2 rounded" style="width: <?= $candidate['vote_count'] > 0 ? min(100, ($candidate['vote_count'] / max(array_column($dna_results, 'vote_count'))) * 100) : 0 ?>%;"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="flex-1 bg-white p-5 rounded-lg shadow mx-2" id="resorts-top-candidates">
            <h3 class="text-lg font-semibold mb-4">Resortsraden</h3>
            <?php foreach ($resorts_results as $candidate): ?>
                <div class="candidate-item flex items-center mb-3">
                    <img src="<?= htmlspecialchars($candidate['Photo'] ?: 'https://via.placeholder.com/50') ?>" alt="<?= htmlspecialchars($candidate['Name']) ?>" class="mr-3 rounded-full" />
                    <div>
                        <div class="candidate-name font-semibold"><?= htmlspecialchars($candidate['Name']) ?></div>
                        <div class="candidate-party text-sm text-gray-600"><?= htmlspecialchars($candidate['PartyName']) ?></div>
                        <div class="progress-bar-container bg-gray-200 rounded h-2 mt-1 w-48">
                            <div class="progress-bar bg-green-600 h-2 rounded" style="width: <?= $candidate['vote_count'] > 0 ? min(100, ($candidate['vote_count'] / max(array_column($resorts_results, 'vote_count'))) * 100) : 0 ?>%;"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // Filter candidates by party
        function filterCandidates(candidates, party) {
            if (party === 'all') return candidates;
            return candidates.filter(c => c.party === party);
        }

        // Create bar chart
        function createBarChart(ctx, candidates) {
            const labels = candidates.map(c => c.name);
            const votes = candidates.map(c => c.votes);
            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Aantal stemmen',
                        data: votes,
                        backgroundColor: [
                            '#f4a261',
                            '#e9c46a',
                            '#2a9d8f',
                            '#264653',
                            '#e76f51',
                            '#b6e2a1'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Aantal stemmen'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Kandidaten'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Render top candidates with progress bars
        function renderTopCandidates(containerId, candidates) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            const maxVotes = Math.max(...candidates.map(c => c.votes));
            candidates.forEach(c => {
                const item = document.createElement('div');
                item.className = 'candidate-item';

                const img = document.createElement('img');
                img.src = c.img;
                img.alt = c.name;

                const info = document.createElement('div');
                info.className = 'candidate-info';

                const name = document.createElement('div');
                name.className = 'candidate-name';
                name.textContent = c.name;

                const party = document.createElement('div');
                party.className = 'candidate-party';
                party.textContent = c.party;

                const progressContainer = document.createElement('div');
                progressContainer.className = 'progress-bar-container';

                const progressBar = document.createElement('div');
                progressBar.className = 'progress-bar';
                const widthPercent = (c.votes / maxVotes) * 100;
                progressBar.style.width = widthPercent + '%';

                progressContainer.appendChild(progressBar);
                info.appendChild(name);
                info.appendChild(party);
                info.appendChild(progressContainer);

                item.appendChild(img);
                item.appendChild(info);

                container.appendChild(item);
            });
        }

        // Initialize page
        window.onload = function() {
            const dnaCandidates = <?= json_encode(array_map(function($c) {
                return ['name' => $c['Name'], 'party' => $c['PartyName'], 'votes' => (int)$c['vote_count'], 'img' => $c['Photo'] ?: 'https://via.placeholder.com/50'];
            }, $dna_results)) ?>;

            const resortsCandidates = <?= json_encode(array_map(function($c) {
                return ['name' => $c['Name'], 'party' => $c['PartyName'], 'votes' => (int)$c['vote_count'], 'img' => $c['Photo'] ?: 'https://via.placeholder.com/50'];
            }, $resorts_results)) ?>;

            const dnaCtx = document.getElementById('dnaChart').getContext('2d');
            const resortsCtx = document.getElementById('resortsChart').getContext('2d');

            let currentDnaChart = createBarChart(dnaCtx, dnaCandidates);
            let currentResortsChart = createBarChart(resortsCtx, resortsCandidates);

            document.getElementById('dna-filter').addEventListener('change', function() {
                const filtered = filterCandidates(dnaCandidates, this.value);
                currentDnaChart.destroy();
                currentDnaChart = createBarChart(dnaCtx, filtered);
            });

            document.getElementById('resorts-filter').addEventListener('change', function() {
                const filtered = filterCandidates(resortsCandidates, this.value);
                currentResortsChart.destroy();
                currentResortsChart = createBarChart(resortsCtx, filtered);
            });

            renderTopCandidates('dna-top-candidates', dnaCandidates);
            renderTopCandidates('resorts-top-candidates', resortsCandidates);
        };
    </script>
</body>
</html>

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
    $sql = "
        SELECT c.CandidateID, c.Name, c.PartyID, p.PartyName, c.Photo,
               COUNT(v.VoteID) AS vote_count
        FROM candidates c
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID
        WHERE c.CandidateType = ?
        GROUP BY c.CandidateID, c.Name, c.PartyID, p.PartyName, c.Photo
        ORDER BY vote_count DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$election_type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$president_results = [];
if ($has_voted) {
    $president_results = fetchElectionResults($pdo, 'President');
}

// Get unique parties for filtering
$parties = [];
if ($has_voted) {
    $parties = array_unique(array_map(function($c) { return $c['PartyName']; }, $president_results));
}

?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Filter Resultaten en President Stemmen</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 p-5">
    <nav class="flex bg-gray-200 rounded-md p-3 mb-5">
        <a href="results_stemmers.php" class="mr-5 font-bold text-gray-600 hover:text-green-700">Resultaten Stemmen</a>
        <a href="#" class="mr-auto font-bold bg-green-200 text-green-800 rounded px-3 py-1">Filter Resultaten</a>
        <button class="bg-green-200 text-green-800 font-bold rounded px-4 py-2">Logged In</button>
    </nav>

    <header class="mb-8">
        <h1 class="text-4xl font-extrabold text-green-700 mb-2">Filter Resultaten en President Stemmen</h1>
        <p class="text-lg text-gray-600">Filter op partijen en bekijk het aantal stemmen voor president kandidaten.</p>
    </header>

    <?php if (!$has_voted): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-10" role="alert">
            <strong class="font-bold">Info:</strong>
            <span class="block sm:inline"><?= $message ?></span>
        </div>
    <?php else: ?>

    <div class="mb-6">
        <label for="party-filter" class="mr-2 font-semibold">Filter op Partij:</label>
        <select id="party-filter" class="border border-gray-300 rounded px-3 py-1 text-sm">
            <option value="all">Alle Partijen</option>
            <?php foreach ($parties as $party): ?>
                <option value="<?= htmlspecialchars($party) ?>"><?= htmlspecialchars($party) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="max-w-3xl mx-auto">
        <canvas id="presidentChart"></canvas>
    </div>

    <div class="bg-green-200 text-green-800 font-bold rounded-lg max-w-3xl mx-auto py-3 text-center mt-10 mb-10">President Kandidaten met de meeste stemmen</div>

    <div class="max-w-3xl mx-auto">
        <?php foreach ($president_results as $candidate): ?>
            <div class="candidate-item flex items-center mb-3 bg-white p-4 rounded shadow">
                <img src="<?= htmlspecialchars($candidate['Photo'] ?: 'https://via.placeholder.com/50') ?>" alt="<?= htmlspecialchars($candidate['Name']) ?>" class="mr-4 rounded-full" />
                <div>
                    <div class="candidate-name font-semibold"><?= htmlspecialchars($candidate['Name']) ?></div>
                    <div class="candidate-party text-sm text-gray-600"><?= htmlspecialchars($candidate['PartyName']) ?></div>
                    <div class="progress-bar-container bg-gray-200 rounded h-2 mt-1 w-48">
                        <div class="progress-bar bg-green-600 h-2 rounded" style="width: <?= $candidate['vote_count'] > 0 ? min(100, ($candidate['vote_count'] / max(array_column($president_results, 'vote_count'))) * 100) : 0 ?>%;"></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-blue-200 text-blue-800 font-bold rounded-lg max-w-3xl mx-auto py-3 text-center mt-10 mb-4">Zetels per Partij (op basis van stemmen)</div>

    <div class="max-w-3xl mx-auto">
        <?php foreach ($party_seats as $party => $seats): ?>
            <div class="party-seat-item flex justify-between bg-white p-4 rounded shadow mb-2">
                <div class="party-name font-semibold"><?= htmlspecialchars($party) ?></div>
                <div class="party-seats text-lg"><?= $seats ?> zetel<?= $seats !== 1 ? 's' : '' ?></div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        const presidentCandidates = <?= json_encode(array_map(function($c) {
            return {name: $c['Name'], party: $c['PartyName'], votes: (int)$c['vote_count'], img: $c['Photo'] ?: 'https://via.placeholder.com/50'};
        }, $president_results)) ?>;

        const ctx = document.getElementById('presidentChart').getContext('2d');

        function filterCandidates(candidates, party) {
            if (party === 'all') return candidates;
            return candidates.filter(c => c.party === party);
        }

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
                        backgroundColor: '#2a9d8f',
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

        let currentChart = createBarChart(ctx, presidentCandidates);

        document.getElementById('party-filter').addEventListener('change', function() {
            const filtered = filterCandidates(presidentCandidates, this.value);
            currentChart.destroy();
            currentChart = createBarChart(ctx, filtered);
        });
    </script>
    <?php endif; ?>
</body>
</html>

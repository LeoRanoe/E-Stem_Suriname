<?php
session_start();
require_once __DIR__ . '/../../include/db_connect.php'; // Corrected path
require_once __DIR__ . '/../../include/config.php'; // Corrected path

try {
    // Get election ID from URL or use most recent completed election
    $election_id = isset($_GET['election']) ? intval($_GET['election']) : null;
    
    // Get completed elections for dropdown
    $stmt = $pdo->query("
        SELECT ElectionID, ElectionName, ShowResults 
        FROM elections 
        WHERE EndDate <= CURRENT_TIMESTAMP
        ORDER BY EndDate DESC
    ");
    $completed_elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$election_id && !empty($completed_elections)) {
        $election_id = $completed_elections[0]['ElectionID'];
    }
    
    if (!$election_id) {
        $_SESSION['error_message'] = "Er zijn nog geen afgeronde verkiezingen.";
        header("Location: " . BASE_URL);
        exit;
    }
    
    // Get election details
    $stmt = $pdo->prepare("
        SELECT ElectionID, ElectionName, StartDate, EndDate, ShowResults,
               (SELECT COUNT(*) FROM votes WHERE ElectionID = e.ElectionID) as total_votes,
               (SELECT COUNT(DISTINCT VoterID) FROM votes WHERE ElectionID = e.ElectionID) as unique_voters
        FROM elections e
        WHERE ElectionID = ?
    ");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$election) {
        $_SESSION['error_message'] = "Verkiezing niet gevonden.";
        header("Location: " . BASE_URL);
        exit;
    }
    
    // Check if results are public
    if (!$election['ShowResults']) {
        $_SESSION['error_message'] = "De resultaten van deze verkiezing zijn nog niet openbaar.";
        header("Location: " . BASE_URL);
        exit;
    }
    
    // Get results per candidate
    $stmt = $pdo->prepare("
        SELECT 
            c.CandidateID,
            c.Name as CandidateName,
            c.Photo,
            p.PartyName,
            COUNT(v.VoteID) as vote_count,
            (COUNT(v.VoteID) * 100.0 / NULLIF((SELECT COUNT(*) FROM votes WHERE ElectionID = ?), 0)) as vote_percentage
        FROM candidates c
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID AND v.ElectionID = ?
        WHERE c.ElectionID = ?
        GROUP BY c.CandidateID, c.Name, c.Photo, p.PartyName
        ORDER BY vote_count DESC
    ");
    $stmt->execute([$election_id, $election_id, $election_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get results per district
    $stmt = $pdo->prepare("
        SELECT 
            d.DistrictName,
            c.CandidateID,
            c.Name as CandidateName,
            p.PartyName,
            COUNT(v.VoteID) as district_votes
        FROM districts d
        CROSS JOIN candidates c
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID 
            AND v.ElectionID = ? 
            AND v.DistrictID = d.DistrictID
        WHERE c.ElectionID = ?
        GROUP BY d.DistrictID, d.DistrictName, c.CandidateID, c.Name, p.PartyName
        ORDER BY d.DistrictName, district_votes DESC
    ");
    $stmt->execute([$election_id, $election_id]);
    $district_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group district results by district
    $districts = [];
    foreach ($district_results as $row) {
        if (!isset($districts[$row['DistrictName']])) {
            $districts[$row['DistrictName']] = [];
        }
        $districts[$row['DistrictName']][] = $row;
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de resultaten.";
    header("Location: " . BASE_URL);
    exit;
}

// Prepare data for charts
$candidate_labels = [];
$vote_counts = [];
$chart_colors = [];

foreach ($results as $result) {
    $candidate_labels[] = $result['CandidateName'] . ' (' . $result['PartyName'] . ')';
    $vote_counts[] = $result['vote_count'];
    $chart_colors[] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verkiezingsresultaten - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'suriname': {
                            'green': '#007749',
                            'dark-green': '#006241',
                            'red': '#C8102E',
                            'dark-red': '#a50d26',
                        },
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../include/nav.php'; // Corrected path ?>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-4 md:mb-0">
                        Verkiezingsresultaten: <?= htmlspecialchars($election['ElectionName']) ?>
                    </h1>
                    
                    <form method="GET" class="flex items-center space-x-4">
                        <select name="election" 
                                onchange="this.form.submit()"
                                class="rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                            <?php foreach ($completed_elections as $e): ?>
                                <?php if ($e['ShowResults']): ?>
                                    <option value="<?= $e['ElectionID'] ?>" 
                                            <?= $election_id == $e['ElectionID'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($e['ElectionName']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Totaal Aantal Stemmen</h3>
                        <p class="text-3xl font-bold text-suriname-green">
                            <?= number_format($election['total_votes']) ?>
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Unieke Stemmers</h3>
                        <p class="text-3xl font-bold text-suriname-green">
                            <?= number_format($election['unique_voters']) ?>
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Opkomstpercentage</h3>
                        <p class="text-3xl font-bold text-suriname-green">
                            <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM voters WHERE ElectionID = ?");
                            $stmt->execute([$election_id]);
                            $total_voters = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                            $turnout = $total_voters > 0 ? ($election['unique_voters'] / $total_voters) * 100 : 0;
                            echo number_format($turnout, 1) . '%';
                            ?>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Resultaten per Kandidaat</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Kandidaat
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Partij
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Stemmen
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Percentage
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <?php if ($result['Photo']): ?>
                                                        <img class="h-10 w-10 rounded-full object-cover mr-3" 
                                                             src="<?= BASE_URL ?>/<?= htmlspecialchars($result['Photo']) ?>" 
                                                             alt="<?= htmlspecialchars($result['CandidateName']) ?>">
                                                    <?php endif; ?>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($result['CandidateName']) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= htmlspecialchars($result['PartyName']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                <?= number_format($result['vote_count']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                <?= number_format($result['vote_percentage'], 1) ?>%
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Grafiek</h2>
                        <canvas id="resultsChart"></canvas>
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Resultaten per District</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($districts as $district_name => $district_data): ?>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-700 mb-4">
                                    <?= htmlspecialchars($district_name) ?>
                                </h3>
                                <div class="space-y-4">
                                    <?php foreach ($district_data as $index => $data): ?>
                                        <?php if ($index < 3): // Show top 3 ?>
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($data['CandidateName']) ?>
                                                    </p>
                                                    <p class="text-sm text-gray-500">
                                                        <?= htmlspecialchars($data['PartyName']) ?>
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-medium text-gray-900">
                                                        <?= number_format($data['district_votes']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../../include/footer.php'; // Corrected path ?>

    <script>
        const ctx = document.getElementById('resultsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($candidate_labels) ?>,
                datasets: [{
                    label: 'Aantal Stemmen',
                    data: <?= json_encode($vote_counts) ?>,
                    backgroundColor: <?= json_encode($chart_colors) ?>,
                    borderColor: <?= json_encode($chart_colors) ?>,
                    borderWidth: 1
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
                            precision: 0
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 
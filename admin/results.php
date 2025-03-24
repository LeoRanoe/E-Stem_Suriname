<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Get election ID from URL
$election_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get all elections
try {
    $stmt = $pdo->query("SELECT * FROM elections ORDER BY StartDate DESC");
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de verkiezingen.";
}

// If an election is selected, get its results
$results = [];
$election = null;
if ($election_id > 0) {
    try {
        // Get election details
        $stmt = $pdo->prepare("SELECT * FROM elections WHERE ElectionID = ?");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$election) {
            $_SESSION['error_message'] = "Verkiezing niet gevonden.";
            header("Location: results.php");
            exit;
        }

        // Get results
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   COUNT(v.VoteID) as vote_count,
                   (COUNT(v.VoteID) / (
                       SELECT COUNT(*) 
                       FROM votes 
                       WHERE ElectionID = ?
                   ) * 100) as percentage
            FROM candidates c
            LEFT JOIN votes v ON c.CandidateID = v.CandidateID
            WHERE c.ElectionID = ?
            GROUP BY c.CandidateID
            ORDER BY vote_count DESC
        ");
        $stmt->execute([$election_id, $election_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get total votes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE ElectionID = ?");
        $stmt->execute([$election_id]);
        $total_votes = $stmt->fetchColumn();

        // Get total voters
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT UserID) 
            FROM votes 
            WHERE ElectionID = ?
        ");
        $stmt->execute([$election_id]);
        $total_voters = $stmt->fetchColumn();

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de resultaten.";
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verkiezingsresultaten - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <?php include 'nav.php'; ?>

    <main class="container mx-auto px-4 py-16">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Verkiezingsresultaten</h1>
                <p class="mt-2 text-gray-600">Bekijk de resultaten van de verkiezingen</p>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <p><?= $_SESSION['error_message'] ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Election Selection -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <form method="GET" class="flex items-center space-x-4">
                    <div class="flex-1">
                        <label for="election" class="block text-sm font-medium text-gray-700 mb-1">
                            Selecteer Verkiezing
                        </label>
                        <select name="id" 
                                id="election"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm"
                                onchange="this.form.submit()">
                            <option value="">Selecteer een verkiezing...</option>
                            <?php foreach ($elections as $e): ?>
                                <option value="<?= $e['ElectionID'] ?>" <?= $election_id == $e['ElectionID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['Name']) ?> 
                                    (<?= date('d-m-Y', strtotime($e['StartDate'])) ?> - 
                                    <?= date('d-m-Y', strtotime($e['EndDate'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" 
                                class="bg-suriname-green text-white px-6 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                            <i class="fas fa-search mr-2"></i> Bekijk Resultaten
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($election && !empty($results)): ?>
                <!-- Election Summary -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <?= htmlspecialchars($election['Name']) ?>
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500">Totale Stemmen</h3>
                            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= $total_votes ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500">Unieke Stemmers</h3>
                            <p class="mt-1 text-2xl font-semibold text-gray-900"><?= $total_voters ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-500">Opkomst</h3>
                            <p class="mt-1 text-2xl font-semibold text-gray-900">
                                <?= round(($total_voters / count($results)) * 100) ?>%
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Results Chart -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <canvas id="resultsChart"></canvas>
                </div>

                <!-- Results Table -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Gedetailleerde Resultaten</h2>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Stemmen
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Percentage
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($results as $result): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full" 
                                                         src="<?= htmlspecialchars($result['Photo']) ?>" 
                                                         alt="<?= htmlspecialchars($result['Name']) ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($result['Name']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($result['Party']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $result['vote_count'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= round($result['percentage'], 1) ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    // Prepare data for the chart
                    const chartData = {
                        labels: <?= json_encode(array_map(function($r) { return $r['Name']; }, $results)) ?>,
                        datasets: [{
                            data: <?= json_encode(array_map(function($r) { return $r['vote_count']; }, $results)) ?>,
                            backgroundColor: [
                                '#007749', // suriname-green
                                '#C8102E', // suriname-red
                                '#FFD700', // gold
                                '#4B0082', // purple
                                '#FF4500', // orange
                                '#008080', // teal
                                '#800000', // maroon
                                '#483D8B', // dark slate blue
                            ],
                        }]
                    };

                    // Create the chart
                    const ctx = document.getElementById('resultsChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'pie',
                        data: chartData,
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'right',
                                },
                                title: {
                                    display: true,
                                    text: 'Verdeling van Stemmen',
                                    font: {
                                        size: 16,
                                    },
                                },
                            },
                        },
                    });
                </script>
            <?php elseif ($election_id > 0): ?>
                <div class="bg-white rounded-lg shadow-lg p-6 text-center">
                    <p class="text-gray-500">Er zijn nog geen resultaten beschikbaar voor deze verkiezing.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
</body>
</html> 
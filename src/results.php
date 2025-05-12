<?php
session_start();
require_once __DIR__ . '/../include/admin_auth.php';
require_once __DIR__ . '/../include/db_connect.php';
require_once __DIR__ . '/../include/config.php';

// --- START: CSV Export Logic ---
if (isset($_GET['election']) && isset($_GET['export'])) {
    $export_election_id = intval($_GET['election']);
    $export_type = $_GET['export']; // 'overall' or 'district'

    // Fetch election name for filename
    $stmt_election_name = $pdo->prepare("SELECT ElectionName FROM elections WHERE ElectionID = ?");
    $stmt_election_name->execute([$export_election_id]);
    $election_name = $stmt_election_name->fetchColumn();
    $filename_safe_election_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $election_name ?: 'election');

    // Set headers
    header('Content-Type: text/csv; charset=utf-8');

    if ($export_type === 'overall') {
        header('Content-Disposition: attachment; filename="' . $filename_safe_election_name . '_overall_results.csv"');

        // Fetch candidate results (consider filter)
        $export_candidate_type_filter = isset($_GET['type']) ? $_GET['type'] : 'All';
        if (!in_array($export_candidate_type_filter, ['All', 'DNA', 'RR'])) {
            $export_candidate_type_filter = 'All';
        }

        $sql_export_overall = "
            SELECT
                c.Name as CandidateName,
                p.PartyName,
                c.CandidateType,
                COUNT(v.VoteID) as vote_count
            FROM candidates c
            LEFT JOIN parties p ON c.PartyID = p.PartyID
            LEFT JOIN votes v ON c.CandidateID = v.CandidateID AND v.ElectionID = ?
            WHERE c.ElectionID = ?
        ";
        $params_export_overall = [$export_election_id, $export_election_id];

        if ($export_candidate_type_filter !== 'All') {
            $sql_export_overall .= " AND c.CandidateType = ?";
            $params_export_overall[] = $export_candidate_type_filter;
        }

        $sql_export_overall .= "
            GROUP BY c.CandidateID, c.Name, p.PartyName, c.CandidateType
            ORDER BY vote_count DESC
        ";

        $stmt_export = $pdo->prepare($sql_export_overall);
        $stmt_export->execute($params_export_overall);
        $results_export = $stmt_export->fetchAll(PDO::FETCH_ASSOC);

        // Fetch party results
        $stmt_party_export = $pdo->prepare("
            SELECT
                p.PartyName,
                COUNT(v.VoteID) AS party_total_votes
            FROM parties p
            LEFT JOIN candidates c ON p.PartyID = c.PartyID AND c.ElectionID = ?
            LEFT JOIN votes v ON c.CandidateID = v.CandidateID AND v.ElectionID = ?
            WHERE c.ElectionID = ?
            GROUP BY p.PartyID, p.PartyName
            ORDER BY party_total_votes DESC
        ");
        $stmt_party_export->execute([$export_election_id, $export_election_id, $export_election_id]);
        $party_results_export = $stmt_party_export->fetchAll(PDO::FETCH_ASSOC);

        $output = fopen('php://output', 'w');
        // Candidate Results
        fputcsv($output, ['Candidate Results (Filter: ' . htmlspecialchars($export_candidate_type_filter) . ')']);
        fputcsv($output, ['Candidate Name', 'Party Name', 'Type', 'Vote Count']);
        foreach ($results_export as $row) {
            fputcsv($output, [
                $row['CandidateName'],
                $row['PartyName'] ?? 'N/A',
                $row['CandidateType'],
                $row['vote_count']
            ]);
        }
        fputcsv($output, []); // Spacer
        // Party Results
        fputcsv($output, ['Party Results']);
        fputcsv($output, ['Party Name', 'Total Votes']);
        foreach ($party_results_export as $row) {
            fputcsv($output, [
                $row['PartyName'],
                $row['party_total_votes']
            ]);
        }
        fclose($output);

    } elseif ($export_type === 'district') {
        header('Content-Disposition: attachment; filename="' . $filename_safe_election_name . '_district_results.csv"');

        // Fetch detailed district turnout data
        $stmt_district_export = $pdo->prepare("
            SELECT
                d.DistrictID, d.DistrictName,
                COUNT(DISTINCT v.UserID) AS district_unique_voters
            FROM districten d
            LEFT JOIN users u ON d.DistrictID = u.DistrictID
            LEFT JOIN votes v ON u.UserID = v.UserID AND v.ElectionID = ?
            WHERE d.DistrictID IS NOT NULL
            GROUP BY d.DistrictID, d.DistrictName
            ORDER BY d.DistrictName
        ");
        $stmt_district_export->execute([$export_election_id]);
        $district_turnout_export = $stmt_district_export->fetchAll(PDO::FETCH_ASSOC);

        $stmt_district_users_export = $pdo->prepare("
            SELECT DistrictID, COUNT(UserID) as total_active_users
            FROM users
            WHERE Status = 'active' AND DistrictID IS NOT NULL
            GROUP BY DistrictID
        ");
        $stmt_district_users_export->execute();
        $district_user_counts_export = $stmt_district_users_export->fetchAll(PDO::FETCH_KEY_PAIR);

        $output = fopen('php://output', 'w');
        fputcsv($output, ['District Turnout Results']);
        fputcsv($output, ['District Name', 'Unique Voters', 'Total Active Users', 'Turnout Percentage']);
        foreach ($district_turnout_export as $row) {
            $total_active = $district_user_counts_export[$row['DistrictID']] ?? 0;
            $turnout_percentage = $total_active > 0 ? ($row['district_unique_voters'] * 100.0 / $total_active) : 0;
            fputcsv($output, [
                $row['DistrictName'],
                $row['district_unique_voters'],
                $total_active,
                number_format($turnout_percentage, 2) . '%'
            ]);
        }
        fclose($output);

    } else {
        header("HTTP/1.1 400 Bad Request");
        echo "Invalid export type specified.";
    }
    exit; // Stop script execution after sending CSV
}
// --- END: CSV Export Logic ---

// --- START: Normal Page Logic ---
$pageTitle = "Verkiezingsresultaten";
$election_id = null;
$election = null;
$results = [];
$party_results = []; // For party aggregation
$districts = []; // Will hold detailed turnout data
$votes_over_time_labels = []; // For time chart
$votes_over_time_counts = []; // For time chart
$qr_stats = ['total' => 0, 'used' => 0]; // For QR stats
$completed_elections = [];
$error_message = $_SESSION['error_message'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['error_message'], $_SESSION['success_message']);

// Get Candidate Type Filter
$candidate_type_filter = isset($_GET['type']) ? $_GET['type'] : 'All'; // Default to 'All'
if (!in_array($candidate_type_filter, ['All', 'DNA', 'RR'])) {
    $candidate_type_filter = 'All'; // Validate filter
}

try {
    // Get election ID from URL or use most recent completed election
    $election_id = isset($_GET['election']) ? intval($_GET['election']) : null;

    // Get completed elections for dropdown
    $stmt = $pdo->query("
        SELECT ElectionID, ElectionName
        FROM elections
        WHERE EndDate <= CURRENT_TIMESTAMP
        ORDER BY EndDate DESC
    ");
    $completed_elections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$election_id && !empty($completed_elections)) {
        $election_id = $completed_elections[0]['ElectionID'];
    }

    if ($election_id) {
        // --- Get election details and candidate results (with filtering) ---
        $sql_candidate_results = "
            SELECT
                e.ElectionID, e.ElectionName, e.StartDate, e.EndDate,
                (SELECT COUNT(v2.VoteID) FROM votes v2
                 INNER JOIN candidates c2 ON v2.CandidateID = c2.CandidateID
                 WHERE v2.ElectionID = e.ElectionID" .
                 ($candidate_type_filter !== 'All' ? " AND c2.CandidateType = ?" : "") .
                ") as total_votes, -- Recalculate total votes based on filter
                (SELECT COUNT(DISTINCT v3.UserID) FROM votes v3
                 INNER JOIN candidates c3 ON v3.CandidateID = c3.CandidateID
                 WHERE v3.ElectionID = e.ElectionID" .
                 ($candidate_type_filter !== 'All' ? " AND c3.CandidateType = ?" : "") .
                ") as unique_voters, -- Recalculate unique voters based on filter
                c.CandidateID, c.Name as CandidateName, c.Photo, c.CandidateType,
                p.PartyName,
                COUNT(v.VoteID) as vote_count
            FROM elections e
            LEFT JOIN candidates c ON c.ElectionID = e.ElectionID
            LEFT JOIN parties p ON c.PartyID = p.PartyID
            LEFT JOIN votes v ON c.CandidateID = v.CandidateID AND v.ElectionID = e.ElectionID
            WHERE e.ElectionID = ?
        ";

        $params = [];
        if ($candidate_type_filter !== 'All') {
            // Add filter parameter twice for the subqueries
            $params[] = $candidate_type_filter;
            $params[] = $candidate_type_filter;
            // Add filter to the main WHERE clause for candidates
            $sql_candidate_results .= " AND c.CandidateType = ?";
            $params[] = $candidate_type_filter;
        }
        $params[] = $election_id; // Election ID for the main WHERE clause

        $sql_candidate_results .= "
            GROUP BY
                e.ElectionID, e.ElectionName, e.StartDate, e.EndDate,
                c.CandidateID, c.Name, c.Photo, c.CandidateType, p.PartyName
            ORDER BY vote_count DESC
        ";

        $stmt = $pdo->prepare($sql_candidate_results);
        $stmt->execute($params);

        $fetched_results = [];
        $filtered_total_votes = 0; // Initialize
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!$election) {
                // Store election details, including potentially filtered counts
                $election = [
                    'ElectionID' => $row['ElectionID'],
                    'ElectionName' => $row['ElectionName'],
                    'StartDate' => $row['StartDate'],
                    'EndDate' => $row['EndDate'],
                    'total_votes' => $row['total_votes'], // Use filtered count
                    'unique_voters' => $row['unique_voters'] // Use filtered count
                ];
                $filtered_total_votes = $election['total_votes']; // Store for percentage calc
            }
            if ($row['CandidateID']) {
                $fetched_results[] = [
                    'CandidateID' => $row['CandidateID'],
                    'CandidateName' => $row['CandidateName'],
                    'Photo' => $row['Photo'],
                    'PartyName' => $row['PartyName'],
                    'CandidateType' => $row['CandidateType'],
                    'vote_count' => $row['vote_count'],
                    // Calculate percentage based on filtered total votes
                    'vote_percentage' => $filtered_total_votes > 0 ? ($row['vote_count'] * 100.0 / $filtered_total_votes) : 0
                ];
            }
        }
        $results = $fetched_results; // Assign potentially filtered results

        // --- START: Fetch Party Results ---
        $stmt_party = $pdo->prepare("
            SELECT
                p.PartyID,
                p.PartyName,
                p.Logo AS PartyLogo,
                COUNT(v.VoteID) AS party_total_votes,
                (COUNT(v.VoteID) * 100.0 / NULLIF((SELECT COUNT(*) FROM votes WHERE ElectionID = ?), 0)) AS party_vote_percentage
            FROM parties p
            LEFT JOIN candidates c ON p.PartyID = c.PartyID AND c.ElectionID = ?
            LEFT JOIN votes v ON c.CandidateID = v.CandidateID AND v.ElectionID = ?
            WHERE c.ElectionID = ? -- Ensure we only consider parties participating in this election
            GROUP BY p.PartyID, p.PartyName, p.Logo
            ORDER BY party_total_votes DESC
        ");
        // Need to bind election_id four times
        $stmt_party->execute([$election_id, $election_id, $election_id, $election_id]);
        $party_results = $stmt_party->fetchAll(PDO::FETCH_ASSOC);
        // --- END: Fetch Party Results ---

        // --- START: Fetch QR Code Stats ---
        $stmt_qr_total = $pdo->prepare("SELECT COUNT(*) FROM qrcodes WHERE ElectionID = ?");
        $stmt_qr_total->execute([$election_id]);
        $qr_stats['total'] = $stmt_qr_total->fetchColumn();

        $stmt_qr_used = $pdo->prepare("SELECT COUNT(*) FROM qrcodes WHERE ElectionID = ? AND Status = 'used'");
        $stmt_qr_used->execute([$election_id]);
        $qr_stats['used'] = $stmt_qr_used->fetchColumn();
        // --- END: Fetch QR Code Stats ---

        // --- START: Fetch Votes Over Time Data ---
        $stmt_time = $pdo->prepare("
            SELECT
                DATE(TimeStamp) as vote_date,
                COUNT(VoteID) as daily_vote_count
            FROM votes
            WHERE ElectionID = ?
            GROUP BY DATE(TimeStamp)
            ORDER BY vote_date ASC
        ");
        $stmt_time->execute([$election_id]);
        $votes_over_time_data = $stmt_time->fetchAll(PDO::FETCH_ASSOC);

        foreach ($votes_over_time_data as $row) {
            $votes_over_time_labels[] = $row['vote_date'];
            $votes_over_time_counts[] = $row['daily_vote_count'];
        }
        // --- END: Fetch Votes Over Time Data ---


        if ($election) {
            // --- START: Get Detailed District Turnout ---
            $stmt_district = $pdo->prepare("
                SELECT
                    d.DistrictID, -- Need DistrictID for user count query
                    d.DistrictName,
                    COUNT(DISTINCT v.UserID) AS district_unique_voters -- Unique voters from this district who voted
                FROM districten d
                LEFT JOIN users u ON d.DistrictID = u.DistrictID
                LEFT JOIN votes v ON u.UserID = v.UserID AND v.ElectionID = ?
                WHERE d.DistrictID IS NOT NULL -- Ensure we only get actual districts
                GROUP BY d.DistrictID, d.DistrictName
                ORDER BY d.DistrictName
            ");
            $stmt_district->execute([$election_id]);
            $district_turnout_data = $stmt_district->fetchAll(PDO::FETCH_ASSOC);

            // Fetch total active users per district separately
            $stmt_district_users = $pdo->prepare("
                SELECT DistrictID, COUNT(UserID) as total_active_users
                FROM users
                WHERE Status = 'active' AND DistrictID IS NOT NULL
                GROUP BY DistrictID
            ");
            $stmt_district_users->execute();
            $district_user_counts = $stmt_district_users->fetchAll(PDO::FETCH_KEY_PAIR); // Fetch as DistrictID => count

            // Combine the data
            $districts_detailed = [];
            foreach ($district_turnout_data as $row) {
                $district_id = $row['DistrictID'];
                $total_active = $district_user_counts[$district_id] ?? 0;
                $turnout_percentage = $total_active > 0 ? ($row['district_unique_voters'] * 100.0 / $total_active) : 0;

                $districts_detailed[$row['DistrictName']] = [
                    'unique_voters' => $row['district_unique_voters'],
                    'total_active_users' => $total_active,
                    'turnout_percentage' => $turnout_percentage
                ];
            }
            $districts = $districts_detailed; // Use the new detailed data
            // --- END: Get Detailed District Turnout ---

            // Calculate overall turnout (used in stats card)
            try {
                $stmt_total_users = $pdo->query("SELECT COUNT(*) as total FROM users WHERE Status = 'active'");
                $total_potential_voters = $stmt_total_users->fetch(PDO::FETCH_ASSOC)['total'];
                $overall_turnout = $total_potential_voters > 0 ? ($election['unique_voters'] / $total_potential_voters) * 100 : 0;
            } catch (PDOException $e) {
                $overall_turnout = "N/A";
                error_log("Error fetching total voters for turnout: " . $e->getMessage());
            }

        } else if (!empty($completed_elections)) {
             $error_message = "Verkiezing niet gevonden.";
        }

    } else if (empty($completed_elections)) {
         $error_message = "Er zijn nog geen afgeronde verkiezingen beschikbaar.";
    }

} catch (PDOException $e) {
    error_log("Database error in results.php: " . $e->getMessage());
    $error_message = "Er is een databasefout opgetreden bij het ophalen van de resultaten.";
}

// Prepare data for Candidate Chart
$candidate_labels = [];
$vote_counts = [];
$chart_colors = [];

if (!empty($results)) {
    foreach ($results as $result) {
        $candidate_labels[] = $result['CandidateName'] . ' (' . ($result['PartyName'] ?? 'N/A') . ')';
        $vote_counts[] = $result['vote_count'];
        $chart_colors[] = sprintf('#%06X', crc32($result['CandidateID'] ?? $result['CandidateName']) & 0xFFFFFF);
    }
}

// Include Admin Layout
ob_start();
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Alert Messages -->
    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Fout!</strong>
            <span class="block sm:inline"><?= htmlspecialchars($error_message) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($success_message): ?>
         <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">Succes!</strong>
            <span class="block sm:inline"><?= htmlspecialchars($success_message) ?></span>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">
            Verkiezingsresultaten <?= $election ? ': ' . htmlspecialchars($election['ElectionName']) : '' ?>
        </h1>
        <p class="text-gray-600">Overzicht van verkiezingsresultaten en statistieken.</p>
    </div>

    <!-- Filters & Export Card -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8">
        <div class="px-6 py-4 border-b border-gray-200 mb-4 -mx-6 -mt-6 rounded-t-xl bg-gray-50">
            <h2 class="text-xl font-semibold text-gray-800">Filters & Export</h2>
        </div>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <?php if (!empty($completed_elections)): ?>
            <form method="GET" action="results.php" class="flex flex-wrap items-center gap-4">
                <div>
                    <label for="election-select" class="sr-only">Selecteer Verkiezing</label>
                    <select name="election" id="election-select"
                            onchange="this.form.submit()"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 sm:text-sm">
                        <?php foreach ($completed_elections as $e): ?>
                            <option value="<?= $e['ElectionID'] ?>"
                                    <?= $election_id == $e['ElectionID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['ElectionName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- START: Candidate Type Filter -->
                <div class="flex items-center space-x-3">
                    <span class="text-sm font-medium text-gray-700">Filter Type:</span>
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="All" class="form-radio h-4 w-4 text-suriname-green focus:ring-suriname-green/50" onchange="this.form.submit()" <?= $candidate_type_filter === 'All' ? 'checked' : '' ?>>
                        <span class="ml-1 text-sm text-gray-600">Alles</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="DNA" class="form-radio h-4 w-4 text-suriname-green focus:ring-suriname-green/50" onchange="this.form.submit()" <?= $candidate_type_filter === 'DNA' ? 'checked' : '' ?>>
                        <span class="ml-1 text-sm text-gray-600">DNA</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="type" value="RR" class="form-radio h-4 w-4 text-suriname-green focus:ring-suriname-green/50" onchange="this.form.submit()" <?= $candidate_type_filter === 'RR' ? 'checked' : '' ?>>
                        <span class="ml-1 text-sm text-gray-600">RR</span>
                    </label>
                </div>
                <!-- END: Candidate Type Filter -->
                <noscript><button type="submit" class="ml-2 px-3 py-1 bg-blue-500 text-white rounded">Bekijk</button></noscript>
            </form>
            <?php endif; ?>

            <?php if ($election_id): ?>
            <!-- START: Export Buttons -->
            <div class="flex items-center space-x-2 flex-shrink-0">
                 <a href="?election=<?= $election_id ?>&type=<?= $candidate_type_filter ?>&export=overall"
                    class="px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 inline-flex items-center">
                     <i class="fas fa-file-csv mr-1"></i> Export Totaal
                 </a>
                 <a href="?election=<?= $election_id ?>&export=district"
                    class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 inline-flex items-center">
                     <i class="fas fa-file-csv mr-1"></i> Export Districten
                 </a>
            </div>
            <!-- END: Export Buttons -->
            <?php endif; ?>
        </div>
    </div>


    <?php if ($election): ?>
        <!-- Statistics Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8"> <!-- MODIFIED: Changed grid layout -->
            <!-- Totaal Stemmen -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                    <i class="fas fa-poll text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Totaal Stemmen</p>
                    <p class="text-2xl font-semibold text-suriname-green">
                        <?= number_format($election['total_votes']) ?>
                    </p>
                    <p class="text-xs text-gray-400">(Filter: <?= htmlspecialchars($candidate_type_filter) ?>)</p>
                </div>
            </div>

            <!-- Unieke Stemmers -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Unieke Stemmers</p>
                    <p class="text-2xl font-semibold text-suriname-green">
                        <?= number_format($election['unique_voters']) ?>
                    </p>
                    <p class="text-xs text-gray-400">(Filter: <?= htmlspecialchars($candidate_type_filter) ?>)</p>
                </div>
            </div>

            <!-- QR Codes Gebruikt -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                    <i class="fas fa-qrcode text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">QR Codes Gebruikt</p>
                    <p class="text-2xl font-semibold text-suriname-green">
                        <?= number_format($qr_stats['used']) ?> / <?= number_format($qr_stats['total']) ?>
                    </p>
                     <p class="text-xs text-gray-400">Gebruikt / Totaal</p>
                </div>
            </div>

            <!-- Opkomst (Totaal) -->
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                    <i class="fas fa-chart-line text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Opkomst (Totaal)</p>
                    <p class="text-2xl font-semibold text-suriname-green">
                        <?= is_numeric($overall_turnout) ? number_format($overall_turnout, 1) . '%' : $overall_turnout ?>
                    </p>
                     <p class="text-xs text-gray-400">Unieke Stemmers / Actieve Kiezers</p>
                </div>
            </div>
        </div>

        <!-- Candidate Results & Chart Card -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-800">Resultaten per Kandidaat & Grafiek (Filter: <?= htmlspecialchars($candidate_type_filter) ?>)</h2>
            </div>
            <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <div class="overflow-x-auto max-h-[600px] overflow-y-auto border rounded-lg"> 
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0"> 
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaat</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">%</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($results)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-gray-500 italic">Geen kandidaten gevonden voor dit filter.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <?php if (!empty($result['Photo']) && file_exists(__DIR__ . '/../' . $result['Photo'])): ?>
                                                        <img class="h-10 w-10 rounded-full object-cover mr-3 flex-shrink-0"
                                                             src="<?= BASE_URL ?>/<?= htmlspecialchars($result['Photo']) ?>"
                                                             alt="<?= htmlspecialchars($result['CandidateName']) ?>">
                                                    <?php else: ?>
                                                        <span class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center text-gray-500 mr-3 flex-shrink-0">
                                                            <i class="fas fa-user"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                    <div class="text-sm font-medium text-gray-900 truncate">
                                                        <?= htmlspecialchars($result['CandidateName']) ?>
                                                        <span class="text-xs text-gray-500">(<?= htmlspecialchars($result['CandidateType']) ?>)</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?= htmlspecialchars($result['PartyName'] ?? 'N/A') ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                <?= number_format($result['vote_count']) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                                <?= number_format($result['vote_percentage'], 1) ?>%
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <canvas id="resultsChart"></canvas>
                </div>
            </div>
        </div>


        <!-- Party Results Card -->
        <?php if (!empty($party_results)): ?>
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-800">Resultaten per Partij</h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto border rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Totaal Stemmen</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">% (van alle stemmen)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($party_results as $party): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if (!empty($party['PartyLogo']) && file_exists(__DIR__ . '/../' . $party['PartyLogo'])): ?>
                                            <img class="h-10 w-10 object-contain"
                                                 src="<?= BASE_URL ?>/<?= htmlspecialchars($party['PartyLogo']) ?>"
                                                 alt="<?= htmlspecialchars($party['PartyName']) ?> Logo">
                                        <?php else: ?>
                                            <span class="h-10 w-10 bg-gray-200 flex items-center justify-center text-gray-400 text-xs">Geen Logo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($party['PartyName']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        <?= number_format($party['party_total_votes']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        <?= number_format($party['party_vote_percentage'] ?? 0, 1) ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>


        <!-- District Turnout Card -->
        <?php if (!empty($districts)): ?>
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-800">Opkomst per District</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($districts as $district_name => $district_data): ?>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 flex flex-col justify-between hover:shadow-md transition-shadow">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700 mb-2 border-b pb-2 truncate" title="<?= htmlspecialchars($district_name) ?>">
                                    <?= htmlspecialchars($district_name) ?>
                                </h3>
                                <div class="text-center mt-2">
                                     <p class="text-2xl font-bold text-suriname-green">
                                         <?= number_format($district_data['turnout_percentage'], 1) ?>%
                                     </p>
                                     <p class="text-xs text-gray-500">Opkomst</p>
                                </div>
                            </div>
                            <div class="text-center mt-3 pt-3 border-t border-gray-200">
                                 <p class="text-sm text-gray-600">
                                    <span class="font-medium"><?= number_format($district_data['unique_voters']) ?></span> Stemmers
                                 </p>
                                 <p class="text-xs text-gray-500">
                                    van <?= number_format($district_data['total_active_users']) ?> Actieve Kiezers
                                 </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>


        <!-- Votes Over Time Card -->
        <?php if (!empty($votes_over_time_data)): ?>
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-semibold text-gray-800">Stemmen Over Tijd</h2>
            </div>
            <div class="p-6">
                <canvas id="votesOverTimeChart"></canvas>
            </div>
        </div>
        <?php endif; ?>


    <?php elseif ($election && empty($results) && $candidate_type_filter !== 'All'): ?>
         <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mt-6" role="alert">
            <strong class="font-bold">Info:</strong>
            <span class="block sm:inline">Er zijn geen resultaten beschikbaar voor de geselecteerde verkiezing '<?= htmlspecialchars($election['ElectionName']) ?>' met het filter '<?= htmlspecialchars($candidate_type_filter) ?>'.</span>
        </div>
    <?php elseif ($election && empty($results)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mt-6" role="alert">
            <strong class="font-bold">Info:</strong>
            <span class="block sm:inline">Er zijn nog geen stemmen uitgebracht voor de geselecteerde verkiezing '<?= htmlspecialchars($election['ElectionName']) ?>'.</span>
        </div>
    <?php elseif (!$election && !empty($completed_elections)): ?>
         <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mt-6" role="alert">
            <strong class="font-bold">Info:</strong>
            <span class="block sm:inline">Selecteer een verkiezing hierboven om de resultaten te bekijken.</span>
        </div>
    <?php elseif (empty($completed_elections) && !$error_message): ?>
         <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mt-6" role="alert">
            <strong class="font-bold">Info:</strong>
            <span class="block sm:inline">Er zijn geen afgeronde verkiezingen gevonden. Resultaten kunnen nog niet worden weergegeven.</span>
        </div>
    <?php endif; ?>

</div> <!-- End container -->

<!-- Data for Charts -->
<script>
    // Pass PHP data to a global JavaScript variable
    window.electionResultsData = {
        // Candidate Bar Chart Data (potentially filtered)
        labels: <?= json_encode($candidate_labels) ?>,
        votes: <?= json_encode($vote_counts) ?>,
        colors: <?= json_encode($chart_colors) ?>,
        // Votes Over Time Line Chart Data
        timeLabels: <?= json_encode($votes_over_time_labels) ?>,
        timeCounts: <?= json_encode($votes_over_time_counts) ?>
    };
</script>

<!-- Include Chart.js and the external chart script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script> <!-- Add Date Adapter -->
<script src="<?= BASE_URL ?>/assets/js/results-chart.js" defer></script>

<?php
$content = ob_get_clean();
// Assuming admin/components/layout.php takes $pageTitle and $content variables
// And that layout.php includes Tailwind and FontAwesome CDNs
require_once __DIR__ . '/../admin/components/layout.php';
?>
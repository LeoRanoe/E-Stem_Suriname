<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is an admin
requireAdmin();

// Get current user's data
$currentUser = getCurrentUser();

// Get statistics
$stats = [
    'total_users' => 0,
    'total_votes' => 0,
    'total_candidates' => 0,
    'total_parties' => 0,
    'active_elections' => 0
];

// Initialize recent activity array
$recent_activity = [];

try {
    // Get total users (voters)
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE Role = 'voter' AND Status = 'active'
    ");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get total votes in active election
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM votes v
        JOIN elections e ON v.ElectionID = e.ElectionID
        WHERE e.Status = 'active'
    ");
    $stats['total_votes'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get total candidates in active election
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM candidates c
        JOIN elections e ON c.ElectionID = e.ElectionID
        WHERE e.Status = 'active' AND c.Status = 'active'
    ");
    $stats['total_candidates'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get total distinct parties in active election
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT c.PartyID) as count 
        FROM candidates c
        JOIN elections e ON c.ElectionID = e.ElectionID
        WHERE e.Status = 'active' AND c.Status = 'active'
    ");
    $stats['total_parties'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get count of active elections
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM elections 
        WHERE Status = 'active'
    ");
    $stats['active_elections'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Get recent activity
    $stmt = $pdo->query("
        (SELECT 
            'vote' as type,
            v.Timestamp as date,
            CONCAT(u.Voornaam, ' ', u.Achternaam) as user_name,
            CONCAT('Stem uitgebracht in verkiezing: ', e.ElectionName) as action
        FROM votes v
        JOIN users u ON v.UserID = u.UserID
        JOIN elections e ON v.ElectionID = e.ElectionID
        ORDER BY v.Timestamp DESC
        LIMIT 5)
        UNION ALL
        (SELECT 
            'registration' as type,
            u.CreatedAt as date,
            CONCAT(u.Voornaam, ' ', u.Achternaam) as user_name,
            'Nieuw account geregistreerd' as action
        FROM users u
        ORDER BY u.CreatedAt DESC
        LIMIT 5)
        ORDER BY date DESC
        LIMIT 10
    ");
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Er is een fout opgetreden bij het ophalen van de statistieken.";
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <?php include 'nav.php'; ?>

    <div class="min-h-screen pt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Users Card -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Totaal Gebruikers</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= number_format($stats['total_users']) ?></p>
                        </div>
                        <div class="bg-suriname-green/10 p-3 rounded-full">
                            <i class="fas fa-users text-suriname-green text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Votes Card -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Totaal Stemmen</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= number_format($stats['total_votes']) ?></p>
                        </div>
                        <div class="bg-suriname-green/10 p-3 rounded-full">
                            <i class="fas fa-vote-yea text-suriname-green text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Candidates Card -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Totaal Kandidaten</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= number_format($stats['total_candidates']) ?></p>
                        </div>
                        <div class="bg-suriname-green/10 p-3 rounded-full">
                            <i class="fas fa-user-tie text-suriname-green text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Parties Card -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Totaal Partijen</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= number_format($stats['total_parties']) ?></p>
                        </div>
                        <div class="bg-suriname-green/10 p-3 rounded-full">
                            <i class="fas fa-flag text-suriname-green text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Recente Activiteit</h2>
                <div class="space-y-4">
                    <?php if (!empty($recent_activity)): ?>
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <?php if ($activity['type'] === 'vote'): ?>
                                        <i class="fas fa-vote-yea text-suriname-green text-xl"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user-plus text-suriname-green text-xl"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($activity['user_name']) ?></p>
                                    <p class="text-sm text-gray-500"><?= htmlspecialchars($activity['action']) ?></p>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= date('d-m-Y H:i', strtotime($activity['date'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-gray-500 py-4">
                            Geen recente activiteit gevonden.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>
</body>
</html> 
<?php
// Start output buffering
ob_start();

session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Initialize variables
$user_count = 0;
$election_count = 0;
$candidate_count = 0;
$recent_votes = [];

// Fetch dashboard data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
    $user_count = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as election_count FROM elections");
    $election_count = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) as candidate_count FROM candidates");
    $candidate_count = $stmt->fetchColumn();

    // Get recent activity
    $stmt = $pdo->query("
        SELECT 'vote' as type, v.Timestamp as date, 
               CONCAT(u.FirstName, ' ', u.LastName) as user_name,
               e.ElectionName
        FROM votes v
        JOIN users u ON v.UserID = u.UserID
        JOIN elections e ON v.ElectionID = e.ElectionID
        ORDER BY v.Timestamp DESC
        LIMIT 5
    ");
    $recent_votes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de dashboardgegevens.";
}
?>

<!-- Dashboard Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Gebruikers</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($user_count) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-users text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Verkiezingen</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($election_count) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-vote-yea text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Kandidaten</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($candidate_count) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-user-tie text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Recente Activiteit</h3>
        <div class="space-y-4">
            <?php if (!empty($recent_votes)): ?>
                <?php foreach ($recent_votes as $vote): ?>
                    <div class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg transform hover:scale-102 transition-all duration-200">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-suriname-green/10 rounded-full flex items-center justify-center">
                                <i class="fas fa-vote-yea text-suriname-green"></i>
                            </div>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($vote['user_name']) ?></p>
                            <p class="text-sm text-gray-500">
                                Heeft gestemd in: <?= htmlspecialchars($vote['ElectionName']) ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                <?= date('d-m-Y H:i', strtotime($vote['date'])) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-gray-500 py-4">
                    <i class="fas fa-info-circle mb-2 text-2xl"></i>
                    <p>Geen recente activiteit gevonden</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Chart -->
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Stemmen per Dag</h3>
        <canvas id="votesChart"></canvas>
    </div>
</div>

<script>
    // Animated loading of chart data
    const ctx = document.getElementById('votesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Januari', 'Februari', 'Maart', 'April', 'Mei', 'Juni'],
            datasets: [{
                label: 'Aantal Stemmen',
                data: [65, 59, 80, 81, 56, 55],
                borderColor: '#007749',
                backgroundColor: 'rgba(0, 119, 73, 0.2)',
                fill: true,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            },
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
</script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once 'components/layout.php';
?> 
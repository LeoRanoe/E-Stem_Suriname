<?php
session_start();
require_once '../include/db_connect.php';
require_once __DIR__ . '/../include/admin_auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Get election ID from URL
$election_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($election_id === 0) {
    $_SESSION['error_message'] = "Ongeldige verkiezing ID.";
    header("Location: /E-Stem_Suriname/src/views/elections.php");
    exit;
}

// Get election data
try {
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE ElectionID = ?");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        $_SESSION['error_message'] = "Verkiezing niet gevonden.";
        header("Location: /E-Stem_Suriname/src/views/elections.php");
        exit;
    }

    // Get candidates and their votes
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(v.VoteID) as vote_count
        FROM candidates c
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID AND v.ElectionID = ?
        WHERE c.ElectionID = ?
        GROUP BY c.CandidateID
        ORDER BY vote_count DESC
    ");
    $stmt->execute([$election_id, $election_id]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de resultaten.";
    header("Location: /E-Stem_Suriname/src/views/elections.php");
    exit;
}

// Start output buffering
ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Resultaten: <?= htmlspecialchars($election['ElectionName'] ?? '') ?></h1>
        <a href="/E-Stem_Suriname/admin/elections.php"
           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-600 hover:bg-gray-700">
            <i class="fas fa-arrow-left mr-2"></i>
            Terug naar overzicht
        </a>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
            <p><?= $_SESSION['error_message'] ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="space-y-6">
            <div>
                <h2 class="text-lg font-medium text-gray-900 mb-4">Kandidaten en stemmen</h2>
                <div class="space-y-4">
                    <?php foreach ($candidates as $candidate): ?>
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h3 class="font-medium"><?= htmlspecialchars($candidate['Name'] ?? '') ?></h3>
                                <p class="text-sm text-gray-500"><?= htmlspecialchars($candidate['PartyName'] ?? '') ?></p>
                            </div>
                            <span class="font-bold"><?= $candidate['vote_count'] ?> stemmen</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once 'components/layout.php';
?>
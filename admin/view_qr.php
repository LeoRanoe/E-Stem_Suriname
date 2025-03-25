<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Get QR code ID from URL
$qr_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($qr_id === 0) {
    $_SESSION['error_message'] = "Ongeldige QR-code ID.";
    header("Location: qrcodes.php");
    exit;
}

// Get QR code data
try {
    $stmt = $pdo->prepare("
        SELECT q.*, e.ElectionName, COUNT(s.ScanID) as scan_count, MAX(s.Timestamp) as last_scan
        FROM qrcodes q
        LEFT JOIN elections e ON q.ElectionID = e.ElectionID
        LEFT JOIN scans s ON q.QRCodeID = s.QRCodeID
        WHERE q.QRCodeID = ?
        GROUP BY q.QRCodeID
    ");
    $stmt->execute([$qr_id]);
    $qr = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$qr) {
        $_SESSION['error_message'] = "QR-code niet gevonden.";
        header("Location: qrcodes.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de QR-code.";
    header("Location: qrcodes.php");
    exit;
}

// Start output buffering
ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">QR-code Details</h1>
        <a href="qrcodes.php" 
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h2 class="text-lg font-medium text-gray-900 mb-4">QR-code Informatie</h2>
                <dl class="grid grid-cols-1 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Verkiezing</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= htmlspecialchars($qr['ElectionName']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Code</dt>
                        <dd class="mt-1 text-sm font-mono text-gray-900"><?= htmlspecialchars($qr['Code']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?= $qr['scan_count'] === 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $qr['scan_count'] === 0 ? 'Actief' : 'Gebruikt' ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Aantal scans</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?= $qr['scan_count'] ?></dd>
                    </div>
                    <?php if ($qr['last_scan']): ?>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Laatste scan</dt>
                            <dd class="mt-1 text-sm text-gray-900"><?= date('d-m-Y H:i', strtotime($qr['last_scan'])) ?></dd>
                        </div>
                    <?php endif; ?>
                </dl>
            </div>

            <div>
                <h2 class="text-lg font-medium text-gray-900 mb-4">QR-code</h2>
                <div class="bg-white p-4 rounded-lg border border-gray-200">
                    <img src="<?= BASE_URL ?>/generate_qr.php?code=<?= urlencode($qr['Code']) ?>" 
                         alt="QR-code"
                         class="mx-auto">
                </div>
                <div class="mt-4 flex justify-center">
                    <a href="<?= BASE_URL ?>/generate_qr.php?code=<?= urlencode($qr['Code']) ?>&download=1" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-suriname-green hover:bg-suriname-dark-green">
                        <i class="fas fa-download mr-2"></i>
                        Download QR-code
                    </a>
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
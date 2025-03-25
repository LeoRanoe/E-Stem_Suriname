<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Handle QR code actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'generate':
                $election_id = intval($_POST['election_id']);
                $quantity = intval($_POST['quantity']);

                if (empty($election_id) || $quantity < 1 || $quantity > 100) {
                    throw new Exception('Ongeldige invoer. Kies een verkiezing en een hoeveelheid tussen 1 en 100.');
                }

                // Start transaction
                $pdo->beginTransaction();

                // Generate QR codes
                for ($i = 0; $i < $quantity; $i++) {
                    $code = bin2hex(random_bytes(16)); // Generate a random 32-character hex string
                    $stmt = $pdo->prepare("
                        INSERT INTO qr_codes (ElectionID, Code, Status, CreatedAt)
                        VALUES (?, ?, 'active', NOW())
                    ");
                    $stmt->execute([$election_id, $code]);
                }

                $pdo->commit();
                $_SESSION['success_message'] = "QR codes zijn succesvol gegenereerd.";
                break;

            case 'revoke':
                $qr_id = intval($_POST['qr_id']);
                $stmt = $pdo->prepare("UPDATE qr_codes SET Status = 'revoked' WHERE QRCodeID = ?");
                $stmt->execute([$qr_id]);
                $_SESSION['success_message'] = "QR code is succesvol ingetrokken.";
                break;

            case 'activate':
                $qr_id = intval($_POST['qr_id']);
                $stmt = $pdo->prepare("UPDATE qr_codes SET Status = 'active' WHERE QRCodeID = ?");
                $stmt->execute([$qr_id]);
                $_SESSION['success_message'] = "QR code is succesvol geactiveerd.";
                break;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all elections for the dropdown
try {
    $stmt = $pdo->query("SELECT ElectionID, ElectionName FROM elections WHERE Status = 'active' ORDER BY ElectionName");
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de verkiezingen.";
}

// Get all QR codes with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) FROM qr_codes");
    $total_qr_codes = $stmt->fetchColumn();
    $total_pages = ceil($total_qr_codes / $per_page);

    // Get QR codes for current page
    $stmt = $pdo->prepare("
        SELECT q.*, e.ElectionName,
               CASE 
                   WHEN v.VoteID IS NOT NULL THEN 'used'
                   WHEN q.Status = 'revoked' THEN 'revoked'
                   ELSE 'active'
               END as CurrentStatus
        FROM qr_codes q
        LEFT JOIN elections e ON q.ElectionID = e.ElectionID
        LEFT JOIN votes v ON q.QRCodeID = v.QRCodeID
        ORDER BY q.CreatedAt DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$per_page, $offset]);
    $qr_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de QR codes.";
    $qr_codes = [];
    $total_pages = 0;
}

// Start output buffering
ob_start();

// Initialize variables
$qrcodes = [];
$total_scanned = 0;
$total_active = 0;
$total_expired = 0;

try {
    // Get QR codes with their scan counts
    $stmt = $pdo->query("
        SELECT q.*,
               e.ElectionName,
               COUNT(s.ScanID) as scan_count,
               MAX(s.Timestamp) as last_scan
        FROM qrcodes q
        LEFT JOIN elections e ON q.ElectionID = e.ElectionID
        LEFT JOIN scans s ON q.QRCodeID = s.QRCodeID
        GROUP BY q.QRCodeID
        ORDER BY q.CreatedAt DESC
    ");
    $qrcodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total scans
    $stmt = $pdo->query("SELECT COUNT(*) FROM scans");
    $total_scanned = $stmt->fetchColumn();

    // Get total active QR codes
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM qrcodes q
        JOIN elections e ON q.ElectionID = e.ElectionID
        WHERE e.EndDate >= NOW()
    ");
    $total_active = $stmt->fetchColumn();

    // Get total expired QR codes
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM qrcodes q
        JOIN elections e ON q.ElectionID = e.ElectionID
        WHERE e.EndDate < NOW()
    ");
    $total_expired = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de QR codes.";
}
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Totaal QR Codes</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format(count($qrcodes)) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-qrcode text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Totaal Scans</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_scanned) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-search text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Actieve QR Codes</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_active) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-check-circle text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Verlopen QR Codes</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_expired) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-clock text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>
</div>

<!-- Add New QR Code Button -->
<div class="mb-6">
    <button onclick="document.getElementById('newQRCodeModal').classList.remove('hidden')" 
            class="bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-2 px-4 rounded transition-all duration-300 transform hover:scale-105">
        <i class="fas fa-plus mr-2"></i>Nieuwe QR Code
    </button>
</div>

<!-- QR Codes Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Code</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verkiezing</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aangemaakt</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scans</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Laatste Scan</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($qrcodes)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        Geen QR codes gevonden
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($qrcodes as $qrcode): ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <img class="h-10 w-10 rounded object-cover transform hover:scale-110 transition-transform duration-200" 
                                         src="<?= $qrcode['QRCodeImage'] ?>" 
                                         alt="QR Code">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($qrcode['QRCodeID']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm text-gray-900"><?= htmlspecialchars($qrcode['ElectionName']) ?></p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm text-gray-500">
                                <?= date('d-m-Y H:i', strtotime($qrcode['CreatedAt'])) ?>
                            </p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                <?= number_format($qrcode['scan_count']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm text-gray-500">
                                <?= $qrcode['last_scan'] ? date('d-m-Y H:i', strtotime($qrcode['last_scan'])) : 'Nog niet gescand' ?>
                            </p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="download_qrcode.php?id=<?= $qrcode['QRCodeID'] ?>" 
                               class="text-suriname-green hover:text-suriname-dark-green mr-3 transition-colors duration-200">
                                <i class="fas fa-download transform hover:scale-110 transition-transform duration-200"></i>
                            </a>
                            <a href="delete_qrcode.php?id=<?= $qrcode['QRCodeID'] ?>" 
                               class="text-suriname-red hover:text-suriname-dark-red transition-colors duration-200"
                               onclick="return confirm('Weet u zeker dat u deze QR code wilt verwijderen?')">
                                <i class="fas fa-trash transform hover:scale-110 transition-transform duration-200"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- New QR Code Modal -->
<div id="newQRCodeModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="generate_qrcode.php" method="POST">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="election">
                            Verkiezing
                        </label>
                        <select name="election" id="election" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Selecteer een verkiezing</option>
                            <?php
                            $elections = $pdo->query("
                                SELECT * FROM elections 
                                WHERE EndDate >= NOW() 
                                ORDER BY StartDate ASC
                            ")->fetchAll();
                            foreach ($elections as $election) {
                                echo '<option value="' . $election['ElectionID'] . '">' . htmlspecialchars($election['ElectionName']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="quantity">
                            Aantal QR Codes
                        </label>
                        <input type="number" name="quantity" id="quantity" min="1" max="100" value="1" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <p class="mt-1 text-xs text-gray-500">Maximaal 100 QR codes per keer</p>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-suriname-green text-base font-medium text-white hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 transform hover:scale-105">
                        Genereren
                    </button>
                    <button type="button" 
                            onclick="document.getElementById('newQRCodeModal').classList.add('hidden')"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 transform hover:scale-105">
                        Annuleren
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once 'components/layout.php';
?> 
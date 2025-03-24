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
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Codes Beheren - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.4.4/build/qrcode.min.js"></script>
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
                <h1 class="text-3xl font-bold text-gray-900">QR Codes Beheren</h1>
                <p class="mt-2 text-gray-600">Genereer en beheer QR codes voor verkiezingen</p>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    <p><?= $_SESSION['success_message'] ?></p>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <p><?= $_SESSION['error_message'] ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Generate QR Codes Form -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Nieuwe QR Codes Genereren</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="generate">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="election_id" class="block text-sm font-medium text-gray-700">
                                Verkiezing
                            </label>
                            <select name="election_id" 
                                    id="election_id" 
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                                <option value="">Selecteer een verkiezing</option>
                                <?php foreach ($elections as $election): ?>
                                    <option value="<?= $election['ElectionID'] ?>">
                                        <?= htmlspecialchars($election['ElectionName']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-700">
                                Aantal QR Codes
                            </label>
                            <input type="number" 
                                   name="quantity" 
                                   id="quantity" 
                                   min="1" 
                                   max="100" 
                                   value="1"
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-suriname-green text-white px-6 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                            <i class="fas fa-qrcode mr-2"></i> QR Codes Genereren
                        </button>
                    </div>
                </form>
            </div>

            <!-- QR Codes List -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">QR Codes Overzicht</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    QR Code
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Verkiezing
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aangemaakt
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acties
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($qr_codes as $qr): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="h-10 w-10">
                                                <canvas id="qr-<?= $qr['QRCodeID'] ?>" class="h-10 w-10"></canvas>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?= substr($qr['Code'], 0, 8) ?>...
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?= $qr['Code'] ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($qr['ElectionName']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                            switch ($qr['CurrentStatus']) {
                                                case 'used':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'revoked':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                default:
                                                    echo 'bg-blue-100 text-blue-800';
                                            }
                                            ?>">
                                            <?php
                                            switch ($qr['CurrentStatus']) {
                                                case 'used':
                                                    echo 'Gebruikt';
                                                    break;
                                                case 'revoked':
                                                    echo 'Ingetrokken';
                                                    break;
                                                default:
                                                    echo 'Actief';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d-m-Y H:i', strtotime($qr['CreatedAt'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-3">
                                            <?php if ($qr['CurrentStatus'] === 'active'): ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Weet u zeker dat u deze QR code wilt intrekken?');">
                                                    <input type="hidden" name="action" value="revoke">
                                                    <input type="hidden" name="qr_id" value="<?= $qr['QRCodeID'] ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($qr['CurrentStatus'] === 'revoked'): ?>
                                                <form method="POST" class="inline" onsubmit="return confirm('Weet u zeker dat u deze QR code wilt activeren?');">
                                                    <input type="hidden" name="action" value="activate">
                                                    <input type="hidden" name="qr_id" value="<?= $qr['QRCodeID'] ?>">
                                                    <button type="submit" class="text-green-600 hover:text-green-900">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-4 flex justify-center">
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?= $i ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50
                                          <?= $i === $page ? 'z-10 bg-suriname-green border-suriname-green text-white' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>

    <script>
        // Generate QR codes for each row
        <?php foreach ($qr_codes as $qr): ?>
            QRCode.toCanvas(document.getElementById('qr-<?= $qr['QRCodeID'] ?>'), 
                '<?= BASE_URL ?>/pages/scan_qr.php?code=<?= $qr['Code'] ?>', 
                function (error) {
                    if (error) console.error(error);
                }
            );
        <?php endforeach; ?>
    </script>
</body>
</html> 
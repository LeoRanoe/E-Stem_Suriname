<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $election_id = intval($_POST['election_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);

        if (empty($election_id) || $quantity < 1) {
            throw new Exception('Vul alle verplichte velden in.');
        }

        // Get election details
        $stmt = $pdo->prepare("SELECT * FROM elections WHERE ElectionID = ?");
        $stmt->execute([$election_id]);
        $election = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$election) {
            throw new Exception('Verkiezing niet gevonden.');
        }

        // Generate QR codes
        for ($i = 0; $i < $quantity; $i++) {
            $code = bin2hex(random_bytes(16)); // Generate a random 32-character hex string
            $stmt = $pdo->prepare("INSERT INTO qrcodes (ElectionID, Code) VALUES (?, ?)");
            $stmt->execute([$election_id, $code]);
        }

        $_SESSION['success_message'] = "QR-codes zijn succesvol gegenereerd.";
        header("Location: qrcodes.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all elections
try {
    $stmt = $pdo->query("SELECT ElectionID, ElectionName FROM elections ORDER BY ElectionName");
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de verkiezingen.";
    header("Location: qrcodes.php");
    exit;
}

// Start output buffering
ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">QR-codes Genereren</h1>
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
        <form method="POST" class="space-y-6">
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
                    Aantal QR-codes
                </label>
                <input type="number" 
                       name="quantity" 
                       id="quantity" 
                       min="1" 
                       value="1"
                       required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
            </div>

            <div class="flex justify-end">
                <button type="submit" 
                        class="bg-suriname-green text-white px-6 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                    <i class="fas fa-qrcode mr-2"></i>
                    QR-codes Genereren
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once 'components/layout.php';
?> 
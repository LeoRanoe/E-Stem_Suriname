<?php
session_start();
require_once('../include/db_connect.php');
require_once('../include/auth.php');

// Check if user is logged in and is admin
requireAdmin();

// Handle QR code generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $quantity = intval($_POST['quantity'] ?? 1);
        $election_id = intval($_POST['election_id']);

        if ($quantity < 1 || $quantity > 100) {
            throw new Exception('Aantal QR codes moet tussen 1 en 100 liggen.');
        }

        // Start transaction
        $pdo->beginTransaction();

        // Generate QR codes
        for ($i = 0; $i < $quantity; $i++) {
            // Generate unique code
            $code = bin2hex(random_bytes(16));
            
            // Insert QR code
            $stmt = $pdo->prepare("
                INSERT INTO qrcodes (Code, ElectionID, Status, CreatedAt)
                VALUES (?, ?, 'active', NOW())
            ");
            $stmt->execute([$code, $election_id]);
        }

        // Commit transaction
        $pdo->commit();

        $_SESSION['success_message'] = "Er zijn $quantity QR codes gegenereerd.";
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get active elections
try {
    $stmt = $pdo->query("
        SELECT * FROM elections 
        WHERE Status = 'active' 
        ORDER BY StartDate DESC
    ");
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de verkiezingen.";
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Codes Genereren - <?= SITE_NAME ?></title>
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
    <?php include '../include/nav.php'; ?>

    <main class="container mx-auto px-4 py-16">
        <div class="max-w-4xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">QR Codes Genereren</h1>
                <p class="mt-2 text-gray-600">Genereer QR codes voor stemmers</p>
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

            <div class="bg-white rounded-lg shadow-lg p-6">
                <form method="POST" class="space-y-6">
                    <div class="space-y-4">
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

                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        Genereer tussen 1 en 100 QR codes per keer. Elke QR code kan slechts één keer worden gebruikt.
                                    </p>
                                </div>
                            </div>
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

            <!-- Generated QR Codes List -->
            <div class="mt-8 bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Gegenereerde QR Codes</h2>
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
                                    Gemaakt op
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            try {
                                $stmt = $pdo->query("
                                    SELECT q.*, e.ElectionName
                                    FROM qrcodes q
                                    JOIN elections e ON q.ElectionID = e.ElectionID
                                    ORDER BY q.CreatedAt DESC
                                    LIMIT 50
                                ");
                                while ($qr = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                                        <?= htmlspecialchars($qr['Code']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($qr['ElectionName']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $qr['Status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $qr['Status'] === 'active' ? 'Actief' : 'Gebruikt' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d-m-Y H:i', strtotime($qr['CreatedAt'])) ?>
                                    </td>
                                </tr>
                            <?php
                                endwhile;
                            } catch (PDOException $e) {
                                error_log("Database error: " . $e->getMessage());
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
</body>
</html> 
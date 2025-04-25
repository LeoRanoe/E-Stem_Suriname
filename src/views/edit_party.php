<?php
session_start();
require_once __DIR__ . '/../../include/db_connect.php'; // Corrected path
require_once __DIR__ . '/../../include/auth.php'; // Corrected path

// Check if user is logged in and is admin
requireAdmin();

// Get party ID from URL
$party_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($party_id === 0) {
    $_SESSION['error_message'] = "Ongeldige partij ID.";
    header('Location: parties.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $party_name = $_POST['partyName'] ?? '';

        if (empty($party_name)) {
            throw new Exception('Vul alle verplichte velden in.');
        }

        // Handle logo upload
        $logo_path = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['logo']['type'], $allowed_types)) {
                throw new Exception('Ongeldig bestandstype. Alleen JPG, PNG en GIF zijn toegestaan.');
            }

            if ($_FILES['logo']['size'] > $max_size) {
                throw new Exception('Bestand is te groot. Maximum grootte is 5MB.');
            }

            $upload_dir = __DIR__ . '/../../uploads/parties/'; // Use __DIR__ for reliable path
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                $logo_path = 'uploads/parties/' . $file_name;
            }
        }

        // Get current logo to delete after update
        $stmt = $pdo->prepare("SELECT Logo FROM parties WHERE PartyID = ?");
        $stmt->execute([$party_id]);
        $old_logo = $stmt->fetchColumn();

        // Update party
        if ($logo_path) {
            $stmt = $pdo->prepare("
                UPDATE parties 
                SET PartyName = ?, Logo = ?
                WHERE PartyID = ?
            ");
            $stmt->execute([$party_name, $logo_path, $party_id]);

            // Delete old logo if exists (use absolute path based on __DIR__)
            $old_logo_absolute_path = __DIR__ . '/../../' . $old_logo;
            if ($old_logo && file_exists($old_logo_absolute_path)) {
                unlink($old_logo_absolute_path);
            }
        } else {
            $stmt = $pdo->prepare("
                UPDATE parties 
                SET PartyName = ?
                WHERE PartyID = ?
            ");
            $stmt->execute([$party_name, $party_id]);
        }

        $_SESSION['success_message'] = "Partij is succesvol bijgewerkt.";
        header('Location: parties.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Fetch party data
try {
    $stmt = $pdo->prepare("SELECT * FROM parties WHERE PartyID = ?");
    $stmt->execute([$party_id]);
    $party = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$party) {
        $_SESSION['error_message'] = "Partij niet gevonden.";
        header('Location: parties.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de partijgegevens.";
    header('Location: parties.php');
    exit;
}

// Start output buffering
ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Partij Bewerken</h2>
        
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700" for="partyName">
                    Naam Partij
                </label>
                <input type="text" name="partyName" id="partyName" required
                       value="<?php echo htmlspecialchars($party['PartyName']); ?>"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700" for="logo">
                    Logo
                </label>
                <?php if ($party['Logo']): ?>
                    <div class="mt-2 mb-4">
                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($party['Logo']) ?>"
                             alt="<?= htmlspecialchars($party['PartyName']) ?>"
                             class="h-20 w-20 object-contain rounded-lg">
                    </div>
                <?php endif; ?>
                <input type="file" name="logo" id="logo" accept="image/*"
                       class="mt-1 block w-full text-sm text-gray-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:text-sm file:font-semibold
                              file:bg-suriname-green file:text-white
                              hover:file:bg-suriname-dark-green">
                <p class="mt-1 text-sm text-gray-500">Maximaal 5MB. Toegestane formaten: JPG, PNG, GIF</p>
            </div>

            <div class="flex justify-end space-x-4">
                <a href="parties.php" 
                   class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green">
                    Annuleren
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-suriname-green hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green">
                    Opslaan
                </button>
            </div>
        </form>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once __DIR__ . '/../../admin/components/layout.php'; // Corrected path
?>
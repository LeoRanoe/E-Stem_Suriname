<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Get candidate ID from URL
$candidate_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($candidate_id === 0) {
    $_SESSION['error_message'] = "Ongeldige kandidaat ID.";
    header("Location: candidates.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'] ?? '';
        $party_id = intval($_POST['party_id'] ?? 0);
        $description = $_POST['description'] ?? '';

        if (empty($name) || empty($party_id)) {
            throw new Exception('Vul alle verplichte velden in.');
        }

        // Handle image upload
        $image_path = $_POST['current_image'] ?? null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                throw new Exception('Ongeldig bestandstype. Alleen JPG, PNG en GIF zijn toegestaan.');
            }

            if ($_FILES['image']['size'] > $max_size) {
                throw new Exception('Bestand is te groot. Maximum grootte is 5MB.');
            }

            $upload_dir = '../uploads/candidates/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                // Delete old image if exists
                if ($image_path && file_exists('../' . $image_path)) {
                    unlink('../' . $image_path);
                }
                $image_path = 'uploads/candidates/' . $file_name;
            }
        }

        $stmt = $pdo->prepare("
            UPDATE candidates 
            SET Name = ?, PartyID = ?, Description = ?, Photo = ?
            WHERE CandidateID = ?
        ");
        $stmt->execute([$name, $party_id, $description, $image_path, $candidate_id]);
        
        $_SESSION['success_message'] = "Kandidaat is succesvol bijgewerkt.";
        header("Location: candidates.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all parties
try {
    $stmt = $pdo->query("SELECT PartyID, PartyName FROM parties ORDER BY PartyName");
    $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de partijen.";
    header("Location: candidates.php");
    exit;
}

// Get candidate data
try {
    $stmt = $pdo->prepare("
        SELECT c.*, e.ElectionName, p.PartyName, p.PartyID
        FROM candidates c
        LEFT JOIN elections e ON c.ElectionID = e.ElectionID
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        WHERE c.CandidateID = ?
    ");
    $stmt->execute([$candidate_id]);
    $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidate) {
        $_SESSION['error_message'] = "Kandidaat niet gevonden.";
        header("Location: candidates.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de kandidaat.";
    header("Location: candidates.php");
    exit;
}

// Start output buffering
ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Kandidaat Bewerken</h1>
        <a href="candidates.php" 
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
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Naam Kandidaat
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name" 
                           value="<?= htmlspecialchars($candidate['Name']) ?>"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                </div>

                <div>
                    <label for="party_id" class="block text-sm font-medium text-gray-700">
                        Partij
                    </label>
                    <select name="party_id" 
                            id="party_id"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        <option value="">Selecteer een partij</option>
                        <?php foreach ($parties as $party): ?>
                            <option value="<?= $party['PartyID'] ?>" 
                                    <?= $candidate['PartyID'] == $party['PartyID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($party['PartyName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="election" class="block text-sm font-medium text-gray-700">
                        Verkiezing
                    </label>
                    <input type="text" 
                           id="election" 
                           value="<?= htmlspecialchars($candidate['ElectionName']) ?>"
                           disabled
                           class="mt-1 block w-full rounded-md border-gray-300 bg-gray-50 shadow-sm sm:text-sm">
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">
                        Foto
                    </label>
                    <?php if ($candidate['Photo']): ?>
                        <div class="mt-2 mb-2">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($candidate['Photo']) ?>" 
                                 alt="<?= htmlspecialchars($candidate['Name']) ?>"
                                 class="h-24 w-24 rounded-full object-cover">
                        </div>
                    <?php endif; ?>
                    <input type="file" 
                           name="image" 
                           id="image" 
                           accept="image/jpeg,image/png,image/gif"
                           class="mt-1 block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-md file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-suriname-green file:text-white
                                  hover:file:bg-suriname-dark-green">
                    <input type="hidden" name="current_image" value="<?= htmlspecialchars($candidate['Photo']) ?>">
                    <p class="mt-1 text-sm text-gray-500">Maximaal 5MB. JPG, PNG of GIF.</p>
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">
                    Beschrijving
                </label>
                <textarea name="description" 
                          id="description" 
                          rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm"><?= htmlspecialchars($candidate['Description']) ?></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit" 
                        class="bg-suriname-green text-white px-6 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>
                    Wijzigingen Opslaan
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
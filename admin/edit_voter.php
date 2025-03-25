<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Get voter ID from URL
$voter_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($voter_id === 0) {
    $_SESSION['error_message'] = "Ongeldige kiezer ID.";
    header("Location: voters.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $district_id = intval($_POST['district_id'] ?? 0);

        if (empty($first_name) || empty($last_name) || empty($email) || empty($district_id)) {
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

            $upload_dir = '../uploads/voters/';
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
                $image_path = 'uploads/voters/' . $file_name;
            }
        }

        $stmt = $pdo->prepare("
            UPDATE users 
            SET FirstName = ?, LastName = ?, Email = ?, DistrictID = ?, Photo = ?
            WHERE UserID = ?
        ");
        $stmt->execute([$first_name, $last_name, $email, $district_id, $image_path, $voter_id]);
        
        $_SESSION['success_message'] = "Kiezer is succesvol bijgewerkt.";
        header("Location: voters.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all districts
try {
    $stmt = $pdo->query("SELECT DistrictID, DistrictName FROM districts ORDER BY DistrictName");
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de districten.";
    header("Location: voters.php");
    exit;
}

// Get voter data
try {
    $stmt = $pdo->prepare("
        SELECT u.*, d.DistrictName
        FROM users u
        LEFT JOIN districts d ON u.DistrictID = d.DistrictID
        WHERE u.UserID = ? AND u.Role = 'voter'
    ");
    $stmt->execute([$voter_id]);
    $voter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$voter) {
        $_SESSION['error_message'] = "Kiezer niet gevonden.";
        header("Location: voters.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de kiezer.";
    header("Location: voters.php");
    exit;
}

// Start output buffering
ob_start();
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Kiezer Bewerken</h1>
        <a href="voters.php" 
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
                    <label for="first_name" class="block text-sm font-medium text-gray-700">
                        Voornaam
                    </label>
                    <input type="text" 
                           name="first_name" 
                           id="first_name" 
                           value="<?= htmlspecialchars($voter['FirstName']) ?>"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">
                        Achternaam
                    </label>
                    <input type="text" 
                           name="last_name" 
                           id="last_name" 
                           value="<?= htmlspecialchars($voter['LastName']) ?>"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        E-mailadres
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           value="<?= htmlspecialchars($voter['Email']) ?>"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                </div>

                <div>
                    <label for="district_id" class="block text-sm font-medium text-gray-700">
                        District
                    </label>
                    <select name="district_id" 
                            id="district_id"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        <option value="">Selecteer een district</option>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?= $district['DistrictID'] ?>" 
                                    <?= $voter['DistrictID'] == $district['DistrictID'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($district['DistrictName']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700">
                        Foto
                    </label>
                    <?php if ($voter['Photo']): ?>
                        <div class="mt-2 mb-2">
                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($voter['Photo']) ?>" 
                                 alt="<?= htmlspecialchars($voter['FirstName'] . ' ' . $voter['LastName']) ?>"
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
                    <input type="hidden" name="current_image" value="<?= htmlspecialchars($voter['Photo']) ?>">
                    <p class="mt-1 text-sm text-gray-500">Maximaal 5MB. JPG, PNG of GIF.</p>
                </div>
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
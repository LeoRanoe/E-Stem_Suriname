<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';
require_once '../include/config.php';

// Check if user is logged in and is admin
requireAdmin();

// Handle form submission for creating/updating party
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $party_name = $_POST['party_name'] ?? '';
        $party_description = $_POST['party_description'] ?? '';
        $party_id = isset($_POST['party_id']) ? intval($_POST['party_id']) : 0;

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

            $upload_dir = '../uploads/parties/';
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

        if ($party_id > 0) {
            // Update existing party
            if ($logo_path) {
                // Get current logo to delete after update
                $stmt = $pdo->prepare("SELECT Logo FROM parties WHERE PartyID = ?");
                $stmt->execute([$party_id]);
                $old_logo = $stmt->fetchColumn();

                $stmt = $pdo->prepare("
                    UPDATE parties 
                    SET PartyName = ?, Description = ?, Logo = ?
                    WHERE PartyID = ?
                ");
                $stmt->execute([$party_name, $party_description, $logo_path, $party_id]);

                // Delete old logo if exists
                if ($old_logo && file_exists('../' . $old_logo)) {
                    unlink('../' . $old_logo);
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE parties 
                    SET PartyName = ?, Description = ?
                    WHERE PartyID = ?
                ");
                $stmt->execute([$party_name, $party_description, $party_id]);
            }
            $_SESSION['success_message'] = "Partij is succesvol bijgewerkt.";
        } else {
            // Create new party
            $stmt = $pdo->prepare("
                INSERT INTO parties (PartyName, Description, Logo)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$party_name, $party_description, $logo_path]);
            $_SESSION['success_message'] = "Partij is succesvol toegevoegd.";
        }

        header("Location: parties.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Handle party deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $party_id = intval($_GET['delete']);

        // Check if party has candidates
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE PartyID = ?");
        $stmt->execute([$party_id]);
        $has_candidates = $stmt->fetchColumn() > 0;

        if ($has_candidates) {
            throw new Exception('Deze partij kan niet worden verwijderd omdat er nog kandidaten aan gekoppeld zijn.');
        }

        // Get logo path before deleting
        $stmt = $pdo->prepare("SELECT Logo FROM parties WHERE PartyID = ?");
        $stmt->execute([$party_id]);
        $logo_path = $stmt->fetchColumn();

        // Delete party
        $stmt = $pdo->prepare("DELETE FROM parties WHERE PartyID = ?");
        $stmt->execute([$party_id]);

        // Delete logo file if exists
        if ($logo_path && file_exists('../' . $logo_path)) {
            unlink('../' . $logo_path);
        }

        $_SESSION['success_message'] = "Partij is succesvol verwijderd.";
        header("Location: parties.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all parties
try {
    $stmt = $pdo->query("
        SELECT p.*, 
               (SELECT COUNT(*) FROM candidates c WHERE c.PartyID = p.PartyID) as candidate_count
        FROM parties p 
        ORDER BY p.PartyName
    ");
    $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de partijen.";
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partijen Beheren - <?= SITE_NAME ?></title>
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

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <p><?= $_SESSION['error_message'] ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    <p><?= $_SESSION['success_message'] ?></p>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Partijen</h1>
                    <button onclick="document.getElementById('addPartyModal').classList.remove('hidden')"
                            class="bg-suriname-green text-white px-4 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        Nieuwe Partij
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Logo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Partij
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Beschrijving
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aantal Kandidaten
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acties
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($parties as $party): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($party['Logo']): ?>
                                            <img src="<?= BASE_URL ?>/<?= htmlspecialchars($party['Logo']) ?>" 
                                                 alt="<?= htmlspecialchars($party['PartyName']) ?>"
                                                 class="h-10 w-10 object-contain">
                                        <?php else: ?>
                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                <i class="fas fa-flag text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($party['PartyName']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 line-clamp-2">
                                            <?= htmlspecialchars($party['Description'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            <?= $party['candidate_count'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick="editParty(<?= htmlspecialchars(json_encode($party)) ?>)"
                                                class="text-suriname-green hover:text-suriname-dark-green mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($party['candidate_count'] == 0): ?>
                                            <a href="?delete=<?= $party['PartyID'] ?>" 
                                               onclick="return confirm('Weet u zeker dat u deze partij wilt verwijderen?')"
                                               class="text-suriname-red hover:text-suriname-dark-red">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add/Edit Party Modal -->
    <div id="addPartyModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Nieuwe Partij</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="party_id" id="partyId">
                
                <div>
                    <label for="party_name" class="block text-sm font-medium text-gray-700">
                        Naam Partij
                    </label>
                    <input type="text" 
                           name="party_name" 
                           id="partyName" 
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                </div>

                <div>
                    <label for="party_description" class="block text-sm font-medium text-gray-700">
                        Beschrijving
                    </label>
                    <textarea name="party_description" 
                              id="partyDescription" 
                              rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm"></textarea>
                </div>

                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700">
                        Logo
                    </label>
                    <input type="file" 
                           name="logo" 
                           id="logo" 
                           accept="image/jpeg,image/png,image/gif"
                           class="mt-1 block w-full text-sm text-gray-500
                                  file:mr-4 file:py-2 file:px-4
                                  file:rounded-md file:border-0
                                  file:text-sm file:font-semibold
                                  file:bg-suriname-green file:text-white
                                  hover:file:bg-suriname-dark-green">
                    <p class="mt-1 text-sm text-gray-500">Maximaal 5MB. JPG, PNG of GIF.</p>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="button" 
                            onclick="closeModal()"
                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 mr-2">
                        Annuleren
                    </button>
                    <button type="submit" 
                            class="bg-suriname-green text-white px-4 py-2 rounded-lg hover:bg-suriname-dark-green">
                        Opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>

    <script>
        function closeModal() {
            document.getElementById('addPartyModal').classList.add('hidden');
            document.getElementById('modalTitle').textContent = 'Nieuwe Partij';
            document.getElementById('partyId').value = '';
            document.getElementById('partyName').value = '';
            document.getElementById('partyDescription').value = '';
            document.getElementById('logo').value = '';
        }

        function editParty(party) {
            document.getElementById('modalTitle').textContent = 'Partij Bewerken';
            document.getElementById('partyId').value = party.PartyID;
            document.getElementById('partyName').value = party.PartyName;
            document.getElementById('partyDescription').value = party.Description || '';
            document.getElementById('addPartyModal').classList.remove('hidden');
        }
    </script>
</body>
</html> 
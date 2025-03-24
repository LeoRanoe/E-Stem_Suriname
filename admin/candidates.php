<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Handle candidate actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create':
                $name = $_POST['name'] ?? '';
                $party_id = intval($_POST['party_id'] ?? 0);
                $election_id = intval($_POST['election_id']);
                $description = $_POST['description'] ?? '';

                if (empty($name) || empty($election_id) || empty($party_id)) {
                    throw new Exception('Vul alle verplichte velden in.');
                }

                // Handle image upload
                $image_path = null;
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
                        $image_path = 'uploads/candidates/' . $file_name;
                    }
                }

                $stmt = $pdo->prepare("
                    INSERT INTO candidates (Name, PartyID, ElectionID, Description, ImagePath, CreatedAt)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $party_id, $election_id, $description, $image_path]);
                $_SESSION['success_message'] = "Kandidaat is succesvol toegevoegd.";
                break;

            case 'delete':
                $candidate_id = intval($_POST['candidate_id']);
                
                // Get image path before deleting
                $stmt = $pdo->prepare("SELECT ImagePath FROM candidates WHERE CandidateID = ?");
                $stmt->execute([$candidate_id]);
                $candidate = $stmt->fetch(PDO::FETCH_ASSOC);

                // Delete candidate
                $stmt = $pdo->prepare("DELETE FROM candidates WHERE CandidateID = ?");
                $stmt->execute([$candidate_id]);

                // Delete image file if exists
                if ($candidate && $candidate['ImagePath'] && file_exists('../' . $candidate['ImagePath'])) {
                    unlink('../' . $candidate['ImagePath']);
                }

                $_SESSION['success_message'] = "Kandidaat is succesvol verwijderd.";
                break;
        }
    } catch (Exception $e) {
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

// Get all parties for the dropdown
try {
    $stmt = $pdo->query("SELECT PartyID, PartyName FROM parties ORDER BY PartyName");
    $parties = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de partijen.";
}

// Get all candidates with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) FROM candidates");
    $total_candidates = $stmt->fetchColumn();
    $total_pages = ceil($total_candidates / $per_page);

    // Get candidates for current page
    $stmt = $pdo->prepare("
        SELECT c.*, e.ElectionName, p.PartyName,
               COUNT(v.VoteID) as vote_count
        FROM candidates c
        LEFT JOIN elections e ON c.ElectionID = e.ElectionID
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID
        GROUP BY c.CandidateID
        ORDER BY c.CreatedAt DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$per_page, $offset]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de kandidaten.";
    $candidates = [];
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kandidaten Beheren - <?= SITE_NAME ?></title>
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
    <?php include 'nav.php'; ?>

    <main class="container mx-auto px-4 py-16">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Kandidaten Beheren</h1>
                <p class="mt-2 text-gray-600">Beheer kandidaten voor verkiezingen</p>
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

            <!-- Create Candidate Form -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Nieuwe Kandidaat Toevoegen</h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="action" value="create">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Naam Kandidaat
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
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
                                    <option value="<?= $party['PartyID'] ?>">
                                        <?= htmlspecialchars($party['PartyName']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

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
                            <label for="image" class="block text-sm font-medium text-gray-700">
                                Foto
                            </label>
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
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm"></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-suriname-green text-white px-6 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                            <i class="fas fa-plus mr-2"></i> Kandidaat Toevoegen
                        </button>
                    </div>
                </form>
            </div>

            <!-- Candidates List -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Kandidaten Overzicht</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Kandidaat
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Partij
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Verkiezing
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Stemmen
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acties
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($candidates)): ?>
                                <?php foreach ($candidates as $candidate): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <img class="h-10 w-10 rounded-full" 
                                                         src="<?= htmlspecialchars($candidate['ImagePath'] ?? '../assets/images/default-avatar.png') ?>" 
                                                         alt="<?= htmlspecialchars($candidate['Name']) ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($candidate['Name']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($candidate['PartyName']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($candidate['ElectionName']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= $candidate['vote_count'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex space-x-3">
                                                <a href="edit_candidate.php?id=<?= $candidate['CandidateID'] ?>" 
                                                   class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" class="inline" onsubmit="return confirm('Weet u zeker dat u deze kandidaat wilt verwijderen?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="candidate_id" value="<?= $candidate['CandidateID'] ?>">
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Er zijn nog geen kandidaten toegevoegd.
                                    </td>
                                </tr>
                            <?php endif; ?>
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
</body>
</html> 
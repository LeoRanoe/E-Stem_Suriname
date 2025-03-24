<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in and is admin
requireAdmin();

// Handle voter actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        switch ($_POST['action']) {
            case 'create':
                $name = $_POST['name'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
                    throw new Exception('Vul alle verplichte velden in.');
                }

                if ($password !== $confirm_password) {
                    throw new Exception('Wachtwoorden komen niet overeen.');
                }

                if (strlen($password) < 8) {
                    throw new Exception('Wachtwoord moet minimaal 8 tekens lang zijn.');
                }

                // Check if email already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Dit e-mailadres is al in gebruik.');
                }

                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO users (Name, Email, Password, UserType, CreatedAt)
                    VALUES (?, ?, ?, 'voter', NOW())
                ");
                $stmt->execute([$name, $email, $hashed_password]);
                $_SESSION['success_message'] = "Stemmer is succesvol toegevoegd.";
                break;

            case 'update':
                $user_id = intval($_POST['user_id']);
                $name = $_POST['name'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                $status = $_POST['status'] ?? 'active';

                if (empty($name) || empty($email)) {
                    throw new Exception('Vul alle verplichte velden in.');
                }

                // Check if email already exists for other users
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Email = ? AND UserID != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Dit e-mailadres is al in gebruik.');
                }

                // Update user data
                if (!empty($password)) {
                    if ($password !== $confirm_password) {
                        throw new Exception('Wachtwoorden komen niet overeen.');
                    }

                    if (strlen($password) < 8) {
                        throw new Exception('Wachtwoord moet minimaal 8 tekens lang zijn.');
                    }

                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET Name = ?, Email = ?, Password = ?, Status = ?
                        WHERE UserID = ?
                    ");
                    $stmt->execute([$name, $email, $hashed_password, $status, $user_id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET Name = ?, Email = ?, Status = ?
                        WHERE UserID = ?
                    ");
                    $stmt->execute([$name, $email, $status, $user_id]);
                }
                
                $_SESSION['success_message'] = "Stemmer is succesvol bijgewerkt.";
                break;

            case 'delete':
                $user_id = intval($_POST['user_id']);
                $stmt = $pdo->prepare("DELETE FROM users WHERE UserID = ?");
                $stmt->execute([$user_id]);
                $_SESSION['success_message'] = "Stemmer is succesvol verwijderd.";
                break;
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all voters with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE UserType = 'voter'");
    $total_voters = $stmt->fetchColumn();
    $total_pages = ceil($total_voters / $per_page);

    // Get voters for current page
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT v.VoteID) as vote_count,
               MAX(v.Timestamp) as last_vote
        FROM users u
        LEFT JOIN votes v ON u.UserID = v.UserID
        WHERE u.UserType = 'voter'
        GROUP BY u.UserID
        ORDER BY u.CreatedAt DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$per_page, $offset]);
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de stemmers.";
    $voters = [];
    $total_pages = 0;
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stemmers Beheren - <?= SITE_NAME ?></title>
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
                <h1 class="text-3xl font-bold text-gray-900">Stemmers Beheren</h1>
                <p class="mt-2 text-gray-600">Beheer stemmers en hun toegang</p>
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

            <!-- Create Voter Form -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Nieuwe Stemmer Toevoegen</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="create">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Naam
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
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
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Wachtwoord
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700">
                                Bevestig Wachtwoord
                            </label>
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password" 
                                   required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green sm:text-sm">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-suriname-green text-white px-6 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                            <i class="fas fa-plus mr-2"></i> Stemmer Toevoegen
                        </button>
                    </div>
                </form>
            </div>

            <!-- Voters List -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Stemmers Overzicht</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Naam
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    E-mailadres
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Stemmen
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Laatste Stem
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acties
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($voters as $voter): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($voter['Name']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($voter['Email']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $voter['Status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $voter['Status'] === 'active' ? 'Actief' : 'Inactief' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $voter['vote_count'] ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= $voter['last_vote'] ? date('d-m-Y H:i', strtotime($voter['last_vote'])) : '-' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-3">
                                            <a href="edit_voter.php?id=<?= $voter['UserID'] ?>" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="inline" onsubmit="return confirm('Weet u zeker dat u deze stemmer wilt verwijderen?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?= $voter['UserID'] ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
</body>
</html> 
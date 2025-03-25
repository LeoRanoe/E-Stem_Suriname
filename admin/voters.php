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

// Start output buffering
ob_start();

// Initialize variables
$voters = [];
$total_votes = 0;
$total_districts = 0;
$total_active_voters = 0;

try {
    // Get voters with their vote counts
    $stmt = $pdo->query("
        SELECT u.*,
               d.DistrictName,
               COUNT(DISTINCT v.VoteID) as vote_count,
               MAX(v.Timestamp) as last_vote
        FROM users u
        LEFT JOIN districts d ON u.DistrictID = d.DistrictID
        LEFT JOIN votes v ON u.UserID = v.UserID
        WHERE u.Role = 'voter'
        GROUP BY u.UserID
        ORDER BY u.LastName ASC, u.FirstName ASC
    ");
    $voters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total votes
    $stmt = $pdo->query("SELECT COUNT(*) FROM votes");
    $total_votes = $stmt->fetchColumn();

    // Get total districts
    $stmt = $pdo->query("SELECT COUNT(*) FROM districts");
    $total_districts = $stmt->fetchColumn();

    // Get total active voters (voted in the last 30 days)
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT UserID) 
        FROM votes 
        WHERE Timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $total_active_voters = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de stemmers.";
}
?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Totaal Stemmers</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format(count($voters)) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-users text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Totaal Stemmen</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_votes) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-vote-yea text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Districten</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_districts) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-map-marker-alt text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Actieve Stemmers</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_active_voters) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-user-check text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>
</div>

<!-- Add New Voter Button -->
<div class="mb-6">
    <button onclick="document.getElementById('newVoterModal').classList.remove('hidden')" 
            class="bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-2 px-4 rounded transition-all duration-300 transform hover:scale-105">
        <i class="fas fa-plus mr-2"></i>Nieuwe Stemmer
    </button>
</div>

<!-- Voters Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Naam</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Laatste Stem</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($voters)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                        Geen stemmers gevonden
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($voters as $voter): ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0">
                                    <img class="h-10 w-10 rounded-full object-cover transform hover:scale-110 transition-transform duration-200" 
                                         src="<?= $voter['ProfileImage'] ?? 'https://via.placeholder.com/40' ?>" 
                                         alt="<?= htmlspecialchars($voter['FirstName']) ?>">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($voter['FirstName'] . ' ' . $voter['LastName']) ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        ID: <?= $voter['UserID'] ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm text-gray-900"><?= htmlspecialchars($voter['Email']) ?></p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                <?= htmlspecialchars($voter['DistrictName'] ?? 'Onbekend') ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                <?= number_format($voter['vote_count']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <p class="text-sm text-gray-500">
                                <?= $voter['last_vote'] ? date('d-m-Y H:i', strtotime($voter['last_vote'])) : 'Nog niet gestemd' ?>
                            </p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="edit_voter.php?id=<?= $voter['UserID'] ?>" 
                               class="text-suriname-green hover:text-suriname-dark-green mr-3 transition-colors duration-200">
                                <i class="fas fa-edit transform hover:scale-110 transition-transform duration-200"></i>
                            </a>
                            <a href="delete_voter.php?id=<?= $voter['UserID'] ?>" 
                               class="text-suriname-red hover:text-suriname-dark-red transition-colors duration-200"
                               onclick="return confirm('Weet u zeker dat u deze stemmer wilt verwijderen?')">
                                <i class="fas fa-trash transform hover:scale-110 transition-transform duration-200"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- New Voter Modal -->
<div id="newVoterModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="add_voter.php" method="POST" enctype="multipart/form-data">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="firstName">
                                Voornaam
                            </label>
                            <input type="text" name="firstName" id="firstName" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="lastName">
                                Achternaam
                            </label>
                            <input type="text" name="lastName" id="lastName" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            Email
                        </label>
                        <input type="email" name="email" id="email" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="district">
                            District
                        </label>
                        <select name="district" id="district" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Selecteer een district</option>
                            <?php
                            $districts = $pdo->query("SELECT * FROM districts ORDER BY DistrictName")->fetchAll();
                            foreach ($districts as $district) {
                                echo '<option value="' . $district['DistrictID'] . '">' . htmlspecialchars($district['DistrictName']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="profileImage">
                            Profielfoto
                        </label>
                        <input type="file" name="profileImage" id="profileImage" accept="image/*"
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-suriname-green text-base font-medium text-white hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 transform hover:scale-105">
                        Opslaan
                    </button>
                    <button type="button" 
                            onclick="document.getElementById('newVoterModal').classList.add('hidden')"
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
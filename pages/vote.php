<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';

// Check if user is logged in
requireLogin();

// Check if user is a voter
$currentUser = getCurrentUser();
if ($currentUser['Role'] !== 'voter') {
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

// Get active election
try {
    $stmt = $pdo->prepare("
        SELECT * FROM elections 
        WHERE Status = 'active' 
        ORDER BY StartDate DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $election = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$election) {
        $_SESSION['error_message'] = "Er is momenteel geen actieve verkiezing.";
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }

    // Check if user has already voted
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as vote_count 
        FROM votes 
        WHERE UserID = ? AND ElectionID = ?
    ");
    $stmt->execute([$_SESSION['User ID'], $election['ElectionID']]);
    $vote_count = $stmt->fetch(PDO::FETCH_ASSOC)['vote_count'];

    if ($vote_count > 0) {
        $_SESSION['error_message'] = "U heeft al gestemd in deze verkiezing.";
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }

    // Get candidates with their parties
    $stmt = $pdo->prepare("
        SELECT c.*, p.PartyName, p.PartyLogo
        FROM candidates c
        JOIN parties p ON c.PartyID = p.PartyID
        WHERE c.ElectionID = ? AND c.Status = 'active'
        ORDER BY p.PartyName, c.CandidateName
    ");
    $stmt->execute([$election['ElectionID']]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Start transaction
            $pdo->beginTransaction();

            $candidate_id = $_POST['candidate_id'];
            $election_id = $election['ElectionID'];

            // Validate candidate
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM candidates 
                WHERE CandidateID = ? AND ElectionID = ? AND Status = 'active'
            ");
            $stmt->execute([$candidate_id, $election_id]);
            $valid_candidate = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

            if (!$valid_candidate) {
                throw new Exception('Ongeldige kandidaat geselecteerd.');
            }

            // Record vote
            $stmt = $pdo->prepare("
                INSERT INTO votes (UserID, ElectionID, CandidateID, Timestamp)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$_SESSION['User ID'], $election_id, $candidate_id]);

            // Commit transaction
            $pdo->commit();

            // Set success message and redirect
            $_SESSION['success_message'] = "Uw stem is succesvol geregistreerd. Bedankt voor het stemmen!";
            header('Location: ' . BASE_URL . '/index.php');
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $_SESSION['error_message'] = $e->getMessage();
        }
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de gegevens.";
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stemmen - <?= SITE_NAME ?></title>
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
                <h1 class="text-3xl font-bold text-gray-900">Stemmen</h1>
                <p class="mt-2 text-gray-600">Selecteer uw kandidaat</p>
            </div>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <p><?= $_SESSION['error_message'] ?></p>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <form method="POST" class="space-y-6">
                    <!-- Election Info -->
                    <div class="mb-8">
                        <h2 class="text-xl font-semibold mb-2"><?= htmlspecialchars($election['ElectionName']) ?></h2>
                        <p class="text-gray-600"><?= htmlspecialchars($election['Description']) ?></p>
                    </div>

                    <!-- Candidates -->
                    <div class="space-y-4">
                        <?php foreach ($candidates as $candidate): ?>
                            <div class="flex items-center p-4 border rounded-lg hover:bg-gray-50">
                                <input type="radio" 
                                       name="candidate_id" 
                                       value="<?= $candidate['CandidateID'] ?>" 
                                       id="candidate_<?= $candidate['CandidateID'] ?>"
                                       class="h-4 w-4 text-suriname-green focus:ring-suriname-green border-gray-300"
                                       required>
                                <label for="candidate_<?= $candidate['CandidateID'] ?>" 
                                       class="ml-4 flex items-center flex-1">
                                    <?php if ($candidate['PartyLogo']): ?>
                                        <img src="<?= htmlspecialchars($candidate['PartyLogo']) ?>" 
                                             alt="<?= htmlspecialchars($candidate['PartyName']) ?>"
                                             class="h-12 w-12 object-contain mr-4">
                                    <?php endif; ?>
                                    <div>
                                        <p class="font-medium"><?= htmlspecialchars($candidate['CandidateName']) ?></p>
                                        <p class="text-sm text-gray-500"><?= htmlspecialchars($candidate['PartyName']) ?></p>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" 
                                class="bg-suriname-green text-white px-6 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                            <i class="fas fa-check mr-2"></i> Stem Uitbrengen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
</body>
</html> 
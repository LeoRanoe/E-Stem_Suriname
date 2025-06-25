<?php
require_once '../include/config.php';
require_once '../include/db_connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['voter_id'])) {
    // Redirect to login page
    header("Location: " . BASE_URL . "/voter/index.php");
    exit;
}

// Ensure election_id is provided
if (!isset($_GET['election_id'])) {
    die("Election ID is missing.");
}
$electionId = $_GET['election_id'];

// Fetch election details
$electionStmt = $db->prepare("SELECT * FROM elections WHERE ElectionID = ? AND Status = 'active'");
$electionStmt->execute([$electionId]);
$election = $electionStmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    die("Active election not found.");
}

$electionType = $election['ElectionType']; // 'DNA' or 'RR'

// Fetch voter details, including their resort name
$voterStmt = $db->prepare("
    SELECT v.*, r.name as resort_name 
    FROM voters v 
    LEFT JOIN resorts r ON v.resort_id = r.id 
    WHERE v.id = ?
");
$voterStmt->execute([$_SESSION['voter_id']]);
$voter = $voterStmt->fetch(PDO::FETCH_ASSOC);

if (!$voter) {
    die("Voter not found.");
}

// Fetch candidates based on election type
$candidates = [];
$pageTitle = '';

if ($electionType === 'DNA') {
    $pageTitle = 'De Nationale Assemblée (DNA) Candidates';
    $stmt = $db->prepare("
        SELECT c.*, p.name as party_name 
        FROM candidates c
        JOIN parties p ON c.PartyID = p.PartyID
        WHERE c.ElectionID = ? AND c.CandidateType = 'DNA'
    ");
    $stmt->execute([$electionId]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($electionType === 'RR') {
    $pageTitle = 'Ressortraden (RR) Candidates';
    $stmt = $db->prepare("
        SELECT c.*, p.name as party_name 
        FROM candidates c
        JOIN parties p ON c.PartyID = p.PartyID
        WHERE c.ElectionID = ? AND c.CandidateType = 'RR' AND c.ResortID = ?
    ");
    $stmt->execute([$electionId, $voter['resort_id']]);
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stembiljet - E-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        .header-bg {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        .candidate-card { transition: transform 0.2s, box-shadow 0.2s; }
        .candidate-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); }
        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar { -ms-overflow-style: none;  scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="header-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-6 flex justify-between items-center">
            <h1 class="text-2xl font-bold">E-Stem Suriname</h1>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-sm">Welkom,</p>
                    <p class="font-semibold"><?= htmlspecialchars($voter['voter_name'] ?? 'Voter') ?></p>
                </div>
                <a href="<?= BASE_URL ?>/logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-all duration-200">
                    <i class="fas fa-sign-out-alt mr-1"></i> Uitloggen
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <h2 class="text-3xl font-bold mb-6 text-center"><?= htmlspecialchars($election['ElectionName']) ?> - <?= $pageTitle; ?></h2>

        <?php if ($electionType === 'RR'): ?>
            <p class="text-center text-lg mb-6">Displaying candidates for your resort: <strong><?= htmlspecialchars($voter['resort_name']) ?></strong></p>
        <?php endif; ?>

        <?php if (empty($candidates)): ?>
            <div class="text-center bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">No candidates found!</strong>
                <span class="block sm:inline">There are no candidates available for you in this election.</span>
            </div>
        <?php else: ?>
            <form id="vote-form" action="submit_vote.php" method="POST" class="hidden">
                <input type="hidden" name="election_id" value="<?= $electionId; ?>">
                <input type="hidden" name="voter_id" value="<?= $_SESSION['voter_id']; ?>">
                <input type="hidden" name="candidate_id" id="candidate-id-input">
            </form>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($candidates as $candidate): ?>
                    <div class="bg-white rounded-lg shadow-lg p-4 flex flex-col items-center text-center transition transform hover:-translate-y-1">
                        <img src="../uploads/candidates/<?= htmlspecialchars($candidate['Photo']); ?>" alt="<?= htmlspecialchars($candidate['Name']); ?>" class="w-32 h-32 rounded-full object-cover mb-4 border-4 border-gray-200">
                        <h3 class="text-xl font-semibold"><?= htmlspecialchars($candidate['Name']); ?></h3>
                        <p class="text-gray-600 mb-4"><?= htmlspecialchars($candidate['party_name']); ?></p>
                        <button type="button" class="vote-button w-full bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600"
                                data-candidate-id="<?= $candidate['CandidateID']; ?>"
                                data-candidate-name="<?= htmlspecialchars($candidate['Name']); ?>"
                                data-party-name="<?= htmlspecialchars($candidate['party_name']); ?>">
                            <i class="fas fa-check-circle"></i> Vote
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <!-- Confirmation Modal -->
    <div id="confirmation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center" style="display: none; z-index: 50;">
        <div class="relative mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <i class="fas fa-vote-yea text-green-600 text-2xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirm Your Vote</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">You are about to cast your vote for:</p>
                    <p class="font-bold text-xl mt-2" id="modal-candidate-name"></p>
                    <p class="text-gray-600" id="modal-party-name"></p>
                </div>
                <div class="items-center px-4 py-3 bg-gray-50 rounded-b-md">
                    <button id="confirm-vote-btn" class="px-4 py-2 bg-green-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300">
                        Yes, Confirm My Vote
                    </button>
                    <button id="cancel-vote-btn" class="mt-2 px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        No, Go Back
                    </button>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-gray-800 text-white py-4 mt-8">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <?= date('Y') ?> E-Stem Suriname. Alle rechten voorbehouden.</p>
        </div>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('confirmation-modal');
        const confirmBtn = document.getElementById('confirm-vote-btn');
        const cancelBtn = document.getElementById('cancel-vote-btn');
        const modalCandidateName = document.getElementById('modal-candidate-name');
        const modalPartyName = document.getElementById('modal-party-name');
        const formToSubmit = document.getElementById('vote-form');
        const candidateIdInput = document.getElementById('candidate-id-input');

        function showModal(candidateId, candidateName, partyName) {
            candidateIdInput.value = candidateId;
            modalCandidateName.textContent = candidateName;
            modalPartyName.textContent = 'Party: ' + partyName;
            modal.style.display = 'flex';
        }

        function hideModal() {
            modal.style.display = 'none';
        }

        confirmBtn.addEventListener('click', function() {
            if (formToSubmit) {
                formToSubmit.submit();
            }
        });

        cancelBtn.addEventListener('click', function() {
            hideModal();
        });

        const voteButtons = document.querySelectorAll('.vote-button');
        voteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const candidateId = this.dataset.candidateId;
                const candidateName = this.dataset.candidateName;
                const partyName = this.dataset.partyName;
                showModal(candidateId, candidateName, partyName);
            });
        });
    });
    </script>
</body>
</html>
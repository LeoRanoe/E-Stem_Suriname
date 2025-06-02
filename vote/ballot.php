<?php
require_once '../include/config.php';
require_once '../include/db_connect.php';
require_once '../src/models/Vote.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if voter is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: " . BASE_URL . "/vote/index.php");
    exit();
}

// Initialize vote model
$voteModel = new Vote($pdo);

// Get active elections
$activeElections = $voteModel->getActiveElections();

// Check if there are active elections
if (empty($activeElections)) {
    $error = "There are no active elections at this time.";
} else {
    // Get the first active election
    $election = $activeElections[0];
    
    // Check if voter has already voted in this election
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM votes 
        WHERE UserID = ? AND ElectionID = ?
    ");
    $stmt->execute([$_SESSION['voter_id'], $election['ElectionID']]);
    
    if ($stmt->fetchColumn() > 0) {
        $error = "You have already voted in this election.";
    } else {
        // Get candidates for this election
        $candidates = $voteModel->getCandidatesByElection($election['ElectionID']);
        
        // Group candidates by party
        $candidatesByParty = [];
        foreach ($candidates as $candidate) {
            $partyId = $candidate['PartyID'];
            if (!isset($candidatesByParty[$partyId])) {
                $candidatesByParty[$partyId] = [
                    'party_name' => $candidate['PartyName'],
                    'party_logo' => $candidate['party_logo'],
                    'candidates' => []
                ];
            }
            $candidatesByParty[$partyId]['candidates'][] = $candidate;
        }
    }
}

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidate_id'])) {
    $candidateId = intval($_POST['candidate_id']);
    $electionId = intval($_POST['election_id']);
    $voterId = $_SESSION['voter_id'];
    $voucherId = $_SESSION['voucher_id'];
    
    // Cast vote
    $success = $voteModel->castVote($voterId, $candidateId, $electionId, $voucherId);
    
    if ($success) {
        // Clear session and redirect to thank you page
        session_destroy();
        header("Location: " . BASE_URL . "/vote/thank_you.php");
        exit();
    } else {
        $error = "Failed to cast your vote. You may have already voted in this election.";
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Your Vote - E-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
            font-family: 'Arial', sans-serif;
        }
        
        .suriname-flag {
            background: linear-gradient(to bottom, 
                #007749 33.33%, 
                #ffffff 33.33%, 
                #ffffff 66.66%, 
                #C8102E 66.66%);
        }
        
        .candidate-card {
            transition: all 0.3s ease;
        }
        
        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .candidate-selected {
            border: 3px solid #007749;
            background-color: #f0fdf4;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <div class="suriname-flag h-8 w-12 mr-3"></div>
                <h1 class="text-2xl font-bold text-gray-800">E-Stem Suriname</h1>
            </div>
            <div>
                <span class="text-gray-600">Welcome, <?= htmlspecialchars($_SESSION['voter_name']) ?></span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8">
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?= htmlspecialchars($error) ?></p>
                <div class="mt-4">
                    <a href="<?= BASE_URL ?>/vote/logout.php" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors duration-200">
                        Logout
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-2xl font-semibold text-gray-800"><?= htmlspecialchars($election['ElectionName']) ?></h2>
                    <p class="text-gray-600">Election Date: <?= date('F j, Y', strtotime($election['ElectionDate'])) ?></p>
                </div>
                
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Select Your Candidate</h3>
                    
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6" role="alert">
                        <p class="text-yellow-700">Please select one candidate from the list below. Once you submit your vote, it cannot be changed.</p>
                    </div>
                    
                    <form id="vote-form" method="POST" action="">
                        <input type="hidden" name="election_id" value="<?= $election['ElectionID'] ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($candidatesByParty as $partyId => $partyData): ?>
                                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                                    <div class="flex items-center bg-gray-100 p-3 rounded-lg mb-3">
                                        <?php if (!empty($partyData['party_logo'])): ?>
                                            <img src="<?= BASE_URL ?>/<?= $partyData['party_logo'] ?>" alt="Party Logo" class="h-10 w-10 mr-3">
                                        <?php endif; ?>
                                        <h4 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($partyData['party_name']) ?></h4>
                                    </div>
                                </div>
                                
                                <?php foreach ($partyData['candidates'] as $candidate): ?>
                                    <div class="candidate-card bg-white rounded-lg shadow overflow-hidden">
                                        <div class="p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <h5 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($candidate['Name']) ?></h5>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $candidate['CandidateType'] === 'DNA' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                                    <?= $candidate['CandidateType'] ?>
                                                </span>
                                            </div>
                                            
                                            <div class="text-sm text-gray-600 mb-3">
                                                <p>District: <?= htmlspecialchars($candidate['DistrictName']) ?></p>
                                            </div>
                                            
                                            <?php if (!empty($candidate['Photo'])): ?>
                                                <div class="mb-4">
                                                    <img src="<?= BASE_URL ?>/<?= $candidate['Photo'] ?>" alt="<?= htmlspecialchars($candidate['Name']) ?>" class="w-full h-40 object-cover rounded">
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="mt-4">
                                                <label class="flex items-center cursor-pointer p-2 rounded hover:bg-gray-50">
                                                    <input type="radio" name="candidate_id" value="<?= $candidate['CandidateID'] ?>" class="candidate-radio h-5 w-5 text-suriname-green focus:ring-suriname-green">
                                                    <span class="ml-2 text-gray-700">Select this candidate</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-8 text-center">
                            <button type="button" id="confirm-vote-btn" class="bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                Cast My Vote
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Confirmation Modal -->
            <div id="confirmation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                            <i class="fas fa-vote-yea text-green-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Confirm Your Vote</h3>
                        <div class="mt-2 px-7 py-3">
                            <p class="text-sm text-gray-500">
                                You are about to vote for <span id="candidate-name" class="font-semibold"></span> from <span id="party-name" class="font-semibold"></span>.
                            </p>
                            <p class="text-sm text-gray-500 mt-2">
                                This action cannot be undone. Are you sure you want to proceed?
                            </p>
                        </div>
                        <div class="flex justify-center space-x-3 mt-3">
                            <button type="button" id="cancel-vote-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                                Cancel
                            </button>
                            <button type="button" id="submit-vote-btn" class="bg-suriname-green hover:bg-suriname-dark-green text-white font-semibold py-2 px-4 rounded-lg transition-all duration-200">
                                Confirm Vote
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p>&copy; <?= date('Y') ?> E-Stem Suriname. All rights reserved.</p>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="hover:text-gray-300 transition-colors duration-200">Privacy Policy</a>
                    <a href="#" class="hover:text-gray-300 transition-colors duration-200">Terms of Service</a>
                    <a href="#" class="hover:text-gray-300 transition-colors duration-200">Contact</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const candidateRadios = document.querySelectorAll('.candidate-radio');
            const confirmVoteBtn = document.getElementById('confirm-vote-btn');
            const confirmationModal = document.getElementById('confirmation-modal');
            const candidateNameSpan = document.getElementById('candidate-name');
            const partyNameSpan = document.getElementById('party-name');
            const cancelVoteBtn = document.getElementById('cancel-vote-btn');
            const submitVoteBtn = document.getElementById('submit-vote-btn');
            const voteForm = document.getElementById('vote-form');
            
            // Enable confirm button when a candidate is selected
            candidateRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    confirmVoteBtn.disabled = false;
                    
                    // Remove selected class from all cards
                    document.querySelectorAll('.candidate-card').forEach(card => {
                        card.classList.remove('candidate-selected');
                    });
                    
                    // Add selected class to the selected card
                    this.closest('.candidate-card').classList.add('candidate-selected');
                });
            });
            
            // Show confirmation modal when confirm button is clicked
            confirmVoteBtn.addEventListener('click', function() {
                const selectedRadio = document.querySelector('input[name="candidate_id"]:checked');
                
                if (selectedRadio) {
                    const candidateCard = selectedRadio.closest('.candidate-card');
                    const candidateName = candidateCard.querySelector('h5').textContent;
                    const partySection = candidateCard.closest('div[class*="col-span"]').previousElementSibling;
                    const partyName = partySection.querySelector('h4').textContent;
                    
                    candidateNameSpan.textContent = candidateName;
                    partyNameSpan.textContent = partyName;
                    
                    confirmationModal.classList.remove('hidden');
                }
            });
            
            // Cancel vote
            cancelVoteBtn.addEventListener('click', function() {
                confirmationModal.classList.add('hidden');
            });
            
            // Submit vote
            submitVoteBtn.addEventListener('click', function() {
                voteForm.submit();
            });
        });
    </script>
</body>
</html>

<?php
session_start();
require_once 'include/db_connect.php';
require_once 'include/auth.php';

// Check if user is logged in and is a voter
requireVoter();

// Check if user has an active voting session
if (!isset($_SESSION['voting_session'])) {
    header('Location: scan_to_vote.php');
    exit();
}

// Check if voting session has expired (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds
if (time() - $_SESSION['voting_session']['start_time'] > $session_timeout) {
    unset($_SESSION['voting_session']);
    header('Location: scan_to_vote.php?error=session_expired');
    exit();
}

// Get current user's data
$currentUser = getCurrentUser();

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidate_id = $_POST['candidate_id'] ?? '';
    
    if (empty($candidate_id)) {
        $error = "Selecteer een kandidaat om te stemmen";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Record the vote
            $stmt = $pdo->prepare("
                INSERT INTO votes (UserID, CandidateID, ElectionID, QRCodeID, TimeStamp) 
                VALUES (:user_id, :candidate_id, :election_id, :qr_code_id, NOW())
            ");
            $stmt->execute([
                'user_id' => $currentUser['UserID'],
                'candidate_id' => $candidate_id,
                'election_id' => $_SESSION['voting_session']['election_id'],
                'qr_code_id' => $_SESSION['voting_session']['qr_code_id']
            ]);
            
            // Mark QR code as used
            $stmt = $pdo->prepare("
                UPDATE qrcodes 
                SET Status = 'used', UsedAt = NOW() 
                WHERE QRCodeID = :qr_code_id
            ");
            $stmt->execute(['qr_code_id' => $_SESSION['voting_session']['qr_code_id']]);
            
            $pdo->commit();
            
            // Clear voting session
            unset($_SESSION['voting_session']);
            
            // Redirect to success page
            header('Location: vote_success.php');
            exit();
            
        } catch(PDOException $e) {
            $pdo->rollBack();
            error_log("Voting error: " . $e->getMessage());
            $error = "Er is een fout opgetreden. Probeer het later opnieuw.";
        }
    }
}

// Get election from session
$election_id = $_SESSION['voting_session']['election_id'];
$election_name = $_SESSION['voting_session']['election_name'];

// Get candidates for the election
try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.PartyName, ct.CandidateType 
        FROM candidates c 
        JOIN parties p ON c.PartyID = p.PartyID 
        JOIN candidatetype ct ON c.CandidateTypeID = ct.CandidateTypeID 
        WHERE c.ElectionID = :election_id
        ORDER BY ct.CandidateType, p.PartyName
    ");
    $stmt->execute(['election_id' => $election_id]);
    $candidates = $stmt->fetchAll();
    
    if (empty($candidates)) {
        $error = "Er zijn geen kandidaten gevonden voor deze verkiezing";
    }
} catch(PDOException $e) {
    error_log("Error fetching candidates: " . $e->getMessage());
    $error = "Er is een fout opgetreden bij het ophalen van de kandidaten.";
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stemmen - E-Stem Suriname</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007749;
            color: white;
            padding: 1rem;
            text-align: center;
        }
        .main-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .candidate-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .candidate-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .candidate-card:hover {
            border-color: #007749;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .candidate-card.selected {
            border-color: #007749;
            background-color: #f0f9f4;
        }
        .candidate-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .candidate-party {
            color: #666;
            font-size: 0.9em;
        }
        .candidate-type {
            color: #007749;
            font-size: 0.8em;
            margin-top: 5px;
        }
        .btn {
            background-color: #007749;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #006241;
        }
        .btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .timer {
            text-align: center;
            font-size: 1.2em;
            margin-bottom: 20px;
            color: #007749;
        }
        .warning {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>E-Stem Suriname</h1>
        <p>Stem op uw favoriete kandidaat</p>
    </div>

    <div class="container">
        <div class="main-content">
            <?php if (isset($error)): ?>
                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($election) && !empty($candidates)): ?>
                <div class="timer">
                    Resterende tijd: <span id="countdown">30:00</span>
                </div>

                <div class="warning">
                    Let op: U heeft 30 minuten om uw stem uit te brengen. Na deze tijd wordt uw sessie beëindigd.
                </div>

                <form method="POST" action="" id="voteForm">
                    <input type="hidden" name="candidate_id" id="selected_candidate">
                    
                    <div class="candidate-list">
                        <?php foreach ($candidates as $candidate): ?>
                            <div class="candidate-card" data-candidate-id="<?php echo $candidate['CandidateID']; ?>">
                                <div class="candidate-name">
                                    <?php echo htmlspecialchars($candidate['Voornaam'] . ' ' . $candidate['Achternaam']); ?>
                                </div>
                                <div class="candidate-party">
                                    <?php echo htmlspecialchars($candidate['PartyName']); ?>
                                </div>
                                <div class="candidate-type">
                                    <?php echo htmlspecialchars($candidate['TypeName']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" class="btn" disabled>Stem uitbrengen</button>
                </form>

                <script>
                    // Handle candidate selection
                    const cards = document.querySelectorAll('.candidate-card');
                    const form = document.getElementById('voteForm');
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const selectedInput = document.getElementById('selected_candidate');

                    cards.forEach(card => {
                        card.addEventListener('click', () => {
                            // Remove selection from all cards
                            cards.forEach(c => c.classList.remove('selected'));
                            // Add selection to clicked card
                            card.classList.add('selected');
                            // Update hidden input
                            selectedInput.value = card.dataset.candidateId;
                            // Enable submit button
                            submitBtn.disabled = false;
                        });
                    });

                    // Countdown timer
                    let timeLeft = <?php echo $session_timeout - (time() - $_SESSION['voting_session']['start_time']); ?>;
                    const countdownElement = document.getElementById('countdown');

                    function updateTimer() {
                        const minutes = Math.floor(timeLeft / 60);
                        const seconds = timeLeft % 60;
                        countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                        
                        if (timeLeft === 0) {
                            window.location.href = 'scan_to_vote.php?error=session_expired';
                        } else {
                            timeLeft--;
                            setTimeout(updateTimer, 1000);
                        }
                    }

                    updateTimer();
                </script>
            <?php else: ?>
                <div class="error">
                    Er zijn momenteel geen kandidaten beschikbaar om op te stemmen.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 
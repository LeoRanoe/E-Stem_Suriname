<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/VoterAuth.php';

// --- VOTE SUBMISSION LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("[VOTE_DEBUG] POST request received. Starting vote submission process.");

    $voterId = $_SESSION['voter_id'] ?? null;
    $dnaCandidateId = $_POST['dna_candidate_id'] ?? null;
    $rrCandidateId = $_POST['rr_candidate_id'] ?? null;
    $electionId = $_POST['election_id'] ?? null;

    // Basic validation
    if (!$voterId || !$dnaCandidateId || !$rrCandidateId || !$electionId) {
        error_log("[VOTE_DEBUG] Validation failed: Missing data. VoterID: $voterId, DNA_ID: $dnaCandidateId, RR_ID: $rrCandidateId, ElectionID: $electionId");
        // Set an error message and redirect back
        $_SESSION['error_message'] = "Er is een fout opgetreden. Niet alle gegevens zijn ontvangen. Probeer het opnieuw.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $voterAuth = new VoterAuth($pdo);

    // Double-check if voter has already voted
    if ($voterAuth->hasVoted($voterId, $electionId)) {
        error_log("[VOTE_DEBUG] Security check failed: Voter ID $voterId has already voted.");
        $_SESSION['message'] = "U heeft al gestemd in deze verkiezing.";
        header("Location: " . BASE_URL . "/pages/voting/thank-you.php");
        exit();
    }

    try {
        error_log("[VOTE_DEBUG] Starting database transaction for voter ID: $voterId");
        $pdo->beginTransaction();

        // Create a QR Code entry for this vote if needed
        $qrCodeId = $voterAuth->getOrCreateQRCodeEntry($voterId, $electionId);
        error_log("[VOTE_DEBUG] QR Code ID: $qrCodeId obtained for voter ID: $voterId");

        // Insert DNA vote - using correct column names from the database schema
        $stmtDNA = $pdo->prepare("INSERT INTO votes (UserID, CandidateID, ElectionID, QRCodeID, TimeStamp) VALUES (?, ?, ?, ?, NOW())");
        $stmtDNA->execute([$voterId, $dnaCandidateId, $electionId, $qrCodeId]);
        error_log("[VOTE_DEBUG] Inserted DNA vote. Voter: $voterId, Candidate: $dnaCandidateId, Election: $electionId");

        // Insert RR vote - using correct column names from the database schema
        $stmtRR = $pdo->prepare("INSERT INTO votes (UserID, CandidateID, ElectionID, QRCodeID, TimeStamp) VALUES (?, ?, ?, ?, NOW())");
        $stmtRR->execute([$voterId, $rrCandidateId, $electionId, $qrCodeId]);
        error_log("[VOTE_DEBUG] Inserted RR vote. Voter: $voterId, Candidate: $rrCandidateId, Election: $electionId");

        // Mark QR code as used
        $stmtQR = $pdo->prepare("UPDATE qrcodes SET Status = 'used', UsedAt = NOW() WHERE QRCodeID = ?");
        $stmtQR->execute([$qrCodeId]);
        error_log("[VOTE_DEBUG] Marked QR code as used for voter ID: $voterId");
        
        // Now also mark the voucher as used after successful vote
        if (isset($_SESSION['voucher_id'])) {
            $voucherId = $_SESSION['voucher_id'];
            $voterAuth = new VoterAuth($pdo);
            $voterAuth->markVoucherAsUsed($voucherId);
            error_log("[VOTE_DEBUG] Marked voucher ID: $voucherId as used for voter ID: $voterId");
        }

        $pdo->commit();
        error_log("[VOTE_DEBUG] Transaction committed successfully for voter ID: $voterId");

        // Set session flag and redirect to thank you page
        $_SESSION['has_voted'] = true;
        $_SESSION['message'] = "Uw stem is succesvol uitgebracht! Bedankt voor uw deelname.";
        header("Location: " . BASE_URL . "/pages/voting/thank-you.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("[VOTE_DEBUG] DATABASE ERROR: Transaction rolled back for voter ID: $voterId. Error: " . $e->getMessage());
        die("Databasefout: Uw stem kon niet worden verwerkt. Details: " . $e->getMessage());
    }
}
// --- END VOTE SUBMISSION LOGIC ---


// Check if voter is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: " . BASE_URL . "/voter/index.php");
    exit();
}

// Initialize VoterAuth
$voterAuth = new VoterAuth($pdo);

// Debug log to check voter ID
error_log("[DEBUG] Checking if voter ID " . $_SESSION['voter_id'] . " has already voted");

// Check if has_voted session flag is set
if (isset($_SESSION['has_voted'])) {
    error_log("[DEBUG] Session has_voted flag is set, redirecting to thank-you page");
    $_SESSION['message'] = "U heeft al gestemd in deze verkiezing.";
    header("Location: " . BASE_URL . "/pages/voting/thank-you.php");
    exit();
}

// Check if voter has already voted in the database
if ($voterAuth->hasVoted($_SESSION['voter_id'])) {
    error_log("[DEBUG] hasVoted() function returned true, redirecting to thank-you page");
    $_SESSION['message'] = "U heeft al gestemd in deze verkiezing.";
    header("Location: " . BASE_URL . "/pages/voting/thank-you.php");
    exit();
}

// If we get here, the voter has not voted yet
error_log("[DEBUG] Voter ID " . $_SESSION['voter_id'] . " has not voted yet, proceeding to voting page");

try {
    // Fetch current election
    $stmt = $pdo->query("SELECT ElectionID, ElectionName, ElectionDate, EndDate FROM elections WHERE Status = 'active' LIMIT 1");
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$election) {
        die("No active election found.");
    }
    $electionID = $election['ElectionID'];
    $electionName = $election['ElectionName'];
    $electionEndDate = new DateTime($election['EndDate']);
    $now = new DateTime();
    
    // Calculate time left
    $timeLeft = $now->diff($electionEndDate);
    $daysLeft = $timeLeft->days;
    $hoursLeft = $timeLeft->h;
    $minutesLeft = $timeLeft->i;

    // Fetch DNA candidates
    $stmtDNA = $pdo->prepare("
        SELECT c.CandidateID, c.Name, p.PartyName, d.DistrictName, c.Photo, c.PartyID, c.DistrictID,
               p.Logo as party_logo
        FROM candidates c
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN districten d ON c.DistrictID = d.DistrictID
        WHERE c.CandidateType = 'DNA' AND c.ElectionID = ?
        ORDER BY p.PartyName, c.Name
    ");
    $stmtDNA->execute([$electionID]);
    $dnaCandidates = $stmtDNA->fetchAll(PDO::FETCH_ASSOC);

    // Fetch RR candidates
    $stmtRR = $pdo->prepare("
        SELECT c.CandidateID, c.Name, p.PartyName, d.DistrictName, c.Photo, c.PartyID, c.DistrictID,
               c.ResortID, r.name as ResortName, p.Logo as party_logo
        FROM candidates c
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN districten d ON c.DistrictID = d.DistrictID
        LEFT JOIN resorts r ON c.ResortID = r.id
        WHERE c.CandidateType = 'RR' AND c.ElectionID = ?
        ORDER BY p.PartyName, c.Name
    ");
    $stmtRR->execute([$electionID]);
    $rrCandidates = $stmtRR->fetchAll(PDO::FETCH_ASSOC);

    // Fetch political parties
    $stmtParties = $pdo->query("SELECT PartyID, PartyName, Logo FROM parties ORDER BY PartyName ASC");
    $parties = $stmtParties->fetchAll(PDO::FETCH_ASSOC);

    // Fetch districts
    $stmtDistricts = $pdo->query("SELECT DistrictID, DistrictName FROM districten ORDER BY DistrictName ASC");
    $districts = $stmtDistricts->fetchAll(PDO::FETCH_ASSOC);

    // Fetch resorts for filtering
    $stmtResorts = $pdo->query("SELECT id, name, district_id FROM resorts ORDER BY name ASC");
    $resorts = $stmtResorts->fetchAll(PDO::FETCH_ASSOC);

    // Get voter info
    $stmtUser = $pdo->prepare("SELECT first_name, last_name, district_id, resort_id FROM voters WHERE id = ?");
    $stmtUser->execute([$_SESSION['voter_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    $userFullName = $user ? $user['first_name'] . " " . $user['last_name'] : "Guest";
    
    // Get voter's district and resort
    $voterDistrictId = $user ? $user['district_id'] : null;
    $voterResortId = $user ? $user['resort_id'] : null;

} catch (PDOException $e) {
    die("An error occurred while retrieving election data: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Your Vote - e-Stem Suriname</title>
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        .candidate-card { transition: all 0.2s ease-in-out; border: 2px solid transparent; background: white; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .candidate-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); border-color: #e5e7eb; }
        .candidate-card.selected { border: 2px solid #007749; background-color: #ecfdf5; box-shadow: 0 4px 12px rgba(0, 119, 73, 0.2); }
        .submit-button { background: linear-gradient(135deg, #007749 0%, #006241 100%); color: white; padding: 12px 32px; border-radius: 8px; font-weight: 600; font-size: 16px; transition: all 0.2s ease-in-out; border: none; cursor: pointer; }
        .submit-button:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(0, 119, 73, 0.3); }
        .submit-button:disabled { background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%); cursor: not-allowed; transform: none; box-shadow: none; }
        .candidate-photo { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #e5e7eb; transition: border-color 0.2s ease-in-out; }
        .candidate-card.selected .candidate-photo { border-color: #007749; }
        .countdown-timer { background: linear-gradient(135deg, #007749 0%, #006241 100%); color: white; border-radius: 8px; padding: 12px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        .time-unit { background: rgba(255, 255, 255, 0.2); padding: 8px; border-radius: 4px; font-weight: bold; min-width: 50px; text-align: center; }
        .party-logo { width: 36px; height: 36px; object-fit: contain; }
        
        /* Step indicator styles */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: #e5e7eb;
            z-index: 1;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #6b7280;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        .step.active {
            background: #007749;
            border-color: #007749;
            color: white;
        }
        .step.completed {
            background: #ecfdf5;
            border-color: #007749;
            color: #007749;
        }
        .step-label {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
            white-space: nowrap;
            font-weight: 500;
        }
        .step.active .step-label {
            color: #007749;
            font-weight: 600;
        }
        .step.completed .step-label {
            color: #007749;
        }
        
        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transform: translateY(20px);
            transition: all 0.3s ease;
        }
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
        
        /* Enhance candidate cards */
        .candidate-card {
            position: relative;
            overflow: hidden;
        }
        .candidate-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 0;
            border-style: solid;
            border-width: 0 40px 40px 0;
            border-color: transparent transparent transparent transparent;
            transition: all 0.3s ease;
        }
        .candidate-card.selected::before {
            border-color: transparent #007749 transparent transparent;
        }
        .candidate-check {
            transition: all 0.3s ease;
        }
        .candidate-card.selected .candidate-check {
            border-color: #007749;
            background: #007749;
        }
        .candidate-card.selected .candidate-check i {
            color: white;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col">
    <?php include '../../include/nav.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header Section -->
        <div class="bg-white p-6 rounded-xl shadow-md mb-8">
            <h1 class="text-3xl font-bold text-suriname-green mb-2"><?= htmlspecialchars($electionName) ?></h1>
            <p class="text-gray-600 mb-6">
                <i class="fas fa-calendar-alt mr-2"></i>
                <?= date('d F Y', strtotime($election['ElectionDate'])) ?>
            </p>
            
            <div class="countdown-timer p-4 rounded-lg mb-4 flex flex-wrap items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-white mb-1">Tijd resterend om te stemmen:</h2>
                    <p class="text-xs text-white/70">Deze verkiezing sluit op <?= date('d F Y H:i', strtotime($election['EndDate'])) ?></p>
                </div>
                <div class="flex space-x-2 mt-2 sm:mt-0">
                    <?php if ($daysLeft > 0): ?>
                    <div class="time-unit">
                        <div class="text-xl"><?= $daysLeft ?></div>
                        <div class="text-xs text-white/70">dagen</div>
                    </div>
                    <?php endif; ?>
                    <div class="time-unit">
                        <div class="text-xl"><?= $hoursLeft ?></div>
                        <div class="text-xs text-white/70">uren</div>
                    </div>
                    <div class="time-unit">
                        <div class="text-xl"><?= $minutesLeft ?></div>
                        <div class="text-xs text-white/70">min</div>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-200 pt-4">
                <h3 class="font-semibold text-gray-700 mb-2">Welkom, <?= htmlspecialchars($userFullName) ?>!</h3>
                <p class="text-gray-600 mb-2">U kunt in deze verkiezing stemmen op:</p>
                <ul class="list-disc list-inside text-gray-600 ml-2 mb-4">
                    <li>Eén kandidaat voor De Nationale Assemblée (DNA)</li>
                    <li>Eén kandidaat voor de Ressortsraad (RR)</li>
                </ul>
                <p class="text-sm text-suriname-red">
                    <i class="fas fa-exclamation-triangle mr-2"></i> 
                    Let op: u kunt slechts één keer stemmen in deze verkiezing.
                </p>
            </div>
            
            <!-- Step Indicator -->
            <div class="step-indicator mt-6">
                <div class="step active" id="step1">
                    <span>1</span>
                    <span class="step-label">DNA Keuze</span>
                </div>
                <div class="step" id="step2">
                    <span>2</span>
                    <span class="step-label">RR Keuze</span>
                </div>
                <div class="step" id="step3">
                    <span>3</span>
                    <span class="step-label">Bevestigen</span>
                </div>
            </div>
        </div>
        
        <!-- User-friendly guide -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 rounded-md shadow-sm">
            <h3 class="font-bold text-blue-700 mb-2 flex items-center">
                <i class="fas fa-info-circle mr-2"></i> Hulp bij stemmen
            </h3>
            <div class="text-blue-600 text-sm space-y-2">
                <p>1. Kies eerst een kandidaat voor de Nationale Assemblée (DNA) door op hun kaart te klikken</p>
                <p>2. Kies daarna een kandidaat voor de Ressortsraad (RR)</p>
                <p>3. Gebruik de filters om gemakkelijker kandidaten te vinden</p>
                <p>4. Controleer uw selecties en klik op 'Stem uitbrengen' wanneer u klaar bent</p>
            </div>
        </div>
        
        <!-- VOTING FORM START -->
        <form id="vote-form" method="POST" action="">
            <input type="hidden" id="selected-dna-candidate" name="dna_candidate_id" value="">
            <input type="hidden" id="selected-rr-candidate" name="rr_candidate_id" value="">
            <input type="hidden" name="election_id" value="<?= htmlspecialchars($electionID) ?>">

            <!-- DNA Section -->
            <section class="mb-12">
                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h2 class="text-2xl font-bold text-suriname-green mb-6 flex items-center">
                        <i class="fas fa-landmark mr-3"></i>De Nationale Assemblée
                    </h2>
                    
                    <!-- Filters -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <h3 class="font-semibold mb-4 text-gray-700 flex items-center">
                            <i class="fas fa-filter mr-2 text-suriname-green"></i>Filter Kandidaten
                        </h3>
                        <div class="flex flex-wrap gap-4">
                            <div class="w-full md:w-auto">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Politieke Partij</label>
                                <div class="relative">
                                    <select id="dna-party-filter" class="w-full md:w-48 border border-gray-300 rounded-md p-2 pl-8 appearance-none">
                                        <option value="">Alle Partijen</option>
                                        <?php foreach ($parties as $party): ?>
                                            <option value="<?= htmlspecialchars($party['PartyID']) ?>">
                                                <?= htmlspecialchars($party['PartyName']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                                        <i class="fas fa-flag text-suriname-green"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="w-full md:w-auto flex items-end">
                                <button onclick="resetFilters('dna')" type="button" 
                                        class="inline-flex items-center text-suriname-green hover:text-suriname-dark-green focus:outline-none">
                                    <i class="fas fa-sync-alt mr-1"></i> Filters herstellen
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- DNA Candidates Grid -->
                    <div id="dna-candidates" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php if (!empty($dnaCandidates)): ?>
                            <?php foreach ($dnaCandidates as $candidate): ?>
                                <div class="candidate-card p-4 rounded-xl cursor-pointer dna-card"
                                     data-party="<?= htmlspecialchars($candidate['PartyID']) ?>"
                                     data-district="<?= htmlspecialchars($candidate['DistrictID']) ?>"
                                     data-candidate-id="<?= htmlspecialchars($candidate['CandidateID']) ?>"
                                     onclick="selectCandidate(this, 'dna')">
                                    <div class="flex items-start gap-4">
                                        <img src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : BASE_URL . '/assets/images/placeholder-profile.jpg' ?>" 
                                             alt="<?= htmlspecialchars($candidate['Name']) ?>" 
                                             class="candidate-photo">
                                        
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <?php if (!empty($candidate['party_logo'])): ?>
                                                    <img src="<?= htmlspecialchars($candidate['party_logo']) ?>" 
                                                         alt="<?= htmlspecialchars($candidate['PartyName']) ?>" 
                                                         class="party-logo">
                                                <?php endif; ?>
                                                <span class="text-sm font-medium text-suriname-green"><?= htmlspecialchars($candidate['PartyName']) ?></span>
                                            </div>
                                            
                                            <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($candidate['Name']) ?></h3>
                                            <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($candidate['DistrictName']) ?></p>
                                        </div>
                                        
                                        <div class="ml-auto">
                                            <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center candidate-check">
                                                <i class="fas fa-check text-suriname-green hidden"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-3 p-4 text-center">
                                <p class="text-gray-500">No DNA candidates available for this election.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- RR Section -->
            <section class="mb-12">
                <div class="bg-white p-6 rounded-xl shadow-md">
                    <h2 class="text-2xl font-bold text-suriname-green mb-6 flex items-center">
                        <i class="fas fa-city mr-3"></i>Resortsraden
                    </h2>
                    
                    <!-- Filters -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <h3 class="font-semibold mb-4 text-gray-700 flex items-center">
                            <i class="fas fa-filter mr-2 text-suriname-green"></i>Filter Kandidaten
                        </h3>
                        <div class="flex flex-wrap gap-4">
                            <div class="w-full md:w-auto">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Politieke Partij</label>
                                <div class="relative">
                                    <select id="rr-party-filter" class="w-full md:w-48 border border-gray-300 rounded-md p-2 pl-8 appearance-none">
                                        <option value="">Alle Partijen</option>
                                        <?php foreach ($parties as $party): ?>
                                            <option value="<?= htmlspecialchars($party['PartyID']) ?>">
                                                <?= htmlspecialchars($party['PartyName']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                                        <i class="fas fa-flag text-suriname-green"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="w-full md:w-auto">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Resort</label>
                                <div class="relative">
                                    <select id="rr-resort-filter" class="w-full md:w-48 border border-gray-300 rounded-md p-2 pl-8 appearance-none" disabled>
                                        <?php foreach ($resorts as $resort): ?>
                                            <?php if ($voterResortId == $resort['id']): ?>
                                            <option value="<?= htmlspecialchars($resort['id']) ?>"
                                                    data-district-id="<?= htmlspecialchars($resort['district_id']) ?>"
                                                    selected>
                                                <?= htmlspecialchars($resort['name']) ?>
                                            </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                                        <i class="fas fa-map-pin text-suriname-green"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="w-full md:w-auto flex items-end">
                                <button onclick="resetFilters('rr')" type="button" 
                                        class="inline-flex items-center text-suriname-green hover:text-suriname-dark-green focus:outline-none">
                                    <i class="fas fa-sync-alt mr-1"></i> Partij filter herstellen
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- RR Candidates Grid -->
                    <div id="rr-candidates" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php if (!empty($rrCandidates)): ?>
                            <?php foreach ($rrCandidates as $candidate): ?>
                                <div class="candidate-card p-4 rounded-xl cursor-pointer rr-card"
                                     data-party="<?= htmlspecialchars($candidate['PartyID']) ?>"
                                     data-resort="<?= htmlspecialchars($candidate['ResortID']) ?>"
                                     data-candidate-id="<?= htmlspecialchars($candidate['CandidateID']) ?>"
                                     onclick="selectCandidate(this, 'rr')">
                                    <div class="flex items-start gap-4">
                                        <img src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : BASE_URL . '/assets/images/placeholder-profile.jpg' ?>" 
                                             alt="<?= htmlspecialchars($candidate['Name']) ?>" 
                                             class="candidate-photo">
                                        
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <?php if (!empty($candidate['party_logo'])): ?>
                                                    <img src="<?= htmlspecialchars($candidate['party_logo']) ?>" 
                                                         alt="<?= htmlspecialchars($candidate['PartyName']) ?>" 
                                                         class="party-logo">
                                                <?php endif; ?>
                                                <span class="text-sm font-medium text-suriname-green"><?= htmlspecialchars($candidate['PartyName']) ?></span>
                                            </div>
                                            
                                            <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($candidate['Name']) ?></h3>
                                            <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($candidate['ResortName']) ?></p>
                                        </div>
                                        
                                        <div class="ml-auto">
                                            <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center candidate-check">
                                                <i class="fas fa-check text-suriname-green hidden"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-span-3 p-4 text-center">
                                <p class="text-gray-500">No RR candidates available for this election.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Submission Section -->
            <div class="bg-white p-6 rounded-xl shadow-md mt-8 sticky bottom-4">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="text-center md:text-left">
                        <h3 class="font-bold text-lg text-gray-800">Bevestig uw stem</h3>
                        <p class="text-gray-600">Controleer uw selecties en breng uw stem uit.</p>
                    </div>
                    <button id="submit-vote" type="button" class="submit-button w-full md:w-auto" disabled onclick="showConfirmationModal()">
                        <i class="fas fa-check-to-slot mr-2"></i>Stem uitbrengen
                    </button>
                </div>
            </div>
        </form>
        <!-- VOTING FORM END -->
        
        <!-- Confirmation Modal -->
        <div class="modal-overlay" id="confirmation-modal">
            <div class="modal-content">
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-suriname-green bg-opacity-10 text-suriname-green mb-4">
                        <i class="fas fa-vote-yea text-2xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800">Bevestig uw stem</h3>
                    <p class="text-gray-600 mt-2">U staat op het punt om uw stem uit te brengen. Deze actie kan niet ongedaan worden gemaakt.</p>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <h4 class="font-semibold text-gray-700 mb-2">Uw selecties:</h4>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-500">De Nationale Assemblée (DNA):</p>
                        <div id="dna-confirmation" class="flex items-center mt-2 p-3 bg-white rounded-lg border border-gray-200">
                            <div class="w-10 h-10 rounded-full overflow-hidden mr-3">
                                <img id="dna-candidate-photo" src="" alt="Candidate" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <p id="dna-candidate-name" class="font-medium text-gray-800">-</p>
                                <p id="dna-candidate-party" class="text-sm text-gray-500">-</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500">Ressortsraad (RR):</p>
                        <div id="rr-confirmation" class="flex items-center mt-2 p-3 bg-white rounded-lg border border-gray-200">
                            <div class="w-10 h-10 rounded-full overflow-hidden mr-3">
                                <img id="rr-candidate-photo" src="" alt="Candidate" class="w-full h-full object-cover">
                            </div>
                            <div>
                                <p id="rr-candidate-name" class="font-medium text-gray-800">-</p>
                                <p id="rr-candidate-party" class="text-sm text-gray-500">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3 justify-end">
                    <button type="button" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50" onclick="hideConfirmationModal()">
                        Annuleren
                    </button>
                    <button type="button" class="px-6 py-2 bg-suriname-green text-white rounded-lg font-medium hover:bg-suriname-dark-green" onclick="submitVote()">
                        Bevestigen en Stemmen
                    </button>
                </div>
            </div>
        </div>

    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log("Voting page loaded. Initializing scripts.");
            // Set default filters on load
            filterCandidates('dna');
            filterCandidates('rr');
            
            // Update steps based on selections
            updateSteps();
        });
        
        // Selected candidates data
        let selectedDNA = null;
        let selectedRR = null;

        function selectCandidate(element, type) {
            console.log(`Candidate selected. Type: ${type}, ID: ${element.dataset.candidateId}`);
            const container = document.getElementById(type + '-candidates');
            const hiddenInput = document.getElementById('selected-' + type + '-candidate');

            // Remove 'selected' from all cards in the same section
            container.querySelectorAll('.candidate-card').forEach(card => {
                card.classList.remove('selected');
                card.querySelector('.fa-check').classList.add('hidden');
            });

            // Add 'selected' to the clicked card
            element.classList.add('selected');
            element.querySelector('.fa-check').classList.remove('hidden');

            // Update the hidden input value
            hiddenInput.value = element.dataset.candidateId;
            console.log(`Hidden input for ${type} updated with value: ${hiddenInput.value}`);
            
            // Store selected candidate data
            if (type === 'dna') {
                selectedDNA = {
                    id: element.dataset.candidateId,
                    name: element.querySelector('h3').textContent,
                    party: element.querySelector('.text-sm.font-medium').textContent,
                    photo: element.querySelector('.candidate-photo').src
                };
            } else {
                selectedRR = {
                    id: element.dataset.candidateId,
                    name: element.querySelector('h3').textContent,
                    party: element.querySelector('.text-sm.font-medium').textContent,
                    photo: element.querySelector('.candidate-photo').src
                };
            }

            checkSelections();
            updateSteps();
        }
        
        function updateSteps() {
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const step3 = document.getElementById('step3');
            
            // Reset all steps
            step1.className = 'step';
            step2.className = 'step';
            step3.className = 'step';
            
            if (selectedDNA) {
                step1.className = 'step completed';
                step2.className = 'step active';
                
                if (selectedRR) {
                    step2.className = 'step completed';
                    step3.className = 'step active';
                }
            } else {
                step1.className = 'step active';
            }
        }

        function checkSelections() {
            const dnaSelected = document.getElementById('selected-dna-candidate').value;
            const rrSelected = document.getElementById('selected-rr-candidate').value;
            const submitButton = document.getElementById('submit-vote');

            console.log(`Checking selections. DNA: '${dnaSelected}', RR: '${rrSelected}'`);

            if (dnaSelected && rrSelected) {
                submitButton.disabled = false;
                console.log("Both candidates selected. Submit button enabled.");
            } else {
                submitButton.disabled = true;
                console.log("One or more candidates not selected. Submit button remains disabled.");
            }
        }

        function filterCandidates(type) {
            console.log(`Filtering candidates for type: ${type}`);
            let partyFilter, locationFilter, candidates, locationType;

            if (type === 'dna') {
                partyFilter = document.getElementById('dna-party-filter').value;
                locationFilter = ''; // No district filter for DNA
                candidates = document.querySelectorAll('#dna-candidates .dna-card');
                locationType = 'district';
            } else { // rr
                partyFilter = document.getElementById('rr-party-filter').value;
                locationFilter = '<?= $voterResortId ?>'; // Always use voter's resort
                candidates = document.querySelectorAll('#rr-candidates .rr-card');
                locationType = 'resort';
            }

            console.log(`Filters - Party: ${partyFilter}, Location: ${locationFilter}`);
            let visibleCount = 0;

            candidates.forEach(card => {
                const partyMatch = !partyFilter || card.dataset.party === partyFilter;
                const locationMatch = !locationFilter || card.dataset[locationType] === locationFilter;
                
                if (partyMatch && locationMatch) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            console.log(`${visibleCount} candidates are visible after filtering.`);
        }
        
        function resetFilters(type) {
            if (type === 'dna') {
                document.getElementById('dna-party-filter').value = '';
                // No district filter to reset
            } else { // rr
                document.getElementById('rr-party-filter').value = '';
                // Resort filter is locked, no need to reset
            }
            filterCandidates(type);
            console.log(`Filters for ${type} have been reset.`);
        }
        
        function showConfirmationModal() {
            // Update modal with selected candidates
            if (selectedDNA) {
                document.getElementById('dna-candidate-name').textContent = selectedDNA.name;
                document.getElementById('dna-candidate-party').textContent = selectedDNA.party;
                document.getElementById('dna-candidate-photo').src = selectedDNA.photo;
            }
            
            if (selectedRR) {
                document.getElementById('rr-candidate-name').textContent = selectedRR.name;
                document.getElementById('rr-candidate-party').textContent = selectedRR.party;
                document.getElementById('rr-candidate-photo').src = selectedRR.photo;
            }
            
            // Show modal
            document.getElementById('confirmation-modal').classList.add('active');
        }
        
        function hideConfirmationModal() {
            document.getElementById('confirmation-modal').classList.remove('active');
        }
        
        function submitVote() {
            // Submit the form
            document.getElementById('vote-form').submit();
        }

        // Add event listeners to filters
        document.getElementById('dna-party-filter').addEventListener('change', () => filterCandidates('dna'));
        // No district filter event listener needed
        document.getElementById('rr-party-filter').addEventListener('change', () => filterCandidates('rr'));
        // Resort filter is locked, no need for event listener
    </script>
</body>
</html>
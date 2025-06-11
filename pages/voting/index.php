<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/VoterAuth.php';

// Check if voter is logged in
if (!isset($_SESSION['voter_id'])) {
    // Redirect to login page
    header("Location: " . BASE_URL . "/voter/index.php");
    exit();
}

// Initialize VoterAuth
$voterAuth = new VoterAuth($pdo);

// Check if voter has already voted
if ($voterAuth->hasVoted($_SESSION['voter_id']) || isset($_SESSION['has_voted'])) {
    // Redirect to results page with message
    $_SESSION['message'] = "You have already voted in this election.";
    header("Location: " . BASE_URL . "/pages/voting/thank-you.php");
    exit();
}

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
        JOIN parties p ON c.PartyID = p.PartyID
        JOIN districten d ON c.DistrictID = d.DistrictID
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
        JOIN parties p ON c.PartyID = p.PartyID
        JOIN districten d ON c.DistrictID = d.DistrictID
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
        
        body { 
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc; 
        }
        
        .candidate-card { 
            transition: all 0.2s ease-in-out;
            border: 2px solid transparent;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .candidate-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-color: #e5e7eb;
        }
        
        .candidate-card.selected { 
            border: 2px solid #007749; 
            background-color: #ecfdf5;
            box-shadow: 0 4px 12px rgba(0, 119, 73, 0.2);
        }
        
        .submit-button { 
            background: linear-gradient(135deg, #007749 0%, #006241 100%);
            color: white; 
            padding: 12px 32px; 
            border-radius: 8px; 
            font-weight: 600;
            font-size: 16px;
            transition: all 0.2s ease-in-out;
            border: none;
            cursor: pointer;
        }
        
        .submit-button:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(0, 119, 73, 0.3);
        }
        
        .submit-button:disabled {
            background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .candidate-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e5e7eb;
            transition: border-color 0.2s ease-in-out;
        }
        
        .candidate-card.selected .candidate-photo {
            border-color: #007749;
        }
        
        /* Added timer styles */
        .countdown-timer {
            background: linear-gradient(135deg, #007749 0%, #006241 100%);
            color: white;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .time-unit {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px;
            border-radius: 4px;
            font-weight: bold;
            min-width: 50px;
            text-align: center;
        }
        
        /* Party logo style */
        .party-logo {
            width: 36px;
            height: 36px;
            object-fit: contain;
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
                        
                        <div class="w-full md:w-auto">
                            <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                            <div class="relative">
                                <select id="dna-district-filter" class="w-full md:w-48 border border-gray-300 rounded-md p-2 pl-8 appearance-none">
                                    <option value="">Alle Districten</option>
                                    <?php foreach ($districts as $district): ?>
                                        <option value="<?= htmlspecialchars($district['DistrictID']) ?>" 
                                                <?= ($voterDistrictId == $district['DistrictID']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($district['DistrictName']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                                    <i class="fas fa-map-marker-alt text-suriname-green"></i>
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                            <div class="relative">
                                <select id="rr-district-filter" class="w-full md:w-48 border border-gray-300 rounded-md p-2 pl-8 appearance-none bg-gray-100" disabled>
                                    <?php 
                                    // Find voter's district
                                    $voterDistrictName = "Onbekend District";
                                    foreach ($districts as $district) {
                                        if ($district['DistrictID'] == $voterDistrictId) {
                                            $voterDistrictName = $district['DistrictName'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <option value="<?= htmlspecialchars($voterDistrictId) ?>" selected>
                                        <?= htmlspecialchars($voterDistrictName) ?> (Uw District)
                                    </option>
                                </select>
                                <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                                    <i class="fas fa-map-marker-alt text-suriname-green"></i>
                                </div>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                    <i class="fas fa-lock text-gray-500" title="U kunt alleen stemmen op RR-kandidaten uit uw eigen district"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="w-full md:w-auto">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Resort</label>
                            <div class="relative">
                                <select id="rr-resort-filter" class="w-full md:w-48 border border-gray-300 rounded-md p-2 pl-8 appearance-none bg-gray-100" disabled>
                                    <?php 
                                    // Find voter's resort
                                    $voterResortName = "Onbekend Resort";
                                    foreach ($resorts as $resort) {
                                        if ($resort['id'] == $voterResortId) {
                                            $voterResortName = $resort['name'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <option value="<?= htmlspecialchars($voterResortId) ?>" selected>
                                        <?= htmlspecialchars($voterResortName) ?> (Uw Resort)
                                    </option>
                                </select>
                                <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                                    <i class="fas fa-map-marker-alt text-suriname-green"></i>
                                </div>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                    <i class="fas fa-lock text-gray-500" title="U kunt alleen stemmen op RR-kandidaten uit uw eigen resort"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="w-full md:w-auto flex items-end">
                            <button onclick="resetFilters('rr')" type="button" 
                                    class="inline-flex items-center text-suriname-green hover:text-suriname-dark-green focus:outline-none">
                                <i class="fas fa-sync-alt mr-1"></i> Filters herstellen
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
                                 data-district="<?= htmlspecialchars($candidate['DistrictID']) ?>"
                                 data-resort="<?= isset($candidate['ResortID']) ? htmlspecialchars($candidate['ResortID']) : '' ?>"
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
                                        <div class="text-sm text-gray-500 mb-1"><?= htmlspecialchars($candidate['DistrictName']) ?></div>
                                        <?php if (!empty($candidate['ResortName'])): ?>
                                        <div class="text-xs text-suriname-green font-medium mb-2">
                                            <i class="fas fa-map-marker-alt mr-1"></i> 
                                            <?= htmlspecialchars($candidate['ResortName']) ?>
                                        </div>
                                        <?php endif; ?>
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

        <!-- Submit Button -->
        <section class="text-center mb-12">
            <div class="bg-white p-6 rounded-xl shadow-md">
                <div class="max-w-lg mx-auto">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center justify-center">
                        <i class="fas fa-clipboard-check text-suriname-green mr-2"></i>
                        Controleer en Bevestig Uw Stem
                    </h2>
                    <p class="mb-6 text-gray-600">Controleer uw selecties voordat u uw stem uitbrengt. U kunt slechts één keer stemmen.</p>
                    
                    <div class="p-6 bg-gray-50 rounded-lg mb-6 shadow-inner">
                        <div class="mb-6 border-b pb-4">
                            <h3 class="font-medium text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-landmark text-suriname-green mr-2"></i>
                                Nationale Assemblée:
                            </h3>
                            <div id="dna-selection" class="text-suriname-green font-medium p-3 bg-white rounded-md border border-gray-200">
                                <span class="flex items-center justify-center">
                                    <i class="fas fa-user-times mr-2 text-gray-400"></i>
                                    Geen kandidaat geselecteerd
                                </span>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-city text-suriname-green mr-2"></i>
                                Ressortsraad:
                            </h3>
                            <div id="rr-selection" class="text-suriname-green font-medium p-3 bg-white rounded-md border border-gray-200">
                                <span class="flex items-center justify-center">
                                    <i class="fas fa-user-times mr-2 text-gray-400"></i>
                                    Geen kandidaat geselecteerd
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <button id="submitBtn" onclick="submitVote()" class="submit-button flex items-center justify-center mx-auto" disabled>
                        <i class="fas fa-vote-yea mr-2"></i>
                        Breng Uw Stem Uit
                    </button>
                    
                    <p class="mt-4 text-xs text-gray-500">
                        Zorg ervoor dat u minimaal één kandidaat heeft geselecteerd om te kunnen stemmen.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <?php include '../../include/footer.php'; ?>

    <!-- Confirmation Modal -->
    <div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 animate-fade-in-up">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-ballot-check text-suriname-green mr-2"></i>
                    Bevestig Uw Stem
                </h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg mb-6 border border-gray-200">
                <p class="text-sm text-gray-600 mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-suriname-green"></i>
                    U staat op het punt om te stemmen op:
                </p>
                
                <div id="modal-selections" class="space-y-4"></div>
            </div>
            
            <div class="bg-yellow-50 p-4 rounded-lg mb-6 border-l-4 border-yellow-400">
                <p class="text-sm text-yellow-700 flex items-start">
                    <i class="fas fa-exclamation-triangle mr-2 mt-0.5 text-yellow-500"></i>
                    <span>
                        <strong>Belangrijke informatie:</strong><br>
                        Deze actie kan niet ongedaan worden gemaakt. U kunt slechts één keer stemmen tijdens deze verkiezing. 
                        Zorg ervoor dat u tevreden bent met uw keuze voordat u bevestigt.
                    </span>
                </p>
            </div>
            
            <div class="flex space-x-3">
                <button onclick="closeModal()" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 flex items-center justify-center">
                    <i class="fas fa-times mr-2"></i>
                    Annuleren
                </button>
                <button onclick="confirmVote()" class="flex-1 px-4 py-2 bg-suriname-green text-white rounded-lg font-medium hover:bg-suriname-dark-green flex items-center justify-center">
                    <i class="fas fa-check mr-2"></i>
                    Bevestig Stem
                </button>
            </div>
        </div>
    </div>

    <script>
        // Selected candidates
        let selectedDNAId = null;
        let selectedRRId = null;
        let selectedDNAName = '';
        let selectedRRName = '';
        let selectedDNAParty = '';
        let selectedRRParty = '';
        let selectedDNADistrict = '';
        let selectedRRDistrict = '';
        let selectedRRResort = '';
        
        // Apply initial filters - show candidates from voter's district by default
        document.addEventListener('DOMContentLoaded', function() {
            // Add tooltip for card selection
            const cards = document.querySelectorAll('.candidate-card');
            cards.forEach(card => {
                card.setAttribute('title', 'Klik om deze kandidaat te selecteren');
            });
            
            // Initialize filters
            document.getElementById('dna-party-filter').addEventListener('change', filterDNACandidates);
            document.getElementById('dna-district-filter').addEventListener('change', filterDNACandidates);
            document.getElementById('rr-party-filter').addEventListener('change', filterRRCandidates);
            document.getElementById('rr-district-filter').addEventListener('change', function() {
                filterResortsByDistrict();
                filterRRCandidates();
            });
            
            // Apply initial filtering
            filterDNACandidates();
            filterRRCandidates(); // This will automatically filter by voter's resort
        });
        
        // Select candidate function
        function selectCandidate(card, type) {
            // Add visual feedback - pulse animation
            card.classList.add('animate-pulse');
            setTimeout(() => {
                card.classList.remove('animate-pulse');
            }, 300);
            
            // Remove selection from all cards of the same type
            const cards = document.querySelectorAll(`.${type}-card`);
            cards.forEach(c => {
                c.classList.remove('selected');
                c.querySelector('.candidate-check i').classList.add('hidden');
            });
            
            // Add selection to clicked card
            card.classList.add('selected');
            card.querySelector('.candidate-check i').classList.remove('hidden');
            
            // Store selected candidate details
            const candidateId = card.getAttribute('data-candidate-id');
            const candidateName = card.querySelector('h3').textContent;
            const candidateParty = card.querySelector('.text-sm.font-medium').textContent;
            const candidateDistrict = card.querySelector('.text-sm.text-gray-500').textContent;
            const candidateResortElement = card.querySelector('.text-xs.text-suriname-green');
            const candidateResort = candidateResortElement ? candidateResortElement.textContent.trim() : '';
            
            if (type === 'dna') {
                selectedDNAId = candidateId;
                selectedDNAName = candidateName;
                selectedDNAParty = candidateParty;
                selectedDNADistrict = candidateDistrict;
                
                // Update selection display with more info
                document.getElementById('dna-selection').innerHTML = `
                    <div class="flex flex-col sm:flex-row items-center sm:items-start">
                        <img src="${card.querySelector('img').src}" class="w-16 h-16 rounded-full border-2 border-suriname-green mb-2 sm:mb-0 sm:mr-3" alt="${candidateName}">
                        <div>
                            <div class="font-bold text-gray-800">${candidateName}</div>
                            <div class="text-sm text-suriname-green">${candidateParty}</div>
                            <div class="text-xs text-gray-500">${candidateDistrict}</div>
                        </div>
                    </div>
                `;
            } else {
                selectedRRId = candidateId;
                selectedRRName = candidateName;
                selectedRRParty = candidateParty;
                selectedRRDistrict = candidateDistrict;
                selectedRRResort = candidateResort;
                
                // Update selection display with more info
                document.getElementById('rr-selection').innerHTML = `
                    <div class="flex flex-col sm:flex-row items-center sm:items-start">
                        <img src="${card.querySelector('img').src}" class="w-16 h-16 rounded-full border-2 border-suriname-green mb-2 sm:mb-0 sm:mr-3" alt="${candidateName}">
                        <div>
                            <div class="font-bold text-gray-800">${candidateName}</div>
                            <div class="text-sm text-suriname-green">${candidateParty}</div>
                            <div class="text-xs text-gray-500">${candidateDistrict}</div>
                            ${candidateResort ? `<div class="text-xs text-suriname-green">${candidateResort}</div>` : ''}
                        </div>
                    </div>
                `;
            }
            
            // Scroll to review section if both selections are made
            if (selectedDNAId && selectedRRId) {
                const reviewSection = document.querySelector('.text-center.mb-12');
                setTimeout(() => {
                    reviewSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
            
            // Enable submit button if at least one candidate is selected
            updateSubmitButton();
        }
        
        // Update submit button state
        function updateSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            if (selectedDNAId || selectedRRId) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                submitBtn.classList.add('animate-pulse');
                setTimeout(() => {
                    submitBtn.classList.remove('animate-pulse');
                }, 500);
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }
        
        // Filter DNA candidates
        function filterDNACandidates() {
            const partyFilter = document.getElementById('dna-party-filter').value;
            const districtFilter = document.getElementById('dna-district-filter').value;
            
            document.querySelectorAll('.dna-card').forEach(card => {
                const partyId = card.getAttribute('data-party');
                const districtId = card.getAttribute('data-district');
                
                const matchesParty = !partyFilter || partyId === partyFilter;
                const matchesDistrict = !districtFilter || districtId === districtFilter;
                
                card.style.display = (matchesParty && matchesDistrict) ? '' : 'none';
            });
        }
        
        // Filter RR candidates
        function filterRRCandidates() {
            const partyFilter = document.getElementById('rr-party-filter').value;
            const districtFilter = document.getElementById('rr-district-filter').value;
            const resortFilter = document.getElementById('rr-resort-filter').value;
            const voterResortId = '<?= $voterResortId ?>';
            
            document.querySelectorAll('.rr-card').forEach(card => {
                const partyId = card.getAttribute('data-party');
                const districtId = card.getAttribute('data-district');
                const resortId = card.getAttribute('data-resort');
                
                const matchesParty = !partyFilter || partyId === partyFilter;
                const matchesDistrict = !districtFilter || districtId === districtFilter;
                const matchesResort = !resortFilter || resortId === resortFilter;
                
                // Only show RR candidates from the voter's resort
                const matchesVoterResort = resortId === voterResortId;
                
                card.style.display = (matchesParty && matchesDistrict && matchesResort && matchesVoterResort) ? '' : 'none';
            });
        }
        
        // Filter resorts based on selected district
        function filterResortsByDistrict() {
            const districtFilter = document.getElementById('rr-district-filter').value;
            const resortDropdown = document.getElementById('rr-resort-filter');
            
            // Show/hide the appropriate optgroups and options
            if (districtFilter) {
                // Hide all optgroups first
                Array.from(resortDropdown.querySelectorAll('optgroup')).forEach(optgroup => {
                    if (optgroup.getAttribute('data-district') === districtFilter) {
                        optgroup.style.display = '';
                    } else {
                        optgroup.style.display = 'none';
                    }
                });
                
                // Reset resort selection
                resortDropdown.value = '';
                
                // Add visual indicator that filtering is active
                resortDropdown.classList.add('border-suriname-green');
            } else {
                // Show all optgroups if no district is selected
                Array.from(resortDropdown.querySelectorAll('optgroup')).forEach(optgroup => {
                    optgroup.style.display = '';
                });
                
                // Remove visual indicator
                resortDropdown.classList.remove('border-suriname-green');
            }
            
            // Trigger the candidate filter
            filterRRCandidates();
        }
        
        // Reset filters
        function resetFilters(type) {
            if (type === 'dna' || type === 'all') {
                document.getElementById('dna-party-filter').value = '';
                document.getElementById('dna-district-filter').value = '';
                filterDNACandidates();
            }
            
            if (type === 'rr' || type === 'all') {
                document.getElementById('rr-party-filter').value = '';
                document.getElementById('rr-district-filter').value = '';
                document.getElementById('rr-resort-filter').value = '';
                
                // Reset resort dropdown
                const resortDropdown = document.getElementById('rr-resort-filter');
                resortDropdown.classList.remove('border-suriname-green');
                
                // Show all optgroups again
                Array.from(resortDropdown.querySelectorAll('optgroup')).forEach(optgroup => {
                    optgroup.style.display = '';
                });
                
                filterRRCandidates();
            }
        }
        
        // Submit vote (open confirmation modal)
        function submitVote() {
            const modalSelections = document.getElementById('modal-selections');
            modalSelections.innerHTML = '';
            
            // Add DNA selection to modal
            if (selectedDNAId) {
                const dnaCard = document.querySelector(`.dna-card[data-candidate-id="${selectedDNAId}"]`);
                const dnaPartyName = dnaCard.querySelector('.text-sm.font-medium').textContent;
                const dnaDistrictName = dnaCard.querySelector('.text-sm.text-gray-500').textContent;
                
                const dnaSelection = document.createElement('div');
                dnaSelection.className = 'p-3 bg-white rounded-lg border border-gray-200';
                dnaSelection.innerHTML = `
                    <h3 class="font-bold text-suriname-green">De Nationale Assemblée</h3>
                    <p class="font-semibold text-gray-800">${selectedDNAName}</p>
                    <p class="text-sm text-gray-600">${dnaPartyName} | ${dnaDistrictName}</p>
                `;
                modalSelections.appendChild(dnaSelection);
            }
            
            // Add RR selection to modal
            if (selectedRRId) {
                const rrCard = document.querySelector(`.rr-card[data-candidate-id="${selectedRRId}"]`);
                const rrPartyName = rrCard.querySelector('.text-sm.font-medium').textContent;
                const rrDistrictName = rrCard.querySelector('.text-sm.text-gray-500').textContent;
                const rrResortElement = rrCard.querySelector('.text-xs.text-suriname-green');
                const rrResortName = rrResortElement ? rrResortElement.textContent.trim() : '';
                
                const rrSelection = document.createElement('div');
                rrSelection.className = 'p-3 bg-white rounded-lg border border-gray-200';
                rrSelection.innerHTML = `
                    <h3 class="font-bold text-suriname-green">Resortsraden</h3>
                    <p class="font-semibold text-gray-800">${selectedRRName}</p>
                    <p class="text-sm text-gray-600">${rrPartyName} | ${rrDistrictName}</p>
                    ${rrResortName ? `<p class="text-xs text-suriname-green font-medium">${rrResortName}</p>` : ''}
                `;
                modalSelections.appendChild(rrSelection);
            }
            
            // Show modal
            document.getElementById('confirmation-modal').classList.remove('hidden');
        }
        
        // Close confirmation modal
        function closeModal() {
            document.getElementById('confirmation-modal').classList.add('hidden');
        }
        
        // Confirm and submit vote
        function confirmVote() {
            // Prepare vote data
            const voteData = {
                dna_candidate_id: selectedDNAId,
                rr_candidate_id: selectedRRId
            };
            
            // Show loading state on buttons
            const confirmBtn = document.querySelector('#confirmation-modal button:last-child');
            const cancelBtn = document.querySelector('#confirmation-modal button:first-child');
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            
            // Send vote data to server
            fetch('<?= BASE_URL ?>/vote/submit_vote.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(voteData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to thank you page
                    window.location.href = '<?= BASE_URL ?>/pages/voting/thank-you.php';
                } else {
                    // Show error message
                    alert(data.message || 'An error occurred while recording your vote. Please try again.');
                    
                    // Reset button states
                    confirmBtn.disabled = false;
                    cancelBtn.disabled = false;
                    confirmBtn.textContent = 'Confirm Vote';
                    
                    // Close modal
                    closeModal();
                }
            })
            .catch(error => {
                console.error('Error submitting vote:', error);
                alert('An error occurred while recording your vote. Please try again.');
                
                // Reset button states
                confirmBtn.disabled = false;
                cancelBtn.disabled = false;
                confirmBtn.textContent = 'Confirm Vote';
                
                // Close modal
                closeModal();
            });
        }
        
        // Update district filter when resort is selected
        function updateDistrictFromResort() {
            const resortFilter = document.getElementById('rr-resort-filter');
            const districtFilter = document.getElementById('rr-district-filter');
            
            if (resortFilter.value) {
                const selectedOption = resortFilter.options[resortFilter.selectedIndex];
                const districtId = selectedOption.getAttribute('data-district');
                
                if (districtId && districtFilter.value !== districtId) {
                    districtFilter.value = districtId;
                }
            }
            
            filterRRCandidates();
        }
        
        // Add event listeners for filters
        document.getElementById('dna-party-filter').addEventListener('change', filterDNACandidates);
        document.getElementById('dna-district-filter').addEventListener('change', filterDNACandidates);
        document.getElementById('rr-party-filter').addEventListener('change', filterRRCandidates);
        document.getElementById('rr-district-filter').addEventListener('change', function() {
            filterResortsByDistrict();
        });
        document.getElementById('rr-resort-filter').addEventListener('change', function() {
            updateDistrictFromResort();
        });
    </script>
</body>
</html>
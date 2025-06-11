<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/VoterAuth.php';

// Check if user is logged in - for showing personalized content
$is_logged_in = isset($_SESSION['voter_id']) || isset($_SESSION['VoterID']);
$voterId = null;
$has_voted = false;

if ($is_logged_in) {
    // Get voter ID from session
    $voterId = $_SESSION['voter_id'] ?? $_SESSION['VoterID'];
    
    // Initialize VoterAuth
    $voterAuth = new VoterAuth($pdo);
    
    // Check if user has voted
    $has_voted = $voterAuth->hasVoted($voterId);
}

function fetchElectionResults($pdo, $election_type) {
    // Fetch candidates and votes for the given election type (e.g., 'DNA' or 'RR')
    $sql = "
        SELECT c.CandidateID, c.Name, c.PartyID, p.PartyName, c.DistrictID, d.DistrictName, c.Photo,
               c.ResortID, r.name as ResortName,
               COUNT(v.VoteID) AS vote_count
        FROM candidates c
        LEFT JOIN parties p ON c.PartyID = p.PartyID
        LEFT JOIN districten d ON c.DistrictID = d.DistrictID
        LEFT JOIN resorts r ON c.ResortID = r.id
        LEFT JOIN votes v ON c.CandidateID = v.CandidateID
        WHERE c.CandidateType = ?
        GROUP BY c.CandidateID, c.Name, c.PartyID, p.PartyName, c.DistrictID, d.DistrictName, c.Photo, c.ResortID, r.name
        ORDER BY vote_count DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$election_type]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Always fetch election results regardless of login status
$dna_results = fetchElectionResults($pdo, 'DNA');
$resorts_results = fetchElectionResults($pdo, 'RR');

// Prepare data for charts
function prepareChartData($results) {
    $labels = [];
    $votes = [];
    foreach ($results as $candidate) {
        $labels[] = htmlspecialchars($candidate['Name']);
        $votes[] = (int)$candidate['vote_count'];
    }
    return ['labels' => $labels, 'votes' => $votes];
}

$dna_chart_data = prepareChartData($dna_results);
$resorts_chart_data = prepareChartData($resorts_results);

// Get election information
try {
    $stmt = $pdo->query("SELECT ElectionName, ElectionDate FROM elections WHERE Status = 'active' LIMIT 1");
    $election = $stmt->fetch(PDO::FETCH_ASSOC);
    $electionName = $election ? $election['ElectionName'] : 'Huidige Verkiezing';
    $electionDate = $election ? date('d F Y', strtotime($election['ElectionDate'])) : date('d F Y');
} catch (PDOException $e) {
    $electionName = 'Huidige Verkiezing';
    $electionDate = date('d F Y');
}

// Get user voted candidates
$userVotes = [];
if ($has_voted) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.CandidateID, c.Name, c.Photo, p.PartyName, d.DistrictName, c.CandidateType
            FROM votes v
            JOIN candidates c ON v.CandidateID = c.CandidateID
            JOIN parties p ON c.PartyID = p.PartyID
            JOIN districten d ON c.DistrictID = d.DistrictID
            WHERE v.UserID = ?
        ");
        $stmt->execute([$voterId]);
        $userVotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user votes: " . $e->getMessage());
    }
}

// Get all resorts for filtering
$resorts = [];
try {
    $stmt = $pdo->query("SELECT id, name, district_id FROM resorts ORDER BY name ASC");
    $resorts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching resorts: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Verkiezingsresultaten - E-Stem Suriname</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
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
        
        .progress-bar-container {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            background-color: #e5e7eb;
        }
        
        .progress-bar {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #007749 0%, #00995D 100%);
            transition: width 0.5s ease-in-out;
        }
        
        .candidate-card {
            transition: all 0.2s ease-in-out;
        }
        
        .candidate-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .border-suriname-green {
            border-color: #007749 !important;
            border-width: 2px !important;
        }
        
        /* New styles for enhanced features */
        .search-container {
            position: relative;
        }
        
        .search-container input {
            padding-left: 2.5rem;
            width: 100%;
        }
        
        .search-container .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        .view-toggle-btn {
            background-color: #f3f4f6;
            color: #6b7280;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .view-toggle-btn.active {
            background-color: #007749;
            color: white;
        }
        
        .tooltip {
            position: relative;
            display: inline-block;
            cursor: help;
        }
        
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.75rem;
        }
        
        .tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        .comparison-container {
            display: none;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .comparison-container.active {
            display: block;
        }
        
        .comparison-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
        }
        
        .comparison-placeholder {
            border: 2px dashed #e5e7eb;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            color: #9ca3af;
        }
        
        .table-view {
            display: none;
        }
        
        .table-view.active {
            display: block;
        }
        
        .card-view.hidden {
            display: none;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                background-color: white;
            }
            
            .container {
                max-width: 100% !important;
                width: 100% !important;
            }
        }
        
        /* Responsive improvements */
        @media (max-width: 640px) {
            .filter-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-container > div {
                margin-bottom: 0.75rem;
            }
            
            .action-buttons {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <?php include '../../include/nav.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8 max-w-6xl">
        <div class="bg-white p-6 rounded-xl shadow-md mb-8">
            <h1 class="text-3xl font-bold text-suriname-green mb-2"><?= htmlspecialchars($electionName) ?></h1>
            <p class="text-gray-600 mb-4">
                <i class="fas fa-calendar-alt mr-2"></i>
                <?= $electionDate ?>
            </p>

            <!-- User Vote Section (only shown if logged in and has voted) -->
            <?php if ($is_logged_in && $has_voted && !empty($userVotes)): ?>
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Uw Stem</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($userVotes as $vote): ?>
                        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 flex items-center space-x-4">
                            <img src="<?= !empty($vote['Photo']) ? htmlspecialchars($vote['Photo']) : BASE_URL . '/assets/images/placeholder-profile.jpg' ?>" 
                                 alt="<?= htmlspecialchars($vote['Name']) ?>" 
                                 class="w-16 h-16 rounded-full object-cover border-2 border-suriname-green">
                            <div>
                                <div class="text-suriname-green font-medium text-sm">
                                    <?= $vote['CandidateType'] === 'DNA' ? 'De Nationale Assemblée' : 'Resortsraad' ?>
                                </div>
                                <div class="font-semibold text-gray-800"><?= htmlspecialchars($vote['Name']) ?></div>
                                <div class="text-gray-600 text-sm"><?= htmlspecialchars($vote['PartyName']) ?></div>
                                <div class="text-gray-500 text-xs"><?= htmlspecialchars($vote['DistrictName']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php elseif ($is_logged_in && !$has_voted): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle mt-1"></i>
                    </div>
                    <div class="ml-3">
                        <p class="font-medium">Nog niet gestemd?</p>
                        <p>U bent ingelogd maar heeft nog niet gestemd. Uw stem is belangrijk!</p>
                        <p class="mt-2">
                            <a href="<?= BASE_URL ?>/pages/voting/index.php" class="text-suriname-green hover:underline">
                                <i class="fas fa-arrow-right mr-1"></i> Ga naar de stempagina
                            </a>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

                <!-- DNA Results Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">
                        <i class="fas fa-landmark mr-2 text-suriname-green"></i>
                        Resultaten De Nationale Assemblée
                    </h2>
                    
                    <!-- Comparison Container for DNA Candidates -->
                    <div id="dna-comparison-container" class="comparison-container mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-800">Vergelijk Kandidaten</h3>
                            <button id="close-dna-comparison" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div id="dna-comparison-1" class="comparison-placeholder">
                                <i class="fas fa-user-plus text-3xl mb-2"></i>
                                <p>Klik op "Vergelijk" bij een kandidaat om toe te voegen</p>
                            </div>
                            <div id="dna-comparison-2" class="comparison-placeholder">
                                <i class="fas fa-user-plus text-3xl mb-2"></i>
                                <p>Klik op "Vergelijk" bij een kandidaat om toe te voegen</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg mb-6 no-print">
                        <!-- Search and Filters Row -->
                        <div class="flex flex-wrap items-center gap-4 mb-4 filter-container">
                            <!-- Search Box -->
                            <div class="search-container flex-grow">
                                <label for="dna-search" class="block text-sm font-medium text-gray-700 mb-1">Zoek kandidaat:</label>
                                <div class="relative">
                                    <span class="search-icon"><i class="fas fa-search"></i></span>
                                    <input type="text" id="dna-search" placeholder="Zoek op naam..." 
                                           class="border border-gray-300 rounded-md p-2 text-sm w-full">
                                </div>
                            </div>
                            
                            <!-- Party Filter -->
                            <div>
                                <label for="dna-filter" class="block text-sm font-medium text-gray-700 mb-1">
                                    Filter op partij:
                                    <span class="tooltip ml-1">
                                        <i class="fas fa-info-circle text-gray-400"></i>
                                        <span class="tooltip-text">Selecteer een politieke partij om alleen kandidaten van die partij te tonen</span>
                                    </span>
                                </label>
                                <select id="dna-filter" class="border border-gray-300 rounded-md p-2 text-sm">
                                    <option value="all">Alle Politieke Partijen</option>
                                    <?php
                                    $dna_parties = array_unique(array_map(function($c) { return $c['PartyName']; }, $dna_results));
                                    foreach ($dna_parties as $party): ?>
                                        <option value="<?= htmlspecialchars($party) ?>"><?= htmlspecialchars($party) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- District Filter -->
                            <div>
                                <label for="dna-district-filter" class="block text-sm font-medium text-gray-700 mb-1">
                                    Filter op district:
                                    <span class="tooltip ml-1">
                                        <i class="fas fa-info-circle text-gray-400"></i>
                                        <span class="tooltip-text">Selecteer een district om alleen kandidaten uit dat district te tonen</span>
                                    </span>
                                </label>
                                <select id="dna-district-filter" class="border border-gray-300 rounded-md p-2 text-sm">
                                    <option value="all">Alle Districten</option>
                                    <?php
                                    $dna_districts = array_unique(array_map(function($c) { return $c['DistrictName']; }, $dna_results));
                                    foreach ($dna_districts as $district): ?>
                                        <option value="<?= htmlspecialchars($district) ?>"><?= htmlspecialchars($district) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Action Buttons Row -->
                        <div class="flex flex-wrap items-center justify-between gap-2 action-buttons">
                            <!-- View Toggle -->
                            <div class="flex space-x-2">
                                <button id="dna-chart-view" class="view-toggle-btn active">
                                    <i class="fas fa-chart-bar mr-1"></i> Grafiek
                                </button>
                                <button id="dna-table-view" class="view-toggle-btn">
                                    <i class="fas fa-table mr-1"></i> Tabel
                                </button>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex space-x-2">
                                <button id="dna-compare-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                    <i class="fas fa-balance-scale mr-1"></i> Vergelijk
                                </button>
                                
                                <button id="dna-share-btn" class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                    <i class="fas fa-share-alt mr-1"></i> Delen
                                </button>
                                
                                <button id="dna-print-btn" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                    <i class="fas fa-print mr-1"></i> Afdrukken
                                </button>
                                
                                <button id="dna-download-btn" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                    <i class="fas fa-download mr-1"></i> PDF
                                </button>
                                
                                <button id="reset-dna-filters" class="text-suriname-green hover:text-suriname-dark-green underline focus:outline-none">
                                    <i class="fas fa-redo-alt mr-1"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chart View -->
                    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
                        <canvas id="dnaChart" height="300"></canvas>
                    </div>
                    
                    <!-- Table View -->
                    <div id="dna-table-container" class="table-view bg-white p-4 rounded-lg shadow-md mb-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaat</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider no-print">Acties</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="dna-table-body">
                                    <?php foreach ($dna_results as $candidate): ?>
                                        <tr class="hover:bg-gray-50 dna-candidate-row"
                                            data-party="<?= htmlspecialchars($candidate['PartyName']) ?>"
                                            data-district="<?= htmlspecialchars($candidate['DistrictName']) ?>"
                                            data-name="<?= htmlspecialchars($candidate['Name']) ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img class="h-10 w-10 rounded-full object-cover" 
                                                             src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : BASE_URL . '/assets/images/placeholder-profile.jpg' ?>" 
                                                             alt="<?= htmlspecialchars($candidate['Name']) ?>">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($candidate['Name']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-suriname-green"><?= htmlspecialchars($candidate['PartyName']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($candidate['DistrictName']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium"><?= $candidate['vote_count'] ?> stemmen</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium no-print">
                                                <button class="text-blue-600 hover:text-blue-900 mr-3 compare-dna-btn" 
                                                        data-id="<?= $candidate['CandidateID'] ?>"
                                                        data-name="<?= htmlspecialchars($candidate['Name']) ?>"
                                                        data-party="<?= htmlspecialchars($candidate['PartyName']) ?>"
                                                        data-district="<?= htmlspecialchars($candidate['DistrictName']) ?>"
                                                        data-votes="<?= $candidate['vote_count'] ?>"
                                                        data-photo="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : BASE_URL . '/assets/images/placeholder-profile.jpg' ?>">
                                                    <i class="fas fa-balance-scale"></i> Vergelijk
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Card View -->
                    <div id="dna-card-container" class="card-view">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="dna-candidates-grid">
                            <?php foreach ($dna_results as $index => $candidate): 
                                $initiallyHidden = $index >= 6 ? 'initially-hidden hidden' : '';
                            ?>
                                <div class="candidate-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 <?= $initiallyHidden ?>"
                                     data-party="<?= htmlspecialchars($candidate['PartyName']) ?>"
                                     data-district="<?= htmlspecialchars($candidate['DistrictName']) ?>"
                                     data-name="<?= htmlspecialchars($candidate['Name']) ?>">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <img src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : BASE_URL . '/assets/images/placeholder-profile.jpg' ?>" 
                                             alt="<?= htmlspecialchars($candidate['Name']) ?>" 
                                             class="w-12 h-12 rounded-full object-cover">
                                        <div>
                                            <div class="font-semibold text-gray-800"><?= htmlspecialchars($candidate['Name']) ?></div>
                                            <div class="text-suriname-green text-sm"><?= htmlspecialchars($candidate['PartyName']) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-xs text-gray-500 mb-2"><?= htmlspecialchars($candidate['DistrictName']) ?></div>
                                    
                                    <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: <?= $candidate['vote_count'] > 0 ? min(100, ($candidate['vote_count'] / max(array_column($dna_results, 'vote_count'))) * 100) : 0 ?>%;"></div>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <div class="text-sm font-medium"><?= $candidate['vote_count'] ?> stemmen</div>
                                        <button class="text-blue-600 hover:text-blue-900 text-xs compare-dna-btn no-print"
                                                data-id="<?= $candidate['CandidateID'] ?>"
                                                data-name="<?= htmlspecialchars($candidate['Name']) ?>"
                                                data-party="<?= htmlspecialchars($candidate['PartyName']) ?>"
                                                data-district="<?= htmlspecialchars($candidate['DistrictName']) ?>"
                                                data-votes="<?= $candidate['vote_count'] ?>"
                                                data-photo="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : BASE_URL . '/assets/images/placeholder-profile.jpg' ?>">
                                            <i class="fas fa-balance-scale mr-1"></i> Vergelijk
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Show More Button -->
                        <?php if (count($dna_results) > 6): ?>
                        <div class="text-center mt-6 no-print">
                            <button id="dna-show-more" class="bg-suriname-green hover:bg-suriname-dark-green text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-plus-circle mr-1"></i> Toon Meer Kandidaten
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RR Results Section -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">
                        <i class="fas fa-building mr-2 text-suriname-green"></i>
                        Resultaten Resortsraden
                    </h2>
                    
                    <!-- Comparison Container for RR Candidates -->
                    <div id="rr-comparison-container" class="comparison-container mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-800">Vergelijk Kandidaten</h3>
                            <button id="close-rr-comparison" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div id="rr-comparison-1" class="comparison-placeholder">
                                <i class="fas fa-user-plus text-3xl mb-2"></i>
                                <p>Klik op "Vergelijk" bij een kandidaat om toe te voegen</p>
                            </div>
                            <div id="rr-comparison-2" class="comparison-placeholder">
                                <i class="fas fa-user-plus text-3xl mb-2"></i>
                                <p>Klik op "Vergelijk" bij een kandidaat om toe te voegen</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg mb-6 no-print">
                        <!-- Search and Filters Row -->
                        <div class="flex flex-wrap items-center gap-4 mb-4 filter-container">
                            <!-- Search Box -->
                            <div class="search-container flex-grow">
                                <label for="rr-search" class="block text-sm font-medium text-gray-700 mb-1">Zoek kandidaat:</label>
                                <div class="relative">
                                    <span class="search-icon"><i class="fas fa-search"></i></span>
                                    <input type="text" id="rr-search" placeholder="Zoek op naam..." 
                                           class="border border-gray-300 rounded-md p-2 text-sm w-full">
                                </div>
                            </div>
                            
                            <!-- Party Filter -->
                            <div>
                                <label for="rr-filter" class="block text-sm font-medium text-gray-700 mb-1">
                                    Filter op partij:
                                    <span class="tooltip ml-1">
                                        <i class="fas fa-info-circle text-gray-400"></i>
                                        <span class="tooltip-text">Selecteer een politieke partij om alleen kandidaten van die partij te tonen</span>
                                    </span>
                                </label>
                                <select id="rr-filter" class="border border-gray-300 rounded-md p-2 text-sm">
                                    <option value="all">Alle Politieke Partijen</option>
                                    <?php
                                    $rr_parties = array_unique(array_map(function($c) { return $c['PartyName']; }, $resorts_results));
                                    foreach ($rr_parties as $party): ?>
                                        <option value="<?= htmlspecialchars($party) ?>"><?= htmlspecialchars($party) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- District Filter -->
                            <div>
                                <label for="rr-district-filter" class="block text-sm font-medium text-gray-700 mb-1">
                                    Filter op district:
                                    <span class="tooltip ml-1">
                                        <i class="fas fa-info-circle text-gray-400"></i>
                                        <span class="tooltip-text">Selecteer een district om alleen kandidaten uit dat district te tonen</span>
                                    </span>
                                </label>
                                <select id="rr-district-filter" class="border border-gray-300 rounded-md p-2 text-sm">
                                    <option value="all">Alle Districten</option>
                                    <?php
                                    $rr_districts = array_unique(array_map(function($c) { return $c['DistrictName']; }, $resorts_results));
                                    foreach ($rr_districts as $district): ?>
                                        <option value="<?= htmlspecialchars($district) ?>"><?= htmlspecialchars($district) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Resort Filter -->
                            <div>
                                <label for="rr-resort-filter" class="block text-sm font-medium text-gray-700 mb-1">
                                    Filter op resort:
                                    <span class="tooltip ml-1">
                                        <i class="fas fa-info-circle text-gray-400"></i>
                                        <span class="tooltip-text">Selecteer een resort om alleen kandidaten uit dat resort te tonen</span>
                                    </span>
                                </label>
                                <select id="rr-resort-filter" class="border border-gray-300 rounded-md p-2 text-sm">
                                    <option value="all">Alle Resorts</option>
                                    <?php
                                    // Group resorts by district
                                    $resortsByDistrict = [];
                                    foreach ($resorts as $resort) {
                                        if (!isset($resortsByDistrict[$resort['district_id']])) {
                                            $resortsByDistrict[$resort['district_id']] = [];
                                        }
                                        $resortsByDistrict[$resort['district_id']][] = $resort;
                                    }
                                    
                                    // Display resorts grouped by district
                                    foreach ($districts = array_unique(array_map(function($c) { return ['DistrictID' => $c['DistrictID'], 'DistrictName' => $c['DistrictName']]; }, $resorts_results), SORT_REGULAR) as $district): 
                                        if (isset($resortsByDistrict[$district['DistrictID']])): ?>
                                            <optgroup label="<?= htmlspecialchars($district['DistrictName']) ?>" data-district="<?= htmlspecialchars($district['DistrictName']) ?>">
                                                <?php foreach ($resortsByDistrict[$district['DistrictID']] as $resort): ?>
                                                    <option value="<?= htmlspecialchars($resort['name']) ?>" data-district="<?= htmlspecialchars($district['DistrictName']) ?>">
                                                        <?= htmlspecialchars($resort['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif;
                                    endforeach; ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1 italic">
                                    <i class="fas fa-info-circle mr-1"></i> Filter op district voor relevante resorten
                                </p>
                            </div>
                        </div>
                        
                        <!-- Action Buttons Row -->
                        <div class="flex flex-wrap items-center justify-between gap-2 action-buttons">
                            <!-- View Toggle -->
                            <div class="flex space-x-2">
                                <button id="rr-chart-view" class="view-toggle-btn active">
                                    <i class="fas fa-chart-bar mr-1"></i> Grafiek
                                </button>
                                <button id="rr-table-view" class="view-toggle-btn">
                                    <i class="fas fa-table mr-1"></i> Tabel
                                </button>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex space-x-2">
                                <button id="rr-compare-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                    <i class="fas fa-balance-scale mr-1"></i> Vergelijk
                                </button>
                                
                                <button id="rr-share-btn" class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                    <i class="fas fa-share-alt mr-1"></i> Delen
                                </button>
                                
                                <button id="rr-print-btn" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                    <i class="fas fa-print mr-1"></i> Afdrukken
                                </button>
                                
                                <button id="rr-download-btn" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors">
                                    <i class="fas fa-download mr-1"></i> PDF
                                </button>
                                
                                <button id="reset-rr-filters" class="text-suriname-green hover:text-suriname-dark-green underline focus:outline-none">
                                    <i class="fas fa-redo-alt mr-1"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chart View -->
                    <div class="bg-white p-4 rounded-lg shadow-md mb-6">
                        <canvas id="rrChart" height="300"></canvas>
                    </div>
                    
                    <!-- Table View -->
                    <div id="rr-table-container" class="table-view bg-white p-4 rounded-lg shadow-md mb-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kandidaat</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resort</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider no-print">Acties</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="rr-table-body">
                                    <?php foreach ($resorts_results as $candidate): ?>
                                        <tr class="hover:bg-gray-50 rr-candidate-row"
                                            data-party="<?= htmlspecialchars($candidate['PartyName']) ?>"
                                            data-district="<?= htmlspecialchars($candidate['DistrictName']) ?>"
                                            data-resort="<?= htmlspecialchars($candidate['ResortName'] ?? 'Onbekend') ?>"
                                            data-name="<?= htmlspecialchars($candidate['Name']) ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <img class="h-10 w-10 rounded-full object-cover" 
                                                             src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : BASE_URL . '/assets/images/placeholder-profile.jpg' ?>" 
                                                             alt="<?= htmlspecialchars($candidate['Name']) ?>">
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($candidate['Name']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-suriname-green"><?= htmlspecialchars($candidate['PartyName']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($candidate['DistrictName']) ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-500"><?= htmlspecialchars($candidate['ResortName'] ?? 'Onbekend') ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium"><?= $candidate['vote_count'] ?> stemmen</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium no-print">
                                                <button class="text-blue-600 hover:text-blue-900 mr-3 compare-rr-btn" 
                                                        data-id="<?= $candidate['CandidateID'] ?>"
                                                        data-name="<?= htmlspecialchars($candidate['Name']) ?>"
                                                        data-party="<?= htmlspecialchars($candidate['PartyName']) ?>"
                                                        data-district="<?= htmlspecialchars($candidate['DistrictName']) ?>"
                                                        data-resort="<?= htmlspecialchars($candidate['ResortName'] ?? 'Onbekend') ?>"
                                                        data-votes="<?= $candidate['vote_count'] ?>"
                                                        data-photo="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : BASE_URL . '/assets/images/placeholder-profile.jpg' ?>">
                                                    <i class="fas fa-balance-scale"></i> Vergelijk
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Card View -->
                    <div id="rr-card-container" class="card-view">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="rr-candidates-grid">
                            <?php foreach ($resorts_results as $index => $candidate): 
                                $initiallyHidden = $index >= 6 ? 'initially-hidden hidden' : '';
                            ?>
                                <div class="candidate-card bg-white p-4 rounded-lg shadow-sm border border-gray-200 <?= $initiallyHidden ?>"
                                     data-party="<?= htmlspecialchars($candidate['PartyName']) ?>"
                                     data-district="<?= htmlspecialchars($candidate['DistrictName']) ?>"
                                     data-resort="<?= htmlspecialchars($candidate['ResortName'] ?? 'Onbekend') ?>"
                                     data-name="<?= htmlspecialchars($candidate['Name']) ?>">
                                    <div class="flex items-center space-x-3 mb-3">
                                        <img src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : BASE_URL . '/assets/images/placeholder-profile.jpg' ?>" 
                                             alt="<?= htmlspecialchars($candidate['Name']) ?>" 
                                             class="w-12 h-12 rounded-full object-cover">
                                        <div>
                                            <div class="font-semibold text-gray-800"><?= htmlspecialchars($candidate['Name']) ?></div>
                                            <div class="text-suriname-green text-sm"><?= htmlspecialchars($candidate['PartyName']) ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-between text-xs text-gray-500 mb-2">
                                        <div><?= htmlspecialchars($candidate['DistrictName']) ?></div>
                                        <?php if (!empty($candidate['ResortName'])): ?>
                                        <div class="ml-2 font-medium text-suriname-green">
                                            <i class="fas fa-map-marker-alt mr-1"></i><?= htmlspecialchars($candidate['ResortName']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: <?= $candidate['vote_count'] > 0 ? min(100, ($candidate['vote_count'] / max(array_column($resorts_results, 'vote_count'))) * 100) : 0 ?>%;"></div>
                                    </div>
                                    <div class="flex justify-between items-center mt-2">
                                        <div class="text-sm font-medium"><?= $candidate['vote_count'] ?> stemmen</div>
                                        <button class="text-blue-600 hover:text-blue-900 text-xs compare-rr-btn no-print"
                                                data-id="<?= $candidate['CandidateID'] ?>"
                                                data-name="<?= htmlspecialchars($candidate['Name']) ?>"
                                                data-party="<?= htmlspecialchars($candidate['PartyName']) ?>"
                                                data-district="<?= htmlspecialchars($candidate['DistrictName']) ?>"
                                                data-resort="<?= htmlspecialchars($candidate['ResortName'] ?? 'Onbekend') ?>"
                                                data-votes="<?= $candidate['vote_count'] ?>"
                                                data-photo="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : BASE_URL . '/assets/images/placeholder-profile.jpg' ?>">
                                            <i class="fas fa-balance-scale mr-1"></i> Vergelijk
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Show More Button -->
                        <?php if (count($resorts_results) > 6): ?>
                        <div class="text-center mt-6 no-print">
                            <button id="rr-show-more" class="bg-suriname-green hover:bg-suriname-dark-green text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-plus-circle mr-1"></i> Toon Meer Kandidaten
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
        </div>
    </main>

    <?php include '../../include/footer.php'; ?>

    <script>
        // Chart configuration
        const dnaChartConfig = {
            type: 'bar',
            data: {
                labels: <?= json_encode($dna_chart_data['labels']) ?>,
                datasets: [{
                    label: 'Aantal stemmen',
                    data: <?= json_encode($dna_chart_data['votes']) ?>,
                    backgroundColor: '#007749',
                    borderColor: '#006241',
                    borderWidth: 1,
                    borderRadius: 4,
                    maxBarThickness: 35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#333',
                        titleFont: {
                            family: 'Poppins'
                        },
                        bodyFont: {
                            family: 'Poppins'
                        },
                        callbacks: {
                            label: function(context) {
                                return context.formattedValue + ' stemmen';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Aantal stemmen',
                            font: {
                                family: 'Poppins',
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    }
                }
            }
        };
        
        const rrChartConfig = {
            type: 'bar',
            data: {
                labels: <?= json_encode($resorts_chart_data['labels']) ?>,
                datasets: [{
                    label: 'Aantal stemmen',
                    data: <?= json_encode($resorts_chart_data['votes']) ?>,
                    backgroundColor: '#007749',
                    borderColor: '#006241',
                    borderWidth: 1,
                    borderRadius: 4,
                    maxBarThickness: 35
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#333',
                        titleFont: {
                            family: 'Poppins'
                        },
                        bodyFont: {
                            family: 'Poppins'
                        },
                        callbacks: {
                            label: function(context) {
                                return context.formattedValue + ' stemmen';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Aantal stemmen',
                            font: {
                                family: 'Poppins',
                                weight: 'bold'
                            }
                        },
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Poppins'
                            }
                        }
                    }
                }
            }
        };
        
        // Initialize charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const dnaCtx = document.getElementById('dnaChart').getContext('2d');
            const rrCtx = document.getElementById('rrChart').getContext('2d');
            const dnaChart = new Chart(dnaCtx, dnaChartConfig);
            const rrChart = new Chart(rrCtx, rrChartConfig);
            
            // Initialize view state
            let dnaViewMode = 'chart'; // 'chart' or 'table'
            let rrViewMode = 'chart'; // 'chart' or 'table'
            let dnaCompareList = [];
            let rrCompareList = [];
            
            // Filter functions
            function filterCandidates(type, partyFilter, districtFilter, resortFilter = 'all', searchTerm = '') {
                const candidateCards = document.querySelectorAll(`.candidate-card[data-party]`);
                const searchTermLower = searchTerm.toLowerCase();
                
                candidateCards.forEach(card => {
                    // Only process cards of the current type (DNA or RR)
                    const isRR = type === 'rr';
                    const hasResort = card.hasAttribute('data-resort');
                    
                    // Skip cards that don't match the type
                    if ((isRR && !hasResort) || (!isRR && hasResort)) return;
                    
                    const cardParty = card.getAttribute('data-party');
                    const cardDistrict = card.getAttribute('data-district');
                    const cardResort = card.getAttribute('data-resort');
                    const candidateName = card.querySelector('.candidate-name').textContent.toLowerCase();
                    
                    const matchesParty = partyFilter === 'all' || cardParty === partyFilter;
                    const matchesDistrict = districtFilter === 'all' || cardDistrict === districtFilter;
                    const matchesResort = resortFilter === 'all' || (isRR && cardResort === resortFilter);
                    const matchesSearch = searchTermLower === '' || candidateName.includes(searchTermLower);
                    
                    // For DNA candidates, we don't check resort
                    const shouldShow = matchesParty && matchesDistrict && (isRR ? matchesResort : true) && matchesSearch;
                    card.style.display = shouldShow ? 'block' : 'none';
                });
                
                // Update chart (if needed)
                if (type === 'dna') {
                    updateDNAChart(partyFilter, districtFilter, searchTermLower);
                } else {
                    updateRRChart(partyFilter, districtFilter, resortFilter, searchTermLower);
                }
            }
            
            // Search functions
            function searchDNACandidates() {
                const searchTerm = document.getElementById('dna-search').value;
                filterCandidates('dna', 
                    document.getElementById('dna-filter').value,
                    document.getElementById('dna-district-filter').value,
                    'all',
                    searchTerm
                );
            }
            
            function searchRRCandidates() {
                const searchTerm = document.getElementById('rr-search').value;
                filterCandidates('rr', 
                    document.getElementById('rr-filter').value,
                    document.getElementById('rr-district-filter').value,
                    document.getElementById('rr-resort-filter').value,
                    searchTerm
                );
            }
            
            // Filter resorts based on selected district
            function filterResortsByDistrict() {
                const districtFilter = document.getElementById('rr-district-filter').value;
                const resortDropdown = document.getElementById('rr-resort-filter');
                
                // Show all optgroups and options initially
                Array.from(resortDropdown.querySelectorAll('optgroup')).forEach(optgroup => {
                    optgroup.style.display = '';
                });
                
                // If a district is selected, only show the corresponding optgroup
                if (districtFilter !== 'all') {
                    Array.from(resortDropdown.querySelectorAll('optgroup')).forEach(optgroup => {
                        if (optgroup.getAttribute('data-district') !== districtFilter) {
                            optgroup.style.display = 'none';
                        }
                    });
                    
                    // Reset resort filter when district changes
                    resortDropdown.value = 'all';
                    
                    // Add visual indicator that filtering is active
                    resortDropdown.classList.add('border-suriname-green');
                } else {
                    // Remove visual indicator
                    resortDropdown.classList.remove('border-suriname-green');
                }
                
                // Trigger the candidate filter
                filterCandidates('rr', 
                    document.getElementById('rr-filter').value,
                    districtFilter,
                    document.getElementById('rr-resort-filter').value
                );
            }
            
            // Update district filter when resort is selected
            function updateDistrictFromResort() {
                const resortFilter = document.getElementById('rr-resort-filter');
                const districtFilter = document.getElementById('rr-district-filter');
                
                if (resortFilter.value !== 'all') {
                    const selectedOption = resortFilter.options[resortFilter.selectedIndex];
                    const districtName = selectedOption.getAttribute('data-district');
                    
                    if (districtName && districtFilter.value !== districtName) {
                        districtFilter.value = districtName;
                    }
                }
                
                filterCandidates('rr', 
                    document.getElementById('rr-filter').value,
                    districtFilter.value,
                    resortFilter.value
                );
            }
            
            function updateDNAChart(partyFilter, districtFilter, searchTerm = '') {
                const filteredData = <?= json_encode($dna_results) ?>.filter(c => {
                    return (partyFilter === 'all' || c.PartyName === partyFilter) &&
                           (districtFilter === 'all' || c.DistrictName === districtFilter) &&
                           (searchTerm === '' || c.Name.toLowerCase().includes(searchTerm));
                });
                
                dnaChart.data.labels = filteredData.map(c => c.Name);
                dnaChart.data.datasets[0].data = filteredData.map(c => c.vote_count);
                dnaChart.update();
            }
            
            function updateRRChart(partyFilter, districtFilter, resortFilter, searchTerm = '') {
                const filteredData = <?= json_encode($resorts_results) ?>.filter(c => {
                    return (partyFilter === 'all' || c.PartyName === partyFilter) &&
                           (districtFilter === 'all' || c.DistrictName === districtFilter) &&
                           (resortFilter === 'all' || (c.ResortName ?? 'Onbekend') === resortFilter) &&
                           (searchTerm === '' || c.Name.toLowerCase().includes(searchTerm));
                });
                
                rrChart.data.labels = filteredData.map(c => c.Name);
                rrChart.data.datasets[0].data = filteredData.map(c => c.vote_count);
                rrChart.update();
            }
            
            // View toggle functions
            function toggleDNAView(mode) {
                dnaViewMode = mode;
                const chartView = document.getElementById('dna-chart-view');
                const tableView = document.getElementById('dna-table-view');
                const chartBtn = document.getElementById('dna-chart-btn');
                const tableBtn = document.getElementById('dna-table-btn');
                
                if (mode === 'chart') {
                    chartView.classList.remove('hidden');
                    tableView.classList.add('hidden');
                    chartBtn.classList.add('bg-suriname-green', 'text-white');
                    chartBtn.classList.remove('bg-gray-200', 'text-gray-700');
                    tableBtn.classList.add('bg-gray-200', 'text-gray-700');
                    tableBtn.classList.remove('bg-suriname-green', 'text-white');
                } else {
                    chartView.classList.add('hidden');
                    tableView.classList.remove('hidden');
                    chartBtn.classList.remove('bg-suriname-green', 'text-white');
                    chartBtn.classList.add('bg-gray-200', 'text-gray-700');
                    tableBtn.classList.remove('bg-gray-200', 'text-gray-700');
                    tableBtn.classList.add('bg-suriname-green', 'text-white');
                }
            }
            
            function toggleRRView(mode) {
                rrViewMode = mode;
                const chartView = document.getElementById('rr-chart-view');
                const tableView = document.getElementById('rr-table-view');
                const chartBtn = document.getElementById('rr-chart-btn');
                const tableBtn = document.getElementById('rr-table-btn');
                
                if (mode === 'chart') {
                    chartView.classList.remove('hidden');
                    tableView.classList.add('hidden');
                    chartBtn.classList.add('bg-suriname-green', 'text-white');
                    chartBtn.classList.remove('bg-gray-200', 'text-gray-700');
                    tableBtn.classList.add('bg-gray-200', 'text-gray-700');
                    tableBtn.classList.remove('bg-suriname-green', 'text-white');
                } else {
                    chartView.classList.add('hidden');
                    tableView.classList.remove('hidden');
                    chartBtn.classList.remove('bg-suriname-green', 'text-white');
                    chartBtn.classList.add('bg-gray-200', 'text-gray-700');
                    tableBtn.classList.remove('bg-gray-200', 'text-gray-700');
                    tableBtn.classList.add('bg-suriname-green', 'text-white');
                }
            }
            
            // Comparison functions
            function toggleDNACompare(candidateId, candidateName) {
                const index = dnaCompareList.findIndex(c => c.id === candidateId);
                const compareContainer = document.getElementById('dna-compare-container');
                
                if (index === -1) {
                    // Add to comparison list if not already there and if less than 3 candidates
                    if (dnaCompareList.length < 3) {
                        dnaCompareList.push({id: candidateId, name: candidateName});
                    } else {
                        alert('Je kunt maximaal 3 kandidaten vergelijken.');
                        return;
                    }
                } else {
                    // Remove from comparison list
                    dnaCompareList.splice(index, 1);
                }
                
                // Update comparison container
                updateDNACompareContainer();
                
                // Show/hide comparison container based on whether there are candidates to compare
                if (dnaCompareList.length > 0) {
                    compareContainer.classList.remove('hidden');
                } else {
                    compareContainer.classList.add('hidden');
                }
            }
            
            function toggleRRCompare(candidateId, candidateName) {
                const index = rrCompareList.findIndex(c => c.id === candidateId);
                const compareContainer = document.getElementById('rr-compare-container');
                
                if (index === -1) {
                    // Add to comparison list if not already there and if less than 3 candidates
                    if (rrCompareList.length < 3) {
                        rrCompareList.push({id: candidateId, name: candidateName});
                    } else {
                        alert('Je kunt maximaal 3 kandidaten vergelijken.');
                        return;
                    }
                } else {
                    // Remove from comparison list
                    rrCompareList.splice(index, 1);
                }
                
                // Update comparison container
                updateRRCompareContainer();
                
                // Show/hide comparison container based on whether there are candidates to compare
                if (rrCompareList.length > 0) {
                    compareContainer.classList.remove('hidden');
                } else {
                    compareContainer.classList.add('hidden');
                }
            }
            
            function updateDNACompareContainer() {
                const container = document.getElementById('dna-compare-list');
                container.innerHTML = '';
                
                dnaCompareList.forEach(candidate => {
                    const item = document.createElement('div');
                    item.className = 'flex items-center justify-between bg-white p-2 rounded shadow mb-2';
                    item.innerHTML = `
                        <span>${candidate.name}</span>
                        <button class="text-red-500 hover:text-red-700" onclick="toggleDNACompare('${candidate.id}', '${candidate.name}')">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    container.appendChild(item);
                });
            }
            
            function updateRRCompareContainer() {
                const container = document.getElementById('rr-compare-list');
                container.innerHTML = '';
                
                rrCompareList.forEach(candidate => {
                    const item = document.createElement('div');
                    item.className = 'flex items-center justify-between bg-white p-2 rounded shadow mb-2';
                    item.innerHTML = `
                        <span>${candidate.name}</span>
                        <button class="text-red-500 hover:text-red-700" onclick="toggleRRCompare('${candidate.id}', '${candidate.name}')">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    container.appendChild(item);
                });
            }
            
            // Share, Print and PDF functions
            function shareResults(type) {
                const url = window.location.href;
                const title = `Verkiezingsresultaten ${type.toUpperCase()}`;
                
                if (navigator.share) {
                    navigator.share({
                        title: title,
                        url: url
                    }).catch(err => {
                        console.error('Error sharing:', err);
                    });
                } else {
                    // Fallback for browsers that don't support Web Share API
                    const tempInput = document.createElement('input');
                    document.body.appendChild(tempInput);
                    tempInput.value = url;
                    tempInput.select();
                    document.execCommand('copy');
                    document.body.removeChild(tempInput);
                    alert('Link gekopieerd naar klembord!');
                }
            }
            
            function printResults(type) {
                const printSection = type === 'dna' ? document.getElementById('dna-results') : document.getElementById('rr-results');
                const originalContents = document.body.innerHTML;
                const printContents = printSection.innerHTML;
                
                document.body.innerHTML = `
                    <div class="p-8">
                        <h1 class="text-2xl font-bold mb-4">Verkiezingsresultaten ${type.toUpperCase()}</h1>
                        ${printContents}
                    </div>
                `;
                
                window.print();
                document.body.innerHTML = originalContents;
                
                // Reinitialize charts and event listeners
                location.reload();
            }
            
            function downloadPDF(type) {
                const element = type === 'dna' ? document.getElementById('dna-results') : document.getElementById('rr-results');
                const filename = `verkiezingsresultaten_${type}.pdf`;
                
                // Create a clone of the element to modify for PDF
                const clone = element.cloneNode(true);
                const tempDiv = document.createElement('div');
                tempDiv.appendChild(clone);
                
                // Add title
                const title = document.createElement('h1');
                title.textContent = `Verkiezingsresultaten ${type.toUpperCase()}`;
                title.className = 'text-2xl font-bold mb-4';
                tempDiv.insertBefore(title, tempDiv.firstChild);
                
                // Apply some styling for PDF
                tempDiv.style.padding = '20px';
                tempDiv.style.backgroundColor = 'white';
                
                // Use html2canvas to capture the element as an image
                html2canvas(tempDiv, {
                    scale: 1,
                    useCORS: true,
                    logging: false
                }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = pdf.internal.pageSize.getHeight();
                    const imgWidth = canvas.width;
                    const imgHeight = canvas.height;
                    const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
                    const imgX = (pdfWidth - imgWidth * ratio) / 2;
                    const imgY = 30;
                    
                    pdf.addImage(imgData, 'PNG', imgX, imgY, imgWidth * ratio, imgHeight * ratio);
                    pdf.save(filename);
                });
            }
            
            // Event listeners for filters
            document.getElementById('dna-filter').addEventListener('change', function() {
                filterCandidates('dna', this.value, document.getElementById('dna-district-filter').value, 'all', document.getElementById('dna-search').value);
            });
            
            document.getElementById('dna-district-filter').addEventListener('change', function() {
                filterCandidates('dna', document.getElementById('dna-filter').value, this.value, 'all', document.getElementById('dna-search').value);
            });
            
            document.getElementById('rr-filter').addEventListener('change', function() {
                filterCandidates('rr', this.value, 
                                 document.getElementById('rr-district-filter').value,
                                 document.getElementById('rr-resort-filter').value,
                                 document.getElementById('rr-search').value);
            });
            
            document.getElementById('rr-district-filter').addEventListener('change', function() {
                filterResortsByDistrict();
            });
            
            document.getElementById('rr-resort-filter').addEventListener('change', function() {
                updateDistrictFromResort();
            });
            
            // Event listeners for search
            document.getElementById('dna-search').addEventListener('input', searchDNACandidates);
            document.getElementById('rr-search').addEventListener('input', searchRRCandidates);
            
            // Event listeners for view toggle
            document.getElementById('dna-chart-btn').addEventListener('click', function() {
                toggleDNAView('chart');
            });
            
            document.getElementById('dna-table-btn').addEventListener('click', function() {
                toggleDNAView('table');
            });
            
            document.getElementById('rr-chart-btn').addEventListener('click', function() {
                toggleRRView('chart');
            });
            
            document.getElementById('rr-table-btn').addEventListener('click', function() {
                toggleRRView('table');
            });
            
            // Event listeners for action buttons
            document.getElementById('dna-share-btn').addEventListener('click', function() {
                shareResults('dna');
            });
            
            document.getElementById('dna-print-btn').addEventListener('click', function() {
                printResults('dna');
            });
            
            document.getElementById('dna-pdf-btn').addEventListener('click', function() {
                downloadPDF('dna');
            });
            
            document.getElementById('rr-share-btn').addEventListener('click', function() {
                shareResults('rr');
            });
            
            document.getElementById('rr-print-btn').addEventListener('click', function() {
                printResults('rr');
            });
            
            document.getElementById('rr-pdf-btn').addEventListener('click', function() {
                downloadPDF('rr');
            });
            
            // Reset filters
            document.getElementById('reset-rr-filters').addEventListener('click', function() {
                document.getElementById('rr-filter').value = 'all';
                document.getElementById('rr-district-filter').value = 'all';
                document.getElementById('rr-resort-filter').value = 'all';
                
                // Reset resort dropdown
                const resortDropdown = document.getElementById('rr-resort-filter');
                resortDropdown.classList.remove('border-suriname-green');
                
                // Show all optgroups again
                Array.from(resortDropdown.querySelectorAll('optgroup')).forEach(optgroup => {
                    optgroup.style.display = '';
                });
                
                filterCandidates('rr', 'all', 'all', 'all');
            });
            
            // Reset DNA filters
            document.getElementById('reset-dna-filters').addEventListener('click', function() {
                document.getElementById('dna-filter').value = 'all';
                document.getElementById('dna-district-filter').value = 'all';
                filterCandidates('dna', 'all', 'all');
            });
            
            // Apply initial filters
            filterCandidates('dna', 'all', 'all');
            filterCandidates('rr', 'all', 'all', 'all');
            
            // Initialize views
            toggleDNAView('chart');
            toggleRRView('chart');
            
            // Initialize comparison containers
            document.getElementById('dna-compare-container').classList.add('hidden');
            document.getElementById('rr-compare-container').classList.add('hidden');
        });
    </script>
</body>
</html>
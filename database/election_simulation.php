<?php
// Include configuration and database connection
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';

// Turn off foreign key checks to enable clean operation
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

echo "Starting election simulation setup...\n";

// Clean existing election data
$tables_to_clean = ['elections', 'candidates', 'votes', 'voting_sessions', 'vouchers'];
foreach ($tables_to_clean as $table) {
    try {
        $pdo->exec("TRUNCATE TABLE `$table`");
        echo "Table $table truncated successfully.\n";
    } catch (PDOException $e) {
        echo "Error truncating table $table: " . $e->getMessage() . "\n";
    }
}

// Create a new active election
$startDate = date('Y-m-d H:i:s', strtotime('-1 day'));
$endDate = date('Y-m-d H:i:s', strtotime('+7 days'));
$electionDate = date('Y-m-d');

$election_stmt = $pdo->prepare("
    INSERT INTO elections (ElectionName, ElectionDate, Status, StartDate, EndDate, ShowResults)
    VALUES ('Verkiezingen Suriname 2025', ?, 'active', ?, ?, 1)
");
$election_stmt->execute([$electionDate, $startDate, $endDate]);
$election_id = $pdo->lastInsertId();
echo "Created active election with ID: $election_id\n";

// Fetch all political parties
$parties_query = $pdo->query("SELECT PartyID, PartyName FROM parties");
$parties = $parties_query->fetchAll(PDO::FETCH_ASSOC);

if (count($parties) === 0) {
    // Create political parties if none exist
    $parties_data = [
        ['Nationale Democratische Partij', 'NDP - Nationale Democratische Partij'],
        ['Vooruitstrevende Hervormings Partij', 'VHP - Vooruitstrevende Hervormings Partij'],
        ['Pertjajah Luhur', 'PL - Pertjajah Luhur'],
        ['Nationale Partij Suriname', 'NPS - Nationale Partij Suriname'],
        ['Algemene Bevrijdings- en Ontwikkelingspartij', 'ABOP - Algemene Bevrijdings- en Ontwikkelingspartij'],
        ['Broederschap en Eenheid in Politiek', 'BEP - Broederschap en Eenheid in Politiek'],
        ['Democratie en Ontwikkeling in Eenheid', 'DOE - Democratie en Ontwikkeling in Eenheid']
    ];

    foreach ($parties_data as $party) {
        $party_stmt = $pdo->prepare("
            INSERT INTO parties (PartyName, Description)
            VALUES (?, ?)
        ");
        $party_stmt->execute($party);
        $parties[] = [
            'PartyID' => $pdo->lastInsertId(),
            'PartyName' => $party[0]
        ];
        echo "Created party: {$party[0]}\n";
    }
}

// Fetch all districten
$districten_query = $pdo->query("SELECT DistrictID, DistrictName FROM districten");
$districten = $districten_query->fetchAll(PDO::FETCH_ASSOC);

// Process each district
foreach ($districten as $district) {
    echo "Processing district: {$district['DistrictName']} (ID: {$district['DistrictID']})\n";
    
    // Get all resorts for this district
    $resorts_query = $pdo->prepare("SELECT id, name FROM resorts WHERE district_id = ?");
    $resorts_query->execute([$district['DistrictID']]);
    $resorts = $resorts_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Create candidates for DNA (national level)
    echo "  Creating DNA candidates for district {$district['DistrictName']}...\n";
    foreach ($parties as $party) {
        // Create 5 candidates per party per district for DNA (increased from 3)
        for ($i = 1; $i <= 5; $i++) {
            $surinamese_names = [
                'Santokhi', 'Brunswijk', 'Bouterse', 'Ramdin', 'Somohardjo', 
                'Matadin', 'Amafo', 'Abrahams', 'Adhin', 'Ajodhia',
                'Akkal', 'Alibux', 'Aloema', 'Aron', 'Asabina',
                'Ashwin', 'Breeveld', 'Cairo', 'Castelen', 'Cederburg',
                'Chandrikapersad', 'Cheuk', 'Daal', 'Defares', 'Dewanchand',
                'Dijksteel', 'Djaismink', 'Doerga', 'Doest', 'Dongor'
            ];
            
            $first_names = [
                'Chan', 'Ronnie', 'Desi', 'Albert', 'Paul', 
                'Jennifer', 'Ashwin', 'Ricardo', 'Ameerali', 'Jules',
                'Rabin', 'Errol', 'Henk', 'Gregory', 'Celsius',
                'Dewanand', 'Ruth', 'Ivan', 'Kenneth', 'Stephanie'
            ];
            
            $random_first_name = $first_names[array_rand($first_names)];
            $random_last_name = $surinamese_names[array_rand($surinamese_names)];
            
            $full_name = $random_first_name . ' ' . $random_last_name;
            
            $candidate_stmt = $pdo->prepare("
                INSERT INTO candidates (Name, PartyID, DistrictID, ResortID, CandidateType, ElectionID)
                VALUES (?, ?, ?, NULL, 'DNA', ?)
            ");
            $candidate_stmt->execute([
                $full_name, 
                $party['PartyID'],
                $district['DistrictID'],
                $election_id
            ]);
            
            echo "    Created DNA candidate: $full_name for {$party['PartyName']}\n";
        }
    }
    
    // Create candidates for RR (resort level)
    if (!empty($resorts)) {
        echo "  Creating RR candidates for resorts in {$district['DistrictName']}...\n";
        
        foreach ($resorts as $resort) {
            echo "    Processing resort: {$resort['name']} (ID: {$resort['id']})\n";
            
            foreach ($parties as $party) {
                // Create 3 candidates per party per resort for RR (increased from 2)
                for ($i = 1; $i <= 3; $i++) {
                    $surinamese_names = [
                        'Santokhi', 'Brunswijk', 'Bouterse', 'Ramdin', 'Somohardjo', 
                        'Matadin', 'Amafo', 'Abrahams', 'Adhin', 'Ajodhia',
                        'Akkal', 'Alibux', 'Aloema', 'Aron', 'Asabina',
                        'Ashwin', 'Breeveld', 'Cairo', 'Castelen', 'Cederburg'
                    ];
                    
                    $first_names = [
                        'Chan', 'Ronnie', 'Desi', 'Albert', 'Paul', 
                        'Jennifer', 'Ashwin', 'Ricardo', 'Ameerali', 'Jules',
                        'Rabin', 'Errol', 'Henk', 'Gregory', 'Celsius',
                        'Dewanand', 'Ruth', 'Ivan', 'Kenneth', 'Stephanie'
                    ];
                    
                    $random_first_name = $first_names[array_rand($first_names)];
                    $random_last_name = $surinamese_names[array_rand($surinamese_names)];
                    
                    $full_name = $random_first_name . ' ' . $random_last_name;
                    
                    $candidate_stmt = $pdo->prepare("
                        INSERT INTO candidates (Name, PartyID, DistrictID, ResortID, CandidateType, ElectionID)
                        VALUES (?, ?, ?, ?, 'RR', ?)
                    ");
                    $candidate_stmt->execute([
                        $full_name, 
                        $party['PartyID'],
                        $district['DistrictID'],
                        $resort['id'],
                        $election_id
                    ]);
                    
                    echo "      Created RR candidate: $full_name for {$party['PartyName']} in resort {$resort['name']}\n";
                }
            }
        }
    } else {
        echo "  No resorts found for district {$district['DistrictName']}\n";
    }
}

// Create test voters (10 per resort, increased from 5)
echo "Creating test voters...\n";

$voter_first_names = [
    'Johan', 'Maria', 'Rakesh', 'Anand', 'Shanti', 'Wong', 'Li', 'Marlon', 'Jennifer', 'Antonio',
    'Rishie', 'Sunita', 'Randjiet', 'James', 'Xiomara', 'Hassan', 'Kevin', 'Sharon', 'Mohammed', 'Sandra'
];

$voter_last_names = [
    'Abauna', 'Jankie', 'Ramdin', 'Karijoleksono', 'Biharie', 'Pansa', 'Malone', 'Soekra', 'Sital', 'Vrede',
    'Sewgolam', 'Madarie', 'Bhola', 'Vermeer', 'Soekhnandan', 'Amir', 'Wolff', 'Ramesar', 'Kotzebue', 'Jogi'
];

foreach ($districten as $district) {
    // Get resorts for this district
    $resorts_query = $pdo->prepare("SELECT id, name FROM resorts WHERE district_id = ?");
    $resorts_query->execute([$district['DistrictID']]);
    $resorts_data = $resorts_query->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($resorts_data)) {
        foreach ($resorts_data as $resort) {
            // Create 10 voters per resort (increased from 5)
            for ($i = 1; $i <= 10; $i++) {
                // Generate a unique voter code
                $voter_code = 'V' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                
                $first_name = $voter_first_names[array_rand($voter_first_names)];
                $last_name = $voter_last_names[array_rand($voter_last_names)];
                $id_number = 'SR' . str_pad(mt_rand(1, 999999) . $i . $resort['id'], 8, '0', STR_PAD_LEFT);
                
                // Create a password and hash it
                $password = substr(md5(uniqid(mt_rand(), true)), 0, 8);
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $voter_stmt = $pdo->prepare("
                    INSERT INTO voters (
                        first_name, last_name, id_number, voter_code, password,
                        status, district_id, resort_id
                    )
                    VALUES (?, ?, ?, ?, ?, 'active', ?, ?)
                ");
                
                $voter_stmt->execute([
                    $first_name, 
                    $last_name, 
                    $id_number, 
                    $voter_code, 
                    $password_hash,
                    $district['DistrictID'], 
                    $resort['id']
                ]);
                
                $voter_id = $pdo->lastInsertId();
                
                // Create a voucher for this voter
                $voucher_id = 'VC' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                
                $voucher_stmt = $pdo->prepare("
                    INSERT INTO vouchers (voter_id, voucher_id, password, used)
                    VALUES (?, ?, ?, 0)
                ");
                
                $voucher_stmt->execute([
                    $voter_id,
                    $voucher_id,
                    $password_hash
                ]);
                
                echo "  Created voter: $first_name $last_name (ID: $voter_id, District: {$district['DistrictName']}, Resort: {$resort['name']})\n";
                echo "    Credentials: Voter Code: $voter_code, Password: $password, Voucher: $voucher_id\n";
                
                // Simulate some voting - increased to 80% chance of having voted
                if (mt_rand(1, 10) > 2) { // 80% chance of having voted
                    // Get candidates for this district and resort
                    $candidates_query = $pdo->prepare("
                        SELECT CandidateID FROM candidates 
                        WHERE ElectionID = ? AND DistrictID = ? AND 
                              (ResortID = ? OR (ResortID IS NULL AND CandidateType = 'DNA'))
                    ");
                    $candidates_query->execute([$election_id, $district['DistrictID'], $resort['id']]);
                    $candidates = $candidates_query->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($candidates)) {
                        // Choose random candidate to vote for
                        $candidate = $candidates[array_rand($candidates)];
                        
                        // Create voting session
                        $session_stmt = $pdo->prepare("
                            INSERT INTO voting_sessions (UserID, StartTime, EndTime, Status)
                            VALUES (?, NOW(), NOW(), 'completed')
                        ");
                        $session_stmt->execute([$voter_id]);
                        
                        // Record the vote
                        $vote_stmt = $pdo->prepare("
                            INSERT INTO votes (UserID, CandidateID, ElectionID, TimeStamp)
                            VALUES (?, ?, ?, NOW())
                        ");
                        $vote_stmt->execute([$voter_id, $candidate, $election_id]);
                        
                        echo "    Voter has cast a vote for candidate ID: $candidate\n";
                    }
                }
            }
        }
    } else {
        echo "  No resorts found for district {$district['DistrictName']}, skipping voter creation\n";
    }
}

// Turn foreign key checks back on
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "\nElection simulation setup completed successfully!\n";
echo "You now have an active election with candidates for all districten and resorts.\n";
echo "Test voters have been created with credentials printed above.\n";
echo "The election will be active for the next 7 days.\n";

// Data summary
echo "\n=== DATA SUMMARY ===\n";
$candidate_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE ElectionID = ?");
$candidate_count_stmt->execute([$election_id]);
$candidate_count = $candidate_count_stmt->fetchColumn();

$dna_candidate_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE ElectionID = ? AND CandidateType = 'DNA'");
$dna_candidate_count_stmt->execute([$election_id]);
$dna_candidate_count = $dna_candidate_count_stmt->fetchColumn();

$rr_candidate_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE ElectionID = ? AND CandidateType = 'RR'");
$rr_candidate_count_stmt->execute([$election_id]);
$rr_candidate_count = $rr_candidate_count_stmt->fetchColumn();

$voter_count_stmt = $pdo->query("SELECT COUNT(*) FROM voters");
$voter_count = $voter_count_stmt->fetchColumn();

$vote_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE ElectionID = ?");
$vote_count_stmt->execute([$election_id]);
$vote_count = $vote_count_stmt->fetchColumn();

echo "Total candidates: $candidate_count\n";
echo "- DNA candidates: $dna_candidate_count\n";
echo "- RR candidates: $rr_candidate_count\n";
echo "Total voters: $voter_count\n";
echo "Total votes: $vote_count\n";
?> 
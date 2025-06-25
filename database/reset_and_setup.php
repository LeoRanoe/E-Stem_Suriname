<?php
// Include configuration and database connection
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';

// Turn off foreign key checks to enable clean deletion
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

echo "Starting database cleanup...\n";

// Get all tables in the database
$tables_query = $pdo->query("SHOW TABLES");
$tables = $tables_query->fetchAll(PDO::FETCH_COLUMN);

// Truncate all tables except sessions
foreach ($tables as $table) {
    // Skip the sessions table to maintain current sessions
    if ($table !== 'sessions') {
        try {
            $pdo->exec("TRUNCATE TABLE `$table`");
            echo "Table $table truncated successfully.\n";
        } catch (PDOException $e) {
            echo "Error truncating table $table: " . $e->getMessage() . "\n";
        }
    }
}

// Turn foreign key checks back on
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "Database cleaned successfully.\n";

// Create a new active election
echo "Creating new active election...\n";

// Create election
$election_stmt = $pdo->prepare("
    INSERT INTO elections (ElectionName, ElectionDate, Status)
    VALUES ('Staatsverkiezing Suriname 2025', '2025-05-25', 'active')
");
$election_stmt->execute();
$election_id = $pdo->lastInsertId();
echo "Created election with ID: $election_id\n";

// Create districts
$districts = [
    'Paramaribo', 'Wanica', 'Nickerie', 'Coronie', 'Saramacca', 
    'Commewijne', 'Marowijne', 'Para', 'Brokopondo', 'Sipaliwini'
];

foreach ($districts as $district) {
    $district_stmt = $pdo->prepare("
        INSERT INTO districts (DistrictName, Country)
        VALUES (?, 'Suriname')
    ");
    $district_stmt->execute([$district]);
    $district_id = $pdo->lastInsertId();
    echo "Created district: $district (ID: $district_id)\n";
    
    // Create resorts for this district
    // For simplicity, we'll create 3 resorts per district
    for ($i = 1; $i <= 3; $i++) {
        $resort_name = "$district Resort $i";
        $resort_stmt = $pdo->prepare("
            INSERT INTO resorts (ResortName, DistrictID)
            VALUES (?, ?)
        ");
        $resort_stmt->execute([$resort_name, $district_id]);
        $resort_id = $pdo->lastInsertId();
        echo "  Created resort: $resort_name (ID: $resort_id)\n";
    }
}

// Create political parties
$parties = [
    'NPS' => 'Nationale Partij Suriname',
    'VHP' => 'Vooruitstrevende Hervormings Partij',
    'NDP' => 'Nationale Democratische Partij',
    'ABOP' => 'Algemene Bevrijdings- en Ontwikkelingspartij',
    'PL' => 'Pertjajah Luhur',
    'BEP' => 'Broederschap en Eenheid in Politiek'
];

foreach ($parties as $acronym => $name) {
    $party_stmt = $pdo->prepare("
        INSERT INTO parties (PartyName, PartyAcronym, Description)
        VALUES (?, ?, 'Politieke partij in Suriname')
    ");
    $party_stmt->execute([$name, $acronym]);
    $party_id = $pdo->lastInsertId();
    echo "Created party: $name ($acronym) with ID: $party_id\n";
}

// Create candidates for DNA
// Get all parties
$parties_query = $pdo->query("SELECT PartyID, PartyName, PartyAcronym FROM parties");
$parties_data = $parties_query->fetchAll(PDO::FETCH_ASSOC);

// Get all districts
$districts_query = $pdo->query("SELECT DistrictID, DistrictName FROM districts");
$districts_data = $districts_query->fetchAll(PDO::FETCH_ASSOC);

echo "Creating DNA candidates...\n";
foreach ($parties_data as $party) {
    foreach ($districts_data as $district) {
        // Create 5 candidates per party per district
        for ($i = 1; $i <= 5; $i++) {
            $first_name = "Kandidaat";
            $last_name = $party['PartyAcronym'] . " " . $district['DistrictName'] . " $i";
            
            $candidate_stmt = $pdo->prepare("
                INSERT INTO candidates (
                    FirstName, LastName, Gender, DateOfBirth, Address, 
                    PhoneNumber, Email, DistrictID, PartyID, Position, ElectionID
                )
                VALUES (
                    ?, ?, ?, '1980-01-01', 'Adres in " . $district['DistrictName'] . "',
                    '597" . rand(1000000, 9999999) . "', ?, ?, ?, 'DNA Kandidaat', ?
                )
            ");
            
            $email = strtolower(str_replace(' ', '.', $last_name)) . '@example.com';
            $gender = ($i % 2 == 0) ? 'M' : 'F';
            
            $candidate_stmt->execute([
                $first_name, 
                $last_name, 
                $gender, 
                $email, 
                $district['DistrictID'], 
                $party['PartyID'], 
                $election_id
            ]);
            
            $candidate_id = $pdo->lastInsertId();
            echo "  Created candidate: $first_name $last_name (ID: $candidate_id)\n";
        }
    }
}

// Create sample voters (10 per district)
echo "Creating sample voters...\n";
foreach ($districts_data as $district) {
    // Get resorts for this district
    $resorts_query = $pdo->prepare("SELECT ResortID, ResortName FROM resorts WHERE DistrictID = ?");
    $resorts_query->execute([$district['DistrictID']]);
    $resorts_data = $resorts_query->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($resorts_data as $resort) {
        for ($i = 1; $i <= 10; $i++) {
            // Generate a unique voucher ID
            $voucher_id = strtoupper(substr($district['DistrictName'], 0, 3)) . '-' . 
                           strtoupper(substr($resort['ResortName'], 0, 3)) . '-' . 
                           str_pad($i, 4, '0', STR_PAD_LEFT);
            
            // Create a QR code containing the voter credentials
            $password = substr(md5(uniqid(mt_rand(), true)), 0, 8);
            $qr_data = json_encode([
                'voter_id' => $voucher_id,
                'password' => $password
            ]);
            
            $voter_stmt = $pdo->prepare("
                INSERT INTO voters (
                    FirstName, LastName, IdNumber, Gender, DateOfBirth, 
                    DistrictID, ResortID, VoucherID, Password, QRCode, Status, ElectionID
                )
                VALUES (
                    ?, ?, ?, ?, '1980-01-01',
                    ?, ?, ?, ?, ?, 'active', ?
                )
            ");
            
            $first_name = "Kiezer";
            $last_name = $district['DistrictName'] . " $i";
            $id_number = "ID" . str_pad($i, 6, '0', STR_PAD_LEFT);
            $gender = ($i % 2 == 0) ? 'M' : 'F';
            
            $voter_stmt->execute([
                $first_name, 
                $last_name, 
                $id_number, 
                $gender, 
                $district['DistrictID'], 
                $resort['ResortID'], 
                $voucher_id, 
                password_hash($password, PASSWORD_DEFAULT), 
                $qr_data, 
                $election_id
            ]);
            
            $voter_id = $pdo->lastInsertId();
            echo "  Created voter: $first_name $last_name (ID: $voter_id, Voucher: $voucher_id, Password: $password)\n";
        }
    }
}

// Create admin user
$admin_stmt = $pdo->prepare("
    INSERT INTO administrators (
        Username, Password, FirstName, LastName, Email, Role, Status
    )
    VALUES (
        'admin', ?, 'Admin', 'User', 'admin@estem.sr', 'super_admin', 'active'
    )
");
$admin_stmt->execute([password_hash('admin123', PASSWORD_DEFAULT)]);
$admin_id = $pdo->lastInsertId();
echo "Created admin user (ID: $admin_id, Username: admin, Password: admin123)\n";

echo "Database setup completed successfully.\n";

// Export the database as a backup
echo "Creating database dump...\n";

// Build the mysqldump command
$dump_file = __DIR__ . "/db_dump_" . date('Y-m-d_H-i-s') . ".sql";
$command = "mysqldump -h " . escapeshellarg(DB_HOST) . " -u " . escapeshellarg(DB_USER);
if (DB_PASS) {
    $command .= " -p" . escapeshellarg(DB_PASS);
}
$command .= " " . escapeshellarg(DB_NAME) . " > " . escapeshellarg($dump_file);

// Execute the command
$output = [];
$return_var = 0;
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "Database dump created successfully at: $dump_file\n";
} else {
    echo "Error creating database dump. Return code: $return_var\n";
    echo "Please run this command manually:\n";
    echo "mysqldump -h " . DB_HOST . " -u " . DB_USER . " " . DB_NAME . " > " . $dump_file . "\n";
}

echo "Process completed successfully.\n";
?> 
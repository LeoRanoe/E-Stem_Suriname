<?php
session_start();
require_once '../include/db_connect.php';
require_once '../include/auth.php';
require_once '../include/config.php';

// Check if user is logged in and is an admin
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        $file = $_FILES['csv_file'];
        
        // Check for errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Er is een fout opgetreden bij het uploaden van het bestand.');
        }
        
        // Check file type
        $mimeType = mime_content_type($file['tmp_name']);
        if ($mimeType !== 'text/csv' && $mimeType !== 'text/plain') {
            throw new Exception('Het bestand moet een CSV bestand zijn.');
        }
        
        // Open the file
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle === false) {
            throw new Exception('Kon het bestand niet openen.');
        }
        
        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            throw new Exception('Kon de header rij niet lezen.');
        }
        
        // Verify required columns
        $requiredColumns = ['Voornaam', 'Achternaam', 'Email', 'IDNumber', 'DistrictID'];
        $missingColumns = array_diff($requiredColumns, $header);
        if (!empty($missingColumns)) {
            throw new Exception('Ontbrekende kolommen: ' . implode(', ', $missingColumns));
        }
        
        // Get column indexes
        $columns = array_flip($header);
        
        // Start transaction
        $pdo->beginTransaction();
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $rowNumber = 1;
        
        // Prepare insert statement
        $stmt = $pdo->prepare("
            INSERT INTO users (Voornaam, Achternaam, Email, IDNumber, DistrictID, Role, Password, Status)
            VALUES (:voornaam, :achternaam, :email, :idnumber, :districtid, 'voter', :password, 'active')
        ");
        
        // Read and process each row
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            try {
                // Validate row data
                if (count($row) !== count($header)) {
                    throw new Exception("Ongeldige aantal kolommen op rij $rowNumber");
                }
                
                // Extract data
                $data = [
                    'voornaam' => trim($row[$columns['Voornaam']]),
                    'achternaam' => trim($row[$columns['Achternaam']]),
                    'email' => trim($row[$columns['Email']]),
                    'idnumber' => trim($row[$columns['IDNumber']]),
                    'districtid' => trim($row[$columns['DistrictID']]),
                    'password' => password_hash(trim($row[$columns['IDNumber']]), PASSWORD_DEFAULT) // Use IDNumber as initial password
                ];
                
                // Validate data
                if (empty($data['voornaam']) || empty($data['achternaam']) || empty($data['email']) || 
                    empty($data['idnumber']) || empty($data['districtid'])) {
                    throw new Exception("Ontbrekende verplichte velden op rij $rowNumber");
                }
                
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Ongeldig email adres op rij $rowNumber");
                }
                
                // Insert user
                if ($stmt->execute($data)) {
                    $successCount++;
                } else {
                    throw new Exception("Database fout op rij $rowNumber");
                }
                
            } catch (Exception $e) {
                $errorCount++;
                $errors[] = $e->getMessage();
            }
        }
        
        // Commit transaction if there were any successful inserts
        if ($successCount > 0) {
            $pdo->commit();
            $_SESSION['success'] = "$successCount gebruikers succesvol geïmporteerd.";
        } else {
            $pdo->rollBack();
        }
        
        // Report errors
        if ($errorCount > 0) {
            $_SESSION['error'] = "$errorCount rijen konden niet worden geïmporteerd.";
            $_SESSION['import_errors'] = $errors;
        }
        
        fclose($handle);
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Import fout: " . $e->getMessage();
    }
}

// Redirect back to qrcodes.php
header("Location: qrcodes.php");
exit; 
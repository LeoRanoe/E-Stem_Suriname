<?php

require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/admin_auth.php';
require_once __DIR__ . '/../../include/config.php';

class ImportController {

    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function handleImportRequest() {
        session_start();
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
                    fclose($handle); // Close file handle
                    throw new Exception('Kon de header rij niet lezen.');
                }
                
                // Verify required columns
                $requiredColumns = ['Voornaam', 'Achternaam', 'Email', 'IDNumber', 'DistrictID'];
                $missingColumns = array_diff($requiredColumns, $header);
                if (!empty($missingColumns)) {
                     fclose($handle); // Close file handle
                    throw new Exception('Ontbrekende kolommen: ' . implode(', ', $missingColumns));
                }
                
                // Get column indexes
                $columns = array_flip($header);
                
                // Start transaction
                $this->pdo->beginTransaction();
                
                $successCount = 0;
                $errorCount = 0;
                $errors = [];
                $rowNumber = 1;
                
                // Prepare insert statement
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (Voornaam, Achternaam, Email, IDNumber, DistrictID, Role, Password, Status)
                    VALUES (:voornaam, :achternaam, :email, :idnumber, :districtid, 'voter', :password, 'active')
                    ON DUPLICATE KEY UPDATE 
                        Voornaam = VALUES(Voornaam), 
                        Achternaam = VALUES(Achternaam), 
                        DistrictID = VALUES(DistrictID),
                        Status = VALUES(Status) -- Optionally update other fields, avoid password update on duplicate
                "); // Added ON DUPLICATE KEY UPDATE to handle existing users gracefully
                
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
                            // Generate password only for new users, or handle updates differently
                            // For simplicity, we'll keep the existing password on update using ON DUPLICATE KEY UPDATE
                            'password' => password_hash(trim($row[$columns['IDNumber']]), PASSWORD_DEFAULT) 
                        ];
                        
                        // Validate data
                        if (empty($data['voornaam']) || empty($data['achternaam']) || empty($data['email']) || 
                            empty($data['idnumber']) || empty($data['districtid'])) {
                            throw new Exception("Ontbrekende verplichte velden op rij $rowNumber");
                        }
                        
                        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Ongeldig email adres op rij $rowNumber");
                        }
                        
                        // Insert or update user
                        if ($stmt->execute($data)) {
                            // Check if it was an insert or update
                            if ($stmt->rowCount() > 0) { // rowCount > 0 for INSERT, == 0 for no change on UPDATE, > 0 for actual UPDATE
                                $successCount++;
                            } else {
                                // Could log that user already existed and wasn't updated significantly
                            }
                        } else {
                            // Check for specific duplicate entry errors if not using ON DUPLICATE KEY UPDATE
                            // For now, assume a general DB error
                             throw new Exception("Database fout op rij $rowNumber: " . implode(", ", $stmt->errorInfo()));
                        }
                        
                    } catch (Exception $e) {
                        $errorCount++;
                        $errors[] = $e->getMessage();
                    }
                }
                
                // Commit transaction if there were any successful operations
                if ($successCount > 0 || $errorCount == 0) { // Commit even if only updates happened
                    $this->pdo->commit();
                    if ($successCount > 0) {
                         $_SESSION['success'] = "$successCount gebruikers succesvol geïmporteerd/bijgewerkt.";
                    } else {
                         $_SESSION['info'] = "Geen nieuwe gebruikers geïmporteerd, bestaande gebruikers mogelijk bijgewerkt.";
                    }
                   
                } else {
                    $this->pdo->rollBack(); // Rollback if only errors occurred
                }
                
                // Report errors
                if ($errorCount > 0) {
                    $_SESSION['error'] = "$errorCount rijen konden niet worden geïmporteerd.";
                    $_SESSION['import_errors'] = $errors; // Store specific errors for potential display
                }
                
                fclose($handle);
                
            } catch (Exception $e) {
                if (isset($this->pdo) && $this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                 if (isset($handle) && is_resource($handle)) {
                    fclose($handle);
                }
                $_SESSION['error'] = "Import fout: " . $e->getMessage();
            }
        } else {
             $_SESSION['error'] = "Geen CSV bestand geüpload.";
        }

        // Redirect back to qrcodes.php (or wherever the import was initiated)
        header("Location: " . BASE_URL . "/src/views/qrcodes.php");
        exit;
    }
}

// Instantiate and handle request
$controller = new ImportController();
$controller->handleImportRequest();

?>
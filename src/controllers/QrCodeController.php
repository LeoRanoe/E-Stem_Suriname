<?php

require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../../include/config.php';

class QrCodeController {

    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function handleRequest() {
        // session_start(); // Removed: Session should be started by the view or entry script
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['generate_bulk_qr'])) {
                $this->handleBulkGeneration();
            } elseif (isset($_POST['revoke_qr'])) {
                $this->handleRevocation();
            } elseif (isset($_POST['delete_qr'])) {
                $this->handleDeletion();
            }
        } elseif (isset($_GET['download']) && isset($_GET['id'])) {
             $this->handleDownload();
        } elseif (isset($_GET['generate_voucher']) && isset($_GET['qr_code']) && isset($_GET['user_name'])) {
             $this->handleVoucherGeneration();
        } elseif (isset($_GET['bulk_download']) && isset($_GET['district_id'])) {
             $this->handleBulkDownload();
        }
    }

    private function handleBulkGeneration() {
        $electionId = $_POST['election_id'] ?? null;
        $districtId = $_POST['district_id'] ?? null;
        
        if (!$electionId) {
            $_SESSION['error'] = "Selecteer een verkiezing.";
        } else {
            try {
                $this->pdo->beginTransaction();
                
                $userQuery = "
                    SELECT u.UserID 
                    FROM users u 
                    LEFT JOIN qrcodes q ON u.UserID = q.UserID AND q.ElectionID = :election_id
                    WHERE u.Role = 'voter' 
                    AND q.QRCodeID IS NULL
                    AND u.Status = 'active'";
                
                $params = ['election_id' => $electionId];
                
                if ($districtId) {
                    $userQuery .= " AND u.DistrictID = :district_id";
                    $params['district_id'] = $districtId;
                }
                
                $stmt = $this->pdo->prepare($userQuery);
                $stmt->execute($params);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $successCount = 0;
                $errorCount = 0;
                
                $insertStmt = $this->pdo->prepare("
                    INSERT INTO qrcodes (UserID, ElectionID, QRCode, Status)
                    VALUES (:user_id, :election_id, :qr_code, 'active')
                ");

                foreach ($users as $user) {
                    $token = bin2hex(random_bytes(16)); // Generate unique token
                    
                    if ($insertStmt->execute([
                        'user_id' => $user['UserID'],
                        'election_id' => $electionId,
                        'qr_code' => $token
                    ])) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
                
                $this->pdo->commit();
                
                if ($successCount > 0) {
                    $_SESSION['success'] = "QR codes gegenereerd voor $successCount gebruikers.";
                } else {
                    $_SESSION['info'] = "Geen nieuwe QR codes nodig voor de geselecteerde criteria.";
                }
                if ($errorCount > 0) {
                    $_SESSION['error'] = "$errorCount QR codes konden niet worden gegenereerd.";
                }
                
            } catch (Exception $e) {
                $this->pdo->rollBack();
                $_SESSION['error'] = "Er is een fout opgetreden: " . $e->getMessage();
            }
        }
        header("Location: " . BASE_URL . "/src/views/qrcodes.php");
        exit;
    }

    private function handleRevocation() {
        $qr_code_id = $_POST['qr_code_id'] ?? '';
        
        if (empty($qr_code_id)) {
            $_SESSION['error_message'] = "QR code ID is verplicht";
        } else {
            try {
                $stmt = $this->pdo->prepare("
                    UPDATE qrcodes 
                    SET Status = 'used', UsedAt = NOW() 
                    WHERE QRCodeID = :qr_code_id AND Status = 'active'
                ");
                $stmt->execute(['qr_code_id' => $qr_code_id]);
                
                if ($stmt->rowCount() > 0) {
                    $_SESSION['success_message'] = "QR code is succesvol ingetrokken";
                } else {
                    $_SESSION['error_message'] = "QR code kon niet worden ingetrokken (mogelijk al gebruikt of ongeldig)";
                }
            } catch(PDOException $e) {
                error_log("QR code revocation error: " . $e->getMessage());
                $_SESSION['error_message'] = "Er is een fout opgetreden bij het intrekken van de QR code";
            }
        }
        header("Location: " . BASE_URL . "/src/views/qrcodes.php");
        exit;
    }

    private function handleDeletion() {
        $qr_code_id = $_POST['qr_code_id'] ?? '';
        
        if (empty($qr_code_id)) {
            $_SESSION['error_message'] = "QR code ID is verplicht";
        } else {
            try {
                $stmt = $this->pdo->prepare("DELETE FROM qrcodes WHERE QRCodeID = :qr_code_id");
                $stmt->execute(['qr_code_id' => $qr_code_id]);
                
                if ($stmt->rowCount() > 0) {
                    $_SESSION['success_message'] = "QR code is succesvol verwijderd";
                } else {
                    $_SESSION['error_message'] = "QR code kon niet worden verwijderd (mogelijk al verwijderd)";
                }
            } catch(PDOException $e) {
                error_log("QR code deletion error: " . $e->getMessage());
                $_SESSION['error_message'] = "Er is een fout opgetreden bij het verwijderen van de QR code";
            }
        }
        header("Location: " . BASE_URL . "/src/views/qrcodes.php");
        exit;
    }
    
    private function handleDownload() {
         $qrId = $_GET['id'];
        try {
            $stmt = $this->pdo->prepare("
                SELECT q.QRCode, u.Voornaam, u.Achternaam
                FROM qrcodes q
                JOIN users u ON q.UserID = u.UserID
                WHERE q.QRCodeID = :qr_id
            ");
            $stmt->execute(['qr_id' => $qrId]);
            $qrData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($qrData) {
                require_once __DIR__ . '/../../vendor/autoload.php'; 
                
                $writer = new \BaconQrCode\Writer(new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400),
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                ));
                
                $qrContent = $qrData['QRCode']; // Use the actual QR code data
                $fileName = sprintf('qr_code_%s_%s_%s.svg', 
                    $qrData['Voornaam'],
                    $qrData['Achternaam'],
                    date('Ymd')
                );
                
                header('Content-Type: image/svg+xml');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                echo $writer->writeString($qrContent);
                exit;
            } else {
                 throw new Exception("QR code niet gevonden.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Kon QR code niet downloaden: " . $e->getMessage();
            header("Location: " . BASE_URL . "/src/views/qrcodes.php");
            exit;
        }
    }
    
    private function handleVoucherGeneration() {
         $qrCode = $_GET['qr_code'];
         $userName = $_GET['user_name'];
        
        try {
             // Verify if this QR code exists in the database
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM qrcodes WHERE QRCode = ?");
            $stmt->execute([$qrCode]);
            $exists = $stmt->fetchColumn();
            
            if ($exists) {
                // We need the generateVoucher function here or in a utility class
                // For now, let's assume it's available globally or include it
                 if (!function_exists('generateVoucher')) {
                     // Define it here or include a file containing it
                     function generateVoucher($qrCode, $userName) {
                         // ... (voucher HTML generation code from the view file) ...
                         // This is not ideal, better to put in a separate utility file
                         return "<html><body>Voucher for $userName with code $qrCode</body></html>"; // Placeholder
                     }
                 }
                $voucherHtml = generateVoucher($qrCode, $userName);
                header('Content-Type: text/html');
                echo $voucherHtml;
                exit;
            } else {
                 header('HTTP/1.1 404 Not Found');
                 echo "QR Code niet gevonden";
                 exit;
            }
        } catch (Exception $e) {
             error_log("Voucher generation error: " . $e->getMessage());
             header('HTTP/1.1 500 Internal Server Error');
             echo "Fout bij genereren voucher";
             exit;
        }
    }
    
     private function handleBulkDownload() {
         $district_id = $_GET['district_id'];
         try {
            require_once __DIR__ . '/../../vendor/autoload.php';
            
            $zip = new ZipArchive();
            $tempFile = tempnam(sys_get_temp_dir(), 'qrcodes_');
            
            if ($zip->open($tempFile, ZipArchive::CREATE) === TRUE) {
                $stmt = $this->pdo->prepare("
                    SELECT 
                        q.QRCode, u.Voornaam, u.Achternaam
                    FROM qrcodes q
                    JOIN users u ON q.UserID = u.UserID
                    WHERE u.DistrictID = :district_id AND q.Status = 'active'
                ");
                $stmt->execute(['district_id' => $district_id]);
                $qrCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($qrCodes)) {
                     $zip->close();
                     unlink($tempFile);
                     throw new Exception("Geen actieve QR codes gevonden voor dit district.");
                }

                $writer = new \BaconQrCode\Writer(new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400),
                    new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                ));

                 if (!function_exists('generateVoucher')) {
                     // Define it here or include a file containing it
                     function generateVoucher($qrCode, $userName) {
                         // ... (voucher HTML generation code from the view file) ...
                         return "<html><body>Voucher for $userName with code $qrCode</body></html>"; // Placeholder
                     }
                 }
                
                foreach ($qrCodes as $qr) {
                    $svgFileName = sprintf('qr_code_%s_%s_%s.svg', $qr['Voornaam'], $qr['Achternaam'], date('Ymd'));
                    $htmlFileName = sprintf('voucher_%s_%s_%s.html', $qr['Voornaam'], $qr['Achternaam'], date('Ymd'));
                    
                    $qrContent = $writer->writeString($qr['QRCode']);
                    $zip->addFromString($svgFileName, $qrContent);
                    
                    $voucherHtml = generateVoucher($qr['QRCode'], $qr['Voornaam'] . ' ' . $qr['Achternaam']);
                    $zip->addFromString($htmlFileName, $voucherHtml);
                }
                
                $zip->close();
                
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="qrcodes_district_' . $district_id . '_' . date('Ymd_His') . '.zip"');
                header('Content-Length: ' . filesize($tempFile));
                readfile($tempFile);
                unlink($tempFile);
                exit;
            } else {
                 throw new Exception("Kon ZIP archief niet aanmaken.");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Fout bij bulk download: " . $e->getMessage();
            header("Location: " . BASE_URL . "/src/views/qrcodes.php");
            exit;
        }
    }


    // --- Data Fetching Methods for the View ---

    public function getDistricts() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM districten ORDER BY DistrictName");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error fetching districts: " . $e->getMessage());
            return [];
        }
    }

    public function getQrCodesWithDetails() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT q.*, 
                       CONCAT(u.Voornaam, ' ', u.Achternaam) as UserName,
                       d.DistrictName,
                       e.ElectionName,
                       CASE 
                           WHEN q.Status = 'active' THEN 'Actief'
                           WHEN q.Status = 'used' THEN 'Gebruikt'
                           ELSE q.Status
                       END as StatusText
                FROM qrcodes q
                JOIN users u ON q.UserID = u.UserID
                JOIN districten d ON u.DistrictID = d.DistrictID
                JOIN elections e ON q.ElectionID = e.ElectionID
                ORDER BY e.ElectionName, d.DistrictName, q.CreatedAt DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error fetching QR codes: " . $e->getMessage());
            $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de QR codes";
            return [];
        }
    }

    public function getActiveElections() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ElectionID, ElectionName
                FROM elections
                WHERE Status = 'active'
                ORDER BY ElectionName
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Error fetching elections: " . $e->getMessage());
            return [];
        }
    }
    
     public function getDistrictStats() {
        $districtStats = [];
        $total_qr_codes = 0;
        $active_qr_codes = 0;
        $used_qr_codes = 0;

        try {
            $districts = $this->getDistricts();
            foreach ($districts as $district) {
                // Fixed query with unique placeholders
                $stmt = $this->pdo->prepare("
                    SELECT 
                        COALESCE(COUNT(DISTINCT q.QRCodeID), 0) as total_qr_codes,
                        COALESCE(SUM(CASE WHEN q.Status = 'active' THEN 1 ELSE 0 END), 0) as active_qr_codes,
                        COALESCE(SUM(CASE WHEN q.Status = 'used' THEN 1 ELSE 0 END), 0) as used_qr_codes,
                        (
                            SELECT COUNT(DISTINCT u2.UserID)
                            FROM users u2
                            LEFT JOIN qrcodes q2 ON u2.UserID = q2.UserID AND q2.ElectionID = e.ElectionID
                            WHERE u2.DistrictID = :district_id_subquery
                            AND u2.Role = 'voter'
                            AND u2.Status = 'active'
                            AND q2.QRCodeID IS NULL
                        ) as new_users
                    FROM users u
                    LEFT JOIN qrcodes q ON u.UserID = q.UserID
                    LEFT JOIN elections e ON q.ElectionID = e.ElectionID
                    WHERE u.DistrictID = :district_id_main
                    AND u.Role = 'voter'
                    AND u.Status = 'active'
                    GROUP BY u.DistrictID
                ");
                $stmt->execute([
                    'district_id_main' => $district['DistrictID'],
                    'district_id_subquery' => $district['DistrictID']
                ]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$stats) {
                    $stats = [
                        'total_qr_codes' => 0,
                        'active_qr_codes' => 0,
                        'used_qr_codes' => 0,
                        'new_users' => 0
                    ];
                }

                $districtStats[] = array_merge($district, $stats);
                $total_qr_codes += $stats['total_qr_codes'];
                $active_qr_codes += $stats['active_qr_codes'];
                $used_qr_codes += $stats['used_qr_codes'];
            }
        } catch (PDOException $e) {
            error_log("Error fetching district statistics: " . $e->getMessage());
            $_SESSION['error'] = "Er is een fout opgetreden bij het ophalen van de district statistieken: " . $e->getMessage();
        }

        return [
            'districtStats' => $districtStats,
            'total_qr_codes' => $total_qr_codes,
            'active_qr_codes' => $active_qr_codes,
            'used_qr_codes' => $used_qr_codes
        ];
    }
}

// Instantiate and handle request if accessed directly
$controller = new QrCodeController();
$controller->handleRequest();
?>
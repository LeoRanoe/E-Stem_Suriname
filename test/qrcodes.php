<?php
require_once __DIR__ . '/../../include/admin_auth.php';
require_once __DIR__ . '/../../include/config.php'; // Corrected path
require_once __DIR__ . '/../controllers/QrCodeController.php'; // Corrected path

$controller = new QrCodeController();

// Check if user is logged in and is an admin
requireAdminLogin();

$districts = $controller->getDistricts();
$qr_codes = $controller->getQrCodesWithDetails(); // Corrected method name
$elections = $controller->getActiveElections(); // Corrected method name

// ✅ CSV Import Logic - Add it here
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $uploadFile = $_FILES['csv_file'];

    // Check for upload errors
    if ($uploadFile['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Fout bij het uploaden van het bestand.";
        header("Location: qrcodes.php");
        exit;
    }

    // Validate MIME type
    $fileType = mime_content_type($uploadFile['tmp_name']);
    if ($fileType !== 'text/csv' && $fileType !== 'application/vnd.ms-excel') {
        $_SESSION['error'] = "Alleen CSV-bestanden zijn toegestaan.";
        header("Location: qrcodes.php");
        exit;
    }

    // Open CSV file
    if (($handle = fopen($uploadFile['tmp_name'], "r")) !== FALSE) {
        $headers = fgetcsv($handle); // Skip headers

        // Optional: validate column names
        $expectedHeaders = ['Voornaam', 'Achternaam', 'Email', 'IDNumber', 'DistrictID'];
        if ($headers !== $expectedHeaders) {
            $_SESSION['error'] = "CSV-kolommen komen niet overeen met de verwachte indeling.";
            header("Location: qrcodes.php");
            exit;
        }

        $successCount = 0;

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            list($Voornaam, $Achternaam, $Email, $IDNumber, $DistrictID) = $data;

            // Trim whitespace
            $Voornaam = trim($Voornaam);
            $Achternaam = trim($Achternaam);
            $Email = trim($Email);
            $IDNumber = trim($IDNumber);
            $DistrictID = (int)$DistrictID;

            // Basic validation
            if (empty($Voornaam) || empty($Achternaam) || empty($Email) || empty($IDNumber) || empty($DistrictID)) {
                continue; // skip invalid rows
            }

            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Email = ?");
            $stmt->execute([$Email]);
            if ($stmt->fetchColumn() > 0) {
                continue; // skip duplicates
            }

            // Insert new user
            $stmt = $pdo->prepare("
                INSERT INTO users (Voornaam, Achternaam, Email, IDNumber, DistrictID, Role, Status)
                VALUES (?, ?, ?, ?, ?, 'voter', 'active')
            ");
            if ($stmt->execute([$Voornaam, $Achternaam, $Email, $IDNumber, $DistrictID])) {
                $successCount++;
            }
        }
        fclose($handle);

        if ($successCount > 0) {
            $_SESSION['success'] = "$successCount gebruiker(s) succesvol geïmporteerd.";
        } else {
            $_SESSION['error'] = "Geen gebruikers geïmporteerd. Mogelijk geen geldige data in het bestand.";
        }
    } else {
        $_SESSION['error'] = "Kon het CSV-bestand niet openen.";
    }

    header("Location: qrcodes.php");
    exit;
}

// Add this function at the top of the file
function generateVoucher($qrCode, $userName) {
    $voucherHtml = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>E-Stem Suriname - Stem Voucher</title>
        <style>
            body {
                margin: 0;
                padding: 20px;
                font-family: Arial, sans-serif;
            }
            .voucher {
                width: 600px;
                height: 250px;
                border: 2px dashed #48BB78;
                border-radius: 15px;
                display: flex;
                overflow: hidden;
                background: white;
            }
            .voucher-left {
                flex: 1;
                padding: 20px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .voucher-right {
                width: 250px;
                background: #48BB78;
                padding: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .title {
                font-size: 32px;
                font-weight: bold;
                margin: 0;
                color: #2D3748;
            }
            .subtitle {
                font-size: 24px;
                color: #48BB78;
                margin: 10px 0;
            }
            .code {
                font-size: 18px;
                color: #718096;
                margin: 10px 0;
            }
            .qr-code {
                width: 200px;
                height: 200px;
                background: white;
                padding: 10px;
                border-radius: 10px;
            }
        </style>
    </head>
    <body>
        <div class="voucher">
            <div class="voucher-left">
                <h1 class="title">E-STEM SURINAME</h1>
                <p class="subtitle">Stem Voucher</p>
                <p class="code">CODE: ' . htmlspecialchars(substr($qrCode, 0, 8)) . '</p>
                <p class="code">Voor: ' . htmlspecialchars($userName) . '</p>
            </div>
            <div class="voucher-right">
                <img class="qr-code" src="https://api.qrserver.com/v1/create-qr-code/?data= ' . urlencode($qrCode) . '&size=200x200" alt="QR Code">
            </div>
        </div>
    </body>
    </html>';

    return $voucherHtml;
}

// Start output buffering
ob_start();

// Initialize variables
$qr_codes = [];
$total_qr_codes = 0;
$active_qr_codes = 0;
$used_qr_codes = 0;
$districtStats = [];

// Get district statistics
try {
    // First get all districts
    $stmt = $pdo->query("SELECT * FROM districten ORDER BY DistrictName");
    $districts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Then get QR code statistics for each district
    foreach ($districts as $district) {
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(COUNT(DISTINCT q.QRCodeID), 0) as total_qr_codes,
                COALESCE(SUM(CASE WHEN q.Status = 'active' THEN 1 ELSE 0 END), 0) as active_qr_codes,
                COALESCE(SUM(CASE WHEN q.Status = 'used' THEN 1 ELSE 0 END), 0) as used_qr_codes,
                (
                    SELECT COUNT(DISTINCT u2.UserID)
                    FROM users u2
                    LEFT JOIN qrcodes q2 ON u2.UserID = q2.UserID AND q2.ElectionID = e.ElectionID
                    WHERE u2.DistrictID = :district_id
                    AND u2.Role = 'voter'
                    AND u2.Status = 'active'
                    AND q2.QRCodeID IS NULL
                ) as new_users
            FROM users u
            LEFT JOIN qrcodes q ON u.UserID = q.UserID
            LEFT JOIN elections e ON q.ElectionID = e.ElectionID
            WHERE u.DistrictID = :district_id
            AND u.Role = 'voter'
            AND u.Status = 'active'
            GROUP BY u.DistrictID
        ");

        $stmt->execute(['district_id' => $district['DistrictID']]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$stats) {
            $stats = [
                'total_qr_codes' => 0,
                'active_qr_codes' => 0,
                'used_qr_codes' => 0,
                'new_users' => 0
            ];
        }

        $stats['total_qr_codes'] = (int)$stats['total_qr_codes'];
        $stats['active_qr_codes'] = (int)$stats['active_qr_codes'];
        $stats['used_qr_codes'] = (int)$stats['used_qr_codes'];
        $stats['new_users'] = (int)$stats['new_users'];

        $districtStats[] = array_merge($district, $stats);

        $total_qr_codes += $stats['total_qr_codes'];
        $active_qr_codes += $stats['active_qr_codes'];
        $used_qr_codes += $stats['used_qr_codes'];
    }
} catch(PDOException $e) {
    error_log("Error fetching district statistics: " . $e->getMessage());
    $_SESSION['error'] = "Er is een fout opgetreden bij het ophalen van de district statistieken: " . $e->getMessage();
}

// Fetch all QR codes with district and election information
try {
    $stmt = $pdo->prepare("
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
    $qr_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching QR codes: " . $e->getMessage());
    $_SESSION['error_message'] = "Er is een fout opgetreden bij het ophalen van de QR codes";
}

// Fetch active elections for the dropdown
try {
    $stmt = $pdo->prepare("
        SELECT ElectionID, ElectionName
        FROM elections
        WHERE Status = 'active'
        ORDER BY ElectionName
    ");
    $stmt->execute();
    $elections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching elections: " . $e->getMessage());
    $elections = [];
}

// Add bulk download functionality
if (isset($_GET['bulk_download']) && isset($_GET['district_id'])) {
    try {
        require_once '../vendor/autoload.php';

        // Create ZIP archive
        $zip = new ZipArchive();
        $tempFile = tempnam(sys_get_temp_dir(), 'qrcodes_');

        if ($zip->open($tempFile, ZipArchive::CREATE) === TRUE) {
            // Get QR codes for the district
            $stmt = $pdo->prepare("
                SELECT 
                    q.QRCode,
                    u.Voornaam,
                    u.Achternaam,
                    d.DistrictName,
                    e.ElectionName
                FROM qrcodes q
                JOIN users u ON q.UserID = u.UserID
                JOIN districten d ON u.DistrictID = d.DistrictID
                JOIN elections e ON q.ElectionID = e.ElectionID
                WHERE u.DistrictID = :district_id
                AND q.Status = 'active'
            ");

            $stmt->execute(['district_id' => $_GET['district_id']]);
            $qrCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Generate QR codes and add to ZIP
            $writer = new \BaconQrCode\Writer(new \BaconQrCode\Renderer\ImageRenderer(
                new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400),
                new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
            ));

            foreach ($qrCodes as $qr) {
                $fileName = sprintf('qr_code_%s_%s_%s.svg',
                    $qr['Voornaam'],
                    $qr['Achternaam'],
                    date('Ymd')
                );

                // Generate QR code
                $qrContent = $writer->writeString($qr['QRCode']);

                // Add to ZIP
                $zip->addFromString($fileName, $qrContent);

                // Generate and add voucher
                $voucherFileName = sprintf('voucher_%s_%s_%s.html',
                    $qr['Voornaam'],
                    $qr['Achternaam'],
                    date('Ymd')
                );
                $voucherHtml = generateVoucher($qr['QRCode'], $qr['Voornaam'] . ' ' . $qr['Achternaam']);
                $zip->addFromString($voucherFileName, $voucherHtml);
            }

            $zip->close();

            // Send ZIP file
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="qrcodes_' . date('Ymd_His') . '.zip"');
            header('Content-Length: ' . filesize($tempFile));
            readfile($tempFile);
            unlink($tempFile);
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Fout bij het downloaden van QR codes: " . $e->getMessage();
        header("Location: qrcodes.php");
        exit;
    }
}
?>
<?php if (isset($_SESSION['success'])): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php elseif (isset($_SESSION['error'])): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>
<!-- Search and Filters Section -->
<div class="mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <h2 class="text-lg font-semibold flex items-center mb-4 md:mb-0">
                <i class="fas fa-filter mr-2 text-suriname-green"></i>
                Filters & Zoeken
            </h2>
            <div class="flex flex-wrap gap-2">
                <button onclick="document.getElementById('importUsersModal').classList.remove('hidden')" 
                        class="bg-suriname-green hover:bg-suriname-dark-green text-white px-4 py-2 rounded-md text-sm flex items-center">
                    <i class="fas fa-file-import mr-2"></i>
                    Importeer Users
                </button>
                <button onclick="document.getElementById('generateQRModal').classList.remove('hidden')" 
                        class="bg-suriname-green hover:bg-suriname-dark-green text-white px-4 py-2 rounded-md text-sm flex items-center">
                    <i class="fas fa-qrcode mr-2"></i>
                    Genereer QR Codes
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Zoeken</label>
                <div class="relative">
                    <input type="text" id="searchInput" 
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green pl-10"
                           placeholder="Zoek op naam, ID nummer of district...">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- District Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                <select id="districtFilter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green">
                    <option value="">Alle districten</option>
                    <?php foreach ($districts as $district): ?>
                        <option value="<?= $district['DistrictID'] ?>"><?= htmlspecialchars($district['DistrictName']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="statusFilter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-suriname-green focus:ring-suriname-green">
                    <option value="">Alle statussen</option>
                    <option value="active">Actief</option>
                    <option value="used">Gebruikt</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Import Users Modal -->
<div id="importUsersModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            CSV Bestand
                        </label>
                        <input type="file" name="csv_file" accept=".csv" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-suriname-green focus:border-suriname-green">
                        <p class="mt-2 text-sm text-gray-500">
                            Upload een CSV bestand met de volgende kolommen: Voornaam, Achternaam, Email, IDNumber, DistrictID
                        </p>
                    </div>
                    <div class="bg-gray-50 mt-4 p-4 rounded-md">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Voorbeeld CSV format:</h4>
                        <code class="text-xs text-gray-600">
                            Voornaam,Achternaam,Email,IDNumber,DistrictID<br>
                            John,Doe,john@example.com,12345,1<br>
                            Jane,Smith,jane@example.com,67890,2
                        </code>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-suriname-green text-base font-medium text-white hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:ml-3 sm:w-auto sm:text-sm">
                        Importeren
                    </button>
                    <button type="button" 
                            onclick="document.getElementById('importUsersModal').classList.add('hidden')"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Annuleren
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Generate QR Codes Modal -->
<div id="generateQRModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST" action="">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="election_id">
                            Verkiezing
                        </label>
                        <select name="election_id" id="election_id" required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Selecteer een verkiezing</option>
                            <?php foreach ($elections as $election): ?>
                                <option value="<?php echo $election['ElectionID']; ?>">
                                    <?php echo htmlspecialchars($election['ElectionName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="district_id">
                            District (Optioneel)
                        </label>
                        <select name="district_id" id="district_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="">Alle districten</option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?php echo $district['DistrictID']; ?>">
                                    <?php echo htmlspecialchars($district['DistrictName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Genereer QR codes voor alle kiezers in één keer.</p>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="generate_bulk_qr" 
                            onclick="return confirm('Weet u zeker dat u QR codes wilt genereren voor alle kiezers? Dit kan even duren.')"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-suriname-green text-base font-medium text-white hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 transform hover:scale-105">
                        Genereren
                    </button>
                    <button type="button" 
                            onclick="document.getElementById('generateQRModal').classList.add('hidden')"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 transform hover:scale-105">
                        Annuleren
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Totaal QR Codes</p>
                <p class="text-2xl font-bold text-suriname-green"><?= number_format((int)$total_qr_codes) ?></p>
            </div>
            <div class="p-3 bg-suriname-green/10 rounded-full">
                <i class="fas fa-qrcode text-2xl text-suriname-green"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Actieve QR Codes</p>
                <p class="text-2xl font-bold text-green-600"><?= number_format((int)$active_qr_codes) ?></p>
            </div>
            <div class="p-3 bg-green-100 rounded-full">
                <i class="fas fa-check-circle text-2xl text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">Gebruikte QR Codes</p>
                <p class="text-2xl font-bold text-blue-600"><?= number_format((int)$used_qr_codes) ?></p>
            </div>
            <div class="p-3 bg-blue-100 rounded-full">
                <i class="fas fa-vote-yea text-2xl text-blue-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- District Statistics -->
<?php if (!empty($districtStats)): ?>
<div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 mb-8">
    <h2 class="text-lg font-semibold mb-6 flex items-center">
        <i class="fas fa-chart-pie mr-2 text-suriname-green"></i>
        QR Codes per District
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($districtStats as $stat): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow duration-300">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-800 text-lg">
                        <?= htmlspecialchars($stat['DistrictName']) ?>
                    </h3>
                    <div class="flex items-center space-x-2">
                        <?php if ($stat['new_users'] > 0): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <?= $stat['new_users'] ?> nieuwe
                            </span>
                        <?php endif; ?>
                        <?php if ($stat['active_qr_codes'] > 0): ?>
                            <a href="?bulk_download=1&district_id=<?= $stat['DistrictID'] ?>" 
                               class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-suriname-green text-white hover:bg-suriname-dark-green transition-colors duration-200">
                                <i class="fas fa-download mr-1"></i> Download Alle
                            </a>
                        <?php endif; ?>
                        <div class="w-2 h-2 rounded-full <?= $stat['total_qr_codes'] > 0 ? 'bg-green-500' : 'bg-gray-300' ?>"></div>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <!-- Total QR Codes -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Totaal</span>
                        <div class="flex items-center">
                            <span class="font-medium text-lg"><?= number_format((int)$stat['total_qr_codes']) ?></span>
                        </div>
                    </div>
                    
                    <!-- Active QR Codes -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Actief</span>
                        <div class="flex items-center">
                            <div class="w-24 h-2 bg-gray-200 rounded-full mr-3">
                                <div class="h-2 bg-green-500 rounded-full" style="width: <?= $stat['total_qr_codes'] > 0 ? ($stat['active_qr_codes'] / $stat['total_qr_codes'] * 100) : 0 ?>%"></div>
                            </div>
                            <span class="font-medium text-green-600"><?= number_format((int)$stat['active_qr_codes']) ?></span>
                        </div>
                    </div>
                    
                    <!-- Used QR Codes -->
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Gebruikt</span>
                        <div class="flex items-center">
                            <div class="w-24 h-2 bg-gray-200 rounded-full mr-3">
                                <div class="h-2 bg-blue-500 rounded-full" style="width: <?= $stat['total_qr_codes'] > 0 ? ($stat['used_qr_codes'] / $stat['total_qr_codes'] * 100) : 0 ?>%"></div>
                            </div>
                            <span class="font-medium text-blue-600"><?= number_format((int)$stat['used_qr_codes']) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Percentage Bar -->
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="flex h-2 rounded-full overflow-hidden bg-gray-200">
                        <?php if ($stat['total_qr_codes'] > 0): ?>
                            <div class="bg-green-500" style="width: <?= ($stat['active_qr_codes'] / $stat['total_qr_codes'] * 100) ?>%"></div>
                            <div class="bg-blue-500" style="width: <?= ($stat['used_qr_codes'] / $stat['total_qr_codes'] * 100) ?>%"></div>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-gray-500">
                        <span><?= $stat['total_qr_codes'] > 0 ? round(($stat['active_qr_codes'] / $stat['total_qr_codes'] * 100)) : 0 ?>% actief</span>
                        <span><?= $stat['total_qr_codes'] > 0 ? round(($stat['used_qr_codes'] / $stat['total_qr_codes'] * 100)) : 0 ?>% gebruikt</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Generate QR Codes Button -->
<div class="mb-6">
    <button onclick="document.getElementById('generateQRModal').classList.remove('hidden')" 
            class="bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-2 px-4 rounded transition-all duration-300 transform hover:scale-105">
        <i class="fas fa-qrcode mr-2"></i>Massaal QR Codes Genereren
    </button>
</div>

<!-- QR Codes Table -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gebruiker</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verkiezing</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aangemaakt</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gebruikt</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($qr_codes)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        Er zijn nog geen QR codes gegenereerd
                    </td>
                </tr>
            <?php else: ?>
                <?php 
                $currentElection = '';
                $currentDistrict = '';
                foreach ($qr_codes as $qr): 
                    if ($currentElection !== $qr['ElectionName']):
                        $currentElection = $qr['ElectionName'];
                ?>
                    <tr class="bg-suriname-green/10">
                        <td colspan="7" class="px-6 py-3 text-sm font-bold text-suriname-green">
                            <?= htmlspecialchars($currentElection) ?>
                        </td>
                    </tr>
                <?php 
                        $currentDistrict = '';
                    endif;
                    if ($currentDistrict !== $qr['DistrictName']):
                        $currentDistrict = $qr['DistrictName'];
                ?>
                    <tr class="bg-gray-50">
                        <td colspan="7" class="px-6 py-2 text-sm font-semibold text-gray-600">
                            <?= htmlspecialchars($currentDistrict) ?>
                        </td>
                    </tr>
                <?php endif; ?>
                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($qr['UserName']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($qr['DistrictName']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($qr['ElectionName']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($qr['Status'] === 'active'): ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    <?php echo htmlspecialchars($qr['StatusText']); ?>
                                </span>
                            <?php elseif ($qr['Status'] === 'used'): ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    <?php echo htmlspecialchars($qr['StatusText']); ?>
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    <?php echo htmlspecialchars($qr['StatusText']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d-m-Y H:i', strtotime($qr['CreatedAt'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $qr['UsedAt'] ? date('d-m-Y H:i', strtotime($qr['UsedAt'])) : '-'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <?php if ($qr['Status'] === 'active'): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="qr_code_id" value="<?php echo $qr['QRCodeID']; ?>">
                                    <button type="submit" name="revoke_qr" 
                                            onclick="return confirm('Weet u zeker dat u deze QR code wilt intrekken?')"
                                            class="text-red-600 hover:text-red-900 mr-3 transition-colors duration-200">
                                        <i class="fas fa-ban transform hover:scale-110 transition-transform duration-200"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="?generate_voucher=1&qr_code=<?php echo urlencode($qr['QRCode']); ?>&user_name=<?php echo urlencode($qr['UserName']); ?>" 
                               class="text-suriname-green hover:text-suriname-dark-green mr-3 transition-colors duration-200"
                               target="_blank">
                                <i class="fas fa-ticket-alt transform hover:scale-110 transition-transform duration-200"></i>
                            </a>
                            <a href="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo urlencode($qr['QRCode']); ?>&size=300x300" 
                               class="text-suriname-green hover:text-suriname-dark-green mr-3 transition-colors duration-200"
                               target="_blank">
                                <i class="fas fa-qrcode transform hover:scale-110 transition-transform duration-200"></i>
                            </a>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="qr_code_id" value="<?php echo $qr['QRCodeID']; ?>">
                                <button type="submit" name="delete_qr" 
                                        onclick="return confirm('Weet u zeker dat u deze QR code wilt verwijderen?')"
                                        class="text-red-600 hover:text-red-900 transition-colors duration-200">
                                    <i class="fas fa-trash transform hover:scale-110 transition-transform duration-200"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add this JavaScript at the bottom of the file -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const districtFilter = document.getElementById('districtFilter');
    const statusFilter = document.getElementById('statusFilter');
    const tableRows = document.querySelectorAll('tbody tr:not(.bg-suriname-green\\/10):not(.bg-gray-50)'); // Escaped '/' in class name

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedDistrict = districtFilter.value;
        const selectedStatus = statusFilter.value;

        tableRows.forEach(row => {
            const userName = row.querySelector('td:nth-child(1)')?.textContent.toLowerCase() || '';
            const district = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
            const status = row.querySelector('.rounded-full')?.textContent.toLowerCase().trim() || '';

            const matchesSearch = userName.includes(searchTerm) || district.includes(searchTerm);
            const matchesDistrict = !selectedDistrict || row.querySelector('td:nth-child(2)')?.textContent.includes(districtFilter.options[districtFilter.selectedIndex].text);
            const matchesStatus = !selectedStatus || status.includes(selectedStatus === 'active' ? 'actief' : 'gebruikt');

            row.style.display = (matchesSearch && matchesDistrict && matchesStatus) ? '' : 'none';
        });

        // Update header rows visibility
        updateHeaderRows();
    }

    function updateHeaderRows() {
        let currentElection = '';
        let currentDistrict = '';
        let hasVisibleRows = false;

        document.querySelectorAll('tbody tr').forEach(row => {
            if (row.classList.contains('bg-suriname-green/10')) {
                // Election header
                currentElection = row;
                hasVisibleRows = false;
            } else if (row.classList.contains('bg-gray-50')) {
                // District header
                currentDistrict = row;
                hasVisibleRows = false;
            } else if (row.style.display !== 'none') {
                hasVisibleRows = true;
                if (currentElection) {
                    currentElection.style.display = '';
                    currentElection = null;
                }
                if (currentDistrict) {
                    currentDistrict.style.display = '';
                    currentDistrict = null;
                }
            }

            if (!hasVisibleRows) {
                if (currentElection) currentElection.style.display = 'none';
                if (currentDistrict) currentDistrict.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterTable);
    districtFilter.addEventListener('change', filterTable);
    statusFilter.addEventListener('change', filterTable);
});
</script>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout template
require_once __DIR__ . '/../../admin/components/layout.php'; // Corrected path
?> 
<?php
session_start();
require_once 'include/db_connect.php';
require_once 'include/auth.php';

// Check if user is logged in and is a voter
requireVoter();

// Get current user's data
$currentUser = getCurrentUser();

// Check if user has already voted
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as vote_count 
        FROM votes 
        WHERE UserID = :user_id
    ");
    $stmt->execute(['user_id' => $currentUser['UserID']]);
    $vote_count = $stmt->fetch()['vote_count'];

    if ($vote_count > 0) {
        header('Location: vote_success.php');
        exit();
    }
} catch(PDOException $e) {
    error_log("Error checking vote status: " . $e->getMessage());
    $error = "Er is een fout opgetreden. Probeer het later opnieuw.";
}

// Handle session expired error
if (isset($_GET['error']) && $_GET['error'] === 'session_expired') {
    $error = "Uw stem sessie is verlopen. Scan opnieuw uw QR code om te stemmen.";
}

// Handle QR code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qr_code = $_POST['qr_code'] ?? '';
    
    if (empty($qr_code)) {
        $error = "QR code is verplicht";
    } else {
        try {
            // Check if QR code exists and is valid
            $stmt = $pdo->prepare("
                SELECT q.*, u.* 
                FROM qrcodes q 
                JOIN users u ON q.UserID = u.UserID 
                WHERE q.QRCode = :qr_code AND q.Status = 'active'
            ");
            $stmt->execute(['qr_code' => $qr_code]);
            $qr_data = $stmt->fetch();

            if (!$qr_data) {
                $error = "Ongeldige QR code";
            } elseif ($qr_data['UserID'] !== $currentUser['UserID']) {
                $error = "Deze QR code is niet voor uw account";
            } else {
                // Start voting session
                $_SESSION['voting_session'] = [
                    'qr_code' => $qr_code,
                    'start_time' => time()
                ];
                
                // Redirect to voting page
                header('Location: vote.php');
                exit();
            }
        } catch(PDOException $e) {
            error_log("QR code validation error: " . $e->getMessage());
            $error = "Er is een fout opgetreden. Probeer het later opnieuw.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan QR Code - E-Stem Suriname</title>
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
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            background-color: #007749;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            background-color: #006241;
        }
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .qr-instructions {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>E-Stem Suriname</h1>
        <p>Scan uw QR code om te stemmen</p>
    </div>

    <div class="container">
        <div class="main-content">
            <?php if (isset($error)): ?>
                <div class="error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="qr-instructions">
                <h2>Hoe werkt het?</h2>
                <ol>
                    <li>Open de QR code die u heeft ontvangen</li>
                    <li>Kopieer de code uit de QR code</li>
                    <li>Plak de code in het onderstaande veld</li>
                    <li>Klik op "Verifiëren" om te beginnen met stemmen</li>
                </ol>
                <p><strong>Let op:</strong> U heeft 30 minuten de tijd om uw stem uit te brengen nadat u de QR code heeft ingevoerd.</p>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="qr_code">QR Code:</label>
                    <input type="text" id="qr_code" name="qr_code" required 
                           placeholder="Plak hier uw QR code">
                </div>
                <button type="submit" class="btn">Verifiëren</button>
            </form>
        </div>
    </div>
</body>
</html> 
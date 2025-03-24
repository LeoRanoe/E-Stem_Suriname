<?php
session_start();
require_once '../include/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['User ID'])) {
    header('Location: /E-Stem_Suriname/pages/login.php');
    exit();
}

// Check if user is admin
$stmt = $conn->prepare("
    SELECT ut.UserType 
    FROM users u 
    JOIN usertype ut ON u.UTypeID = ut.UTypeID 
    WHERE u.UserID = :userID
");
$stmt->execute(['userID' => $_SESSION['User ID']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['UserType'] !== 'Admin') {
    header('Location: /E-Stem_Suriname/index.php');
    exit();
}

// Get all QR codes with user details
$stmt = $conn->prepare("
    SELECT q.*, u.Voornaam, u.Achternaam, u.Email, d.Name as District,
           c.Voornaam as CreatedByVoornaam, c.Achternaam as CreatedByAchternaam,
           r.Voornaam as RevokedByVoornaam, r.Achternaam as RevokedByAchternaam
    FROM qrcodes q
    JOIN users u ON q.UserID = u.UserID
    JOIN districts d ON u.DistrictID = d.DistrictID
    LEFT JOIN users c ON q.CreatedBy = c.UserID
    LEFT JOIN users r ON q.RevokedBy = r.UserID
    ORDER BY q.CreatedAt DESC
");
$stmt->execute();
$qr_codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Codes - E-Stem Suriname</title>
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
</head>
<body class="bg-gray-50">
    <?php include '../include/nav.php'; ?>

    <div class="min-h-screen pt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">QR Codes</h1>
                    <a href="/E-Stem_Suriname/admin/generate_qr.php" 
                        class="bg-suriname-green text-white px-4 py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-300 flex items-center space-x-2">
                        <i class="fas fa-plus"></i>
                        <span>Nieuwe QR Code</span>
                    </a>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <?php unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    QR Code
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Stemmer
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    District
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Vervaldatum
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acties
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($qr_codes as $qr): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars(substr($qr['QRCode'], 0, 8)) ?>...
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?= htmlspecialchars($qr['Voornaam'] . ' ' . $qr['Achternaam']) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?= htmlspecialchars($qr['Email']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($qr['District']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?= $qr['Status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $qr['Status'] === 'active' ? 'Actief' : 'Ingetrokken' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= date('d-m-Y H:i', strtotime($qr['ExpiryDate'])) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="/E-Stem_Suriname/admin/view_qr.php?id=<?= $qr['QRCodeID'] ?>" 
                                            class="text-suriname-green hover:text-suriname-dark-green mr-4">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($qr['Status'] === 'active'): ?>
                                            <a href="/E-Stem_Suriname/admin/revoke_qr.php?id=<?= $qr['QRCodeID'] ?>" 
                                                class="text-suriname-red hover:text-suriname-dark-red"
                                                onclick="return confirm('Weet u zeker dat u deze QR code wilt intrekken?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../include/footer.php'; ?>
</body>
</html> 
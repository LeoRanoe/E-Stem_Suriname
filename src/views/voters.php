<?php
require_once __DIR__ . '/../../include/auth.php'; // Corrected path
require_once __DIR__ . '/../../include/config.php'; // Corrected path

require_once __DIR__ . '/../controllers/VoterController.php'; // Corrected path

$controller = new VoterController();

// Check if user is logged in and is admin
requireAdmin();

$voters = $controller->getVoters();
$total_votes = $controller->getTotalVotes();
$total_districts = $controller->getTotalDistricts();
$total_active_voters = $controller->getTotalActiveVoters();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stemmers - E-Stem Suriname</title>
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
        /* Custom Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #007749;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #006241;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Navigation is now included via layout.php -->

        <!-- Main Content -->
        <div class="flex-1 ml-64 p-6 overflow-y-auto">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 animate-fade-in" role="alert">
                    <span class="block sm:inline"><?= $_SESSION['success_message'] ?></span>
                    <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 animate-fade-in" role="alert">
                    <span class="block sm:inline"><?= $_SESSION['error_message'] ?></span>
                    <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="animate-slide-in">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Totaal Stemmers</p>
                                <p class="text-2xl font-bold text-suriname-green"><?= number_format(count($voters)) ?></p>
                            </div>
                            <div class="p-3 bg-suriname-green/10 rounded-full">
                                <i class="fas fa-users text-2xl text-suriname-green"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Totaal Stemmen</p>
                                <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_votes) ?></p>
                            </div>
                            <div class="p-3 bg-suriname-green/10 rounded-full">
                                <i class="fas fa-vote-yea text-2xl text-suriname-green"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Districten</p>
                                <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_districts) ?></p>
                            </div>
                            <div class="p-3 bg-suriname-green/10 rounded-full">
                                <i class="fas fa-map-marker-alt text-2xl text-suriname-green"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 transform hover:scale-105 transition-all duration-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Actieve Stemmers</p>
                                <p class="text-2xl font-bold text-suriname-green"><?= number_format($total_active_voters) ?></p>
                            </div>
                            <div class="p-3 bg-suriname-green/10 rounded-full">
                                <i class="fas fa-user-check text-2xl text-suriname-green"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add New Voter Button -->
                <div class="mb-6">
                    <button onclick="document.getElementById('newVoterModal').classList.remove('hidden')" 
                            class="bg-suriname-green hover:bg-suriname-dark-green text-white font-bold py-2 px-4 rounded transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-plus mr-2"></i>Nieuwe Stemmer
                    </button>
                </div>

                <!-- Voters Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Naam</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Laatste Stem</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($voters)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        Geen stemmers gevonden
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($voters as $voter): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-10 w-10 flex-shrink-0">
                                                    <?php
                                                    $imageSrc = 'https://via.placeholder.com/40'; // Default placeholder
                                                    if (isset($voter['ProfileImage']) && !empty(trim($voter['ProfileImage']))) {
                                                        // Trim whitespace, remove leading/trailing slashes, then urlencode path segments
                                                        $trimmedPath = trim($voter['ProfileImage'], " \t\n\r\0\x0B/");
                                                        $pathSegments = explode('/', $trimmedPath);
                                                        $encodedPath = implode('/', array_map('rawurlencode', $pathSegments));
                                                        
                                                        // Ensure BASE_URL is defined and ends with a slash
                                                        if (!defined('BASE_URL')) {
                                                            define('BASE_URL', 'http://localhost/E-Stem_Suriname'); // Default if not defined
                                                        }
                                                        $baseUrl = rtrim(BASE_URL, '/');
                                                        
                                                        // Construct the full URL
                                                        $imageSrc = $baseUrl . '/' . $encodedPath;
                                                        
                                                        // Debugging: Check if the file exists
                                                        $absolutePath = __DIR__ . '/../../' . $trimmedPath;
                                                        if (!file_exists($absolutePath)) {
                                                            error_log("Voter profile image not found: " . $absolutePath);
                                                            $imageSrc = 'https://via.placeholder.com/40'; // Fallback to placeholder
                                                        }
                                                    }
                                                    ?>
                                                    <img class="h-10 w-10 rounded-full object-cover transform hover:scale-110 transition-transform duration-200"
                                                         src="<?= htmlspecialchars($imageSrc) ?>"
                                                         alt="<?= htmlspecialchars($voter['Voornaam'] . ' ' . $voter['Achternaam']) ?>">
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($voter['Voornaam'] . ' ' . $voter['Achternaam']) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        ID: <?= $voter['UserID'] ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <p class="text-sm text-gray-900"><?= htmlspecialchars($voter['Email']) ?></p>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                                <?= htmlspecialchars($voter['DistrictName'] ?? 'Onbekend') ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-suriname-green/10 text-suriname-green">
                                                <?= number_format($voter['vote_count']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <p class="text-sm text-gray-500">
                                                <?= $voter['last_vote'] ? date('d-m-Y H:i', strtotime($voter['last_vote'])) : 'Nog niet gestemd' ?>
                                            </p>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="edit_voter.php?id=<?= $voter['UserID'] ?>" 
                                               class="text-suriname-green hover:text-suriname-dark-green mr-3 transition-colors duration-200">
                                                <i class="fas fa-edit transform hover:scale-110 transition-transform duration-200"></i>
                                            </a>
                                            <a href="javascript:void(0);" 
                                               onclick="if(confirm('Weet u zeker dat u deze stemmer wilt verwijderen?')) { 
                                                   const form = document.createElement('form');
                                                   form.method = 'POST';
                                                   form.innerHTML = '<input name=\'action\' value=\'delete\'><input name=\'user_id\' value=\'<?= $voter['UserID'] ?>\'>';
                                                   document.body.appendChild(form);
                                                   form.submit();
                                               }"
                                               class="text-suriname-red hover:text-suriname-dark-red transition-colors duration-200">
                                                <i class="fas fa-trash transform hover:scale-110 transition-transform duration-200"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- New Voter Modal -->
                <div id="newVoterModal" class="hidden fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <form method="POST" action="voters.php">
                                <input type="hidden" name="action" value="create">
                                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="voornaam">
                                            Voornaam
                                        </label>
                                        <input type="text" name="voornaam" id="voornaam" required
                                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="achternaam">
                                            Achternaam
                                        </label>
                                        <input type="text" name="achternaam" id="achternaam" required
                                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                                            Email
                                        </label>
                                        <input type="email" name="email" id="email" required
                                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                                            Wachtwoord
                                        </label>
                                        <input type="password" name="password" id="password" required
                                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">
                                            Bevestig Wachtwoord
                                        </label>
                                        <input type="password" name="confirm_password" id="confirm_password" required
                                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="district_id">
                                            District
                                        </label>
                                        <select name="district_id" id="district_id" required
                                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                            <option value="">Selecteer een district</option>
                                            <?php
                                            $districts = $pdo->query("SELECT * FROM districten ORDER BY DistrictName")->fetchAll();
                                            foreach ($districts as $district) {
                                                echo '<option value="' . $district['DistrictID'] . '">' . htmlspecialchars($district['DistrictName']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="id_number">
                                            ID Nummer
                                        </label>
                                        <input type="text" name="id_number" id="id_number" required
                                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                    <button type="submit" 
                                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-suriname-green text-base font-medium text-white hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 transform hover:scale-105">
                                        Opslaan
                                    </button>
                                    <button type="button" 
                                            onclick="document.getElementById('newVoterModal').classList.add('hidden')"
                                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-all duration-300 transform hover:scale-105">
                                        Annuleren
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scroll to top button -->
            <button id="scrollToTop" 
                    class="fixed bottom-4 right-4 bg-suriname-green text-white rounded-full p-3 hidden transition-all duration-300 hover:bg-suriname-dark-green hover:scale-110"
                    onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
                <i class="fas fa-arrow-up"></i>
            </button>
        </div>
    </div>

    <script>
        // Scroll to top button visibility
        window.onscroll = function() {
            const scrollButton = document.getElementById('scrollToTop');
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                scrollButton.classList.remove('hidden');
            } else {
                scrollButton.classList.add('hidden');
            }
        };

        // Add hover effect to all tables
        document.querySelectorAll('table tr').forEach(row => {
            row.classList.add('hover-row');
        });

        // Add hover effect to all buttons
        document.querySelectorAll('button').forEach(button => {
            button.classList.add('btn-hover');
        });
    </script>
</body>
</html>
<?php
// Get the buffered content
// Note: ob_start() should be called at the beginning of the script if using layout this way
// $content = ob_get_clean(); // This line might be needed depending on how layout uses $content

// Include the layout template
require_once __DIR__ . '/../../admin/components/layout.php'; 
?>
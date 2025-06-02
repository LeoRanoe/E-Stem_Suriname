<?php
require_once __DIR__ . '/../../include/admin_auth.php';
require_once __DIR__ . '/../../include/config.php'; // Defines BASE_URL
require_once __DIR__ . '/../../include/db_connect.php'; // For $pdo
require_once __DIR__ . '/../controllers/CandidateController.php';

requireAdmin();

$controller = new CandidateController();

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;

$filters = [
    'district_id' => $_GET['district_id'] ?? null,
    'party_id' => $_GET['party_id'] ?? null,
    'candidate_type' => $_GET['candidate_type'] ?? null
];

$candidates = $controller->getCandidates($page, $per_page, $filters) ?? [];
$total_candidates = $controller->getTotalCandidatesCount($filters) ?? 0;
$total_pages = $total_candidates > 0 ? ceil($total_candidates / $per_page) : 1;

$formData = $controller->getFormData() ?? [];
$elections = isset($formData['elections']) && is_array($formData['elections']) ? $formData['elections'] : [];
$parties = isset($formData['parties']) && is_array($formData['parties']) ? $formData['parties'] : [];
$districts = isset($formData['districts']) && is_array($formData['districts']) ? $formData['districts'] : [];

// Statistics
$totalCandidatesCount = $controller->getTotalCandidatesCount([]); // All candidates
$rrCandidatesCount = $controller->getTotalCandidatesCount(['candidate_type' => 'RR']);
$dnaCandidatesCount = $controller->getTotalCandidatesCount(['candidate_type' => 'DNA']);

// Candidate counts per district for the filter dropdown
$candidateCountsByDistrictIdData = $pdo->query("
    SELECT DistrictID, COUNT(CandidateID) as CandidateCount
    FROM candidates
    GROUP BY DistrictID
")->fetchAll(PDO::FETCH_ASSOC);
$candidateCountsMap = [];
foreach ($candidateCountsByDistrictIdData as $row) {
    $candidateCountsMap[$row['DistrictID']] = $row['CandidateCount'];
}

ob_start();
?>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Kandidatenbeheer</h1>
        <p class="text-gray-600">Overzicht en beheer van alle kandidaten.</p>
    </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-lg bg-suriname-green/10 text-suriname-green">
                    <i class="fas fa-users text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Totaal Kandidaten</p>
                    <p class="text-3xl font-semibold text-gray-800 mt-1"><?= $totalCandidatesCount ?></p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-lg bg-blue-100 text-blue-600">
                    <i class="fas fa-user-tie text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">RR Kandidaten</p>
                    <p class="text-3xl font-semibold text-gray-800 mt-1"><?= $rrCandidatesCount ?></p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-200 flex items-center space-x-4 transition-all duration-300 hover:shadow-xl hover:scale-105">
                <div class="p-3 rounded-lg bg-purple-100 text-purple-600">
                    <i class="fas fa-landmark text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">DNA Kandidaten</p>
                    <p class="text-3xl font-semibold text-gray-800 mt-1"><?= $dnaCandidatesCount ?></p>
                </div>
            </div>
        </div>
        <!-- Statistics Cards End -->

        <!-- Add New Candidate Button -->
        <div class="mb-6 flex justify-end">
            <button id="openNewCandidateModalBtn"
                    class="bg-suriname-green hover:bg-suriname-dark-green text-white font-semibold py-3 px-6 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center">
                <i class="fas fa-plus mr-2"></i>Nieuwe Kandidaat Toevoegen
            </button>
        </div>

        <!-- Filter Form -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Filters</h2>
            </div>
            <div class="p-6">
                <form id="filterForm" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="district_id_filter" class="block text-sm font-medium text-gray-700 mb-2">District</label>
                        <select id="district_id_filter" name="district_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm">
                            <option value="">Alle Districten</option>
                            <?php if (!empty($districts)): ?>
                                <?php foreach ($districts as $districtItem): ?>
                                    <?php
                                        $districtId = $districtItem['DistrictID'];
                                        $districtName = $districtItem['DistrictName'];
                                        $candidateCountInDistrict = $candidateCountsMap[$districtId] ?? 0;
                                        $isSelected = isset($_GET['district_id']) && $_GET['district_id'] == $districtId;
                                    ?>
                                    <option value="<?= $districtId ?>" <?= $isSelected ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($districtName) ?> (<?= $candidateCountInDistrict ?>)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div>
                        <label for="party_id_filter" class="block text-sm font-medium text-gray-700 mb-2">Partij</label>
                        <select id="party_id_filter" name="party_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm">
                            <option value="">Alle Partijen</option>
                            <?php foreach ($parties as $party): ?>
                                <option value="<?= $party['PartyID'] ?>" <?= isset($_GET['party_id']) && $_GET['party_id'] == $party['PartyID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($party['PartyName']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="candidate_type_filter" class="block text-sm font-medium text-gray-700 mb-2">Kandidaat Type</label>
                        <select id="candidate_type_filter" name="candidate_type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-suriname-green focus:border-suriname-green sm:text-sm">
                            <option value="">Alle Types</option>
                            <option value="DNA" <?= isset($_GET['candidate_type']) && $_GET['candidate_type'] == 'DNA' ? 'selected' : '' ?>>DNA</option>
                            <option value="RR" <?= isset($_GET['candidate_type']) && $_GET['candidate_type'] == 'RR' ? 'selected' : '' ?>>RR</option>
                        </select>
                    </div>
                    <div class="col-span-1 md:col-span-3 flex justify-end mt-4">
                        <a href="candidates.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-times mr-2"></i>Reset Filters
                        </a>
                        <button type="submit" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-suriname-green hover:bg-suriname-dark-green">
                            <i class="fas fa-filter mr-2"></i>Toepassen
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Candidates Table Start -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Alle Kandidaten</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Foto</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Naam</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partij</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verkiezing</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">District</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resort</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Stemmen</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($candidates)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-3 py-8">
                                        <svg class="h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 12.75zM12 15.75h.008v.008H12v-.008z" />
                                        </svg>
                                        <h3 class="text-lg font-semibold text-gray-800">
                                            Geen kandidaten gevonden
                                        </h3>
                                        <p class="text-sm text-gray-500">
                                            Pas uw filters aan of voeg een nieuwe kandidaat toe.
                                        </p>
                                        <button id="openNewCandidateModalBtnEmptyState"
                                                class="mt-2 inline-flex items-center justify-center rounded-md border border-transparent bg-suriname-green px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-suriname-dark-green focus:outline-none focus:ring-2 focus:ring-suriname-green focus:ring-offset-2">
                                            <i class="fas fa-plus mr-2"></i>Nieuwe Kandidaat
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($candidates as $candidate): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                            $imageSrc = BASE_URL . '/assets/images/placeholder_user.png'; // Default placeholder
                                            if (!empty($candidate['Photo']) && is_string($candidate['Photo'])) {
                                                $photoPath = ltrim($candidate['Photo'], '/');
                                                if (filter_var($candidate['Photo'], FILTER_VALIDATE_URL)) {
                                                    $imageSrc = $candidate['Photo'];
                                                } elseif (file_exists(__DIR__ . '/../../' . $photoPath)) {
                                                    $imageSrc = BASE_URL . '/' . $photoPath;
                                                }
                                            }
                                        ?>
                                        <img src="<?= htmlspecialchars($imageSrc) ?>"
                                             alt="<?= htmlspecialchars($candidate['Name'] ?? 'Kandidaat Foto') ?>"
                                             class="h-10 w-10 rounded-full object-cover">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($candidate['Name'] ?? 'N/A') ?></p>
                                        <p class="text-xs text-gray-500">ID: <?= htmlspecialchars($candidate['CandidateID'] ?? 'N/A') ?></p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm text-gray-700"><?= htmlspecialchars($candidate['PartyName'] ?? 'N/A') ?></p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm text-gray-700"><?= htmlspecialchars($candidate['ElectionName'] ?? 'N/A') ?></p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm text-gray-700"><?= htmlspecialchars($candidate['DistrictName'] ?? 'N/A') ?></p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm text-gray-700"><?= (isset($candidate['CandidateType']) && $candidate['CandidateType'] === 'RR') ? htmlspecialchars($candidate['ResortName'] ?? 'N/A') : '-' ?></p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="inline-flex rounded-full bg-opacity-10 py-1 px-3 text-sm font-medium <?= (isset($candidate['CandidateType']) && $candidate['CandidateType'] === 'DNA') ? 'bg-blue-500 text-blue-500' : 'bg-suriname-red text-suriname-red' ?>">
                                            <?= htmlspecialchars($candidate['CandidateType'] ?? 'N/A') ?>
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <p class="inline-flex rounded-full bg-purple-500 bg-opacity-10 py-1 px-3 text-sm font-medium text-purple-500">
                                            <?= number_format($candidate['vote_count'] ?? 0) ?>
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-3.5">
                                                <a href="<?= BASE_URL ?>/src/views/edit_candidate.php?id=<?= $candidate['CandidateID'] ?? '' ?>"
                                                   class="hover:text-suriname-green" title="Bewerken">
                                                    <i class="fas fa-edit fa-fw"></i>
                                                </a>
                                                <form action="<?= BASE_URL ?>/src/controllers/CandidateController.php" method="POST" class="inline-block">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="candidate_id" value="<?= $candidate['CandidateID'] ?? '' ?>">
                                                    <button type="submit" class="hover:text-suriname-red" title="Verwijderen"
                                                            onclick="return confirm('Weet u zeker dat u deze kandidaat wilt verwijderen?')">
                                                        <i class="fas fa-trash fa-fw"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Start -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-6 flex items-center justify-between border-t border-stroke dark:border-strokedark pt-5">
                        <div class="text-sm text-gray-700">
                            Pagina <span class="font-medium"><?= $page ?></span> van <span class="font-medium"><?= $total_pages ?></span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <?php
                            $queryParams = [];
                            if (!empty($filters['district_id'])) $queryParams['district_id'] = $filters['district_id'];
                            if (!empty($filters['party_id'])) $queryParams['party_id'] = $filters['party_id'];
                            if (!empty($filters['candidate_type'])) $queryParams['candidate_type'] = $filters['candidate_type'];
                            $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
                            ?>
                            <a href="?page=1<?= $queryString ?>"
                               class="rounded-md px-3 py-2 leading-tight text-gray-700 bg-white border border-stroke hover:bg-gray-100 <?= ($page <= 1) ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                <i class="fas fa-angle-double-left text-xs"></i>
                            </a>
                            <a href="?page=<?= max(1, $page - 1) ?><?= $queryString ?>"
                               class="rounded-md px-3 py-2 leading-tight text-gray-700 bg-white border border-stroke hover:bg-gray-100 <?= ($page <= 1) ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                <i class="fas fa-angle-left text-xs"></i> Vorige
                            </a>
                            
                            <a href="?page=<?= min($total_pages, $page + 1) ?><?= $queryString ?>"
                               class="rounded-md px-3 py-2 leading-tight text-gray-700 bg-white border border-stroke hover:bg-gray-100 <?= ($page >= $total_pages) ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                Volgende <i class="fas fa-angle-right text-xs"></i>
                            </a>
                            <a href="?page=<?= $total_pages ?><?= $queryString ?>"
                               class="rounded-md px-3 py-2 leading-tight text-gray-700 bg-white border border-stroke hover:bg-gray-100 <?= ($page >= $total_pages) ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                <i class="fas fa-angle-double-right text-xs"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                <!-- Pagination End -->
            </div>
        </div>
        <!-- Candidates Table End -->
</div>

<!-- New Candidate Modal Start -->
<div id="newCandidateModal" class="hidden fixed z-[100] inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true"></div>

        <!-- Modal panel -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-boxdark rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="<?= BASE_URL ?>/src/controllers/CandidateController.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="bg-white dark:bg-boxdark px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-suriname-green bg-opacity-10 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-user-plus text-suriname-green text-xl"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-800" id="modal-title">
                                Nieuwe Kandidaat Toevoegen
                            </h3>
                            <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                <div>
                                    <label for="new_firstName" class="mb-2.5 block text-gray-700">Voornaam</label>
                                    <input type="text" name="firstName" id="new_firstName" required placeholder="John"
                                           class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-suriname-green active:border-suriname-green disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-suriname-green">
                                </div>
                                <div>
                                    <label for="new_lastName" class="mb-2.5 block text-gray-700">Achternaam</label>
                                    <input type="text" name="lastName" id="new_lastName" required placeholder="Doe"
                                           class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-suriname-green active:border-suriname-green disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-suriname-green">
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="new_party_id" class="mb-2.5 block text-gray-700">Partij</label>
                                    <select name="party_id" id="new_party_id" required
                                            class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-suriname-green active:border-suriname-green dark:border-form-strokedark dark:bg-form-input dark:focus:border-suriname-green">
                                        <option value="">Selecteer een partij</option>
                                        <?php foreach ($parties as $party): ?>
                                            <option value="<?= $party['PartyID'] ?>">
                                                <?= htmlspecialchars($party['PartyName']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="new_election_id" class="mb-2.5 block text-gray-700">Verkiezing</label>
                                    <select name="election_id" id="new_election_id" required
                                            class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-suriname-green active:border-suriname-green dark:border-form-strokedark dark:bg-form-input dark:focus:border-suriname-green">
                                        <option value="">Selecteer een verkiezing</option>
                                        <?php foreach ($elections as $election): ?>
                                            <option value="<?= $election['ElectionID'] ?>">
                                                <?= htmlspecialchars($election['ElectionName']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="new_candidate_type" class="mb-2.5 block text-gray-700">Kandidaat Type</label>
                                    <select name="candidate_type" id="new_candidate_type" required
                                            class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-suriname-green active:border-suriname-green dark:border-form-strokedark dark:bg-form-input dark:focus:border-suriname-green">
                                        <option value="RR" selected>RR (Ressortraad)</option>
                                        <option value="DNA">DNA (De Nationale Assemblée)</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="new_district_id" class="mb-2.5 block text-gray-700">District</label>
                                    <select name="district_id" id="new_district_id" required
                                            class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-suriname-green active:border-suriname-green dark:border-form-strokedark dark:bg-form-input dark:focus:border-suriname-green">
                                        <option value="">Selecteer een district</option>
                                        <?php foreach ($districts as $district): ?>
                                            <option value="<?= $district['DistrictID'] ?>">
                                                <?= htmlspecialchars($district['DistrictName']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Resort selection for RR candidates - initially hidden -->
                                <div id="resort_selection_container" style="display: none;">
                                    <label for="new_resort_id" class="mb-2.5 block text-gray-700">Resort</label>
                                    <select name="resort_id" id="new_resort_id"
                                            class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-suriname-green active:border-suriname-green dark:border-form-strokedark dark:bg-form-input dark:focus:border-suriname-green">
                                        <option value="">Selecteer eerst een district</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="new_image" class="mb-2.5 block text-gray-700">Foto</label>
                                    <input type="file" name="image" id="new_image" accept="image/*"
                                           class="w-full cursor-pointer rounded-lg border-[1.5px] border-stroke bg-transparent font-medium outline-none transition file:mr-5 file:border-collapse file:cursor-pointer file:border-0 file:border-r file:border-solid file:border-stroke file:bg-whiter file:py-3 file:px-5 file:hover:bg-suriname-green file:hover:bg-opacity-10 focus:border-suriname-green active:border-suriname-green disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:file:border-form-strokedark dark:file:bg-white/30 dark:file:text-white dark:focus:border-suriname-green">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Optioneel. Maximaal 5MB. JPG, PNG of GIF.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-boxdarkdark px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-stroke dark:border-strokedark">
                    <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-suriname-green text-base font-medium text-white hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-boxdark focus:ring-suriname-green sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-150">
                        Opslaan
                    </button>
                    <button type="button" id="closeNewCandidateModalBtn"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-stroke shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-150">
                        Annuleren
                    </button>
                    <button type="button" id="closeNewCandidateModalBtn"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-stroke shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-suriname-green sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-150">
                        Sluiten
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- New Candidate Modal End -->

<!-- JavaScript for handling resort selection -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal handling
    const modal = document.getElementById('newCandidateModal');
    const openModalBtn = document.getElementById('openNewCandidateModalBtn');
    const openModalBtnEmptyState = document.getElementById('openNewCandidateModalBtnEmptyState');
    const closeModalBtn = document.getElementById('closeNewCandidateModalBtn');
    
    // Function to open modal
    function openModal() {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    
    // Function to close modal
    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
    
    // Event listeners for modal buttons
    if (openModalBtn) openModalBtn.addEventListener('click', openModal);
    if (openModalBtnEmptyState) openModalBtnEmptyState.addEventListener('click', openModal);
    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    
    // Get DOM elements for resort selection
    const candidateTypeSelect = document.getElementById('new_candidate_type');
    const resortContainer = document.getElementById('resort_selection_container');
    const districtSelect = document.getElementById('new_district_id');
    const resortSelect = document.getElementById('new_resort_id');
    
    // Show/hide resort selection based on candidate type
    function toggleResortSelection() {
        if (candidateTypeSelect && candidateTypeSelect.value === 'RR') {
            if (resortContainer) resortContainer.style.display = 'block';
            if (resortSelect) resortSelect.setAttribute('required', 'required');
        } else {
            if (resortContainer) resortContainer.style.display = 'none';
            if (resortSelect) resortSelect.removeAttribute('required');
        }
    }
    
    // Initialize on page load
    if (candidateTypeSelect) {
        toggleResortSelection();
        
        // Add event listener for candidate type change
        candidateTypeSelect.addEventListener('change', toggleResortSelection);
    }
    
    // Load resorts when district changes
    if (districtSelect && resortSelect) {
        districtSelect.addEventListener('change', function() {
            const districtId = this.value;
            if (districtId) {
                // Clear current options
                resortSelect.innerHTML = '<option value="">Laden...</option>';
                
                // Fetch resorts for the selected district
                fetch(`${BASE_URL}/src/api/get_resorts.php?district_id=${districtId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.resorts && data.resorts.length > 0) {
                            resortSelect.innerHTML = '<option value="">Selecteer een resort</option>';
                            
                            data.resorts.forEach(resort => {
                                const option = document.createElement('option');
                                option.value = resort.ResortID;
                                option.textContent = resort.ResortName;
                                resortSelect.appendChild(option);
                            });
                        } else {
                            resortSelect.innerHTML = '<option value="">Geen resorts gevonden</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        resortSelect.innerHTML = '<option value="">Fout bij het laden van resorts</option>';
                    });
            } else {
                resortSelect.innerHTML = '<option value="">Selecteer eerst een district</option>';
            }
        });
    }
    
    // Filter form auto-submit
    const filterForm = document.getElementById('filterForm');
    const filterSelects = filterForm ? filterForm.querySelectorAll('select') : [];
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            filterForm.submit();
        });
    });
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../admin/components/layout.php';
?>
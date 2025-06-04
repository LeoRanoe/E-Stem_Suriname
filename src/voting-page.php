<?php
session_start();
$host = 'localhost';
$db   = 'e-stem_suriname';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch current election
    $stmt = $pdo->query("SELECT ElectionID, ElectionName FROM elections WHERE Status = 'active' LIMIT 1");
    $election = $stmt->fetch();
    if (!$election) die("Geen actieve verkiezing gevonden.");
    $electionID = $election['ElectionID'];

    // Fetch DNA candidates
    $stmtDNA = $pdo->prepare("
        SELECT c.CandidateID, c.Name, p.PartyName, d.DistrictName, c.Photo, c.PartyID, c.DistrictID 
        FROM candidates c
        JOIN parties p ON c.PartyID = p.PartyID
        JOIN districten d ON c.DistrictID = d.DistrictID
        WHERE c.CandidateType = 'DNA' AND c.ElectionID = ?
    ");
    $stmtDNA->execute([$electionID]);
    $dnaCandidates = $stmtDNA->fetchAll(PDO::FETCH_ASSOC);

    // Fetch RR candidates
    $stmtRR = $pdo->prepare("
        SELECT c.CandidateID, c.Name, p.PartyName, d.DistrictName, c.Photo, c.PartyID, c.DistrictID 
        FROM candidates c
        JOIN parties p ON c.PartyID = p.PartyID
        JOIN districten d ON c.DistrictID = d.DistrictID
        WHERE c.CandidateType = 'RR' AND c.ElectionID = ?
    ");
    $stmtRR->execute([$electionID]);
    $rrCandidates = $stmtRR->fetchAll(PDO::FETCH_ASSOC);

    // Fetch political parties
    $stmtParties = $pdo->query("SELECT PartyID, PartyName FROM parties ORDER BY PartyName ASC");
    $parties = $stmtParties->fetchAll(PDO::FETCH_ASSOC);

    // Fetch districts
    $stmtDistricts = $pdo->query("SELECT DistrictID, DistrictName FROM districten ORDER BY DistrictName ASC");
    $districts = $stmtDistricts->fetchAll(PDO::FETCH_ASSOC);

    // Get user name
    $userFullName = "Gast";
    if (isset($_SESSION['UserID'])) {
        $stmtUser = $pdo->prepare("SELECT Voornaam, Achternaam FROM users WHERE UserID = ?");
        $stmtUser->execute([$_SESSION['UserID']]);
        $user = $stmtUser->fetch();
        if ($user) {
            $userFullName = $user['Voornaam'] . " " . $user['Achternaam'];
        }
    }

} catch (PDOException $e) {
    die("Er is een fout opgetreden bij het ophalen van kandidaten: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="UTF-8" />
  <title>Stemmen - e-Stem Suriname</title>
  <!-- Tailwind CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <style>
    body { 
      background-color: #f8fafc; 
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
    }
    
    .candidate-card { 
      transition: all 0.2s ease-in-out;
      border: 2px solid transparent;
      background: white;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .candidate-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      border-color: #e5e7eb;
    }
    
    .candidate-card.selected { 
      border: 2px solid #059669; 
      background-color: #ecfdf5;
      box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
    }
    
    .submit-button { 
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      color: white; 
      padding: 12px 32px; 
      border-radius: 8px; 
      font-weight: 600;
      font-size: 16px;
      transition: all 0.2s ease-in-out;
      border: none;
      cursor: pointer;
    }
    
    .submit-button:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: 0 8px 25px rgba(5, 150, 105, 0.3);
    }
    
    .submit-button:disabled {
      background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }
    
    .section-divider { 
      border-bottom: 2px solid #e5e7eb; 
      margin: 32px 0; 
    }
    
    .nav-pill { 
      border-radius: 24px; 
      padding: 8px 16px; 
      font-size: 14px; 
      font-weight: 500;
      background-color: #f3f4f6;
      color: #6b7280;
      text-decoration: none;
      transition: all 0.2s ease-in-out;
    }
    
    .nav-pill:hover {
      background-color: #e5e7eb;
    }
    
    .nav-pill.active { 
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      color: white;
      box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3);
    }
    
    .filter-container {
      background: white;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .filter-select {
      border: 2px solid #e5e7eb;
      border-radius: 8px;
      padding: 8px 12px;
      font-size: 14px;
      background: white;
      transition: border-color 0.2s ease-in-out;
    }
    
    .filter-select:focus {
      outline: none;
      border-color: #059669;
      box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
    }
    
    .reset-filters {
      color: #059669;
      text-decoration: underline;
      font-size: 14px;
      background: none;
      border: none;
      cursor: pointer;
      transition: color 0.2s ease-in-out;
    }
    
    .reset-filters:hover {
      color: #047857;
    }
    
    .section-title {
      color: #047857;
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 20px;
    }
    
    .welcome-section {
      background: white;
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 32px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      border: 1px solid #e5e7eb;
    }
    
    .candidate-photo {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #e5e7eb;
      transition: border-color 0.2s ease-in-out;
    }
    
    .candidate-card.selected .candidate-photo {
      border-color: #059669;
    }
    
    .login-button {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
    }
    
    .login-button:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
    }
    
    .logout-button {
      background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
    }
    
    .logout-button:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }
    
    .modal-overlay {
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
    }
    
    .modal-content {
      background: white;
      border-radius: 16px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      border: 1px solid #e5e7eb;
    }
    
    .confirm-button {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
    }
    
    .confirm-button:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(5, 150, 105, 0.3);
    }
    
    .cancel-button {
      background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease-in-out;
    }
    
    .cancel-button:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(107, 114, 128, 0.3);
    }
  </style>
</head>
<body>
<!-- Header -->
<header class="bg-white shadow-sm py-4 mb-6">
  <div class="container mx-auto px-4 flex justify-between items-center max-w-6xl">
    <div class="flex space-x-2">
      <a href="#" class="nav-pill">Overzicht</a>
      <a href="#" class="nav-pill">Resultaten</a>
      <a href="#" class="nav-pill active">Stemmen</a>
    </div>
    <?php if (!isset($_SESSION['UserID'])): ?>
    <button class="login-button">Log in</button>
    <?php else: ?>
    <button class="logout-button">Log uit</button>
    <?php endif; ?>
  </div>
</header>

<main class="container mx-auto px-4 py-2 max-w-6xl">
  <!-- Welcome Message -->
  <section class="welcome-section text-center">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Welkom, <?= htmlspecialchars($userFullName) ?>.</h1>
    <p class="text-gray-600">U heeft nog niet gestemd. Vergeet niet dat u slechts één keer kunt stemmen.</p>
  </section>

  <!-- DNA Section -->
  <section class="mb-12">
    <h2 class="section-title">De Nationale Assemblée</h2>
    <div class="filter-container mb-6">
      <div class="text-base mb-4 font-semibold text-gray-700">Filter Opties</div>
      <div class="flex flex-wrap gap-6 items-end">
        <div class="flex flex-col">
          <label class="text-sm mb-2 font-medium text-gray-700">Politieke partijen</label>
          <select id="dna-party-filter" class="filter-select min-w-48">
            <option value="">Alle partijen</option>
            <?php foreach ($parties as $party): ?>
              <option value="<?= htmlspecialchars($party['PartyID']) ?>">
                <?= htmlspecialchars($party['PartyName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex flex-col">
          <label class="text-sm mb-2 font-medium text-gray-700">Districten</label>
          <select id="dna-district-filter" class="filter-select min-w-48">
            <option value="">Alle districten</option>
            <?php foreach ($districts as $district): ?>
              <option value="<?= htmlspecialchars($district['DistrictID']) ?>">
                <?= htmlspecialchars($district['DistrictName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button onclick="resetFilters()" type="button" class="reset-filters">Herstel filters</button>
      </div>
    </div>
    <h3 class="font-semibold mb-4 text-lg text-gray-800">Kandidaten</h3>
    <div id="dna-candidates" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php if (!empty($dnaCandidates)): ?>
        <?php foreach ($dnaCandidates as $candidate): ?>
          <div class="candidate-card p-4 flex items-center gap-4 rounded-xl cursor-pointer dna-card"
               data-party="<?= htmlspecialchars($candidate['PartyID']) ?>"
               data-district="<?= htmlspecialchars($candidate['DistrictID']) ?>"
               data-candidate-id="<?= htmlspecialchars($candidate['CandidateID']) ?>"
               onclick="selectCandidate(this, 'dna')">
            <img src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : 'https://via.placeholder.com/60' ?>" alt="<?= htmlspecialchars($candidate['Name']) ?>" class="candidate-photo">
            <div class="flex-1">
              <h3 class="font-bold text-gray-800 text-base"><?= htmlspecialchars($candidate['Name']) ?></h3>
              <p class="text-sm text-gray-600 mb-1"><?= htmlspecialchars($candidate['PartyName']) ?></p>
              <p class="text-sm text-gray-500"><?= htmlspecialchars($candidate['DistrictName']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>Geen DNA-kandidaten beschikbaar voor deze verkiezing.</p>
      <?php endif; ?>
    </div>
    <div class="section-divider"></div>
  </section>

  <!-- RR Section -->
  <section class="mb-12">
    <h2 class="section-title">Resortsraden</h2>
    <div class="filter-container mb-6">
      <div class="text-base mb-4 font-semibold text-gray-700">Filter Opties</div>
      <div class="flex flex-wrap gap-6 items-end">
        <div class="flex flex-col">
          <label class="text-sm mb-2 font-medium text-gray-700">Politieke partijen</label>
          <select id="rr-party-filter" class="filter-select min-w-48">
            <option value="">Alle partijen</option>
            <?php foreach ($parties as $party): ?>
              <option value="<?= htmlspecialchars($party['PartyID']) ?>">
                <?= htmlspecialchars($party['PartyName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex flex-col">
          <label class="text-sm mb-2 font-medium text-gray-700">Districten</label>
          <select id="rr-district-filter" class="filter-select min-w-48">
            <option value="">Alle districten</option>
            <?php foreach ($districts as $district): ?>
              <option value="<?= htmlspecialchars($district['DistrictID']) ?>">
                <?= htmlspecialchars($district['DistrictName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button onclick="resetFilters()" type="button" class="reset-filters">Herstel filters</button>
      </div>
    </div>
    <h3 class="font-semibold mb-4 text-lg text-gray-800">Kandidaten</h3>
    <div id="rr-candidates" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <?php if (!empty($rrCandidates)): ?>
        <?php foreach ($rrCandidates as $candidate): ?>
          <div class="candidate-card p-4 flex items-center gap-4 rounded-xl cursor-pointer rr-card"
               data-party="<?= htmlspecialchars($candidate['PartyID']) ?>"
               data-district="<?= htmlspecialchars($candidate['DistrictID']) ?>"
               data-candidate-id="<?= htmlspecialchars($candidate['CandidateID']) ?>"
               onclick="selectCandidate(this, 'rr')">
            <img src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : 'https://via.placeholder.com/60' ?>" alt="<?= htmlspecialchars($candidate['Name']) ?>" class="candidate-photo">
            <div class="flex-1">
              <h3 class="font-bold text-gray-800 text-base"><?= htmlspecialchars($candidate['Name']) ?></h3>
              <p class="text-sm text-gray-600 mb-1"><?= htmlspecialchars($candidate['PartyName']) ?></p>
              <p class="text-sm text-gray-500"><?= htmlspecialchars($candidate['DistrictName']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>Geen RR-kandidaten beschikbaar voor deze verkiezing.</p>
      <?php endif; ?>
    </div>
    <div class="section-divider"></div>
  </section>

  <!-- Submit Button -->
  <section class="text-center mb-12">
    <p class="mb-2 text-base text-gray-700">Bent u <span class="text-green-700 font-bold">zeker</span> van uw keuze? <span class="text-green-700 font-semibold">Dien dan uw stem in</span>.</p>
    <p class="text-red-600 mb-6 text-sm font-medium">Let op: u kunt slechts één keer stemmen.</p>
    <div class="flex justify-center">
      <button id="submitBtn" onclick="submitVote()" class="submit-button" disabled>
        Indienen
      </button>
    </div>
  </section>
</main>

<!-- Modal Popup -->
<div id="popup-modal" class="fixed inset-0 hidden modal-overlay flex items-center justify-center z-50 p-4">
  <div class="modal-content w-full max-w-lg p-8">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Geselecteerde Kandidaten</h2>
    <div id="selected-candidates" class="space-y-6"></div>
    <div class="mt-8 flex justify-between gap-4">
      <button onclick="closePopup()" class="cancel-button flex-1">Annuleren</button>
      <button onclick="confirmVote()" class="confirm-button flex-1">Bevestigen</button>
    </div>
  </div>
</div>

<script>
  let selectedDNA = null;
  let selectedRR = null;
  let selectedDNAId = null;
  let selectedRRId = null;

  function selectCandidate(card, type) {
    const cards = document.querySelectorAll(`.${type === 'dna' ? 'dna-card' : 'rr-card'}`);
    cards.forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    if (type === 'dna') {
      selectedDNA = card;
      selectedDNAId = card.getAttribute('data-candidate-id');
    } else {
      selectedRR = card;
      selectedRRId = card.getAttribute('data-candidate-id');
    }
    updateSubmitButton();
  }

  function updateSubmitButton() {
    const submitBtn = document.getElementById('submitBtn');
    if (selectedDNA || selectedRR) {
      submitBtn.disabled = false;
    } else {
      submitBtn.disabled = true;
    }
  }

  function submitVote() {
    const container = document.getElementById('selected-candidates');
    container.innerHTML = '';
    if (selectedDNA) {
      const dnaTitle = document.createElement('h3');
      dnaTitle.className = 'font-bold text-lg text-gray-800 mb-3'; 
      dnaTitle.textContent = 'De Nationale Assemblée';
      container.appendChild(dnaTitle);
      const dnaClone = selectedDNA.cloneNode(true);
      dnaClone.classList.remove('cursor-pointer'); 
      dnaClone.onclick = null;
      container.appendChild(dnaClone);
    }
    if (selectedRR) {
      const rrTitle = document.createElement('h3');
      rrTitle.className = 'font-bold text-lg text-gray-800 mb-3 mt-6'; 
      rrTitle.textContent = 'Resortsraden';
      container.appendChild(rrTitle);
      const rrClone = selectedRR.cloneNode(true);
      rrClone.classList.remove('cursor-pointer'); 
      rrClone.onclick = null;
      container.appendChild(rrClone);
    }
    document.getElementById('popup-modal').classList.remove('hidden');
  }

  function closePopup() {
    document.getElementById('popup-modal').classList.add('hidden');
  }

  function confirmVote() {
    alert("Bedankt voor uw stem!");
    closePopup();
    document.querySelectorAll('.candidate-card').forEach(card => {
      card.classList.remove('cursor-pointer');
      card.onclick = null;
    });
    document.getElementById('submitBtn').disabled = true;
  }

  function filterDNACandidates() {
    const partyFilter = document.getElementById('dna-party-filter').value;
    const districtFilter = document.getElementById('dna-district-filter').value;
    document.querySelectorAll('.dna-card').forEach(card => {
      const partyId = card.getAttribute('data-party') || '';
      const districtId = card.getAttribute('data-district') || '';
      const matchesParty = partyFilter === '' || partyId === partyFilter;
      const matchesDistrict = districtFilter === '' || districtId === districtFilter;
      card.style.display = (matchesParty && matchesDistrict) ? '' : 'none';
    });
  }

  function filterRRCandidates() {
    const partyFilter = document.getElementById('rr-party-filter').value;
    const districtFilter = document.getElementById('rr-district-filter').value;
    document.querySelectorAll('.rr-card').forEach(card => {
      const partyId = card.getAttribute('data-party') || '';
      const districtId = card.getAttribute('data-district') || '';
      const matchesParty = partyFilter === '' || partyId === partyFilter;
      const matchesDistrict = districtFilter === '' || districtId === districtFilter;
      card.style.display = (matchesParty && matchesDistrict) ? '' : 'none';
    });
  }

  function resetFilters() {
    document.getElementById('dna-party-filter').value = '';
    document.getElementById('dna-district-filter').value = '';
    document.getElementById('rr-party-filter').value = '';
    document.getElementById('rr-district-filter').value = '';
    filterDNACandidates();
    filterRRCandidates();
  }

  // Attach event listeners
  document.getElementById('dna-party-filter').addEventListener('change', filterDNACandidates);
  document.getElementById('dna-district-filter').addEventListener('change', filterDNACandidates);
  document.getElementById('rr-party-filter').addEventListener('change', filterRRCandidates);
  document.getElementById('rr-district-filter').addEventListener('change', filterRRCandidates);
</script>
</body>
</html>
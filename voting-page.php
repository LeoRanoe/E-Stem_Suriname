<?php
// Start session at the top
session_start();

// Include database connection
$host = 'localhost';
$db   = 'e_stem_suriname';
$user = 'root'; // Change if needed
$pass = '';     // Usually empty in XAMPP
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch current election (active one)
    $stmt = $pdo->query("SELECT ElectionID, ElectionName FROM elections WHERE Status = 'active' LIMIT 1");
    $election = $stmt->fetch();

    if (!$election) {
        die("Geen actieve verkiezing gevonden.");
    }
    $electionID = $election['ElectionID'];

    // Fetch DNA candidates
    $stmtDNA = $pdo->prepare("
        SELECT c.CandidateID, c.Name, p.PartyName, p.Logo as PartyLogo, d.DistrictName, c.Photo 
        FROM candidates c
        JOIN parties p ON c.PartyID = p.PartyID
        JOIN districten d ON c.DistrictID = d.DistrictID
        WHERE c.CandidateType = 'DNA' AND c.ElectionID = ?
    ");
    $stmtDNA->execute([$electionID]);
    $dnaCandidates = $stmtDNA->fetchAll(PDO::FETCH_ASSOC);

    // Fetch RR candidates
    $stmtRR = $pdo->prepare("
        SELECT c.CandidateID, c.Name, p.PartyName, p.Logo as PartyLogo, d.DistrictName, c.Photo 
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

    // Get current user details if logged in
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
      background-color: #f9f9f9;
      font-family: Arial, sans-serif;
    }

    .candidate-card.selected {
      border: 2px solid #65a30d;
      background-color: #f0fdf4;
    }

    .vote-button {
      background-color: #4b7e3d;
      color: white;
      padding: 4px 10px;
      border-radius: 4px;
      font-size: 12px;
    }

    .submit-button {
      background-color: #4b7e3d;
      color: white;
      width: 100%;
      max-width: 200px;
      padding: 8px;
      border-radius: 4px;
      font-weight: bold;
    }

    .section-divider {
      border-bottom: 1px solid #d1d5db;
      margin: 20px 0;
    }

    .nav-pill {
      border-radius: 20px;
      padding: 2px 10px;
      font-size: 12px;
      background-color: #e5e7eb;
    }

    .nav-pill.active {
      background-color: #4b7e3d;
      color: white;
    }
    
    .header-container {
      background-color: #f0f0f0;
      border-radius: 20px;
      padding: 5px;
    }
  </style>
</head>
<body class="bg-gray-100">

<!-- Header -->
<header class="bg-gray-200 py-2 rounded-md mb-4 w-11/12 mx-auto mt-2">
  <div class="container mx-auto px-4 flex justify-between items-center">
    <div class="flex space-x-1">
      <a href="#" class="nav-pill">Overzicht</a>
      <a href="#" class="nav-pill">Resultaten</a>
      <a href="#" class="nav-pill active">Stemmen</a>
    </div>
    <?php if (!isset($_SESSION['UserID'])): ?>
    <button class="bg-green-600 hover:bg-green-700 text-white py-1 px-3 rounded-md text-sm">
      Log in
    </button>
    <?php else: ?>
    <button class="bg-red-600 hover:bg-red-700 text-white py-1 px-3 rounded-md text-sm">
      Log uit
    </button>
    <?php endif; ?>
  </div>
</header>

<main class="container mx-auto px-2 py-2 max-w-4xl">

  <!-- Welcome Message -->
  <section class="text-center mb-4">
    <h1 class="text-lg font-bold">Welkom, <?= htmlspecialchars($userFullName) ?>.</h1>
    <p class="text-sm">U heeft nog niet gestemd. Vergeet niet dat u slechts één keer kunt stemmen.</p>
    <div class="section-divider"></div>
  </section>

  <!-- DNA Section -->
  <section class="mb-6">
    <h2 class="text-lg font-bold text-green-800 mb-2">De Nationale Assemblée</h2>

    <!-- Filter Options -->
    <div class="bg-gray-100 p-3 mb-3 rounded">
      <div class="text-sm mb-1 font-medium">Filter Opties</div>
      <div class="flex flex-wrap gap-2 text-sm">
        <div class="flex flex-col">
          <label class="text-sm mb-1">Politieke partijen</label>
          <select id="dna-party-filter" class="border border-gray-300 rounded p-1 text-sm bg-white">
            <option value="">Alle partijen</option>
            <?php foreach ($parties as $party): ?>
              <option value="<?= htmlspecialchars($party['PartyID']) ?>">
                <?= htmlspecialchars($party['PartyName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex flex-col ml-4">
          <label class="text-sm mb-1">Districten</label>
          <select id="dna-district-filter" class="border border-gray-300 rounded p-1 text-sm bg-white">
            <option value="">Alle districten</option>
            <?php foreach ($districts as $district): ?>
              <option value="<?= htmlspecialchars($district['DistrictID']) ?>">
                <?= htmlspecialchars($district['DistrictName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <h3 class="font-bold mb-2 text-sm">Kandidaten</h3>

    <!-- Candidates Grid -->
    <div id="dna-candidates" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
      <?php if (!empty($dnaCandidates)): ?>
        <?php foreach ($dnaCandidates as $candidate): ?>
          <div class="p-2 flex items-center gap-2 bg-gray-200 rounded-md cursor-pointer dna-card candidate-card" 
               data-party="<?= htmlspecialchars($candidate['PartyID'] ?? '') ?>"
               data-district="<?= htmlspecialchars($candidate['DistrictID'] ?? '') ?>"
               data-candidate-id="<?= htmlspecialchars($candidate['CandidateID']) ?>"
               onclick="selectCandidate(this, 'dna')">
            <img src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : 'https://via.placeholder.com/60' ?>" 
                 alt="<?= htmlspecialchars($candidate['Name']) ?>" 
                 class="w-12 h-12 object-cover rounded-full">
            <div class="text-sm">
              <h3 class="font-bold"><?= htmlspecialchars($candidate['Name']) ?></h3>
              <p class="text-xs text-gray-600"><?= htmlspecialchars($candidate['PartyName']) ?></p>
              <p class="text-xs text-gray-600"><?= htmlspecialchars($candidate['DistrictName']) ?></p>
              <button class="vote-button mt-1">Stem</button>
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
  <section class="mb-6">
    <h2 class="text-lg font-bold text-green-800 mb-2">Resortsraden</h2>

    <!-- Filter Options -->
    <div class="bg-gray-100 p-3 mb-3 rounded">
      <div class="text-sm mb-1 font-medium">Filter Opties</div>
      <div class="flex flex-wrap gap-2 text-sm">
        <div class="flex flex-col">
          <label class="text-sm mb-1">Politieke partijen</label>
          <select id="rr-party-filter" class="border border-gray-300 rounded p-1 text-sm bg-white">
            <option value="">Alle partijen</option>
            <?php foreach ($parties as $party): ?>
              <option value="<?= htmlspecialchars($party['PartyID']) ?>">
                <?= htmlspecialchars($party['PartyName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="flex flex-col ml-2">
          <label class="text-sm mb-1">Districten</label>
          <select id="rr-district-filter" class="border border-gray-300 rounded p-1 text-sm bg-white">
            <option value="">Alle districten</option>
            <?php foreach ($districts as $district): ?>
              <option value="<?= htmlspecialchars($district['DistrictID']) ?>">
                <?= htmlspecialchars($district['DistrictName']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <h3 class="font-bold mb-2 text-sm">Kandidaten</h3>

    <!-- Candidates Grid -->
    <div id="rr-candidates" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
      <?php if (!empty($rrCandidates)): ?>
        <?php foreach ($rrCandidates as $candidate): ?>
          <div class="p-2 flex items-center gap-2 bg-gray-200 rounded-md cursor-pointer rr-card candidate-card"
               data-party="<?= htmlspecialchars($candidate['PartyID'] ?? '') ?>"
               data-district="<?= htmlspecialchars($candidate['DistrictID'] ?? '') ?>"
               data-candidate-id="<?= htmlspecialchars($candidate['CandidateID']) ?>"
               onclick="selectCandidate(this, 'rr')">
            <img src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : 'https://via.placeholder.com/60' ?>" 
                 alt="<?= htmlspecialchars($candidate['Name']) ?>" 
                 class="w-12 h-12 object-cover rounded-full">
            <div class="text-sm">
              <h3 class="font-bold"><?= htmlspecialchars($candidate['Name']) ?></h3>
              <p class="text-xs text-gray-600"><?= htmlspecialchars($candidate['PartyName']) ?></p>
              <p class="text-xs text-gray-600"><?= htmlspecialchars($candidate['DistrictName']) ?></p>
              <button class="vote-button mt-1">Stem</button>
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
  <section class="text-center mb-6">
    <p class="mb-1 text-sm">Bent u <span class="text-green-700 font-bold">zeker</span> van uw keuze? <span class="text-green-700">Dien dan uw stem in</span>.</p>
    <p class="text-red-600 mb-3 text-sm">Let op: u kunt slechts één keer stemmen.</p>
    <div class="flex justify-center">
      <button id="submitBtn" onclick="submitVote()" class="bg-green-700 text-white py-2 px-4 rounded opacity-50 cursor-not-allowed" disabled>
        Indienen
      </button>
    </div>
  </section>

</main>

<!-- Modal Popup -->
<div id="popup-modal" class="fixed inset-0 hidden bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-md w-full max-w-md p-6">
    <h2 class="text-xl font-bold mb-4">Geselecteerde Kandidaten</h2>
    <div id="selected-candidates" class="space-y-4"></div>
    <div class="mt-6 flex justify-between">
      <button onclick="closePopup()" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
        Annuleren
      </button>
      <button onclick="confirmVote()" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
        Bevestigen
      </button>
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
      submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
      submitBtn.disabled = false;
    } else {
      submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
      submitBtn.disabled = true;
    }
  }

  function submitVote() {
    const container = document.getElementById('selected-candidates');
    container.innerHTML = '';

    // Add title for DNA selection
    if (selectedDNA) {
      const dnaTitle = document.createElement('h3');
      dnaTitle.classList.add('font-bold', 'mt-2');
      dnaTitle.textContent = 'De Nationale Assemblée';
      container.appendChild(dnaTitle);
      
      const dnaClone = selectedDNA.cloneNode(true);
      dnaClone.classList.remove('cursor-pointer');
      dnaClone.classList.add('bg-gray-100');
      dnaClone.onclick = null;
      container.appendChild(dnaClone);
    }

    // Add title for RR selection
    if (selectedRR) {
      const rrTitle = document.createElement('h3');
      rrTitle.classList.add('font-bold', 'mt-4');
      rrTitle.textContent = 'Resortsraden';
      container.appendChild(rrTitle);
      
      const rrClone = selectedRR.cloneNode(true);
      rrClone.classList.remove('cursor-pointer');
      rrClone.classList.add('bg-gray-100');
      rrClone.onclick = null;
      container.appendChild(rrClone);
    }

    // Show modal
    document.getElementById('popup-modal').classList.remove('hidden');
  }

  function closePopup() {
    document.getElementById('popup-modal').classList.add('hidden');
  }

  function confirmVote() {
    // Here you would send the vote data to the server
    const voteData = {
      dna_candidate_id: selectedDNAId,
      rr_candidate_id: selectedRRId,
      election_id: <?= json_encode($electionID) ?>
    };
    
    // For now, just log the data and close the modal
    console.log("Vote data:", voteData);
    
    // In a real implementation, you would use fetch or XMLHttpRequest to send this data to a PHP endpoint
    // that would record the vote in the database
    
    alert("Bedankt voor uw stem!");
    closePopup();
    
    // Disable all interaction after voting
    document.querySelectorAll('.candidate-card').forEach(card => {
      card.classList.remove('cursor-pointer');
      card.onclick = null;
    });
    
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').classList.add('opacity-50', 'cursor-not-allowed');
  }
  
  // Filtering functionality
  document.getElementById('dna-party-filter').addEventListener('change', filterDNACandidates);
  document.getElementById('dna-district-filter').addEventListener('change', filterDNACandidates);
  document.getElementById('rr-party-filter').addEventListener('change', filterRRCandidates);
  document.getElementById('rr-district-filter').addEventListener('change', filterRRCandidates);
  
  function filterDNACandidates() {
    const partyFilter = document.getElementById('dna-party-filter').value;
    const districtFilter = document.getElementById('dna-district-filter').value;
    
    document.querySelectorAll('.dna-card').forEach(card => {
      const partyMatch = !partyFilter || card.getAttribute('data-party') === partyFilter;
      const districtMatch = !districtFilter || card.getAttribute('data-district') === districtFilter;
      
      if (partyMatch && districtMatch) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  }
  
  function filterRRCandidates() {
    const partyFilter = document.getElementById('rr-party-filter').value;
    const districtFilter = document.getElementById('rr-district-filter').value;
    
    document.querySelectorAll('.rr-card').forEach(card => {
      const partyMatch = !partyFilter || card.getAttribute('data-party') === partyFilter;
      const districtMatch = !districtFilter || card.getAttribute('data-district') === districtFilter;
      
      if (partyMatch && districtMatch) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
      }
    });
  }
</script>

</body>
</html>
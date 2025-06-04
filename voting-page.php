<?php
session_start();
$host = 'localhost';
$db   = 'e_stem_suriname';
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
   <style>
  .candidate-card { 
    transition: all 0.2s ease-in-out;
    border: 2px solid #e5e7eb; /* Light gray border */
    background: white;
    border-radius: 12px; /* Rounded corners */
    padding: 16px; /* Increased padding for better spacing */
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
  }
  
  .candidate-card:hover {
    transform: translateY(-2px); /* Slightly more pronounced hover effect */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Enhanced shadow on hover */
  }
  
  .candidate-card.selected {
    border: 2px solid #10b981; /* Green border for selected state */
    background-color: #ecfdf5; /* Light green background for selected state */
  }
    .submit-button {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
      box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
    }
    
    .submit-button:disabled {
      background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
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
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
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


    /* Remove the default hover border effect */
select {
  border-color: #d1d5db; /* Set the default border color */
}
/* On hover, set the border color to transparent */
select:hover {
  border-color: transparent; /* Remove the gray hover border */
}
/* On focus, apply the green border */
select:focus {
  border-color: #10b981; /* Green border on focus */
  outline: none; /* Remove the default outline */
  box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3); /* Optional: Add a shadow effect */
}

.Filter-break-line {
  width: 100%; /* Make the line take the full width of the parent */
  height: 2px; /* Height of the line */
  background-color: #10b981; /* Green color */
  margin-top: 4px; /* Space between the text and the line */
  border-radius: 1px; /* Slightly rounded edges */
}

.filter-container {
  background-color: #f7f7f7; /* Light gray background */
  padding: 16px; /* Increased padding for better spacing */
  border-radius: 12px; /* Rounded corners */
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
}

  </style>
</head>
<body class="bg-gray-50 min-h-screen">

<!-- Header -->
<header class="bg-white border-b border-gray-200">
  <div class="max-w-4xl mx-auto px-4 py-4">
    <div class="flex justify-between items-center">
      <!-- Navigation -->
      <nav class="flex space-x-1">
        <a href="#" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100">Overzicht</a>
        <a href="#" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 rounded-lg hover:bg-gray-100">Resultaten</a>
        <a href="#" class="px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-lg">Stemmen</a>
      </nav>
      
      <!-- Login/Logout Button -->
      <?php if (!isset($_SESSION['UserID'])): ?>
      <button class="px-4 py-2 text-sm font-medium text-white bg-green-500 rounded-lg hover:bg-green-600 transition-colors">
        Log in
      </button>
      <?php else: ?>
      <button class="px-4 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 transition-colors">
        Log uit
      </button>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="max-w-4xl mx-auto px-4 py-6">
  <!-- Welcome Message -->
  <div class="text-center mb-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-2">
      Welkom, <span class="text-green-600"><?= htmlspecialchars($userFullName) ?></span>.
    </h1>
    <p class="text-gray-600">
      U heeft nog niet gestemd. Vergeet niet dat u slechts één keer kunt stemmen.
    </p>
  </div>

  <!-- DNA Section -->
  <section class="mb-12">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h2 class="text-xl font-bold text-green-600 mb-6">De Nationale Assemblée</h2>
      

    
<!-- Filter Options -->
<div class="filter-container mb-6">
  <h3 class="text-sm font-semibold text-gray-700 mb-4">Filter Opties</h3>
  <div class="Filter-break-line"></div>
  
  <!-- New container for filter dropdowns -->
  <div class="border border-gray-300 rounded-lg p-4 mt-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Politieke partijen</label>
        <select id="dna-party-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
          <option value="">Alle partijen</option>
          <?php foreach ($parties as $party): ?>
            <option value="<?= htmlspecialchars($party['PartyID']) ?>">
              <?= htmlspecialchars($party['PartyName']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Districten</label>
        <select id="dna-district-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
          <option value="">Alle districten</option>
          <?php foreach ($districts as $district): ?>
            <option value="<?= htmlspecialchars($district['DistrictID']) ?>">
              <?= htmlspecialchars($district['DistrictName']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <button onclick="resetFilters()" type="button" class="text-sm text-green-600 hover:text-green-700 font-medium underline">
          Herstel filters
        </button>
      </div>
    </div>
  </div>
</div>



      <!-- Candidates -->
      <div>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Kandidaten</h3>
        <div id="dna-candidates" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <?php if (!empty($dnaCandidates)): ?>
            <?php foreach ($dnaCandidates as $candidate): ?>
              <div class="candidate-card border border-gray-200 rounded-lg p-4 cursor-pointer dna-card hover:shadow-md"
                   data-party="<?= htmlspecialchars($candidate['PartyID']) ?>"
                   data-district="<?= htmlspecialchars($candidate['DistrictID']) ?>"
                   data-candidate-id="<?= htmlspecialchars($candidate['CandidateID']) ?>"
                   onclick="selectCandidate(this, 'dna')">
                <div class="flex items-start space-x-4">
                  <img src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : 'https://via.placeholder.com/80' ?>" 
                       alt="<?= htmlspecialchars($candidate['Name']) ?>" 
                       class="w-20 h-20 rounded-lg object-cover border-2 border-gray-300 flex-shrink-0">
                  <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 leading-tight mb-1"><?= htmlspecialchars($candidate['Name']) ?></h4>
                    <p class="text-sm text-gray-600 mb-1"><?= htmlspecialchars($candidate['PartyName']) ?></p>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($candidate['DistrictName']) ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-gray-500 col-span-full text-center py-8">Geen DNA-kandidaten beschikbaar voor deze verkiezing.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- RR Section -->
  <section class="mb-12">
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h2 class="text-xl font-bold text-green-600 mb-6">Resortsraden</h2>
      
    <!-- Filter Options -->
<div class="filter-container mb-6">
  <h3 class="text-sm font-semibold text-gray-700 mb-4">Filter Opties</h3>
  <div class="Filter-break-line"></div>
  
  <!-- New container for filter dropdowns -->
  <div class="border border-gray-300 rounded-lg p-4 mt-4">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Politieke partijen</label>
        <select id="dna-party-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
          <option value="">Alle partijen</option>
          <?php foreach ($parties as $party): ?>
            <option value="<?= htmlspecialchars($party['PartyID']) ?>">
              <?= htmlspecialchars($party['PartyName']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Districten</label>
        <select id="dna-district-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
          <option value="">Alle districten</option>
          <?php foreach ($districts as $district): ?>
            <option value="<?= htmlspecialchars($district['DistrictID']) ?>">
              <?= htmlspecialchars($district['DistrictName']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <button onclick="resetFilters()" type="button" class="text-sm text-green-600 hover:text-green-700 font-medium underline">
          Herstel filters
        </button>
      </div>
    </div>
  </div>
</div>



      <!-- Candidates -->
      <div>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Kandidaten</h3>
        <div id="rr-candidates" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <?php if (!empty($rrCandidates)): ?>
            <?php foreach ($rrCandidates as $candidate): ?>
              <div class="candidate-card border border-gray-200 rounded-lg p-4 cursor-pointer rr-card hover:shadow-md"
                   data-party="<?= htmlspecialchars($candidate['PartyID']) ?>"
                   data-district="<?= htmlspecialchars($candidate['DistrictID']) ?>"
                   data-candidate-id="<?= htmlspecialchars($candidate['CandidateID']) ?>"
                   onclick="selectCandidate(this, 'rr')">
                <div class="flex items-start space-x-4">
                  <img src="<?= !empty($candidate['Photo']) ? htmlspecialchars($candidate['Photo']) : 'https://via.placeholder.com/80' ?>" 
                       alt="<?= htmlspecialchars($candidate['Name']) ?>" 
                       class="w-20 h-20 rounded-lg object-cover border-2 border-gray-300 flex-shrink-0">
                  <div class="flex-1">
                    <h4 class="font-semibold text-gray-900 leading-tight mb-1"><?= htmlspecialchars($candidate['Name']) ?></h4>
                    <p class="text-sm text-gray-600 mb-1"><?= htmlspecialchars($candidate['PartyName']) ?></p>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($candidate['DistrictName']) ?></p>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-gray-500 col-span-full text-center py-8">Geen RR-kandidaten beschikbaar voor deze verkiezing.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Submit Section -->
  <div class="text-center">
    <p class="text-gray-700 mb-2">
      Bent u <span class="font-bold text-green-600">zeker</span> van uw keuze? 
      <span class="font-semibold text-green-600">Dien dan uw stem in</span>.
    </p>
    <p class="text-red-600 mb-6 text-sm">
      <span class="font-semibold">Let op:</span> u kunt slechts <span class="font-bold">één keer</span> stemmen.
    </p>
    
    <button id="submitBtn" onclick="submitVote()" class="submit-button" disabled>
      Indienen
    </button>
  </div>
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
    
    console.log('DNA Filters - Party:', partyFilter, 'District:', districtFilter); // Debug log
    
    document.querySelectorAll('.dna-card').forEach(card => {
        const partyId = card.getAttribute('data-party') || '';
        const districtId = card.getAttribute('data-district') || '';
        
        console.log('Card - Party:', partyId, 'District:', districtId); // Debug log
        
        const matchesParty = partyFilter === '' || partyId === partyFilter;
        const matchesDistrict = districtFilter === '' || districtId === districtFilter;
        
        if (matchesParty && matchesDistrict) {
            card.style.display = '';
            card.classList.remove('hidden');
        } else {
            card.style.display = 'none';
            card.classList.add('hidden');
        }
    });
}

function filterRRCandidates() {
    const partyFilter = document.getElementById('rr-party-filter').value;
    const districtFilter = document.getElementById('rr-district-filter').value;
    
    console.log('RR Filters - Party:', partyFilter, 'District:', districtFilter); // Debug log
    
    document.querySelectorAll('.rr-card').forEach(card => {
        const partyId = card.getAttribute('data-party') || '';
        const districtId = card.getAttribute('data-district') || '';
        
        console.log('Card - Party:', partyId, 'District:', districtId); // Debug log
        
        const matchesParty = partyFilter === '' || partyId === partyFilter;
        const matchesDistrict = districtFilter === '' || districtId === districtFilter;
        
        if (matchesParty && matchesDistrict) {
            card.style.display = '';
            card.classList.remove('hidden');
        } else {
            card.style.display = 'none';
            card.classList.add('hidden');
        }
    });
}

function resetFilters() {
    document.getElementById('dna-party-filter').value = '';
    document.getElementById('dna-district-filter').value = '';
    document.getElementById('rr-party-filter').value = '';
    document.getElementById('rr-district-filter').value = '';
    
    // Toon alle kandidaten weer
    document.querySelectorAll('.dna-card, .rr-card').forEach(card => {
        card.style.display = '';
        card.classList.remove('hidden');
    });
}

  // Attach event listeners
  document.getElementById('dna-party-filter').addEventListener('change', filterDNACandidates);
  document.getElementById('dna-district-filter').addEventListener('change', filterDNACandidates);
  document.getElementById('rr-party-filter').addEventListener('change', filterRRCandidates);
  document.getElementById('rr-district-filter').addEventListener('change', filterRRCandidates);
</script>
</body>
</html>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Resultaten Stemmen</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 p-5">
    <nav class="flex bg-gray-200 rounded-md p-3 mb-5">
        <a href="#" class="mr-5 font-bold text-gray-600 hover:text-green-700">Overzicht</a>
        <a href="#" class="mr-auto font-bold bg-green-200 text-green-800 rounded px-3 py-1">Resultaten</a>
        <button class="bg-green-200 text-green-800 font-bold rounded px-4 py-2">Logged In</button>
    </nav>

    <header>
        <h1 class="text-2xl mb-1">Hello, <span id="username" class="font-semibold">[Gebruikersnaam]</span>.</h1>
        <p class="text-gray-600 mb-5">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt</p>
    </header>

    <div class="flex justify-between bg-white p-5 rounded-lg shadow mb-10">
        <div class="flex-1 mx-3 text-center">
            <h3 class="text-lg font-semibold text-green-700 mb-3">Jouw stem</h3>
            <div>
                <img src="https://via.placeholder.com/80" alt="Kandidaat 1" class="mx-auto rounded-full mb-2" />
                <div><strong>Naam</strong></div>
                <div>Politieke partij</div>
                <div>District</div>
            </div>
            <div class="mt-4">
                <img src="https://via.placeholder.com/80" alt="Kandidaat 2" class="mx-auto rounded-full mb-2" />
                <div><strong>Naam</strong></div>
                <div>Politieke partij</div>
                <div>District</div>
            </div>
        </div>
        <div class="flex-1 mx-3">
            <h3 class="text-lg font-semibold text-green-700 mb-3 text-center">Kleine nieuws sectie</h3>
            <div class="flex justify-around mt-2">
                <div class="max-w-[45%]">
                    <h4 class="font-semibold mb-1">Lorem ipsum</h4>
                    <p class="text-gray-600 text-sm">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                </div>
                <div class="max-w-[45%]">
                    <h4 class="font-semibold mb-1">Lorem ipsum</h4>
                    <p class="text-gray-600 text-sm">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                </div>
            </div>
        </div>
    </div>

    <hr class="border-gray-300 my-8" />

    <section class="bg-white p-5 rounded-lg shadow mb-10" id="dna-results">
        <h2 class="text-green-700 text-xl mb-3">Resultaten van De Nationale Assemblée</h2>
        <div class="mb-4">
            <label for="dna-filter" class="mr-2 font-semibold">Filter:</label>
            <select id="dna-filter" class="border border-gray-300 rounded px-3 py-1 text-sm">
                <option value="all">Alle Politieke Partijen</option>
            </select>
        </div>
        <div class="max-w-3xl mx-auto">
            <canvas id="dnaChart"></canvas>
        </div>
    </section>

    <section class="bg-white p-5 rounded-lg shadow mb-10" id="resorts-results">
        <h2 class="text-green-700 text-xl mb-3">Resultaten van Resortsraden</h2>
        <div class="mb-4">
            <label for="resorts-filter" class="mr-2 font-semibold">Filter:</label>
            <select id="resorts-filter" class="border border-gray-300 rounded px-3 py-1 text-sm">
                <option value="all">Alle Resortsraden</option>
            </select>
        </div>
        <div class="max-w-3xl mx-auto">
            <canvas id="resortsChart"></canvas>
        </div>
    </section>

    <div class="bg-green-200 text-green-800 font-bold rounded-lg max-w-3xl mx-auto py-3 text-center mb-10">Kandidaten met de meeste stemmen</div>

    <div class="flex max-w-3xl mx-auto justify-between">
        <div class="flex-1 bg-white p-5 rounded-lg shadow mx-2" id="dna-top-candidates">
            <h3 class="text-lg font-semibold mb-4">De Nationale Assemblée</h3>
        </div>
        <div class="flex-1 bg-white p-5 rounded-lg shadow mx-2" id="resorts-top-candidates">
            <h3 class="text-lg font-semibold mb-4">Resortsraden</h3>
        </div>
    </div>

    <script>
        // Mock data for DNA candidates
        const dnaCandidates = [
            { id: 1, name: "Naam 1", party: "Partij A", district: "District 1", votes: 120, img: "https://via.placeholder.com/50" },
            { id: 2, name: "Naam 2", party: "Partij B", district: "District 2", votes: 200, img: "https://via.placeholder.com/50" },
            { id: 3, name: "Naam 3", party: "Partij A", district: "District 3", votes: 150, img: "https://via.placeholder.com/50" },
            { id: 4, name: "Naam 4", party: "Partij C", district: "District 4", votes: 90, img: "https://via.placeholder.com/50" }
        ];

        // Mock data for Resortsraden candidates
        const resortsCandidates = [
            { id: 1, name: "Naam A", party: "Resort 1", votes: 180, img: "https://via.placeholder.com/50" },
            { id: 2, name: "Naam B", party: "Resort 2", votes: 220, img: "https://via.placeholder.com/50" },
            { id: 3, name: "Naam C", party: "Resort 1", votes: 130, img: "https://via.placeholder.com/50" },
            { id: 4, name: "Naam D", party: "Resort 3", votes: 160, img: "https://via.placeholder.com/50" }
        ];

        // Populate filter dropdowns
        function populateFilterOptions() {
            const dnaFilter = document.getElementById('dna-filter');
            const resortsFilter = document.getElementById('resorts-filter');

            const dnaParties = [...new Set(dnaCandidates.map(c => c.party))];
            dnaParties.forEach(party => {
                const option = document.createElement('option');
                option.value = party;
                option.textContent = party;
                dnaFilter.appendChild(option);
            });

            const resortsParties = [...new Set(resortsCandidates.map(c => c.party))];
            resortsParties.forEach(party => {
                const option = document.createElement('option');
                option.value = party;
                option.textContent = party;
                resortsFilter.appendChild(option);
            });
        }

        // Filter candidates by party
        function filterCandidates(candidates, party) {
            if (party === 'all') return candidates;
            return candidates.filter(c => c.party === party);
        }

        // Create bar chart
        function createBarChart(ctx, candidates) {
            const labels = candidates.map(c => c.name);
            const votes = candidates.map(c => c.votes);
            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Aantal stemmen',
                        data: votes,
                        backgroundColor: [
                            '#f4a261',
                            '#e9c46a',
                            '#2a9d8f',
                            '#264653',
                            '#e76f51',
                            '#b6e2a1'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Aantal stemmen'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Kandidaten'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Render top candidates with progress bars
        function renderTopCandidates(containerId, candidates) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            const maxVotes = Math.max(...candidates.map(c => c.votes));
            candidates.forEach(c => {
                const item = document.createElement('div');
                item.className = 'candidate-item';

                const img = document.createElement('img');
                img.src = c.img;
                img.alt = c.name;

                const info = document.createElement('div');
                info.className = 'candidate-info';

                const name = document.createElement('div');
                name.className = 'candidate-name';
                name.textContent = c.name;

                const party = document.createElement('div');
                party.className = 'candidate-party';
                party.textContent = c.party;

                const progressContainer = document.createElement('div');
                progressContainer.className = 'progress-bar-container';

                const progressBar = document.createElement('div');
                progressBar.className = 'progress-bar';
                const widthPercent = (c.votes / maxVotes) * 100;
                progressBar.style.width = widthPercent + '%';

                progressContainer.appendChild(progressBar);
                info.appendChild(name);
                info.appendChild(party);
                info.appendChild(progressContainer);

                item.appendChild(img);
                item.appendChild(info);

                container.appendChild(item);
            });
        }

        // Initialize page
        window.onload = function() {
            populateFilterOptions();

            const dnaCtx = document.getElementById('dnaChart').getContext('2d');
            const resortsCtx = document.getElementById('resortsChart').getContext('2d');

            let currentDnaChart = createBarChart(dnaCtx, dnaCandidates);
            let currentResortsChart = createBarChart(resortsCtx, resortsCandidates);

            document.getElementById('dna-filter').addEventListener('change', function() {
                const filtered = filterCandidates(dnaCandidates, this.value);
                currentDnaChart.destroy();
                currentDnaChart = createBarChart(dnaCtx, filtered);
            });

            document.getElementById('resorts-filter').addEventListener('change', function() {
                const filtered = filterCandidates(resortsCandidates, this.value);
                currentResortsChart.destroy();
                currentResortsChart = createBarChart(resortsCtx, filtered);
            });

            renderTopCandidates('dna-top-candidates', dnaCandidates);
            renderTopCandidates('resorts-top-candidates', resortsCandidates);
        };
    </script>
</body>
</html>

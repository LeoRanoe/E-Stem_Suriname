// Debug logging setup
console.log('Admin dashboard script loaded');
console.groupCollapsed('Initial Chart Data');
console.log('Active Voters:', document.getElementById('activeVoters')?.value);
console.log('Inactive Voters:', document.getElementById('inactiveVoters')?.value);
console.log('Voters Who Voted:', document.getElementById('votersWhoVoted')?.value);
console.log('Vote Dates:', document.getElementById('voteDates')?.value);
console.log('Vote Counts:', document.getElementById('voteCounts')?.value);
console.log('Districts:', document.getElementById('districts')?.value);
console.log('District Votes:', document.getElementById('districtVotes')?.value);
console.groupEnd();

// Voter Status Chart
function initVoterStatusChart(activeVoters, inactiveVoters, votersWhoVoted) {
    try {
        console.log('Initializing voter status chart...');
        const canvas = document.getElementById('voterStatusChart');
        console.log('Canvas element:', canvas);

        if (!canvas) {
            console.warn('Voter status chart canvas not found - showing fallback message');
            showChartFallbackMessage(canvas);
            return null;
        }

        // Validate and parse inputs
        activeVoters = parseInt(activeVoters);
        inactiveVoters = parseInt(inactiveVoters);
        votersWhoVoted = parseInt(votersWhoVoted);
        console.log('Parsed values:', {activeVoters, inactiveVoters, votersWhoVoted});

        // Check for invalid data
        if (isNaN(activeVoters) || isNaN(inactiveVoters) || isNaN(votersWhoVoted)) {
            console.error('Invalid voter status data:', {
                activeVoters,
                inactiveVoters,
                votersWhoVoted,
                activeVotersEl: document.getElementById('activeVoters')?.value,
                inactiveVotersEl: document.getElementById('inactiveVoters')?.value,
                votersWhoVotedEl: document.getElementById('votersWhoVoted')?.value
            });
            throw new Error('Invalid voter status data');
        }

        // Check if we have at least one positive value
        const hasData = activeVoters > 0 || inactiveVoters > 0 || votersWhoVoted > 0;
        console.log('Data availability check:', {hasData, activeVoters, inactiveVoters, votersWhoVoted});

        if (!hasData) {
            console.warn('No voter data available - showing fallback message');
            showChartFallbackMessage(canvas);
            return null;
        }

        const ctx = canvas.getContext('2d');
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Actief', 'Inactief', 'Gestemd'],
                datasets: [{
                    data: [activeVoters, inactiveVoters, votersWhoVoted],
                    backgroundColor: [
                        '#007749', // suriname-green
                        '#C8102E', // suriname-red
                        '#006241'  // suriname-dark-green
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                },
                onResize: (chart, size) => {
                    if (size.height < 200) {
                        chart.options.plugins.legend.position = 'bottom';
                    } else {
                        chart.options.plugins.legend.position = 'right';
                    }
                }
            }
        });

        // Add error handler for chart rendering
        chart.options.onHover = (event, chartElements) => {
            if (chartElements && chartElements.length) {
                canvas.style.cursor = 'pointer';
            } else {
                canvas.style.cursor = 'default';
            }
        };

        return chart;
    } catch (error) {
        console.error('Error initializing voter status chart:', error);
        try {
            const canvas = document.getElementById('voterStatusChart');
            if (canvas) {
                showChartFallbackMessage(canvas);
                console.log('Successfully displayed fallback message after error');
            } else {
                console.warn('Could not find canvas element to show fallback message');
            }
        } catch (fallbackError) {
            console.error('Error showing fallback message:', fallbackError);
        }
        return null;
    }
}

// Show fallback message when chart cannot be rendered
function showChartFallbackMessage(canvas) {
    if (!canvas) return;
    
    const container = canvas.closest('.h-72');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500 h-full flex flex-col justify-center items-center">
                <i class="fas fa-info-circle text-3xl mb-2 text-gray-400"></i>
                <p>Geen recente activiteit gevonden.</p>
            </div>
        `;
    }
}

// Votes per Day Chart
function initVotesPerDayChart(dates, counts) {
    try {
        console.log('Initializing votes per day chart...');
        const canvas = document.getElementById('votesPerDayChart');
        console.log('Canvas element:', canvas);

        if (!canvas) {
            // console.warn('Votes per day chart canvas not found');
            return null;
        }

        // Validate inputs
        if (!Array.isArray(dates) || !Array.isArray(counts)) {
            console.warn('Invalid data for votes per day chart');
            return null;
        }

        const ctx = canvas.getContext('2d');
        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Aantal Stemmen',
                    data: counts,
                    borderColor: '#007749',
                    backgroundColor: 'rgba(0, 119, 73, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    } catch (error) {
        console.error('Error initializing votes per day chart:', error);
        return null;
    }
}

// Votes by District Chart
function initVotesByDistrictChart(districts, counts) {
    try {
        console.log('Initializing votes by district chart...');
        const canvas = document.getElementById('votesByDistrictChart');
        console.log('Canvas element:', canvas);

        if (!canvas) {
            // console.warn('Votes by district chart canvas not found');
            return null;
        }

        // Validate inputs
        if (!Array.isArray(districts) || !Array.isArray(counts)) {
            console.warn('Invalid data for votes by district chart');
            return null;
        }

        const ctx = canvas.getContext('2d');
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: districts,
                datasets: [{
                    label: 'Aantal Stemmen',
                    data: counts,
                    backgroundColor: '#007749',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    } catch (error) {
        console.error('Error initializing votes by district chart:', error);
        return null;
    }
}

// Initialize all charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded - initializing charts');
    
    try {
        // Initialize Voter Status Chart
        const activeVotersEl = document.getElementById('activeVoters');
        const inactiveVotersEl = document.getElementById('inactiveVoters');
        const votersWhoVotedEl = document.getElementById('votersWhoVoted');

        if (activeVotersEl && inactiveVotersEl && votersWhoVotedEl) {
            initVoterStatusChart(
                parseInt(activeVotersEl.value),
                parseInt(inactiveVotersEl.value),
                parseInt(votersWhoVotedEl.value)
            );
        } else {
            console.error('Missing required elements for voter status chart');
        }

        // Initialize Votes Per Day Chart
        const voteDatesEl = document.getElementById('voteDates');
        const voteCountsEl = document.getElementById('voteCounts');

        if (voteDatesEl && voteCountsEl) {
            try {
                initVotesPerDayChart(
                    JSON.parse(voteDatesEl.value),
                    JSON.parse(voteCountsEl.value)
                );
            } catch (e) {
                console.error('Error parsing vote dates/counts:', e);
            }
        } else {
            console.error('Missing required elements for votes per day chart');
        }

        // Initialize Votes By District Chart
        const districtsEl = document.getElementById('districts');
        const districtVotesEl = document.getElementById('districtVotes');

        if (districtsEl && districtVotesEl) {
            try {
                initVotesByDistrictChart(
                    JSON.parse(districtsEl.value),
                    JSON.parse(districtVotesEl.value)
                );
            } catch (e) {
                console.error('Error parsing district votes:', e);
            }
        } else {
            console.error('Missing required elements for votes by district chart');
        }
    } catch (error) {
        console.error('Error initializing dashboard charts:', error);
    }
});

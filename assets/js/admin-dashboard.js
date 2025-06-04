// Admin Dashboard JavaScript
console.log('Admin dashboard script loaded');

// Initial data for charts
console.log('Initial Chart Data');

// Chart configuration and data
const chartColors = {
    green: '#007749',
    red: '#C8102E',
    yellow: '#FFD700',
    blue: '#0066cc',
    purple: '#6f42c1',
    gray: '#6c757d',
    lightGray: '#f8f9fa'
};

// Utility functions
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

function formatPercentage(num) {
    return new Intl.NumberFormat('default', {
        style: 'percent',
        minimumFractionDigits: 1,
        maximumFractionDigits: 1
    }).format(num / 100);
}

// Function to update dashboard stats
function updateDashboardStats() {
    fetch('/src/api/get_dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            // Update stats cards
            if (data.success) {
                const stats = data.stats;
                
                // Update voter count
                const voterCountElement = document.getElementById('voter-count');
                if (voterCountElement) {
                    voterCountElement.textContent = formatNumber(stats.total_voters);
                }
                
                // Update turnout
                const turnoutElement = document.getElementById('turnout-percentage');
                if (turnoutElement) {
                    turnoutElement.textContent = stats.turnout_percentage + '%';
                }
                
                // Update QR codes count
                const qrCodesElement = document.getElementById('qr-codes-count');
                if (qrCodesElement) {
                    qrCodesElement.textContent = formatNumber(stats.total_qrcodes);
                }
                
                // Update active elections
                const activeElectionsElement = document.getElementById('active-elections');
                if (activeElectionsElement) {
                    activeElectionsElement.textContent = stats.active_elections;
                }
                
                // Update charts
                updateCharts(stats);
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard stats:', error);
        });
}

// Function to update all charts
function updateCharts(stats) {
    // Update voter status chart
    if (window.voterStatusChart && stats.voter_status) {
        window.voterStatusChart.data.datasets[0].data = [
            stats.voter_status.active,
            stats.voter_status.inactive
        ];
        window.voterStatusChart.update();
    }
    
    // Update votes per day chart
    if (window.votesPerDayChart && stats.votes_per_day) {
        window.votesPerDayChart.data.labels = stats.votes_per_day.map(item => item.date);
        window.votesPerDayChart.data.datasets[0].data = stats.votes_per_day.map(item => item.count);
        window.votesPerDayChart.update();
    }
    
    // Update votes by district chart
    if (window.votesByDistrictChart && stats.votes_by_district) {
        window.votesByDistrictChart.data.labels = stats.votes_by_district.map(item => item.district);
        window.votesByDistrictChart.data.datasets[0].data = stats.votes_by_district.map(item => item.count);
        window.votesByDistrictChart.update();
    }
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded - initializing charts');
    
    // Initialize voter status chart
    const voterStatusCtx = document.getElementById('voter-status-chart');
    if (voterStatusCtx) {
        window.voterStatusChart = new Chart(voterStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Inactive'],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: [chartColors.green, chartColors.gray]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    } else {
        console.log('Missing required elements for voter status chart');
    }
    
    // Initialize votes per day chart
    const votesPerDayCtx = document.getElementById('votes-per-day-chart');
    if (votesPerDayCtx) {
        window.votesPerDayChart = new Chart(votesPerDayCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Votes',
                    data: [],
                    borderColor: chartColors.blue,
                    backgroundColor: 'rgba(0, 102, 204, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    } else {
        console.log('Missing required elements for votes per day chart');
    }
    
    // Initialize votes by district chart
    const votesByDistrictCtx = document.getElementById('votes-by-district-chart');
    if (votesByDistrictCtx) {
        window.votesByDistrictChart = new Chart(votesByDistrictCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Votes',
                    data: [],
                    backgroundColor: chartColors.green
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y'
            }
        });
    } else {
        console.log('Missing required elements for votes by district chart');
    }
    
    // Initial update
    updateDashboardStats();
    
    // Set up periodic updates (every 30 seconds)
    setInterval(updateDashboardStats, 30000);
});

document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('resultsChart');
    
    // Check if the canvas element and the data exist
    if (!canvas) {
        console.error('Chart canvas element (resultsChart) not found.');
        return;
    }
    
    if (typeof window.electionResultsData === 'undefined' || !window.electionResultsData) {
        console.warn('Election results data (window.electionResultsData) not found or empty. Chart will not be rendered.');
        // Optionally display a message on the canvas or nearby
        const ctx = canvas.getContext('2d');
        ctx.font = '16px Arial';
        ctx.fillStyle = 'grey';
        ctx.textAlign = 'center';
        ctx.fillText('Geen data beschikbaar voor grafiek.', canvas.width / 2, canvas.height / 2);
        return;
    }

    const { labels, votes, colors } = window.electionResultsData;

    // Basic validation
    if (!Array.isArray(labels) || !Array.isArray(votes) || !Array.isArray(colors) || labels.length !== votes.length || labels.length !== colors.length) {
        console.error('Chart data is invalid or mismatched.');
        return;
    }
    
    // If there's no data to display, show a message instead of an empty chart
    if (labels.length === 0) {
         console.warn('No data points available for the chart.');
         const ctx = canvas.getContext('2d');
         ctx.font = '16px Arial';
         ctx.fillStyle = 'grey';
         ctx.textAlign = 'center';
         ctx.fillText('Geen stemresultaten om weer te geven.', canvas.width / 2, canvas.height / 2);
         return;
    }

    const ctx = canvas.getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Aantal Stemmen',
                data: votes,
                backgroundColor: colors,
                borderColor: colors.map(color => { // Slightly darken border for better visibility
                    // Basic darken function (adjust multiplier for desired darkness)
                    let r = parseInt(color.slice(1, 3), 16);
                    let g = parseInt(color.slice(3, 5), 16);
                    let b = parseInt(color.slice(5, 7), 16);
                    r = Math.max(0, r - 30);
                    g = Math.max(0, g - 30);
                    b = Math.max(0, b - 30);
                    return `rgb(${r}, ${g}, ${b})`;
                }),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Allow chart to adapt height
            plugins: {
                legend: {
                    display: false // Legend is not very useful for many candidates
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += context.parsed.y + ' stemmen';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0 // Ensure whole numbers for vote counts
                    },
                    title: {
                         display: true,
                         text: 'Aantal Stemmen'
                    }
                },
                x: {
                     ticks: {
                         // Auto-skip ticks if too many labels, or consider rotating them
                         autoSkip: true,
                         maxRotation: 0, // Keep labels horizontal initially
                         minRotation: 0
                     },
                     title: {
                          display: true,
                          text: 'Kandidaten (Partij)'
                     }
                }
            }
        }
    });

    // --- START: Votes Over Time Line Chart ---
    const timeCanvas = document.getElementById('votesOverTimeChart');

    if (!timeCanvas) {
        console.warn('Chart canvas element (votesOverTimeChart) not found.');
        // Don't return here, the other chart might still work
    } else if (typeof window.electionResultsData === 'undefined' || !window.electionResultsData || !window.electionResultsData.timeLabels || !window.electionResultsData.timeCounts) {
        console.warn('Votes over time data not found or empty. Time chart will not be rendered.');
        const timeCtx = timeCanvas.getContext('2d');
        timeCtx.font = '16px Arial';
        timeCtx.fillStyle = 'grey';
        timeCtx.textAlign = 'center';
        timeCtx.fillText('Geen data beschikbaar voor tijdlijn.', timeCanvas.width / 2, timeCanvas.height / 2);
    } else {
        const { timeLabels, timeCounts } = window.electionResultsData;

        if (!Array.isArray(timeLabels) || !Array.isArray(timeCounts) || timeLabels.length !== timeCounts.length) {
            console.error('Votes over time chart data is invalid or mismatched.');
        } else if (timeLabels.length === 0) {
            console.warn('No data points available for the time chart.');
            const timeCtx = timeCanvas.getContext('2d');
            timeCtx.font = '16px Arial';
            timeCtx.fillStyle = 'grey';
            timeCtx.textAlign = 'center';
            timeCtx.fillText('Geen stemmen over tijd om weer te geven.', timeCanvas.width / 2, timeCanvas.height / 2);
        } else {
            const timeCtx = timeCanvas.getContext('2d');
            new Chart(timeCtx, {
                type: 'line',
                data: {
                    labels: timeLabels, // Dates
                    datasets: [{
                        label: 'Aantal Stemmen per Dag',
                        data: timeCounts, // Counts
                        fill: true, // Fill area under the line
                        borderColor: 'rgb(75, 192, 192)', // Example color
                        backgroundColor: 'rgba(75, 192, 192, 0.2)', // Lighter fill
                        tension: 0.1 // Slight curve to the line
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true, // Show legend for this chart
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        x: {
                            type: 'time', // Use time scale
                            time: {
                                unit: 'day', // Display unit as day
                                tooltipFormat: 'PPP', // Format for tooltip (e.g., May 12, 2025)
                                displayFormats: {
                                    day: 'MMM d' // Format for axis labels (e.g., May 12)
                                }
                            },
                            title: {
                                display: true,
                                text: 'Datum'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0 // Whole numbers for vote counts
                            },
                            title: {
                                display: true,
                                text: 'Aantal Stemmen'
                            }
                        }
                    },
                    interaction: { // Improve hover interaction
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }
    }
    // --- END: Votes Over Time Line Chart ---
});
document.getElementById('fileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) {
        return;
    }
    
    if (!file.name.endsWith('.csv')) {
        alert('Please upload a valid CSV file (only .csv files are allowed)');
        return;
    }

    // Load PapaParse library if not already loaded
    if (!window.Papa) {
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.3.0/papaparse.min.js';
        document.head.appendChild(script);
    }

    Papa.parse(file, {
        header: true,
        skipEmptyLines: true,
        complete: function(results) {
            console.log('Parsed CSV data:', results.data);
            // Process parsed CSV data here
        },
        error: function(error) {
            console.error('Error parsing CSV:', error);
            alert('Error parsing CSV file: ' + error.message);
        }
    });
});
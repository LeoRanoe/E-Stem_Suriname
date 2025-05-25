document.addEventListener('DOMContentLoaded', function() {
    // QR Code Generation Modal Handling
    const generateBtn = document.getElementById('generateQrBtn');
    const qrModal = document.getElementById('qrModal');
    const cancelQrBtn = document.getElementById('cancelQrModalBtn');
    const confirmGenerateBtn = document.getElementById('confirmGenerateBtn');

    // Handle QR generation modal
    if (generateBtn && qrModal) {
        generateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            qrModal.classList.remove('hidden');
        });

        cancelQrBtn?.addEventListener('click', function() {
            qrModal.classList.add('hidden');
        });

        confirmGenerateBtn?.addEventListener('click', async function() {
            try {
                confirmGenerateBtn.disabled = true;
                confirmGenerateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generating...';

                const response = await fetch('../src/controllers/QrCodeController.php?action=generateQrCodes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        election_id: electionId,
                        user_count: userCount
                    })
                });

                const result = await response.json();
                if (response.ok) {
                    alert(`Successfully generated ${result.count} QR codes`);
                    location.reload();
                } else {
                    throw new Error(result.message || 'Generation failed');
                }
            } catch (error) {
                console.error('Generation error:', error);
                alert('Error: ' + error.message);
            } finally {
                confirmGenerateBtn.disabled = false;
                confirmGenerateBtn.innerHTML = 'Generate';
                qrModal.classList.add('hidden');
            }
        });
    }

    // CSV Import Modal Handling
    const importBtn = document.getElementById('importUsersBtn');
    const importModal = document.getElementById('importModal');
    const cancelImportBtn = document.getElementById('cancelImportBtn');
    const confirmImportBtn = document.getElementById('confirmImportBtn');
    const csvFileInput = document.getElementById('csvFile');
    const electionSelect = document.getElementById('importElectionSelect');
    const progressDiv = document.getElementById('importProgress');
    
    // Make sure we have access to the file input
    if (!csvFileInput) {
        console.error('CSV file input element not found');
    }

    if (importBtn && importModal) {
        const errorDiv = document.getElementById('importError');

        function showError(message) {
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
            } else {
                alert(message);
            }
        }

        function hideError() {
            if (errorDiv) {
                errorDiv.classList.add('hidden');
            }
        }

        function updateProgress(percent, text) {
            if (progressDiv) {
                progressDiv.classList.remove('hidden');
                const progressBar = progressDiv.querySelector('#progressBar');
                const progressText = progressDiv.querySelector('#progressText');
                if (progressBar) progressBar.style.width = `${percent}%`;
                if (progressText) progressText.textContent = text || `${percent}% Complete`;
            }
        }

        function resetProgress() {
            if (progressDiv) {
                progressDiv.classList.add('hidden');
                const progressBar = progressDiv.querySelector('#progressBar');
                if (progressBar) progressBar.style.width = '0%';
            }
            hideError();
        }

        importBtn.addEventListener('click', function(e) {
            e.preventDefault();
            importModal.classList.remove('hidden');
            resetProgress();
        });

        cancelImportBtn?.addEventListener('click', function() {
            importModal.classList.add('hidden');
            csvFileInput.value = '';
            electionSelect.value = '';
            resetProgress();
        });

        confirmImportBtn?.addEventListener('click', async function() {
            if (!csvFileInput) {
                console.error('CSV file input not found');
                return;
            }
            
            hideError();
            
            const file = csvFileInput.files[0];
            if (!file) {
                showError('Please select a CSV file first');
                return;
            }

            // Validate file type
            const validTypes = ['text/csv', 'application/vnd.ms-excel'];
            if (!validTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.csv')) {
                showError('Please select a valid CSV file');
                return;
            }

            try {
                confirmImportBtn.disabled = true;
                confirmImportBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Importing...';
                progressDiv.classList.remove('hidden');
                
                // Read and validate CSV before sending
                const reader = new FileReader();
                reader.onload = async function(e) {
                    try {
                        const csv = e.target.result;
                        const lines = csv.split('\n');
                        const headers = lines[0].split(',').map(h => h.trim());
                        const requiredHeaders = ['Voornaam', 'Achternaam', 'Email', 'IDNumber', 'DistrictID'];
                        
                        // Validate headers
                        const missingHeaders = requiredHeaders.filter(h => !headers.includes(h));
                        if (missingHeaders.length > 0) {
                            throw new Error('CSV file is missing required columns: ' + missingHeaders.join(', '));
                        }

                        updateProgress(20, 'Validating data...');

                        // Create form data for upload
                        const formData = new FormData();
                        formData.append('file', file);
                        
                        // Append election_id if one is selected
                        const electionId = electionSelect.value;
                        if (electionId) {
                            formData.append('election_id', electionId);
                            updateProgress(40, 'Importing users and generating QR codes...');
                        } else {
                            updateProgress(40, 'Importing users...');
                        }
                        
                        const response = await fetch('../../src/ajax/import-user.php', {
                            method: 'POST',
                            body: formData
                        });

                        updateProgress(80, 'Processing...');

                        const result = await response.json();
                        if (response.ok) {
                            updateProgress(100, 'Complete!');
                            alert(result.message);
                            location.reload();
                        } else {
                            throw new Error(result.message || 'Import failed');
                        }
                    } catch (error) {
                        showError(error.message);
                        resetProgress();
                    }
                };

                reader.onerror = function() {
                    showError('Error reading file');
                    resetProgress();
                };

                reader.readAsText(file);

            } catch (error) {
                console.error('Import error:', error);
                showError(error.message);
                resetProgress();
            } finally {
                confirmImportBtn.disabled = false;
                confirmImportBtn.innerHTML = 'Import';
            }
        });
    }

    // Initialize DataTables if present
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Dutch.json'
            }
        });
    }
});
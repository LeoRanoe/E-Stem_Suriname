document.addEventListener('DOMContentLoaded', function() {
    // QR Code Generation Modal Handling
    const generateBtn = document.getElementById('generateQrBtn');
    const qrModal = document.getElementById('qrModal');
    const cancelQrBtn = document.getElementById('cancelQrModalBtn');
    const confirmGenerateBtn = document.getElementById('confirmGenerateBtn');
    const electionSelect = document.getElementById('electionId');
    const userCountInput = document.getElementById('userCount');

    if (generateBtn && qrModal) {
        // Show modal when generate button clicked
        generateBtn.addEventListener('click', function(e) {
            e.preventDefault();
            qrModal.classList.remove('hidden');
        });

        // Hide modal when cancel button clicked
        cancelQrBtn.addEventListener('click', function() {
            qrModal.classList.add('hidden');
        });

        // Handle QR code generation
        confirmGenerateBtn.addEventListener('click', async function() {
            const electionId = electionSelect.value;
            const userCount = userCountInput.value;
            
            if (!electionId) {
                alert('Please select an election first');
                return;
            }

            try {
                // Show loading state
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
                // Reset button state
                confirmGenerateBtn.disabled = false;
                confirmGenerateBtn.innerHTML = 'Generate';
                qrModal.classList.add('hidden');
            }
        });
    }

    // Import Users Modal Handling
    const importBtn = document.getElementById('importUsersBtn');
    const importModal = document.getElementById('importModal');
    const cancelImportBtn = document.getElementById('cancelImportBtn');
    const confirmImportBtn = document.getElementById('confirmImportBtn');
    const csvFileInput = document.getElementById('csvFile');

    if (importBtn && importModal) {
        // Show modal when import button clicked
        importBtn.addEventListener('click', function(e) {
            e.preventDefault();
            importModal.classList.remove('hidden');
        });

        // Hide modal when cancel button clicked
        cancelImportBtn.addEventListener('click', function() {
            importModal.classList.add('hidden');
            csvFileInput.value = '';
        });

        // Handle import submission
        confirmImportBtn.addEventListener('click', async function() {
            const file = csvFileInput.files[0];
            
            if (!file) {
                alert('Please select a CSV file first');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            try {
                // Show loading state
                confirmImportBtn.disabled = true;
                confirmImportBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Importing...';

                const response = await fetch('../src/controllers/QrCodeController.php?action=importUsers', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (response.ok) {
                    alert(`Successfully imported ${result.count} users`);
                    location.reload();
                } else {
                    throw new Error(result.message || 'Import failed');
                }
            } catch (error) {
                console.error('Import error:', error);
                alert('Error: ' + error.message);
            } finally {
                // Reset button state
                confirmImportBtn.disabled = false;
                confirmImportBtn.innerHTML = '<i class="fas fa-file-import mr-2"></i> Import';
                importModal.classList.add('hidden');
                csvFileInput.value = '';
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
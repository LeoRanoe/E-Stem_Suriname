// Bulk QR code functionality for the E-Stem Suriname Voting System
document.addEventListener('DOMContentLoaded', function() {
    console.log('Bulk QR Code JS loaded');
    
    // Get DOM elements for bulk operations
    const selectAllVouchers = document.getElementById('select-all-vouchers');
    const selectAllVoters = document.getElementById('select-all-voters');
    const bulkPrintBtn = document.getElementById('bulk-print-btn');
    const bulkDownloadBtn = document.getElementById('bulk-download-btn');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const downloadAllBtn = document.getElementById('download-all-btn');
    const bulkDownloadModal = document.getElementById('bulk-download-modal');
    const closeBulkDownloadModal = document.getElementById('close-bulk-download-modal');
    const bulkDownloadProgress = document.getElementById('bulk-download-progress').querySelector('div');
    const bulkDownloadStatus = document.getElementById('bulk-download-status');
    const bulkDownloadComplete = document.getElementById('bulk-download-complete');
    const bulkDownloadLink = document.getElementById('bulk-download-link');
    const filterDistrict = document.getElementById('filter-district');
    
    // Initialize JSZip if available
    let JSZip;
    if (window.JSZip) {
        JSZip = new window.JSZip();
    }
    
    // Handle select all vouchers
    if (selectAllVouchers) {
        selectAllVouchers.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.voucher-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            
            // Show/hide bulk action buttons
            toggleBulkActionButtons();
        });
    }
    
    // Handle select all voters in bulk generation modal
    if (selectAllVoters) {
        selectAllVoters.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('#voter-checkboxes input[type="checkbox"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Handle voter checkboxes change events
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('voucher-checkbox')) {
            toggleBulkActionButtons();
        }
    });
    
    // Handle bulk print button
    if (bulkPrintBtn) {
        bulkPrintBtn.addEventListener('click', function() {
            const selectedVouchers = getSelectedVouchers();
            if (selectedVouchers.length === 0) {
                alert('Please select at least one voucher to print.');
                return;
            }
            
            printSelectedVouchers(selectedVouchers);
        });
    }
    
    // Handle bulk download button
    if (bulkDownloadBtn) {
        bulkDownloadBtn.addEventListener('click', function() {
            const selectedVouchers = getSelectedVouchers();
            if (selectedVouchers.length === 0) {
                alert('Please select at least one voucher to download.');
                return;
            }
            
            openBulkDownloadModal();
            downloadSelectedVouchers(selectedVouchers);
        });
    }
    
    // Handle download all button
    if (downloadAllBtn) {
        downloadAllBtn.addEventListener('click', function() {
            const allVouchers = getAllVouchers();
            if (allVouchers.length === 0) {
                alert('No vouchers available to download.');
                return;
            }
            
            openBulkDownloadModal();
            downloadSelectedVouchers(allVouchers);
        });
    }
    
    // Handle bulk delete button
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const selectedVouchers = getSelectedVoucherIds();
            if (selectedVouchers.length === 0) {
                alert('Please select at least one voucher to delete.');
                return;
            }
            
            if (confirm(`Are you sure you want to delete ${selectedVouchers.length} vouchers? This action cannot be undone.`)) {
                deleteSelectedVouchers(selectedVouchers);
            }
        });
    }
    
    // Close bulk download modal
    if (closeBulkDownloadModal) {
        closeBulkDownloadModal.addEventListener('click', function() {
            bulkDownloadModal.classList.add('hidden');
        });
    }
    
    // Filter district change handler
    if (filterDistrict) {
        filterDistrict.addEventListener('change', function() {
            const districtId = this.value;
            filterVotersByDistrict(districtId);
        });
    }
    
    // Function to toggle bulk action buttons visibility
    function toggleBulkActionButtons() {
        const selectedCount = document.querySelectorAll('.voucher-checkbox:checked').length;
        
        if (bulkPrintBtn) {
            bulkPrintBtn.classList.toggle('hidden', selectedCount === 0);
        }
        
        if (bulkDownloadBtn) {
            bulkDownloadBtn.classList.toggle('hidden', selectedCount === 0);
        }
        
        if (bulkDeleteBtn) {
            bulkDeleteBtn.classList.toggle('hidden', selectedCount === 0);
        }
    }
    
    // Function to get selected voucher codes
    function getSelectedVouchers() {
        const selectedVouchers = [];
        const checkboxes = document.querySelectorAll('.voucher-checkbox:checked');
        
        checkboxes.forEach(checkbox => {
            selectedVouchers.push(checkbox.dataset.voucherId);
        });
        
        return selectedVouchers;
    }
    
    // Function to get selected voucher IDs
    function getSelectedVoucherIds() {
        const selectedIds = [];
        const checkboxes = document.querySelectorAll('.voucher-checkbox:checked');
        
        checkboxes.forEach(checkbox => {
            selectedIds.push(checkbox.value);
        });
        
        return selectedIds;
    }
    
    // Function to get all vouchers
    function getAllVouchers() {
        const allVouchers = [];
        const checkboxes = document.querySelectorAll('.voucher-checkbox');
        
        checkboxes.forEach(checkbox => {
            allVouchers.push(checkbox.dataset.voucherId);
        });
        
        return allVouchers;
    }
    
    // Function to print selected vouchers
    function printSelectedVouchers(vouchers) {
        try {
            console.log('Printing vouchers:', vouchers);
            
            // Create a new window for printing
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                console.error('Could not open print window. Pop-up might be blocked.');
                alert('Please allow pop-ups to print the QR codes.');
                return;
            }
            
            // Start HTML content for the print window
            let htmlContent = `
                <html>
                <head>
                    <title>E-Stem Suriname Voting Vouchers</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        .page-break { page-break-after: always; }
                        .voucher-container { border: 2px solid #0066cc; border-radius: 10px; padding: 15px; max-width: 350px; margin: 20px auto; }
                        .header { background-color: #0066cc; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px; }
                        .qr-container { margin: 15px auto; text-align: center; }
                        .voucher-id { font-size: 16px; margin-top: 10px; font-weight: bold; text-align: center; }
                        .manual-login { border-top: 1px dashed #ccc; margin-top: 15px; padding-top: 15px; }
                        .login-info { margin: 5px 0; font-size: 14px; }
                        .login-label { font-weight: bold; display: inline-block; width: 100px; }
                        .website { margin-top: 15px; font-style: italic; font-size: 12px; }
                        .instructions { font-size: 12px; margin: 15px 0; }
                        .footer { font-size: 10px; margin-top: 15px; color: #666; text-align: center; }
                        @media print { @page { size: auto; margin: 10mm; } }
                    </style>
                    <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
                </head>
                <body>
            `;
            
            // Add each voucher to the HTML content
            vouchers.forEach((voucherId, index) => {
                // Add page break after each voucher except the first one
                if (index > 0) {
                    htmlContent += '<div class="page-break"></div>';
                }
                
                htmlContent += `
                    <div class="voucher-container">
                        <div class="header"><h2>E-Stem Suriname Voting Voucher</h2></div>
                        <div class="instructions">Scan the QR code below with your smartphone camera to access the voting page, or use the login information provided below.</div>
                        <div class="qr-container">
                            <div id="qr-${index}"></div>
                            <div class="voucher-id">Voucher ID: ${voucherId}</div>
                        </div>
                        <div class="manual-login">
                            <h3>Manual Login Information</h3>
                            <div class="login-info"><span class="login-label">Username:</span> ${voucherId}</div>
                            <div class="login-info"><span class="login-label">Password:</span> ${voucherId.substring(voucherId.length - 6)}</div>
                            <div class="website">Visit: ${window.BASE_URL}</div>
                        </div>
                        <div class="footer">This voucher is for one-time use only. Please keep it secure and confidential.</div>
                    </div>
                `;
            });
            
            // Add script to generate QR codes and print
            htmlContent += `
                <script>
                    window.onload = function() {
                        // Generate QR codes
                        ${vouchers.map((voucherId, index) => `
                            new QRious({
                                element: document.createElement('canvas'),
                                value: '${voucherId}',
                                size: 200,
                                level: 'H'
                            }).element.toBlob(function(blob) {
                                const img = document.createElement('img');
                                img.src = URL.createObjectURL(blob);
                                document.getElementById('qr-${index}').appendChild(img);
                            });
                        `).join('')}
                        
                        // Print after all QR codes are loaded
                        setTimeout(function() {
                            window.print();
                            setTimeout(function() { window.close(); }, 500);
                        }, 1000);
                    };
                </script>
                </body>
                </html>
            `;
            
            printWindow.document.write(htmlContent);
            printWindow.document.close();
            
        } catch (error) {
            console.error('Error printing vouchers:', error);
            alert('An error occurred while trying to print the vouchers.');
        }
    }
    
    // Function to open bulk download modal
    function openBulkDownloadModal() {
        if (bulkDownloadModal) {
            bulkDownloadModal.classList.remove('hidden');
            
            // Reset UI
            bulkDownloadProgress.style.width = '0%';
            bulkDownloadStatus.classList.remove('hidden');
            bulkDownloadComplete.classList.add('hidden');
        }
    }
    
    // Function to download selected vouchers
    function downloadSelectedVouchers(vouchers) {
        if (!window.JSZip) {
            alert('JSZip library is not available. Please try again later.');
            return;
        }
        
        try {
            console.log('Downloading vouchers:', vouchers);
            const zip = new JSZip();
            const total = vouchers.length;
            let processed = 0;
            
            // Process each voucher
            vouchers.forEach((voucherId) => {
                generateQRCode(voucherId, function(imgData) {
                    // Add image to zip
                    const imgName = `qr-${voucherId}.png`;
                    zip.file(imgName, imgData.split(',')[1], {base64: true});
                    
                    // Update progress
                    processed++;
                    const progress = Math.round((processed / total) * 100);
                    updateDownloadProgress(progress);
                    
                    // When all vouchers are processed, generate the zip file
                    if (processed === total) {
                        zip.generateAsync({type: 'blob'})
                            .then(function(content) {
                                // Create download link
                                const date = new Date().toISOString().slice(0, 10);
                                bulkDownloadLink.href = URL.createObjectURL(content);
                                bulkDownloadLink.download = `e-stem-qr-codes-${date}.zip`;
                                
                                // Show download button
                                bulkDownloadStatus.classList.add('hidden');
                                bulkDownloadComplete.classList.remove('hidden');
                            });
                    }
                });
            });
            
        } catch (error) {
            console.error('Error downloading vouchers:', error);
            alert('An error occurred while trying to download the vouchers.');
            if (bulkDownloadModal) {
                bulkDownloadModal.classList.add('hidden');
            }
        }
    }
    
    // Function to update download progress
    function updateDownloadProgress(percentage) {
        if (bulkDownloadProgress) {
            bulkDownloadProgress.style.width = percentage + '%';
        }
    }
    
    // Function to generate QR code and return as data URL
    function generateQRCode(data, callback) {
        const canvas = document.createElement('canvas');
        
        try {
            new QRious({
                element: canvas,
                value: data,
                size: 300,
                level: 'H'
            });
            
            const imgData = canvas.toDataURL('image/png');
            callback(imgData);
        } catch (error) {
            console.error('Error generating QR code:', error);
            callback(null);
        }
    }
    
    // Function to delete selected vouchers
    function deleteSelectedVouchers(voucherIds) {
        try {
            // Create form data with voucher IDs
            const formData = new FormData();
            formData.append('action', 'delete_bulk');
            voucherIds.forEach(id => {
                formData.append('voucher_ids[]', id);
            });
            
            // Send request to server
            fetch(`${window.BASE_URL}/src/controllers/QrCodeController.php`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`Successfully deleted ${data.deleted_count} vouchers.`);
                    // Reload page to refresh vouchers list
                    window.location.reload();
                } else {
                    alert(`Error: ${data.message}`);
                }
            })
            .catch(error => {
                console.error('Error deleting vouchers:', error);
                alert('An error occurred while trying to delete the vouchers.');
            });
            
        } catch (error) {
            console.error('Error deleting vouchers:', error);
            alert('An error occurred while trying to delete the vouchers.');
        }
    }
    
    // Function to filter voters by district
    function filterVotersByDistrict(districtId) {
        const voterCheckboxes = document.getElementById('voter-checkboxes');
        if (!voterCheckboxes) return;
        
        const checkboxes = voterCheckboxes.querySelectorAll('div');
        if (districtId === '') {
            // Show all voters
            checkboxes.forEach(div => {
                div.style.display = 'flex';
            });
        } else {
            // Show only voters from selected district
            checkboxes.forEach(div => {
                const districtAttribute = div.getAttribute('data-district-id');
                if (districtAttribute === districtId) {
                    div.style.display = 'flex';
                } else {
                    div.style.display = 'none';
                }
            });
        }
    }
}); 
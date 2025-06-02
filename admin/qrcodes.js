// QR Code functionality for the E-Stem Suriname Voting System
document.addEventListener('DOMContentLoaded', function() {
    console.log('QR Code JS loaded');
    
    // Define BASE_URL from the global variable set in PHP
    var BASE_URL = window.BASE_URL || '';
    console.log('BASE_URL:', BASE_URL);
    
    // Get all DOM elements needed for QR code functionality
    var singleQrModal = document.getElementById('single-qr-modal');
    var bulkQrModal = document.getElementById('bulk-qr-modal');
    var viewQrModal = document.getElementById('view-qr-modal');
    var deleteModal = document.getElementById('delete-modal');
    var generateSingleBtn = document.getElementById('generate-single-btn');
    var generateBulkBtn = document.getElementById('generate-bulk-btn');
    var closeSingleModalBtn = document.getElementById('close-single-modal');
    var closeBulkModalBtn = document.getElementById('close-bulk-modal');
    var closeViewModalBtn = document.getElementById('close-view-modal');
    var cancelSingleBtn = document.getElementById('cancel-single-btn');
    var cancelBulkBtn = document.getElementById('cancel-bulk-btn');
    var cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    var singleQrForm = document.getElementById('single-qr-form');
    var bulkQrForm = document.getElementById('bulk-qr-form');
    var voterSelect = document.getElementById('voter_id');
    var voterCheckboxes = document.getElementById('voter-checkboxes');
    var viewQrBtns = document.querySelectorAll('.view-qr-btn');
    var deleteVoucherBtns = document.querySelectorAll('.delete-voucher-btn');
    var printQrBtn = document.getElementById('print-qr-btn');
    var downloadQrBtn = document.getElementById('download-qr-btn');
    
    // Log DOM elements for debugging
    console.log('DOM Elements loaded:', {
        singleQrModal: !!singleQrModal,
        bulkQrModal: !!bulkQrModal,
        viewQrModal: !!viewQrModal,
        generateSingleBtn: !!generateSingleBtn,
        generateBulkBtn: !!generateBulkBtn,
        printQrBtn: !!printQrBtn,
        downloadQrBtn: !!downloadQrBtn
    });
    
    // Show generate single QR modal
    if (generateSingleBtn) {
        generateSingleBtn.addEventListener('click', function() {
            console.log('Generate Single QR button clicked');
            loadVotersWithoutVouchers();
            if (singleQrModal) {
                singleQrModal.classList.remove('hidden');
            }
        });
    }
    
    // Show generate bulk QR modal
    if (generateBulkBtn) {
        generateBulkBtn.addEventListener('click', function() {
            console.log('Generate Bulk QR button clicked');
            loadVotersForBulk();
            if (bulkQrModal) {
                bulkQrModal.classList.remove('hidden');
            }
        });
    }
    
    // Close modals
    if (closeSingleModalBtn && singleQrModal) {
        closeSingleModalBtn.addEventListener('click', function() {
            singleQrModal.classList.add('hidden');
        });
    }
    
    if (closeBulkModalBtn && bulkQrModal) {
        closeBulkModalBtn.addEventListener('click', function() {
            bulkQrModal.classList.add('hidden');
        });
    }
    
    if (closeViewModalBtn && viewQrModal) {
        closeViewModalBtn.addEventListener('click', function() {
            viewQrModal.classList.add('hidden');
        });
    }
    
    if (cancelSingleBtn && singleQrModal) {
        cancelSingleBtn.addEventListener('click', function() {
            singleQrModal.classList.add('hidden');
        });
    }
    
    if (cancelBulkBtn && bulkQrModal) {
        cancelBulkBtn.addEventListener('click', function() {
            bulkQrModal.classList.add('hidden');
        });
    }
    
    if (cancelDeleteBtn && deleteModal) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.classList.add('hidden');
        });
    }
    
    // View QR code
    if (viewQrBtns && viewQrBtns.length > 0) {
        console.log('Found', viewQrBtns.length, 'view QR buttons');
        viewQrBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var voucherId = this.dataset.voucher;
                console.log('View QR clicked for voucher:', voucherId);
                
                var qrVoucherIdElement = document.getElementById('qr-voucher-id');
                if (qrVoucherIdElement) {
                    qrVoucherIdElement.textContent = voucherId;
                }
                
                // Generate QR code
                var qrContainer = document.getElementById('qr-code-container');
                if (qrContainer) {
                    qrContainer.innerHTML = '';
                    
                    var canvas = document.createElement('canvas');
                    canvas.id = 'qr-canvas';
                    qrContainer.appendChild(canvas);
                    
                    try {
                        new QRious({
                            element: canvas,
                            value: voucherId,
                            size: 250,
                            level: 'H'
                        });
                        console.log('QR code generated successfully');
                    } catch (error) {
                        console.error('Error generating QR code:', error);
                    }
                    
                    if (viewQrModal) {
                        viewQrModal.classList.remove('hidden');
                    }
                }
            });
        });
    }
    
    // Delete voucher
    if (deleteVoucherBtns && deleteVoucherBtns.length > 0) {
        deleteVoucherBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                var voucherId = this.dataset.id;
                console.log('Delete voucher clicked for ID:', voucherId);
                
                var deleteVoucherIdElement = document.getElementById('delete-voucher-id');
                if (deleteVoucherIdElement) {
                    deleteVoucherIdElement.value = voucherId;
                }
                
                if (deleteModal) {
                    deleteModal.classList.remove('hidden');
                }
            });
        });
    }
    
    // Print QR code
    if (printQrBtn) {
        printQrBtn.addEventListener('click', function() {
            console.log('Print QR button clicked');
            var canvas = document.getElementById('qr-canvas');
            if (!canvas) {
                console.error('QR canvas not found');
                return;
            }
            
            var qrVoucherIdElement = document.getElementById('qr-voucher-id');
            if (!qrVoucherIdElement) {
                console.error('QR voucher ID element not found');
                return;
            }
            
            var voucherId = qrVoucherIdElement.textContent;
            console.log('Printing QR for voucher:', voucherId);
            
            try {
                var printWindow = window.open('', '_blank');
                if (!printWindow) {
                    console.error('Could not open print window. Pop-up might be blocked.');
                    alert('Please allow pop-ups to print the QR code.');
                    return;
                }
                
                var imgData = canvas.toDataURL('image/png');
                
                // Create HTML content for the print window with enhanced information for manual login
                const htmlContent = `
                    <html>
                    <head>
                        <title>E-Stem Suriname Voting Voucher</title>
                        <style>
                            body { font-family: Arial, sans-serif; text-align: center; padding: 20px; }
                            .voucher-container { border: 2px solid #0066cc; border-radius: 10px; padding: 15px; max-width: 350px; margin: 0 auto; }
                            .header { background-color: #0066cc; color: white; padding: 10px; margin-bottom: 15px; border-radius: 5px; }
                            .qr-container { margin: 15px auto; }
                            img { max-width: 100%; height: auto; }
                            .voucher-id { font-size: 16px; margin-top: 10px; font-weight: bold; }
                            .manual-login { border-top: 1px dashed #ccc; margin-top: 15px; padding-top: 15px; text-align: left; }
                            .login-info { margin: 5px 0; font-size: 14px; }
                            .login-label { font-weight: bold; display: inline-block; width: 100px; }
                            .website { margin-top: 15px; font-style: italic; font-size: 12px; }
                            .instructions { font-size: 12px; margin: 15px 0; text-align: left; }
                            .footer { font-size: 10px; margin-top: 15px; color: #666; }
                            @media print { @page { size: auto; margin: 10mm; } }
                        </style>
                    </head>
                    <body>
                        <div class="voucher-container">
                            <div class="header"><h2>E-Stem Suriname Voting Voucher</h2></div>
                            <div class="instructions">Scan the QR code below with your smartphone camera to access the voting page, or use the login information provided below.</div>
                            <div class="qr-container">
                                <img src="${imgData}" alt="QR Code">
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
                        <script>
                            window.onload = function() {
                                window.print();
                                setTimeout(function() { window.close(); }, 500);
                            };
                        </script>
                    </body>
                    </html>`;
                
                printWindow.document.write(htmlContent);
                printWindow.document.close();
                console.log('Print window opened and content written');
            } catch (error) {
                console.error('Error printing QR code:', error);
                alert('An error occurred while trying to print the QR code.');
            }
        });
    }
    
    // Download QR code
    if (downloadQrBtn) {
        downloadQrBtn.addEventListener('click', function() {
            console.log('Download QR button clicked');
            var canvas = document.getElementById('qr-canvas');
            if (!canvas) {
                console.error('QR canvas not found');
                return;
            }
            
            var qrVoucherIdElement = document.getElementById('qr-voucher-id');
            if (!qrVoucherIdElement) {
                console.error('QR voucher ID element not found');
                return;
            }
            
            var voucherId = qrVoucherIdElement.textContent;
            console.log('Downloading QR for voucher:', voucherId);
            
            try {
                var link = document.createElement('a');
                link.download = 'qr-code-' + voucherId + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                console.log('QR code download initiated');
            } catch (error) {
                console.error('Error downloading QR code:', error);
                alert('An error occurred while trying to download the QR code.');
            }
        });
    }
    
    // Function to load voters without vouchers for single QR generation
    function loadVotersWithoutVouchers() {
        console.log('Loading voters without vouchers for single QR generation');
        if (!voterSelect) {
            console.error('Voter select element not found');
            return;
        }
        
        // Show loading indicator
        voterSelect.innerHTML = '<option value="">Loading voters...</option>';
        
        fetch(BASE_URL + '/src/api/get_voters_without_vouchers.php')
            .then(function(response) {
                console.log('API response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log('API data received:', data);
                if (data.success) {
                    voterSelect.innerHTML = '<option value="">Select a voter</option>';
                    
                    if (data.voters && data.voters.length > 0) {
                        data.voters.forEach(function(voter) {
                            const option = document.createElement('option');
                            option.value = voter.id;
                            option.textContent = voter.first_name + ' ' + voter.last_name + ' (' + voter.id_number + ')';
                            
                            // If we have a generate_for parameter, select that voter
                            var generateFor = window.generateFor;
                            if (generateFor && voter.id == generateFor) {
                                option.selected = true;
                            }
                            
                            voterSelect.appendChild(option);
                        });
                        
                        console.log('Loaded', data.voters.length, 'voters without vouchers');
                    } else {
                        console.log('No voters without vouchers found');
                        voterSelect.innerHTML = '<option value="">No voters without vouchers</option>';
                    }
                } else {
                    console.error('API returned error:', data.message || 'Unknown error');
                    voterSelect.innerHTML = '<option value="">Error loading voters</option>';
                }
            })
            .catch(function(error) {
                console.error('Error fetching voters without vouchers:', error);
                voterSelect.innerHTML = '<option value="">Error loading voters</option>';
            });
    }
    
    // Function to load voters without vouchers for bulk QR generation
    function loadVotersForBulk() {
        console.log('Loading voters without vouchers for bulk QR generation');
        if (!voterCheckboxes) {
            console.error('Voter checkboxes container not found');
            return;
        }
        
        // Show loading indicator
        voterCheckboxes.innerHTML = '<p class="text-gray-500 text-sm">Loading voters...</p>';
        
        fetch(BASE_URL + '/src/api/get_voters_without_vouchers.php')
            .then(function(response) {
                console.log('API response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.json();
            })
            .then(function(data) {
                console.log('API data received:', data);
                if (data.success) {
                    voterCheckboxes.innerHTML = '';
                    
                    if (data.voters && data.voters.length > 0) {
                        data.voters.forEach(function(voter) {
                            var div = document.createElement('div');
                            div.className = 'flex items-center';
                            
                            var checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.name = 'voter_ids[]';
                            checkbox.value = voter.id;
                            checkbox.id = 'voter-' + voter.id;
                            checkbox.className = 'h-4 w-4 text-suriname-green focus:ring-suriname-green border-gray-300 rounded';
                            
                            var label = document.createElement('label');
                            label.htmlFor = 'voter-' + voter.id;
                            label.className = 'ml-2 block text-sm text-gray-900';
                            label.textContent = voter.first_name + ' ' + voter.last_name + ' (' + voter.id_number + ')';
                            
                            div.appendChild(checkbox);
                            div.appendChild(label);
                            voterCheckboxes.appendChild(div);
                        });
                        
                        console.log('Loaded', data.voters.length, 'voters for bulk QR generation');
                    } else {
                        console.log('No voters without vouchers found for bulk generation');
                        voterCheckboxes.innerHTML = '<p class="text-gray-500 text-sm">No voters without vouchers</p>';
                    }
                } else {
                    console.error('API returned error:', data.message || 'Unknown error');
                    voterCheckboxes.innerHTML = '<p class="text-red-500 text-sm">Error loading voters</p>';
                }
            })
            .catch(function(error) {
                console.error('Error fetching voters for bulk:', error);
                voterCheckboxes.innerHTML = '<p class="text-red-500 text-sm">Error loading voters</p>';
            });
    }
    
    // Auto-open single QR modal if we have a generate_for parameter
    if (window.autoOpenSingleQrModal && generateSingleBtn) {
        console.log('Auto-opening single QR modal');
        generateSingleBtn.click();
    }
});

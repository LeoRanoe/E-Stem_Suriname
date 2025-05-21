// QR Codes Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterTableRows(searchTerm);
        });
    }

    // Filter functionality
    const districtFilter = document.getElementById('districtFilter');
    const statusFilter = document.getElementById('statusFilter');
    
    if (districtFilter) {
        districtFilter.addEventListener('change', applyFilters);
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }

    // Filter table rows based on search and filters
    function filterTableRows(searchTerm = '') {
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const textContent = row.textContent.toLowerCase();
            if (textContent.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Apply all active filters
    function applyFilters() {
        const districtValue = districtFilter ? districtFilter.value : '';
        const statusValue = statusFilter ? statusFilter.value : '';
        const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
        
        const rows = document.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const districtMatch = !districtValue || 
                row.querySelector('td[data-district]').dataset.district === districtValue;
            
            const statusMatch = !statusValue || 
                row.querySelector('td[data-status]').dataset.status === statusValue;
            
            const searchMatch = !searchValue || 
                row.textContent.toLowerCase().includes(searchValue);
            
            if (districtMatch && statusMatch && searchMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
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
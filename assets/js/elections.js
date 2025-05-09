document.addEventListener('DOMContentLoaded', () => {
    // New Election Modal Handling
    const newElectionModal = document.getElementById('newElectionModal');
    const openNewElectionModalBtn = document.getElementById('openNewElectionModalBtn');
    const cancelNewElectionModalBtn = document.getElementById('cancelNewElectionModalBtn');
    const modalOverlay = document.querySelector('#newElectionModal > div[class*="bg-gray-700"]');

    if (openNewElectionModalBtn && newElectionModal) {
        openNewElectionModalBtn.addEventListener('click', () => {
            newElectionModal.classList.remove('hidden');
        });
    }

    if (cancelNewElectionModalBtn && newElectionModal) {
        cancelNewElectionModalBtn.addEventListener('click', () => {
            newElectionModal.classList.add('hidden');
        });
    }

    if (modalOverlay && newElectionModal) {
        modalOverlay.addEventListener('click', (event) => {
            // Only hide if the click is directly on the overlay
            if (event.target === modalOverlay) {
                newElectionModal.classList.add('hidden');
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && newElectionModal && !newElectionModal.classList.contains('hidden')) {
            newElectionModal.classList.add('hidden');
        }
    });

    // Delete Confirmation for Upcoming Elections
    const deleteButtons = document.querySelectorAll('.confirm-delete-election');

    deleteButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            const confirmation = confirm('Weet u zeker dat u deze verkiezing wilt verwijderen?');
            if (!confirmation) {
                event.preventDefault(); // Prevent form submission
            }
        });
    });
});
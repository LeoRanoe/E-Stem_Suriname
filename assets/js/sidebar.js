// Initialize Flowbite drawer component for admin sidebar
document.addEventListener('DOMContentLoaded', function() {
    // Options for the drawer
    const drawerOptions = {
        placement: 'left',
        backdrop: true,
        bodyScrolling: false,
        edge: false,
        edgeOffset: 'bottom-[60px]',
        backdropClasses: 'bg-gray-900 bg-opacity-50 dark:bg-opacity-80 fixed inset-0 z-30'
    };

    // Initialize drawer
    const drawerEl = document.getElementById('admin-sidebar');
    const drawer = new Drawer(drawerEl, drawerOptions);

    // Toggle button event listeners
    const toggleButtons = document.querySelectorAll('[data-drawer-toggle="admin-sidebar"]');
    toggleButtons.forEach(button => {
        button.addEventListener('click', () => {
            drawer.toggle();
        });
    });

    // Close drawer when clicking on a nav link (for mobile)
    const navLinks = document.querySelectorAll('#admin-sidebar a');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 768) { // Only on mobile
                drawer.hide();
            }
        });
    });
});
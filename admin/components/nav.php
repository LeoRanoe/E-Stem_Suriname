<?php
// Get the current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Navigation items configuration
// Navigation items configuration - Paths relative to project root
$nav_items = [
    'admin/index.php' => ['icon' => 'home', 'label' => 'Dashboard'],
    'src/views/candidates.php' => ['icon' => 'user-tie', 'label' => 'Kandidaten'],
    'src/views/elections.php' => ['icon' => 'vote-yea', 'label' => 'Verkiezingen'],
    'src/views/parties.php' => ['icon' => 'flag', 'label' => 'Partijen'],
    'src/views/qrcodes.php' => ['icon' => 'qrcode', 'label' => 'QR Codes'],
    'src/views/voters.php' => ['icon' => 'users', 'label' => 'Stemmers'],
    'src/views/results.php' => ['icon' => 'chart-bar', 'label' => 'Resultaten']
];

// Determine initial expanded state based on screen size (using a simple check, JS will refine)
// We default to expanded and let JS handle collapsing on smaller screens initially.
$is_expanded_initial = true; 
?>

<!-- Sidebar -->
<div id="sidebar" class="fixed left-0 top-0 h-full bg-white shadow-lg z-50 transition-all duration-300 ease-in-out <?= $is_expanded_initial ? 'w-64' : 'w-16' ?>">
    
    <!-- Header & Toggle Button -->
    <div class="flex items-center justify-between p-4 border-b border-gray-200 h-16">
        <h1 class="text-xl font-bold text-suriname-green flex items-center">
            <!-- Shield icon removed -->
            <span class="nav-item-label <?= $is_expanded_initial ? 'inline' : 'hidden' ?>">Admin Dashboard</span>
        </h1>
        <!-- Removed lg:hidden to make toggle always visible -->
        <button id="sidebar-toggle" class="text-gray-700 hover:text-suriname-green focus:outline-none">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <!-- Navigation -->
    <nav class="mt-4 overflow-y-auto overflow-x-hidden h-[calc(100vh-8rem)]"> <!-- Adjusted height -->
        <?php foreach ($nav_items as $page => $item): ?>
            <!-- Use BASE_URL for absolute paths -->
            <a href="<?= BASE_URL . '/' . $page ?>"
               class="flex items-center px-4 py-2.5 transition-colors duration-200 ease-in-out
                      <?= $current_page === basename($page) // Compare only the filename for active state
                          ? 'bg-suriname-green text-white shadow-md'
                          : 'text-gray-700 hover:bg-gray-100' ?>
                      <?= !$is_expanded_initial ? 'justify-center' : '' ?>">
                <i class="fas fa-<?= $item['icon'] ?> <?= $is_expanded_initial ? 'mr-3 w-5 text-center' : 'w-5 text-center mx-auto' ?>"></i>
                <span class="nav-item-label <?= $is_expanded_initial ? 'inline' : 'hidden' ?>"><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>
        
        <!-- Logout (always at bottom) -->
        <div class="absolute bottom-0 left-0 right-0 border-t border-gray-200">
            <!-- Use BASE_URL for logout link -->
            <a href="<?= BASE_URL ?>/pages/logout.php"
               class="flex items-center px-4 py-2.5 text-red-600 hover:bg-red-50 transition-colors duration-200 ease-in-out
                      <?= !$is_expanded_initial ? 'justify-center' : '' ?>">
                 <i class="fas fa-sign-out-alt <?= $is_expanded_initial ? 'mr-3 w-5 text-center' : 'w-5 text-center mx-auto' ?>"></i>
                <span class="nav-item-label <?= $is_expanded_initial ? 'inline' : 'hidden' ?>">Logout</span>
            </a>
        </div>
    </nav>
</div>

<!-- JavaScript for Sidebar Toggle -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('sidebar');
        const toggleButton = document.getElementById('sidebar-toggle');
        const mainContent = document.getElementById('main-content'); // Target ID added in layout.php
        const navLabels = sidebar.querySelectorAll('.nav-item-label');
        const navIcons = sidebar.querySelectorAll('nav a i, .p-4 i'); // Include header icon
        const navLinks = sidebar.querySelectorAll('nav a, .absolute a'); // Include logout link
        const headerIcon = sidebar.querySelector('.p-4 i'); // Specific header icon

        const LG_BREAKPOINT = 1024; // Tailwind's lg breakpoint

        function setSidebarState(isExpanded) {
            // --- Sidebar Width ---
            sidebar.classList.toggle('w-64', isExpanded);
            sidebar.classList.toggle('w-16', !isExpanded);

            // --- Main Content Margin ---
            if (mainContent) {
                // Ensure correct classes are present based on state for different screen sizes
                mainContent.classList.toggle('lg:ml-64', isExpanded); // Margin for large screens when expanded
                mainContent.classList.toggle('ml-16', !isExpanded); // Margin for small screens OR large screens when collapsed
                
                // Explicitly remove the other class to avoid conflicts if needed, though toggle should handle it.
                // If expanded, ensure ml-16 is NOT present. If collapsed, ensure lg:ml-64 is NOT present.
                if (isExpanded) {
                    mainContent.classList.remove('ml-16');
                } else {
                    mainContent.classList.remove('lg:ml-64');
                }
            } else {
                console.error("Debug: Main content element with ID 'main-content' not found.");
            }

            // --- Labels, Icons, Links ---
            navLabels.forEach(label => label.classList.toggle('hidden', !isExpanded));
            navIcons.forEach(icon => {
                icon.classList.toggle('mx-auto', !isExpanded); // Center icon when collapsed
                icon.classList.toggle('mr-3', isExpanded);    // Add margin when expanded
            });
            navLinks.forEach(link => link.classList.toggle('justify-center', !isExpanded)); // Center link content when collapsed

            // --- Header Icon Specific Styling ---
            if(headerIcon) {
                headerIcon.classList.toggle('mr-2', isExpanded);    // Margin for expanded
                headerIcon.classList.toggle('mx-auto', !isExpanded); // Center when collapsed
            }

            // --- Store State ---
            sidebar.dataset.expanded = isExpanded;
        }

        // --- Initial State ---
        // Check if sidebar and mainContent exist before proceeding
        if (sidebar && mainContent) {
            let initialState = window.innerWidth >= LG_BREAKPOINT;
            setSidebarState(initialState);

            // --- Event Listeners ---
            if (toggleButton) {
                toggleButton.addEventListener('click', () => {
                    const currentState = sidebar.dataset.expanded === 'true';
                    setSidebarState(!currentState);
                });
            } else {
                 console.error("Debug: Sidebar toggle button with ID 'sidebar-toggle' not found.");
            }

            window.addEventListener('resize', () => {
                let shouldBeExpanded = window.innerWidth >= LG_BREAKPOINT;
                // Avoid unnecessary state changes if already correct
                if (String(shouldBeExpanded) !== sidebar.dataset.expanded) {
                     setSidebarState(shouldBeExpanded);
                }
            });
        } else {
             console.error("Debug: Sidebar or Main Content element not found. Navigation script cannot initialize properly.");
        }
    });
</script>
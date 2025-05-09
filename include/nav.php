<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// Fetch user details if logged in
$userName = '';
$isAdmin = false;
if (isset($_SESSION['User ID'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM users 
            WHERE UserID = :userID
        ");
        $stmt->execute(['userID' => $_SESSION['User ID']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userName = $user ? htmlspecialchars($user['Voornaam']) : 'User';
        $isAdmin = $user && $user['Role'] === 'admin';
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="bg-white shadow-lg border-b-4 border-suriname-green animate-fade-in dark:bg-gray-800 transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?= BASE_URL ?>/index.php" class="flex items-center hover:opacity-90 transition-opacity duration-200">
                        <img class="h-12 w-auto" src="<?= BASE_URL ?>/assets/images/logo.png" alt="<?= SITE_NAME ?>">
                        <span class="ml-2 text-xl font-bold text-suriname-green dark:text-white">E-Stem</span>
                    </a>
                </div>

                <!-- Desktop Toggle Button - More visible -->
                <button type="button"
                        class="ml-4 inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-white hover:bg-suriname-green focus:outline-none focus:ring-2 focus:ring-suriname-green transition-all duration-200 lg:flex hidden"
                        onclick="toggleDesktopMenu()"
                        aria-label="Toggle desktop menu"
                        aria-expanded="false">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Navigation Links - Enhanced styling -->
                <div id="desktop-menu" class="hidden lg:flex lg:ml-6 lg:space-x-4">
                    <a href="<?= BASE_URL ?>/index.php"
                       class="<?= $current_page === 'index.php' ? 'bg-suriname-green text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?> px-3 py-2 rounded-md text-sm font-medium transition-all duration-200">
                        <i class="fas fa-home mr-2"></i>Home
                    </a>
                    <?php if (isset($_SESSION['User ID'])): ?>
                        <a href="<?= BASE_URL ?>/src/views/scan.php"
                           class="<?= $current_page === 'scan.php' ? 'bg-suriname-green text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?> px-3 py-2 rounded-md text-sm font-medium transition-all duration-200">
                            <i class="fas fa-vote-yea mr-2"></i>Stemmen
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right side - Enhanced styling -->
            <div id="desktop-right-menu" class="hidden lg:flex lg:ml-4 lg:items-center lg:space-x-3">
                <?php if (isset($_SESSION['User ID'])): ?>
                    <?php if (isset($_SESSION['IsAdmin']) && $_SESSION['IsAdmin']): ?>
                        <a href="<?= BASE_URL ?>/admin/dashboard.php"
                           class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-suriname-green hover:bg-suriname-dark-green shadow-md transition-all duration-200 hover:scale-105">
                            <i class="fas fa-cog mr-2"></i>
                            Admin Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/pages/logout.php"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-suriname-red hover:bg-suriname-dark-red shadow-md transition-all duration-200 hover:scale-105">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Uitloggen
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/src/views/login.php"
                       class="inline-flex items-center px-4 py-2 text-sm font-medium rounded-md text-white bg-suriname-green hover:bg-suriname-dark-green shadow-md transition-all duration-200 hover:scale-105">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Inloggen
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button type="button" 
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-suriname-green hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-suriname-green"
                        aria-controls="mobile-menu" 
                        aria-expanded="false"
                        onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <span class="sr-only">Open main menu</span>
                    <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu - Enhanced styling -->
    <div class="lg:hidden hidden transition-all duration-300 ease-in-out" id="mobile-menu">
        <div class="pt-2 pb-4 space-y-1 px-2">
            <a href="<?= BASE_URL ?>/index.php"
               class="<?= $current_page === 'index.php' ? 'bg-suriname-green text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?> block px-3 py-2 rounded-md text-base font-medium transition-all duration-200">
                <i class="fas fa-home mr-2"></i>
                Home
            </a>
            <?php if (isset($_SESSION['User ID'])): ?>
                <a href="<?= BASE_URL ?>/src/views/scan.php"
                   class="<?= $current_page === 'scan.php' ? 'bg-suriname-green text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' ?> block px-3 py-2 rounded-md text-base font-medium transition-all duration-200">
                    <i class="fas fa-vote-yea mr-2"></i>
                    Stemmen
                </a>
            <?php endif; ?>
            <?php if (isset($_SESSION['User ID'])): ?>
                <?php if (isset($_SESSION['IsAdmin']) && $_SESSION['IsAdmin']): ?>
                    <a href="<?= BASE_URL ?>/admin/dashboard.php"
                       class="text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 block px-3 py-2 rounded-md text-base font-medium transition-all duration-200">
                        <i class="fas fa-cog mr-2"></i>
                        Admin Dashboard
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/pages/logout.php"
                   class="text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 block px-3 py-2 rounded-md text-base font-medium transition-all duration-200">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Uitloggen
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/src/views/login.php"
                   class="text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 block px-3 py-2 rounded-md text-base font-medium transition-all duration-200">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Inloggen
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
// Enhanced menu toggle functions with animations
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const isHidden = mobileMenu.classList.contains('hidden');
    
    if (isHidden) {
        mobileMenu.classList.remove('hidden');
        mobileMenu.style.opacity = '0';
        mobileMenu.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            mobileMenu.style.opacity = '1';
            mobileMenu.style.transform = 'translateY(0)';
            mobileMenu.style.transition = 'opacity 200ms ease, transform 200ms ease';
        }, 10);
    } else {
        mobileMenu.style.opacity = '0';
        mobileMenu.style.transform = 'translateY(-10px)';
        mobileMenu.style.transition = 'opacity 200ms ease, transform 200ms ease';
        setTimeout(() => {
            mobileMenu.classList.add('hidden');
        }, 200);
    }
    
    // Update button state
    const button = document.querySelector('[aria-controls="mobile-menu"]');
    button.setAttribute('aria-expanded', !isHidden);
    button.querySelectorAll('svg').forEach(svg => svg.classList.toggle('hidden'));
}

function toggleDesktopMenu() {
    const desktopMenu = document.getElementById('desktop-menu');
    const desktopRightMenu = document.getElementById('desktop-right-menu');
    const isHidden = desktopMenu.classList.contains('hidden');
    
    if (isHidden) {
        desktopMenu.classList.remove('hidden');
        desktopRightMenu.classList.remove('hidden');
        desktopMenu.style.opacity = '0';
        desktopRightMenu.style.opacity = '0';
        setTimeout(() => {
            desktopMenu.style.opacity = '1';
            desktopRightMenu.style.opacity = '1';
            desktopMenu.style.transition = 'opacity 200ms ease';
            desktopRightMenu.style.transition = 'opacity 200ms ease';
        }, 10);
    } else {
        desktopMenu.style.opacity = '0';
        desktopRightMenu.style.opacity = '0';
        desktopMenu.style.transition = 'opacity 200ms ease';
        desktopRightMenu.style.transition = 'opacity 200ms ease';
        setTimeout(() => {
            desktopMenu.classList.add('hidden');
            desktopRightMenu.classList.add('hidden');
        }, 200);
    }
    
    // Update button state
    const button = document.querySelector('[onclick="toggleDesktopMenu()"]');
    button.setAttribute('aria-expanded', !isHidden);
    button.classList.toggle('bg-suriname-green');
    button.classList.toggle('text-white');
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    const nav = document.querySelector('nav');
    if (!nav.contains(event.target)) {
        document.getElementById('mobile-menu').classList.add('hidden');
        document.querySelector('[aria-controls="mobile-menu"]').setAttribute('aria-expanded', 'false');
        document.querySelector('[aria-controls="mobile-menu"] svg').classList.add('hidden');
        document.querySelector('[aria-controls="mobile-menu"] svg + svg').classList.remove('hidden');
    }
});
</script>
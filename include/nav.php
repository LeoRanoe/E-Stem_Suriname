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

<nav class="bg-white shadow-lg border-b-4 border-suriname-green">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?= BASE_URL ?>/index.php" class="flex items-center">
                        <img class="h-12 w-auto" src="<?= BASE_URL ?>/assets/images/logo.png" alt="<?= SITE_NAME ?>">
                        <span class="ml-2 text-xl font-bold text-suriname-green">E-Stem</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="<?= BASE_URL ?>/index.php" 
                       class="<?= $current_page === 'index.php' ? 'border-suriname-green text-suriname-green' : 'border-transparent text-gray-500 hover:border-suriname-green hover:text-suriname-green' ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Home
                    </a>
                    <?php if (isset($_SESSION['User ID'])): ?>
                        <a href="<?= BASE_URL ?>/pages/scan.php" 
                           class="<?= $current_page === 'scan.php' ? 'border-suriname-green text-suriname-green' : 'border-transparent text-gray-500 hover:border-suriname-green hover:text-suriname-green' ?> inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            Stemmen
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right side -->
            <div class="hidden sm:ml-6 sm:flex sm:items-center">
                <?php if (isset($_SESSION['User ID'])): ?>
                    <?php if (isset($_SESSION['IsAdmin']) && $_SESSION['IsAdmin']): ?>
                        <a href="<?= BASE_URL ?>/admin/dashboard.php" 
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-suriname-green hover:bg-suriname-dark-green shadow-md">
                            <i class="fas fa-cog mr-2"></i>
                            Admin Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/pages/logout.php" 
                       class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-suriname-red hover:bg-suriname-dark-red shadow-md">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Uitloggen
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/pages/login.php" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-suriname-green hover:bg-suriname-dark-green shadow-md">
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

    <!-- Mobile menu -->
    <div class="sm:hidden hidden" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1">
            <a href="<?= BASE_URL ?>/index.php" 
               class="<?= $current_page === 'index.php' ? 'bg-suriname-green text-white' : 'text-gray-500 hover:bg-suriname-green hover:text-white' ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                <i class="fas fa-home mr-2"></i>
                Home
            </a>
            <?php if (isset($_SESSION['User ID'])): ?>
                <a href="<?= BASE_URL ?>/pages/scan.php" 
                   class="<?= $current_page === 'scan.php' ? 'bg-suriname-green text-white' : 'text-gray-500 hover:bg-suriname-green hover:text-white' ?> block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    <i class="fas fa-vote-yea mr-2"></i>
                    Stemmen
                </a>
            <?php endif; ?>
            <?php if (isset($_SESSION['User ID'])): ?>
                <?php if (isset($_SESSION['IsAdmin']) && $_SESSION['IsAdmin']): ?>
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" 
                       class="text-gray-500 hover:bg-suriname-green hover:text-white block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                        <i class="fas fa-cog mr-2"></i>
                        Admin Dashboard
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/pages/logout.php" 
                   class="text-gray-500 hover:bg-suriname-red hover:text-white block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Uitloggen
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/pages/login.php" 
                   class="text-gray-500 hover:bg-suriname-green hover:text-white block pl-3 pr-4 py-2 border-l-4 text-base font-medium">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Inloggen
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const isHidden = mobileMenu.classList.contains('hidden');
    mobileMenu.classList.toggle('hidden');
    
    // Update aria-expanded
    const button = document.querySelector('[aria-controls="mobile-menu"]');
    button.setAttribute('aria-expanded', !isHidden);
}
</script>
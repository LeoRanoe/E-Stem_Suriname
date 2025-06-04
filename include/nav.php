<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_connect.php';

// Fetch user details if logged in
$userName = '';
$isAdmin = false;
$isVoter = false;

// Check for admin session
if (isset($_SESSION['AdminID'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM admins 
            WHERE AdminID = :adminID AND Status = 'active'
        ");
        $stmt->execute(['adminID' => $_SESSION['AdminID']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            $userName = $admin['FirstName'] . ' ' . $admin['LastName'];
            $isAdmin = true;
        }
    } catch (PDOException $e) {
        error_log("Admin session error: " . $e->getMessage());
    }
}
// Check for voter session (support both session variable formats)
else if (isset($_SESSION['voter_id']) || isset($_SESSION['VoterID'])) {
    $voterId = $_SESSION['voter_id'] ?? $_SESSION['VoterID'];
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM voters 
            WHERE id = :voterID AND status = 'active'
        ");
        $stmt->execute(['voterID' => $voterId]);
        $voter = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($voter) {
            $userName = $voter['first_name'] . ' ' . $voter['last_name'];
            $isVoter = true;
        }
    } catch (PDOException $e) {
        error_log("Voter session error: " . $e->getMessage());
    }
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="bg-white shadow-suriname sticky top-0 z-50 border-b-2 border-suriname-green">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center">
                    <a href="<?= BASE_URL ?>/index.php" class="flex items-center group">
                        <img class="h-12 w-auto transition-transform duration-300 group-hover:scale-105" 
                             src="<?= BASE_URL ?>/assets/Images/logo.png" 
                             alt="E-Stem Suriname Logo">
                        <span class="ml-2 text-xl font-bold text-suriname-green">E-Stem</span>
                    </a>
                </div>

                <!-- Navigation Links - Pill Shape -->
                <div class="hidden sm:ml-6 sm:flex sm:space-x-4 items-center">
                    <div class="bg-gray-100 rounded-full p-1.5 flex space-x-1">
                        <a href="<?= BASE_URL ?>/index.php"
                           class="<?= $current_page === 'index.php' ? 'bg-suriname-green text-white' : 'text-gray-700 hover:text-suriname-green hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 flex items-center">
                            <i class="fas fa-home mr-2"></i>
                            Home
                        </a>
                        <?php if ($isVoter || isset($_SESSION['voter_id']) || isset($_SESSION['VoterID'])): ?>
                            <a href="<?= BASE_URL ?>/pages/voting/index.php"
                               class="<?= $current_page === 'index.php' && strpos($_SERVER['PHP_SELF'], '/pages/voting/') !== false ? 'bg-suriname-green text-white' : 'text-gray-700 hover:text-suriname-green hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 flex items-center">
                                <i class="fas fa-vote-yea mr-2"></i>
                                Stemmen
                            </a>
                            <a href="<?= BASE_URL ?>/src/views/results.php"
                               class="<?= $current_page === 'results.php' ? 'bg-suriname-green text-white' : 'text-gray-700 hover:text-suriname-green hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 flex items-center">
                                <i class="fas fa-chart-bar mr-2"></i>
                                Resultaten
                            </a>
                            <a href="<?= BASE_URL ?>/voter/profile.php"
                               class="<?= $current_page === 'profile.php' ? 'bg-suriname-green text-white' : 'text-gray-700 hover:text-suriname-green hover:bg-gray-200' ?> px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 flex items-center">
                                <i class="fas fa-user mr-2"></i>
                                Profiel
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right side -->
            <div class="hidden sm:ml-6 sm:flex sm:items-center">
                <?php if ($isAdmin || $isVoter || isset($_SESSION['voter_id']) || isset($_SESSION['VoterID'])): ?>
                    <?php if ($isAdmin): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php"
                           class="sr-button sr-button-primary mr-3">
                            <i class="fas fa-cog mr-2"></i>
                            Admin Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/include/logout.php" 
                       class="sr-button sr-button-accent">
                        <i class="fas fa-sign-out-alt mr-2 group-hover:animate-bounce-subtle"></i>
                        Uitloggen
                    </a>
                <?php else: ?>
                    <div class="flex space-x-2">
                        <a href="<?= BASE_URL ?>/voter/index.php" class="sr-button sr-button-secondary">
                            <i class="fas fa-sign-in-alt mr-2"></i> Inloggen
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button type="button" 
                        class="inline-flex items-center justify-center p-2 rounded-full text-gray-400 hover:text-suriname-green hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-suriname-green transition-colors duration-200"
                        aria-controls="mobile-menu" 
                        aria-expanded="false"
                        onclick="toggleMobileMenu()">
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
    <div class="sm:hidden hidden animate-fade-in" id="mobile-menu">
        <div class="pt-2 pb-3 space-y-1 border-t border-gray-200 bg-gray-50 p-4">
            <div class="flex flex-col space-y-2">
                <a href="<?= BASE_URL ?>/index.php" 
                   class="<?= $current_page === 'index.php' ? 'bg-suriname-green text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?> flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors duration-200 shadow-sm">
                    <i class="fas fa-home mr-3 w-5 text-center"></i>
                    Home
                </a>
                <?php if ($isVoter || isset($_SESSION['voter_id']) || isset($_SESSION['VoterID'])): ?>
                    <a href="<?= BASE_URL ?>/pages/voting/index.php" 
                       class="<?= $current_page === 'index.php' && strpos($_SERVER['PHP_SELF'], '/pages/voting/') !== false ? 'bg-suriname-green text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?> flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors duration-200 shadow-sm">
                        <i class="fas fa-vote-yea mr-3 w-5 text-center"></i>
                        Stemmen
                    </a>
                    <a href="<?= BASE_URL ?>/src/views/results.php" 
                       class="<?= $current_page === 'results.php' ? 'bg-suriname-green text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?> flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors duration-200 shadow-sm">
                        <i class="fas fa-chart-bar mr-3 w-5 text-center"></i>
                        Resultaten
                    </a>
                    <a href="<?= BASE_URL ?>/voter/profile.php" 
                       class="<?= $current_page === 'profile.php' ? 'bg-suriname-green text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?> flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors duration-200 shadow-sm">
                        <i class="fas fa-user mr-3 w-5 text-center"></i>
                        Mijn Profiel
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/voter/index.php" 
                       class="bg-white text-gray-700 hover:bg-gray-100 flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors duration-200 shadow-sm">
                        <i class="fas fa-sign-in-alt mr-3 w-5 text-center"></i>
                        Inloggen
                    </a>
                <?php endif; ?>
                
                <?php if ($isAdmin || $isVoter): ?>
                    <?php if ($isAdmin): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php"
                           class="bg-suriname-green text-white flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors duration-200 shadow-sm">
                            <i class="fas fa-cog mr-3 w-5 text-center"></i>
                            Admin Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="<?= BASE_URL ?>/include/logout.php" 
                       class="bg-suriname-red text-white flex items-center px-4 py-3 rounded-lg text-base font-medium transition-colors duration-200 shadow-sm">
                        <i class="fas fa-sign-out-alt mr-3 w-5 text-center"></i>
                        Uitloggen
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    const isHidden = mobileMenu.classList.contains('hidden');
    
    if (isHidden) {
        mobileMenu.classList.remove('hidden');
        mobileMenu.classList.add('animate-fade-in');
    } else {
        mobileMenu.classList.add('hidden');
        mobileMenu.classList.remove('animate-fade-in');
    }
    
    // Update aria-expanded
    const button = document.querySelector('[aria-controls="mobile-menu"]');
    button.setAttribute('aria-expanded', !isHidden);
    
    // Change button appearance
    const menuOpenIcon = button.querySelector('svg.block');
    const menuCloseIcon = button.querySelector('svg.hidden');
    
    menuOpenIcon.classList.toggle('hidden');
    menuOpenIcon.classList.toggle('block');
    menuCloseIcon.classList.toggle('hidden');
    menuCloseIcon.classList.toggle('block');
}
</script>
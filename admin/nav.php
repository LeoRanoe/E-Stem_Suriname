<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/db_connect.php';

// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="bg-white/95 backdrop-blur-lg shadow-lg fixed w-full top-0 z-50 border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <!-- Mobile Menu Button -->
            <button class="lg:hidden text-suriname-green hover:text-suriname-red transition-colors duration-300 focus:outline-none" id="mobile-menu-button">
                <i class="fas fa-bars text-2xl"></i>
            </button>

            <!-- Logo -->
            <a href="<?= BASE_URL ?>/admin/index.php" class="flex items-center space-x-3 group">
                <div class="relative">
                    <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Logo" class="h-12 w-auto">
                    <div class="absolute -inset-1 bg-suriname-green/10 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </div>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden lg:flex items-center space-x-1">
                <a href="<?= BASE_URL ?>/admin/index.php" class="px-4 py-2 text-gray-700 hover:text-suriname-green transition-colors duration-300 rounded-lg hover:bg-gray-50/80 relative group <?= $current_page === 'index.php' ? 'text-suriname-green' : '' ?>">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    <span>Dashboard</span>
                    <div class="absolute bottom-0 left-0 w-full h-0.5 bg-suriname-green transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                </a>
                <a href="<?= BASE_URL ?>/admin/elections.php" class="px-4 py-2 text-gray-700 hover:text-suriname-green transition-colors duration-300 rounded-lg hover:bg-gray-50/80 relative group <?= $current_page === 'elections.php' ? 'text-suriname-green' : '' ?>">
                    <i class="fas fa-vote-yea mr-2"></i>
                    <span>Verkiezingen</span>
                    <div class="absolute bottom-0 left-0 w-full h-0.5 bg-suriname-green transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                </a>
                <a href="<?= BASE_URL ?>/admin/candidates.php" class="px-4 py-2 text-gray-700 hover:text-suriname-green transition-colors duration-300 rounded-lg hover:bg-gray-50/80 relative group <?= $current_page === 'candidates.php' ? 'text-suriname-green' : '' ?>">
                    <i class="fas fa-user-tie mr-2"></i>
                    <span>Kandidaten</span>
                    <div class="absolute bottom-0 left-0 w-full h-0.5 bg-suriname-green transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                </a>
                <a href="<?= BASE_URL ?>/admin/parties.php" class="px-4 py-2 text-gray-700 hover:text-suriname-green transition-colors duration-300 rounded-lg hover:bg-gray-50/80 relative group <?= $current_page === 'parties.php' ? 'text-suriname-green' : '' ?>">
                    <i class="fas fa-flag mr-2"></i>
                    <span>Partijen</span>
                    <div class="absolute bottom-0 left-0 w-full h-0.5 bg-suriname-green transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                </a>
                <a href="<?= BASE_URL ?>/admin/qrcodes.php" class="px-4 py-2 text-gray-700 hover:text-suriname-green transition-colors duration-300 rounded-lg hover:bg-gray-50/80 relative group <?= $current_page === 'qrcodes.php' ? 'text-suriname-green' : '' ?>">
                    <i class="fas fa-qrcode mr-2"></i>
                    <span>QR Codes</span>
                    <div class="absolute bottom-0 left-0 w-full h-0.5 bg-suriname-green transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                </a>
                <a href="<?= BASE_URL ?>/admin/voters.php" class="px-4 py-2 text-gray-700 hover:text-suriname-green transition-colors duration-300 rounded-lg hover:bg-gray-50/80 relative group <?= $current_page === 'voters.php' ? 'text-suriname-green' : '' ?>">
                    <i class="fas fa-user-friends mr-2"></i>
                    <span>Stemmers</span>
                    <div class="absolute bottom-0 left-0 w-full h-0.5 bg-suriname-green transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                </a>
                <a href="<?= BASE_URL ?>/admin/results.php" class="px-4 py-2 text-gray-700 hover:text-suriname-green transition-colors duration-300 rounded-lg hover:bg-gray-50/80 relative group <?= $current_page === 'results.php' ? 'text-suriname-green' : '' ?>">
                    <i class="fas fa-chart-bar mr-2"></i>
                    <span>Resultaten</span>
                    <div class="absolute bottom-0 left-0 w-full h-0.5 bg-suriname-green transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></div>
                </a>
            </div>

            <!-- Auth Buttons -->
            <div class="hidden lg:flex items-center space-x-4">
                <a href="<?= BASE_URL ?>/index.php" class="flex items-center space-x-2 text-gray-700 hover:text-suriname-green transition-colors duration-300">
                    <i class="fas fa-home mr-2"></i>
                    <span>Frontend</span>
                </a>
                <a href="<?= BASE_URL ?>/pages/logout.php" class="flex items-center space-x-2 border-2 border-suriname-red text-suriname-red px-6 py-2.5 rounded-full hover:bg-suriname-red hover:text-white transition-all duration-300 transform hover:-translate-y-1 shadow-md hover:shadow-lg group">
                    <i class="fas fa-sign-out-alt transform group-hover:scale-110 transition-transform duration-300"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="lg:hidden hidden animate-slide-down" id="mobile-menu">
        <div class="mt-2 py-4 flex flex-col space-y-2 bg-white rounded-xl shadow-xl border border-gray-100">
            <a href="<?= BASE_URL ?>/admin/index.php" class="px-6 py-3 text-gray-700 hover:text-suriname-green hover:bg-gray-50/80 transition-colors duration-300 flex items-center space-x-3 <?= $current_page === 'index.php' ? 'text-suriname-green bg-gray-50' : '' ?>">
                <i class="fas fa-tachometer-alt text-suriname-green"></i>
                <span>Dashboard</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/elections.php" class="px-6 py-3 text-gray-700 hover:text-suriname-green hover:bg-gray-50/80 transition-colors duration-300 flex items-center space-x-3 <?= $current_page === 'elections.php' ? 'text-suriname-green bg-gray-50' : '' ?>">
                <i class="fas fa-vote-yea text-suriname-green"></i>
                <span>Verkiezingen</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/candidates.php" class="px-6 py-3 text-gray-700 hover:text-suriname-green hover:bg-gray-50/80 transition-colors duration-300 flex items-center space-x-3 <?= $current_page === 'candidates.php' ? 'text-suriname-green bg-gray-50' : '' ?>">
                <i class="fas fa-user-tie text-suriname-green"></i>
                <span>Kandidaten</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/parties.php" class="px-6 py-3 text-gray-700 hover:text-suriname-green hover:bg-gray-50/80 transition-colors duration-300 flex items-center space-x-3 <?= $current_page === 'parties.php' ? 'text-suriname-green bg-gray-50' : '' ?>">
                <i class="fas fa-flag text-suriname-green"></i>
                <span>Partijen</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/qrcodes.php" class="px-6 py-3 text-gray-700 hover:text-suriname-green hover:bg-gray-50/80 transition-colors duration-300 flex items-center space-x-3 <?= $current_page === 'qrcodes.php' ? 'text-suriname-green bg-gray-50' : '' ?>">
                <i class="fas fa-qrcode text-suriname-green"></i>
                <span>QR Codes</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/voters.php" class="px-6 py-3 text-gray-700 hover:text-suriname-green hover:bg-gray-50/80 transition-colors duration-300 flex items-center space-x-3 <?= $current_page === 'voters.php' ? 'text-suriname-green bg-gray-50' : '' ?>">
                <i class="fas fa-user-friends text-suriname-green"></i>
                <span>Stemmers</span>
            </a>
            <a href="<?= BASE_URL ?>/admin/results.php" class="px-6 py-3 text-gray-700 hover:text-suriname-green hover:bg-gray-50/80 transition-colors duration-300 flex items-center space-x-3 <?= $current_page === 'results.php' ? 'text-suriname-green bg-gray-50' : '' ?>">
                <i class="fas fa-chart-bar text-suriname-green"></i>
                <span>Resultaten</span>
            </a>
            <a href="<?= BASE_URL ?>/pages/logout.php" class="px-6 py-3 text-red-600 hover:text-red-700 hover:bg-red-50/80 transition-colors duration-300 flex items-center space-x-3">
                <i class="fas fa-sign-out-alt text-red-600"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</nav>

<script>
    // Mobile menu toggle with animation
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    mobileMenuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
        if (!mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('animate-slide-down');
        }
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!mobileMenu.contains(e.target) && !mobileMenuButton.contains(e.target)) {
            mobileMenu.classList.add('hidden');
        }
    });
</script> 
<?php
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script_path = str_replace('/admin/components', '', dirname($_SERVER['SCRIPT_NAME']));
    $script_path = preg_replace('/\\/src\\/views$/', '', $script_path);
    $script_path = preg_replace('/\\/admin$/', '', $script_path);
    define('BASE_URL', rtrim($protocol . $host . $script_path, '/'));
}
?>
<!-- Sidebar - Updated with desktop toggle functionality -->
<aside id="admin-sidebar" class="fixed top-0 left-0 z-40 w-16 md:w-16 hover:w-64 h-screen p-4 overflow-y-auto transition-all duration-300 ease-in-out bg-white dark:bg-gray-800 shadow-lg border-r-4 border-suriname-green group" aria-label="Sidebar">
    <div class="h-full flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-5">
                 <span class="text-xl font-semibold text-white hidden md:group-hover:inline">Admin Menu</span>
                 <!-- Toggle buttons for all screens -->
                 <button id="sidebar-toggle" aria-controls="admin-sidebar" type="button" class="inline-flex items-center p-2 rounded-md text-gray-500 hover:text-white hover:bg-suriname-green focus:outline-none focus:ring-2 focus:ring-suriname-green transition-all duration-200">
                    <span class="sr-only">Toggle sidebar</span>
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                 </button>
            </div>
            <ul class="space-y-2 font-medium">
                <li>
                    <a href="<?= BASE_URL ?>/admin/index.php" class="flex items-center p-2 text-white rounded-lg hover:bg-suriname-green/20 transition-all duration-200">
                        <i class="fas fa-tachometer-alt w-5 h-5 text-white transition duration-200"></i>
                        <span class="ms-3 hidden md:group-hover:inline">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/src/views/elections.php" class="flex items-center p-2 text-white rounded-lg hover:bg-suriname-green/20 transition-all duration-200">
                        <i class="fas fa-vote-yea w-5 h-5 text-white transition duration-200"></i>
                        <span class="flex-1 ms-3 whitespace-nowrap hidden md:group-hover:inline">Verkiezingen</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/src/views/candidates.php" class="flex items-center p-2 text-white rounded-lg hover:bg-suriname-green/20 transition-all duration-200">
                        <i class="fas fa-users w-5 h-5 text-white transition duration-200"></i>
                        <span class="flex-1 ms-3 whitespace-nowrap hidden md:group-hover:inline">Kandidaten</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/src/views/parties.php" class="flex items-center p-2 text-white rounded-lg hover:bg-suriname-green/20 transition-all duration-200">
                        <i class="fas fa-flag w-5 h-5 text-white transition duration-200"></i>
                        <span class="flex-1 ms-3 whitespace-nowrap hidden md:group-hover:inline">Partijen</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/voters.php" class="flex items-center p-2 text-white rounded-lg hover:bg-suriname-green/20 transition-all duration-200">
                        <i class="fas fa-user-check w-5 h-5 text-white transition duration-200"></i>
                        <span class="flex-1 ms-3 whitespace-nowrap hidden md:group-hover:inline">Kiezers</span>
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>/admin/qrcodes.php" class="flex items-center p-2 text-white rounded-lg hover:bg-suriname-green/20 transition-all duration-200">
                        <i class="fas fa-qrcode w-5 h-5 text-white transition duration-200"></i>
                        <span class="flex-1 ms-3 whitespace-nowrap hidden md:group-hover:inline">QR Codes</span>
                    </a>
                </li>
            </ul>
        </div>
        <ul class="pt-4 mt-4 space-y-2 font-medium border-t border-gray-200 dark:border-gray-700">
             <li>
                <a href="<?= BASE_URL ?>/pages/logout.php" class="flex items-center p-2 text-white rounded-lg hover:bg-suriname-green/20 transition-all duration-200">
                    <i class="fas fa-sign-out-alt w-5 h-5 text-white transition duration-200"></i>
                    <span class="flex-1 ms-3 whitespace-nowrap hidden md:group-hover:inline">Afmelden</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
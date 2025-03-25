<?php
// Get the current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="bg-white w-64 shadow-md transition-all duration-300 ease-in-out">
    <div class="p-4 border-b border-gray-200">
        <h1 class="text-xl font-bold text-suriname-green transform hover:scale-105 transition-transform duration-200">
            <i class="fas fa-shield-alt mr-2"></i>Admin Dashboard
        </h1>
    </div>
    <nav class="mt-4">
        <a href="index.php" 
           class="block px-4 py-2 transition-all duration-200 ease-in-out transform hover:translate-x-2 <?= $current_page === 'index.php' ? 'bg-suriname-green text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
            <i class="fas fa-home mr-2"></i>Dashboard
        </a>
        <a href="candidates.php" 
           class="block px-4 py-2 transition-all duration-200 ease-in-out transform hover:translate-x-2 <?= $current_page === 'candidates.php' ? 'bg-suriname-green text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
            <i class="fas fa-user-tie mr-2"></i>Kandidaten
        </a>
        <a href="elections.php" 
           class="block px-4 py-2 transition-all duration-200 ease-in-out transform hover:translate-x-2 <?= $current_page === 'elections.php' ? 'bg-suriname-green text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
            <i class="fas fa-vote-yea mr-2"></i>Verkiezingen
        </a>
        <a href="parties.php" 
           class="block px-4 py-2 transition-all duration-200 ease-in-out transform hover:translate-x-2 <?= $current_page === 'parties.php' ? 'bg-suriname-green text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
            <i class="fas fa-flag mr-2"></i>Partijen
        </a>
        <a href="qrcodes.php" 
           class="block px-4 py-2 transition-all duration-200 ease-in-out transform hover:translate-x-2 <?= $current_page === 'qrcodes.php' ? 'bg-suriname-green text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
            <i class="fas fa-qrcode mr-2"></i>QR Codes
        </a>
        <a href="voters.php" 
           class="block px-4 py-2 transition-all duration-200 ease-in-out transform hover:translate-x-2 <?= $current_page === 'voters.php' ? 'bg-suriname-green text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
            <i class="fas fa-users mr-2"></i>Stemmers
        </a>
        <a href="results.php" 
           class="block px-4 py-2 transition-all duration-200 ease-in-out transform hover:translate-x-2 <?= $current_page === 'results.php' ? 'bg-suriname-green text-white' : 'text-gray-700 hover:bg-gray-100' ?>">
            <i class="fas fa-chart-bar mr-2"></i>Resultaten
        </a>
        <a href="../pages/logout.php" 
           class="block px-4 py-2 transition-all duration-200 ease-in-out transform hover:translate-x-2 text-red-600 hover:bg-red-50">
            <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </a>
    </nav>
</div>

<!-- Topbar -->
<div class="flex justify-between items-center mb-6 bg-white p-4 rounded-lg shadow-sm">
    <h2 class="text-2xl font-bold text-gray-900 transform hover:scale-105 transition-transform duration-200">
        <?= ucfirst(str_replace('.php', '', $current_page)) ?>
    </h2>
    <div class="flex items-center space-x-4">
        <span class="text-gray-700">Welkom, Admin</span>
        <div class="relative group">
            <img src="https://via.placeholder.com/40" 
                 alt="Profile" 
                 class="rounded-full transform transition-transform duration-200 group-hover:scale-110 cursor-pointer">
            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 hidden group-hover:block transition-opacity duration-200">
                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-user-circle mr-2"></i>Profiel
                </a>
                <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-cog mr-2"></i>Instellingen
                </a>
                <hr class="my-2">
                <a href="../pages/logout.php" class="block px-4 py-2 text-red-600 hover:bg-red-50 transition-colors duration-200">
                    <i class="fas fa-sign-out-alt mr-2"></i>Uitloggen
                </a>
            </div>
        </div>
    </div>
</div> 
<?php
// Get the current page name for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Navigation items configuration
$nav_items = [
    'index.php' => ['icon' => 'home', 'label' => 'Dashboard'],
    'candidates.php' => ['icon' => 'user-tie', 'label' => 'Kandidaten'],
    'elections.php' => ['icon' => 'vote-yea', 'label' => 'Verkiezingen'],
    'parties.php' => ['icon' => 'flag', 'label' => 'Partijen'],
    'qrcodes.php' => ['icon' => 'qrcode', 'label' => 'QR Codes'],
    'voters.php' => ['icon' => 'users', 'label' => 'Stemmers'],
    'results.php' => ['icon' => 'chart-bar', 'label' => 'Resultaten']
];
?>

<!-- Sidebar -->
<div class="fixed left-0 top-0 h-full w-64 bg-white shadow-lg z-50">
    <div class="p-4 border-b border-gray-200">
        <h1 class="text-xl font-bold text-suriname-green">
            <i class="fas fa-shield-alt mr-2"></i>Admin Dashboard
        </h1>
    </div>
    
    <nav class="mt-4 overflow-y-auto h-[calc(100vh-8rem)]">
        <?php foreach ($nav_items as $page => $item): ?>
            <a href="<?= $page ?>" 
               class="block px-4 py-2.5 transition-all duration-200 ease-in-out transform hover:translate-x-2
                      <?= $current_page === $page ? 'bg-suriname-green text-white shadow-lg' : 'text-gray-700 hover:bg-gray-100' ?>">
                <i class="fas fa-<?= $item['icon'] ?> mr-2"></i><?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
        
        <div class="mt-auto absolute bottom-0 left-0 right-0 border-t border-gray-200 p-4">
            <a href="../pages/logout.php" 
               class="block px-4 py-2.5 text-red-600 hover:bg-red-50 transition-all duration-200 ease-in-out transform hover:translate-x-2 rounded">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </nav>
</div>
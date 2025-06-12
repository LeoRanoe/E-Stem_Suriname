<?php
session_start();
require 'include/db_connect.php';

// Fetch latest news
try {
    $news_stmt = $pdo->query("SELECT * FROM news WHERE Status = 'published' ORDER BY DatePosted DESC LIMIT 3");
    $latest_news = $news_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $latest_news = [];
}

// Fetch active elections
try {
    $election_stmt = $pdo->query("SELECT * FROM elections WHERE Status = 'active' ORDER BY ElectionDate DESC");
    $active_elections = $election_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $active_elections = [];
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Stem Suriname - Online Stemmen Simulatie</title>
    
    <!-- Include centralized styles -->
    <?php include_once 'include/styles.php'; ?>
    
    <!-- Additional CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/suriname-style.css">
</head>
<body class="min-h-screen bg-gray-50 font-sans">
    <!-- Subtle pattern background -->
    <div class="fixed inset-0 z-0 pointer-events-none sr-bg-diagonal opacity-30"></div>

    <?php include 'include/nav.php'; ?>

    <!-- Hero Section -->
    <section class="relative sr-hero-bg text-white py-20">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute inset-0 bg-black opacity-10"></div>
            <svg class="absolute bottom-0 w-full h-20 text-gray-50 opacity-10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="currentColor" d="M0,160L48,176C96,192,192,224,288,224C384,224,480,192,576,160C672,128,768,96,864,106.7C960,117,1056,171,1152,197.3C1248,224,1344,224,1392,224L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg>
        </div>
        <div class="container mx-auto px-4 relative z-10">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6 animate-fade-in">
                    Welkom bij de Online Stemmen Simulatie voor de Surinaamse Staatsverkiezingen 2025!
                </h1>
                <p class="text-xl mb-8 text-gray-100 animate-fade-in opacity-90">
                    Neem deel aan de digitale democratie van Suriname. Uw stem telt mee voor de toekomst van ons land.
                </p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="voter/index.php"
                       class="sr-button sr-button-secondary group animate-fade-in">
                        <i class="fas fa-sign-in-alt transform group-hover:scale-110 transition-transform duration-300 mr-2"></i>
                        <span>Inloggen om te stemmen</span>
                    </a>
                <?php else: ?>
                    <a href="pages/voting/index.php"
                       class="sr-button sr-button-secondary group animate-fade-in">
                        <i class="fas fa-vote-yea transform group-hover:scale-110 transition-transform duration-300 mr-2"></i>
                        <span>Ga naar stemmen</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-16 bg-white relative">
        <div class="absolute inset-0 sr-pattern-dots opacity-20 pointer-events-none"></div>
        <div class="container mx-auto px-4 relative z-10">
            <h2 class="text-3xl font-bold text-center mb-4 text-suriname-green">Hoe werkt het?</h2>
            <p class="text-center text-gray-600 max-w-3xl mx-auto mb-12">Volg deze eenvoudige stappen om deel te nemen aan het online stemproces</p>
            
            <div class="grid grid-cols-1 md:grid-cols-5 gap-8 max-w-5xl mx-auto">
                <div class="text-center transform transition-all duration-300 hover:-translate-y-2">
                    <div class="w-16 h-16 bg-suriname-green text-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-suriname">
                        <i class="fas fa-user-plus text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">1. Registreren</h3>
                    <p class="text-gray-600">Maak een account aan met uw persoonlijke gegevens</p>
                </div>
                <div class="text-center transform transition-all duration-300 hover:-translate-y-2">
                    <div class="w-16 h-16 bg-suriname-green text-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-suriname">
                        <i class="fas fa-sign-in-alt text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">2. Inloggen</h3>
                    <p class="text-gray-600">Log in met uw account of scan uw QR-code</p>
                </div>
                <div class="text-center transform transition-all duration-300 hover:-translate-y-2">
                    <div class="w-16 h-16 bg-suriname-green text-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-suriname">
                        <i class="fas fa-list-alt text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">3. Kies kandidaten</h3>
                    <p class="text-gray-600">Selecteer uw voorkeuren uit de kandidatenlijst</p>
                </div>
                <div class="text-center transform transition-all duration-300 hover:-translate-y-2">
                    <div class="w-16 h-16 bg-suriname-green text-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-suriname">
                        <i class="fas fa-vote-yea text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">4. Stemmen</h3>
                    <p class="text-gray-600">Bevestig en breng uw stem uit</p>
                </div>
                <div class="text-center transform transition-all duration-300 hover:-translate-y-2">
                    <div class="w-16 h-16 bg-suriname-green text-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-suriname">
                        <i class="fas fa-chart-bar text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">5. Resultaten</h3>
                    <p class="text-gray-600">Bekijk de verkiezingsresultaten</p>
                </div>
            </div>
            
            <div class="text-center mt-12">
                <a href="voter/index.php" class="sr-button sr-button-primary">
                    <i class="fas fa-arrow-right mr-2"></i>
                    Begin nu met stemmen
                </a>
            </div>
        </div>
    </section>

    <!-- Active Elections Section -->
    <?php if (!empty($active_elections)): ?>
    <section class="py-16 bg-gray-50 relative">
        <div class="absolute inset-0 sr-bg-wavy opacity-40 pointer-events-none"></div>
        <div class="container mx-auto px-4 relative z-10">
            <h2 class="text-3xl font-bold text-center mb-4 text-suriname-green">Actieve Verkiezingen</h2>
            <p class="text-center text-gray-600 max-w-3xl mx-auto mb-12">De volgende verkiezingen zijn momenteel actief en beschikbaar om in te stemmen</p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <?php foreach ($active_elections as $election): ?>
                <div class="sr-card-premium hover:shadow-suriname-lg">
                    <div class="flex items-center mb-4">
                        <div class="bg-suriname-green bg-opacity-10 p-3 rounded-full mr-4">
                            <i class="fas fa-vote-yea text-suriname-green"></i>
                        </div>
                        <h3 class="text-xl font-semibold"><?= htmlspecialchars($election['ElectionName']) ?></h3>
                    </div>
                    <p class="text-gray-600 mb-4"><?= htmlspecialchars($election['Description'] ?? '') ?></p>
                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <i class="fas fa-calendar-alt mr-2 text-suriname-green"></i>
                        <span><?= date('d F Y', strtotime($election['ElectionDate'])) ?></span>
                    </div>
                    <a href="voter/index.php"
                       class="sr-button sr-button-primary w-full text-center">
                        <i class="fas fa-arrow-circle-right mr-2 group-hover:translate-x-1 transition-transform"></i>
                        Stem nu
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Latest News Section -->
    <?php if (!empty($latest_news)): ?>
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-4 text-suriname-green">Laatste Nieuws</h2>
            <p class="text-center text-gray-600 max-w-3xl mx-auto mb-12">Blijf op de hoogte van de meest recente ontwikkelingen</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <?php foreach ($latest_news as $news): ?>
                <div class="sr-card-basic">
                    <?php if ($news['FeaturedImage']): ?>
                    <img src="<?= htmlspecialchars($news['FeaturedImage']) ?>" alt="<?= htmlspecialchars($news['Title']) ?>" class="w-full h-48 object-cover rounded-t-lg">
                    <?php else: ?>
                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center rounded-t-lg">
                        <i class="fas fa-newspaper text-4xl text-gray-400"></i>
                    </div>
                    <?php endif; ?>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($news['Title']) ?></h3>
                        <p class="text-gray-600 mb-4"><?= substr(htmlspecialchars($news['Content']), 0, 150) ?>...</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-clock mr-2 text-suriname-green"></i>
                                <span><?= date('d F Y', strtotime($news['DatePosted'])) ?></span>
                            </div>
                            <a href="#" class="text-suriname-green hover:text-suriname-green-dark transition-colors">
                                Lees meer <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php include 'include/footer.php'; ?>
</body>
</html>

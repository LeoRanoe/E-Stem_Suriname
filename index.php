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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'suriname': {
                            'green': '#007749',
                            'dark-green': '#006241',
                            'red': '#C8102E',
                            'dark-red': '#a50d26',
                        },
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.5s ease-out',
                        'slide-in': 'slideIn 0.5s ease-out',
                    },
                    keyframes: {
                        fadeInUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateX(-20px)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' },
                        },
                    },
                },
            },
        }
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-50 via-green-50 to-emerald-100">
    <?php include 'include/nav.php'; ?>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-r from-suriname-green to-suriname-dark-green text-white py-20">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6 animate-fade-in-up">
                    Welkom bij de Online Stemmen Simulatie voor de Surinaamse Staatsverkiezingen 2025!
                </h1>
                <p class="text-xl mb-8 text-emerald-50 animate-fade-in-up">
                    Neem deel aan de digitale democratie van Suriname. Uw stem telt mee voor de toekomst van ons land.
                </p>
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="src/views/register_step1.php"
                                           class="inline-flex items-center space-x-2 bg-white text-suriname-green px-8 py-3 rounded-lg hover:bg-emerald-50 transition-all duration-300 transform hover:-translate-y-1 shadow-lg hover:shadow-xl group animate-fade-in-up">
                                            <i class="fas fa-user-plus transform group-hover:scale-110 transition-transform duration-300"></i>
                                            <span>Registreer nu om te stemmen</span>
                                        </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Hoe werkt het?</h2>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-8 max-w-5xl mx-auto">
                <div class="text-center">
                    <div class="w-16 h-16 bg-suriname-green text-white rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user-plus text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">1. Registreren</h3>
                    <p class="text-gray-600">Maak een account aan met uw gegevens</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-suriname-green text-white rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-sign-in-alt text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">2. Inloggen</h3>
                    <p class="text-gray-600">Log in met uw account</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-suriname-green text-white rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-qrcode text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">3. QR-code scannen</h3>
                    <p class="text-gray-600">Scan uw unieke QR-code</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-suriname-green text-white rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-vote-yea text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">4. Stemmen</h3>
                    <p class="text-gray-600">Geef uw stem uit</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-suriname-green text-white rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-bar text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">5. Resultaten</h3>
                    <p class="text-gray-600">Bekijk de verkiezingsresultaten</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Active Elections Section -->
    <?php if (!empty($active_elections)): ?>
    <section class="py-16 bg-emerald-50">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-12">Actieve Verkiezingen</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <?php foreach ($active_elections as $election): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($election['ElectionName']) ?></h3>
                        <p class="text-gray-600 mb-4"><?= htmlspecialchars($election['Description']) ?></p>
                        <div class="flex items-center text-sm text-gray-500 mb-4">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <span><?= date('d F Y', strtotime($election['ElectionDate'])) ?></span>
                        </div>
                        <a href="src/controllers/vote.php?election=<?= $election['ElectionID'] ?>"
                                                   class="block w-full text-center bg-suriname-green text-white py-2 rounded-lg hover:bg-suriname-dark-green transition-colors duration-200">
                                                    Stem nu
                                                </a>
                    </div>
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
            <h2 class="text-3xl font-bold text-center mb-12">Laatste Nieuws</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <?php foreach ($latest_news as $news): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                    <?php if ($news['FeaturedImage']): ?>
                    <img src="<?= htmlspecialchars($news['FeaturedImage']) ?>" alt="<?= htmlspecialchars($news['Title']) ?>" class="w-full h-48 object-cover">
                    <?php endif; ?>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars($news['Title']) ?></h3>
                        <p class="text-gray-600 mb-4"><?= substr(htmlspecialchars($news['Content']), 0, 150) ?>...</p>
                        <div class="flex items-center text-sm text-gray-500">
                            <i class="fas fa-clock mr-2"></i>
                            <span><?= date('d F Y', strtotime($news['DatePosted'])) ?></span>
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

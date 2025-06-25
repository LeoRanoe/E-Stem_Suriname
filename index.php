<?php
session_start();
require 'include/db_connect.php';

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
    <!-- <div class="fixed inset-0 z-0 pointer-events-none sr-bg-diagonal opacity-30"></div> -->

    <?php include 'include/nav.php'; ?>

    <!-- Hero Section -->
    <section class="relative text-white py-32" style="background-image: url('assets/Images/brug.jpg'); background-size: cover; background-position: center;">
        <div class="absolute inset-0 bg-black opacity-40"></div>
        <div class="absolute inset-0 overflow-hidden">
            <!-- <svg class="absolute bottom-0 w-full h-20 text-gray-50 opacity-10" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
                <path fill="currentColor" d="M0,160L48,176C96,192,192,224,288,224C384,224,480,192,576,160C672,128,768,96,864,106.7C960,117,1056,171,1152,197.3C1248,224,1344,224,1392,224L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
            </svg> -->
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
                       class="sr-button group animate-fade-in px-6 py-3 rounded-lg shadow-lg bg-white text-suriname-green hover:bg-suriname-green hover:text-white transition duration-300 text-lg">
                        <i class="fas fa-sign-in-alt transform group-hover:scale-110 transition-transform duration-300 mr-2"></i>
                        <span style="text-shadow: 1px 1px 3px rgba(0,0,0,0.7);">Scan QR code om te stemmen</span>
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
    <section class="relative py-20 bg-gray-50">
        <div class="relative container mx-auto px-4 text-center text-black z-10 max-w-5xl">
            <h2 class="text-3xl md:text-4xl font-extrabold mb-[-12px] text-black text-center relative inline-block">
                Hoe moet je stemmen ?
                <span class="absolute left-1/2 transform -translate-x-1/2 bottom-[-10px] w-[28rem] h-1 bg-green-700 rounded shadow-lg"></span>
            </h2>
            <p class="mt-12 mb-8 text-lg font-light text-black max-w-3xl mx-auto">
                Doe mee aan de online stemsimulatie en ontdek hoe eenvoudig het is om je stem uit te brengen.<br>
                Volg onze simpele stappen om ervoor te zorgen dat je stem gehoord wordt bij de komende verkiezingen.
            </p>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 max-w-5xl mx-auto">
                <div class="text-center transform transition-all duration-300 hover:-translate-y-2 bg-suriname-green rounded-lg shadow-lg p-6 flex flex-col items-center">
                    <div class="w-16 h-16 bg-white text-suriname-green rounded-full flex items-center justify-center mb-4 shadow-suriname">
                        <i class="fas fa-qrcode text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2 text-white">QR code scannen</h3>
                    <p class="text-white max-w-xs">Scan de QR code die u hebt ontvangen om te stemmen</p>
                </div>
                <div class="text-center transform transition-all duration-300 hover:-translate-y-2 bg-suriname-green rounded-lg shadow-lg p-6 flex flex-col items-center">
                    <div class="w-16 h-16 bg-white text-suriname-green rounded-full flex items-center justify-center mb-4 shadow-suriname">
                        <i class="fas fa-list-alt text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2 text-white">Kies kandidaten</h3>
                    <p class="text-white max-w-xs">Selecteer uw voorkeuren uit de kandidatenlijst</p>
                </div>
                <div class="text-center transform transition-all duration-300 hover:-translate-y-2 bg-suriname-green rounded-lg shadow-lg p-6 flex flex-col items-center">
                    <div class="w-16 h-16 bg-white text-suriname-green rounded-full flex items-center justify-center mb-4 shadow-suriname">
                        <i class="fas fa-vote-yea text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2 text-white">Stemmen</h3>
                    <p class="text-white max-w-xs">Bevestig en breng uw stem uit</p>
                </div>
                <div class="text-center transform transition-all duration-300 hover:-translate-y-2 bg-suriname-green rounded-lg shadow-lg p-6 flex flex-col items-center">
                    <div class="w-16 h-16 bg-white text-suriname-green rounded-full flex items-center justify-center mb-4 shadow-suriname">
                        <i class="fas fa-chart-bar text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2 text-white">Resultaten</h3>
                    <p class="text-white max-w-xs">Bekijk de verkiezingsresultaten</p>
                </div>
            </div>
            <a href="voter/index.php"
               class="sr-button group animate-fade-in px-6 py-3 rounded-xl shadow-lg bg-white text-suriname-green hover:bg-suriname-green hover:text-white transition duration-300 text-lg mt-12 inline-block flex items-center justify-center max-w-max mx-auto border-2 border-suriname-green">
                <span>Begin nu met stemmen</span>
                <i class="fas fa-arrow-right ml-3"></i>
            </a>
            <p class="mt-6 text-2xl font-extrabold text-red-700 max-w-3xl mx-auto opacity-100">
                <span class="font-black underline decoration-red-700">Let op:</span> u mag maar één keer stemmen.
            </p>
        </div>
    </section>

    <section class="relative py-32" style="background-image: url('assets/Images/surinaamse-vlag.jpg'); background-size: cover; background-position: center;">
        <div class="absolute inset-0 bg-black opacity-50 pointer-events-none"></div>
        <div class="container mx-auto px-4 relative z-10 text-center max-w-4xl mx-auto text-white drop-shadow-lg">
            <h2 class="text-3xl font-bold mb-6 text-white drop-shadow-xl">Uw Stem Maakt Verschil!</h2>
            <p class="mb-6 text-lg leading-relaxed drop-shadow-xl">
                Elke stem telt en bepaalt de toekomst van Suriname. Door te stemmen draagt u bij aan een sterker en rechtvaardiger land.
                Het is uw kans om invloed uit te oefenen op belangrijke beslissingen die ons allemaal raken.
            </p>
            <p class="mb-8 text-lg leading-relaxed drop-shadow-xl">
                Stemmen is niet alleen een recht, maar ook een plicht. Laat uw stem horen en help mee om de democratie te versterken.
                Samen kunnen we bouwen aan een betere toekomst voor ons en de volgende generaties.
            </p>
            
        </div>
    </section>

    <?php include 'include/footer.php'; ?>
</body>

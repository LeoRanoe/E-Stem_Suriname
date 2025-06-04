<!DOCTYPE html>
<html lang="en">
<head>
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
<body>
    <footer class="bg-white border-t-2 border-suriname-green mt-auto relative overflow-hidden">
        <!-- Suriname-themed pattern overlay -->
        <div class="absolute inset-0 sr-bg-diagonal opacity-5 pointer-events-none"></div>
        
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About Section -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <img src="<?= BASE_URL ?>/assets/Images/logo.png" alt="E-Stem Suriname" class="h-10 w-auto">
                        <span class="text-suriname-green font-bold text-xl">E-Stem Suriname</span>
                    </div>
                    <p class="text-gray-600 mb-6">
                        Een veilig en betrouwbaar platform voor online stemmen in Suriname. 
                        Maak deel uit van de digitale democratie en stem gemakkelijk vanuit het comfort van uw eigen omgeving.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-suriname-green transition-colors duration-300 transform hover:scale-110">
                            <i class="fab fa-facebook-f text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-suriname-green transition-colors duration-300 transform hover:scale-110">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-suriname-green transition-colors duration-300 transform hover:scale-110">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-suriname-green transition-colors duration-300 transform hover:scale-110">
                            <i class="fab fa-linkedin-in text-xl"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-suriname-green font-semibold text-lg mb-4">Snelle Links</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="<?= BASE_URL ?>/index.php" class="text-gray-600 hover:text-suriname-green transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right text-xs mr-2 transition-transform duration-300 group-hover:translate-x-1"></i>
                                <span>Home</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-600 hover:text-suriname-green transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right text-xs mr-2 transition-transform duration-300 group-hover:translate-x-1"></i>
                                <span>Over Ons</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-600 hover:text-suriname-green transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right text-xs mr-2 transition-transform duration-300 group-hover:translate-x-1"></i>
                                <span>Contact</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-600 hover:text-suriname-green transition-colors duration-300 flex items-center group">
                                <i class="fas fa-chevron-right text-xs mr-2 transition-transform duration-300 group-hover:translate-x-1"></i>
                                <span>FAQ</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-suriname-green font-semibold text-lg mb-4">Contact</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3 group">
                            <i class="fas fa-map-marker-alt text-suriname-green mt-1 group-hover:animate-bounce-subtle"></i>
                            <span class="text-gray-600">Paramaribo, Suriname</span>
                        </li>
                        <li class="flex items-start space-x-3 group">
                            <i class="fas fa-phone text-suriname-green mt-1 group-hover:animate-bounce-subtle"></i>
                            <span class="text-gray-600">+597 123-4567</span>
                        </li>
                        <li class="flex items-start space-x-3 group">
                            <i class="fas fa-envelope text-suriname-green mt-1 group-hover:animate-bounce-subtle"></i>
                            <span class="text-gray-600">info@estemsuriname.com</span>
                        </li>
                        <li class="flex items-start space-x-3 mt-4">
                            <a href="#" class="sr-button sr-button-primary inline-flex items-center">
                                <i class="fas fa-headset mr-2"></i>
                                <span>Hulp Nodig?</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="mt-12 pt-8 border-t border-gray-100">
                <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                    <p class="text-gray-600 text-sm">
                        © <?= date('Y') ?> E-Stem Suriname. Alle rechten voorbehouden.
                    </p>
                    <div class="flex space-x-6">
                        <a href="#" class="text-gray-600 hover:text-suriname-green text-sm transition-colors duration-300">
                            Privacybeleid
                        </a>
                        <a href="#" class="text-gray-600 hover:text-suriname-green text-sm transition-colors duration-300">
                            Gebruiksvoorwaarden
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
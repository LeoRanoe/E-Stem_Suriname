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
                },
            },
        }
    </script>
</head>
<body>
    <footer class="bg-white border-t border-gray-200">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- About Section -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <i class="fas fa-crow text-suriname-green text-2xl"></i>
                        <span class="text-suriname-green font-bold text-xl">E-Stem Suriname</span>
                    </div>
                    <p class="text-gray-600 mb-6">
                        Een veilig en betrouwbaar platform voor online stemmen in Suriname. 
                        Maak deel uit van de digitale democratie en stem gemakkelijk vanuit het comfort van uw eigen omgeving.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-suriname-green transition-colors duration-300">
                            <i class="fab fa-facebook-f text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-suriname-green transition-colors duration-300">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-suriname-green transition-colors duration-300">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-suriname-green transition-colors duration-300">
                            <i class="fab fa-linkedin-in text-xl"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="text-suriname-green font-semibold text-lg mb-4">Snelle Links</h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="../index.php" class="text-gray-600 hover:text-suriname-green transition-colors duration-300 flex items-center space-x-2">
                                <i class="fas fa-chevron-right text-xs"></i>
                                <span>Home</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-600 hover:text-suriname-green transition-colors duration-300 flex items-center space-x-2">
                                <i class="fas fa-chevron-right text-xs"></i>
                                <span>Over Ons</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-600 hover:text-suriname-green transition-colors duration-300 flex items-center space-x-2">
                                <i class="fas fa-chevron-right text-xs"></i>
                                <span>Contact</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-600 hover:text-suriname-green transition-colors duration-300 flex items-center space-x-2">
                                <i class="fas fa-chevron-right text-xs"></i>
                                <span>FAQ</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h3 class="text-suriname-green font-semibold text-lg mb-4">Contact</h3>
                    <ul class="space-y-3">
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-map-marker-alt text-suriname-green mt-1"></i>
                            <span class="text-gray-600">Paramaribo, Suriname</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-phone text-suriname-green mt-1"></i>
                            <span class="text-gray-600">+597 123-4567</span>
                        </li>
                        <li class="flex items-start space-x-3">
                            <i class="fas fa-envelope text-suriname-green mt-1"></i>
                            <span class="text-gray-600">info@estemsuriname.com</span>
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
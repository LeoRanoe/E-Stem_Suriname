<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verkiezingen Keuzes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .button-font, .header-font {
            font-family: 'Montserrat', sans-serif;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body class="bg-white">
    <div class="max-w-md mx-auto p-6 relative border-2 border-gray-300 rounded-lg shadow-md my-8">
        <!-- Green blobs background effect -->
        <div class="absolute inset-0 z-0 overflow-hidden">
            <div class="absolute top-0 right-0 w-96 h-72 bg-green-300 rounded-full opacity-20 blur-xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-72 bg-green-300 rounded-full opacity-20 blur-xl"></div>
        </div>
        
        <!-- Content container -->
        <div class="relative z-10">
            <!-- Title -->
            <h1 class="text-2xl font-bold mb-6 pb-1 border-b border-gray-800 header-font">
                Bent u <span class="text-red-600">zeker</span> van uw keuzes
            </h1>
            
            <!-- First candidate card -->
            <div class="bg-gray-200 mb-6 rounded flex">
                <div class="w-1/3 p-4 flex items-center justify-center">
                    <div class="bg-amber-500 w-20 h-20 rounded-full overflow-hidden flex items-end justify-center">
                        <div class="w-16 h-12 bg-gray-200 rounded-t-full mt-2"></div>
                    </div>
                </div>
                <div class="w-2/3 p-4">
                    <p class="font-semibold">Naam</p>
                    <p>Politieke partij</p>
                    <p>District</p>
                </div>
            </div>
            
            <!-- Second candidate card -->
            <div class="bg-gray-200 mb-6 rounded flex">
                <div class="w-1/3 p-4 flex items-center justify-center">
                    <div class="bg-amber-100 w-20 h-20 rounded-full overflow-hidden flex items-end justify-center">
                        <div class="w-16 h-12 bg-white rounded-t-full mt-6"></div>
                    </div>
                </div>
                <div class="w-2/3 p-4">
                    <p class="font-semibold">Naam</p>
                    <p>Politieke partij</p>
                    <p>District</p>
                    <p>Ressort</p>
                </div>
            </div>
            
            <!-- Submit button -->
            <div class="flex justify-center mt-8">
                <button class="bg-green-500 text-black px-8 py-2 rounded-full font-medium hover:bg-green-600 hover:shadow-lg transition-all duration-200 transform hover:scale-105 button-font font-semibold tracking-wide">
                    Indienen
                </button>
            </div>
        </div>
    </div>
</body>
</html>
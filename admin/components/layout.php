<?php
require_once __DIR__ . '/../../include/db_connect.php'; // Corrected path
require_once __DIR__ . '/../../include/admin_auth.php'; // Corrected path

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= SITE_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin-styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <!-- Add QR Scanner library -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        /* Custom Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }

        /* Smooth Scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #007749;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #006241;
        }

        /* Table Hover Effect */
        .hover-row:hover td {
            background-color: #f8f8f8;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        /* Button Hover Effect */
        .btn-hover {
            transition: all 0.3s ease;
        }
        
        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* QR Scanner Styles */
        #qr-reader {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        #qr-reader__scan_region {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        /* Print-specific styles */
        @media print {
            /* Hide everything by default */
            body > * {
                display: none;
            }

            /* Show only the main content area */
            #main-content {
                display: block !important;
                margin-left: 0 !important;
            }

            /* Ensure the content inside main-content is visible */
            #main-content > * {
                display: block;
                visibility: visible;
            }

            /* Hide elements specifically marked with print:hidden */
            .print\:hidden {
                display: none !important;
            }
        }

        /* Debug Console Styles */
        #debug-console {
            position: fixed;
            bottom: 0;
            right: 0;
            width: 300px;
            max-height: 200px;
            background: rgba(0, 0, 0, 0.8);
            color: #00ff00;
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        #debug-console.visible {
            display: block;
        }

        #debug-console .debug-entry {
            margin-bottom: 5px;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }

        #debug-console .debug-time {
            color: #888;
            font-size: 10px;
        }

        #debug-console .debug-error {
            color: #ff4444;
        }

        #debug-console .debug-success {
            color: #44ff44;
        }
    </style>
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

        // Debug Console
        const DebugConsole = {
            console: document.createElement('div'),
            init() {
                this.console.id = 'debug-console';
                document.body.appendChild(this.console);
                
                // Add debug toggle button
                const toggleBtn = document.createElement('button');
                toggleBtn.innerHTML = '<i class="fas fa-bug"></i>';
                toggleBtn.className = 'fixed bottom-4 right-4 bg-gray-800 text-white rounded-full p-3 z-1000';
                toggleBtn.onclick = () => this.toggle();
                document.body.appendChild(toggleBtn);
            },
            log(message, type = 'info') {
                const entry = document.createElement('div');
                entry.className = `debug-entry debug-${type}`;
                const time = new Date().toLocaleTimeString();
                entry.innerHTML = `<span class="debug-time">[${time}]</span> ${message}`;
                this.console.appendChild(entry);
                this.console.scrollTop = this.console.scrollHeight;
                
                // Also log to browser console
                console.log(`[${type.toUpperCase()}] ${message}`);
            },
            toggle() {
                this.console.classList.toggle('visible');
            }
        };

        // Initialize debug console
        document.addEventListener('DOMContentLoaded', () => {
            DebugConsole.init();
            DebugConsole.log('Debug console initialized', 'success');
        });

        // QR Code Scanner
        const QRScanner = {
            scanner: null,
            init(elementId) {
                this.scanner = new Html5Qrcode(elementId);
                DebugConsole.log('QR Scanner initialized', 'success');
            },
            start(onSuccess, onError) {
                const config = { fps: 10, qrbox: { width: 250, height: 250 } };
                this.scanner.start(
                    { facingMode: "environment" },
                    config,
                    (decodedText) => {
                        DebugConsole.log(`QR Code detected: ${decodedText}`, 'success');
                        onSuccess(decodedText);
                    },
                    (error) => {
                        DebugConsole.log(`QR Scan error: ${error}`, 'error');
                        onError(error);
                    }
                );
            },
            stop() {
                if (this.scanner) {
                    this.scanner.stop();
                    DebugConsole.log('QR Scanner stopped', 'info');
                }
            }
        };

        // Error handling
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            DebugConsole.log(`Error: ${msg} at ${url}:${lineNo}:${columnNo}`, 'error');
            return false;
        };

        // AJAX error handling
        document.addEventListener('ajaxError', function(e) {
            DebugConsole.log(`AJAX Error: ${e.detail.message}`, 'error');
        });
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <div class="print:hidden">
            <?php include 'nav.php'; ?>
        </div>

        <!-- Main Content - Updated for responsive sidebar -->
        <div id="main-content" class="flex-1 transition-all duration-300 ease-in-out ml-16 p-6 overflow-y-auto" style="transition-property: margin; transition-duration: 300ms;">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 animate-fade-in print:hidden" role="alert">
                    <span class="block sm:inline"><?= $_SESSION['success_message'] ?></span>
                    <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 animate-fade-in print:hidden" role="alert">
                    <span class="block sm:inline"><?= $_SESSION['error_message'] ?></span>
                    <button class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?= $content ?? '' ?>

            <!-- Scroll to top button -->
            <button id="scrollToTop" 
                    class="fixed bottom-4 right-4 bg-suriname-green text-white rounded-full p-3 hidden transition-all duration-300 hover:bg-suriname-dark-green hover:scale-110"
                    onclick="window.scrollTo({top: 0, behavior: 'smooth'})">
                <i class="fas fa-arrow-up"></i>
            </button>
        </div>
    </div>

    <script>
        // Scroll to top button visibility
        window.onscroll = function() {
            const scrollButton = document.getElementById('scrollToTop');
            if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
                scrollButton.classList.remove('hidden');
            } else {
                scrollButton.classList.add('hidden');
            }
        };

        // Add hover effect to all tables
        document.querySelectorAll('table tr').forEach(row => {
            row.classList.add('hover-row');
        });

        // Add hover effect to all buttons
        document.querySelectorAll('button').forEach(button => {
            button.classList.add('btn-hover');
        });
    </script>
    <?php if (isset($pageScript)): ?>
    <script>
        <?= $pageScript ?>
    </script>
    <?php endif; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <!-- <script src="<?php echo BASE_URL; ?>/assets/js/sidebar.js"></script> -->
    <!-- <script src="<?php echo BASE_URL; ?>/assets/js/admin-dashboard.js"></script> -->
</body>
</html> 
<?php
// Prevent session settings modification after session start
if (session_status() !== PHP_SESSION_NONE) {
    session_write_close();
    session_unset();
}

// Base configuration
define('BASE_PATH', '/E-Stem_Suriname/E-Stem_Suriname');
define('BASE_URL', 'http://localhost/E-Stem_Suriname/E-Stem_Suriname');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'e_stem_suriname');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('SITE_NAME', 'E-Stem Suriname');
define('SITE_DESCRIPTION', 'Online Voting System for Suriname');

// Surinamese colors
define('COLORS', [
    'green' => '#007749',
    'dark-green' => '#006241',
    'red' => '#C8102E',
    'dark-red' => '#a50d26',
    'yellow' => '#FFD700',
    'white' => '#FFFFFF',
    'black' => '#000000'
]);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Time zone
date_default_timezone_set('America/Paramaribo');

// Configure session settings
// Note: Custom handler is registered via session_set_save_handler() below
// Remove ini_set for session.save_handler as it's handled by the handler registration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 1440);

class DBSessionHandler implements SessionHandlerInterface {
    private $pdo;
    
    public function open($savePath, $sessionName): bool {
        try {
            $this->pdo = new PDO(
                'mysql:host='.DB_HOST.';dbname='.DB_NAME,
                DB_USER,
                DB_PASS
            );
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS sessions (
                    id VARCHAR(128) PRIMARY KEY,
                    data TEXT,
                    access INT(11)
                )
            ");
            return true;
        } catch (PDOException $e) {
            error_log("Session open failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function close(): bool {
        $this->pdo = null;
        return true;
    }
    
    public function read($id): string|false {
        try {
            $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetchColumn() ?: '';
        } catch (PDOException $e) {
            error_log("Session read failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function write($id, $data): bool {
        try {
            $stmt = $this->pdo->prepare(
                "REPLACE INTO sessions (id, data, access) VALUES (?, ?, ?)"
            );
            return $stmt->execute([$id, $data, time()]);
        } catch (PDOException $e) {
            error_log("Session write failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function destroy($id): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Session destroy failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function gc($maxlifetime): int|false {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM sessions WHERE access < ?"
            );
            return $stmt->execute([time() - $maxlifetime]) ? $stmt->rowCount() : 0;
        } catch (PDOException $e) {
            error_log("Session gc failed: " . $e->getMessage());
            return false;
        }
    }
}

// Register the session handler
session_set_save_handler(new DBSessionHandler(), true);

// Start session immediately
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify admin auth functions are available
if (!@require_once __DIR__ . '/admin_auth.php') {
    die('Failed to load admin authentication functions');
}

// Ensure development mode is set
if (!defined('DEVELOPMENT_MODE')) {
    define('DEVELOPMENT_MODE', true);
}

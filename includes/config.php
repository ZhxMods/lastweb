<?php
/**
 * ============================================
 * InfinityFree Core Configuration
 * PHP 8.2 Optimized | Security First
 * ============================================
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

// ============================================
// Environment Configuration
// ============================================
define('ENVIRONMENT', 'development'); // 'development' or 'production'

// ============================================
// Database Configuration
// ============================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'infinityfree_db');
define('DB_USER', 'infinityfree_user');
define('DB_PASS', 'your_secure_password_here');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// Application Constants
// ============================================
define('APP_NAME', 'InfinityFree');
define('APP_URL', 'https://yourdomain.com');
define('APP_VERSION', '1.0.0');

// ============================================
// Path Constants
// ============================================
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('STUDENT_PATH', ROOT_PATH . '/student');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('LANG_PATH', ROOT_PATH . '/lang');

// ============================================
// Language Configuration
// ============================================
define('DEFAULT_LANG', 'fr');
define('SUPPORTED_LANGS', ['fr', 'en', 'ar']);

// ============================================
// Security Configuration
// ============================================
// Encryption key - CHANGE THIS TO A RANDOM 32-byte string
define('ENCRYPTION_KEY', 'your-32-byte-encryption-key-here!!');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

// Session configuration
define('SESSION_NAME', 'INFINITYFREE_SESSION');
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 days
define('COOKIE_LIFETIME', 3600 * 24 * 30); // 30 days

// CSRF token lifetime (in seconds)
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour

// Password hashing
define('PASSWORD_HASH_ALGO', PASSWORD_ARGON2ID);
define('PASSWORD_HASH_OPTIONS', [
    'memory_cost' => 65536,
    'time_cost' => 4,
    'threads' => 3
]);

// ============================================
// Security Headers Configuration
// ============================================
define('SECURITY_HEADERS', [
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';"
]);

// ============================================
// Secure Cookie Configuration
// ============================================
function initSecureSession(): void {
    // Set secure cookie parameters
    session_set_cookie_params([
        'lifetime' => COOKIE_LIFETIME,
        'path' => '/',
        'domain' => parse_url(APP_URL, PHP_URL_HOST) ?? '',
        'secure' => true,          // HTTPS only
        'httponly' => true,        // No JavaScript access
        'samesite' => 'Lax'        // CSRF protection
    ]);
    
    // Set custom session name
    session_name(SESSION_NAME);
    
    // Start session with strict mode
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // Every 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// ============================================
// Database Connection (PDO)
// ============================================
function getDatabaseConnection(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            if (ENVIRONMENT === 'development') {
                die('Database Connection Error: ' . $e->getMessage());
            } else {
                error_log('Database Connection Error: ' . $e->getMessage());
                die('A database error occurred. Please try again later.');
            }
        }
    }
    
    return $pdo;
}

// ============================================
// Error Handling Configuration
// ============================================
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
}

// ============================================
// Timezone Configuration
// ============================================
date_default_timezone_set('Africa/Casablanca');

// ============================================
// Set Security Headers
// ============================================
function setSecurityHeaders(): void {
    foreach (SECURITY_HEADERS as $header => $value) {
        header("$header: $value");
    }
}

// ============================================
// Auto-initialize on include
// ============================================
setSecurityHeaders();
initSecureSession();

// ============================================
// Helper Functions
// ============================================

/**
 * Get current language from session or cookie
 */
function getCurrentLanguage(): string {
    return $_SESSION['lang'] ?? $_COOKIE['lang'] ?? DEFAULT_LANG;
}

/**
 * Sanitize output for HTML display
 */
function escape(mixed $data): string {
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize string input
 */
function sanitizeString(string $input): string {
    return trim(strip_tags($input));
}

/**
 * Validate email format
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a secure random token
 */
function generateSecureToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_token']);
}

/**
 * Get current user ID
 */
function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Redirect helper
 */
function redirect(string $url, int $statusCode = 302): void {
    header("Location: $url", true, $statusCode);
    exit;
}

/**
 * JSON response helper
 */
function jsonResponse(mixed $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

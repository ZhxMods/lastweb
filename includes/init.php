<?php
/**
 * ============================================
 * InfinityFree Application Initializer
 * Main entry point for all pages - FIXED VERSION
 * ============================================
 */

// Define application initialization constant
define('APP_INIT', true);

// Load configuration
require_once __DIR__ . '/config.php';

// Load security functions
require_once __DIR__ . '/security.php';

// Load translation and helper functions
require_once __DIR__ . '/functions.php';

// ============================================
// Session Restoration from Encrypted Cookie
// ============================================

// Auto-restore session from encrypted "remember_me" cookie if not logged in
if (!isLoggedIn() && isset($_COOKIE['remember_user'])) {
    $restored = restoreSessionFromCookie();
    
    if ($restored && ENVIRONMENT === 'development') {
        error_log('Session restored from encrypted cookie for user: ' . getCurrentUserId());
    }
}

// ============================================
// Language Detection and Setup
// ============================================

// Detect and set current language
$currentLang = getCurrentLanguage();

// Set language in session if not already set
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = $currentLang;
}

// ============================================
// User Session Validation
// ============================================

// Update last activity timestamp
if (isLoggedIn()) {
    $_SESSION['last_activity'] = time();
    
    // Verify session is still valid
    if (!verifySessionToken($_SESSION['user_id'], $_SESSION['user_token'])) {
        destroyUserSession();
        redirect('/login.php');
    }
}

// ============================================
// Helper Functions
// ============================================

/**
 * Load current user data if logged in
 */
function loadCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, last_name, role, preferred_lang, avatar
        FROM users
        WHERE id = :user_id AND is_active = 1
    ");
    
    $stmt->execute(['user_id' => getCurrentUserId()]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_lang'] = $user['preferred_lang'];
        return $user;
    }
    
    return null;
}

/**
 * Format time duration (seconds to human readable)
 */
function formatDuration(int $seconds): string {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%dh %02dm', $hours, $minutes);
    } elseif ($minutes > 0) {
        return sprintf('%dm %02ds', $minutes, $secs);
    } else {
        return sprintf('%ds', $secs);
    }
}

/**
 * Get asset URL with version for cache busting
 */
function asset(string $path): string {
    return APP_URL . '/assets/' . ltrim($path, '/') . '?v=' . APP_VERSION;
}

/**
 * Generate URL - FIXED for InfinityFree
 * Adds .php extension for compatibility when mod_rewrite might fail
 */
function url(string $path): string {
    $path = ltrim($path, '/');
    
    // If path is empty, return home
    if (empty($path)) {
        return APP_URL . '/index.php';
    }
    
    // Check if path already has an extension
    if (preg_match('/\.(php|html|htm)$/i', $path)) {
        return APP_URL . '/' . $path;
    }
    
    // Main pages that need .php extension for InfinityFree
    $mainPages = [
        'login' => 'login.php',
        'register' => 'register.php',
        'logout' => 'logout.php',
        'dashboard' => 'dashboard.php',
        'profile' => 'profile.php',
        'levels' => 'levels.php',
        'subjects' => 'subjects.php',
        'about' => 'about.php',
        'contact' => 'contact.php',
        'privacy' => 'privacy.php',
        'terms' => 'terms.php',
        'help' => 'help.php',
        'documentation' => 'documentation.php',
        'settings' => 'settings.php',
        'auth' => 'auth.php',
        'set_language' => 'set_language.php'
    ];
    
    // Extract the first part of the path
    $pathParts = explode('/', $path);
    $firstPart = $pathParts[0];
    
    // Remove query string if present
    $cleanFirstPart = explode('?', $firstPart)[0];
    
    // If it's a main page, add .php extension
    if (isset($mainPages[$cleanFirstPart])) {
        // Preserve query string
        if (str_contains($path, '?')) {
            $parts = explode('?', $path, 2);
            return APP_URL . '/' . $mainPages[$cleanFirstPart] . '?' . $parts[1];
        }
        return APP_URL . '/' . $mainPages[$cleanFirstPart];
    }
    
    // For URLs with IDs (lesson/123, subject/456), .htaccess will handle
    // But add .php to the handler file
    if (preg_match('/^(lesson|subject|level)\/\d+/', $path)) {
        return APP_URL . '/' . $path;
    }
    
    // For admin/student/ajax paths, let .htaccess handle
    if (preg_match('/^(admin|student|ajax)\//', $path)) {
        return APP_URL . '/' . $path;
    }
    
    // For all other cases, return as is and let .htaccess try to handle
    return APP_URL . '/' . $path;
}

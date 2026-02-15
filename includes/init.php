<?php
/**
 * ============================================
 * InfinityFree Application Initializer
 * Main entry point for all pages
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
        redirect('/login');
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
 * Generate URL with language prefix if needed
 */
function url(string $path): string {
    return APP_URL . '/' . ltrim($path, '/');
}

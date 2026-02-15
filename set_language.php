<?php
/**
 * ============================================
 * Language Switcher
 * Handles language switching with encrypted cookies
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

// Get the target language from URL
$targetLang = $_GET['lang'] ?? DEFAULT_LANG;

// Validate language
if (!in_array($targetLang, SUPPORTED_LANGS)) {
    $targetLang = DEFAULT_LANG;
}

// Set language in encrypted cookie
setLanguageCookie($targetLang);

// Set language in session
$_SESSION['lang'] = $targetLang;

// Update user preference if logged in
if (isLoggedIn()) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        UPDATE users 
        SET preferred_lang = :lang,
            updated_at = NOW()
        WHERE id = :user_id
    ");
    
    $stmt->execute([
        'lang' => $targetLang,
        'user_id' => getCurrentUserId()
    ]);
}

// Get the referrer (previous page) or redirect to home
$referer = $_SERVER['HTTP_REFERER'] ?? '/';

// Parse the referer to ensure it's from our domain
$refererHost = parse_url($referer, PHP_URL_HOST);
$currentHost = parse_url(APP_URL, PHP_URL_HOST);

// Only redirect to referer if it's from our domain
if ($refererHost === $currentHost) {
    // Remove any existing lang parameter from referer
    $redirectUrl = preg_replace('/[?&]lang=[a-z]{2}/', '', $referer);
    
    // Add the new language parameter if not default
    if ($targetLang !== DEFAULT_LANG) {
        $separator = (strpos($redirectUrl, '?') !== false) ? '&' : '?';
        $redirectUrl .= $separator . 'lang=' . $targetLang;
    }
    
    redirect($redirectUrl);
} else {
    // Redirect to home if referer is not from our domain
    redirect('/');
}

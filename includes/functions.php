<?php
/**
 * ============================================
 * InfinityFree Global Functions
 * Translation Engine & Helper Functions
 * ============================================
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

// ============================================
// Translation System
// ============================================

/**
 * Get current language with priority:
 * 1. URL parameter (?lang=fr)
 * 2. Encrypted cookie
 * 3. Session
 * 4. User preference (if logged in)
 * 5. Default language
 */
function getCurrentLanguage(): string {
    // Check URL parameter first (for language switcher)
    if (isset($_GET['lang']) && in_array($_GET['lang'], SUPPORTED_LANGS)) {
        $lang = $_GET['lang'];
        setLanguageCookie($lang);
        $_SESSION['lang'] = $lang;
        return $lang;
    }
    
    // Check session
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], SUPPORTED_LANGS)) {
        return $_SESSION['lang'];
    }
    
    // Check encrypted cookie
    $cookieLang = getEncryptedCookie('user_lang');
    if ($cookieLang && in_array($cookieLang, SUPPORTED_LANGS)) {
        $_SESSION['lang'] = $cookieLang;
        return $cookieLang;
    }
    
    // Check user preference if logged in
    if (isLoggedIn()) {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT preferred_lang FROM users WHERE id = ?");
        $stmt->execute([getCurrentUserId()]);
        $user = $stmt->fetch();
        
        if ($user && in_array($user['preferred_lang'], SUPPORTED_LANGS)) {
            $lang = $user['preferred_lang'];
            $_SESSION['lang'] = $lang;
            return $lang;
        }
    }
    
    // Return default language
    return DEFAULT_LANG;
}

/**
 * Set language in encrypted cookie
 */
function setLanguageCookie(string $lang): void {
    if (in_array($lang, SUPPORTED_LANGS)) {
        setEncryptedCookie(
            'user_lang',
            $lang,
            time() + (365 * 24 * 60 * 60), // 1 year
            '/',
            '',
            true,
            true
        );
    }
}

/**
 * Main translation function
 * Usage: __('welcome') or __('hello', 'en')
 */
function __(string $key, ?string $lang = null): string {
    static $translations = [];
    
    // Get language (use provided lang or detect current)
    $lang = $lang ?? getCurrentLanguage();
    
    // Load translations for this language if not already loaded
    if (!isset($translations[$lang])) {
        $langFile = LANG_PATH . "/{$lang}.php";
        
        if (file_exists($langFile)) {
            $translations[$lang] = require $langFile;
        } else {
            // Fallback to default language
            $defaultFile = LANG_PATH . '/' . DEFAULT_LANG . '.php';
            if (file_exists($defaultFile)) {
                $translations[$lang] = require $defaultFile;
            } else {
                $translations[$lang] = [];
            }
        }
    }
    
    // Return translation or the key itself if not found
    return $translations[$lang][$key] ?? $key;
}

/**
 * Translation with placeholders
 * Usage: trans_replace('welcome_user', ['name' => 'John'])
 * In lang file: 'welcome_user' => 'Welcome, {name}!'
 */
function trans_replace(string $key, array $replacements = [], ?string $lang = null): string {
    $text = __($key, $lang);
    
    foreach ($replacements as $placeholder => $value) {
        $text = str_replace('{' . $placeholder . '}', $value, $text);
    }
    
    return $text;
}

/**
 * Get all available languages
 */
function getAvailableLanguages(): array {
    return [
        'fr' => __('lang_fr'),
        'en' => __('lang_en'),
        'ar' => __('lang_ar'),
    ];
}

/**
 * Check if current language is RTL (Right-to-Left)
 */
function isRTL(?string $lang = null): bool {
    $lang = $lang ?? getCurrentLanguage();
    return $lang === 'ar';
}

/**
 * Get HTML dir attribute based on language
 */
function getTextDirection(?string $lang = null): string {
    return isRTL($lang) ? 'rtl' : 'ltr';
}

// ============================================
// Content Translation Helpers
// ============================================

/**
 * Get translated field from database row
 * Usage: getTranslatedField($lesson, 'title')
 * Will return title_fr, title_en, or title_ar based on current language
 */
function getTranslatedField(array $row, string $fieldName, ?string $lang = null): ?string {
    $lang = $lang ?? getCurrentLanguage();
    $fieldKey = $fieldName . '_' . $lang;
    
    return $row[$fieldKey] ?? $row[$fieldName . '_' . DEFAULT_LANG] ?? null;
}

/**
 * Build SELECT statement for translated fields
 * Usage: SELECT id, <?= translatedFields('title, description') ?> FROM lessons
 */
function translatedFields(string $fields): string {
    $lang = getCurrentLanguage();
    $fieldArray = array_map('trim', explode(',', $fields));
    $result = [];
    
    foreach ($fieldArray as $field) {
        $result[] = "{$field}_{$lang}";
    }
    
    return implode(', ', $result);
}

// ============================================
// URL & Link Helpers
// ============================================

/**
 * Generate URL with current language
 */
function langUrl(string $path, ?string $lang = null): string {
    $lang = $lang ?? getCurrentLanguage();
    $path = ltrim($path, '/');
    
    // Don't add default language to URL
    if ($lang === DEFAULT_LANG) {
        return APP_URL . '/' . $path;
    }
    
    return APP_URL . '/' . $path . (strpos($path, '?') !== false ? '&' : '?') . 'lang=' . $lang;
}

/**
 * Get language switcher URL for current page
 */
function getLangSwitchUrl(string $targetLang): string {
    $currentUrl = $_SERVER['REQUEST_URI'];
    
    // Remove existing lang parameter
    $currentUrl = preg_replace('/[?&]lang=[a-z]{2}/', '', $currentUrl);
    
    // Add new lang parameter
    $separator = (strpos($currentUrl, '?') !== false) ? '&' : '?';
    
    // If target lang is default, just return URL without lang parameter
    if ($targetLang === DEFAULT_LANG) {
        return $currentUrl;
    }
    
    return $currentUrl . $separator . 'lang=' . $targetLang;
}

// ============================================
// Date & Time Formatting
// ============================================

/**
 * Format date according to language
 */
function formatLocalizedDate(string $date, ?string $lang = null): string {
    $lang = $lang ?? getCurrentLanguage();
    $timestamp = strtotime($date);
    
    if (!$timestamp) {
        return $date;
    }
    
    return match($lang) {
        'fr' => date('d/m/Y H:i', $timestamp),
        'ar' => date('Y/m/d H:i', $timestamp),
        default => date('m/d/Y h:i A', $timestamp)
    };
}

/**
 * Get relative time (e.g., "2 hours ago")
 */
function timeAgo(string $datetime, ?string $lang = null): string {
    $lang = $lang ?? getCurrentLanguage();
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return __('just_now', $lang);
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' ' . __('minutes', $lang);
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' ' . __('hours', $lang);
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' ' . __('days', $lang);
    } else {
        return formatLocalizedDate($datetime, $lang);
    }
}

// ============================================
// Number Formatting
// ============================================

/**
 * Format number according to locale
 */
function formatNumber(float $number, int $decimals = 0, ?string $lang = null): string {
    $lang = $lang ?? getCurrentLanguage();
    
    return match($lang) {
        'fr' => number_format($number, $decimals, ',', ' '),
        'ar' => number_format($number, $decimals, '.', ','),
        default => number_format($number, $decimals, '.', ',')
    };
}

// ============================================
// Form Helpers
// ============================================

/**
 * Generate language-aware placeholder attribute
 */
function placeholder(string $key): string {
    return 'placeholder="' . escape(__($key)) . '"';
}

/**
 * Generate translated label
 */
function label(string $key, string $for = ''): string {
    $forAttr = $for ? ' for="' . escape($for) . '"' : '';
    return '<label' . $forAttr . '>' . escape(__($key)) . '</label>';
}

// ============================================
// Validation Messages
// ============================================

/**
 * Get validation error message
 */
function validationMessage(string $type, array $params = []): string {
    $message = __('validation_' . $type);
    
    foreach ($params as $key => $value) {
        $message = str_replace('{' . $key . '}', $value, $message);
    }
    
    return $message;
}

// ============================================
// Meta Tags for SEO
// ============================================

/**
 * Generate language-specific meta tags
 */
function getLanguageMetaTags(): string {
    $currentLang = getCurrentLanguage();
    $availableLangs = SUPPORTED_LANGS;
    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    
    $tags = '<link rel="alternate" hreflang="x-default" href="' . APP_URL . '">' . "\n";
    
    foreach ($availableLangs as $lang) {
        $langUrl = getLangSwitchUrl($lang);
        $tags .= '<link rel="alternate" hreflang="' . $lang . '" href="' . $langUrl . '">' . "\n";
    }
    
    return $tags;
}

// ============================================
// Content Sanitization (Language-aware)
// ============================================

/**
 * Sanitize and escape translated content
 */
function escapeTranslation(string $key, ?string $lang = null): string {
    return escape(__($key, $lang));
}

/**
 * Allow safe HTML in translations (for rich content)
 */
function safeTranslation(string $key, ?string $lang = null): string {
    $text = __($key, $lang);
    return cleanHTML($text, ['p', 'br', 'strong', 'em', 'u', 'a', 'ul', 'ol', 'li']);
}

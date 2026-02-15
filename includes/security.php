<?php
/**
 * ============================================
 * InfinityFree Security Core
 * PHP 8.2 Optimized | Advanced Security
 * ============================================
 */

// Prevent direct access
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

// ============================================
// Encrypted Cookie Management
// ============================================

/**
 * Encrypt data using AES-256-CBC
 */
function encryptData(string $data): string {
    $iv = random_bytes(openssl_cipher_iv_length(ENCRYPTION_METHOD));
    $encrypted = openssl_encrypt(
        $data,
        ENCRYPTION_METHOD,
        ENCRYPTION_KEY,
        0,
        $iv
    );
    
    // Combine IV and encrypted data, then base64 encode
    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt data using AES-256-CBC
 */
function decryptData(string $encryptedData): string|false {
    $data = base64_decode($encryptedData);
    if ($data === false) {
        return false;
    }
    
    $ivLength = openssl_cipher_iv_length(ENCRYPTION_METHOD);
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    
    return openssl_decrypt(
        $encrypted,
        ENCRYPTION_METHOD,
        ENCRYPTION_KEY,
        0,
        $iv
    );
}

/**
 * Set an encrypted cookie
 */
function setEncryptedCookie(
    string $name,
    string $value,
    int $expire = 0,
    string $path = '/',
    string $domain = '',
    bool $secure = true,
    bool $httpOnly = true
): bool {
    $encryptedValue = encryptData($value);
    
    return setcookie(
        $name,
        $encryptedValue,
        [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => 'Lax'
        ]
    );
}

/**
 * Get and decrypt a cookie value
 */
function getEncryptedCookie(string $name): string|false {
    if (!isset($_COOKIE[$name])) {
        return false;
    }
    
    return decryptData($_COOKIE[$name]);
}

/**
 * Delete an encrypted cookie
 */
function deleteEncryptedCookie(string $name, string $path = '/', string $domain = ''): bool {
    unset($_COOKIE[$name]);
    return setcookie($name, '', [
        'expires' => time() - 3600,
        'path' => $path,
        'domain' => $domain
    ]);
}

// ============================================
// Session Token Management
// ============================================

/**
 * Generate a secure session token for a user
 */
function generateSessionToken(int $userId): string {
    $token = bin2hex(random_bytes(32));
    $hashedToken = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    // Store hashed token in database
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        UPDATE users 
        SET session_token = :token,
            session_expires = :expires,
            last_activity = NOW()
        WHERE id = :user_id
    ");
    
    $stmt->execute([
        'token' => $hashedToken,
        'expires' => $expiresAt,
        'user_id' => $userId
    ]);
    
    return $token;
}

/**
 * Verify session token
 */
function verifySessionToken(int $userId, string $token): bool {
    $hashedToken = hash('sha256', $token);
    
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT id, session_expires 
        FROM users 
        WHERE id = :user_id 
        AND session_token = :token
        AND session_expires > NOW()
        AND is_active = 1
    ");
    
    $stmt->execute([
        'user_id' => $userId,
        'token' => $hashedToken
    ]);
    
    return $stmt->fetch() !== false;
}

/**
 * Store user session with encrypted cookie
 */
function createUserSession(int $userId, bool $rememberMe = false): bool {
    $token = generateSessionToken($userId);
    
    // Set session variables
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_token'] = $token;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Set encrypted cookie if "remember me" is enabled
    if ($rememberMe) {
        $cookieData = json_encode([
            'user_id' => $userId,
            'token' => $token,
            'created' => time()
        ]);
        
        setEncryptedCookie(
            'remember_user',
            $cookieData,
            time() + COOKIE_LIFETIME
        );
    }
    
    return true;
}

/**
 * Verify and restore session from encrypted cookie
 */
function restoreSessionFromCookie(): bool {
    $cookieData = getEncryptedCookie('remember_user');
    
    if ($cookieData === false) {
        return false;
    }
    
    $data = json_decode($cookieData, true);
    if (!$data || !isset($data['user_id'], $data['token'])) {
        return false;
    }
    
    // Verify token is still valid
    if (verifySessionToken($data['user_id'], $data['token'])) {
        $_SESSION['user_id'] = $data['user_id'];
        $_SESSION['user_token'] = $data['token'];
        $_SESSION['login_time'] = $data['created'];
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    // Token invalid, delete cookie
    deleteEncryptedCookie('remember_user');
    return false;
}

/**
 * Destroy user session completely
 */
function destroyUserSession(): void {
    // Clear session token in database
    if (isset($_SESSION['user_id'])) {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("
            UPDATE users 
            SET session_token = NULL,
                session_expires = NULL
            WHERE id = :user_id
        ");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
    }
    
    // Clear session
    $_SESSION = [];
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy remember me cookie
    deleteEncryptedCookie('remember_user');
    
    session_destroy();
}

// ============================================
// CSRF Protection
// ============================================

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_tokens'][$token] = time();
    
    // Clean old tokens (older than CSRF_TOKEN_LIFETIME)
    cleanExpiredCSRFTokens();
    
    return $token;
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken(string $token): bool {
    if (!isset($_SESSION['csrf_tokens'][$token])) {
        return false;
    }
    
    $timestamp = $_SESSION['csrf_tokens'][$token];
    
    // Check if token has expired
    if (time() - $timestamp > CSRF_TOKEN_LIFETIME) {
        unset($_SESSION['csrf_tokens'][$token]);
        return false;
    }
    
    // Token is valid, remove it (one-time use)
    unset($_SESSION['csrf_tokens'][$token]);
    return true;
}

/**
 * Clean expired CSRF tokens
 */
function cleanExpiredCSRFTokens(): void {
    if (!isset($_SESSION['csrf_tokens'])) {
        return;
    }
    
    $currentTime = time();
    foreach ($_SESSION['csrf_tokens'] as $token => $timestamp) {
        if ($currentTime - $timestamp > CSRF_TOKEN_LIFETIME) {
            unset($_SESSION['csrf_tokens'][$token]);
        }
    }
}

/**
 * Get CSRF token HTML input field
 */
function csrfField(): string {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . escape($token) . '">';
}

/**
 * Validate CSRF token from POST request
 */
function validateCSRFToken(): bool {
    if (!isset($_POST['csrf_token'])) {
        return false;
    }
    
    return verifyCSRFToken($_POST['csrf_token']);
}

// ============================================
// Password Security
// ============================================

/**
 * Hash password with Argon2id
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
}

/**
 * Verify password
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 */
function needsRehash(string $hash): bool {
    return password_needs_rehash($hash, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
}

// ============================================
// Input Validation & Sanitization
// ============================================

/**
 * Validate and sanitize user input
 */
function validateInput(string $input, string $type = 'string', array $options = []): mixed {
    $input = trim($input);
    
    return match($type) {
        'email' => filter_var($input, FILTER_VALIDATE_EMAIL),
        'int' => filter_var($input, FILTER_VALIDATE_INT, $options),
        'float' => filter_var($input, FILTER_VALIDATE_FLOAT, $options),
        'url' => filter_var($input, FILTER_VALIDATE_URL),
        'bool' => filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        'string' => filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
        'alpha' => preg_match('/^[a-zA-Z]+$/', $input) ? $input : false,
        'alphanum' => preg_match('/^[a-zA-Z0-9]+$/', $input) ? $input : false,
        default => htmlspecialchars($input, ENT_QUOTES, 'UTF-8')
    };
}

/**
 * Sanitize array of inputs
 */
function sanitizeArray(array $data, array $rules = []): array {
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        $type = $rules[$key] ?? 'string';
        $sanitized[$key] = is_array($value) 
            ? sanitizeArray($value, $rules)
            : validateInput((string)$value, $type);
    }
    
    return $sanitized;
}

// ============================================
// Rate Limiting
// ============================================

/**
 * Check rate limit for an action
 */
function checkRateLimit(string $action, string $identifier, int $maxAttempts = 5, int $timeWindow = 300): bool {
    $key = "rate_limit_{$action}_{$identifier}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    $data = $_SESSION[$key];
    
    // Reset if time window has passed
    if (time() - $data['first_attempt'] > $timeWindow) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }
    
    // Check if limit exceeded
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    // Increment attempts
    $_SESSION[$key]['attempts']++;
    return true;
}

/**
 * Get remaining rate limit attempts
 */
function getRateLimitRemaining(string $action, string $identifier, int $maxAttempts = 5): int {
    $key = "rate_limit_{$action}_{$identifier}";
    
    if (!isset($_SESSION[$key])) {
        return $maxAttempts;
    }
    
    return max(0, $maxAttempts - $_SESSION[$key]['attempts']);
}

// ============================================
// XSS Protection
// ============================================

/**
 * Clean HTML content (allow only safe tags)
 */
function cleanHTML(string $html, array $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'a']): string {
    return strip_tags($html, $allowedTags);
}

/**
 * Prevent SQL injection by escaping for LIKE queries
 */
function escapeLikeString(string $string): string {
    return addcslashes($string, '%_');
}

// ============================================
// User Authentication Helpers
// ============================================

/**
 * Check if user has specific role
 */
function hasRole(string $role): bool {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Require authentication
 */
function requireAuth(string $redirectUrl = '/login'): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect($redirectUrl);
    }
}

/**
 * Require specific role
 */
function requireRole(string $role, string $redirectUrl = '/'): void {
    requireAuth();
    
    if (!hasRole($role)) {
        redirect($redirectUrl);
    }
}

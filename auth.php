<?php
/**
 * ============================================
 * Authentication Controller
 * Handles Login & Registration
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

// Get database connection
$pdo = getDatabaseConnection();

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

// ============================================
// REGISTRATION HANDLER
// ============================================
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    
    // Validate CSRF token
    if (!validateCSRFToken()) {
        $_SESSION['error'] = __('invalid_csrf');
        redirect('/register');
    }
    
    // Rate limiting
    if (!checkRateLimit('register', $_SERVER['REMOTE_ADDR'], 5, 300)) {
        $_SESSION['error'] = __('too_many_attempts');
        redirect('/register');
    }
    
    // Get and sanitize inputs
    $fullName = sanitizeString($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $levelId = (int)($_POST['level_id'] ?? 0);
    
    // Validate all fields are filled
    if (empty($fullName) || empty($email) || empty($password) || empty($passwordConfirm) || $levelId === 0) {
        $_SESSION['error'] = __('all_fields_required');
        redirect('/register');
    }
    
    // Split full name into first and last name
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0];
    $lastName = $nameParts[1] ?? '';
    
    // Validate email format
    if (!isValidEmail($email)) {
        $_SESSION['error'] = __('invalid_email_format');
        redirect('/register');
    }
    
    // Validate password length
    if (strlen($password) < 8) {
        $_SESSION['error'] = __('password_too_short');
        redirect('/register');
    }
    
    // Validate passwords match
    if ($password !== $passwordConfirm) {
        $_SESSION['error'] = __('password_mismatch');
        redirect('/register');
    }
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $_SESSION['error'] = __('email_exists');
        redirect('/register');
    }
    
    // Verify level exists
    $stmt = $pdo->prepare("SELECT id FROM levels WHERE id = ? AND is_active = 1");
    $stmt->execute([$levelId]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = __('invalid_level');
        redirect('/register');
    }
    
    // Hash password with Argon2ID
    $hashedPassword = hashPassword($password);
    
    // Create user account
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (
                email, 
                password, 
                first_name, 
                last_name, 
                role, 
                preferred_lang,
                email_verified,
                is_active,
                created_at
            ) VALUES (?, ?, ?, ?, 'student', ?, 1, 1, NOW())
        ");
        
        $stmt->execute([
            $email,
            $hashedPassword,
            $firstName,
            $lastName,
            getCurrentLanguage()
        ]);
        
        // Get the new user ID
        $userId = $pdo->lastInsertId();
        
        // Success - set session message
        $_SESSION['success'] = __('registration_success');
        redirect('/login');
        
    } catch (PDOException $e) {
        error_log('Registration error: ' . $e->getMessage());
        $_SESSION['error'] = __('registration_failed');
        redirect('/register');
    }
}

// ============================================
// LOGIN HANDLER
// ============================================
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    
    // Validate CSRF token
    if (!validateCSRFToken()) {
        $_SESSION['error'] = __('invalid_csrf');
        redirect('/login');
    }
    
    // Rate limiting (5 attempts per 5 minutes)
    if (!checkRateLimit('login', $_SERVER['REMOTE_ADDR'], 5, 300)) {
        $_SESSION['error'] = __('too_many_attempts');
        redirect('/login');
    }
    
    // Get and sanitize inputs
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['remember_me']);
    
    // Validate fields
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = __('all_fields_required');
        redirect('/login');
    }
    
    // Validate email format
    if (!isValidEmail($email)) {
        $_SESSION['error'] = __('invalid_email_format');
        redirect('/login');
    }
    
    // Fetch user from database
    $stmt = $pdo->prepare("
        SELECT id, email, password, first_name, last_name, role, preferred_lang, is_active
        FROM users 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Check if user exists and password is correct
    if (!$user || !verifyPassword($password, $user['password'])) {
        $_SESSION['error'] = __('invalid_credentials');
        redirect('/login');
    }
    
    // Check if account is active
    if (!$user['is_active']) {
        $_SESSION['error'] = __('account_inactive');
        redirect('/login');
    }
    
    // Check if password needs rehashing (for security upgrades)
    if (needsRehash($user['password'])) {
        $newHash = hashPassword($password);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $user['id']]);
    }
    
    // Create user session
    createUserSession($user['id'], $rememberMe);
    
    // Set user info in session
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_lang'] = $user['preferred_lang'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
    
    // Update last login
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Set success message
    $_SESSION['success'] = __('login_success');
    
    // Redirect based on role
    if ($user['role'] === 'admin') {
        redirect('/admin/dashboard');
    } else {
        // Check if there's a redirect URL stored
        $redirectUrl = $_SESSION['redirect_after_login'] ?? '/dashboard';
        unset($_SESSION['redirect_after_login']);
        redirect($redirectUrl);
    }
}

// If we get here, invalid action
redirect('/');

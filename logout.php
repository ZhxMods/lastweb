<?php
/**
 * ============================================
 * Logout Handler
 * Securely destroys session and cookies
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

// Verify user is logged in
if (!isLoggedIn()) {
    redirect('/login');
}

// Destroy user session (includes clearing encrypted cookies)
destroyUserSession();

// Set logout success message
$_SESSION['success'] = __('logout_success');

// Redirect to home page
redirect('/');

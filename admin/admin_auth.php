<?php
/**
 * ============================================
 * Admin Authentication Middleware
 * Verifies admin role and active status
 * ============================================
 */

require_once __DIR__ . '/../includes/init.php';

// Require authentication first
requireAuth();

// Get database connection
$pdo = getDatabaseConnection();

// Get current user ID
$userId = getCurrentUserId();

// Verify user is admin and active
$stmt = $pdo->prepare("
    SELECT id, email, first_name, last_name, role, is_active
    FROM users
    WHERE id = ? AND is_active = 1
");
$stmt->execute([$userId]);
$adminUser = $stmt->fetch();

// Check if user exists and is active
if (!$adminUser) {
    destroyUserSession();
    $_SESSION['error'] = __('account_inactive');
    redirect('/login');
}

// Check if user has admin or super_admin role
if (!in_array($adminUser['role'], ['admin', 'super_admin'])) {
    $_SESSION['error'] = __('unauthorized_access');
    redirect('/dashboard');
}

// Store admin user data for use in admin pages
$_SESSION['admin_user'] = $adminUser;

/**
 * Verify CSRF token for admin actions
 */
function adminVerifyCsrf(): bool {
    return validateCSRFToken();
}

/**
 * Check if user has super admin role
 */
function isSuperAdmin(): bool {
    return isset($_SESSION['admin_user']) && $_SESSION['admin_user']['role'] === 'super_admin';
}

/**
 * Get admin user data
 */
function getAdminUser(): ?array {
    return $_SESSION['admin_user'] ?? null;
}

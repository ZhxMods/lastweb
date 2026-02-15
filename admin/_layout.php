<?php
/**
 * ============================================
 * Admin Panel Layout - Header
 * Dark Sidebar | Professional Design
 * ============================================
 */

// Ensure admin auth is included
if (!isset($_SESSION['admin_user'])) {
    require_once __DIR__ . '/admin_auth.php';
}

$adminUser = getAdminUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLanguage() ?>" dir="<?= getTextDirection() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? escape($pageTitle) . ' - ' : '' ?>Admin Panel</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>">
    
    <!-- Admin Styles -->
    <style>
    :root {
        /* Admin Color Scheme */
        --admin-bg: #0d1117;
        --admin-sidebar: #161b22;
        --admin-hover: #21262d;
        --admin-blue: #2563eb;
        --admin-blue-light: #3b82f6;
        --admin-text: #c9d1d9;
        --admin-text-dim: #8b949e;
        --admin-border: #30363d;
        --admin-success: #10b981;
        --admin-danger: #ef4444;
        --admin-warning: #f59e0b;
    }
    
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        background: var(--admin-bg);
        color: var(--admin-text);
        overflow-x: hidden;
    }
    
    /* Sidebar */
    .admin-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 260px;
        height: 100vh;
        background: var(--admin-sidebar);
        border-right: 1px solid var(--admin-border);
        overflow-y: auto;
        z-index: 1000;
        transition: transform 0.3s ease;
    }
    
    .sidebar-header {
        padding: 2rem 1.5rem;
        border-bottom: 1px solid var(--admin-border);
    }
    
    .sidebar-logo {
        font-size: 1.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--admin-blue) 0%, var(--admin-blue-light) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .sidebar-user {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.5rem;
        border-bottom: 1px solid var(--admin-border);
    }
    
    .user-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--admin-blue) 0%, var(--admin-blue-light) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1.125rem;
    }
    
    .user-info {
        flex: 1;
    }
    
    .user-name {
        font-weight: 600;
        color: var(--admin-text);
        margin-bottom: 0.25rem;
    }
    
    .user-role {
        font-size: 0.813rem;
        color: var(--admin-text-dim);
    }
    
    .sidebar-nav {
        padding: 1rem 0;
    }
    
    .nav-section {
        padding: 0 1rem;
        margin-bottom: 1.5rem;
    }
    
    .nav-section-title {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--admin-text-dim);
        margin-bottom: 0.75rem;
        padding: 0 0.5rem;
    }
    
    .nav-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 0.875rem;
        color: var(--admin-text);
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.2s ease;
        margin-bottom: 0.25rem;
    }
    
    .nav-item:hover {
        background: var(--admin-hover);
        color: var(--admin-blue-light);
    }
    
    .nav-item.active {
        background: var(--admin-blue);
        color: white;
        position: relative;
    }
    
    .nav-item.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 70%;
        background: white;
        border-radius: 0 3px 3px 0;
    }
    
    .nav-icon {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Main Content */
    .admin-content {
        margin-left: 260px;
        min-height: 100vh;
        padding: 2rem;
    }
    
    .content-header {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--admin-border);
    }
    
    .content-header h1 {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: var(--admin-text);
    }
    
    .content-header p {
        color: var(--admin-text-dim);
    }
    
    /* Cards */
    .admin-card {
        background: var(--admin-sidebar);
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .admin-card-header {
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--admin-border);
    }
    
    .admin-card-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--admin-text);
    }
    
    /* Buttons */
    .admin-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.938rem;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .admin-btn-primary {
        background: var(--admin-blue);
        color: white;
    }
    
    .admin-btn-primary:hover {
        background: var(--admin-blue-light);
        transform: translateY(-1px);
    }
    
    .admin-btn-success {
        background: var(--admin-success);
        color: white;
    }
    
    .admin-btn-danger {
        background: var(--admin-danger);
        color: white;
    }
    
    .admin-btn-secondary {
        background: var(--admin-hover);
        color: var(--admin-text);
        border: 1px solid var(--admin-border);
    }
    
    /* Form Elements */
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--admin-text);
        font-size: 0.938rem;
    }
    
    .form-control {
        width: 100%;
        padding: 0.75rem;
        background: var(--admin-bg);
        border: 1px solid var(--admin-border);
        border-radius: 6px;
        color: var(--admin-text);
        font-size: 0.938rem;
        transition: all 0.2s ease;
    }
    
    .form-control:focus {
        outline: none;
        border-color: var(--admin-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .admin-sidebar {
            transform: translateX(-100%);
        }
        
        .admin-sidebar.mobile-open {
            transform: translateX(0);
        }
        
        .admin-content {
            margin-left: 0;
        }
    }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <!-- Logo -->
        <div class="sidebar-header">
            <div class="sidebar-logo">InfinityFree</div>
            <div style="font-size: 0.813rem; color: var(--admin-text-dim); margin-top: 0.5rem;">
                <?= __('admin_panel') ?>
            </div>
        </div>
        
        <!-- User Info -->
        <div class="sidebar-user">
            <div class="user-avatar">
                <?= strtoupper(substr($adminUser['first_name'], 0, 1)) ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= escape($adminUser['first_name']) ?></div>
                <div class="user-role"><?= ucfirst($adminUser['role']) ?></div>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title"><?= __('main') ?></div>
                <a href="/admin/dashboard.php" class="nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <span class="nav-icon">📊</span>
                    <span><?= __('dashboard') ?></span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title"><?= __('content') ?></div>
                <a href="/admin/manage_lessons.php" class="nav-item <?= $currentPage === 'manage_lessons' ? 'active' : '' ?>">
                    <span class="nav-icon">📚</span>
                    <span><?= __('lessons') ?></span>
                </a>
                <a href="/admin/manage_subjects.php" class="nav-item <?= $currentPage === 'manage_subjects' ? 'active' : '' ?>">
                    <span class="nav-icon">📖</span>
                    <span><?= __('subjects') ?></span>
                </a>
                <a href="/admin/manage_levels.php" class="nav-item <?= $currentPage === 'manage_levels' ? 'active' : '' ?>">
                    <span class="nav-icon">🎓</span>
                    <span><?= __('levels') ?></span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title"><?= __('users') ?></div>
                <a href="/admin/manage_users.php" class="nav-item <?= $currentPage === 'manage_users' ? 'active' : '' ?>">
                    <span class="nav-icon">👥</span>
                    <span><?= __('manage_users') ?></span>
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title"><?= __('settings') ?></div>
                <a href="/admin/settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span>
                    <span><?= __('settings') ?></span>
                </a>
                <a href="/logout.php" class="nav-item">
                    <span class="nav-icon">🚪</span>
                    <span><?= __('logout') ?></span>
                </a>
            </div>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <main class="admin-content">

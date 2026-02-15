<?php
/**
 * ============================================
 * Global Header Component
 * Professional navigation with language switcher
 * ============================================
 */

// Ensure this file is included, not accessed directly
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

// Load current user if logged in
$currentUser = loadCurrentUser();
$isLoggedIn = $currentUser !== null;
$currentLang = getCurrentLanguage();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= getTextDirection() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= escape(__('hero_subtitle')) ?>">
    <meta name="keywords" content="education, learning, courses, multilingual, français, english, arabic">
    <meta name="author" content="InfinityFree">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:title" content="<?= isset($pageTitle) ? escape($pageTitle) . ' - ' : '' ?>InfinityFree">
    <meta property="og:description" content="<?= escape(__('hero_subtitle')) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= escape($_SERVER['REQUEST_URI']) ?>">
    
    <!-- Language Alternates -->
    <?= getLanguageMetaTags() ?>
    
    <title><?= isset($pageTitle) ? escape($pageTitle) . ' - ' : '' ?>InfinityFree</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    
    <?php if (isRTL()): ?>
    <!-- RTL Styles for Arabic -->
    <link rel="stylesheet" href="<?= asset('css/rtl.css') ?>">
    <?php endif; ?>
    
    <!-- Additional CSS can be added by pages -->
    <?php if (isset($additionalCSS)): ?>
        <?= $additionalCSS ?>
    <?php endif; ?>
</head>
<body class="<?= isRTL() ? 'rtl' : 'ltr' ?>">
    
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <!-- Logo -->
                <a href="<?= url('/') ?>" class="logo">
                    <h1 class="logo-text">InfinityFree</h1>
                </a>
                
                <!-- Mobile Menu Toggle -->
                <button class="menu-toggle" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <!-- Navigation Menu -->
                <ul class="nav-menu">
                    <li><a href="<?= url('/') ?>" class="<?= ($_SERVER['REQUEST_URI'] === '/' || $_SERVER['REQUEST_URI'] === '/index.php') ? 'active' : '' ?>"><?= __('home') ?></a></li>
                    <li><a href="<?= url('/levels') ?>"><?= __('levels') ?></a></li>
                    <li><a href="<?= url('/subjects') ?>"><?= __('subjects') ?></a></li>
                    
                    <?php if ($isLoggedIn): ?>
                        <li><a href="<?= url('/dashboard') ?>"><?= __('dashboard') ?></a></li>
                        
                        <?php if (hasRole('admin')): ?>
                            <li><a href="<?= url('/admin/dashboard') ?>"><?= __('admin_panel') ?></a></li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <!-- Right Side Actions -->
                <div class="nav-actions">
                    <!-- Language Switcher -->
                    <div class="language-switcher">
                        <button class="lang-toggle" aria-label="<?= __('change_language') ?>">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7 2a1 1 0 011 1v1h3a1 1 0 110 2H9.578a18.87 18.87 0 01-1.724 4.78c.29.354.596.696.914 1.026a1 1 0 11-1.44 1.389c-.188-.196-.373-.396-.554-.6a19.098 19.098 0 01-3.107 3.567 1 1 0 01-1.334-1.49 17.087 17.087 0 003.13-3.733 18.992 18.992 0 01-1.487-2.494 1 1 0 111.79-.89c.234.47.489.928.764 1.372.417-.934.752-1.913.997-2.927H3a1 1 0 110-2h3V3a1 1 0 011-1zm6 6a1 1 0 01.894.553l2.991 5.982a.869.869 0 01.02.037l.99 1.98a1 1 0 11-1.79.895L15.383 16h-4.764l-.723 1.447a1 1 0 11-1.788-.894l.99-1.98.019-.038 2.99-5.982A1 1 0 0113 8zm-1.382 6h2.764L13 11.236 11.618 14z"/>
                            </svg>
                            <span class="current-lang"><?= strtoupper($currentLang) ?></span>
                        </button>
                        
                        <div class="lang-dropdown">
                            <?php foreach (SUPPORTED_LANGS as $lang): ?>
                                <?php if ($lang !== $currentLang): ?>
                                    <a href="<?= url('/set_language?lang=' . $lang) ?>" class="lang-option">
                                        <span class="lang-code"><?= strtoupper($lang) ?></span>
                                        <span class="lang-name"><?= __('lang_' . $lang) ?></span>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- User Actions -->
                    <?php if ($isLoggedIn): ?>
                        <div class="user-menu">
                            <button class="user-toggle" aria-label="<?= __('my_account') ?>">
                                <?php if (!empty($currentUser['avatar'])): ?>
                                    <img src="<?= escape($currentUser['avatar']) ?>" alt="Avatar" class="user-avatar">
                                <?php else: ?>
                                    <div class="user-avatar-placeholder">
                                        <?= strtoupper(substr($currentUser['first_name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <span class="user-name"><?= escape($currentUser['first_name']) ?></span>
                            </button>
                            
                            <div class="user-dropdown">
                                <a href="<?= url('/profile') ?>" class="dropdown-item">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                    </svg>
                                    <?= __('profile') ?>
                                </a>
                                <a href="<?= url('/settings') ?>" class="dropdown-item">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                                    </svg>
                                    <?= __('settings') ?>
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="<?= url('/logout') ?>" class="dropdown-item text-danger">
                                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <?= __('logout') ?>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?= url('/login') ?>" class="btn btn-secondary"><?= __('login') ?></a>
                        <a href="<?= url('/register') ?>" class="btn btn-primary"><?= __('register') ?></a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">

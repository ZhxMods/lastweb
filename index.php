<?php
/**
 * ============================================
 * InfinityFree - Home Page
 * Using Global Layouts & Translation System
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

// Set page title
$pageTitle = __('home');

// Include header
include_once __DIR__ . '/includes/_header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h2><?= __('hero_title') ?></h2>
            <p><?= __('hero_subtitle') ?></p>
            
            <?php if (!isLoggedIn()): ?>
                <div class="hero-actions">
                    <a href="<?= url('/register') ?>" class="btn btn-primary btn-lg"><?= __('get_started') ?></a>
                    <a href="<?= url('/levels') ?>" class="btn btn-outline btn-lg"><?= __('explore_courses') ?></a>
                </div>
            <?php else: ?>
                <div class="hero-actions">
                    <a href="<?= url('/dashboard') ?>" class="btn btn-primary btn-lg"><?= __('dashboard') ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features">
    <div class="container">
        <h3><?= __('why_choose_us') ?></h3>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📚</div>
                <h4><?= __('feature_multilingual') ?></h4>
                <p><?= __('feature_multilingual_desc') ?></p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h4><?= __('feature_tracking') ?></h4>
                <p><?= __('feature_tracking_desc') ?></p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">✅</div>
                <h4><?= __('feature_quizzes') ?></h4>
                <p><?= __('feature_quizzes_desc') ?></p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h4><?= __('feature_security') ?></h4>
                <p><?= __('feature_security_desc') ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section (Only for logged in users) -->
<?php if (isLoggedIn()): ?>
    <?php
    $currentUser = loadCurrentUser();
    
    // Get user statistics
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT lesson_id) as lessons_started,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as lessons_completed,
            SUM(time_spent_seconds) as total_time
        FROM user_progress
        WHERE user_id = :user_id
    ");
    $stmt->execute(['user_id' => $currentUser['id']]);
    $stats = $stmt->fetch();
    ?>
    
    <section class="statistics">
        <div class="container">
            <h3 style="text-align: center; margin-bottom: 2rem;"><?= __('my_progress') ?></h3>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['lessons_started'] ?? 0 ?></div>
                    <div class="stat-label"><?= __('total_lessons') ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= $stats['lessons_completed'] ?? 0 ?></div>
                    <div class="stat-label"><?= __('lessons_completed') ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?= formatDuration($stats['total_time'] ?? 0) ?></div>
                    <div class="stat-label"><?= __('total_time_spent') ?></div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Language Demo Section (for demonstration purposes) -->
<section class="features" style="background-color: var(--bg-white);">
    <div class="container">
        <h3><?= __('change_language') ?></h3>
        <div style="text-align: center; padding: 2rem 0;">
            <p style="font-size: 1.125rem; color: var(--text-gray); margin-bottom: 1.5rem;">
                Current Language: <strong><?= strtoupper(getCurrentLanguage()) ?></strong> (<?= __('lang_' . getCurrentLanguage()) ?>)
            </p>
            <p style="font-size: 0.938rem; color: var(--text-gray);">
                Use the language switcher in the header to change the interface language.
                <br>All content will be displayed in your selected language.
            </p>
        </div>
    </div>
</section>

<?php
// Include footer
include_once __DIR__ . '/includes/_footer.php';
?>

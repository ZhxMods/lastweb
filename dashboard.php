<?php
/**
 * ============================================
 * Enhanced Student Dashboard
 * XP Gamification | Real-time Progress | Animations
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

// Require authentication
requireAuth();

// Require student or teacher role (not admin-only)
if (hasRole('admin')) {
    redirect('/admin/dashboard');
}

// Load current user
$currentUser = loadCurrentUser();

// Set page title
$pageTitle = __('dashboard');

// Get user statistics
$pdo = getDatabaseConnection();

// Get XP (handle if column doesn't exist)
try {
    $stmt = $pdo->prepare("SELECT COALESCE(xp_points, 0) as xp_points FROM users WHERE id = ?");
    $stmt->execute([$currentUser['id']]);
    $xpData = $stmt->fetch();
    $currentXP = $xpData['xp_points'];
} catch (PDOException $e) {
    $currentXP = 0; // Default if column doesn't exist
}

// Calculate level and progress
$currentLevel = floor($currentXP / 100);
$xpInCurrentLevel = $currentXP % 100;
$xpToNextLevel = 100 - $xpInCurrentLevel;
$levelProgress = $xpInCurrentLevel;

// Get progress statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT lesson_id) as lessons_started,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as lessons_completed,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as lessons_in_progress,
        SUM(time_spent_seconds) as total_time,
        AVG(score) as avg_score
    FROM user_progress
    WHERE user_id = :user_id
");
$stmt->execute(['user_id' => $currentUser['id']]);
$stats = $stmt->fetch();

// Get recent activity
$currentLang = getCurrentLanguage();
$stmt = $pdo->prepare("
    SELECT 
        l.id,
        l.title_{$currentLang} as title,
        s.name_{$currentLang} as subject,
        up.progress_percentage,
        up.status,
        up.last_accessed
    FROM user_progress up
    INNER JOIN lessons l ON up.lesson_id = l.id
    INNER JOIN subjects s ON l.subject_id = s.id
    WHERE up.user_id = :user_id
    ORDER BY up.last_accessed DESC
    LIMIT 5
");
$stmt->execute(['user_id' => $currentUser['id']]);
$recentLessons = $stmt->fetchAll();

// Include header
include_once __DIR__ . '/includes/_header.php';
?>

<style>
.dashboard-header {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
    color: var(--text-white);
    padding: 3rem 0;
    margin-bottom: 3rem;
}

.dashboard-welcome h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: var(--text-white);
}

.dashboard-welcome p {
    font-size: 1.125rem;
    opacity: 0.9;
}

/* XP Progress Card */
.xp-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-xl);
    color: white;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.xp-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: pulse 3s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 0.5; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.xp-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
}

.xp-level {
    font-size: 3rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.xp-amount {
    font-size: 1.5rem;
    font-weight: 600;
}

.xp-progress-container {
    background: rgba(255, 255, 255, 0.2);
    height: 30px;
    border-radius: 999px;
    overflow: hidden;
    position: relative;
    z-index: 1;
}

.xp-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    border-radius: 999px;
    transition: width 1s ease-out;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
    box-shadow: 0 2px 10px rgba(16, 185, 129, 0.5);
    position: relative;
}

.xp-progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.xp-info {
    display: flex;
    justify-content: space-between;
    margin-top: 0.75rem;
    font-size: 0.938rem;
    position: relative;
    z-index: 1;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: var(--bg-white);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    border-left: 4px solid var(--primary-blue);
    transition: transform var(--transition-fast);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: 0.5rem;
    animation: countUp 1s ease-out;
}

@keyframes countUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.stat-label {
    color: var(--text-gray);
    font-size: 0.938rem;
    font-weight: 500;
}

.section-title {
    font-size: 1.5rem;
    color: var(--text-dark);
    margin-bottom: 1.5rem;
}

.lesson-list {
    background: var(--bg-white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    overflow: hidden;
}

.lesson-item {
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    transition: background-color var(--transition-fast);
    cursor: pointer;
}

.lesson-item:last-child {
    border-bottom: none;
}

.lesson-item:hover {
    background-color: var(--bg-gray-50);
}

.lesson-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 0.5rem;
}

.lesson-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 0.25rem;
}

.lesson-subject {
    font-size: 0.875rem;
    color: var(--text-gray);
}

.lesson-status {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.813rem;
    font-weight: 500;
}

.status-completed {
    background-color: #d1fae5;
    color: #065f46;
}

.status-in-progress {
    background-color: #dbeafe;
    color: #1e40af;
}

.status-not-started {
    background-color: #f3f4f6;
    color: #4b5563;
}

.progress-bar-container {
    height: 8px;
    background-color: var(--gray-200);
    border-radius: 999px;
    overflow: hidden;
    margin-top: 0.75rem;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
    border-radius: 999px;
    transition: width 0.6s ease-out;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-gray);
}

.empty-state svg {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    color: var(--gray-400);
}

/* Floating XP Animation */
.floating-xp {
    position: fixed;
    font-size: 2rem;
    font-weight: 700;
    color: #10b981;
    pointer-events: none;
    z-index: 9999;
    animation: floatUp 2s ease-out forwards;
}

@keyframes floatUp {
    0% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translateY(-100px) scale(1.5);
    }
}

@media (max-width: 768px) {
    .dashboard-welcome h1 {
        font-size: 2rem;
    }
    
    .xp-level {
        font-size: 2rem;
    }
    
    .xp-amount {
        font-size: 1.125rem;
    }
    
    .lesson-header {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<div class="dashboard-header">
    <div class="container">
        <div class="dashboard-welcome">
            <h1><?= __('welcome_back') ?>, <?= escape($currentUser['first_name']) ?>! 🎓</h1>
            <p><?= __('continue_learning') ?></p>
        </div>
    </div>
</div>

<section class="container" style="padding-bottom: 3rem;">
    <!-- XP Progress Card -->
    <div class="xp-card">
        <div class="xp-header">
            <div>
                <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 0.5rem;">
                    <?= __('current_level') ?>
                </div>
                <div class="xp-level">
                    <?= __('level') ?> <?= $currentLevel ?>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 0.5rem;">
                    <?= __('total_xp') ?>
                </div>
                <div class="xp-amount" id="currentXP" data-target="<?= $currentXP ?>">
                    0 XP
                </div>
            </div>
        </div>
        
        <div class="xp-progress-container">
            <div class="xp-progress-bar" id="xpProgress" style="width: 0%">
                <span id="progressText">0%</span>
            </div>
        </div>
        
        <div class="xp-info">
            <span><?= $xpInCurrentLevel ?> / 100 XP</span>
            <span><?= $xpToNextLevel ?> XP <?= __('to_next_level') ?></span>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-value" data-target="<?= $stats['lessons_started'] ?? 0 ?>">0</div>
            <div class="stat-label"><?= __('total_lessons') ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-value" data-target="<?= $stats['lessons_completed'] ?? 0 ?>">0</div>
            <div class="stat-label"><?= __('lessons_completed') ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📖</div>
            <div class="stat-value" data-target="<?= $stats['lessons_in_progress'] ?? 0 ?>">0</div>
            <div class="stat-label"><?= __('lessons_in_progress') ?></div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏰</div>
            <div class="stat-value" data-time="<?= $stats['total_time'] ?? 0 ?>"><?= formatDuration($stats['total_time'] ?? 0) ?></div>
            <div class="stat-label"><?= __('total_time_spent') ?></div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <h2 class="section-title"><?= __('recent_activity') ?></h2>
    
    <?php if (empty($recentLessons)): ?>
        <div class="lesson-list">
            <div class="empty-state">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                <p><?= __('no_data') ?></p>
                <a href="<?= url('/levels') ?>" class="btn btn-primary" style="margin-top: 1rem;">
                    <?= __('explore_courses') ?>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="lesson-list">
            <?php foreach ($recentLessons as $lesson): ?>
                <div class="lesson-item" onclick="window.location.href='/lesson/<?= $lesson['id'] ?>'">
                    <div class="lesson-header">
                        <div>
                            <div class="lesson-title"><?= escape($lesson['title']) ?></div>
                            <div class="lesson-subject"><?= escape($lesson['subject']) ?></div>
                        </div>
                        <span class="lesson-status status-<?= $lesson['status'] ?>">
                            <?= __($lesson['status']) ?>
                        </span>
                    </div>
                    
                    <div class="progress-bar-container">
                        <div class="progress-bar" style="width: <?= $lesson['progress_percentage'] ?>%"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div style="margin-top: 2rem; text-align: center;">
        <a href="<?= url('/levels') ?>" class="btn btn-primary" style="margin: 0.5rem;">
            <?= __('explore_courses') ?>
        </a>
        <a href="<?= url('/profile') ?>" class="btn btn-secondary" style="margin: 0.5rem;">
            <?= __('profile') ?>
        </a>
    </div>
</section>

<script>
// Animate XP count-up
document.addEventListener('DOMContentLoaded', function() {
    animateXP();
    animateStats();
    animateXPProgress();
});

function animateXP() {
    const xpElement = document.getElementById('currentXP');
    const target = parseInt(xpElement.dataset.target);
    const duration = 2000;
    const steps = 60;
    const increment = target / steps;
    let current = 0;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        xpElement.textContent = Math.floor(current) + ' XP';
    }, duration / steps);
}

function animateStats() {
    const statValues = document.querySelectorAll('.stat-value');
    
    statValues.forEach(stat => {
        if (stat.dataset.target) {
            const target = parseInt(stat.dataset.target);
            const duration = 1500;
            const steps = 50;
            const increment = target / steps;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                stat.textContent = Math.floor(current);
            }, duration / steps);
        }
    });
}

function animateXPProgress() {
    const progressBar = document.getElementById('xpProgress');
    const progressText = document.getElementById('progressText');
    const targetProgress = <?= $levelProgress ?>;
    
    setTimeout(() => {
        progressBar.style.width = targetProgress + '%';
        progressText.textContent = targetProgress + '%';
    }, 500);
}

// Show floating XP when awarded
function showFloatingXP(amount, x, y) {
    const floatingXP = document.createElement('div');
    floatingXP.className = 'floating-xp';
    floatingXP.textContent = '+' + amount + ' XP';
    floatingXP.style.left = x + 'px';
    floatingXP.style.top = y + 'px';
    
    document.body.appendChild(floatingXP);
    
    setTimeout(() => {
        floatingXP.remove();
    }, 2000);
}
</script>

<?php
// Include footer
include_once __DIR__ . '/includes/_footer.php';
?>

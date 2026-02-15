<?php
/**
 * ============================================
 * Levels Page
 * Display all available levels
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

$pageTitle = __('levels');
$pdo = getDatabaseConnection();
$currentLang = getCurrentLanguage();

// Fetch all active levels
$stmt = $pdo->query("
    SELECT 
        l.id,
        l.name_{$currentLang} as name,
        l.description_{$currentLang} as description,
        COUNT(DISTINCT s.id) as subject_count,
        COUNT(DISTINCT lessons.id) as lesson_count
    FROM levels l
    LEFT JOIN subjects s ON s.level_id = l.id AND s.is_active = 1
    LEFT JOIN lessons ON lessons.subject_id = s.id AND lessons.is_active = 1
    WHERE l.is_active = 1
    GROUP BY l.id
    ORDER BY l.order_position ASC
");
$levels = $stmt->fetchAll();

include_once __DIR__ . '/includes/_header.php';
?>

<style>
.page-header {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
    color: white;
    padding: 4rem 0 3rem;
    margin-bottom: 3rem;
}

.page-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: white;
}

.levels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.level-card {
    background: var(--bg-white);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
    text-decoration: none;
    display: block;
}

.level-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
}

.level-card h3 {
    font-size: 1.5rem;
    color: var(--primary-blue);
    margin-bottom: 1rem;
}

.level-card p {
    color: var(--text-gray);
    margin-bottom: 1.5rem;
}

.level-stats {
    display: flex;
    gap: 1.5rem;
    font-size: 0.875rem;
    color: var(--text-gray);
}

.level-stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
</style>

<div class="page-header">
    <div class="container">
        <h1><?= __('levels') ?></h1>
        <p><?= __('select_your_level') ?></p>
    </div>
</div>

<div class="container" style="padding-bottom: 3rem;">
    <?php if (empty($levels)): ?>
        <div style="text-align: center; padding: 3rem 0;">
            <p style="font-size: 1.25rem; color: var(--text-gray);">
                <?= __('no_levels_available') ?>
            </p>
        </div>
    <?php else: ?>
        <div class="levels-grid">
            <?php foreach ($levels as $level): ?>
                <a href="<?= APP_URL ?>/level.php?id=<?= $level['id'] ?>" class="level-card">
                    <h3><?= escape($level['name']) ?></h3>
                    <p><?= escape($level['description'] ?? __('explore_this_level')) ?></p>
                    <div class="level-stats">
                        <div class="level-stat">
                            <span>📚</span>
                            <span><?= $level['subject_count'] ?> <?= __('subjects') ?></span>
                        </div>
                        <div class="level-stat">
                            <span>📖</span>
                            <span><?= $level['lesson_count'] ?> <?= __('lessons') ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/_footer.php'; ?>

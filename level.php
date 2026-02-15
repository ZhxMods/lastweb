<?php
/**
 * ============================================
 * Level Detail Page
 * Shows all subjects for a specific level
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

$levelId = (int)($_GET['id'] ?? 0);

if ($levelId <= 0) {
    redirect(APP_URL . '/levels.php');
}

$pdo = getDatabaseConnection();
$currentLang = getCurrentLanguage();

// Fetch level details
$stmt = $pdo->prepare("
    SELECT 
        id,
        name_{$currentLang} as name,
        description_{$currentLang} as description
    FROM levels
    WHERE id = ? AND is_active = 1
");
$stmt->execute([$levelId]);
$level = $stmt->fetch();

if (!$level) {
    redirect(APP_URL . '/levels.php');
}

// Fetch subjects for this level
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.name_{$currentLang} as name,
        s.description_{$currentLang} as description,
        s.icon,
        s.color,
        COUNT(l.id) as lesson_count
    FROM subjects s
    LEFT JOIN lessons l ON l.subject_id = s.id AND l.is_active = 1
    WHERE s.level_id = ? AND s.is_active = 1
    GROUP BY s.id
    ORDER BY s.order_position ASC
");
$stmt->execute([$levelId]);
$subjects = $stmt->fetchAll();

$pageTitle = $level['name'];
include_once __DIR__ . '/includes/_header.php';
?>

<style>
.level-header {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 3rem;
}

.level-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: white;
}

.subjects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.subject-card {
    background: var(--bg-white);
    border-radius: var(--border-radius-lg);
    padding: 2rem;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
    text-decoration: none;
    display: block;
    border-left: 4px solid var(--primary-blue);
}

.subject-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
}

.subject-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.subject-card h3 {
    font-size: 1.25rem;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.subject-card p {
    color: var(--text-gray);
    font-size: 0.938rem;
    margin-bottom: 1rem;
}

.lesson-count {
    display: inline-block;
    background: var(--bg-gray-100);
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.813rem;
    color: var(--primary-blue);
    font-weight: 500;
}
</style>

<div class="level-header">
    <div class="container">
        <h1><?= escape($level['name']) ?></h1>
        <p><?= escape($level['description'] ?? '') ?></p>
    </div>
</div>

<div class="container" style="padding-bottom: 3rem;">
    <a href="<?= APP_URL ?>/levels.php" class="btn btn-secondary" style="margin-bottom: 2rem;">
        ← <?= __('back_to_levels') ?>
    </a>
    
    <?php if (empty($subjects)): ?>
        <div style="text-align: center; padding: 3rem 0;">
            <p style="font-size: 1.25rem; color: var(--text-gray);">
                <?= __('no_subjects_available') ?>
            </p>
        </div>
    <?php else: ?>
        <div class="subjects-grid">
            <?php foreach ($subjects as $subject): ?>
                <a href="<?= APP_URL ?>/subject.php?id=<?= $subject['id'] ?>" 
                   class="subject-card" 
                   style="border-left-color: <?= escape($subject['color'] ?? '#2563eb') ?>;">
                    <div class="subject-icon"><?= escape($subject['icon'] ?? '📚') ?></div>
                    <h3><?= escape($subject['name']) ?></h3>
                    <p><?= escape(substr($subject['description'] ?? '', 0, 100)) ?><?= strlen($subject['description'] ?? '') > 100 ? '...' : '' ?></p>
                    <span class="lesson-count">
                        <?= $subject['lesson_count'] ?> <?= __('lessons') ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/_footer.php'; ?>

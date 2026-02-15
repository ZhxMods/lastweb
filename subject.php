<?php
/**
 * ============================================
 * Subject Detail Page
 * Shows all lessons for a specific subject
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

$subjectId = (int)($_GET['id'] ?? 0);

if ($subjectId <= 0) {
    redirect(APP_URL . '/levels.php');
}

$pdo = getDatabaseConnection();
$currentLang = getCurrentLanguage();

// Fetch subject details with level info
$stmt = $pdo->prepare("
    SELECT 
        s.id,
        s.name_{$currentLang} as name,
        s.description_{$currentLang} as description,
        s.icon,
        s.color,
        s.level_id,
        l.name_{$currentLang} as level_name
    FROM subjects s
    INNER JOIN levels l ON s.level_id = l.id
    WHERE s.id = ? AND s.is_active = 1
");
$stmt->execute([$subjectId]);
$subject = $stmt->fetch();

if (!$subject) {
    redirect(APP_URL . '/levels.php');
}

// Fetch lessons for this subject
$stmt = $pdo->prepare("
    SELECT 
        id,
        title_{$currentLang} as title,
        description_{$currentLang} as description,
        duration_minutes,
        difficulty,
        is_free,
        thumbnail,
        views_count
    FROM lessons
    WHERE subject_id = ? AND is_active = 1
    ORDER BY order_position ASC
");
$stmt->execute([$subjectId]);
$lessons = $stmt->fetchAll();

$pageTitle = $subject['name'];
include_once __DIR__ . '/includes/_header.php';
?>

<style>
.subject-header {
    background: linear-gradient(135deg, <?= $subject['color'] ?? '#2563eb' ?> 0%, <?= $subject['color'] ?? '#1d4ed8' ?> 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 3rem;
}

.subject-header-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.subject-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: white;
}

.lessons-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.lesson-card {
    background: var(--bg-white);
    border-radius: var(--border-radius-lg);
    padding: 1.5rem;
    box-shadow: var(--shadow-md);
    transition: all var(--transition-base);
    text-decoration: none;
    display: flex;
    gap: 1.5rem;
    align-items: center;
}

.lesson-card:hover {
    transform: translateX(4px);
    box-shadow: var(--shadow-lg);
}

.lesson-thumbnail {
    width: 120px;
    height: 80px;
    border-radius: var(--border-radius);
    background: var(--bg-gray-100);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.lesson-info {
    flex: 1;
}

.lesson-info h3 {
    font-size: 1.25rem;
    color: var(--text-dark);
    margin-bottom: 0.5rem;
}

.lesson-info p {
    color: var(--text-gray);
    font-size: 0.938rem;
    margin-bottom: 0.75rem;
}

.lesson-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    font-size: 0.813rem;
    color: var(--text-gray);
}

.lesson-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.difficulty-easy { color: #10b981; }
.difficulty-medium { color: #f59e0b; }
.difficulty-hard { color: #ef4444; }
</style>

<div class="subject-header">
    <div class="container">
        <div class="subject-header-icon"><?= escape($subject['icon'] ?? '📚') ?></div>
        <h1><?= escape($subject['name']) ?></h1>
        <p><?= escape($subject['description'] ?? '') ?></p>
        <p style="opacity: 0.9; margin-top: 0.5rem;">
            📚 <?= escape($subject['level_name']) ?>
        </p>
    </div>
</div>

<div class="container" style="padding-bottom: 3rem;">
    <a href="<?= APP_URL ?>/level.php?id=<?= $subject['level_id'] ?>" class="btn btn-secondary" style="margin-bottom: 2rem;">
        ← <?= __('back_to_level') ?>
    </a>
    
    <?php if (empty($lessons)): ?>
        <div style="text-align: center; padding: 3rem 0;">
            <p style="font-size: 1.25rem; color: var(--text-gray);">
                <?= __('no_lessons_available') ?>
            </p>
        </div>
    <?php else: ?>
        <div class="lessons-list">
            <?php foreach ($lessons as $lesson): ?>
                <a href="<?= APP_URL ?>/lesson.php?id=<?= $lesson['id'] ?>" class="lesson-card">
                    <div class="lesson-thumbnail">
                        <?php if ($lesson['thumbnail']): ?>
                            <img src="<?= escape($lesson['thumbnail']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover; border-radius: var(--border-radius);">
                        <?php else: ?>
                            📖
                        <?php endif; ?>
                    </div>
                    <div class="lesson-info">
                        <h3><?= escape($lesson['title']) ?></h3>
                        <p><?= escape(substr($lesson['description'] ?? '', 0, 150)) ?><?= strlen($lesson['description'] ?? '') > 150 ? '...' : '' ?></p>
                        <div class="lesson-meta">
                            <span class="lesson-badge">
                                ⏱️ <?= $lesson['duration_minutes'] ?> min
                            </span>
                            <span class="lesson-badge difficulty-<?= $lesson['difficulty'] ?>">
                                <?= $lesson['difficulty'] === 'easy' ? '⭐' : ($lesson['difficulty'] === 'medium' ? '⭐⭐' : '⭐⭐⭐') ?>
                                <?= ucfirst($lesson['difficulty']) ?>
                            </span>
                            <?php if ($lesson['is_free']): ?>
                                <span class="lesson-badge" style="color: #10b981;">
                                    ✓ <?= __('free') ?>
                                </span>
                            <?php endif; ?>
                            <span class="lesson-badge">
                                👁️ <?= number_format($lesson['views_count']) ?>
                            </span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/includes/_footer.php'; ?>

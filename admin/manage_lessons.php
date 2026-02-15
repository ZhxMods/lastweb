<?php
/**
 * ============================================
 * Admin: Lesson Manager
 * Full CRUD | Multi-Language | Live Preview
 * ============================================
 */

require_once __DIR__ . '/admin_auth.php';

$pageTitle = __('manage_lessons');

// Get database connection
$pdo = getDatabaseConnection();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Verify CSRF
    if (!adminVerifyCsrf()) {
        exit(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
    }
    
    $action = $_POST['action'];
    
    // Get Lesson
    if ($action === 'get_lesson') {
        $lessonId = (int)$_POST['lesson_id'];
        
        $stmt = $pdo->prepare("
            SELECT * FROM lessons WHERE id = ?
        ");
        $stmt->execute([$lessonId]);
        $lesson = $stmt->fetch();
        
        exit(json_encode([
            'success' => true,
            'lesson' => $lesson
        ]));
    }
    
    // Save Lesson (Create or Update)
    if ($action === 'save_lesson') {
        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $subjectId = (int)$_POST['subject_id'];
        $titleFr = sanitizeString($_POST['title_fr']);
        $titleEn = sanitizeString($_POST['title_en']);
        $titleAr = sanitizeString($_POST['title_ar']);
        $descFr = sanitizeString($_POST['description_fr'] ?? '');
        $descEn = sanitizeString($_POST['description_en'] ?? '');
        $descAr = sanitizeString($_POST['description_ar'] ?? '');
        $contentFr = $_POST['content_fr'] ?? '';
        $contentEn = $_POST['content_en'] ?? '';
        $contentAr = $_POST['content_ar'] ?? '';
        $videoUrl = $_POST['video_url'] ?? '';
        $duration = (int)($_POST['duration_minutes'] ?? 0);
        $difficulty = $_POST['difficulty'] ?? 'medium';
        $orderPosition = (int)($_POST['order_position'] ?? 0);
        $isFree = isset($_POST['is_free']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            if ($lessonId > 0) {
                // Update existing lesson
                $stmt = $pdo->prepare("
                    UPDATE lessons SET
                        subject_id = ?,
                        title_fr = ?,
                        title_en = ?,
                        title_ar = ?,
                        description_fr = ?,
                        description_en = ?,
                        description_ar = ?,
                        content_fr = ?,
                        content_en = ?,
                        content_ar = ?,
                        video_url = ?,
                        duration_minutes = ?,
                        difficulty = ?,
                        order_position = ?,
                        is_free = ?,
                        is_active = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $subjectId, $titleFr, $titleEn, $titleAr,
                    $descFr, $descEn, $descAr,
                    $contentFr, $contentEn, $contentAr,
                    $videoUrl, $duration, $difficulty, $orderPosition,
                    $isFree, $isActive, $lessonId
                ]);
                
                exit(json_encode([
                    'success' => true,
                    'message' => 'Lesson updated successfully'
                ]));
                
            } else {
                // Create new lesson
                $stmt = $pdo->prepare("
                    INSERT INTO lessons (
                        subject_id, title_fr, title_en, title_ar,
                        description_fr, description_en, description_ar,
                        content_fr, content_en, content_ar,
                        video_url, duration_minutes, difficulty,
                        order_position, is_free, is_active,
                        created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $stmt->execute([
                    $subjectId, $titleFr, $titleEn, $titleAr,
                    $descFr, $descEn, $descAr,
                    $contentFr, $contentEn, $contentAr,
                    $videoUrl, $duration, $difficulty, $orderPosition,
                    $isFree, $isActive
                ]);
                
                exit(json_encode([
                    'success' => true,
                    'message' => 'Lesson created successfully'
                ]));
            }
        } catch (PDOException $e) {
            exit(json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]));
        }
    }
    
    // Delete Lesson
    if ($action === 'delete_lesson') {
        $lessonId = (int)$_POST['lesson_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
            $stmt->execute([$lessonId]);
            
            exit(json_encode([
                'success' => true,
                'message' => 'Lesson deleted successfully'
            ]));
        } catch (PDOException $e) {
            exit(json_encode([
                'success' => false,
                'message' => 'Error deleting lesson'
            ]));
        }
    }
}

// Fetch all lessons with subject and level info
$lessons = $pdo->query("
    SELECT 
        l.id,
        l.title_fr,
        l.title_en,
        l.title_ar,
        l.video_url,
        l.duration_minutes,
        l.difficulty,
        l.is_active,
        l.views_count,
        s.name_fr as subject_name,
        lv.name_fr as level_name
    FROM lessons l
    INNER JOIN subjects s ON l.subject_id = s.id
    INNER JOIN levels lv ON s.level_id = lv.id
    ORDER BY l.created_at DESC
")->fetchAll();

// Fetch subjects for dropdown
$subjects = $pdo->query("
    SELECT id, name_fr, name_en, name_ar 
    FROM subjects 
    WHERE is_active = 1 
    ORDER BY name_fr
")->fetchAll();

// Include layout
include_once __DIR__ . '/_layout.php';
?>

<style>
.lesson-table-actions {
    display: flex;
    gap: 0.5rem;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.813rem;
    font-weight: 500;
}

.status-active {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
}

.status-inactive {
    background: rgba(107, 114, 128, 0.2);
    color: #6b7280;
}

.preview-container {
    width: 100%;
    max-width: 100%;
    margin-top: 1rem;
}

.preview-video {
    width: 100%;
    aspect-ratio: 16/9;
    border-radius: 6px;
    background: #000;
}

.preview-image {
    max-width: 100%;
    border-radius: 6px;
}

/* Tab styling */
.nav-tabs {
    border-bottom: 2px solid var(--admin-border);
    margin-bottom: 1.5rem;
}

.nav-tabs .nav-link {
    background: transparent;
    border: none;
    color: var(--admin-text-dim);
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
}

.nav-tabs .nav-link:hover {
    color: var(--admin-text);
    border-bottom-color: var(--admin-text-dim);
}

.nav-tabs .nav-link.active {
    color: var(--admin-blue);
    border-bottom-color: var(--admin-blue);
    background: transparent;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}
</style>

<div class="content-header">
    <div style="display: flex; justify-content: space-between; align-items: start;">
        <div>
            <h1><?= __('manage_lessons') ?></h1>
            <p><?= __('create_edit_lessons') ?></p>
        </div>
        <button class="admin-btn admin-btn-primary" onclick="openLessonModal()">
            ➕ <?= __('add_new_lesson') ?>
        </button>
    </div>
</div>

<!-- Lessons Table -->
<div class="admin-card">
    <table id="lessonsTable" class="display" style="width:100%">
        <thead>
            <tr>
                <th>ID</th>
                <th><?= __('title') ?> (FR)</th>
                <th><?= __('subject') ?></th>
                <th><?= __('level') ?></th>
                <th><?= __('duration') ?></th>
                <th><?= __('difficulty') ?></th>
                <th><?= __('status') ?></th>
                <th><?= __('views') ?></th>
                <th><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lessons as $lesson): ?>
            <tr>
                <td><?= $lesson['id'] ?></td>
                <td><?= escape($lesson['title_fr']) ?></td>
                <td><?= escape($lesson['subject_name']) ?></td>
                <td><?= escape($lesson['level_name']) ?></td>
                <td><?= $lesson['duration_minutes'] ?> min</td>
                <td>
                    <?php
                    $difficultyColors = [
                        'easy' => '#10b981',
                        'medium' => '#f59e0b',
                        'hard' => '#ef4444'
                    ];
                    $color = $difficultyColors[$lesson['difficulty']] ?? '#6b7280';
                    ?>
                    <span style="color: <?= $color ?>;">
                        <?= ucfirst($lesson['difficulty']) ?>
                    </span>
                </td>
                <td>
                    <span class="status-badge <?= $lesson['is_active'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $lesson['is_active'] ? __('active') : __('inactive') ?>
                    </span>
                </td>
                <td><?= number_format($lesson['views_count']) ?></td>
                <td>
                    <div class="lesson-table-actions">
                        <button class="admin-btn admin-btn-secondary" onclick="editLesson(<?= $lesson['id'] ?>)">
                            ✏️ <?= __('edit') ?>
                        </button>
                        <button class="admin-btn admin-btn-danger" onclick="deleteLesson(<?= $lesson['id'] ?>)">
                            🗑️ <?= __('delete') ?>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Lesson Modal -->
<div id="lessonModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.8); z-index:9999; overflow-y:auto;">
    <div style="max-width: 900px; margin: 2rem auto; background: var(--admin-sidebar); border-radius: 8px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 id="modalTitle" style="color: var(--admin-text);"><?= __('add_new_lesson') ?></h2>
            <button onclick="closeModal()" style="background: transparent; border: none; color: var(--admin-text); font-size: 1.5rem; cursor: pointer;">×</button>
        </div>
        
        <form id="lessonForm">
            <input type="hidden" id="lesson_id" name="lesson_id" value="0">
            
            <!-- Subject Selection -->
            <div class="form-group">
                <label class="form-label"><?= __('subject') ?></label>
                <select class="form-control" name="subject_id" required>
                    <option value=""><?= __('select_subject') ?></option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?= $subject['id'] ?>"><?= escape($subject['name_fr']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Multi-language Tabs -->
            <div class="nav-tabs" style="display: flex; gap: 0;">
                <button type="button" class="nav-link active" onclick="switchTab(event, 'fr')">🇫🇷 Français</button>
                <button type="button" class="nav-link" onclick="switchTab(event, 'en')">🇬🇧 English</button>
                <button type="button" class="nav-link" onclick="switchTab(event, 'ar')">🇸🇦 العربية</button>
            </div>
            
            <!-- French Tab -->
            <div class="tab-pane active" id="tab-fr">
                <div class="form-group">
                    <label class="form-label"><?= __('title') ?> (FR)</label>
                    <input type="text" class="form-control" name="title_fr" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('description') ?> (FR)</label>
                    <textarea class="form-control" name="description_fr" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('content') ?> (FR)</label>
                    <textarea class="form-control" name="content_fr" rows="5"></textarea>
                </div>
            </div>
            
            <!-- English Tab -->
            <div class="tab-pane" id="tab-en">
                <div class="form-group">
                    <label class="form-label"><?= __('title') ?> (EN)</label>
                    <input type="text" class="form-control" name="title_en" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('description') ?> (EN)</label>
                    <textarea class="form-control" name="description_en" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('content') ?> (EN)</label>
                    <textarea class="form-control" name="content_en" rows="5"></textarea>
                </div>
            </div>
            
            <!-- Arabic Tab -->
            <div class="tab-pane" id="tab-ar">
                <div class="form-group">
                    <label class="form-label"><?= __('title') ?> (AR)</label>
                    <input type="text" class="form-control" name="title_ar" required dir="rtl">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('description') ?> (AR)</label>
                    <textarea class="form-control" name="description_ar" rows="3" dir="rtl"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('content') ?> (AR)</label>
                    <textarea class="form-control" name="content_ar" rows="5" dir="rtl"></textarea>
                </div>
            </div>
            
            <!-- Video/Media URL with Live Preview -->
            <div class="form-group">
                <label class="form-label"><?= __('video_url') ?> (YouTube/PDF/Image)</label>
                <input type="url" class="form-control" name="video_url" id="videoUrl" onchange="updatePreview()">
                <div id="mediaPreview" class="preview-container"></div>
            </div>
            
            <!-- Duration & Difficulty -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label"><?= __('duration') ?> (minutes)</label>
                    <input type="number" class="form-control" name="duration_minutes" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= __('difficulty') ?></label>
                    <select class="form-control" name="difficulty">
                        <option value="easy"><?= __('easy') ?></option>
                        <option value="medium" selected><?= __('medium') ?></option>
                        <option value="hard"><?= __('hard') ?></option>
                    </select>
                </div>
            </div>
            
            <!-- Options -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text);">
                    <input type="checkbox" name="is_free" value="1">
                    <?= __('free_lesson') ?>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; color: var(--admin-text);">
                    <input type="checkbox" name="is_active" value="1" checked>
                    <?= __('active') ?>
                </label>
                <div class="form-group">
                    <input type="number" class="form-control" name="order_position" placeholder="<?= __('order') ?>" value="0">
                </div>
            </div>
            
            <!-- Submit -->
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="admin-btn admin-btn-secondary" onclick="closeModal()">
                    <?= __('cancel') ?>
                </button>
                <button type="submit" class="admin-btn admin-btn-primary">
                    💾 <?= __('save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$additionalJS = <<<'JAVASCRIPT'
<script>
let lessonsTable;

$(document).ready(function() {
    // Initialize DataTable
    lessonsTable = initDataTable('#lessonsTable');
    
    // Form submit handler
    $('#lessonForm').on('submit', function(e) {
        e.preventDefault();
        saveLesson();
    });
});

function switchTab(event, lang) {
    event.preventDefault();
    
    // Update tab buttons
    $('.nav-link').removeClass('active');
    $(event.target).addClass('active');
    
    // Update tab panes
    $('.tab-pane').removeClass('active');
    $('#tab-' + lang).addClass('active');
}

function openLessonModal() {
    $('#lesson_id').val('0');
    $('#lessonForm')[0].reset();
    $('#modalTitle').text('<?= __('add_new_lesson') ?>');
    $('#lessonModal').show();
}

function closeModal() {
    $('#lessonModal').hide();
    $('#mediaPreview').empty();
}

function editLesson(lessonId) {
    adminAjax('', {
        action: 'get_lesson',
        lesson_id: lessonId
    }, function(response) {
        const lesson = response.lesson;
        
        // Populate form
        $('#lesson_id').val(lesson.id);
        $('[name="subject_id"]').val(lesson.subject_id);
        $('[name="title_fr"]').val(lesson.title_fr);
        $('[name="title_en"]').val(lesson.title_en);
        $('[name="title_ar"]').val(lesson.title_ar);
        $('[name="description_fr"]').val(lesson.description_fr);
        $('[name="description_en"]').val(lesson.description_en);
        $('[name="description_ar"]').val(lesson.description_ar);
        $('[name="content_fr"]').val(lesson.content_fr);
        $('[name="content_en"]').val(lesson.content_en);
        $('[name="content_ar"]').val(lesson.content_ar);
        $('[name="video_url"]').val(lesson.video_url);
        $('[name="duration_minutes"]').val(lesson.duration_minutes);
        $('[name="difficulty"]').val(lesson.difficulty);
        $('[name="order_position"]').val(lesson.order_position);
        $('[name="is_free"]').prop('checked', lesson.is_free == 1);
        $('[name="is_active"]').prop('checked', lesson.is_active == 1);
        
        updatePreview();
        
        $('#modalTitle').text('<?= __('edit_lesson') ?>');
        $('#lessonModal').show();
    });
}

function saveLesson() {
    const formData = $('#lessonForm').serialize() + '&action=save_lesson';
    
    adminAjax('', formData, function(response) {
        showAlert(response.message, 'success');
        closeModal();
        lessonsTable.ajax.reload();
        location.reload(); // Reload to update table
    });
}

function deleteLesson(lessonId) {
    confirmAction('<?= __('confirm_delete_lesson') ?>', function() {
        adminAjax('', {
            action: 'delete_lesson',
            lesson_id: lessonId
        }, function(response) {
            showAlert(response.message, 'success');
            lessonsTable.ajax.reload();
            location.reload();
        });
    });
}

function updatePreview() {
    const url = $('#videoUrl').val();
    const preview = $('#mediaPreview');
    preview.empty();
    
    if (!url) return;
    
    // YouTube
    if (url.match(/youtube\.com|youtu\.be/)) {
        const videoId = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/);
        if (videoId) {
            preview.html(`
                <iframe class="preview-video" 
                    src="https://www.youtube.com/embed/${videoId[1]}" 
                    frameborder="0" allowfullscreen>
                </iframe>
            `);
        }
    }
    // PDF
    else if (url.match(/\.pdf$/i)) {
        preview.html('<p style="color: var(--admin-text);">📄 PDF Document</p>');
    }
    // Image
    else if (url.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
        preview.html(`<img src="${url}" class="preview-image" alt="Preview">`);
    }
}
</script>
JAVASCRIPT;

include_once __DIR__ . '/_layout_end.php';
?>

<?php
/**
 * ============================================
 * Lesson View Page
 * YouTube Anti-Cheat | PDF Viewer | Image Display
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

// Require authentication
requireAuth();

// Get lesson ID from URL (/lesson/123)
$lessonId = (int)($_GET['id'] ?? 0);

if ($lessonId <= 0) {
    $_SESSION['error'] = __('invalid_lesson');
    redirect('/levels');
}

// Get database connection
$pdo = getDatabaseConnection();
$currentLang = getCurrentLanguage();

// Fetch lesson details
$stmt = $pdo->prepare("
    SELECT 
        l.id,
        l.title_{$currentLang} as title,
        l.description_{$currentLang} as description,
        l.content_{$currentLang} as content,
        l.video_url,
        l.thumbnail,
        l.duration_minutes,
        l.difficulty,
        s.name_{$currentLang} as subject_name,
        lv.name_{$currentLang} as level_name
    FROM lessons l
    INNER JOIN subjects s ON l.subject_id = s.id
    INNER JOIN levels lv ON s.level_id = lv.id
    WHERE l.id = ? AND l.is_active = 1
");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    $_SESSION['error'] = __('lesson_not_found');
    redirect('/levels');
}

// Check user progress
$currentUser = loadCurrentUser();
$stmt = $pdo->prepare("
    SELECT status, progress_percentage, time_spent_seconds, completed_at
    FROM user_progress
    WHERE user_id = ? AND lesson_id = ?
");
$stmt->execute([$currentUser['id'], $lessonId]);
$progress = $stmt->fetch();

$isCompleted = $progress && $progress['status'] === 'completed';

// Detect content type
$contentType = 'text'; // default
$videoId = null;
$pdfUrl = null;
$imageUrl = null;

if (!empty($lesson['video_url'])) {
    // Check if YouTube URL
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $lesson['video_url'], $matches)) {
        $contentType = 'youtube';
        $videoId = $matches[1];
    } elseif (strpos($lesson['video_url'], '.pdf') !== false) {
        $contentType = 'pdf';
        $pdfUrl = $lesson['video_url'];
    } elseif (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $lesson['video_url'])) {
        $contentType = 'image';
        $imageUrl = $lesson['video_url'];
    }
}

// Generate verification token
$verificationToken = hash('sha256', $currentUser['id'] . $lessonId . session_id() . ENCRYPTION_KEY);

// Set page title
$pageTitle = $lesson['title'];

// Include header
include_once __DIR__ . '/includes/_header.php';
?>

<style>
.lesson-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.lesson-header {
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
    color: var(--text-white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    margin-bottom: 2rem;
}

.lesson-meta {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.lesson-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 999px;
    font-size: 0.875rem;
}

.content-wrapper {
    background: var(--bg-white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    margin-bottom: 2rem;
}

.video-container {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 aspect ratio */
    height: 0;
    overflow: hidden;
    background: #000;
}

.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
}

.pdf-viewer {
    width: 100%;
    height: 800px;
    border: none;
}

.image-viewer {
    width: 100%;
    max-width: 100%;
    display: block;
}

.lesson-content {
    padding: 2rem;
}

.xp-button-container {
    text-align: center;
    padding: 2rem;
    background: var(--bg-gray-50);
}

#claimXpBtn {
    padding: 1rem 3rem;
    font-size: 1.125rem;
    font-weight: 600;
    background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
    color: white;
    border: none;
    border-radius: var(--border-radius);
    cursor: not-allowed;
    opacity: 0.5;
    transition: all var(--transition-fast);
}

#claimXpBtn:not(:disabled) {
    cursor: pointer;
    opacity: 1;
}

#claimXpBtn:not(:disabled):hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

#claimXpBtn.completed {
    background: var(--gray-400);
    cursor: not-allowed;
}

.timer-display {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-blue);
    margin-bottom: 1rem;
}

.xp-notification {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) scale(0);
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 2rem 3rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-xl);
    font-size: 2rem;
    font-weight: 700;
    z-index: 10000;
    animation: xpPop 0.6s ease-out forwards;
}

@keyframes xpPop {
    0% {
        transform: translate(-50%, -50%) scale(0);
        opacity: 0;
    }
    50% {
        transform: translate(-50%, -50%) scale(1.2);
        opacity: 1;
    }
    100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
}

.anti-cheat-warning {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 1rem;
    margin: 1rem 0;
    border-radius: var(--border-radius);
}

@media (max-width: 768px) {
    .lesson-header {
        padding: 1.5rem;
    }
    
    .pdf-viewer {
        height: 500px;
    }
}
</style>

<div class="lesson-container">
    <!-- Lesson Header -->
    <div class="lesson-header">
        <h1><?= escape($lesson['title']) ?></h1>
        <p><?= escape($lesson['description']) ?></p>
        <div class="lesson-meta">
            <span class="lesson-badge">
                📚 <?= escape($lesson['subject_name']) ?>
            </span>
            <span class="lesson-badge">
                🎓 <?= escape($lesson['level_name']) ?>
            </span>
            <span class="lesson-badge">
                ⏱️ <?= $lesson['duration_minutes'] ?> <?= __('minutes') ?>
            </span>
            <span class="lesson-badge">
                <?= $lesson['difficulty'] === 'easy' ? '⭐' : ($lesson['difficulty'] === 'medium' ? '⭐⭐' : '⭐⭐⭐') ?>
                <?= __($lesson['difficulty']) ?>
            </span>
            <?php if ($isCompleted): ?>
            <span class="lesson-badge" style="background: rgba(16, 185, 129, 0.9);">
                ✅ <?= __('completed') ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Content Display -->
    <div class="content-wrapper">
        <?php if ($contentType === 'youtube'): ?>
            <!-- YouTube Player with Anti-Cheat -->
            <div class="video-container">
                <div id="player"></div>
            </div>
            
            <?php if (!$isCompleted): ?>
            <div class="anti-cheat-warning">
                ⚠️ <strong><?= __('anti_cheat_notice') ?>:</strong> 
                <?= __('please_watch_full_video') ?>
            </div>
            <?php endif; ?>
            
        <?php elseif ($contentType === 'pdf'): ?>
            <!-- PDF Viewer -->
            <iframe src="<?= escape($pdfUrl) ?>" class="pdf-viewer"></iframe>
            
        <?php elseif ($contentType === 'image'): ?>
            <!-- Image Display -->
            <img src="<?= escape($imageUrl) ?>" alt="<?= escape($lesson['title']) ?>" class="image-viewer">
            
        <?php endif; ?>
        
        <!-- Text Content (if any) -->
        <?php if (!empty($lesson['content'])): ?>
        <div class="lesson-content">
            <?= nl2br(escape($lesson['content'])) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- XP Claim Button -->
    <?php if (!$isCompleted): ?>
    <div class="xp-button-container">
        <div id="timerDisplay" class="timer-display" style="display: none;">
            <span id="timerCountdown">60</span>s
        </div>
        
        <button id="claimXpBtn" disabled>
            🎁 <?= __('claim_xp') ?> (+10 XP)
        </button>
        
        <p style="margin-top: 1rem; color: var(--text-gray); font-size: 0.938rem;">
            <?= __('complete_lesson_instruction') ?>
        </p>
    </div>
    <?php else: ?>
    <div class="xp-button-container">
        <button class="btn btn-primary completed" disabled>
            ✅ <?= __('lesson_completed') ?>
        </button>
        <p style="margin-top: 1rem; color: var(--success-color); font-weight: 500;">
            <?= __('xp_already_awarded') ?>
        </p>
    </div>
    <?php endif; ?>
</div>

<!-- XP Notification -->
<div id="xpNotification" class="xp-notification" style="display: none;">
    +10 XP! 🎉
</div>

<script>
// Global variables
const lessonId = <?= $lessonId ?>;
const userId = <?= $currentUser['id'] ?>;
const contentType = '<?= $contentType ?>';
const verificationToken = '<?= $verificationToken ?>';
const csrfToken = '<?= generateCSRFToken() ?>';
let timeSpent = 0;
let timerInterval = null;
let startTime = Date.now();

// YouTube Player variables
let player = null;
let videoCompleted = false;
let seekingDisabled = false;

<?php if ($contentType === 'youtube' && !$isCompleted): ?>
// Load YouTube IFrame API
var tag = document.createElement('script');
tag.src = "https://www.youtube.com/iframe_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

// Initialize player when API is ready
function onYouTubeIframeAPIReady() {
    player = new YT.Player('player', {
        height: '100%',
        width: '100%',
        videoId: '<?= $videoId ?>',
        playerVars: {
            'playsinline': 1,
            'rel': 0,
            'modestbranding': 1,
            'controls': 1,
            'disablekb': 0
        },
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });
}

function onPlayerReady(event) {
    console.log('YouTube player ready');
    seekingDisabled = true;
}

function onPlayerStateChange(event) {
    // Video ended - enable XP button
    if (event.data === YT.PlayerState.ENDED && !videoCompleted) {
        videoCompleted = true;
        enableXpButton();
    }
    
    // Anti-cheat: Detect seeking
    if (event.data === YT.PlayerState.PLAYING && seekingDisabled) {
        preventSeeking();
    }
}

// Prevent fast-forwarding
function preventSeeking() {
    let currentTime = player.getCurrentTime();
    let lastTime = currentTime;
    
    setInterval(() => {
        currentTime = player.getCurrentTime();
        
        // If user jumped ahead more than 2 seconds, rewind
        if (currentTime - lastTime > 2) {
            player.seekTo(lastTime);
            alert('<?= __('no_skipping_allowed') ?>');
        }
        
        lastTime = currentTime;
    }, 1000);
}

<?php elseif (($contentType === 'pdf' || $contentType === 'image') && !$isCompleted): ?>
// PDF/Image: 60-second timer
document.addEventListener('DOMContentLoaded', function() {
    startContentTimer();
});

function startContentTimer() {
    let countdown = 60;
    const timerDisplay = document.getElementById('timerDisplay');
    const countdownElement = document.getElementById('timerCountdown');
    
    timerDisplay.style.display = 'block';
    
    timerInterval = setInterval(() => {
        countdown--;
        countdownElement.textContent = countdown;
        
        if (countdown <= 0) {
            clearInterval(timerInterval);
            timerDisplay.style.display = 'none';
            enableXpButton();
        }
    }, 1000);
}
<?php endif; ?>

// Enable XP button
function enableXpButton() {
    const btn = document.getElementById('claimXpBtn');
    btn.disabled = false;
    btn.style.cursor = 'pointer';
    btn.style.opacity = '1';
    
    // Add click handler
    btn.addEventListener('click', claimXP);
}

// Claim XP via AJAX
function claimXP() {
    const btn = document.getElementById('claimXpBtn');
    btn.disabled = true;
    btn.textContent = '<?= __('processing') ?>...';
    
    const timeSpent = Math.floor((Date.now() - startTime) / 1000);
    
    // Create verification token for PDF/Image
    let finalToken = verificationToken;
    if (contentType === 'pdf' || contentType === 'image') {
        // Re-calculate token with time spent
        finalToken = '<?= hash('sha256', $currentUser['id'] . $lessonId . '60' . ENCRYPTION_KEY) ?>';
    }
    
    fetch('/ajax/complete_lesson.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            lesson_id: lessonId,
            type: contentType,
            time_spent: timeSpent,
            verification_token: finalToken,
            csrf_token: csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show XP notification
            showXPNotification();
            
            // Update button
            btn.textContent = '✅ ' + '<?= __('completed') ?>';
            btn.classList.add('completed');
            
            // Redirect to dashboard after 2 seconds
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 2000);
        } else {
            alert(data.message || '<?= __('error_occurred') ?>');
            btn.disabled = false;
            btn.textContent = '🎁 <?= __('claim_xp') ?> (+10 XP)';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('<?= __('connection_error') ?>');
        btn.disabled = false;
        btn.textContent = '🎁 <?= __('claim_xp') ?> (+10 XP)';
    });
}

// Show XP notification with animation
function showXPNotification() {
    const notification = document.getElementById('xpNotification');
    notification.style.display = 'block';
    
    setTimeout(() => {
        notification.style.display = 'none';
    }, 1500);
}

// Track time spent
setInterval(() => {
    timeSpent++;
}, 1000);
</script>

<?php
// Include footer
include_once __DIR__ . '/includes/_footer.php';
?>

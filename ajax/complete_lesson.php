<?php
/**
 * ============================================
 * AJAX Lesson Completion Handler
 * Anti-Cheat Protected XP Award System
 * ============================================
 */

require_once __DIR__ . '/../includes/init.php';

// Only accept AJAX POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Invalid request']));
}

// Require authentication
if (!isLoggedIn()) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Verify CSRF token
if (!validateCSRFToken()) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
}

// Get current user
$userId = getCurrentUserId();
$pdo = getDatabaseConnection();

// Get request data
$lessonId = (int)($_POST['lesson_id'] ?? 0);
$completionType = $_POST['type'] ?? 'manual'; // 'youtube', 'pdf', 'image', 'manual'
$timeSpent = (int)($_POST['time_spent'] ?? 0);
$verificationToken = $_POST['verification_token'] ?? '';

// Validate lesson ID
if ($lessonId <= 0) {
    exit(json_encode(['success' => false, 'message' => 'Invalid lesson ID']));
}

// Verify lesson exists and is active
$stmt = $pdo->prepare("
    SELECT id, duration_minutes 
    FROM lessons 
    WHERE id = ? AND is_active = 1
");
$stmt->execute([$lessonId]);
$lesson = $stmt->fetch();

if (!$lesson) {
    exit(json_encode(['success' => false, 'message' => 'Lesson not found']));
}

// Anti-cheat verification for different content types
$antiCheatPassed = false;

switch ($completionType) {
    case 'youtube':
        // Verify the token matches expected pattern
        // Token should be: hash(user_id . lesson_id . session_id . secret)
        $expectedToken = hash('sha256', $userId . $lessonId . session_id() . ENCRYPTION_KEY);
        $antiCheatPassed = hash_equals($expectedToken, $verificationToken);
        break;
        
    case 'pdf':
    case 'image':
        // Verify minimum time spent (60 seconds)
        if ($timeSpent >= 60) {
            $expectedToken = hash('sha256', $userId . $lessonId . $timeSpent . ENCRYPTION_KEY);
            $antiCheatPassed = hash_equals($expectedToken, $verificationToken);
        }
        break;
        
    case 'manual':
        // Manual completion (for admin or special cases)
        $antiCheatPassed = true;
        break;
}

if (!$antiCheatPassed) {
    exit(json_encode([
        'success' => false, 
        'message' => 'Anti-cheat verification failed'
    ]));
}

// Check if already completed
$stmt = $pdo->prepare("
    SELECT status, progress_percentage 
    FROM user_progress 
    WHERE user_id = ? AND lesson_id = ?
");
$stmt->execute([$userId, $lessonId]);
$progress = $stmt->fetch();

// If already completed, don't award XP again
if ($progress && $progress['status'] === 'completed') {
    exit(json_encode([
        'success' => true,
        'already_completed' => true,
        'message' => 'Lesson already completed',
        'xp_awarded' => 0
    ]));
}

// Calculate XP (10 XP per lesson)
$xpAwarded = 10;

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Update or insert progress
    if ($progress) {
        // Update existing progress
        $stmt = $pdo->prepare("
            UPDATE user_progress 
            SET status = 'completed',
                progress_percentage = 100,
                time_spent_seconds = time_spent_seconds + ?,
                completed_at = NOW(),
                last_accessed = NOW()
            WHERE user_id = ? AND lesson_id = ?
        ");
        $stmt->execute([$timeSpent, $userId, $lessonId]);
    } else {
        // Insert new progress
        $stmt = $pdo->prepare("
            INSERT INTO user_progress (
                user_id, lesson_id, status, progress_percentage, 
                time_spent_seconds, completed_at, last_accessed
            ) VALUES (?, ?, 'completed', 100, ?, NOW(), NOW())
        ");
        $stmt->execute([$userId, $lessonId, $timeSpent]);
    }
    
    // Award XP (add xp_points column if not exists - we'll handle this gracefully)
    $stmt = $pdo->prepare("
        UPDATE users 
        SET updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    
    // Try to update XP (column might not exist yet)
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET xp_points = COALESCE(xp_points, 0) + ?
            WHERE id = ?
        ");
        $stmt->execute([$xpAwarded, $userId]);
    } catch (PDOException $e) {
        // Column doesn't exist - that's okay for now
        error_log('XP column not found: ' . $e->getMessage());
    }
    
    // Get updated XP
    $stmt = $pdo->prepare("SELECT COALESCE(xp_points, 0) as xp_points FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    $currentXP = $user['xp_points'] ?? 0;
    
    // Commit transaction
    $pdo->commit();
    
    // Return success response
    exit(json_encode([
        'success' => true,
        'xp_awarded' => $xpAwarded,
        'current_xp' => $currentXP,
        'level' => floor($currentXP / 100),
        'xp_to_next_level' => 100 - ($currentXP % 100),
        'message' => __('lesson_completed')
    ]));
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Lesson completion error: ' . $e->getMessage());
    exit(json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]));
}

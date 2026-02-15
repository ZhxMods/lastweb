<?php
/**
 * ============================================
 * Admin: User Management
 * Live Actions | AJAX Updates | No Refresh
 * ============================================
 */

require_once __DIR__ . '/admin_auth.php';

$pageTitle = __('manage_users');

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
    $userId = (int)($_POST['user_id'] ?? 0);
    
    // Toggle Active Status
    if ($action === 'toggle_active') {
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user) {
            $newStatus = $user['is_active'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            
            exit(json_encode([
                'success' => true,
                'is_active' => $newStatus,
                'message' => $newStatus ? 'User activated' : 'User deactivated'
            ]));
        }
    }
    
    // Update Role
    if ($action === 'update_role') {
        $newRole = $_POST['role'];
        
        if (in_array($newRole, ['student', 'teacher', 'admin'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            
            exit(json_encode([
                'success' => true,
                'message' => 'Role updated successfully'
            ]));
        }
    }
    
    // Update XP
    if ($action === 'update_xp') {
        $newXP = (int)$_POST['xp_points'];
        
        try {
            $stmt = $pdo->prepare("UPDATE users SET xp_points = ? WHERE id = ?");
            $stmt->execute([$newXP, $userId]);
            
            exit(json_encode([
                'success' => true,
                'message' => 'XP updated successfully'
            ]));
        } catch (PDOException $e) {
            exit(json_encode([
                'success' => false,
                'message' => 'XP column may not exist'
            ]));
        }
    }
    
    // Delete User
    if ($action === 'delete_user') {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'super_admin'");
            $stmt->execute([$userId]);
            
            exit(json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]));
        } catch (PDOException $e) {
            exit(json_encode([
                'success' => false,
                'message' => 'Cannot delete user'
            ]));
        }
    }
}

// Fetch all users
try {
    $users = $pdo->query("
        SELECT 
            id,
            email,
            first_name,
            last_name,
            role,
            COALESCE(xp_points, 0) as xp_points,
            is_active,
            email_verified,
            last_login,
            created_at
        FROM users
        ORDER BY created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    // Handle if xp_points column doesn't exist
    $users = $pdo->query("
        SELECT 
            id,
            email,
            first_name,
            last_name,
            role,
            0 as xp_points,
            is_active,
            email_verified,
            last_login,
            created_at
        FROM users
        ORDER BY created_at DESC
    ")->fetchAll();
}

// Include layout
include_once __DIR__ . '/_layout.php';
?>

<style>
.user-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--admin-blue) 0%, var(--admin-blue-light) 100%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1rem;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.role-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 999px;
    font-size: 0.813rem;
    font-weight: 500;
}

.role-student {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
}

.role-teacher {
    background: rgba(168, 85, 247, 0.2);
    color: #a855f7;
}

.role-admin {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
    cursor: pointer;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: var(--admin-success);
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

.xp-display {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    background: rgba(168, 85, 247, 0.2);
    border-radius: 999px;
    color: #a855f7;
    font-weight: 600;
    cursor: pointer;
}

.xp-display:hover {
    background: rgba(168, 85, 247, 0.3);
}
</style>

<div class="content-header">
    <h1><?= __('manage_users') ?></h1>
    <p><?= __('manage_all_users') ?></p>
</div>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <?php
    $totalUsers = count($users);
    $activeUsers = count(array_filter($users, fn($u) => $u['is_active']));
    $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
    $studentCount = count(array_filter($users, fn($u) => $u['role'] === 'student'));
    ?>
    
    <div class="admin-card" style="text-align: center;">
        <div style="font-size: 2.5rem; font-weight: 700; color: var(--admin-blue); margin-bottom: 0.5rem;">
            <?= $totalUsers ?>
        </div>
        <div style="color: var(--admin-text-dim);"><?= __('total_users') ?></div>
    </div>
    
    <div class="admin-card" style="text-align: center;">
        <div style="font-size: 2.5rem; font-weight: 700; color: var(--admin-success); margin-bottom: 0.5rem;">
            <?= $activeUsers ?>
        </div>
        <div style="color: var(--admin-text-dim);"><?= __('active_users') ?></div>
    </div>
    
    <div class="admin-card" style="text-align: center;">
        <div style="font-size: 2.5rem; font-weight: 700; color: var(--admin-danger); margin-bottom: 0.5rem;">
            <?= $adminCount ?>
        </div>
        <div style="color: var(--admin-text-dim);"><?= __('admins') ?></div>
    </div>
    
    <div class="admin-card" style="text-align: center;">
        <div style="font-size: 2.5rem; font-weight: 700; color: var(--admin-blue-light); margin-bottom: 0.5rem;">
            <?= $studentCount ?>
        </div>
        <div style="color: var(--admin-text-dim);"><?= __('students') ?></div>
    </div>
</div>

<!-- Users Table -->
<div class="admin-card">
    <table id="usersTable" class="display" style="width:100%">
        <thead>
            <tr>
                <th><?= __('user') ?></th>
                <th><?= __('email') ?></th>
                <th><?= __('role') ?></th>
                <th>XP</th>
                <th><?= __('status') ?></th>
                <th><?= __('last_login') ?></th>
                <th><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr data-user-id="<?= $user['id'] ?>">
                <td>
                    <div class="user-info">
                        <div class="user-avatar-small">
                            <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--admin-text);">
                                <?= escape($user['first_name'] . ' ' . $user['last_name']) ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td><?= escape($user['email']) ?></td>
                <td>
                    <span class="role-badge role-<?= $user['role'] ?>">
                        <?= ucfirst($user['role']) ?>
                    </span>
                </td>
                <td>
                    <span class="xp-display" onclick="editXP(<?= $user['id'] ?>, <?= $user['xp_points'] ?>)">
                        ⭐ <?= number_format($user['xp_points']) ?>
                    </span>
                </td>
                <td>
                    <label class="toggle-switch">
                        <input type="checkbox" 
                            <?= $user['is_active'] ? 'checked' : '' ?>
                            onchange="toggleActive(<?= $user['id'] ?>, this)">
                        <span class="toggle-slider"></span>
                    </label>
                </td>
                <td>
                    <?= $user['last_login'] ? formatLocalizedDate($user['last_login']) : __('never') ?>
                </td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="admin-btn admin-btn-secondary" onclick="editRole(<?= $user['id'] ?>, '<?= $user['role'] ?>')">
                            🔄 <?= __('role') ?>
                        </button>
                        <?php if ($user['role'] !== 'super_admin'): ?>
                        <button class="admin-btn admin-btn-danger" onclick="deleteUser(<?= $user['id'] ?>)">
                            🗑️
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$additionalJS = <<<'JAVASCRIPT'
<script>
let usersTable;

$(document).ready(function() {
    usersTable = initDataTable('#usersTable');
});

function toggleActive(userId, checkbox) {
    const isActive = checkbox.checked;
    
    adminAjax('', {
        action: 'toggle_active',
        user_id: userId
    }, function(response) {
        showAlert(response.message, 'success');
        
        // Visual feedback
        const row = $(`tr[data-user-id="${userId}"]`);
        if (response.is_active) {
            row.css('opacity', '1');
        } else {
            row.css('opacity', '0.5');
        }
    }, function(error) {
        checkbox.checked = !isActive;
        showAlert('Error updating status', 'danger');
    });
}

function editRole(userId, currentRole) {
    const roles = ['student', 'teacher', 'admin'];
    const roleSelect = roles.map(r => 
        `<option value="${r}" ${r === currentRole ? 'selected' : ''}>${r.charAt(0).toUpperCase() + r.slice(1)}</option>`
    ).join('');
    
    const newRole = prompt(`Select new role:\n1. Student\n2. Teacher\n3. Admin\n\nCurrent: ${currentRole}`);
    
    const roleMap = {'1': 'student', '2': 'teacher', '3': 'admin'};
    const selectedRole = roleMap[newRole] || newRole;
    
    if (selectedRole && roles.includes(selectedRole)) {
        adminAjax('', {
            action: 'update_role',
            user_id: userId,
            role: selectedRole
        }, function(response) {
            showAlert(response.message, 'success');
            location.reload();
        });
    }
}

function editXP(userId, currentXP) {
    const newXP = prompt(`Enter new XP amount:\n\nCurrent XP: ${currentXP}`, currentXP);
    
    if (newXP !== null && !isNaN(newXP)) {
        adminAjax('', {
            action: 'update_xp',
            user_id: userId,
            xp_points: parseInt(newXP)
        }, function(response) {
            showAlert(response.message, 'success');
            location.reload();
        });
    }
}

function deleteUser(userId) {
    confirmAction('Are you sure you want to delete this user?', function() {
        adminAjax('', {
            action: 'delete_user',
            user_id: userId
        }, function(response) {
            showAlert(response.message, 'success');
            location.reload();
        });
    });
}
</script>
JAVASCRIPT;

include_once __DIR__ . '/_layout_end.php';
?>

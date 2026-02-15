<?php
/**
 * ============================================
 * Registration Page
 * Professional White & Blue Design
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/dashboard');
}

// Set page title
$pageTitle = __('register');

// Fetch available levels from database
$pdo = getDatabaseConnection();
$stmt = $pdo->query("
    SELECT id, name_" . getCurrentLanguage() . " as name 
    FROM levels 
    WHERE is_active = 1 
    ORDER BY order_position ASC
");
$levels = $stmt->fetchAll();

// Include header
include_once __DIR__ . '/includes/_header.php';
?>

<style>
.auth-container {
    min-height: calc(100vh - 200px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 0;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.auth-card {
    background: var(--bg-white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-xl);
    max-width: 500px;
    width: 100%;
    padding: 3rem;
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-header h1 {
    color: var(--primary-blue);
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.auth-header p {
    color: var(--text-gray);
    font-size: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-dark);
    font-size: 0.938rem;
}

.form-control {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--gray-300);
    border-radius: var(--border-radius);
    font-size: 1rem;
    font-family: var(--font-sans);
    transition: all var(--transition-fast);
    background-color: var(--bg-white);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-control::placeholder {
    color: var(--gray-400);
}

.form-hint {
    font-size: 0.813rem;
    color: var(--text-gray);
    margin-top: 0.25rem;
}

.btn-submit {
    width: 100%;
    padding: 1rem;
    font-size: 1rem;
    font-weight: 600;
    margin-top: 1rem;
}

.auth-footer {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--gray-200);
    color: var(--text-gray);
}

.auth-footer a {
    color: var(--primary-blue);
    font-weight: 500;
    text-decoration: none;
}

.auth-footer a:hover {
    text-decoration: underline;
}

.form-select {
    width: 100%;
    padding: 0.875rem 1rem;
    border: 2px solid var(--gray-300);
    border-radius: var(--border-radius);
    font-size: 1rem;
    background-color: var(--bg-white);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.form-select:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

@media (max-width: 768px) {
    .auth-card {
        padding: 2rem 1.5rem;
        margin: 0 1rem;
    }
    
    .auth-header h1 {
        font-size: 1.75rem;
    }
}
</style>

<div class="auth-container">
    <div class="container">
        <div class="auth-card">
            <div class="auth-header">
                <h1><?= __('create_your_account') ?></h1>
                <p><?= __('welcome_message') ?></p>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= escape($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= escape($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <form action="<?= url('/auth') ?>" method="POST" id="registerForm" data-validate>
                <?= csrfField() ?>
                <input type="hidden" name="action" value="register">
                
                <!-- Full Name -->
                <div class="form-group">
                    <label for="full_name" class="form-label">
                        <?= __('full_name') ?> <span style="color: var(--danger-color);">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        class="form-control" 
                        placeholder="<?= __('full_name') ?>"
                        required
                        value="<?= isset($_POST['full_name']) ? escape($_POST['full_name']) : '' ?>"
                    >
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label">
                        <?= __('email') ?> <span style="color: var(--danger-color);">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="exemple@email.com"
                        required
                        value="<?= isset($_POST['email']) ? escape($_POST['email']) : '' ?>"
                    >
                </div>
                
                <!-- Grade Level -->
                <div class="form-group">
                    <label for="level_id" class="form-label">
                        <?= __('grade_level') ?> <span style="color: var(--danger-color);">*</span>
                    </label>
                    <select id="level_id" name="level_id" class="form-select" required>
                        <option value=""><?= __('select_level') ?></option>
                        <?php foreach ($levels as $level): ?>
                            <option value="<?= $level['id'] ?>" 
                                <?= (isset($_POST['level_id']) && $_POST['level_id'] == $level['id']) ? 'selected' : '' ?>>
                                <?= escape($level['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <?= __('password') ?> <span style="color: var(--danger-color);">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="••••••••"
                        required
                        minlength="8"
                    >
                    <p class="form-hint"><?= __('password_requirements') ?></p>
                </div>
                
                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="password_confirm" class="form-label">
                        <?= __('confirm_password') ?> <span style="color: var(--danger-color);">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="password_confirm" 
                        name="password_confirm" 
                        class="form-control" 
                        placeholder="••••••••"
                        required
                        minlength="8"
                    >
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-submit">
                    <?= __('register') ?>
                </button>
            </form>
            
            <div class="auth-footer">
                <?= __('already_registered') ?>
                <a href="<?= url('/login') ?>"><?= __('login_here') ?></a>
            </div>
        </div>
    </div>
</div>

<style>
.alert {
    padding: 1rem;
    border-radius: var(--border-radius);
    margin-bottom: 1.5rem;
    font-size: 0.938rem;
}

.alert-danger {
    background-color: #fee;
    color: var(--danger-color);
    border: 1px solid var(--danger-color);
}

.alert-success {
    background-color: #efe;
    color: var(--success-color);
    border: 1px solid var(--success-color);
}
</style>

<script>
// Client-side validation
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    
    if (password !== passwordConfirm) {
        e.preventDefault();
        alert('<?= __('password_mismatch') ?>');
        return false;
    }
    
    if (password.length < 8) {
        e.preventDefault();
        alert('<?= __('password_too_short') ?>');
        return false;
    }
});
</script>

<?php
// Include footer
include_once __DIR__ . '/includes/_footer.php';
?>

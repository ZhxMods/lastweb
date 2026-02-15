<?php
/**
 * ============================================
 * Login Page
 * Professional White & Blue Design with Remember Me
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/dashboard');
}

// Set page title
$pageTitle = __('login');

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
    max-width: 450px;
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

.form-check {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.form-check input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: var(--primary-blue);
}

.form-check label {
    font-size: 0.938rem;
    color: var(--text-gray);
    cursor: pointer;
}

.forgot-password {
    text-align: right;
    margin-top: -0.5rem;
    margin-bottom: 1rem;
}

.forgot-password a {
    font-size: 0.875rem;
    color: var(--primary-blue);
    text-decoration: none;
}

.forgot-password a:hover {
    text-decoration: underline;
}

.btn-submit {
    width: 100%;
    padding: 1rem;
    font-size: 1rem;
    font-weight: 600;
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

.login-benefits {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 2rem;
}

.login-benefits h3 {
    color: var(--primary-blue);
    font-size: 1.125rem;
    margin-bottom: 1rem;
}

.login-benefits ul {
    list-style: none;
    padding: 0;
}

.login-benefits li {
    padding: 0.5rem 0;
    color: var(--text-gray);
    font-size: 0.938rem;
}

.login-benefits li:before {
    content: "✓ ";
    color: var(--success-color);
    font-weight: bold;
    margin-right: 0.5rem;
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
                <h1><?= __('login') ?></h1>
                <p><?= __('login_to_continue') ?></p>
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
            
            <form action="<?= url('/auth') ?>" method="POST" id="loginForm">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="login">
                
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
                        autofocus
                        value="<?= isset($_POST['email']) ? escape($_POST['email']) : '' ?>"
                    >
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
                    >
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="form-check">
                    <input 
                        type="checkbox" 
                        id="remember_me" 
                        name="remember_me" 
                        value="1"
                    >
                    <label for="remember_me"><?= __('remember_me') ?></label>
                </div>
                
                <div class="forgot-password">
                    <a href="<?= url('/forgot-password') ?>"><?= __('forgot_password') ?></a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary btn-submit">
                    <?= __('login') ?>
                </button>
            </form>
            
            <div class="auth-footer">
                <?= __('not_registered') ?>
                <a href="<?= url('/register') ?>"><?= __('register_here') ?></a>
            </div>
        </div>
        
        <!-- Login Benefits (Optional) -->
        <div style="max-width: 450px; margin: 2rem auto 0;">
            <div class="login-benefits">
                <h3><?= __('why_choose_us') ?></h3>
                <ul>
                    <li><?= __('feature_multilingual_desc') ?></li>
                    <li><?= __('feature_tracking_desc') ?></li>
                    <li><?= __('feature_security_desc') ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once __DIR__ . '/includes/_footer.php';
?>

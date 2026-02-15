<?php
/**
 * ============================================
 * 404 Error Page
 * ============================================
 */

require_once __DIR__ . '/../includes/init.php';

$pageTitle = '404 - Page Not Found';
http_response_code(404);

include_once __DIR__ . '/../includes/_header.php';
?>

<style>
.error-container {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    text-align: center;
}

.error-content {
    max-width: 600px;
}

.error-code {
    font-size: 8rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-light) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    margin-bottom: 1rem;
}

.error-title {
    font-size: 2rem;
    color: var(--text-dark);
    margin-bottom: 1rem;
}

.error-text {
    font-size: 1.125rem;
    color: var(--text-gray);
    margin-bottom: 2rem;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}
</style>

<div class="error-container">
    <div class="error-content">
        <div class="error-code">404</div>
        <h1 class="error-title"><?= __('page_not_found') ?></h1>
        <p class="error-text">
            <?= __('page_not_found_message') ?>
        </p>
        <div class="error-actions">
            <a href="<?= APP_URL ?>/index.php" class="btn btn-primary">
                <?= __('go_home') ?>
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <?= __('go_back') ?>
            </a>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/_footer.php'; ?>

<?php
$pageTitle = 'Reset Password';
require_once __DIR__ . '/../includes/header.php';

if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

$token = $_GET['token'] ?? '';
$resetData = valid_password_reset_token($token);

if (!$resetData) {
    set_flash('error', 'Invalid or expired password reset token.');
    redirect('pages/forgot_password.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    
    if ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_HASH_COST]);
        
        db_connect()->begin_transaction();
        
        try {
            execute_statement('UPDATE users SET password_hash = ? WHERE id = ?', 'si', [$hash, $resetData['user_id']]);
            mark_password_reset_used((int)$resetData['id']);
            db_connect()->commit();
            
            set_flash('success', 'Your password has been successfully reset. Please login.');
            redirect('pages/login.php');
        } catch (Exception $e) {
            db_connect()->rollback();
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card fade-in">
        <div class="text-center mb-4">
            <i class="bi bi-shield-lock text-primary fs-1"></i>
            <h3 class="mt-2 fw-bold">Reset Password</h3>
            <p class="text-muted">Enter your new password below</p>
        </div>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?= h($errors['general']) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required autofocus>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= h($errors['password']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password_confirm" class="form-label">Confirm New Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" required>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <div class="invalid-feedback"><?= h($errors['password_confirm']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Update Password</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

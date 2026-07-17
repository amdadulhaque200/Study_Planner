<?php
$pageTitle = 'Forgot Password';
require_once __DIR__ . '/../includes/header.php';

if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $errors['email'] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    } else {
        $user = fetch_one('SELECT id FROM users WHERE email = ? LIMIT 1', 's', [$email]);
        
        if ($user) {
            $token = create_password_reset_token((int)$user['id']);
            // In a real application, you would send an email here.
            // For this project, we'll just display a link for demonstration purposes.
            $resetLink = base_url('pages/reset_password.php?token=' . $token);
            $successMessage = 'A password reset link has been generated. <br><a href="'.h($resetLink).'" class="btn btn-sm btn-outline-primary mt-2">Simulate Email Link Click</a>';
        } else {
            // To prevent email enumeration, we still show a generic success message
            $successMessage = 'If that email is in our database, we have sent a reset link to it.';
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card fade-in">
        <div class="text-center mb-4">
            <i class="bi bi-key text-primary fs-1"></i>
            <h3 class="mt-2 fw-bold">Forgot Password</h3>
            <p class="text-muted">Enter your email to reset your password</p>
        </div>
        
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= $successMessage ?></div>
        <?php else: ?>
            <form method="post" action="">
                <?= csrf_field() ?>
                
                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= h($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-grid gap-2 mb-3">
                    <button type="submit" class="btn btn-primary btn-lg">Reset Password</button>
                </div>
                
                <div class="text-center">
                    <a href="<?= base_url('pages/login.php') ?>" class="text-decoration-none text-muted"><i class="bi bi-arrow-left me-1"></i>Back to Login</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

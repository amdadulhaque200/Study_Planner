<?php
$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';

if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errors['general'] = 'Please enter both username and password.';
    } else {
        $user = fetch_one('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1', 'ss', [$username, $username]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user);
            update_last_login((int)$user['id']);
            set_flash('success', 'Welcome back, ' . h($user['username']) . '!');
            redirect('pages/dashboard.php');
        } else {
            $errors['general'] = 'Invalid username or password.';
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card fade-in">
        <div class="text-center mb-4">
            <i class="bi bi-book-half text-primary fs-1"></i>
            <h3 class="mt-2 fw-bold">Welcome Back</h3>
            <p class="text-muted">Login to access your study planner</p>
        </div>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?= h($errors['general']) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label for="username" class="form-label">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control" id="username" name="username" value="<?= h($_POST['username'] ?? '') ?>" required autofocus>
                </div>
            </div>
            
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label mb-0">Password</label>
                    <a href="<?= base_url('pages/forgot_password.php') ?>" class="text-decoration-none small">Forgot Password?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Login</button>
            </div>
        </form>
        
        <div class="text-center mt-4">
            <p class="text-muted">Don't have an account? <a href="<?= base_url('pages/register.php') ?>" class="text-primary text-decoration-none fw-medium">Register here</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

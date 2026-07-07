<?php
$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';

if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($username)) {
        $errors['username'] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username must be at least 3 characters.';
    } elseif (username_exists($username)) {
        $errors['username'] = 'Username is already taken.';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    } elseif (email_exists($email)) {
        $errors['email'] = 'Email is already registered.';
    }
    
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
        
        $success = execute_statement(
            'INSERT INTO users (username, email, full_name, password_hash) VALUES (?, ?, ?, ?)',
            'ssss',
            [$username, $email, $fullName, $hash]
        );
        
        if ($success) {
            set_flash('success', 'Registration successful! Please log in.');
            redirect('pages/login.php');
        } else {
            $errors['general'] = 'Registration failed due to a database error.';
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card fade-in">
        <div class="text-center mb-4">
            <i class="bi bi-book-half text-primary fs-1"></i>
            <h3 class="mt-2 fw-bold">Create an Account</h3>
            <p class="text-muted">Start organizing your studies today</p>
        </div>
        
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger"><?= h($errors['general']) ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                    <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>" id="username" name="username" value="<?= h($_POST['username'] ?? '') ?>" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="invalid-feedback"><?= h($errors['username']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= h($_POST['email'] ?? '') ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?= h($errors['email']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person-vcard"></i></span>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= h($_POST['full_name'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback"><?= h($errors['password']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="password_confirm" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" required>
                    <?php if (isset($errors['password_confirm'])): ?>
                        <div class="invalid-feedback"><?= h($errors['password_confirm']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">Register</button>
            </div>
        </form>
        
        <div class="text-center mt-4">
            <p class="text-muted">Already have an account? <a href="<?= base_url('pages/login.php') ?>" class="text-primary text-decoration-none fw-medium">Login here</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

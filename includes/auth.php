<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $cachedUser = null;
    static $cachedUserId = null;

    if ($cachedUser !== null && $cachedUserId === (int) $_SESSION['user_id']) {
        return $cachedUser;
    }

    $user = fetch_one('SELECT id, username, email, role, created_at, last_login FROM users WHERE id = ?', 'i', [(int) $_SESSION['user_id']]);

    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
        return null;
    }

    $cachedUser = $user;
    $cachedUserId = (int) $user['id'];

    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['role'] = (string) $user['role'];
}

function logout_user(): void
{
    unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role']);
    session_regenerate_id(true);
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Please log in to continue.');
        redirect('pages/login.php');
    }
}

function require_admin(): void
{
    $user = current_user();

    if (!$user || ($user['role'] ?? '') !== 'admin') {
        set_flash('error', 'Access denied.');
        redirect('pages/dashboard.php');
    }
}

function username_exists(string $username): bool
{
    return fetch_one('SELECT id FROM users WHERE username = ? LIMIT 1', 's', [$username]) !== null;
}

function email_exists(string $email): bool
{
    return fetch_one('SELECT id FROM users WHERE email = ? LIMIT 1', 's', [$email]) !== null;
}

function create_password_reset_token(int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);

    execute_statement(
        'INSERT INTO password_resets (user_id, token, expires_at, used) VALUES (?, ?, ?, 0)',
        'iss',
        [$userId, $token, $expiresAt]
    );

    return $token;
}

function valid_password_reset_token(string $token): ?array
{
    return fetch_one(
        'SELECT pr.id, pr.user_id, pr.token, pr.expires_at, pr.used, u.username, u.email
         FROM password_resets pr
         INNER JOIN users u ON u.id = pr.user_id
         WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()
         LIMIT 1',
        's',
        [$token]
    );
}

function mark_password_reset_used(int $resetId): bool
{
    return execute_statement('UPDATE password_resets SET used = 1 WHERE id = ?', 'i', [$resetId]);
}

function update_last_login(int $userId): bool
{
    return execute_statement('UPDATE users SET last_login = NOW() WHERE id = ?', 'i', [$userId]);
}

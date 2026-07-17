<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const APP_NAME = 'Study Planner';
const BASE_URL = '/sp-4444/Study_Planner';
const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'study_planner';
const PASSWORD_HASH_COST = 12;

function db_connect(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($connection->connect_error) {
        die('Database connection failed.');
    }

    $connection->set_charset('utf8mb4');

    return $connection;
}

function base_url(string $path = ''): string
{
    $path = ltrim($path, '/');

    if ($path === '') {
        return BASE_URL . '/';
    }

    return BASE_URL . '/' . $path;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return is_array($flash) ? $flash : null;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

function password_policy_valid(string $password): bool
{
    return (bool) preg_match('/^(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

function fetch_one(string $sql, string $types = '', array $params = []): ?array
{
    $statement = db_connect()->prepare($sql);

    if (!$statement) {
        return null;
    }

    if ($types !== '') {
        $statement->bind_param($types, ...$params);
    }

    $statement->execute();
    $result = $statement->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $statement->close();

    return $row ?: null;
}

function fetch_all(string $sql, string $types = '', array $params = []): array
{
    $statement = db_connect()->prepare($sql);

    if (!$statement) {
        return [];
    }

    if ($types !== '') {
        $statement->bind_param($types, ...$params);
    }

    $statement->execute();
    $result = $statement->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $statement->close();

    return $rows;
}

function execute_statement(string $sql, string $types = '', array $params = []): bool
{
    $statement = db_connect()->prepare($sql);

    if (!$statement) {
        return false;
    }

    if ($types !== '') {
        $statement->bind_param($types, ...$params);
    }

    $success = $statement->execute();
    $statement->close();

    return $success;
}

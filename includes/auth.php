<?php
// includes/auth.php

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Регистрация ─────────────────────────────────────────────────
function registerUser(array $data): array {
    $db = getDB();

    $username  = trim($data['username']);
    $email     = trim($data['email']);
    $password  = $data['password'];
    $password2 = $data['password2'];
    $full_name = trim($data['full_name']);

    if (strlen($username) < 3)
        return ['success' => false, 'error' => 'Потребителското ime трябва да е поне 3 символа.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return ['success' => false, 'error' => 'Невалиден имейл адрес.'];
    if (strlen($password) < 6)
        return ['success' => false, 'error' => 'Паролата трябва да е поне 6 символа.'];
    if ($password !== $password2)
        return ['success' => false, 'error' => 'Паролите не съвпадат.'];
    if (empty($full_name))
        return ['success' => false, 'error' => 'Моля въведи пълното си ime.'];

    // Проверка за съществуващ потребител
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0)
        return ['success' => false, 'error' => 'Потребителското ime или имейлът вече са заети.'];

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare(
        "INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param('ssss', $username, $email, $hash, $full_name);
    $stmt->execute();

    return ['success' => true];
}

// ── Вход ────────────────────────────────────────────────────────
function loginUser(string $email, string $password): array {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user['password']))
        return ['success' => false, 'error' => 'Грешен имейл или парола.'];

    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];

    return ['success' => true, 'role' => $user['role']];
}

// ── Helpers ─────────────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/pages/login.php');
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function logoutUser(): void {
    session_destroy();
}
?>
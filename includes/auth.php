<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool
{
    startSession();
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    $user = getCurrentUser();
    if (!$user || $user['role'] !== 'admin') {
        flash('error', 'Admin access required.');
        header('Location: ../dashboard.php');
        exit;
    }
}

function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    $stmt = getDB()->prepare('SELECT id, fullname, email, phone, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function isAdmin(): bool
{
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

function loginUser(string $email, string $password): array
{
    $stmt = getDB()->prepare('SELECT id, fullname, email, password, role FROM users WHERE email = ?');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['fullname'];

    return ['success' => true, 'message' => 'Welcome back, ' . $user['fullname'] . '!'];
}

function registerUser(string $fullname, string $email, string $phone, string $password): array
{
    if (strlen($fullname) < 2) {
        return ['success' => false, 'message' => 'Please enter your full name.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = getDB()->prepare(
            'INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, \'customer\')'
        );
        $stmt->execute([trim($fullname), trim($email), trim($phone), $hash]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['success' => false, 'message' => 'Email is already registered.'];
        }
        throw $e;
    }

    return ['success' => true, 'message' => 'Account created! You can now log in.'];
}

function updateProfile(int $userId, string $fullname, string $email, string $phone): array
{
    if (strlen($fullname) < 2) {
        return ['success' => false, 'message' => 'Please enter your full name.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    try {
        $stmt = getDB()->prepare('UPDATE users SET fullname = ?, email = ?, phone = ? WHERE id = ?');
        $stmt->execute([trim($fullname), trim($email), trim($phone), $userId]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['success' => false, 'message' => 'Email is already in use.'];
        }
        throw $e;
    }

    $_SESSION['user_name'] = trim($fullname);
    return ['success' => true, 'message' => 'Profile updated successfully.'];
}

function changePassword(int $userId, string $current, string $new): array
{
    $stmt = getDB()->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }

    if (strlen($new) < 6) {
        return ['success' => false, 'message' => 'New password must be at least 6 characters.'];
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = getDB()->prepare('UPDATE users SET password = ? WHERE id = ?');
    $stmt->execute([$hash, $userId]);

    return ['success' => true, 'message' => 'Password changed successfully.'];
}

function createPasswordReset(string $email): array
{
    $stmt = getDB()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([trim($email)]);
    if (!$stmt->fetch()) {
        return ['success' => true, 'message' => 'If that email exists, a reset link has been generated.', 'token' => null];
    }

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);

    getDB()->prepare('DELETE FROM password_resets WHERE email = ?')->execute([trim($email)]);

    $stmt = getDB()->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([trim($email), $token, $expires]);

    return [
        'success' => true,
        'message' => 'Password reset link generated.',
        'token' => $token,
    ];
}

function resetPasswordWithToken(string $token, string $password): array
{
    $stmt = getDB()->prepare('SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()');
    $stmt->execute([$token]);
    $reset = $stmt->fetch();

    if (!$reset) {
        return ['success' => false, 'message' => 'Invalid or expired reset link.'];
    }

    if (strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    getDB()->prepare('UPDATE users SET password = ? WHERE email = ?')->execute([$hash, $reset['email']]);
    getDB()->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$reset['email']]);

    return ['success' => true, 'message' => 'Password reset successfully. You can now log in.'];
}

function logoutUser(): void
{
    startSession();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

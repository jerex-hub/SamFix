<?php
// Admin session handling (BR-03)
require_once __DIR__ . '/config.php';

const LOGIN_MAX_ATTEMPTS = 5;     // failed tries allowed
const LOGIN_LOCK_SECONDS = 300;   // lockout length (5 minutes)

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// Call at the very top of every admin page except login.php
function require_admin(): void
{
    start_secure_session();

    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }

    // Stop the browser from showing a cached admin page after logout (TC-15)
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// Seconds left on a login lockout (0 = not locked). Use it in login.php to show a message.
function login_locked_seconds(): int
{
    start_secure_session();
    $until = (int) ($_SESSION['login_locked_until'] ?? 0);
    return max(0, $until - time());
}

// Returns true and starts the admin session if the credentials are correct
function login_admin(PDO $pdo, string $username, string $password): bool
{
    start_secure_session();

    if (login_locked_seconds() > 0) {
        return false;   // too many failed attempts, still locked
    }

    $stmt = $pdo->prepare('SELECT admin_id, username, password_hash FROM administrators WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        $_SESSION['login_failed'] = (int) ($_SESSION['login_failed'] ?? 0) + 1;
        if ($_SESSION['login_failed'] >= LOGIN_MAX_ATTEMPTS) {
            $_SESSION['login_locked_until'] = time() + LOGIN_LOCK_SECONDS;
            $_SESSION['login_failed'] = 0;
        }
        return false;
    }

    session_regenerate_id(true);   // prevents session fixation
    unset($_SESSION['login_failed'], $_SESSION['login_locked_until']);
    $_SESSION['admin_id']       = (int) $admin['admin_id'];
    $_SESSION['admin_username'] = $admin['username'];
    return true;
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
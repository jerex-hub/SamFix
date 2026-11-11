<?php
// Admin session handling (BR-03)
require_once __DIR__ . '/config.php';

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

// Returns true and starts the admin session if the credentials are correct
function login_admin(PDO $pdo, string $username, string $password): bool
{
    $stmt = $pdo->prepare('SELECT admin_id, username, password_hash FROM administrators WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);   // prevents session fixation
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
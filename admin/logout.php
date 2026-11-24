<?php
// admin/logout.php: signs the admin out only after they re-enter their own password.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

start_secure_session();

// Not signed in: nothing to log out of
if (empty($_SESSION['admin_logged_in']) && empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Where to send the admin back to if the password is wrong (whitelist, never trust user input)
$allowed = ['requests.php', 'services.php'];
$back = in_array($_POST['back'] ?? '', $allowed, true) ? $_POST['back'] : 'requests.php';

// Opening this page directly (GET) does not log anyone out
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: requests.php');
    exit;
}

// Security token
if (!hash_equals((string) ($_SESSION['csrf'] ?? ''), (string) ($_POST['csrf'] ?? ''))) {
    $_SESSION['toast'] = 'Your session expired. Reload the page and try again.';
    header('Location: ' . $back);
    exit;
}

// Check the password of the admin who is signed in
$password = (string) ($_POST['password'] ?? '');
$adminId  = (int) ($_SESSION['admin_id'] ?? 0);
$ok = false;

if ($adminId > 0 && $password !== '') {
    try {
        $st = $pdo->prepare('SELECT password_hash FROM administrators WHERE admin_id = ?');
        $st->execute([$adminId]);
        $hash = $st->fetchColumn();
        $ok = is_string($hash) && password_verify($password, $hash);
    } catch (PDOException $e) {
        error_log('admin/logout.php: ' . $e->getMessage());
    }
}

if ($ok) {
    logout_admin();
    header('Location: login.php');
    exit;
}

// Wrong password: stay signed in. After 5 wrong tries in a row, sign out for safety.
$_SESSION['logout_failed'] = (int) ($_SESSION['logout_failed'] ?? 0) + 1;
if ($_SESSION['logout_failed'] >= 5) {
    logout_admin();
    header('Location: login.php');
    exit;
}

$_SESSION['toast'] = 'Incorrect password. You are still signed in.';
header('Location: ' . $back);
exit;
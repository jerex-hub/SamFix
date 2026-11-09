<?php
// admin/login.php (FR-08)
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

start_secure_session();

// Already logged in: go straight to the requests page
if (!empty($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/requests.php');
    exit;
}

$pageTitle = 'Admin Login';
$isAdmin   = true;
$error     = '';
$username  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired, please try again.';
    } else {
        try {
            if (login_admin($pdo, $username, $password)) {
                header('Location: ' . BASE_URL . '/admin/requests.php');
                exit;
            }
            // Same message whether the username or the password was wrong
            $error = 'Invalid username or password';
        } catch (PDOException $e) {
            error_log('login failed: ' . $e->getMessage());
            $error = 'Sorry, something went wrong. Please try again.';
        }
    }
}

$inputClass = 'w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-3 text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500';

require __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto pt-6">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8">
        <h1 class="text-2xl font-bold tracking-tight">Admin login</h1>
        <p class="mt-1 text-slate-400 text-sm">Sign in to manage repair requests and services.</p>

        <?php if ($error): ?>
            <p class="mt-5 rounded-xl border border-red-900 bg-red-950/60 p-3 text-sm text-red-300"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/admin/login.php" class="mt-6 space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div>
                <label for="username" class="block text-sm font-medium text-slate-300 mb-1.5">Username</label>
                <input type="text" id="username" name="username" required maxlength="50" autocomplete="username"
                       value="<?= e($username) ?>" class="<?= $inputClass ?>">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password"
                       class="<?= $inputClass ?>">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-3 rounded-xl font-semibold hover:bg-blue-400">
                Login
            </button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
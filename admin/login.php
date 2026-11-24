<?php
// admin/login.php: signs in with a username and password stored in the administrators table.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

start_secure_session();

// Already signed in: go straight to the dashboard
if (!empty($_SESSION['admin_logged_in']) || !empty($_SESSION['admin_id'])) {
    header('Location: requests.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $wait = login_locked_seconds();
    if ($wait > 0) {
        $error = 'Too many failed attempts. Try again in ' . ceil($wait / 60) . ' minute(s).';
    } elseif ($username === '' || $password === '') {
        $error = 'Enter your username and password.';
    } else {
        try {
            if (login_admin($pdo, $username, $password)) {
                $_SESSION['admin_logged_in'] = true;   // the dashboard checks this flag
                header('Location: requests.php');
                exit;
            }
            $error = login_locked_seconds() > 0
                ? 'Too many failed attempts. Try again in ' . ceil(login_locked_seconds() / 60) . ' minute(s).'
                : 'Invalid username or password.';
        } catch (PDOException $e) {
            error_log('admin/login.php: ' . $e->getMessage());
            $error = 'Could not sign in right now. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= e(APP_NAME) ?>.</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-[#F9FAFB] flex items-center justify-center min-h-screen p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xs p-8 border border-gray-200">
        <div class="flex items-center gap-2.5 mb-6">
            <div class="w-8 h-8 bg-[#2563EB] rounded-lg flex items-center justify-center text-white">
                <i data-lucide="wrench" class="w-4 h-4"></i>
            </div>
            <span class="text-xl font-bold tracking-tight text-gray-900"><?= e(APP_NAME) ?><span class="text-[#2563EB]">.</span></span>
        </div>
 
        <h2 class="text-xl font-bold text-gray-900">Sign in to Admin workspace</h2>
        <p class="text-xs text-gray-500 mt-1 mb-6"><?= e(defined('SHOP_NAME') ? SHOP_NAME : APP_NAME) ?> · Administration</p>

        <?php if ($error !== ''): ?>
            <div role="alert" class="bg-red-50 text-red-700 p-3 rounded-lg text-xs mb-4 border border-red-200">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4" autocomplete="off" data-validate>
            <div>
                <label for="username" class="block text-xs font-medium text-gray-700 mb-1.5">Username</label>
                <input type="text" id="username" name="username" value="<?= e($_POST['username'] ?? '') ?>" required maxlength="50" autofocus
                       class="w-full px-3.5 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#2563EB]">
            </div>
            <div>
                <label for="password" class="block text-xs font-medium text-gray-700 mb-1.5">Password</label>
                <input type="password" id="password" name="password" required 
                       class="w-full px-3.5 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#2563EB]">
            </div>
            <button type="submit" class="w-full bg-[#2563EB] hover:bg-blue-700 text-white text-sm font-medium py-2.5 rounded-lg transition cursor-pointer">
                Sign in
            </button>
        </form>
    </div>
    <script src="../assets/js/app.js" defer></script>
    <script>lucide.createIcons();</script>
</body>
</html>
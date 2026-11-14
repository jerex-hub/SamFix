<?php
session_start();
$admin_email = "alex@fixflow.com";
$admin_pass = "admin123";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email === $admin_email && $password === $admin_pass) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_email'] = $email;
        header("Location: requests.php");
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - fixflow.</title>
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
            <span class="text-xl font-bold tracking-tight text-gray-900">fixflow<span class="text-[#2563EB]">.</span></span>
        </div>

        <h2 class="text-xl font-bold text-gray-900">Sign in to Admin workspace</h2>
        <p class="text-xs text-gray-500 mt-1 mb-6">Downtown Service Center · Administration</p>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-700 p-3 rounded-lg text-xs mb-4 border border-red-200">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Email address</label>
                <input type="email" name="email" value="alex@fixflow.com" required class="w-full px-3.5 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#2563EB]">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1.5">Password</label>
                <input type="password" name="password" value="admin123" required class="w-full px-3.5 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-[#2563EB]">
            </div>
            <button type="submit" class="w-full bg-[#2563EB] hover:bg-blue-700 text-white text-sm font-medium py-2.5 rounded-lg transition cursor-pointer">
                Sign in
            </button>
        </form>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
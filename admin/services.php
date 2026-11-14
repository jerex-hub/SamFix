<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$services = [
    ["name" => "Screen Replacement", "price" => "$120", "duration" => "45 mins"],
    ["name" => "Battery Replacement", "price" => "$80", "duration" => "30 mins"],
    ["name" => "Water Damage Diagnostic", "price" => "$50", "duration" => "1 day"],
    ["name" => "Charging Port Repair", "price" => "$90", "duration" => "1 hour"]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Services Management - FixFlow</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    <aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between">
        <div class="p-6">
            <h1 class="text-xl font-bold text-blue-600 mb-8">fixflow.</h1>
            <nav class="space-y-1">
                <a href="requests.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50">Admin dashboard</a>
                <a href="services.php" class="flex items-center px-4 py-2 text-sm font-medium rounded-lg bg-blue-50 text-blue-700">Team & Services</a>
            </nav>
        </div>
        <div class="p-6 border-t border-gray-200">
            <a href="logout.php" class="text-sm font-medium text-red-600 hover:text-red-700">Sign out</a>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Manage Services</h2>
        <div class="bg-white rounded-xl border border-gray-200 shadow-xs p-6">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3">Service Name</th>
                        <th class="px-6 py-3">Standard Price</th>
                        <th class="px-6 py-3">Estimated Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm">
                    <?php foreach ($services as $srv): ?>
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-900"><?= $srv['name'] ?></td>
                        <td class="px-6 py-4 text-gray-600"><?= $srv['price'] ?></td>
                        <td class="px-6 py-4 text-gray-600"><?= $srv['duration'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
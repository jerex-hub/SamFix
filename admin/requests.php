<?php
// admin/requests.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

start_secure_session();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Ensure team_members table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS team_members (
        member_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        role VARCHAR(50) NOT NULL DEFAULT 'Technician',
        status VARCHAR(20) NOT NULL DEFAULT 'Active',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch (PDOException $e) {
    // Table exists
}

// Handle Form Actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_member') {
        $name = trim($_POST['member_name'] ?? '');
        $email = trim($_POST['member_email'] ?? '');
        $role = trim($_POST['member_role'] ?? 'Technician');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please provide a valid name and email address.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO team_members (name, email, role, status) VALUES (?, ?, ?, 'Active')");
                $stmt->execute([$name, $email, $role]);
                $success = "Team member added successfully!";
            } catch (PDOException $e) {
                $error = "Failed to add team member (Email might already exist).";
            }
        }
    }

    if ($action === 'add_repair') {
        $customerName = trim($_POST['customer_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $brand = trim($_POST['device_brand'] ?? '');
        $model = trim($_POST['device_model'] ?? '');
        $issue = trim($_POST['issue'] ?? '');
        $serviceId = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;

        if ($customerName === '' || $phone === '' || $model === '') {
            $error = "Please fill in all required customer and device fields.";
        } else {
            try {
                $pdo->beginTransaction();
                $tempCode = 'TMP-' . bin2hex(random_bytes(6));
                
                $stmt = $pdo->prepare("INSERT INTO repair_requests (request_code, service_id, customer_name, email, phone, device_brand, device_model, issue, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
                $stmt->execute([$tempCode, $serviceId, $customerName, $email, $phone, $brand, $model, $issue]);
                
                $insertId = (int)$pdo->lastInsertId();
                $requestCode = generate_request_code($insertId);

                $upd = $pdo->prepare("UPDATE repair_requests SET request_code = ? WHERE request_id = ?");
                $upd->execute([$requestCode, $insertId]);

                $pdo->commit();
                $success = "Repair job created successfully with Ticket ID: {$requestCode}";
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = "Failed to create repair job: " . $e->getMessage();
            }
        }
    }
}

// Fetch Filters & Search
$search = trim($_GET['search'] ?? '');
$filterTab = $_GET['tab'] ?? 'all';

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(r.request_code LIKE ? OR r.customer_name LIKE ? OR r.device_model LIKE ? OR r.phone LIKE ?)";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like);
}

if ($filterTab === 'active') {
    $whereClauses[] = "r.status IN ('Pending', 'In Progress', 'Awaiting parts')";
} elseif ($filterTab === 'pickup') {
    $whereClauses[] = "r.status = 'Ready for pickup'";
} elseif ($filterTab === 'completed') {
    $whereClauses[] = "r.status = 'Completed'";
}

$whereSql = count($whereClauses) > 0 ? "WHERE " . implode(" AND ", $whereClauses) : "";

try {
    $repairQuery = "SELECT r.*, s.service_name, s.base_price FROM repair_requests r LEFT JOIN services s ON r.service_id = s.service_id {$whereSql} ORDER BY r.created_at DESC";
    $stmt = $pdo->prepare($repairQuery);
    $stmt->execute($params);
    $repair_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $services = $pdo->query("SELECT * FROM services ORDER BY service_name")->fetchAll(PDO::FETCH_ASSOC);
    $team_members = $pdo->query("SELECT * FROM team_members ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

    $totalRepairs = (int)$pdo->query("SELECT COUNT(*) FROM repair_requests")->fetchColumn();
    $activeRepairs = (int)$pdo->query("SELECT COUNT(*) FROM repair_requests WHERE status IN ('Pending', 'In Progress', 'Awaiting parts')")->fetchColumn();
    $totalValue = (float)$pdo->query("SELECT SUM(COALESCE(s.base_price, 0)) FROM repair_requests r LEFT JOIN services s ON r.service_id = s.service_id")->fetchColumn();
    $totalTeamCount = count($team_members);

} catch (PDOException $e) {
    $repair_jobs = [];
    $team_members = [];
    $services = [];
    $totalRepairs = $activeRepairs = $totalValue = $totalTeamCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fixflow</title>
    <!-- Tailwind Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .dot-blue::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #2563eb; margin-right: 6px; }
        .dot-amber::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #d97706; margin-right: 6px; }
        .dot-emerald::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #059669; margin-right: 6px; }
        .dot-purple::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #7c3aed; margin-right: 6px; }
        .dot-gray::before { content: ''; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #4b5563; margin-right: 6px; }
    </style>
</head>
<body class="bg-[#F9FAFB] dark:bg-slate-950 text-[#101828] dark:text-slate-100 flex h-screen overflow-hidden transition-colors duration-200">

    <!-- Left Sidebar -->
    <aside class="w-[260px] bg-white dark:bg-slate-900 border-r border-gray-200 dark:border-slate-800 flex flex-col justify-between shrink-0 overflow-y-auto transition-colors duration-200">
        <div class="p-5">
            <!-- Brand Logo -->
            <div class="flex items-center gap-2.5 px-1 mb-6">
                <div class="w-8 h-8 bg-[#2563EB] rounded-lg flex items-center justify-center text-white shadow-xs">
                    <i data-lucide="wrench" class="w-4 h-4"></i>
                </div>
                <span class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">fixflow<span class="text-[#2563EB]">.</span></span>
            </div>

            <!-- Workspace Switcher -->
            <div class="border border-gray-200 dark:border-slate-800 rounded-xl p-2.5 mb-6 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-slate-800 cursor-pointer transition">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 flex items-center justify-center text-gray-600 dark:text-slate-300">
                        <i data-lucide="box" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-900 dark:text-white leading-tight">Downtown Service Center</p>
                        <p class="text-[11px] text-gray-500 dark:text-slate-400">Admin workspace</p>
                    </div>
                </div>
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
            </div>

            <!-- Workspace Nav -->
            <div class="mb-6">
                <p class="px-3 text-[11px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">Workspace</p>
                <nav class="space-y-1">
                    <a href="requests.php" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg bg-[#EFF6FF] dark:bg-blue-950/60 text-[#2563EB] dark:text-blue-400">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        Admin dashboard
                    </a>
                    <a href="#repairs" class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition">
                        <span class="flex items-center gap-3">
                            <i data-lucide="wrench" class="w-4 h-4 text-gray-400"></i>
                            Repair jobs
                        </span>
                        <span class="bg-[#EFF6FF] dark:bg-blue-950/50 text-[#2563EB] dark:text-blue-400 text-xs font-semibold px-2 py-0.5 rounded-full"><?= $totalRepairs ?></span>
                    </a>
                    <a href="#users" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition">
                        <i data-lucide="users" class="w-4 h-4 text-gray-400"></i>
                        Team members
                    </a>
                </nav>
            </div>
        </div>

        <!-- Bottom Logout -->
        <div class="p-5 space-y-4">
            <div class="pt-2 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between px-1">
                <a href="../logout.php" title="Sign out" class="text-xs font-medium text-red-600 dark:text-red-400 hover:text-red-700 flex items-center gap-1">
                    <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Right Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Top Header Bar -->
        <header class="h-16 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 px-8 flex items-center justify-between shrink-0 transition-colors duration-200">
            <div class="flex items-center gap-2 text-sm">
                <span class="text-gray-500 dark:text-slate-400">Administration</span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                <span class="font-medium text-gray-900 dark:text-white">Admin dashboard</span>
            </div>

            <div class="flex items-center gap-3">
                <!-- Dark Mode Toggle Button -->
                <button id="darkModeToggle" type="button" class="w-9 h-9 rounded-lg border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 flex items-center justify-center transition shadow-xs" title="Toggle Dark Mode">
                    <i id="darkModeIcon" class="w-4 h-4"></i>
                </button>

                <a href="../index.php" target="_blank" class="hidden sm:flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-slate-300 hover:text-gray-900 dark:hover:text-white px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-800 transition">
                    <i data-lucide="external-link" class="w-3.5 h-3.5 text-gray-400"></i>
                    View customer portal
                </a>

                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 font-semibold text-xs flex items-center justify-center">
                    AM
                </div>
            </div>
        </header>

        <!-- Scrollable Dashboard Body -->
        <main class="flex-1 overflow-y-auto p-8 space-y-6">
            
            <?php if (!empty($success)): ?>
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/50 rounded-xl text-emerald-700 dark:text-emerald-400 text-sm">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 rounded-xl text-red-700 dark:text-red-400 text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Page Title & Primary CTA -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] font-semibold tracking-wider text-gray-400 dark:text-slate-500 uppercase mb-1">Administration · Your Service Center</p>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Admin dashboard</h1>
                    <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">A complete view of your repairs, your team, and your business.</p>
                </div>
                <button onclick="document.getElementById('newRepairModal').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-[#2563EB] hover:bg-blue-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg shadow-xs transition cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    New repair
                </button>
            </div>

            <!-- 4 KPI Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-gray-200 dark:border-slate-800 shadow-2xs flex flex-col justify-between transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500 dark:text-slate-400">Repairs in the system</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <i data-lucide="smartphone" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $totalRepairs ?></p>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-1">Across all repair statuses</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-gray-200 dark:border-slate-800 shadow-2xs flex flex-col justify-between transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500 dark:text-slate-400">Active repairs</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                            <i data-lucide="wrench" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $activeRepairs ?></p>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-1">Currently in progress</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-gray-200 dark:border-slate-800 shadow-2xs flex flex-col justify-between transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500 dark:text-slate-400">Estimated repair value</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                            <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white">$<?= number_format($totalValue, 2) ?></p>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-1">Total base value of services</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-gray-200 dark:border-slate-800 shadow-2xs flex flex-col justify-between transition-colors">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500 dark:text-slate-400">Active team members</span>
                        <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white"><?= $totalTeamCount ?></p>
                        <p class="text-[11px] text-gray-400 dark:text-slate-500 mt-1">Members in this workspace</p>
                    </div>
                </div>
            </div>

            <!-- Repair Jobs Table Card -->
            <div id="repairs" class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-2xs overflow-hidden transition-colors">
                <div class="p-6 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Repair jobs</h2>
                            <span class="bg-blue-50 dark:bg-blue-950/50 text-[#2563EB] dark:text-blue-400 text-xs font-medium px-2.5 py-0.5 rounded-full"><?= count($repair_jobs) ?> repairs</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Stay on top of your repair queue.</p>
                    </div>
                </div>

                <!-- Filter Bar & Search -->
                <div class="px-6 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="inline-flex bg-gray-50 dark:bg-slate-800/60 p-1 rounded-lg border border-gray-200 dark:border-slate-700 self-start">
                        <a href="requests.php" class="px-3 py-1.5 text-xs font-medium rounded-md <?= $filterTab === 'all' ? 'bg-white dark:bg-slate-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-600 dark:text-slate-400' ?>">All repairs</a>
                        <a href="requests.php?tab=active" class="px-3 py-1.5 text-xs font-medium rounded-md <?= $filterTab === 'active' ? 'bg-white dark:bg-slate-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-600 dark:text-slate-400' ?>">Active</a>
                        <a href="requests.php?tab=pickup" class="px-3 py-1.5 text-xs font-medium rounded-md <?= $filterTab === 'pickup' ? 'bg-white dark:bg-slate-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-600 dark:text-slate-400' ?>">Ready for pickup</a>
                        <a href="requests.php?tab=completed" class="px-3 py-1.5 text-xs font-medium rounded-md <?= $filterTab === 'completed' ? 'bg-white dark:bg-slate-900 text-gray-900 dark:text-white shadow-2xs' : 'text-gray-600 dark:text-slate-400' ?>">Completed</a>
                    </div>

                    <form method="GET" action="requests.php" class="flex items-center gap-2">
                        <div class="relative">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-2.5"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search repairs..." class="pl-8 pr-4 py-1.5 text-xs border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:border-blue-500 w-56">
                        </div>
                        <button type="submit" class="border border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 text-xs font-medium px-3 py-1.5 rounded-lg transition">Search</button>
                    </form>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-y border-gray-200 dark:border-slate-800 bg-gray-50/70 dark:bg-slate-800/40 text-[11px] font-medium text-gray-500 dark:text-slate-400">
                                <th class="py-3 px-4">Ticket ID</th>
                                <th class="py-3 px-4">Customer</th>
                                <th class="py-3 px-4">Device / Issue</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Date added</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-slate-800 text-xs">
                            <?php if (empty($repair_jobs)): ?>
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-500 dark:text-slate-400">No repair jobs found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($repair_jobs as $job): ?>
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-slate-800/50 transition">
                                    <td class="py-3.5 px-4 font-semibold text-[#2563EB] dark:text-blue-400"><?= htmlspecialchars($job['request_code']) ?></td>
                                    <td class="py-3.5 px-4">
                                        <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($job['customer_name']) ?></p>
                                        <p class="text-[11px] text-gray-400 dark:text-slate-500"><?= htmlspecialchars($job['email']) ?></p>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <p class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($job['device_brand'] . ' ' . $job['device_model']) ?></p>
                                        <p class="text-[11px] text-gray-400 dark:text-slate-500"><?= htmlspecialchars($job['service_name'] ?? $job['issue']) ?></p>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <?php 
                                        $status = $job['status'];
                                        $dot = match($status) {
                                            'In Progress' => 'dot-blue',
                                            'Ready for pickup' => 'dot-emerald',
                                            'Completed' => 'dot-gray',
                                            default => 'dot-amber'
                                        };
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 <?= $dot ?>">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-500 dark:text-slate-400"><?= htmlspecialchars($job['created_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- User Management Card -->
            <div id="users" class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-2xs p-6 transition-colors">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Team management</h2>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Manage workspace access and responsibilities.</p>
                    </div>
                    <span class="text-[11px] font-medium text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 px-2.5 py-1 rounded-md">Admin view</span>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-slate-800 border-b border-gray-100 dark:border-slate-800 pb-4 mb-6">
                    <?php if (empty($team_members)): ?>
                        <p class="text-xs text-gray-500 dark:text-slate-400 py-2">No team members added yet.</p>
                    <?php else: ?>
                        <?php foreach ($team_members as $member): ?>
                        <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 font-semibold text-xs flex items-center justify-center">
                                    <?= strtoupper(substr($member['name'], 0, 2)) ?>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($member['name']) ?></p>
                                    <p class="text-[11px] text-gray-500 dark:text-slate-400"><?= htmlspecialchars($member['email']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs font-medium text-gray-700 dark:text-slate-300 bg-gray-50 dark:bg-slate-800 px-3 py-1 rounded-lg border border-gray-200 dark:border-slate-700"><?= htmlspecialchars($member['role']) ?></span>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Active
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Add Team Member Form -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-900 dark:text-white mb-3">Add a team member</h3>
                    <form method="POST" action="requests.php" class="flex flex-col sm:flex-row items-center gap-3">
                        <input type="hidden" name="action" value="add_member">
                        <input type="text" name="member_name" required placeholder="Full name" class="w-full sm:flex-1 px-3.5 py-2 text-xs border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:border-blue-500">
                        <input type="email" name="member_email" required placeholder="Email address" class="w-full sm:flex-1 px-3.5 py-2 text-xs border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-950 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:border-blue-500">
                        <select name="member_role" class="w-full sm:w-auto px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-950 text-gray-900 dark:text-white">
                            <option value="Technician">Technician</option>
                            <option value="Administrator">Administrator</option>
                        </select>
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-medium px-4 py-2 rounded-lg transition cursor-pointer shrink-0">
                            Add member <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- New Repair Modal -->
            <div id="newRepairModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs flex items-center justify-center hidden z-50 p-4">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-xl relative space-y-4 text-gray-900 dark:text-slate-100">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-slate-800">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Create New Repair Job</h3>
                        <button onclick="document.getElementById('newRepairModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 dark:hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
                    </div>
                    <form method="POST" action="requests.php" class="space-y-4">
                        <input type="hidden" name="action" value="add_repair">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Customer Name</label>
                                <input type="text" name="customer_name" required class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-950 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Email</label>
                                <input type="email" name="email" required class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-950 rounded-lg">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Phone Number</label>
                                <input type="text" name="phone" required class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-950 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Service Type</label>
                                <select name="service_id" class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-950 text-gray-900 dark:text-white">
                                    <option value="">Select Service</option>
                                    <?php foreach ($services as $srv): ?>
                                        <option value="<?= $srv['service_id'] ?>"><?= htmlspecialchars($srv['service_name']) ?> ($<?= number_format($srv['base_price'], 2) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Device Brand</label>
                                <input type="text" name="device_brand" required placeholder="e.g. Apple" class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-950 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Device Model</label>
                                <input type="text" name="device_model" required placeholder="e.g. iPhone 14 Pro" class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-950 rounded-lg">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-slate-300 mb-1">Issue Description</label>
                            <input type="text" name="issue" required placeholder="e.g. Cracked screen" class="w-full px-3 py-2 text-xs border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-950 rounded-lg">
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" onclick="document.getElementById('newRepairModal').classList.add('hidden')" class="px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-lg text-xs font-medium text-gray-600 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-[#2563EB] hover:bg-blue-700 text-white rounded-lg text-xs font-medium">Create Repair</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <footer class="pt-2 pb-4 flex flex-col sm:flex-row items-center justify-between text-[11px] text-gray-400 dark:text-slate-500">
                <p>© 2026 Fixflow. Built for better repairs.</p>
                <div class="flex items-center gap-1.5 mt-2 sm:mt-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>All systems operational</span>
                </div>
            </footer>
        </main>
    </div>

    <!-- Dark Mode & Lucide Initialization Script -->
    <script>
        lucide.createIcons();

        const darkModeToggle = document.getElementById('darkModeToggle');
        const darkModeIcon = document.getElementById('darkModeIcon');
        const htmlElement = document.documentElement;

        if (!localStorage.getItem('admin_theme')) {
            localStorage.setItem('admin_theme', 'dark');
        }

        if (localStorage.getItem('admin_theme') === 'dark') {
            htmlElement.classList.add('dark');
            darkModeIcon.setAttribute('data-lucide', 'sun');
        } else {
            htmlElement.classList.remove('dark');
            darkModeIcon.setAttribute('data-lucide', 'moon');
        }
        lucide.createIcons();

        darkModeToggle.addEventListener('click', () => {
            if (htmlElement.classList.contains('dark')) {
                htmlElement.classList.remove('dark');
                localStorage.setItem('admin_theme', 'light');
                darkModeIcon.setAttribute('data-lucide', 'moon');
            } else {
                htmlElement.classList.add('dark');
                localStorage.setItem('admin_theme', 'dark');
                darkModeIcon.setAttribute('data-lucide', 'sun');
            }
            lucide.createIcons();
        });
    </script>
</body>
</html>
<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Sample Repair Jobs matching your UI
$repair_jobs = [
    ["id" => "#FX-1048", "initials" => "OR", "avatar_bg" => "bg-orange-100 text-orange-700", "customer" => "Olivia Rhye", "email" => "olivia.rhye@gmail.com", "device" => "iPhone 14 Pro", "issue" => "Screen replacement", "status" => "In progress", "status_style" => "bg-blue-50 text-blue-700 dot-blue", "assigned_initials" => "AM", "assigned_bg" => "bg-emerald-100 text-emerald-700", "assigned" => "Alex Morgan", "date" => "Jun 12, 2025"],
    ["id" => "#FX-1047", "initials" => "PB", "avatar_bg" => "bg-amber-100 text-amber-700", "customer" => "Phoenix Baker", "email" => "phoenix.b@gmail.com", "device" => "Samsung Galaxy S23", "issue" => "Battery replacement", "status" => "Awaiting parts", "status_style" => "bg-amber-50 text-amber-700 dot-amber", "assigned_initials" => "JC", "assigned_bg" => "bg-rose-100 text-rose-700", "assigned" => "Jamie Chen", "date" => "Jun 12, 2025"],
    ["id" => "#FX-1046", "initials" => "LS", "avatar_bg" => "bg-purple-100 text-purple-700", "customer" => "Lana Steiner", "email" => "lana.steiner@gmail.com", "device" => "iPhone 13", "issue" => "Charging port repair", "status" => "Ready for pickup", "status_style" => "bg-emerald-50 text-emerald-700 dot-emerald", "assigned_initials" => "AM", "assigned_bg" => "bg-emerald-100 text-emerald-700", "assigned" => "Alex Morgan", "date" => "Jun 11, 2025"],
    ["id" => "#FX-1045", "initials" => "DW", "avatar_bg" => "bg-sky-100 text-sky-700", "customer" => "Demi Wilkinson", "email" => "demi.w@gmail.com", "device" => "Google Pixel 8", "issue" => "Screen replacement", "status" => "In progress", "status_style" => "bg-blue-50 text-blue-700 dot-blue", "assigned_initials" => "SW", "assigned_bg" => "bg-indigo-100 text-indigo-700", "assigned" => "Sam Wilson", "date" => "Jun 11, 2025"],
    ["id" => "#FX-1044", "initials" => "DC", "avatar_bg" => "bg-indigo-100 text-indigo-700", "customer" => "Drew Cano", "email" => "drew.cano@gmail.com", "device" => "iPhone 12 Pro", "issue" => "Water damage", "status" => "Received", "status_style" => "bg-purple-50 text-purple-700 dot-purple", "assigned_initials" => "JC", "assigned_bg" => "bg-rose-100 text-rose-700", "assigned" => "Jamie Chen", "date" => "Jun 10, 2025"],
    ["id" => "#FX-1043", "initials" => "NC", "avatar_bg" => "bg-slate-100 text-slate-700", "customer" => "Natali Craig", "email" => "natali.c@gmail.com", "device" => "Samsung Galaxy A54", "issue" => "Battery replacement", "status" => "Completed", "status_style" => "bg-gray-100 text-gray-700 dot-gray", "assigned_initials" => "SW", "assigned_bg" => "bg-indigo-100 text-indigo-700", "assigned" => "Sam Wilson", "date" => "Jun 10, 2025"],
];

// Sample Team Members matching your UI
$team_members = [
    ["initials" => "AM", "bg" => "bg-emerald-100 text-emerald-700", "name" => "Alex Morgan", "email" => "alex@fixflow.com", "role" => "Administrator"],
    ["initials" => "JC", "bg" => "bg-rose-100 text-rose-700", "name" => "Jamie Chen", "email" => "jamie@fixflow.com", "role" => "Technician"],
    ["initials" => "SW", "bg" => "bg-indigo-100 text-indigo-700", "name" => "Sam Wilson", "email" => "sam@fixflow.com", "role" => "Technician"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin dashboard - fixflow.</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
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
<body class="bg-[#F9FAFB] text-[#101828] flex h-screen overflow-hidden">

    <!-- Left Sidebar -->
    <aside class="w-[260px] bg-white border-r border-gray-200 flex flex-col justify-between shrink-0 overflow-y-auto">
        <div class="p-5">
            <!-- Brand Logo -->
            <div class="flex items-center gap-2.5 px-1 mb-6">
                <div class="w-8 h-8 bg-[#2563EB] rounded-lg flex items-center justify-center text-white shadow-xs">
                    <i data-lucide="wrench" class="w-4 h-4"></i>
                </div>
                <span class="text-xl font-bold tracking-tight text-gray-900">fixflow<span class="text-[#2563EB]">.</span></span>
            </div>

            <!-- Workspace Switcher -->
            <div class="border border-gray-200 rounded-xl p-2.5 mb-6 flex items-center justify-between hover:bg-gray-50 cursor-pointer transition">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-600">
                        <i data-lucide="box" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-900 leading-tight">Downtown Service Center</p>
                        <p class="text-[11px] text-gray-500">Admin workspace</p>
                    </div>
                </div>
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
            </div>

            <!-- WORKSPACE Nav -->
            <div class="mb-6">
                <p class="px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Workspace</p>
                <nav class="space-y-1">
                    <a href="requests.php" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg bg-[#EFF6FF] text-[#2563EB]">
                        <i data-lucide="shield-check" class="w-4 h-4"></i>
                        Admin dashboard
                    </a>
                    <a href="requests.php#repairs" class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 transition">
                        <span class="flex items-center gap-3">
                            <i data-lucide="wrench" class="w-4 h-4 text-gray-400"></i>
                            Repair jobs
                        </span>
                        <span class="bg-[#EFF6FF] text-[#2563EB] text-xs font-semibold px-2 py-0.5 rounded-full">24</span>
                    </a>
                    <a href="#" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 transition">
                        <i data-lucide="users" class="w-4 h-4 text-gray-400"></i>
                        Customers
                    </a>
                    <a href="#" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 transition">
                        <i data-lucide="bar-chart-2" class="w-4 h-4 text-gray-400"></i>
                        Reports
                    </a>
                </nav>
            </div>

            <!-- MANAGE Nav -->
            <div>
                <p class="px-3 text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Manage</p>
                <nav class="space-y-1">
                    <a href="requests.php#users" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 transition">
                        <i data-lucide="users-round" class="w-4 h-4 text-gray-400"></i>
                        Team members
                    </a>
                    <a href="services.php" class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 transition">
                        <i data-lucide="settings" class="w-4 h-4 text-gray-400"></i>
                        Settings & Services
                    </a>
                </nav>
            </div>
        </div>

        <!-- Bottom Help Card & Logout -->
        <div class="p-5 space-y-4">
            <div class="bg-[#F8FAFC] border border-blue-100 rounded-xl p-4">
                <div class="w-7 h-7 rounded-full bg-blue-100 text-[#2563EB] flex items-center justify-center mb-3">
                    <i data-lucide="help-circle" class="w-4 h-4"></i>
                </div>
                <p class="text-xs font-semibold text-gray-900 mb-1">A little help goes a long way.</p>
                <p class="text-[11px] text-gray-500 mb-3 leading-relaxed">Our team is here to support yours.</p>
                <a href="#" class="inline-flex items-center gap-1.5 text-xs font-semibold text-[#2563EB] hover:underline">
                    Visit help center <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="pt-2 border-t border-gray-100 flex items-center justify-between px-1">
                <a href="#" class="flex items-center gap-2 text-xs font-medium text-gray-600 hover:text-gray-900">
                    <i data-lucide="life-buoy" class="w-4 h-4 text-gray-400"></i> Help & support
                </a>
                <a href="logout.php" title="Sign out" class="text-xs font-medium text-red-600 hover:text-red-700 flex items-center gap-1">
                    <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Logout
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Right Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        
        <!-- Top Header Bar -->
        <header class="h-16 bg-white border-b border-gray-200 px-8 flex items-center justify-between shrink-0">
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-sm">
                <span class="text-gray-500">Administration</span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                <span class="font-medium text-gray-900">Admin dashboard</span>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center gap-3">
                <button class="w-9 h-9 rounded-lg border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-50">
                    <i data-lucide="moon" class="w-4 h-4"></i>
                </button>
                <a href="#" class="hidden sm:flex items-center gap-2 text-xs font-medium text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg hover:bg-gray-50">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-gray-400"></i>
                    Track a repair
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 text-gray-400"></i>
                </a>
                <div class="relative">
                    <select class="appearance-none bg-white border border-amber-400 text-gray-800 text-xs font-medium rounded-lg pl-3 pr-8 py-2 focus:outline-none">
                        <option>Admin dashboard</option>
                        <option>Technician view</option>
                    </select>
                    <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-500 absolute right-2.5 top-2.5 pointer-events-none"></i>
                </div>
                <button class="w-9 h-9 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-50 relative">
                    <i data-lucide="bell" class="w-4 h-4"></i>
                </button>
                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 font-semibold text-xs flex items-center justify-center">
                    AM
                </div>
            </div>
        </header>

        <!-- Scrollable Dashboard Body -->
        <main class="flex-1 overflow-y-auto p-8 space-y-6">
            
            <!-- Page Title & Primary CTA -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <p class="text-[11px] font-semibold tracking-wider text-gray-400 uppercase mb-1">Administration · Your Service Center</p>
                    <h1 class="text-2xl font-bold text-gray-900">Admin dashboard</h1>
                    <p class="text-sm text-gray-500 mt-1">A complete view of your repairs, your team, and your business.</p>
                </div>
                <button class="inline-flex items-center gap-2 bg-[#2563EB] hover:bg-blue-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg shadow-xs transition cursor-pointer">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    New repair
                </button>
            </div>

            <!-- 4 KPI Stat Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Card 1 -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500">Repairs in the system</span>
                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i data-lucide="smartphone" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900">7</p>
                        <p class="text-[11px] text-gray-400 mt-1">Across all repair statuses</p>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500">Active repairs</span>
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                            <i data-lucide="wrench" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900">4</p>
                        <p class="text-[11px] text-gray-400 mt-1">Currently in your repair queue</p>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500">Estimated repair value</span>
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900">$770</p>
                        <p class="text-[11px] text-gray-400 mt-1">Total value of recorded repairs</p>
                    </div>
                </div>

                <!-- Card 4 -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-2xs flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-medium text-gray-500">Active team members</span>
                        <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i data-lucide="users" class="w-4 h-4"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-3xl font-bold text-gray-900">3</p>
                        <p class="text-[11px] text-gray-400 mt-1">3 members in this workspace</p>
                    </div>
                </div>
            </div>

            <!-- "You're in control" Banner -->
            <div class="bg-[#F4F7FF] border border-blue-100 rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-white border border-blue-100 text-[#2563EB] flex items-center justify-center shrink-0 shadow-2xs">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">You're in control.</h3>
                        <p class="text-xs text-gray-500">Monitor the queue, manage access, and keep your service center moving.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <a href="#users" class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-medium px-3.5 py-2 rounded-lg shadow-2xs transition">
                        <i data-lucide="users" class="w-3.5 h-3.5 text-gray-500"></i>
                        Manage users
                    </a>
                    <button class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-medium px-3.5 py-2 rounded-lg shadow-2xs transition cursor-pointer">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-gray-500"></i>
                        Export repairs
                    </button>
                </div>
            </div>

            <!-- Repair Jobs Table Card -->
            <div id="repairs" class="bg-white rounded-xl border border-gray-200 shadow-2xs overflow-hidden">
                <!-- Card Header -->
                <div class="p-6 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-base font-semibold text-gray-900">Repair jobs</h2>
                            <span class="bg-blue-50 text-[#2563EB] text-xs font-medium px-2.5 py-0.5 rounded-full">7 repairs</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Stay on top of your repair queue.</p>
                    </div>
                    <button class="inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-medium px-3.5 py-2 rounded-lg shadow-2xs transition self-start cursor-pointer">
                        <i data-lucide="download" class="w-3.5 h-3.5 text-gray-500"></i>
                        Export
                    </button>
                </div>

                <!-- Filter Bar -->
                <div class="px-6 pb-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <!-- Tabs -->
                    <div class="inline-flex bg-gray-50 p-1 rounded-lg border border-gray-200 self-start">
                        <button class="px-3 py-1.5 text-xs font-medium rounded-md bg-white text-gray-900 shadow-2xs">All repairs</button>
                        <button class="px-3 py-1.5 text-xs font-medium rounded-md text-gray-600 hover:text-gray-900 flex items-center gap-1.5">
                            Active <span class="bg-gray-200 text-gray-700 text-[10px] px-1.5 py-0.2 rounded-full">23</span>
                        </button>
                        <button class="px-3 py-1.5 text-xs font-medium rounded-md text-gray-600 hover:text-gray-900">Ready for pickup</button>
                        <button class="px-3 py-1.5 text-xs font-medium rounded-md text-gray-600 hover:text-gray-900">Completed</button>
                    </div>

                    <!-- Search & Filter Button -->
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <i data-lucide="search" class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-2.5"></i>
                            <input type="text" placeholder="Search repairs..." class="pl-8 pr-12 py-1.5 text-xs border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500 w-56">
                            <span class="absolute right-2 top-1.5 text-[10px] text-gray-400 border border-gray-200 rounded px-1.5 py-0.5 bg-gray-50">⌘ K</span>
                        </div>
                        <button class="inline-flex items-center gap-1.5 border border-gray-200 hover:bg-gray-50 text-gray-700 text-xs font-medium px-3 py-1.5 rounded-lg">
                            <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5 text-gray-500"></i>
                            Filters
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-y border-gray-200 bg-gray-50/70 text-[11px] font-medium text-gray-500">
                                <th class="py-3 pl-6 pr-3 w-10"><input type="checkbox" class="rounded border-gray-300"></th>
                                <th class="py-3 px-4">Ticket ID ↓</th>
                                <th class="py-3 px-4">Customer</th>
                                <th class="py-3 px-4">Device / Issue</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Assigned to</th>
                                <th class="py-3 px-4">Date added</th>
                                <th class="py-3 pr-6 pl-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs">
                            <?php foreach ($repair_jobs as $job): ?>
                            <tr class="hover:bg-gray-50/80 transition">
                                <td class="py-3.5 pl-6 pr-3"><input type="checkbox" class="rounded border-gray-300"></td>
                                <td class="py-3.5 px-4 font-semibold text-[#2563EB]"><?= $job['id'] ?></td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full <?= $job['avatar_bg'] ?> font-semibold text-[11px] flex items-center justify-center shrink-0">
                                            <?= $job['initials'] ?>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900"><?= $job['customer'] ?></p>
                                            <p class="text-[11px] text-gray-400"><?= $job['email'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-md border border-gray-200 flex items-center justify-center text-gray-400 shrink-0">
                                            <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900"><?= $job['device'] ?></p>
                                            <p class="text-[11px] text-gray-400"><?= $job['issue'] ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium <?= $job['status_style'] ?>">
                                        <?= $job['status'] ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full <?= $job['assigned_bg'] ?> font-semibold text-[10px] flex items-center justify-center">
                                            <?= $job['assigned_initials'] ?>
                                        </span>
                                        <span class="text-gray-700"><?= $job['assigned'] ?></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-gray-500"><?= $job['date'] ?></td>
                                <td class="py-3.5 pr-6 pl-3 text-right">
                                    <button class="text-gray-400 hover:text-gray-600 p-1"><i data-lucide="more-horizontal" class="w-4 h-4"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between text-xs text-gray-500">
                    <span>Showing <strong class="text-gray-900">1–6</strong> of <strong class="text-gray-900">7</strong> repairs</span>
                    <div class="flex items-center gap-1">
                        <button class="px-2.5 py-1 text-gray-400 hover:text-gray-700 flex items-center gap-1"><i data-lucide="chevron-left" class="w-3.5 h-3.5"></i> Previous</button>
                        <button class="w-7 h-7 rounded-md bg-blue-50 text-[#2563EB] font-semibold">1</button>
                        <button class="w-7 h-7 rounded-md hover:bg-gray-100 text-gray-600">2</button>
                        <button class="px-2.5 py-1 text-gray-700 hover:bg-gray-100 rounded-md flex items-center gap-1">Next <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i></button>
                    </div>
                </div>
            </div>

            <!-- User Management Card (from your 2nd screenshot) -->
            <div id="users" class="bg-white rounded-xl border border-gray-200 shadow-2xs p-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">User management</h2>
                        <p class="text-xs text-gray-500 mt-1">Manage workspace access and responsibilities.</p>
                    </div>
                    <span class="text-[11px] font-medium text-gray-500 bg-gray-100 border border-gray-200 px-2.5 py-1 rounded-md">Admin view</span>
                </div>

                <!-- Team Member List -->
                <div class="divide-y divide-gray-100 border-b border-gray-100 pb-4 mb-6">
                    <?php foreach ($team_members as $member): ?>
                    <div class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full <?= $member['bg'] ?> font-semibold text-xs flex items-center justify-center">
                                <?= $member['initials'] ?>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-gray-900"><?= $member['name'] ?></p>
                                <p class="text-[11px] text-gray-500"><?= $member['email'] ?></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <select class="appearance-none bg-white border border-gray-200 text-gray-700 text-xs font-medium rounded-lg pl-3 pr-8 py-1.5 focus:outline-none">
                                    <option <?= $member['role'] === 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                                    <option <?= $member['role'] === 'Technician' ? 'selected' : '' ?>>Technician</option>
                                </select>
                                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-gray-400 absolute right-2.5 top-2 pointer-events-none"></i>
                            </div>

                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Active
                            </span>

                            <button class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs font-medium text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition">
                                Deactivate
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Add a team member form -->
                <div>
                    <h3 class="text-xs font-semibold text-gray-900 mb-3">Add a team member</h3>
                    <form class="flex flex-col sm:flex-row items-center gap-3">
                        <input type="text" placeholder="Full name" class="w-full sm:flex-1 px-3.5 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500">
                        <input type="email" placeholder="Email address" class="w-full sm:flex-1 px-3.5 py-2 text-xs border border-gray-200 rounded-lg focus:outline-none focus:border-blue-500">
                        <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 bg-[#2563EB] hover:bg-blue-700 text-white text-xs font-medium px-4 py-2 rounded-lg transition cursor-pointer shrink-0">
                            Add member <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <footer class="pt-2 pb-4 flex flex-col sm:flex-row items-center justify-between text-[11px] text-gray-400">
                <p>© 2025 Fixflow. Built for better repairs.</p>
                <div class="flex items-center gap-1.5 mt-2 sm:mt-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>All systems operational</span>
                </div>
            </footer>

        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
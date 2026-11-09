<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-20 space-y-8">

    <!-- Search Box Header -->
    <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Track Repair Status</h1>
        <p class="text-sm text-slate-500 mb-6">Enter your unique tracking ticket code to view your device repair progress.</p>

        <form action="#" method="GET" class="flex gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="ticket" value="FX-1048" placeholder="e.g. FX-1048" class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold transition shrink-0 shadow-sm">
                Search
            </button>
        </form>
    </div>

    <!-- UI Preview: Repair Result Details Card -->
    <div class="bg-white p-8 rounded-2xl border border-slate-200 shadow-sm space-y-8">
        
        <!-- Header & Status Badge -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Ticket Reference</span>
                <h2 class="text-2xl font-mono font-bold text-blue-600">FX-1048</h2>
            </div>
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                Status: In Repair
            </div>
        </div>

        <!-- Progress Step Bar -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-2">
            <!-- Step 1 (Completed) -->
            <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/50 text-blue-900">
                <span class="text-xs font-bold block mb-1 text-blue-600">Step 1</span>
                <span class="text-sm font-semibold">Received & Logged</span>
            </div>
            
            <!-- Step 2 (Completed) -->
            <div class="p-4 rounded-xl border border-blue-200 bg-blue-50/50 text-blue-900">
                <span class="text-xs font-bold block mb-1 text-blue-600">Step 2</span>
                <span class="text-sm font-semibold">Diagnosing Issue</span>
            </div>
            
            <!-- Step 3 (Current Active) -->
            <div class="p-4 rounded-xl border border-blue-600 bg-blue-600 text-white shadow-sm">
                <span class="text-xs font-bold block mb-1 text-blue-100">Step 3</span>
                <span class="text-sm font-semibold">In Repair / Parts</span>
            </div>
            
            <!-- Step 4 (Pending) -->
            <div class="p-4 rounded-xl border border-slate-100 bg-slate-50 text-slate-400">
                <span class="text-xs font-bold block mb-1 text-slate-400">Step 4</span>
                <span class="text-sm font-semibold">Ready for Pickup</span>
            </div>
        </div>

        <!-- Device Info Specs Grid -->
        <div class="grid md:grid-cols-2 gap-6 bg-slate-50 p-6 rounded-xl text-sm">
            <div>
                <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Customer Name</span>
                <strong class="text-slate-900">Jordan Lee</strong>
            </div>
            <div>
                <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Device Brand & Model</span>
                <strong class="text-slate-900">Apple iPhone 15 Pro</strong>
            </div>
            <div>
                <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Repair Type</span>
                <strong class="text-slate-900">Screen & Battery Replacement</strong>
            </div>
            <div>
                <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Date Submitted</span>
                <strong class="text-slate-900">Today at 10:30 AM</strong>
            </div>
            <div class="md:col-span-2 border-t border-slate-200 pt-4">
                <span class="text-slate-400 block text-xs font-semibold uppercase mb-1">Notes / Issue Description</span>
                <p class="text-slate-700">Cracked front glass and battery drains rapidly within 2 hours.</p>
            </div>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
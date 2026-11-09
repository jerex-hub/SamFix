<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 pb-20 space-y-12">

    <!-- Booking & Location Section -->
    <div class="grid lg:grid-cols-3 gap-8 items-start">
        
        <!-- Left Column: Booking Form (2 Columns wide) -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900/80 p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Online Booking</span>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Tell us what needs fixing.</h1>
                </div>
            </div>

            <p class="text-sm text-slate-500 dark:text-slate-400 mb-8">
                Submit your repair request and we'll have a technician ready to assess your phone when you arrive.
            </p>

            <form action="#" method="POST" class="space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-2">Full name</label>
                        <input type="text" placeholder="e.g. Jordan Lee" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-2">Email address</label>
                        <input type="email" placeholder="you@email.com" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-2">Phone number</label>
                        <input type="text" placeholder="(415) 555-0120" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-2">Phone brand</label>
                        <input type="text" placeholder="e.g. Apple, Samsung" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-2">Phone model</label>
                        <input type="text" placeholder="e.g. iPhone 15 Pro" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-2">Repair type</label>
                        <select class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                            <option value="" disabled selected>Select a repair</option>
                            <option value="Screen Replacement">LCD & Screen Replacement</option>
                            <option value="Battery Replacement">Battery Replacement</option>
                            <option value="Charging Port">Charging Port Repair</option>
                            <option value="Camera Repair">Camera Repair</option>
                            <option value="Water Damage">Water Damage Treatment</option>
                            <option value="Diagnostics">Diagnostics & Software</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase mb-2">Notes</label>
                    <textarea rows="4" placeholder="Describe the issue, damage, or anything our technician should know." class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"></textarea>
                </div>

                <div class="pt-2">
                    <button type="button" class="px-6 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold text-sm transition inline-flex items-center gap-2 shadow-sm">
                        Submit repair order <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-3">
                        No payment is required now. We'll confirm the final quote before starting any work.
                    </p>
                </div>
            </form>
        </div>

        <!-- Right Column: Workshop Location Card -->
        <div class="bg-white dark:bg-slate-900/80 p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-6 transition-colors">
            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Visit the workshop</span>
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">Downtown Service Center</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">214 Market Street, San Francisco, CA 94105</p>
            </div>

            <!-- Map Mockup Box -->
            <div class="h-32 bg-blue-50/50 dark:bg-blue-950/30 rounded-xl border border-blue-100 dark:border-blue-900/40 flex flex-col items-center justify-center text-blue-600 dark:text-blue-400 gap-1 text-sm font-medium">
                <i class="fa-solid fa-location-dot text-xl"></i>
                <span class="text-xs">Downtown San Francisco</span>
            </div>

            <div class="space-y-4 text-xs text-slate-600 dark:text-slate-400 pt-2">
                <div>
                    <span class="block text-slate-400 dark:text-slate-500 font-semibold mb-1">Opening hours</span>
                    <p class="text-slate-800 dark:text-slate-200 font-medium">Monday–Friday · 9 AM–6 PM</p>
                    <p class="text-slate-800 dark:text-slate-200 font-medium">Saturday · 10 AM–4 PM</p>
                </div>
                <div class="border-t border-slate-100 dark:border-slate-800/80 pt-3">
                    <span class="block text-slate-400 dark:text-slate-500 font-semibold mb-1">Call us</span>
                    <p class="font-medium text-slate-800 dark:text-slate-200">(415) 555-0120</p>
                </div>
                <div class="border-t border-slate-100 dark:border-slate-800/80 pt-3">
                    <span class="block text-slate-400 dark:text-slate-500 font-semibold mb-1">Email</span>
                    <p class="font-medium text-slate-800 dark:text-slate-200">hello@fixflow.com</p>
                </div>
            </div>

            <a href="#" class="w-full flex items-center justify-between px-4 py-3 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-xs transition">
                <span>Get directions</span>
                <i class="fa-solid fa-arrow-right text-xs text-slate-400 dark:text-slate-500"></i>
            </a>
        </div>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
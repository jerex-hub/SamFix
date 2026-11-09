<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-20 space-y-20">

    <!-- Hero Header -->
    <section class="text-center max-w-2xl mx-auto space-y-4">
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
            CONNECTED TO YOUR REPAIR
        </span>
        <h1 class="text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight sm:text-5xl">
            Your repair.<br><span class="text-blue-600 dark:text-blue-400">One less thing to worry about.</span>
        </h1>
        <p class="text-slate-500 dark:text-slate-400 text-lg">
            From the first check-in to the final fix, know exactly where your device stands. No calls. No guesswork.
        </p>
    </section>

    <!-- Track Repair & Contact Section -->
    <section class="grid md:grid-cols-2 gap-8">
        <!-- Track Form -->
        <div class="bg-white dark:bg-slate-900/80 p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between transition-colors">
            <div class="space-y-6">
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-mobile-screen"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Track your repair</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Enter the ticket number from your repair receipt.</p>
                </div>

                <form action="track.php" method="GET" class="space-y-3">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"></i>
                        <input type="text" name="ticket" placeholder="e.g. FX-1048" class="w-full pl-11 pr-32 py-3 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                        <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition flex items-center gap-2 shadow-sm">
                            Track repair <i class="fa-solid fa-arrow-right text-xs"></i>
                        </button>
                    </div>
                </form>

                <p class="text-xs text-slate-400 dark:text-slate-500 flex items-center gap-1.5">
                    <i class="fa-regular fa-circle-question"></i>
                    Your ticket number starts with FX and is on your receipt.
                </p>
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-4 bg-slate-50 dark:bg-slate-950/50 p-4 rounded-xl">
                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Good hands. Clear updates.</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Your repair status, estimated cost, and pickup info right here.</p>
                </div>
            </div>
        </div>

        <!-- Contact & Location Box -->
        <div id="visit" class="bg-white dark:bg-slate-900/80 p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col justify-between transition-colors">
            <div class="space-y-6">
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">A real team, here for you.</h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">Have a question about your repair? We're always happy to help.</p>
                </div>

                <div class="space-y-4 text-sm text-slate-600 dark:text-slate-300">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-store mt-1 text-slate-400 dark:text-slate-500"></i>
                        <div>
                            <strong class="block text-slate-900 dark:text-white">Downtown Service Center</strong>
                            <span class="text-xs text-slate-500 dark:text-slate-400">Monday–Friday: 9 AM–6 PM</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fa-regular fa-envelope text-slate-400 dark:text-slate-500"></i>
                        <span class="text-slate-600 dark:text-slate-300">hello@SamFix.com</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-phone text-slate-400 dark:text-slate-500"></i>
                        <span class="text-slate-600 dark:text-slate-300">(415) 555-0120</span>
                    </div>
                </div>
            </div>

            <a href="#booking" class="mt-8 w-full block text-center py-3 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-sm transition">
                Book a repair &rarr;
            </a>
        </div>
    </section>

    <!-- Online Booking & Workshop Section -->
    <section id="booking" class="pt-10 border-t border-slate-200 dark:border-slate-800/80">
        <div class="grid lg:grid-cols-3 gap-8 items-start">
            
            <!-- Booking Form (2 Columns) -->
            <div class="lg:col-span-2 bg-white dark:bg-slate-900/80 p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Online Booking</span>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Tell us what needs fixing.</h2>
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

            <!-- Workshop Location Card -->
            <div class="bg-white dark:bg-slate-900/80 p-8 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-6 transition-colors">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Visit the workshop</span>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mt-1">Downtown Service Center</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">214 Market Street, San Francisco, CA 94105</p>
                </div>

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
                        <p class="font-medium text-slate-800 dark:text-slate-200">hello@SamFix.com</p>
                    </div>
                </div>

                <a href="#" class="w-full flex items-center justify-between px-4 py-3 border border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-xs transition">
                    <span>Get directions</span>
                    <i class="fa-solid fa-arrow-right text-xs text-slate-400 dark:text-slate-500"></i>
                </a>
            </div>

        </div>
    </section>

    <!-- 3-Step Process Section -->
    <section class="pt-10 border-t border-slate-200 dark:border-slate-800/80 text-center space-y-12">
        <div class="space-y-2">
            <span class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500">Simple from start to finish</span>
            <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white">Back to connected, in three steps.</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <div class="flex flex-col items-center space-y-4">
                <div class="relative w-16 h-16 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-center text-blue-600 dark:text-blue-400 text-xl shadow-sm">
                    <i class="fa-solid fa-mobile-screen"></i>
                    <span class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-950 border border-blue-200 dark:border-blue-900 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center">1</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Drop it off</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs leading-relaxed">
                    Bring in your device. We'll take care of the diagnosis and give you a unique ticket.
                </p>
            </div>

            <div class="flex flex-col items-center space-y-4">
                <div class="relative w-16 h-16 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-center text-blue-600 dark:text-blue-400 text-xl shadow-sm">
                    <i class="fa-solid fa-wrench"></i>
                    <span class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-950 border border-blue-200 dark:border-blue-900 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center">2</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Stay in the loop</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs leading-relaxed">
                    Check your repair status and estimated cost online, anytime you need an update.
                </p>
            </div>

            <div class="flex flex-col items-center space-y-4">
                <div class="relative w-16 h-16 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-center text-blue-600 dark:text-blue-400 text-xl shadow-sm">
                    <i class="fa-regular fa-circle-check"></i>
                    <span class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-950 border border-blue-200 dark:border-blue-900 text-blue-600 dark:text-blue-400 text-xs font-bold flex items-center justify-center">3</span>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Pick up & power on</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs leading-relaxed">
                    Once your device is ready, stop by your service center and get back to your day.
                </p>
            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php
// index.php: Home page with services and prices, tracking search, booking form, and workshop details.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

start_secure_session();   // Needed for CSRF token validation in booking form

$pageTitle = 'Customer Portal';
$activeNav = 'services';
$services  = [];
$error     = '';

try {
    $services = $pdo->query(
        'SELECT service_id, service_name, description, base_price FROM services ORDER BY service_name'
    )->fetchAll();
} catch (PDOException $e) {
    error_log('index.php query failed: ' . $e->getMessage());
    $error = 'Sorry, we could not load the services right now.';
}

// Fallback services array if database is empty during early setup
if (empty($services)) {
    $services = [
        ['service_id' => 1, 'service_name' => 'LCD & screen replacement', 'description' => 'Cracked glass, display, or touch issues', 'base_price' => 99.00],
        ['service_id' => 2, 'service_name' => 'Battery replacement', 'description' => 'Poor battery life or unexpected shutdowns', 'base_price' => 59.00],
        ['service_id' => 3, 'service_name' => 'Charging port repair', 'description' => 'Loose connection or device not charging', 'base_price' => 65.00],
        ['service_id' => 4, 'service_name' => 'Camera repair', 'description' => 'Focus, lens, or camera module issues', 'base_price' => 89.00],
        ['service_id' => 5, 'service_name' => 'Water damage treatment', 'description' => 'Internal cleaning and damage assessment', 'base_price' => 79.00],
        ['service_id' => 6, 'service_name' => 'Diagnostics & software', 'description' => 'Performance, startup, and system issues', 'base_price' => 39.00],
    ];
}

$serviceIcons = ['smartphone', 'clock', 'wrench', 'check-circle', 'shield-check', 'settings'];

require __DIR__ . '/includes/header.php';
?>

<div class="space-y-16 sm:space-y-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 transition-colors duration-200">

    <!-- Hero Section -->
    <section class="text-center max-w-3xl mx-auto space-y-4 sm:space-y-6 pt-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/10 dark:bg-blue-500/20 border border-blue-500/20 dark:border-blue-500/30 text-blue-600 dark:text-blue-400 text-xs font-semibold tracking-wider uppercase">
            <span class="w-2 h-2 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span> 
            CONNECTED TO YOUR REPAIR
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-tight">
            Your repair.<br><span class="text-blue-600 dark:text-blue-400">One less thing to worry about.</span>
        </h1>
        <p class="text-sm sm:text-base lg:text-lg text-slate-600 dark:text-slate-400 max-w-xl mx-auto leading-relaxed">
            From the first check-in to the final fix, know exactly where your device stands. No calls. No guesswork.
        </p>
    </section>

    <!-- Track + Team Contact Cards Grid -->
    <section id="track" class="grid grid-cols-1 lg:grid-cols-12 gap-8 scroll-mt-24">
        
        <!-- Track Repair Box (Takes 7 cols on lg) -->
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm flex flex-col justify-between transition-colors duration-200">
            <div class="space-y-6">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50 flex items-center justify-center text-xl shadow-sm">
                    <?= icon('smartphone', 'w-6 h-6') ?>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Track your repair</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Enter the ticket number from your repair receipt.</p>
                </div>

                <form action="<?= BASE_URL ?>/track.php" method="get">
                    <label for="search" class="sr-only">Request ID or phone number</label>
                    <div class="relative flex items-center">
                        <span class="absolute left-4 text-slate-400 dark:text-slate-500 pointer-events-none">
                            <?= icon('search', 'w-5 h-5') ?>
                        </span>
                        <input type="text" id="search" name="search" required maxlength="30"
                               placeholder="e.g. FX-1048" 
                               class="w-full pl-11 pr-32 py-3.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-600 text-sm transition-colors">
                        <button type="submit" class="absolute right-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-xs transition inline-flex items-center gap-1.5 shadow-sm">
                            Track repair <?= icon('arrow-right', 'w-4 h-4') ?>
                        </button>
                    </div>
                </form>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1.5">
                        <?= icon('help-circle', 'w-4 h-4') ?>
                        Your ticket number starts with FX and is on your receipt.
                    </span>
                    <a href="<?= BASE_URL ?>/track.php?code=FX-1048" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">Take a look at a sample repair.</a>
                </div>
            </div>

            <div class="mt-10 pt-6 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-4 rounded-2xl bg-slate-50 dark:bg-slate-950/60 p-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40">
                    <?= icon('shield-check', 'w-5 h-5') ?>
                </span>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Good hands. Clear updates.</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400">Your repair status, estimated cost, and pickup information will appear right here.</p>
                </div>
            </div>
        </div>

        <!-- Team Contact Box (Takes 5 cols on lg) -->
        <div class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm flex flex-col justify-between transition-colors duration-200">
            <div class="space-y-6">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50 flex items-center justify-center text-xl shadow-sm">
                    <?= icon('users', 'w-6 h-6') ?>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">A real team, here for you.</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Have a question about your repair? We're always happy to help.</p>
                </div>
                <ul class="space-y-4 text-sm text-slate-600 dark:text-slate-300">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-slate-400 dark:text-slate-500"><?= icon('box', 'w-5 h-5') ?></span>
                        <div>
                            <strong class="block text-slate-900 dark:text-white"><?= e(SHOP_NAME ?? 'Downtown Service Center') ?></strong>
                            <span class="text-xs text-slate-500 dark:text-slate-400"><?= e(SHOP_HOURS[0] ?? 'Monday–Friday · 9 AM–6 PM') ?></span>
                        </div>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="text-slate-400 dark:text-slate-500"><?= icon('mail', 'w-5 h-5') ?></span>
                        <span><?= e(SHOP_EMAIL ?? 'hello@fixflow.com') ?></span>
                    </li>
                    <li class="flex items-center gap-3">
                        <span class="text-slate-400 dark:text-slate-500"><?= icon('phone', 'w-5 h-5') ?></span>
                        <span><?= e(SHOP_PHONE ?? '(415) 555-0120') ?></span>
                    </li>
                </ul>
            </div>
            
            <a href="#visit" class="mt-8 w-full py-3.5 px-4 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-2xl text-slate-800 dark:text-white text-sm font-medium transition flex items-center justify-between group">
                <span>Visit help center</span>
                <span class="text-slate-400 group-hover:translate-x-1 transition-transform"><?= icon('arrow-right', 'w-4 h-4') ?></span>
            </a>
        </div>
    </section>

    <!-- Services (from database or fallback) -->
    <section id="services" class="scroll-mt-16">
        <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400 block mb-1">Clear pricing, no surprises</p>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">Popular repair services</h2>
            </div>
            <p class="max-w-md text-xs sm:text-sm text-slate-600 dark:text-slate-400">Final pricing is confirmed after a free device inspection. Parts and labor are included in the rates below.</p>
        </div>

        <?php if ($error): ?>
            <p class="rounded-xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/60 p-4 text-red-700 dark:text-red-300 text-sm"><?= e($error) ?></p>

        <?php elseif (empty($services)): ?>
            <p class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-8 text-center text-slate-500 text-sm">No services available yet.</p>

        <?php else: ?>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($services as $i => $service): ?>
                    <article class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 flex flex-col justify-between hover:border-slate-300 dark:hover:border-slate-700 transition shadow-sm">
                        <div>
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40 flex items-center justify-center mb-4">
                                <?= icon($serviceIcons[$i % count($serviceIcons)], 'w-5 h-5') ?>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1"><?= e($service['service_name']) ?></h3>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-6"><?= e($service['description']) ?></p>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                            <span class="text-xs text-slate-400">Starting rate</span>
                            <span class="text-blue-600 dark:text-blue-400 font-bold text-base">From $<?= number_format((float) $service['base_price'], 0) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Booking Form & Workshop Address Section -->
    <section id="booking" class="scroll-mt-16 border-t border-slate-200 dark:border-slate-800 pt-16">
        <div class="grid items-start gap-8 lg:grid-cols-12">

            <!-- Booking Form Card (7 cols) -->
            <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm transition-colors duration-200">
                <div class="mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40 flex items-center justify-center">
                        <?= icon('calendar', 'w-5 h-5') ?>
                    </span>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400 block mb-0.5">Online booking</span>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Tell us what needs fixing.</h2>
                    </div>
                </div>
                <p class="mb-8 text-sm text-slate-600 dark:text-slate-400">Submit your repair request and we’ll have a technician ready to assess your phone when you arrive.</p>

                <form action="<?= BASE_URL ?>/request.php" method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label for="customer_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Full name</label>
                            <input type="text" id="customer_name" name="customer_name" required maxlength="100" placeholder="e.g. Jordan Lee" 
                                   class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-600 text-sm transition-colors">
                        </div>
                        <div>
                            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Email address</label>
                            <input type="email" id="email" name="email" required maxlength="100" placeholder="you@email.com" 
                                   class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-600 text-sm transition-colors">
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Phone number</label>
                            <input type="text" id="phone" name="phone" required maxlength="25" placeholder="(415) 555-0120" 
                                   class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-600 text-sm transition-colors">
                        </div>
                        <div>
                            <label for="device_brand" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Phone brand</label>
                            <input type="text" id="device_brand" name="device_brand" required maxlength="50" placeholder="e.g. Apple, Samsung" 
                                   class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-600 text-sm transition-colors">
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label for="device_model" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Phone model</label>
                            <input type="text" id="device_model" name="device_model" required maxlength="50" placeholder="e.g. iPhone 15 Pro" 
                                   class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-600 text-sm transition-colors">
                        </div>
                        <div>
                            <label for="service_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Repair type</label>
                            <select id="service_id" name="service_id" 
                                    class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-600 text-sm transition-colors">
                                <option value="">Select a repair</option>
                                <?php foreach ($services as $s): ?>
                                    <option value="<?= (int) $s['service_id'] ?>"><?= e($s['service_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Notes</label>
                        <textarea id="notes" name="notes" rows="4" maxlength="1000"
                                  placeholder="Describe the issue, damage, or anything our technician should know." 
                                  class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-600 text-sm transition-colors"></textarea>
                    </div>

                    <div>
                        <button type="submit" class="w-full sm:w-auto px-6 py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition text-sm flex items-center justify-center gap-2 shadow-sm">
                            Submit repair order <?= icon('arrow-right', 'w-4 h-4') ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Workshop Address & Map Card (5 cols) -->
            <div id="visit" class="lg:col-span-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm transition-colors duration-200 scroll-mt-24">
                <span class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40 flex items-center justify-center">
                    <?= icon('map-pin', 'w-5 h-5') ?>
                </span>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400 block mb-0.5">VISIT THE WORKSHOP</span>
                    <h3 class="mt-1 text-xl font-bold text-slate-900 dark:text-white"><?= e(SHOP_NAME ?? 'Downtown Service Center') ?></h3>
                    <p class="mt-1 text-xs sm:text-sm text-slate-600 dark:text-slate-400"><?= e(SHOP_ADDRESS ?? '214 Market Street, San Francisco, CA 94105') ?></p>
                </div>

                <!-- Mini Map Box placeholder -->
                <div class="h-44 rounded-2xl bg-slate-100 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 flex flex-col items-center justify-center text-slate-500 dark:text-slate-400 gap-2 p-4 text-center">
                    <?= icon('map-pin', 'w-6 h-6 text-blue-600 dark:text-blue-400') ?>
                    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Downtown San Francisco</span>
                </div>

                <div class="space-y-4 border-t border-slate-100 dark:border-slate-800 pt-4 text-xs">
                    <div>
                        <span class="block font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Opening hours</span>
                        <p class="font-medium text-slate-800 dark:text-slate-200">Monday–Friday · 9 AM–6 PM</p>
                        <p class="font-medium text-slate-800 dark:text-slate-200">Saturday · 10 AM–4 PM</p>
                    </div>
                    <div class="border-t border-slate-100 dark:border-slate-800 pt-3">
                        <span class="block font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Call us</span>
                        <p class="font-medium text-slate-800 dark:text-slate-200"><?= e(SHOP_PHONE ?? '(415) 555-0120') ?></p>
                    </div>
                    <div class="border-t border-slate-100 dark:border-slate-800 pt-3">
                        <span class="block font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Email</span>
                        <p class="font-medium text-slate-800 dark:text-slate-200"><?= e(SHOP_EMAIL ?? 'hello@fixflow.com') ?></p>
                    </div>
                </div>

                <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode(SHOP_ADDRESS ?? '214 Market Street, San Francisco, CA 94105') ?>"
                   target="_blank" rel="noopener" 
                   class="w-full py-3.5 px-4 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-2xl text-slate-800 dark:text-white text-xs font-medium transition flex items-center justify-between group">
                    <span>Get directions</span>
                    <span class="text-slate-400 group-hover:translate-x-1 transition-transform"><?= icon('arrow-right', 'w-4 h-4') ?></span>
                </a>
            </div>
        </div>
    </section>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
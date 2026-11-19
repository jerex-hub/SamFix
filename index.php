<?php
// index.php: customer portal. Services and prices, repair tracking, booking form, workshop details.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

start_secure_session();   // needed for the booking form's CSRF token

$pageTitle = 'Customer Portal';
$activeNav = 'services';
$services  = [];
$error     = '';
$cur       = '₱';   // same currency as the admin dashboard

// Constants may be undefined, and "SHOP_NAME ?? x" throws on an undefined constant, so check with defined().
$shop = [
    'name'       => defined('SHOP_NAME') ? SHOP_NAME : 'Downtown Service Center',
    'address'    => defined('SHOP_ADDRESS') ? SHOP_ADDRESS : '214 Market Street, San Francisco, CA 94105',
    'phone'      => defined('SHOP_PHONE') ? SHOP_PHONE : '(415) 555-0120',
    'email'      => defined('SHOP_EMAIL') ? SHOP_EMAIL : 'hello@fixflow.com',
    'hours'      => (defined('SHOP_HOURS') && is_array(SHOP_HOURS)) ? SHOP_HOURS : ['Monday–Friday · 9 AM–6 PM', 'Saturday · 10 AM–4 PM'],
    'turnaround' => defined('SHOP_TURNAROUND') ? SHOP_TURNAROUND : '2–3 business days',
];

try {
    $services = $pdo->query('SELECT service_id, service_name, description, base_price FROM services ORDER BY service_name')->fetchAll();
} catch (PDOException $e) {
    error_log('index.php services: ' . $e->getMessage());
    $error = 'Sorry, we could not load the services right now.';
}

// Display-only fallback while the services table is empty. These ids don't exist in the database,
// so they are never offered in the booking form.
$usingFallback = false;
if (empty($services) && !$error) {
    $usingFallback = true;
    $services = [
        ['service_id' => 0, 'service_name' => 'LCD & screen replacement', 'description' => 'Cracked glass, display, or touch issues', 'base_price' => 99],
        ['service_id' => 0, 'service_name' => 'Battery replacement', 'description' => 'Poor battery life or unexpected shutdowns', 'base_price' => 59],
        ['service_id' => 0, 'service_name' => 'Charging port repair', 'description' => 'Loose connection or device not charging', 'base_price' => 65],
        ['service_id' => 0, 'service_name' => 'Camera repair', 'description' => 'Focus, lens, or camera module issues', 'base_price' => 89],
        ['service_id' => 0, 'service_name' => 'Water damage treatment', 'description' => 'Internal cleaning and damage assessment', 'base_price' => 79],
        ['service_id' => 0, 'service_name' => 'Diagnostics & software', 'description' => 'Performance, startup, and system issues', 'base_price' => 39],
    ];
}
$serviceIcons = ['smartphone', 'clock', 'wrench', 'check-circle', 'shield-check', 'settings'];

// ---- Repair tracking: by ticket code only (a phone number would expose someone else's repair) ----
$searchQuery = strtoupper(trim((string)($_GET['search'] ?? $_GET['track'] ?? $_GET['code'] ?? '')));
$tracked = null;
if ($searchQuery !== '') {
    $sql = 'SELECT r.*, s.service_name, s.base_price%s FROM repair_requests r
            LEFT JOIN services s ON r.service_id = s.service_id%s WHERE r.request_code = ? LIMIT 1';
    // Try with technician names first; fall back if the technicians migration hasn't been run yet.
    foreach ([[', t.full_name AS tech_name', ' LEFT JOIN technicians t ON r.technician_id = t.technician_id'], ['', '']] as [$cols, $join]) {
        try {
            $st = $pdo->prepare(sprintf($sql, $cols, $join));
            $st->execute([$searchQuery]);
            $tracked = $st->fetch() ?: null;
            break;
        } catch (PDOException $e) {
            error_log('index.php tracking: ' . $e->getMessage());
        }
    }
}

$stepOf = ['Pending' => 1, 'In Progress' => 2, 'Ready for pickup' => 3, 'Completed' => 4];
$stepLabels = ['Received', 'In progress', 'Ready for pickup', 'Completed'];
$messages = [
    'Pending'          => 'We have your device. A technician will start on it soon.',
    'In Progress'      => 'Your technician is working on your device. Check back here for the latest status.',
    'Ready for pickup' => 'Your device is ready. Visit us during opening hours to collect it.',
    'Completed'        => 'This repair is complete. Thank you for choosing us.',
    'Cancelled'        => 'This repair was cancelled. Contact us if you have questions.',
];
$badges = [
    'Completed'        => 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900',
    'In Progress'      => 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-900',
    'Ready for pickup' => 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-900',
    'Cancelled'        => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700',
];
$badgeDefault = 'bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 border-purple-200 dark:border-purple-900';

// ---- Booking confirmation: only trust a well-formed ticket, never invent one ----
$t = strtoupper(trim((string)($_GET['ticket'] ?? '')));
$bookedTicket = preg_match('/^[A-Z]{2,4}-\d{3,8}$/', $t) ? $t : null;
$booked = $bookedTicket !== null || ($_GET['booked'] ?? '') === '1';

// Shared class strings
$card  = 'bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800';
$field = 'w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-600 text-sm transition-colors';
$label = 'block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2';
$chip  = 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/40 flex items-center justify-center';
$well  = 'bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800';
$link  = 'w-full py-3.5 px-4 bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700/80 rounded-2xl text-slate-800 dark:text-white text-sm font-medium transition flex items-center justify-between group';

require __DIR__ . '/includes/header.php';
?>

<div class="space-y-16 sm:space-y-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

    <!-- Hero -->
    <section class="text-center max-w-3xl mx-auto space-y-4 sm:space-y-6 pt-4">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-blue-500/10 dark:bg-blue-500/20 border border-blue-500/20 dark:border-blue-500/30 text-blue-600 dark:text-blue-400 text-xs font-semibold tracking-wider uppercase">
            <span class="w-2 h-2 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span> Connected to your repair
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-tight">
            Your repair.<br><span class="text-blue-600 dark:text-blue-400">One less thing to worry about.</span>
        </h1>
        <p class="text-sm sm:text-base lg:text-lg text-slate-600 dark:text-slate-400 max-w-xl mx-auto leading-relaxed">
            From the first check-in to the final fix, know exactly where your device stands. No calls. No guesswork.
        </p>
    </section>

    <!-- Track + team contact -->
    <section id="track" class="grid grid-cols-1 lg:grid-cols-12 gap-8 scroll-mt-24">

        <div class="lg:col-span-7 <?= $card ?> rounded-3xl p-6 sm:p-8 shadow-sm flex flex-col justify-between">
            <div class="space-y-6">
                <div class="w-12 h-12 rounded-2xl <?= $chip ?> shadow-sm"><?= icon('smartphone', 'w-6 h-6') ?></div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Track your repair</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Enter the ticket number from your repair receipt.</p>
                </div>

                <form action="index.php#track" method="get">
                    <label for="search" class="sr-only">Ticket number</label>
                    <div class="relative flex items-center">
                        <span class="absolute left-4 text-slate-400 dark:text-slate-500 pointer-events-none"><?= icon('search', 'w-5 h-5') ?></span>
                        <input type="text" id="search" name="search" required maxlength="30" value="<?= e($searchQuery) ?>" placeholder="e.g. SFX-1049"
                               class="<?= $field ?> pl-11 pr-36 py-3.5">
                        <button type="submit" class="absolute right-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-xs transition inline-flex items-center gap-1.5 shadow-sm">
                            Track repair <?= icon('arrow-right', 'w-4 h-4') ?>
                        </button>
                    </div>
                </form>

                <p class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400"><?= icon('help-circle', 'w-4 h-4') ?> Your ticket number starts with SFX and is on your receipt.</p>

                <?php if ($searchQuery !== ''): ?>
                <div id="result" role="status" class="<?= $card ?> rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                <?php if (!$tracked): ?>
                    <div class="flex items-start gap-3">
                        <span class="w-10 h-10 shrink-0 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-900/50 flex items-center justify-center"><?= icon('search', 'w-5 h-5') ?></span>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white">We couldn’t find “<?= e($searchQuery) ?>”</h3>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Check the ticket number on your receipt, or call us on <?= e($shop['phone']) ?>.</p>
                        </div>
                    </div>
                <?php else:
                    $status = (string)$tracked['status'];
                    $step   = $stepOf[$status] ?? 0;
                    $shown  = $status === 'Pending' ? 'Received' : $status;
                    $cost   = $tracked['estimated_cost'] ?? $tracked['base_price'] ?? null;
                    $tech   = trim((string)($tracked['tech_name'] ?? ''));
                    $date   = !empty($tracked['created_at']) ? date('M j, Y', strtotime((string)$tracked['created_at'])) : '';
                ?>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100 dark:border-slate-800">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Request code</span>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white"><?= e($tracked['request_code']) ?></h3>
                        </div>
                        <span class="inline-flex items-center self-start px-3 py-1.5 rounded-full text-xs font-semibold border <?= $badges[$status] ?? $badgeDefault ?>"><?= e($shown) ?></span>
                    </div>

                    <div>
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white"><?= e(trim($tracked['device_brand'] . ' ' . $tracked['device_model'])) ?></h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><?= e($tracked['service_name'] ?: $tracked['issue']) ?></p>
                    </div>

                    <?php if ($status !== 'Cancelled'): ?>
                    <div class="grid grid-cols-4 gap-2 text-center text-xs py-2" aria-label="Repair progress">
                        <?php foreach ($stepLabels as $i => $l): $on = $i < $step; ?>
                        <div class="space-y-2">
                            <div class="h-2 rounded-full <?= $on ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-800' ?>"></div>
                            <span class="block <?= $on ? 'font-semibold text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-500' ?>"><?= $l ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="<?= $well ?> p-4 rounded-2xl">
                            <span class="text-xs text-slate-400 dark:text-slate-500 block mb-1">Estimated repair cost</span>
                            <span class="text-xl font-bold text-slate-900 dark:text-white"><?= $cost !== null ? $cur . number_format((float)$cost, 2) : 'To be confirmed' ?></span>
                        </div>
                        <div class="<?= $well ?> p-4 rounded-2xl">
                            <span class="text-xs text-slate-400 dark:text-slate-500 block mb-1">Estimated turnaround</span>
                            <span class="text-xl font-bold text-slate-900 dark:text-white"><?= e($shop['turnaround']) ?></span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-between gap-2 pt-4 text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800">
                        <div>Received: <strong class="text-slate-800 dark:text-slate-200"><?= e($date) ?></strong></div>
                        <div>Your technician: <strong class="text-slate-800 dark:text-slate-200"><?= $tech !== '' ? e($tech) : 'Not assigned yet' ?></strong></div>
                    </div>

                    <div class="bg-blue-50/60 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 rounded-2xl p-4 flex items-center gap-3 text-xs text-blue-700 dark:text-blue-300">
                        <span class="shrink-0"><?= icon('help-circle', 'w-4 h-4') ?></span><span><?= e($messages[$status] ?? 'Check back here for the latest status.') ?></span>
                    </div>
                <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-10 pt-6 border-t border-slate-100 dark:border-slate-800/80 flex items-center gap-4 rounded-2xl <?= $well ?> p-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full <?= $chip ?>"><?= icon('shield-check', 'w-5 h-5') ?></span>
                <div>
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Good hands. Clear updates.</h3>
                    <p class="text-xs text-slate-600 dark:text-slate-400">Your repair status, estimated cost, and pickup information appear right here.</p>
                </div>
            </div>
        </div>

        <div class="lg:col-span-5 <?= $card ?> rounded-3xl p-6 sm:p-8 shadow-sm flex flex-col justify-between">
            <div class="space-y-6">
                <div class="w-12 h-12 rounded-2xl <?= $chip ?> shadow-sm"><?= icon('users', 'w-6 h-6') ?></div>
                <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white">A real team, here for you.</h2>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Have a question about your repair? We’re always happy to help.</p>
                </div>
                <ul class="space-y-4 text-sm text-slate-600 dark:text-slate-300">
                    <li class="flex items-start gap-3"><span class="mt-0.5 text-slate-400"><?= icon('box', 'w-5 h-5') ?></span>
                        <div><strong class="block text-slate-900 dark:text-white"><?= e($shop['name']) ?></strong><span class="text-xs text-slate-500 dark:text-slate-400"><?= e($shop['hours'][0] ?? '') ?></span></div></li>
                    <li class="flex items-center gap-3"><span class="text-slate-400"><?= icon('mail', 'w-5 h-5') ?></span><span><?= e($shop['email']) ?></span></li>
                    <li class="flex items-center gap-3"><span class="text-slate-400"><?= icon('phone', 'w-5 h-5') ?></span><span><?= e($shop['phone']) ?></span></li>
                </ul>
            </div>
            <button type="button" id="openHelpCard" class="mt-8 <?= $link ?>"><span>Visit help center</span><span class="text-slate-400 group-hover:translate-x-1 transition-transform"><?= icon('arrow-right', 'w-4 h-4') ?></span></button>
        </div>
    </section>

    <!-- Services -->
    <section id="services" class="scroll-mt-16">
        <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400 mb-1">Clear pricing, no surprises</p>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">Popular repair services</h2>
            </div>
            <p class="max-w-md text-xs sm:text-sm text-slate-600 dark:text-slate-400">Final pricing is confirmed after a free device inspection. Parts and labor are included in the rates below.</p>
        </div>

        <?php if ($error): ?>
            <p class="rounded-xl border border-red-200 dark:border-red-900 bg-red-50 dark:bg-red-950/60 p-4 text-red-700 dark:text-red-300 text-sm"><?= e($error) ?></p>
        <?php else: ?>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($services as $i => $s): ?>
                <article class="<?= $card ?> rounded-2xl p-6 flex flex-col justify-between hover:border-slate-300 dark:hover:border-slate-700 transition shadow-sm">
                    <div>
                        <div class="w-10 h-10 rounded-xl <?= $chip ?> mb-4"><?= icon($serviceIcons[$i % count($serviceIcons)], 'w-5 h-5') ?></div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1"><?= e($s['service_name']) ?></h3>
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed mb-6"><?= e($s['description']) ?></p>
                    </div>
                    <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-xs text-slate-400">Starting rate</span>
                        <span class="text-blue-600 dark:text-blue-400 font-bold text-base">From <?= $cur . number_format((float)$s['base_price'], 0) ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Booking + workshop -->
    <section id="booking" class="scroll-mt-16 border-t border-slate-200 dark:border-slate-800 pt-16">
        <div class="grid items-start gap-8 lg:grid-cols-12">

            <div class="lg:col-span-7 <?= $card ?> rounded-3xl p-6 sm:p-8 shadow-sm">
                <div class="mb-6 flex items-center gap-3">
                    <span class="w-10 h-10 rounded-xl <?= $chip ?>"><?= icon('calendar', 'w-5 h-5') ?></span>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400 block mb-0.5">Online booking</span>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white">Tell us what needs fixing.</h2>
                    </div>
                </div>
                <p class="mb-8 text-sm text-slate-600 dark:text-slate-400">Submit your repair request and we’ll have a technician ready to assess your phone when you arrive.</p>

                <?php if ($booked): ?>
                <div role="status" class="mb-8 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800/80 p-5 flex items-start gap-3 shadow-sm">
                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 mt-0.5"><?= icon('check-circle', 'w-4 h-4') ?></span>
                    <div>
                        <h4 class="text-sm font-semibold text-emerald-900 dark:text-white">Your repair request is booked.</h4>
                        <?php if ($bookedTicket): ?>
                        <p class="mt-0.5 text-xs text-emerald-700 dark:text-emerald-400/90">Your ticket is <strong><?= e($bookedTicket) ?></strong>. Save it to track your repair at any time.</p>
                        <a href="?search=<?= urlencode($bookedTicket) ?>#track" class="mt-3 inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-medium transition">Track this repair <?= icon('arrow-right', 'w-3.5 h-3.5') ?></a>
                        <?php else: ?>
                        <p class="mt-0.5 text-xs text-emerald-700 dark:text-emerald-400/90">We’ll contact you shortly with your ticket number.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <form action="request.php" method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="grid gap-6 md:grid-cols-2">
                        <?php foreach ([
                            ['customer_name', 'Full name', 'text', 'e.g. Jordan Lee', 100],
                            ['email', 'Email address', 'email', 'you@email.com', 100],
                            ['phone', 'Phone number', 'tel', '(415) 555-0120', 25],
                            ['device_brand', 'Phone brand', 'text', 'e.g. Apple, Samsung', 50],
                            ['device_model', 'Phone model', 'text', 'e.g. iPhone 15 Pro', 50],
                        ] as [$id, $l, $type, $ph, $max]): ?>
                        <div><label for="<?= $id ?>" class="<?= $label ?>"><?= $l ?></label>
                            <input type="<?= $type ?>" id="<?= $id ?>" name="<?= $id ?>" required maxlength="<?= $max ?>" placeholder="<?= e($ph) ?>" class="<?= $field ?>"></div>
                        <?php endforeach; ?>
                        <div><label for="service_id" class="<?= $label ?>">Repair type</label>
                            <select id="service_id" name="service_id" class="<?= $field ?>">
                                <option value="">Select a repair</option>
                                <?php if (!$usingFallback) foreach ($services as $s): ?>
                                <option value="<?= (int)$s['service_id'] ?>"><?= e($s['service_name']) ?></option>
                                <?php endforeach; ?>
                            </select></div>
                    </div>
                    <div><label for="issue" class="<?= $label ?>">What is the problem?</label>
                        <input type="text" id="issue" name="issue" required maxlength="255" placeholder="e.g. Cracked screen" class="<?= $field ?>"></div>
                    <div><label for="notes" class="<?= $label ?>">Notes</label>
                        <textarea id="notes" name="notes" rows="4" maxlength="1000" placeholder="Describe the issue, damage, or anything our technician should know." class="<?= $field ?>"></textarea></div>
                    <button type="submit" class="w-full sm:w-auto px-6 py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition text-sm inline-flex items-center justify-center gap-2 shadow-sm">
                        Submit repair order <?= icon('arrow-right', 'w-4 h-4') ?>
                    </button>
                </form>
            </div>

            <div id="visit" class="lg:col-span-5 <?= $card ?> rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm scroll-mt-24">
                <span class="w-10 h-10 rounded-xl <?= $chip ?>"><?= icon('map-pin', 'w-5 h-5') ?></span>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400 block mb-0.5">Visit the workshop</span>
                    <h3 class="mt-1 text-xl font-bold text-slate-900 dark:text-white"><?= e($shop['name']) ?></h3>
                    <p class="mt-1 text-xs sm:text-sm text-slate-600 dark:text-slate-400"><?= e($shop['address']) ?></p>
                </div>
                <div class="space-y-4 border-t border-slate-100 dark:border-slate-800 pt-4 text-xs">
                    <div><span class="block font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Opening hours</span>
                        <?php foreach ($shop['hours'] as $h): ?><p class="font-medium text-slate-800 dark:text-slate-200"><?= e($h) ?></p><?php endforeach; ?></div>
                    <div class="border-t border-slate-100 dark:border-slate-800 pt-3"><span class="block font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Call us</span>
                        <p class="font-medium text-slate-800 dark:text-slate-200"><?= e($shop['phone']) ?></p></div>
                    <div class="border-t border-slate-100 dark:border-slate-800 pt-3"><span class="block font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1">Email</span>
                        <p class="font-medium text-slate-800 dark:text-slate-200"><?= e($shop['email']) ?></p></div>
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($shop['address']) ?>" target="_blank" rel="noopener" class="<?= $link ?>">
                    <span>Get directions</span><span class="text-slate-400 group-hover:translate-x-1 transition-transform"><?= icon('arrow-right', 'w-4 h-4') ?></span>
                </a>
            </div>
        </div>
    </section>
</div>

<!-- Help modal -->
<div id="helpModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4" role="dialog" aria-modal="true" aria-labelledby="helpTitle">
    <div class="<?= $card ?> rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl relative space-y-6">
        <button type="button" id="closeHelpModal" aria-label="Close" class="absolute top-5 right-6 text-2xl leading-none text-slate-400 hover:text-slate-600 dark:hover:text-white">&times;</button>
        <div class="w-10 h-10 rounded-xl <?= $chip ?>"><?= icon('help-circle', 'w-5 h-5') ?></div>
        <div>
            <h3 id="helpTitle" class="text-xl font-bold text-slate-900 dark:text-white">Here when you need us.</h3>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">Find quick answers or reach our service center.</p>
        </div>
        <div class="space-y-3 border-t border-slate-100 dark:border-slate-800 pt-4 text-xs sm:text-sm">
            <div><strong class="block text-slate-900 dark:text-white mb-1">How do I track a repair?</strong><p class="text-slate-600 dark:text-slate-400">Enter the ticket number from your receipt in “Track your repair”.</p></div>
            <div class="border-t border-slate-100 dark:border-slate-800 pt-3"><strong class="block text-slate-900 dark:text-white mb-1">How long will it take?</strong><p class="text-slate-600 dark:text-slate-400">Most repairs take <?= e($shop['turnaround']) ?>. Your technician confirms after inspecting the device.</p></div>
            <div class="border-t border-slate-100 dark:border-slate-800 pt-3"><strong class="block text-slate-900 dark:text-white mb-1">Need a hand?</strong><p class="text-slate-600 dark:text-slate-400">Contact <?= e($shop['email']) ?> or <?= e($shop['phone']) ?>.<br><?= e($shop['hours'][0] ?? '') ?></p></div>
        </div>
        <button type="button" id="gotItBtn" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm transition">Got it &#10003;</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('helpModal');
    const show = on => { modal.classList.toggle('hidden', !on); modal.classList.toggle('flex', on); };
    ['helpSupportBtn', 'mobileHelpBtn', 'openHelpCard'].forEach(id => {
        const b = document.getElementById(id); if (b) b.addEventListener('click', e => { e.preventDefault(); show(true); });
    });
    ['closeHelpModal', 'gotItBtn'].forEach(id => document.getElementById(id).addEventListener('click', () => show(false)));
    modal.addEventListener('click', e => { if (e.target === modal) show(false); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') show(false); });
    if (document.getElementById('result')) document.getElementById('result').scrollIntoView({ block: 'nearest' });
});
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
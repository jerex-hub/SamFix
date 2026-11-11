<?php
// index.php: home page with services and prices (FR-01), tracking search and booking form
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

start_secure_session();   // needed for the CSRF token in the booking form

$pageTitle = 'Services & Prices';
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

// Icons cycle through the service cards
$serviceIcons = ['smartphone', 'clock', 'wrench', 'check-circle', 'shield-check', 'settings'];

require __DIR__ . '/includes/header.php';
?>

<div class="space-y-20">

    <!-- Hero -->
    <section class="text-center max-w-3xl mx-auto space-y-5 pt-4">
        <span class="pill"><span class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"></span> CONNECTED TO YOUR REPAIR</span>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white">
            Your repair.<br><span class="text-blue-400">One less thing to worry about.</span>
        </h1>
        <p class="text-lg text-slate-400">
            From the first check-in to the final fix, know exactly where your device stands. No calls. No guesswork.
        </p>
    </section>

    <!-- Track + contact -->
    <section id="track" class="grid md:grid-cols-2 gap-8 scroll-mt-24">
        <div class="card p-6 sm:p-8 flex flex-col justify-between">
            <div class="space-y-6">
                <span class="icon-box"><?= icon('smartphone', 'w-6 h-6') ?></span>
                <div>
                    <h2 class="text-2xl font-bold text-white">Track your repair</h2>
                    <p class="mt-1 text-sm text-slate-400">Enter your Request ID, or the phone number you used when booking.</p>
                </div>

                <form action="<?= BASE_URL ?>/track.php" method="get">
                    <label for="search" class="sr-only">Request ID or phone number</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500"><?= icon('search', 'w-5 h-5') ?></span>
                        <input type="text" id="search" name="search" required maxlength="30"
                               placeholder="e.g. SF-20261105-00042" class="field pl-11 pr-36">
                        <button type="submit" class="btn btn-primary absolute right-2 top-1/2 -translate-y-1/2 px-4 py-2">
                            Track <?= icon('arrow-right', 'w-4 h-4') ?>
                        </button>
                    </div>
                </form>

                <p class="flex items-center gap-1.5 text-xs text-slate-500">
                    <?= icon('help-circle', 'w-4 h-4') ?>
                    Your Request ID starts with SF- and is shown right after you submit a request.
                </p>
            </div>

            <div class="mt-8 flex items-center gap-4 rounded-xl bg-slate-950/60 p-4">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-blue-500/15 text-blue-300"><?= icon('shield-check', 'w-5 h-5') ?></span>
                <div>
                    <h3 class="text-sm font-semibold text-white">Good hands. Clear updates.</h3>
                    <p class="text-xs text-slate-400">Check your repair status any time, right here.</p>
                </div>
            </div>
        </div>

        <div class="card p-6 sm:p-8 flex flex-col justify-between">
            <div class="space-y-6">
                <span class="icon-box"><?= icon('users', 'w-6 h-6') ?></span>
                <div>
                    <h2 class="text-2xl font-bold text-white">A real team, here for you.</h2>
                    <p class="mt-1 text-sm text-slate-400">Have a question about your repair? We are always happy to help.</p>
                </div>
                <ul class="space-y-4 text-sm text-slate-300">
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 text-slate-500"><?= icon('clock', 'w-5 h-5') ?></span>
                        <span>
                            <strong class="block text-white"><?= e(SHOP_NAME) ?></strong>
                            <span class="text-xs text-slate-400"><?= e(SHOP_HOURS[0]) ?></span>
                        </span>
                    </li>
                    <li class="flex items-center gap-3"><span class="text-slate-500"><?= icon('mail', 'w-5 h-5') ?></span><?= e(SHOP_EMAIL) ?></li>
                    <li class="flex items-center gap-3"><span class="text-slate-500"><?= icon('phone', 'w-5 h-5') ?></span><?= e(SHOP_PHONE) ?></li>
                </ul>
            </div>
            <a href="#booking" class="btn btn-outline mt-8 w-full py-3">Book a repair <?= icon('arrow-right', 'w-4 h-4') ?></a>
        </div>
    </section>

    <!-- Services (from the database) -->
    <section id="services" class="scroll-mt-16">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="eyebrow">Clear pricing, no surprises</p>
                <h2 class="mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight text-white">Popular repair services</h2>
            </div>
            <p class="max-w-md text-sm text-slate-400">Prices shown are starting rates. Final pricing is confirmed after your device is inspected.</p>
        </div>

        <?php if ($error): ?>
            <p class="rounded-xl border border-red-900 bg-red-950/60 p-4 text-red-300"><?= e($error) ?></p>

        <?php elseif (empty($services)): ?>
            <p class="card p-8 text-center text-slate-400">No services available yet.</p>

        <?php else: ?>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($services as $i => $service): ?>
                    <article class="card p-6 flex gap-4">
                        <span class="icon-box"><?= icon($serviceIcons[$i % count($serviceIcons)], 'w-5 h-5') ?></span>
                        <div class="flex flex-1 flex-col">
                            <h3 class="text-lg font-semibold text-white"><?= e($service['service_name']) ?></h3>
                            <p class="mt-1 flex-1 text-sm text-slate-400"><?= e($service['description']) ?></p>
                            <p class="mt-5 font-semibold text-blue-400">From &#8369;<?= number_format((float) $service['base_price'], 2) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Booking + workshop -->
    <section id="booking" class="scroll-mt-16 border-t border-slate-800 pt-16">
        <div class="grid items-start gap-8 lg:grid-cols-3">

            <div class="card p-6 sm:p-8 lg:col-span-2">
                <div class="mb-6 flex items-center gap-3">
                    <span class="icon-box h-10 w-10"><?= icon('calendar', 'w-5 h-5') ?></span>
                    <div>
                        <span class="eyebrow">Online booking</span>
                        <h2 class="text-2xl font-bold text-white">Tell us what needs fixing.</h2>
                    </div>
                </div>
                <p class="mb-8 text-sm text-slate-400">Submit your repair request and we will have a technician ready to assess your phone when you arrive.</p>

                <!-- Submits to request.php, which validates and saves it -->
                <form action="<?= BASE_URL ?>/request.php" method="post" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label for="customer_name" class="field-label">Full name</label>
                            <input type="text" id="customer_name" name="customer_name" required maxlength="100" placeholder="e.g. Jordan Lee" class="field">
                        </div>
                        <div>
                            <label for="email" class="field-label">Email address</label>
                            <input type="email" id="email" name="email" required maxlength="100" placeholder="you@email.com" class="field">
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label for="phone" class="field-label">Phone number</label>
                            <input type="text" id="phone" name="phone" required maxlength="25" placeholder="0917 123 4567" class="field">
                        </div>
                        <div>
                            <label for="device_brand" class="field-label">Phone brand</label>
                            <input type="text" id="device_brand" name="device_brand" required maxlength="50" placeholder="e.g. Apple, Samsung" class="field">
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div>
                            <label for="device_model" class="field-label">Phone model</label>
                            <input type="text" id="device_model" name="device_model" required maxlength="50" placeholder="e.g. iPhone 15 Pro" class="field">
                        </div>
                        <div>
                            <label for="service_id" class="field-label">Repair type</label>
                            <select id="service_id" name="service_id" class="field">
                                <option value="">Not sure / other</option>
                                <?php foreach ($services as $s): ?>
                                    <option value="<?= (int) $s['service_id'] ?>"><?= e($s['service_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="issue" class="field-label">What is the problem?</label>
                        <input type="text" id="issue" name="issue" required maxlength="255" placeholder="e.g. Cracked screen" class="field">
                    </div>

                    <div>
                        <label for="notes" class="field-label">Notes (optional)</label>
                        <textarea id="notes" name="notes" rows="4" maxlength="1000"
                                  placeholder="Describe the damage, or anything our technician should know." class="field"></textarea>
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary px-6 py-3.5">Submit repair order <?= icon('arrow-right', 'w-4 h-4') ?></button>
                        <p class="mt-3 text-xs text-slate-500">No payment is required now. We will confirm the final quote before starting any work.</p>
                    </div>
                </form>
            </div>

            <div class="card p-6 sm:p-8 space-y-6">
                <span class="icon-box h-10 w-10"><?= icon('map-pin', 'w-5 h-5') ?></span>
                <div>
                    <span class="eyebrow">Visit the workshop</span>
                    <h3 class="mt-1 text-xl font-bold text-white"><?= e(SHOP_NAME) ?></h3>
                    <p class="mt-1 text-xs text-slate-400"><?= e(SHOP_ADDRESS) ?></p>
                </div>

                <div class="space-y-4 border-t border-slate-800 pt-4 text-xs">
                    <div>
                        <span class="info-title">Opening hours</span>
                        <?php foreach (SHOP_HOURS as $line): ?>
                            <p class="font-medium text-slate-200"><?= e($line) ?></p>
                        <?php endforeach; ?>
                    </div>
                    <div class="border-t border-slate-800 pt-3">
                        <span class="info-title">Call us</span>
                        <p class="font-medium text-slate-200"><?= e(SHOP_PHONE) ?></p>
                    </div>
                    <div class="border-t border-slate-800 pt-3">
                        <span class="info-title">Email</span>
                        <p class="font-medium text-slate-200"><?= e(SHOP_EMAIL) ?></p>
                    </div>
                </div>

                <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode(SHOP_ADDRESS) ?>"
                   target="_blank" rel="noopener" class="link-row">
                    Get directions <?= icon('arrow-right', 'w-4 h-4') ?>
                </a>
            </div>
        </div>
    </section>

    <!-- Visit anchor + three steps -->
    <section id="visit" class="scroll-mt-16 border-t border-slate-800 pt-16 text-center space-y-12">
        <div class="space-y-2">
            <p class="eyebrow">Simple from start to finish</p>
            <h2 class="text-3xl font-extrabold text-white">Back to connected, in three steps.</h2>
        </div>

        <div class="grid gap-8 md:grid-cols-3">
            <?php
            $steps = [
                ['smartphone',   'Drop it off',       'Book online or bring in your device. You get a Request ID so you can follow your repair.'],
                ['wrench',       'Stay in the loop',  'Check your repair status online, any time you need an update.'],
                ['check-circle', 'Pick up & power on', 'Once your device is ready, stop by the service center and get back to your day.'],
            ];
            foreach ($steps as $n => [$ic, $title, $text]): ?>
                <div class="flex flex-col items-center space-y-4">
                    <div class="relative grid h-16 w-16 place-items-center rounded-2xl border border-slate-800 bg-slate-900 text-blue-400 shadow-sm">
                        <?= icon($ic, 'w-7 h-7') ?>
                        <span class="absolute -right-2 -top-2 grid h-6 w-6 place-items-center rounded-full border border-blue-500/40 bg-blue-500/20 text-xs font-bold text-blue-300"><?= $n + 1 ?></span>
                    </div>
                    <h3 class="text-base font-bold text-white"><?= e($title) ?></h3>
                    <p class="max-w-xs text-xs leading-relaxed text-slate-400"><?= e($text) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
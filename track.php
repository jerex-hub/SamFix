<?php
// track.php: Track Repair Status (ticket code only, so nobody can look up someone else's phone number)
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Track Repair';
$activeNav = 'track';
$cur = '₱';

$trackingCode = strtoupper(trim((string) ($_GET['code'] ?? $_GET['search'] ?? '')));
$repair = null;
$error  = null;

if ($trackingCode !== '') {
    $sql = 'SELECT r.*, s.service_name, s.base_price%s FROM repair_requests r
            LEFT JOIN services s ON r.service_id = s.service_id%s WHERE r.request_code = ? LIMIT 1';
    $lookedUp = false;
    // Try with technician names first; fall back if the technicians table isn't set up yet.
    foreach ([[', t.full_name AS tech_name', ' LEFT JOIN technicians t ON r.technician_id = t.technician_id'], ['', '']] as [$cols, $join]) {
        try {
            $st = $pdo->prepare(sprintf($sql, $cols, $join));
            $st->execute([$trackingCode]);
            $repair = $st->fetch() ?: null;
            $lookedUp = true;
            break;
        } catch (PDOException $ex) {
            error_log('track.php lookup failed: ' . $ex->getMessage());
        }
    }
    if (!$lookedUp) {
        $error = 'An error occurred while looking up your repair. Please try again later.';
    } elseif (!$repair) {
        $error = 'No repair found for ticket: ' . $trackingCode;
    }
}

$stepOf     = ['Pending' => 1, 'In Progress' => 2, 'Ready for pickup' => 3, 'Completed' => 4];
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
$turnaround   = defined('SHOP_TURNAROUND') ? SHOP_TURNAROUND : '2–3 business days';

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-10">
    <!-- Page Header -->
    <div class="text-center mb-8 sm:mb-10 space-y-3">
        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">Track Your Device Repair</h1>
        <p class="text-slate-600 dark:text-slate-400 text-xs sm:text-sm max-w-lg mx-auto leading-relaxed">
            Enter the ticket number from your repair receipt to check the status of your repair.
        </p>
    </div>

    <!-- Search Box Card -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-8 shadow-sm transition-colors duration-200 mb-8">
        <form method="GET" action="track.php" class="flex flex-col sm:flex-row gap-3" data-validate>
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </span>
                <input type="text" name="code" value="<?= e($trackingCode) ?>" placeholder="e.g. SFX-2641" required maxlength="30"
                       class="w-full pl-10 pr-4 py-3.5 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 transition text-sm">
            </div>
            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition text-sm shadow-sm shrink-0">
                Search status
            </button>
        </form>
    </div>

    <!-- Error Alert -->
    <?php if ($error): ?>
        <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 rounded-xl text-red-700 dark:text-red-400 text-sm text-center mb-8">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Repair Status Result Card -->
    <?php if ($repair):
        $status = (string) ($repair['status'] ?? 'Pending');
        $step   = $stepOf[$status] ?? 0;
        $shown  = $status === 'Pending' ? 'Received' : $status;
        $cost   = $repair['estimated_cost'] ?? $repair['base_price'] ?? null;
        $tech   = trim((string) ($repair['tech_name'] ?? ''));
        $date   = !empty($repair['created_at']) ? date('M j, Y', strtotime((string) $repair['created_at'])) : '';
    ?>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6 transition-colors duration-200">

            <!-- Request Code & Status Badge -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Request code</span>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white"><?= e($repair['request_code']) ?></h2>
                </div>
                <span class="inline-flex items-center self-start px-3 py-1.5 rounded-full text-xs font-semibold border <?= $badges[$status] ?? $badgeDefault ?>"><?= e($shown) ?></span>
            </div>

            <!-- Device & Service Title -->
            <div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white"><?= e(trim(($repair['device_brand'] ?? '') . ' ' . ($repair['device_model'] ?? ''))) ?></h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"><?= e($repair['service_name'] ?: ($repair['issue'] ?? 'General repair')) ?></p>
            </div>

            <?php if ($status !== 'Cancelled'): ?>
            <div class="py-2"><?= progress_stepper($step, $stepLabels) ?></div>
            <?php endif; ?>

            <!-- Cost & Turnaround -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-slate-50 dark:bg-slate-950/60 p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <span class="text-xs text-slate-400 dark:text-slate-500 block mb-1">Estimated repair cost</span>
                    <span class="text-xl font-bold text-slate-900 dark:text-white"><?= $cost !== null && (float) $cost > 0 ? $cur . number_format((float) $cost, 2) : 'To be confirmed' ?></span>
                </div>
                <div class="bg-slate-50 dark:bg-slate-950/60 p-4 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <span class="text-xs text-slate-400 dark:text-slate-500 block mb-1">Estimated turnaround</span>
                    <span class="text-xl font-bold text-slate-900 dark:text-white"><?= e($turnaround) ?></span>
                </div>
            </div>

            <!-- Customer & Notes Details -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm pt-2 border-t border-slate-100 dark:border-slate-800">
                <div>
                    <span class="text-slate-400 dark:text-slate-500 block text-xs uppercase tracking-wider mb-1">Customer Name</span>
                    <span class="text-slate-900 dark:text-white font-semibold"><?= e($repair['customer_name']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500 block text-xs uppercase tracking-wider mb-1">Phone Number</span>
                    <span class="text-slate-900 dark:text-white font-semibold"><?= e($repair['phone']) ?></span>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-slate-400 dark:text-slate-500 block text-xs uppercase tracking-wider mb-1">Reported Issue</span>
                    <p class="text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-950/50 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800 text-xs sm:text-sm">
                        <?= nl2br(e($repair['issue'])) ?>
                    </p>
                </div>
                <?php if (!empty($repair['notes'])): ?>
                <div class="sm:col-span-2">
                    <span class="text-slate-400 dark:text-slate-500 block text-xs uppercase tracking-wider mb-1">Additional Notes</span>
                    <p class="text-slate-700 dark:text-slate-300 bg-blue-50/50 dark:bg-blue-950/20 p-3.5 rounded-xl border border-blue-100 dark:border-blue-900/40 text-xs sm:text-sm">
                        <?= nl2br(e($repair['notes'])) ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Meta Info -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 pt-4 text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800">
                <div>Received: <strong class="text-slate-800 dark:text-slate-200"><?= e($date) ?></strong></div>
                <div>Your technician: <strong class="text-slate-800 dark:text-slate-200"><?= $tech !== '' ? e($tech) : 'Not assigned yet' ?></strong></div>
            </div>

            <!-- Status Message Banner -->
            <div class="bg-blue-50/60 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 rounded-2xl p-4 flex items-center gap-3 text-xs text-blue-700 dark:text-blue-300">
                <i class="fa-regular fa-circle-question text-base shrink-0"></i>
                <span><?= e($messages[$status] ?? 'Check back here for the latest status.') ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
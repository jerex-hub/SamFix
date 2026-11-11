<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Track Repair';
$activeNav = 'track';

$trackingCode = trim($_GET['code'] ?? '');
$repair = null;
$error = null;

if ($trackingCode !== '') {
    try {
        // Assumes $pdo is initialized in your config.php or database connection file
        $stmt = $pdo->prepare("SELECT * FROM repairs WHERE tracking_code = ? LIMIT 1");
        $stmt->execute([$trackingCode]);
        $repair = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$repair) {
            $error = "No repair record found with tracking code: " . e($trackingCode);
        }
    } catch (Exception $e) {
        $error = "An error occurred while looking up your repair. Please try again later.";
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-3xl mx-auto py-8">
    <!-- Page Header & Search Input -->
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white mb-3">Track Your Device Repair</h1>
        <p class="text-slate-600 dark:text-slate-400 text-sm max-w-lg mx-auto">
            Enter your unique tracking code below to check the real-time status of your hardware repair.
        </p>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm transition-colors duration-200 mb-8">
        <form method="GET" action="track.php" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </span>
                <input type="text" name="code" value="<?= e($trackingCode) ?>" placeholder="e.g. SF-94821" required
                       class="w-full pl-10 pr-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/80 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 transition text-sm">
            </div>
            <button type="submit" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition text-sm shadow-sm">
                Search status
            </button>
        </form>
    </div>

    <!-- Error Alert -->
    <?php if ($error): ?>
        <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 rounded-xl text-red-700 dark:text-red-400 text-sm text-center mb-8">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <!-- Repair Status Result Card -->
    <?php if ($repair): ?>
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm transition-colors duration-200 space-y-6">
            
            <!-- Tracking Code & Dynamic Status Badge -->
            <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Tracking Code</span>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white"><?= e($repair['tracking_code']) ?></h2>
                </div>
                <div>
                    <?php 
                    $status = $repair['status'] ?? 'Pending';
                    $statusClass = match($status) {
                        'Completed' => 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 border-emerald-200 dark:border-emerald-900',
                        'In Progress' => 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border-blue-200 dark:border-blue-900',
                        'Waiting for Parts' => 'bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 border-amber-200 dark:border-amber-900',
                        default => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700'
                    };
                    ?>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold border <?= $statusClass ?>">
                        <?= e($status) ?>
                    </span>
                </div>
            </div>

            <!-- Ticket Details Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                <div>
                    <span class="text-slate-400 dark:text-slate-500 block text-xs font-medium uppercase tracking-wider mb-1">Customer Name</span>
                    <span class="text-slate-900 dark:text-white font-semibold"><?= e($repair['customer_name']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400 dark:text-slate-500 block text-xs font-medium uppercase tracking-wider mb-1">Device Model</span>
                    <span class="text-slate-900 dark:text-white font-semibold"><?= e($repair['device_model']) ?></span>
                </div>
                <div class="sm:col-span-2">
                    <span class="text-slate-400 dark:text-slate-500 block text-xs font-medium uppercase tracking-wider mb-1">Reported Issue</span>
                    <p class="text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/50 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800">
                        <?= nl2br(e($repair['issue_description'])) ?>
                    </p>
                </div>
                <?php if (!empty($repair['technician_notes'])): ?>
                <div class="sm:col-span-2">
                    <span class="text-slate-400 dark:text-slate-500 block text-xs font-medium uppercase tracking-wider mb-1">Technician Notes</span>
                    <p class="text-slate-700 dark:text-slate-300 bg-blue-50/50 dark:bg-blue-950/20 p-3.5 rounded-xl border border-blue-100 dark:border-blue-900/40">
                        <?= nl2br(e($repair['technician_notes'])) ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Timestamps -->
            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 text-xs text-slate-400 dark:text-slate-500">
                <span>Submitted: <?= e($repair['created_at']) ?></span>
                <span>Last Updated: <?= e($repair['updated_at'] ?? $repair['created_at']) ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
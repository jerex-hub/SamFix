<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

$pageTitle = 'Book a Repair';
$activeNav = 'book';

$error = null;
$successCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid security token. Please refresh the page and try again.";
    } else {
        $customerName = trim($_POST['customer_name'] ?? '');
        $email        = trim($_POST['email'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $deviceModel  = trim($_POST['device_model'] ?? '');
        $issueDesc    = trim($_POST['issue_description'] ?? '');

        // Basic validation
        if ($customerName === '' || $email === '' || $phone === '' || $deviceModel === '' || $issueDesc === '') {
            $error = "Please fill in all required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            try {
                // Generate a unique tracking code e.g. SF-79A4B
                $trackingCode = 'SF-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

                $stmt = $pdo->prepare("
                    INSERT INTO repairs (tracking_code, customer_name, email, phone, device_model, issue_description, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending', NOW())
                ");
                $stmt->execute([$trackingCode, $customerName, $email, $phone, $deviceModel, $issueDesc]);

                $successCode = $trackingCode;
            } catch (Exception $e) {
                $error = "An error occurred while submitting your repair request. Please try again later.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-2xl mx-auto py-8">
    <!-- Page Header -->
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white mb-3">Book a Device Repair</h1>
        <p class="text-slate-600 dark:text-slate-400 text-sm max-w-md mx-auto">
            Fill out the details below to submit your device for diagnostics and repair. We will follow up with you shortly.
        </p>
    </div>

    <?php if ($successCode): ?>
        <!-- Success Confirmation Card -->
        <div class="bg-white dark:bg-slate-900 border border-emerald-200 dark:border-emerald-900/50 rounded-2xl p-8 shadow-sm text-center space-y-6 transition-colors duration-200">
            <div class="w-16 h-16 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto text-2xl">
                <i class="fa-solid fa-check"></i>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-2">Repair Request Submitted!</h2>
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Your repair ticket has been successfully created. Keep your tracking code safe to check your repair status anytime.
                </p>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 p-4 rounded-xl border border-slate-200 dark:border-slate-700 max-w-xs mx-auto">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-1">Your Tracking Code</span>
                <span class="text-xl font-mono font-bold text-blue-600 dark:text-blue-400"><?= e($successCode) ?></span>
            </div>

            <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="track.php?code=<?= e($successCode) ?>" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition text-sm shadow-sm">
                    Track this repair now
                </a>
                <a href="index.php" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium rounded-xl transition text-sm">
                    Return home
                </a>
            </div>
        </div>
    <?php else: ?>

        <!-- Error Alert -->
        <?php if ($error): ?>
            <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 rounded-xl text-red-700 dark:text-red-400 text-sm text-center mb-6">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Booking Form Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm transition-colors duration-200">
            <form method="POST" action="request.php" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Full Name</label>
                        <input type="text" name="customer_name" value="<?= e($_POST['customer_name'] ?? '') ?>" required placeholder="e.g. Juan Dela Cruz"
                               class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 text-sm transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Email Address</label>
                        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required placeholder="e.g. juan@example.com"
                               class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 text-sm transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Phone Number</label>
                        <input type="text" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" required placeholder="e.g. 09123456789"
                               class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 text-sm transition">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Device Model / Type</label>
                        <input type="text" name="device_model" value="<?= e($_POST['device_model'] ?? '') ?>" required placeholder="e.g. Tecno Camon 18 / Laptop / Custom PC"
                               class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 text-sm transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">Describe the Issue</label>
                    <textarea name="issue_description" rows="4" required placeholder="Please describe the hardware malfunction, symptoms, or service needed..."
                              class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 dark:focus:ring-blue-500 text-sm transition"><?= e($_POST['issue_description'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition text-sm shadow-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    <span>Submit repair request</span>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
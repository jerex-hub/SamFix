<?php
// request.php: Submit Repair Request (FR-02 to FR-05)
// Design unchanged. Only the logic at the top was fixed so bookings save correctly.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

start_secure_session();   // the CSRF token lives in the session

$pageTitle = 'Book a Repair';
$activeNav = 'book';

$error = null;
$successCode = null;
$ticket = null;   // summary shown on the confirmation card

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF token
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please refresh the page and try again.";
    } else {
        // Read a posted value safely as a trimmed string
        $get = function (string $key): string {
            $v = $_POST[$key] ?? '';
            return is_string($v) ? trim($v) : '';
        };

        $customerName = $get('customer_name');
        $email        = $get('email');
        $phone        = $get('phone');
        $deviceBrand  = $get('device_brand');
        $deviceModel  = $get('device_model');
        $notes        = $get('notes');
        $serviceIdRaw = $get('service_id');

        // This page sends "issue_description"; the home page form sends "issue"
        $issueDesc = $get('issue_description');
        if ($issueDesc === '') {
            $issueDesc = $get('issue');
        }

        // The database needs a brand; this page has no brand field, so use a default
        if ($deviceBrand === '') {
            $deviceBrand = 'Not specified';
        }

        // "issue" holds up to 255 characters; a longer description is kept in full in notes
        $issue = mb_substr($issueDesc, 0, 255);
        if (mb_strlen($issueDesc) > 255) {
            $notes = trim($issueDesc . ($notes !== '' ? "\n\n" . $notes : ''));
        }

        // 2. Validate with the shared rules
        $errors = validate_request_form([
            'customer_name' => $customerName,
            'email'         => $email,
            'phone'         => $phone,
            'device_brand'  => $deviceBrand,
            'device_model'  => $deviceModel,
            'issue'         => $issue,
            'notes'         => $notes,
        ]);

        // Optional service: must exist in the database
        $serviceId = null;
        if (!$errors && $serviceIdRaw !== '') {
            try {
                $chk = $pdo->prepare('SELECT service_id FROM services WHERE service_id = ?');
                $chk->execute([(int) $serviceIdRaw]);
                if (ctype_digit($serviceIdRaw) && $chk->fetch()) {
                    $serviceId = (int) $serviceIdRaw;
                } else {
                    $errors['service_id'] = 'Please choose a valid service.';
                }
            } catch (PDOException $e) {
                error_log('request.php service check failed: ' . $e->getMessage());
                $errors['service_id'] = 'Could not check the selected service. Please try again.';
            }
        }

        if ($errors) {
            $error = reset($errors);   // show the first problem
        } else {
            // 3. Save inside a transaction: insert, build the Request ID from the row ID, commit
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO repair_requests
                        (request_code, service_id, customer_name, email, phone,
                         device_brand, device_model, issue, notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    'TMP-' . bin2hex(random_bytes(8)),   // temporary, replaced below
                    $serviceId,
                    $customerName,
                    $email,
                    normalize_phone($phone),
                    $deviceBrand,
                    $deviceModel,
                    $issue,
                    $notes !== '' ? $notes : null,
                ]);

                $id   = (int) $pdo->lastInsertId();
                $code = generate_request_code($id);   // e.g. SF-20261111-00001

                $upd = $pdo->prepare('UPDATE repair_requests SET request_code = ? WHERE request_id = ?');
                $upd->execute([$code, $id]);

                $pdo->commit();

                unset($_SESSION['csrf_token']);   // fresh token for the next form
                $successCode = $code;
                $ticket = [
                    'name'   => $customerName,
                    'device' => trim(($deviceBrand !== 'Not specified' ? $deviceBrand . ' ' : '') . $deviceModel),
                    'issue'  => $issue,
                    'date'   => date('M j, Y g:i A'),
                ];
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('request.php save failed: ' . $e->getMessage());
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
                    Your repair ticket has been successfully created. Keep your ticket number safe to check your repair status anytime.
                </p>
            </div>

            <div class="bg-slate-50 dark:bg-slate-800/60 p-4 rounded-xl border border-slate-200 dark:border-slate-700 max-w-xs mx-auto">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500 block mb-1">Your Ticket Number</span>
                <span class="text-xl font-mono font-bold text-blue-600 dark:text-blue-400"><?= e($successCode) ?></span>
            </div>

            <?php if ($ticket): ?>
                <dl class="text-left text-sm max-w-xs mx-auto space-y-2 text-slate-600 dark:text-slate-400">
                    <div class="flex justify-between gap-4"><dt>Name</dt><dd class="font-medium text-slate-900 dark:text-white text-right"><?= e($ticket['name']) ?></dd></div>
                    <div class="flex justify-between gap-4"><dt>Device</dt><dd class="font-medium text-slate-900 dark:text-white text-right"><?= e($ticket['device']) ?></dd></div>
                    <div class="flex justify-between gap-4"><dt>Issue</dt><dd class="font-medium text-slate-900 dark:text-white text-right"><?= e($ticket['issue']) ?></dd></div>
                    <div class="flex justify-between gap-4"><dt>Status</dt><dd class="font-medium text-slate-900 dark:text-white text-right">Pending</dd></div>
                    <div class="flex justify-between gap-4"><dt>Submitted</dt><dd class="font-medium text-slate-900 dark:text-white text-right"><?= e($ticket['date']) ?></dd></div>
                </dl>
            <?php endif; ?>

            <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="track.php?search=<?= urlencode($successCode) ?>" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition text-sm shadow-sm">
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
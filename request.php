<?php
// request.php: Submit Repair Request (FR-02 to FR-05)
// Form redesigned to match the "Online Booking" card. Save logic unchanged.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

start_secure_session();   // the CSRF token lives in the session

$pageTitle = 'Book a Repair';
$activeNav = 'book';

$error = null;
$successCode = null;
$ticket = null;   // summary shown on the confirmation card

// Load the services for the "Repair type" dropdown
$services = [];
try {
    $services = $pdo->query('SELECT service_id, service_name FROM services ORDER BY service_name')->fetchAll();
} catch (PDOException $e) {
    error_log('request.php services load failed: ' . $e->getMessage());
}

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
        $issueDesc    = $get('issue');   // "What is the problem?"

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
        if (!$errors && $serviceIdRaw !== '' && $serviceIdRaw !== 'other') {
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
            // 3. Save: pick a unique ticket like SFX-1506, insert, retry on the rare duplicate
            try {
                $code = null;
                for ($try = 0; $try < 5 && $code === null; $try++) {
                    $candidate = generate_request_code($pdo);
                    try {
                        $stmt = $pdo->prepare(
                            'INSERT INTO repair_requests
                                (request_code, service_id, customer_name, email, phone,
                                 device_brand, device_model, issue, notes)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                        );
                        $stmt->execute([
                            $candidate,
                            $serviceId,
                            $customerName,
                            $email,
                            normalize_phone($phone),
                            $deviceBrand,
                            $deviceModel,
                            $issue,
                            $notes !== '' ? $notes : null,
                        ]);
                        $code = $candidate;
                    } catch (PDOException $dup) {
                        if ($dup->getCode() !== '23000') { throw $dup; }   // only retry on a duplicate code
                    }
                }
                if ($code === null) {
                    throw new RuntimeException('Could not generate a unique ticket code');
                }

                unset($_SESSION['csrf_token']);   // fresh token for the next form
                $successCode = $code;
                $ticket = [
                    'name'   => $customerName,
                    'device' => trim($deviceBrand . ' ' . $deviceModel),
                    'issue'  => $issue,
                    'date'   => date('M j, Y g:i A'),
                ];
            } catch (Throwable $e) {
                error_log('request.php save failed: ' . $e->getMessage());
                $error = "An error occurred while submitting your repair request. Please try again later.";
            }
        }
    }
}

// Shared input styling
$inputCls = 'w-full px-4 py-3 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-600 text-sm transition-colors';
$selectCls = $inputCls;   // same solid dark background as index.php, so the dropdown list matches too
$optCls    = 'bg-white text-slate-900 dark:bg-slate-950 dark:text-white';
$selSvc    = (string) ($_POST['service_id'] ?? '');
$labelCls = 'block text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2';

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-2xl mx-auto py-8">

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
                    <div class="flex justify-between gap-4"><dt>Status</dt><dd class="font-medium text-slate-900 dark:text-white text-right">Received</dd></div>
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

        <!-- Booking Form Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm transition-colors duration-200">

            <!-- Card header -->
            <div class="flex items-center gap-3 mb-6">
                <div class="w-11 h-11 shrink-0 flex items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-950/50 border border-blue-100 dark:border-blue-900/50 text-blue-600 dark:text-blue-400">
                    <i class="fa-regular fa-calendar"></i>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Online booking</p>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Tell us what needs fixing.</h1>
                </div>
            </div>

            <p class="text-sm text-slate-600 dark:text-slate-400 mb-8">
                Submit your repair request and we’ll have a technician ready to assess your phone when you arrive.
            </p>

            <!-- Error Alert -->
            <?php if ($error): ?>
                <div class="p-4 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/50 rounded-xl text-red-700 dark:text-red-400 text-sm text-center mb-6">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="request.php" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <!-- Full name / Email -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="customer_name" class="<?= $labelCls ?>">Full name</label>
                        <input type="text" id="customer_name" name="customer_name" value="<?= e($_POST['customer_name'] ?? '') ?>" required placeholder="e.g. Jordan Lee" class="<?= $inputCls ?>">
                    </div>
                    <div>
                        <label for="email" class="<?= $labelCls ?>">Email address</label>
                        <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required placeholder="you@email.com" class="<?= $inputCls ?>">
                    </div>
                </div>

                <!-- Phone number / Phone brand -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="phone" class="<?= $labelCls ?>">Phone number</label>
                        <input type="text" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" required placeholder="(415) 555-0120" class="<?= $inputCls ?>">
                    </div>
                    <div>
                        <label for="device_brand" class="<?= $labelCls ?>">Phone brand</label>
                        <input type="text" id="device_brand" name="device_brand" value="<?= e($_POST['device_brand'] ?? '') ?>" required placeholder="e.g. Apple, Samsung" class="<?= $inputCls ?>">
                    </div>
                </div>

                <!-- Phone model / Repair type -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="device_model" class="<?= $labelCls ?>">Phone model</label>
                        <input type="text" id="device_model" name="device_model" value="<?= e($_POST['device_model'] ?? '') ?>" required placeholder="e.g. iPhone 15 Pro" class="<?= $inputCls ?>">
                    </div>
                    <div>
                        <label for="service_id" class="<?= $labelCls ?>">Repair type</label>
                        <select id="service_id" name="service_id" required class="<?= $selectCls ?>">
                            <option value="" disabled <?= $selSvc === '' ? 'selected' : '' ?> class="text-slate-400">Select a repair</option>
                            <?php foreach ($services as $s): $sid = (int) $s['service_id']; ?>
                                <option value="<?= $sid ?>" class="<?= $optCls ?>" <?= $selSvc === (string) $sid ? 'selected' : '' ?>><?= e($s['service_name']) ?></option>
                            <?php endforeach; ?>
                            <option value="other" class="<?= $optCls ?>" <?= $selSvc === 'other' ? 'selected' : '' ?>>Other repair</option>
                        </select>
                    </div>
                </div>

                <!-- What is the problem? -->
                <div>
                    <label for="issue" class="<?= $labelCls ?>">What is the problem?</label>
                    <input type="text" id="issue" name="issue" value="<?= e($_POST['issue'] ?? '') ?>" required maxlength="255" placeholder="e.g. Cracked screen" class="<?= $inputCls ?>">
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="<?= $labelCls ?>">Notes</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Describe the issue, damage, or anything our technician should know." class="<?= $inputCls ?>"><?= e($_POST['notes'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition text-sm shadow-sm">
                    <span>Submit repair order</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
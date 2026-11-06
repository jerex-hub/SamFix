<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

start_secure_session();

$pageTitle = 'Request a Repair';
$fields    = ['customer_name', 'email', 'phone', 'device_brand', 'device_model', 'issue', 'notes', 'service_id'];
$old       = array_fill_keys($fields, '');
$errors    = [];
$formError = '';
$services  = [];

$confirmedCode = null;
if (isset($_GET['done']) && !empty($_SESSION['last_request_code'])) {
    $confirmedCode = $_SESSION['last_request_code'];
    unset($_SESSION['last_request_code']);
}

try {
    $services = $pdo->query('SELECT service_id, service_name FROM services ORDER BY service_name')->fetchAll();
} catch (PDOException $e) {
    error_log('request.php services query failed: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $f) {
        $v = $_POST[$f] ?? '';
        $old[$f] = is_string($v) ? trim($v) : '';
    }

    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $formError = 'Session expired, please try again.';
    } else {
        $errors = validate_request_form($old);

        $serviceId = null;
        if ($old['service_id'] !== '') {
            $validIds = array_map('intval', array_column($services, 'service_id'));
            if (!ctype_digit($old['service_id']) || !in_array((int) $old['service_id'], $validIds, true)) {
                $errors['service_id'] = 'Please choose a valid service.';
            } else {
                $serviceId = (int) $old['service_id'];
            }
        }

        if (!$errors) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO repair_requests
                        (request_code, service_id, customer_name, email, phone,
                         device_brand, device_model, issue, notes)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    'TMP-' . bin2hex(random_bytes(8)),   // temporary code, replaced below
                    $serviceId,
                    $old['customer_name'],
                    $old['email'],
                    normalize_phone($old['phone']),
                    $old['device_brand'],
                    $old['device_model'],
                    $old['issue'],
                    $old['notes'] !== '' ? $old['notes'] : null,
                ]);

                $id   = (int) $pdo->lastInsertId();
                $code = generate_request_code($id);

                $upd = $pdo->prepare('UPDATE repair_requests SET request_code = ? WHERE request_id = ?');
                $upd->execute([$code, $id]);

                $pdo->commit();

                $_SESSION['last_request_code'] = $code;
                unset($_SESSION['csrf_token']);   // new token for the next form
                header('Location: ' . BASE_URL . '/request.php?done=1');
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('request.php save failed: ' . $e->getMessage());
                $formError = 'Sorry, we could not save your request. Please try again.';
            }
        }
    }
}

$inputClass = 'w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500';

require __DIR__ . '/includes/header.php';
?>

<?php if ($confirmedCode): ?>
    <div class="max-w-xl mx-auto bg-white rounded-lg shadow p-8 text-center">
        <h1 class="text-2xl font-bold text-green-600 mb-2">Request submitted!</h1>
        <p class="text-gray-600 mb-4">Keep your Request ID to track your repair.</p>
        <p class="text-3xl font-mono font-bold bg-gray-100 rounded-lg py-3 mb-6"><?= e($confirmedCode) ?></p>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="<?= BASE_URL ?>/track.php?search=<?= urlencode($confirmedCode) ?>"
               class="bg-blue-600 text-white px-5 py-2 rounded-lg font-medium hover:bg-blue-700">Track this repair</a>
            <a href="<?= BASE_URL ?>/index.php"
               class="border border-gray-300 px-5 py-2 rounded-lg font-medium hover:bg-gray-100">Back to services</a>
        </div>
    </div>

<?php else: ?>
    <div class="max-w-xl mx-auto">
        <h1 class="text-3xl font-bold mb-1">Request a Repair</h1>
        <p class="text-gray-600 mb-6">Fill in the form and we will get started on your device.</p>

        <?php if ($formError): ?>
            <p class="bg-red-100 text-red-700 p-4 rounded-lg mb-4"><?= e($formError) ?></p>
        <?php elseif ($errors): ?>
            <p class="bg-red-100 text-red-700 p-4 rounded-lg mb-4">Please fix the errors below.</p>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/request.php" class="bg-white rounded-lg shadow p-6 space-y-4" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div>
                <label for="customer_name" class="block font-medium mb-1">Full name *</label>
                <input type="text" id="customer_name" name="customer_name" maxlength="100"
                       value="<?= e($old['customer_name']) ?>" class="<?= $inputClass ?>">
                <?= field_error($errors, 'customer_name') ?>
            </div>

            <div>
                <label for="email" class="block font-medium mb-1">Email *</label>
                <input type="email" id="email" name="email" maxlength="100"
                       value="<?= e($old['email']) ?>" class="<?= $inputClass ?>">
                <?= field_error($errors, 'email') ?>
            </div>

            <div>
                <label for="phone" class="block font-medium mb-1">Phone number *</label>
                <input type="text" id="phone" name="phone" maxlength="25" placeholder="e.g. 0917 123 4567"
                       value="<?= e($old['phone']) ?>" class="<?= $inputClass ?>">
                <?= field_error($errors, 'phone') ?>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="device_brand" class="block font-medium mb-1">Device brand *</label>
                    <input type="text" id="device_brand" name="device_brand" maxlength="50" placeholder="e.g. Samsung"
                           value="<?= e($old['device_brand']) ?>" class="<?= $inputClass ?>">
                    <?= field_error($errors, 'device_brand') ?>
                </div>
                <div>
                    <label for="device_model" class="block font-medium mb-1">Device model *</label>
                    <input type="text" id="device_model" name="device_model" maxlength="50" placeholder="e.g. Galaxy A54"
                           value="<?= e($old['device_model']) ?>" class="<?= $inputClass ?>">
                    <?= field_error($errors, 'device_model') ?>
                </div>
            </div>

            <div>
                <label for="service_id" class="block font-medium mb-1">Service (optional)</label>
                <select id="service_id" name="service_id" class="<?= $inputClass ?>">
                    <option value="">Not sure / other</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= (int) $s['service_id'] ?>"
                            <?= $old['service_id'] === (string) $s['service_id'] ? 'selected' : '' ?>>
                            <?= e($s['service_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'service_id') ?>
            </div>

            <div>
                <label for="issue" class="block font-medium mb-1">What is the problem? *</label>
                <input type="text" id="issue" name="issue" maxlength="255" placeholder="e.g. Cracked screen"
                       value="<?= e($old['issue']) ?>" class="<?= $inputClass ?>">
                <?= field_error($errors, 'issue') ?>
            </div>

            <div>
                <label for="notes" class="block font-medium mb-1">Additional notes (optional)</label>
                <textarea id="notes" name="notes" rows="4" maxlength="1000"
                          class="<?= $inputClass ?>"><?= e($old['notes']) ?></textarea>
                <?= field_error($errors, 'notes') ?>
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg font-medium hover:bg-blue-700">
                Submit Request
            </button>
        </form>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
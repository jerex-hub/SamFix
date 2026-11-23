<?php
// admin/requests.php: SamFix design wired to the samfix database.
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

start_secure_session();
if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

$adminId = !empty($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
$self    = strtok($_SERVER['REQUEST_URI'], '?');

function csrf(): string { return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">'; }
function initials(string $n): string { $p = preg_split('/\s+/', trim($n)) ?: []; return strtoupper(($p[0][0] ?? '') . ($p[1][0] ?? '')) ?: '?'; }
function hue(string $s): int { return crc32(strtolower($s)) % 360; }
function av(string $name, string $size = 'w-8 h-8 text-[11px]'): string {
    $h = hue($name);
    return '<span class="' . $size . ' rounded-full font-semibold flex items-center justify-center shrink-0" style="background:hsl(' . $h . ' 70% 50% / .16);color:hsl(' . $h . ' 70% 48%)">' . e(initials($name)) . '</span>';
}
function badge_class(string $s): string {
    return match ($s) { 'Completed' => 'g', 'In Progress' => 'b', 'Pending' => 'a', default => 's' };
}
function tech_options(array $techs, $sel = null, bool $all = false): string {
    $h = '<option value="">Unassigned</option>';
    foreach ($techs as $t) {
        $id = (int)$t['technician_id'];
        if (!$t['is_active'] && !$all && $id !== (int)$sel) { continue; }
        $h .= '<option value="' . $id . '"' . ($id === (int)$sel ? ' selected' : '') . '>' . e($t['full_name']) . ($t['is_active'] ? '' : ' (inactive)') . '</option>';
    }
    return $h;
}
function csv_safe($v): string { $v = (string)$v; return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v; }

// ---------- CSV export ----------
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="SamFix_repairs_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Request Code', 'Customer', 'Email', 'Phone', 'Device', 'Service', 'Issue', 'Status', 'Base Price', 'Date Added']);
    $rows = $pdo->query('SELECT r.request_code, r.customer_name, r.email, r.phone, r.device_brand, r.device_model, r.issue, r.status, r.created_at, s.service_name, s.base_price
                         FROM repair_requests r LEFT JOIN services s ON r.service_id = s.service_id ORDER BY r.created_at DESC')->fetchAll();
    foreach ($rows as $r) {
        fputcsv($out, array_map('csv_safe', [$r['request_code'], $r['customer_name'], $r['email'], $r['phone'],
            trim($r['device_brand'] . ' ' . $r['device_model']), $r['service_name'] ?? '', $r['issue'], $r['status'], $r['base_price'] ?? 0, $r['created_at']]));
    }
    fclose($out);
    exit;
}

// ---------- Services ----------
$services = [];
try { $services = $pdo->query('SELECT service_id, service_name, base_price FROM services ORDER BY service_name')->fetchAll(); }
catch (PDOException $e) { error_log('requests.php services: ' . $e->getMessage()); }

// ---------- POST actions (CSRF-checked, then redirect) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $msg = '';
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        $msg = 'Your session expired. Reload the page and try again.';
    } else {
        $action = (string)$_POST['action'];
        $status = in_array($_POST['status'] ?? '', STATUSES, true) ? $_POST['status'] : 'Pending';
        $svcId  = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
        $techId = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
        $cost   = is_numeric($_POST['estimated_cost'] ?? '') ? max(0, round((float)$_POST['estimated_cost'], 2)) : null;
        try {
            if ($action === 'new_repair') {
                $f = [];
                foreach (['customer_name', 'email', 'phone', 'device_brand', 'device_model', 'issue'] as $k) { $f[$k] = trim((string)($_POST[$k] ?? '')); }
                if (in_array('', $f, true) || !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
                    $msg = 'Fill in every field with a valid email address.';
                } else {
                    do {
                        $code = 'SFX-' . random_int(1000, 9999);
                        $chk = $pdo->prepare('SELECT 1 FROM repair_requests WHERE request_code = ? LIMIT 1');
                        $chk->execute([$code]);
                    } while ($chk->fetchColumn());
                    $pdo->prepare('INSERT INTO repair_requests (request_code, service_id, technician_id, estimated_cost, updated_by, customer_name, email, phone, device_brand, device_model, issue, notes, status)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                        ->execute([$code, $svcId, $techId, $cost, $adminId, $f['customer_name'], $f['email'], $f['phone'], $f['device_brand'], $f['device_model'], $f['issue'], null, $status]);
                    $msg = "Added repair {$code}.";
                }
            } elseif ($action === 'update_repair') {
                $code = trim((string)($_POST['request_code'] ?? ''));
                $notes = trim((string)($_POST['notes'] ?? ''));
                if ($code !== '') {
                    $pdo->prepare('UPDATE repair_requests SET status = ?, service_id = ?, technician_id = ?, estimated_cost = ?, notes = ?, updated_by = ? WHERE request_code = ?')
                        ->execute([$status, $svcId, $techId, $cost, $notes !== '' ? $notes : null, $adminId, $code]);
                    $msg = "Updated repair {$code}.";
                }
            } elseif ($action === 'delete_repair') {
                $code = trim((string)($_POST['request_code'] ?? ''));
                if ($code !== '') {
                    $pdo->prepare('DELETE FROM repair_requests WHERE request_code = ?')->execute([$code]);
                    $msg = "Deleted repair {$code}.";
                }
            } elseif ($action === 'assign_technician') {
                $code = trim((string)($_POST['request_code'] ?? ''));
                if ($code !== '') {
                    $pdo->prepare('UPDATE repair_requests SET technician_id = ?, updated_by = ? WHERE request_code = ?')->execute([$techId, $adminId, $code]);
                    $msg = "Updated the technician for {$code}.";
                }
            } elseif ($action === 'add_technician') {
                $n = trim((string)($_POST['name'] ?? ''));
                $m = trim((string)($_POST['email'] ?? ''));
                if ($n === '' || !filter_var($m, FILTER_VALIDATE_EMAIL)) {
                    $msg = 'Enter the technician’s name and a valid email address.';
                } else {
                    $pdo->prepare('INSERT INTO technicians (full_name, email) VALUES (?, ?)')->execute([$n, $m]);
                    $msg = "Added technician {$n}.";
                }
            } elseif ($action === 'toggle_technician') {
                if ($techId) {
                    $pdo->prepare('UPDATE technicians SET is_active = 1 - is_active WHERE technician_id = ?')->execute([$techId]);
                    $msg = 'Technician updated.';
                }
            } elseif ($action === 'add_admin') {
                $u = trim((string)($_POST['name'] ?? $_POST['username'] ?? ''));
                $p = (string)($_POST['password'] ?? '');
                if ($u === '' || strlen($p) < 8) {
                    $msg = 'Enter a username and a password of at least 8 characters.';
                } else {
                    try {
                        $pdo->prepare('INSERT INTO administrators (username, password_hash) VALUES (?, ?)')->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
                        $msg = "Added administrator {$u}.";
                    } catch (PDOException $e) {
                        $msg = 'Could not add administrator. That username may already exist.';
                    }
                }
            }
        } catch (PDOException $e) {
            error_log('requests.php action ' . $action . ': ' . $e->getMessage());
            $msg = 'Something went wrong while saving. Check the server log.';
        }
    }
    $_SESSION['toast'] = $msg;
    header('Location: ' . $self);
    exit;
}
$toast = (string)($_SESSION['toast'] ?? '');
unset($_SESSION['toast']);

// ---------- Load data ----------
$schemaOk = true;
try { $pdo->query('SELECT technician_id, estimated_cost FROM repair_requests LIMIT 1'); $pdo->query('SELECT 1 FROM technicians LIMIT 1'); }
catch (PDOException $e) { $schemaOk = false; }

$jobs = [];
try {
    $rows = $pdo->query('SELECT r.request_code, r.service_id, r.technician_id, r.estimated_cost, r.customer_name, r.email, r.phone, r.device_brand, r.device_model, r.issue, r.notes, r.status, r.created_at,
                                s.service_name, s.base_price, a.username AS updated_by_user, t.full_name AS tech_name
                         FROM repair_requests r
                         LEFT JOIN services s ON r.service_id = s.service_id
                         LEFT JOIN administrators a ON r.updated_by = a.admin_id
                         LEFT JOIN technicians t ON r.technician_id = t.technician_id
                         ORDER BY r.created_at DESC')->fetchAll();
    foreach ($rows as $r) {
        $ts = !empty($r['created_at']) ? strtotime((string)$r['created_at']) : time();
        $jobs[] = [
            'request_code' => (string)$r['request_code'],
            'service_id'   => $r['service_id'] !== null ? (int)$r['service_id'] : '',
            'customer'     => (string)$r['customer_name'],
            'email'        => (string)$r['email'],
            'phone'        => (string)$r['phone'],
            'device'       => trim($r['device_brand'] . ' ' . $r['device_model']),
            'issue'        => (string)$r['issue'],
            'notes'        => (string)($r['notes'] ?? ''),
            'status'       => (string)$r['status'],
            'by'           => (string)($r['updated_by_user'] ?? 'Unassigned'),
            'tech_id'      => $r['technician_id'] !== null ? (int)$r['technician_id'] : '',
            'tech'         => (string)($r['tech_name'] ?? 'Unassigned'),
            'cost'         => $r['estimated_cost'] !== null ? (float)$r['estimated_cost'] : null,
            'price'        => $r['estimated_cost'] !== null ? (float)$r['estimated_cost'] : (float)($r['base_price'] ?? 0),
            'date'         => date('M d, Y', $ts),
            'ts'           => $ts,
        ];
    }
} catch (PDOException $e) { error_log('requests.php repairs: ' . $e->getMessage()); }

$admins = [];
try { $admins = $pdo->query('SELECT admin_id, username FROM administrators ORDER BY admin_id ASC')->fetchAll(); }
catch (PDOException $e) { error_log('requests.php admins: ' . $e->getMessage()); }

$techs = [];
try { $techs = $pdo->query('SELECT technician_id, full_name, email, is_active FROM technicians ORDER BY full_name')->fetchAll(); }
catch (PDOException $e) { error_log('requests.php technicians: ' . $e->getMessage()); }
$techLoad = [];
foreach ($jobs as $j) {
    if ($j['tech_id'] && in_array($j['status'], ['Pending', 'In Progress'], true)) { $techLoad[$j['tech_id']] = ($techLoad[$j['tech_id']] ?? 0) + 1; }
}
$teamCount = count($admins) + count(array_filter($techs, fn($t) => $t['is_active']));

$customers = [];
foreach ($jobs as $j) {
    $k = strtolower($j['email'] ?: $j['phone']);
    if (!isset($customers[$k])) { $customers[$k] = $j + ['count' => 0]; }
    $customers[$k]['count']++;
}

$total   = count($jobs);
$cnt     = array_count_values(array_column($jobs, 'status'));
$pending = $cnt['Pending'] ?? 0;
$inprog  = $cnt['In Progress'] ?? 0;
$done    = $cnt['Completed'] ?? 0;
$active  = $pending + $inprog;
$value   = array_sum(array_column($jobs, 'price'));
$adminName = 'Admin';
foreach ($admins as $a) { if ((int)$a['admin_id'] === $adminId) { $adminName = $a['username']; } }

$weekStart = strtotime('monday this week 00:00');
$days = ['Mon' => 0, 'Tue' => 0, 'Wed' => 0, 'Thu' => 0, 'Fri' => 0, 'Sat' => 0, 'Sun' => 0];
$weekDone = 0;
foreach ($jobs as $j) {
    if ($j['status'] === 'Completed' && $j['ts'] >= $weekStart) { $days[date('D', $j['ts'])]++; $weekDone++; }
}
$maxDay = max(1, max($days));
$today = date('D');

// ---------- Reusable pieces ----------
function render_table(array $jobs, int $active, string $p): void { ?>
<div id="t-<?= $p ?>" class="card rounded-xl border overflow-hidden" data-f="ALL" data-q="">
  <div class="p-5 sm:p-6 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2.5"><h2 class="text-sm font-semibold t1">Repair jobs</h2><span class="pill text-[11px] font-medium px-2.5 py-0.5 rounded-full"><?= count($jobs) ?> repairs</span></div>
      <p class="text-xs t2 mt-1">Stay on top of your repair queue.</p>
    </div>
    <a href="?export=csv" class="btn-o inline-flex items-center gap-2 text-xs font-medium px-3.5 py-2 rounded-lg self-start"><i data-lucide="download" class="w-3.5 h-3.5"></i>Export</a>
  </div>
  <div class="px-5 sm:px-6 pb-4 flex flex-col xl:flex-row xl:items-center justify-between gap-3">
    <div class="sub bd border inline-flex flex-wrap p-1 rounded-lg gap-1 self-start">
      <?php foreach (['ALL' => 'All repairs', 'ACTIVE' => 'Active', 'Pending' => 'Pending', 'In Progress' => 'In progress', 'Completed' => 'Completed'] as $k => $l): ?>
      <button data-f="<?= $k ?>" class="ftab px-3 py-1.5 text-xs font-medium rounded-md flex items-center gap-1.5 <?= $k === 'ALL' ? 'on' : '' ?>"><?= $l ?><?php if ($k === 'ACTIVE'): ?><span class="pill text-[10px] px-1.5 rounded-full"><?= $active ?></span><?php endif; ?></button>
      <?php endforeach; ?>
    </div>
    <div class="relative w-full sm:w-64">
      <i data-lucide="search" class="w-3.5 h-3.5 t3 absolute left-3 top-2.5"></i>
      <input type="search" data-search placeholder="Search repairs..." class="inp w-full border pl-8 pr-4 py-1.5 text-xs rounded-lg">
    </div>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[800px]">
      <thead><tr class="sub bd border-y text-[11px] font-medium t2">
        <th class="py-3 pl-6 pr-3 w-10"><input type="checkbox" data-all></th>
        <th class="py-3 px-4">Ticket ID</th><th class="py-3 px-4">Customer</th><th class="py-3 px-4">Device / Issue</th>
        <th class="py-3 px-4">Status</th><th class="py-3 px-4">Technician</th><th class="py-3 px-4">Date added</th><th class="py-3 pr-6 pl-3 text-right">Actions</th>
      </tr></thead>
      <tbody class="text-xs">
      <?php if (!$jobs): ?>
        <tr><td colspan="8" class="py-12 px-6 text-center t2">No repairs yet. Add one with “New repair”, or wait for a booking.</td></tr>
      <?php else: foreach ($jobs as $j): ?>
        <tr class="row bd border-b" data-status="<?= e($j['status']) ?>" data-s="<?= e(strtolower($j['request_code'] . ' ' . $j['customer'] . ' ' . $j['email'] . ' ' . $j['phone'] . ' ' . $j['device'] . ' ' . $j['issue'] . ' ' . $j['tech'])) ?>">
          <td class="py-3.5 pl-6 pr-3"><input type="checkbox" class="rcb"></td>
          <td class="py-3.5 px-4 font-semibold acct"><button type="button" class="hover:underline" data-edit="<?= e(json_encode($j)) ?>"><?= e($j['request_code']) ?></button></td>
          <td class="py-3.5 px-4"><div class="flex items-center gap-3"><?= av($j['customer']) ?><div><p class="font-medium t1"><?= e($j['customer']) ?></p><p class="text-[11px] t3"><?= e($j['phone']) ?> · <?= e($j['email']) ?></p></div></div></td>
          <td class="py-3.5 px-4"><p class="font-medium t1"><?= e($j['device']) ?></p><p class="text-[11px] t3"><?= e($j['issue']) ?></p></td>
          <td class="py-3.5 px-4"><span class="bdg <?= badge_class($j['status']) ?>"><?= e($j['status']) ?></span></td>
          <td class="py-3.5 px-4"><?php if ($j['tech_id']): ?><div class="flex items-center gap-2"><?= av($j['tech'], 'w-6 h-6 text-[10px]') ?><span class="t1"><?= e($j['tech']) ?></span></div><?php else: ?><span class="t3">Unassigned</span><?php endif; ?></td>
          <td class="py-3.5 px-4 t2"><?= e($j['date']) ?></td>
          <td class="py-3.5 pr-6 pl-3 text-right whitespace-nowrap">
            <button type="button" aria-label="Edit <?= e($j['request_code']) ?>" data-edit="<?= e(json_encode($j)) ?>" class="ico p-1.5 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></button>
            <form method="POST" class="inline" onsubmit="return confirm('Delete <?= e($j['request_code']) ?>? This cannot be undone.')">
              <?= csrf() ?><input type="hidden" name="action" value="delete_repair"><input type="hidden" name="request_code" value="<?= e($j['request_code']) ?>">
              <button type="submit" aria-label="Delete <?= e($j['request_code']) ?>" class="ico del p-1.5 rounded-lg"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
        <tr data-empty class="hidden"><td colspan="8" class="py-10 text-center t2">No repairs match your filters.</td></tr>
      </tbody>
    </table>
  </div>
  <div class="px-6 py-3.5 text-xs t2">Showing <strong class="t1" data-shown><?= count($jobs) ?></strong> of <strong class="t1"><?= count($jobs) ?></strong> repairs</div>
</div>
<?php }

function render_team(array $admins, array $techs, array $load): void { ?>
<div class="card rounded-xl border p-5 sm:p-6">
  <div class="flex items-start justify-between mb-6">
    <div><h2 class="text-sm font-semibold t1">User management</h2><p class="text-xs t2 mt-1">Manage administrators and the technicians who work on repairs.</p></div>
    <span class="pill text-[11px] font-medium px-2.5 py-1 rounded-md">Admin view</span>
  </div>
  <div class="mb-6">
    <?php foreach ($admins as $a): ?>
    <div class="bd border-b py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-3"><?= av($a['username'], 'w-9 h-9 text-xs') ?><div><p class="text-xs font-semibold t1"><?= e($a['username']) ?></p><p class="text-[11px] t2">Administrator #<?= (int)$a['admin_id'] ?></p></div></div>
      <div class="flex items-center gap-2"><span class="pill text-[11px] font-medium px-2.5 py-1 rounded-full">Administrator</span><span class="bdg g">Active</span></div>
    </div>
    <?php endforeach; ?>
    <?php foreach ($techs as $t): $n = $load[(int)$t['technician_id']] ?? 0; ?>
    <div class="bd border-b py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center gap-3"><?= av($t['full_name'], 'w-9 h-9 text-xs') ?><div><p class="text-xs font-semibold t1"><?= e($t['full_name']) ?></p><p class="text-[11px] t2"><?= e($t['email']) ?> · <?= $n ?> active <?= $n === 1 ? 'repair' : 'repairs' ?></p></div></div>
      <div class="flex items-center gap-2">
        <span class="sub bd border t1 text-[11px] font-medium px-2.5 py-1 rounded-full">Technician</span>
        <span class="bdg <?= $t['is_active'] ? 'g' : 's' ?>"><?= $t['is_active'] ? 'Active' : 'Inactive' ?></span>
        <form method="POST"><?= csrf() ?><input type="hidden" name="action" value="toggle_technician"><input type="hidden" name="technician_id" value="<?= (int)$t['technician_id'] ?>">
          <button class="btn-o px-3 py-1.5 rounded-lg text-xs font-medium"><?= $t['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
      </div>
    </div>
    <?php endforeach; ?>
    </div>

    <h3 class="text-xs font-semibold t1 mb-3">Add a technician</h3>
  <form method="POST" class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3 mb-6">
    <?= csrf() ?><input type="hidden" name="action" value="add_technician">
    <input type="text" name="name" required maxlength="100" placeholder="Full name" autocomplete="off" class="inp border px-3.5 py-2 text-xs rounded-lg">
    <input type="email" name="email" required placeholder="Email address" class="inp border px-3.5 py-2 text-xs rounded-lg">
    <button type="submit" class="btn-p inline-flex items-center justify-center gap-1.5 text-xs font-medium px-4 py-2 rounded-lg">Add technician <i data-lucide="plus" class="w-3.5 h-3.5"></i></button>
  </form>

  <h3 class="text-xs font-semibold t1 mb-3">Add an administrator</h3>
  <form method="POST" class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3">
    <?= csrf() ?><input type="hidden" name="action" value="add_admin">
    <input type="text" name="name" required maxlength="50" placeholder="Username" autocomplete="off" class="inp border px-3.5 py-2 text-xs rounded-lg">
    <input type="password" name="password" required minlength="8" placeholder="Password (8+ characters)" autocomplete="new-password" class="inp border px-3.5 py-2 text-xs rounded-lg">
    <button type="submit" class="btn-p inline-flex items-center justify-center gap-1.5 text-xs font-medium px-4 py-2 rounded-lg">Add administrator <i data-lucide="plus" class="w-3.5 h-3.5"></i></button>
  </form>
</div>
<?php }

function head(string $title, string $sub, string $btn = 'new'): void { ?>
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
  <div><p class="text-[10px] font-semibold tracking-wider t3 uppercase mb-1">Administration · <span data-ws>SamFix Service Center</span></p>
    <h1 class="text-2xl font-bold t1"><?= $title ?></h1><p class="text-xs t2 mt-1"><?= $sub ?></p></div>
  <?php if ($btn === 'new'): ?><button onclick="openModal('new-modal')" class="btn-p inline-flex items-center justify-center gap-2 text-xs font-medium px-4 py-2.5 rounded-lg self-start sm:self-auto"><i data-lucide="plus" class="w-4 h-4"></i>New repair</button>
  <?php elseif ($btn === 'export'): ?><a href="?export=csv" class="btn-p inline-flex items-center justify-center gap-2 text-xs font-medium px-4 py-2.5 rounded-lg self-start sm:self-auto"><i data-lucide="download" class="w-4 h-4"></i>Export report</a><?php endif; ?>
</div>
<?php }

function kpi(string $label, $value, string $note, string $icon, string $tone): void { ?>
<div class="card p-5 rounded-xl border">
  <div class="flex items-center justify-between mb-3"><span class="text-xs font-medium t2"><?= $label ?></span>
    <span class="tone-<?= $tone ?> w-8 h-8 rounded-lg flex items-center justify-center"><i data-lucide="<?= $icon ?>" class="w-4 h-4"></i></span></div>
  <p class="text-3xl font-bold t1"><?= $value ?></p><p class="text-[11px] t3 mt-1"><?= $note ?></p>
</div>
<?php }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin dashboard - SamFix.</title>
<script>try{if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark')}catch(e){}</script>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--app:#F5F7FB;--side:#fff;--card:#fff;--sub:#F3F6FA;--inp:#fff;--bd:#E4E9F1;--t1:#0F172A;--t2:#5B6B82;--t3:#94A3B8;--acc:#2F5FF0;--accs:#E6EDFE;--acct:#2455E6;--hov:#F3F6FA;--banner:#E9F0FE;--bannerbd:#D3E0FC;--shadow:0 1px 2px rgba(15,23,42,.04);--modal:rgba(15,23,42,.45)}
html.dark{--app:#0E1525;--side:#121A2B;--card:#151E30;--sub:#182236;--inp:#101726;--bd:#1F2A3F;--t1:#F1F5F9;--t2:#94A3B8;--t3:#64748B;--acc:#2563EB;--accs:#1D2D49;--acct:#60A5FA;--hov:#182236;--banner:#18253C;--bannerbd:#1E3256;--shadow:none;--modal:rgba(0,0,0,.6);color-scheme:dark}
body{font-family:'Inter',sans-serif;background:var(--app);color:var(--t1)}
.side,.top{background:var(--side);border-color:var(--bd)}.card{background:var(--card);border-color:var(--bd);box-shadow:var(--shadow)}
.sub{background:var(--sub)}.bd{border-color:var(--bd)}.t1{color:var(--t1)}.t2{color:var(--t2)}.t3{color:var(--t3)}.acct{color:var(--acct)}
.pill{background:var(--accs);color:var(--acct)}.banner{background:var(--banner);border:1px solid var(--bannerbd)}
.inp{background:var(--inp);border-color:var(--bd);color:var(--t1)}.inp:focus{outline:2px solid var(--acc);outline-offset:-1px}
.inp::placeholder{color:var(--t3)}
.btn-p{background:var(--acc);color:#fff}.btn-p:hover{filter:brightness(1.1)}
.btn-o{background:var(--card);border:1px solid var(--bd);color:var(--t1)}.btn-o:hover{background:var(--hov)}
.nav{color:var(--t2)}.nav:hover{background:var(--hov);color:var(--t1)}.nav.on{background:var(--accs);color:var(--acct)}
.ftab{color:var(--t2)}.ftab.on{background:var(--card);color:var(--t1);box-shadow:var(--shadow)}.ftab:hover{color:var(--t1)}
.row:hover{background:var(--hov)}.ico{color:var(--t3)}.ico:hover{background:var(--sub);color:var(--t1)}.ico.del:hover{color:#e11d48}
button,.btn-p,.btn-o,.nav{cursor:pointer}:focus-visible{outline:2px solid var(--acc);outline-offset:2px}
.bdg{display:inline-flex;align-items:center;gap:6px;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:500;border:1px solid}
.bdg::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}
.g{background:#DCFCE7;color:#15803D;border-color:#BBF7D0}.b{background:#DBEAFE;color:#1D4ED8;border-color:#BFDBFE}.a{background:#FEF3C7;color:#B45309;border-color:#FDE68A}.s{background:#F1F5F9;color:#475569;border-color:#E2E8F0}
html.dark .g{background:#06281fb3;color:#34d399;border-color:#065f4666}html.dark .b{background:#0f2147b3;color:#60a5fa;border-color:#1e3a8a66}
html.dark .a{background:#33210ab3;color:#fbbf24;border-color:#78350f66}html.dark .s{background:#1e293be6;color:#cbd5e1;border-color:#334155}
.tone-blue{background:#E6EDFE;color:#2455E6}.tone-amber{background:#FFF1DC;color:#D97706}.tone-green{background:#DDF5E8;color:#16A34A}.tone-purple{background:#EEE6FD;color:#7C3AED}
html.dark .tone-blue{background:#172554b3;color:#60a5fa}html.dark .tone-amber{background:#451a03b3;color:#fbbf24}html.dark .tone-green{background:#052e16b3;color:#34d399}html.dark .tone-purple{background:#3b0764b3;color:#c084fc}
.modal{background:var(--modal);backdrop-filter:blur(3px)}.bar{background:var(--acc)}.bar.z{background:var(--sub)}
::-webkit-scrollbar{width:6px;height:6px}::-webkit-scrollbar-thumb{background:var(--bd);border-radius:9999px}
</style>
</head>
<body class="flex h-screen overflow-hidden antialiased">

<div id="backdrop" onclick="toggleSidebar()" class="modal fixed inset-0 z-30 hidden lg:hidden"></div>

<aside id="sidebar" class="side border-r fixed inset-y-0 left-0 z-40 w-[260px] flex flex-col justify-between overflow-y-auto -translate-x-full lg:translate-x-0 lg:static transition-transform duration-200">
  <div class="p-5">
    <div class="flex items-center justify-between px-1 mb-6">
      <div class="flex items-center gap-2.5"><span class="btn-p w-8 h-8 rounded-lg flex items-center justify-center"><i data-lucide="wrench" class="w-4 h-4"></i></span>
        <span class="text-xl font-bold tracking-tight t1">SamFix<span class="acct">.</span></span></div>
      <button onclick="toggleSidebar()" class="lg:hidden t2 p-1" aria-label="Close menu"><i data-lucide="x" class="w-5 h-5"></i></button>
    </div>
    <div class="card border rounded-xl p-2.5 mb-6 flex items-center gap-2.5">
      <span class="sub bd border w-8 h-8 rounded-lg flex items-center justify-center t2"><i data-lucide="box" class="w-4 h-4"></i></span>
      <div class="min-w-0"><p class="text-xs font-semibold t1 truncate" data-ws>SamFix Service Center</p><p class="text-[11px] t2">Admin workspace</p></div>
    </div>
    <p class="px-3 text-[10px] font-semibold t3 uppercase tracking-wider mb-2">Workspace</p>
    <nav class="space-y-1 mb-6">
      <button data-tab="dashboard" data-label="Admin dashboard" class="nav on w-full flex items-center gap-3 px-3 py-2 text-xs font-medium rounded-lg"><i data-lucide="shield-check" class="w-4 h-4"></i>Admin dashboard</button>
      <button data-tab="repairs" data-label="Repair jobs" class="nav w-full flex items-center justify-between px-3 py-2 text-xs font-medium rounded-lg"><span class="flex items-center gap-3"><i data-lucide="wrench" class="w-4 h-4"></i>Repair jobs</span><span class="pill text-[11px] font-semibold px-2 py-0.5 rounded-full"><?= $total ?></span></button>
      <button data-tab="customers" data-label="Customers" class="nav w-full flex items-center gap-3 px-3 py-2 text-xs font-medium rounded-lg"><i data-lucide="users" class="w-4 h-4"></i>Customers</button>
      <button data-tab="reports" data-label="Reports" class="nav w-full flex items-center gap-3 px-3 py-2 text-xs font-medium rounded-lg"><i data-lucide="bar-chart-2" class="w-4 h-4"></i>Reports</button>
    </nav>
    <p class="px-3 text-[10px] font-semibold t3 uppercase tracking-wider mb-2">Manage</p>
    <nav class="space-y-1">
      <button data-tab="team" data-label="Team members" class="nav w-full flex items-center gap-3 px-3 py-2 text-xs font-medium rounded-lg"><i data-lucide="users-round" class="w-4 h-4"></i>Team members</button>
      <button data-tab="settings" data-label="Settings" class="nav w-full flex items-center gap-3 px-3 py-2 text-xs font-medium rounded-lg"><i data-lucide="settings" class="w-4 h-4"></i>Settings</button>
    </nav>
  </div>
  <div class="p-5 space-y-4">
    <div class="sub bd border rounded-xl p-4">
      <span class="pill w-7 h-7 rounded-full flex items-center justify-center mb-3"><i data-lucide="help-circle" class="w-4 h-4"></i></span>
      <p class="text-xs font-semibold t1 mb-1">A little help goes a long way.</p>
      <p class="text-[11px] t2 mb-3">Our team is here to support yours.</p>
      <button onclick="openModal('help-modal')" class="acct inline-flex items-center gap-1.5 text-xs font-semibold hover:underline">Visit help center <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></button>
    </div>
    <div class="bd border-t pt-3 flex items-center justify-between px-1">
      <button onclick="openModal('help-modal')" class="t2 flex items-center gap-2 text-xs font-medium hover:opacity-80"><i data-lucide="help-circle" class="w-4 h-4"></i>Help &amp; support</button>
      <a href="logout.php" title="Log out" aria-label="Log out" class="t2 hover:text-rose-500"><i data-lucide="log-out" class="w-4 h-4"></i></a>
    </div>
  </div>
</aside>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
  <header class="top border-b h-16 px-4 sm:px-8 flex items-center justify-between shrink-0 gap-3">
    <div class="flex items-center gap-3 min-w-0">
      <button onclick="toggleSidebar()" class="btn-o lg:hidden p-2 rounded-lg" aria-label="Open menu"><i data-lucide="menu" class="w-4 h-4"></i></button>
      <div class="flex items-center gap-2 text-xs truncate"><span class="t2 hidden sm:inline">Administration</span><i data-lucide="chevron-right" class="w-3.5 h-3.5 t3 hidden sm:inline"></i><span id="crumb" class="font-medium t1 truncate">Admin dashboard</span></div>
    </div>
    <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
      <button id="theme-btn" onclick="toggleTheme()" class="btn-o w-8 h-8 rounded-lg flex items-center justify-center" aria-label="Toggle light and dark mode"><i data-lucide="moon" class="w-4 h-4"></i></button>
      <button onclick="openModal('track-modal')" class="t2 hidden md:flex items-center gap-2 text-xs font-medium px-3 py-1.5 rounded-lg hover:opacity-80"><i data-lucide="search" class="w-3.5 h-3.5"></i>Track a repair<i data-lucide="arrow-right" class="w-3.5 h-3.5"></i></button>
      <button onclick="goRepairs('Pending')" class="t2 w-8 h-8 rounded-lg flex items-center justify-center relative hover:opacity-80" aria-label="<?= $pending ?> pending repairs">
        <i data-lucide="bell" class="w-4 h-4"></i><?php if ($pending): ?><span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-rose-500"></span><?php endif; ?></button>
      <span class="w-8 h-8" title="<?= e($adminName) ?>"><?= av($adminName, 'w-8 h-8 text-xs') ?></span>
    </div>
  </header>

  <?php if ($toast !== ''): ?>
  <div id="toast" role="status" class="tone-green px-4 sm:px-8 py-2.5 text-xs flex items-center justify-between"><span><?= e($toast) ?></span>
    <button onclick="this.parentNode.remove()" aria-label="Dismiss"><i data-lucide="x" class="w-3.5 h-3.5"></i></button></div>
  <?php endif; ?>

  <main class="flex-1 overflow-y-auto p-4 sm:p-8">
    <?php if (!$schemaOk): ?>
    <div role="alert" class="tone-amber rounded-xl px-4 py-3 text-xs mb-6"><strong>Database update needed.</strong> Import <code>migration.sql</code> into the SamFix database (phpMyAdmin → Import), then reload this page.</div>
    <?php endif; ?>

    <section id="view-dashboard" class="tab-view space-y-6">
      <?php head('Admin dashboard', 'A complete view of your repairs, your team, and your business.'); ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <?php kpi('Repairs in the system', $total, 'Across all repair statuses', 'smartphone', 'blue');
              kpi('Active repairs', $active, 'Pending and in progress', 'wrench', 'amber');
              kpi('Estimated repair value', '₱' . number_format($value), 'Estimated cost, or service price if unset', 'bar-chart-3', 'green');
              kpi('Active team members', $teamCount, count($admins) . ' admin, ' . ($teamCount - count($admins)) . ' technician', 'users', 'purple'); ?>
      </div>
      <div class="banner rounded-xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5"><span class="card border w-10 h-10 rounded-xl flex items-center justify-center acct shrink-0"><i data-lucide="shield-check" class="w-5 h-5"></i></span>
          <div><h3 class="text-xs font-semibold t1">You're in control.</h3><p class="text-[11px] t2 mt-0.5">Monitor the queue, manage access, and keep your service center moving.</p></div></div>
        <div class="flex gap-2.5">
          <button data-tab="team" data-label="Team members" class="btn-o inline-flex items-center gap-2 text-xs font-medium px-3.5 py-2 rounded-lg"><i data-lucide="users" class="w-3.5 h-3.5"></i>Manage users</button>
          <a href="?export=csv" class="btn-o inline-flex items-center gap-2 text-xs font-medium px-3.5 py-2 rounded-lg"><i data-lucide="download" class="w-3.5 h-3.5"></i>Export repairs</a>
        </div>
      </div>
      <?php render_table($jobs, $active, 'dash'); render_team($admins, $techs, $techLoad); ?>
    </section>

    <section id="view-repairs" class="tab-view hidden space-y-6">
      <?php head('Repair jobs', 'Keep every repair moving, from check-in to pickup.'); ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <?php kpi('Total repairs', $total, 'All statuses', 'smartphone', 'blue');
              kpi('Pending', $pending, 'Waiting to start', 'clock', 'amber');
              kpi('In progress', $inprog, 'On the bench', 'wrench', 'blue');
              kpi('Completed', $done, 'Ready or picked up', 'check-circle-2', 'green'); ?>
      </div>
      <?php render_table($jobs, $active, 'rep'); ?>
    </section>

    <section id="view-customers" class="tab-view hidden space-y-6">
      <?php head('Customers', 'Great repairs start with great customer relationships.'); ?>
      <div class="card rounded-xl border p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <div><h2 class="text-sm font-semibold t1">Customer directory</h2><p class="text-xs t2 mt-0.5"><?= count($customers) ?> customers and their repair history</p></div>
          <div class="relative w-full sm:w-64"><i data-lucide="search" class="w-3.5 h-3.5 t3 absolute left-3 top-2.5"></i>
            <input type="search" id="cust-search" placeholder="Search customers..." class="inp w-full border pl-9 pr-4 py-2 text-xs rounded-lg"></div>
        </div>
        <?php if (!$customers): ?>
          <div class="bd border border-dashed rounded-xl text-center py-12 text-xs t2">No customers yet. Their cards appear here after the first repair is added.</div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          <?php foreach ($customers as $c): ?>
          <div data-cs="<?= e(strtolower($c['customer'] . ' ' . $c['email'] . ' ' . $c['phone'] . ' ' . $c['device'] . ' ' . $c['tech'])) ?>" class="cust sub bd border rounded-xl p-5 flex flex-col">
            <button type="button" data-edit="<?= e(json_encode($c)) ?>" class="text-left hover:opacity-90">
              <div class="mb-4 flex items-start justify-between"><?= av($c['customer'], 'w-9 h-9 text-xs') ?><span class="bdg <?= badge_class($c['status']) ?>"><?= e($c['status']) ?></span></div>
              <h3 class="text-sm font-semibold t1"><?= e($c['customer']) ?></h3>
              <p class="text-xs t2 mt-0.5 truncate"><?= e($c['email']) ?></p><p class="text-xs t2 mb-4"><?= e($c['phone']) ?></p>
              <div class="flex items-center justify-between text-xs t1 mb-4"><span><?= e($c['device']) ?></span><span class="t3"><?= $c['count'] ?> <?= $c['count'] === 1 ? 'repair' : 'repairs' ?></span></div>
            </button>
            <form method="POST" class="bd border-t pt-3 mt-auto">
              <?= csrf() ?><input type="hidden" name="action" value="assign_technician"><input type="hidden" name="request_code" value="<?= e($c['request_code']) ?>">
              <label class="text-[11px] t2 block mb-1">Technician for <?= e($c['request_code']) ?></label>
              <select name="technician_id" onchange="this.form.submit()" class="inp w-full border rounded-lg px-2.5 py-1.5 text-xs"><?= tech_options($techs, $c['tech_id']) ?></select>
            </form>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>

    <section id="view-reports" class="tab-view hidden space-y-6">
      <?php head('Reports', "A clear picture of your repair center's performance.", 'export'); ?>
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 card border rounded-xl p-6">
          <h2 class="text-sm font-semibold t1">Repair performance</h2>
          <p class="text-xs t2 mt-0.5 mb-6">Repairs added this week that are now completed.</p>
          <div class="flex flex-col sm:flex-row sm:items-end gap-8">
            <div class="shrink-0"><p class="text-3xl font-bold t1"><?= $weekDone ?></p><p class="text-xs t2 mt-1">completed this week</p></div>
            <div class="bd flex-1 grid grid-cols-7 gap-3 items-end h-36 pt-4 border-b pb-2">
              <?php foreach ($days as $d => $n): ?>
              <div class="flex flex-col items-center gap-2 h-full justify-end" title="<?= $n ?> completed">
                <div class="bar <?= $n ? '' : 'z' ?> w-full rounded-t-md <?= $d === $today && $n ? 'opacity-100' : ($n ? 'opacity-70' : '') ?>" style="height:<?= $n ? max(18, (int)round($n / $maxDay * 100)) : 4 ?>%"></div>
                <span class="text-[10px] <?= $d === $today ? 't1 font-medium' : 't3' ?>"><?= $d ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <p class="text-[11px] t3 pt-4"><?= date('M j', $weekStart) ?> – <?= date('M j, Y', strtotime('+6 days', $weekStart)) ?></p>
        </div>
        <div class="lg:col-span-5 card border rounded-xl p-6">
          <div class="flex items-start justify-between mb-6"><div><h2 class="text-sm font-semibold t1">Recent activity</h2><p class="text-xs t2 mt-0.5">The latest repairs added.</p></div><span class="bdg g">Live</span></div>
          <?php if (!$jobs): ?><p class="text-xs t2 py-8 text-center">No repair activity yet.</p><?php else: ?>
          <div class="space-y-4">
            <?php foreach (array_slice($jobs, 0, 5) as $a): ?>
            <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-medium t1"><?= e($a['status']) ?> · <?= e($a['request_code']) ?></p><p class="text-[11px] t2"><?= e($a['customer']) ?>, <?= e($a['device']) ?></p></div><span class="text-[11px] t3 shrink-0"><?= e($a['date']) ?></span></div>
            <?php endforeach; ?>
          </div><?php endif; ?>
        </div>
      </div>
    </section>

    <section id="view-team" class="tab-view hidden space-y-6">
      <?php head('Team members', 'The people keeping your customers connected.', ''); render_team($admins, $techs, $techLoad); ?>
    </section>

    <section id="view-settings" class="tab-view hidden space-y-6">
      <?php head('Settings', 'Make this workspace work for you.', ''); ?>
      <form id="settings-form" class="card max-w-2xl border rounded-xl p-6 space-y-4 text-xs">
        <div><h2 class="text-sm font-semibold t1">Workspace settings</h2><p class="t2 mt-0.5">Saved in this browser only.</p></div>
        <label class="block"><span class="font-medium t1 block mb-1.5">Service center name</span><input name="ws" value="SamFix Service Center" class="inp w-full border px-3.5 py-2.5 rounded-lg"></label>
        <label class="block"><span class="font-medium t1 block mb-1.5">Contact email</span><input name="mail" type="email" placeholder="hello@samfix.com" class="inp w-full border px-3.5 py-2.5 rounded-lg"></label>
        <label class="block"><span class="font-medium t1 block mb-1.5">Contact number</span><input name="tel" placeholder="(032) 555-0120" class="inp w-full border px-3.5 py-2.5 rounded-lg"></label>
        <label class="block"><span class="font-medium t1 block mb-1.5">Default repair turnaround</span>
          <select name="eta" class="inp w-full border px-3.5 py-2.5 rounded-lg"><option>2–3 business days</option><option>Same day (24 hours)</option><option>3–5 business days</option></select></label>
        <div class="flex items-center gap-3"><button class="btn-p px-4 py-2.5 rounded-lg font-medium">Save changes</button><span id="saved" class="tone-green hidden px-2.5 py-1 rounded-full">Saved</span></div>
      </form>
    </section>

    <footer class="pt-8 pb-2 flex flex-col sm:flex-row items-center justify-between text-[11px] t3">
      <p>© <?= date('Y') ?> SamFix. Built for better repairs.</p>
      <span class="flex items-center gap-1.5 mt-2 sm:mt-0"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Connected to database</span>
    </footer>
  </main>
</div>

<!-- Modals -->
<div id="new-modal" class="modal fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
  <div class="card border rounded-2xl max-w-md w-full p-6 relative max-h-[92vh] overflow-y-auto">
    <button onclick="closeModals()" class="t2 absolute right-5 top-5" aria-label="Close"><i data-lucide="x" class="w-4 h-4"></i></button>
    <h3 class="text-base font-bold t1 mb-1">Add repair request</h3><p class="text-xs t2 mb-5">Creates a new ticket in the repair queue.</p>
    <form method="POST" class="space-y-3 text-xs">
      <?= csrf() ?><input type="hidden" name="action" value="new_repair">
      <div class="grid sm:grid-cols-2 gap-3">
        <label><span class="t1 block mb-1">Customer name</span><input name="customer_name" required class="inp w-full border rounded-lg px-3 py-2"></label>
        <label><span class="t1 block mb-1">Email</span><input type="email" name="email" required class="inp w-full border rounded-lg px-3 py-2"></label>
      </div>
      <div class="grid sm:grid-cols-2 gap-3">
        <label><span class="t1 block mb-1">Phone</span><input name="phone" required class="inp w-full border rounded-lg px-3 py-2"></label>
        <label><span class="t1 block mb-1">Service</span><select name="service_id" class="inp w-full border rounded-lg px-3 py-2"><option value="">Select service</option>
          <?php foreach ($services as $s): ?><option value="<?= (int)$s['service_id'] ?>"><?= e($s['service_name']) ?></option><?php endforeach; ?></select></label>
      </div>
      <div class="grid sm:grid-cols-2 gap-3">
        <label><span class="t1 block mb-1">Brand</span><input name="device_brand" required placeholder="Apple" class="inp w-full border rounded-lg px-3 py-2"></label>
        <label><span class="t1 block mb-1">Model</span><input name="device_model" required placeholder="iPhone 15" class="inp w-full border rounded-lg px-3 py-2"></label>
      </div>
      <label class="block"><span class="t1 block mb-1">Issue</span><input name="issue" required class="inp w-full border rounded-lg px-3 py-2"></label>
      <label class="block"><span class="t1 block mb-1">Status</span><select name="status" class="inp w-full border rounded-lg px-3 py-2"><?php foreach (STATUSES as $s) echo '<option>' . $s . '</option>'; ?></select></label>
      <div class="grid sm:grid-cols-2 gap-3">
        <label><span class="t1 block mb-1">Technician</span><select name="technician_id" class="inp w-full border rounded-lg px-3 py-2"><?= tech_options($techs) ?></select></label>
        <label><span class="t1 block mb-1">Estimated cost (₱)</span><input type="number" name="estimated_cost" min="0" step="0.01" class="inp w-full border rounded-lg px-3 py-2"></label>
      </div>
      <div class="pt-2 flex justify-end gap-2"><button type="button" onclick="closeModals()" class="btn-o px-4 py-2 rounded-lg">Cancel</button><button class="btn-p px-4 py-2 rounded-lg font-medium">Save repair</button></div>
    </form>
  </div>
</div>

<div id="edit-modal" class="modal fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
  <div class="card border rounded-2xl max-w-lg w-full p-6 relative max-h-[94vh] overflow-y-auto">
    <button onclick="closeModals()" class="t2 absolute right-5 top-5" aria-label="Close"><i data-lucide="x" class="w-4 h-4"></i></button>
    <div class="flex items-start justify-between pr-8 mb-5">
      <span class="pill w-12 h-12 rounded-xl flex items-center justify-center"><i data-lucide="smartphone" class="w-6 h-6"></i></span>
      <span id="e-badge" class="bdg s"></span>
    </div>
    <h3 class="text-2xl font-bold t1">Repair <span id="e-code"></span></h3>
    <p id="e-sub" class="text-sm t2 mt-1 mb-5"></p>
    <form method="POST" class="space-y-4 text-xs">
      <?= csrf() ?><input type="hidden" name="action" value="update_repair"><input type="hidden" name="request_code" id="e-req">
      <div class="sub bd border rounded-xl p-4 flex items-center gap-3"><span id="e-av"></span><div class="min-w-0"><p id="e-name" class="text-sm font-semibold t1"></p><p id="e-contact" class="t2 truncate"></p></div></div>
      <div class="grid sm:grid-cols-2 gap-4">
        <label><span class="t1 block mb-1.5">Repair status</span><select name="status" id="e-status" class="inp w-full border rounded-lg px-3 py-2.5"><?php foreach (STATUSES as $s) echo '<option>' . $s . '</option>'; ?></select></label>
        <label><span class="t1 block mb-1.5">Estimated cost (₱)</span><input type="number" name="estimated_cost" id="e-cost" min="0" step="0.01" class="inp w-full border rounded-lg px-3 py-2.5"></label>
      </div>
      <label class="block"><span class="t1 block mb-1.5">Assigned technician</span><select name="technician_id" id="e-tech" class="inp w-full border rounded-lg px-3 py-2.5"><?= tech_options($techs, null, true) ?></select></label>
      <label class="block"><span class="t1 block mb-1.5">Service</span><select name="service_id" id="e-svc" class="inp w-full border rounded-lg px-3 py-2.5"><option value="">None</option>
        <?php foreach ($services as $s): ?><option value="<?= (int)$s['service_id'] ?>"><?= e($s['service_name']) ?></option><?php endforeach; ?></select></label>
      <label class="block"><span class="t1 block mb-1.5">Notes</span><textarea name="notes" id="e-notes" rows="2" class="inp w-full border rounded-lg px-3 py-2.5"></textarea></label>
      <div class="sub bd border rounded-lg px-4 py-3 flex items-center gap-2.5 t2"><i data-lucide="clock" class="w-4 h-4 shrink-0"></i><span id="e-meta"></span></div>
      <div class="bd border-t pt-4 flex items-center justify-between gap-2">
        <button type="submit" name="action" value="delete_repair" onclick="return confirm('Delete this repair? This cannot be undone.')" class="px-3 py-2 rounded-lg font-medium" style="background:#FFE4E6;color:#E11D48">Delete</button>
        <div class="flex gap-2">
          <a id="e-view" href="../index.php" target="_blank" rel="noopener" class="btn-o px-4 py-2 rounded-lg font-medium">Customer view</a>
          <button class="btn-p inline-flex items-center gap-1.5 px-4 py-2 rounded-lg font-medium">Save updates <i data-lucide="check" class="w-3.5 h-3.5"></i></button>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="track-modal" class="modal fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
  <div class="card border rounded-2xl max-w-md w-full p-6 relative">
    <button onclick="closeModals()" class="t2 absolute right-5 top-5" aria-label="Close"><i data-lucide="x" class="w-4 h-4"></i></button>
    <h3 class="text-base font-bold t1 mb-1">Track a repair</h3><p class="text-xs t2 mb-4">Search by ticket code, customer name, or phone.</p>
    <input id="track-q" type="search" placeholder="e.g. SFX-1042" class="inp w-full border rounded-lg px-3.5 py-2.5 text-xs mb-3">
    <div id="track-out" class="space-y-2 max-h-56 overflow-y-auto text-xs"></div>
  </div>
</div>

<div id="help-modal" class="modal fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
  <div class="card border rounded-2xl max-w-md w-full p-6 relative">
    <button onclick="closeModals()" class="t2 absolute right-5 top-5" aria-label="Close"><i data-lucide="x" class="w-4 h-4"></i></button>
    <span class="pill w-9 h-9 rounded-xl flex items-center justify-center mb-4"><i data-lucide="help-circle" class="w-5 h-5"></i></span>
    <h3 class="text-lg font-bold t1">Here when you need us.</h3>
    <p class="bd border-b text-xs t2 mt-1 pb-4">Quick answers about your dashboard.</p>
    <div class="space-y-4 my-5 text-xs">
      <div><p class="font-semibold t1">How do repairs appear here?</p><p class="t2 mt-1">Bookings from <code>index.php</code> are saved automatically and show up in the repair queue.</p></div>
      <div><p class="font-semibold t1">How do I update a repair?</p><p class="t2 mt-1">Click a ticket code or the pencil icon, change the status, service or notes, then save.</p></div>
      <div><p class="font-semibold t1">How do I track a repair?</p><p class="t2 mt-1">Use “Track a repair” in the header, or press Ctrl/⌘ + K.</p></div>
    </div>
    <button onclick="closeModals()" class="btn-p inline-flex items-center gap-1.5 text-xs font-medium px-4 py-2 rounded-lg">Got it <i data-lucide="check" class="w-3.5 h-3.5"></i></button>
  </div>
</div>

<script>
const JOBS = <?= json_encode($jobs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const store = { get: k => { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} } };
const icons = () => lucide.createIcons();

/* Theme: light by default, remembered per browser */
function paintTheme() {
  const dark = document.documentElement.classList.contains('dark');
  $('#theme-btn').innerHTML = `<i data-lucide="${dark ? 'sun' : 'moon'}" class="w-4 h-4"></i>`; icons();
}
function toggleTheme() { document.documentElement.classList.toggle('dark'); store.set('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light'); paintTheme(); }

/* Navigation */
function switchTab(id, label) {
  $$('.tab-view').forEach(v => v.classList.add('hidden')); $('#view-' + id).classList.remove('hidden');
  $$('.nav').forEach(b => b.classList.toggle('on', b.dataset.tab === id));
  $('#crumb').textContent = label; store.set('tab', id + '|' + label); $('main').scrollTop = 0;
  if (innerWidth < 1024) { $('#sidebar').classList.add('-translate-x-full'); $('#backdrop').classList.add('hidden'); }
}
function toggleSidebar() { $('#sidebar').classList.toggle('-translate-x-full'); $('#backdrop').classList.toggle('hidden'); }
$$('[data-tab]').forEach(b => b.addEventListener('click', () => switchTab(b.dataset.tab, b.dataset.label)));

/* Modals */
function openModal(id) { closeModals(); const m = $('#' + id); m.classList.remove('hidden'); m.classList.add('flex'); const f = $('input:not([type=hidden]),select', m); if (f) setTimeout(() => f.focus(), 30); }
function closeModals() { $$('.modal').forEach(m => { if (m.id !== 'backdrop') { m.classList.add('hidden'); m.classList.remove('flex'); } }); $('#backdrop').classList.add('hidden'); }
$$('[role=dialog]').forEach(m => m.addEventListener('mousedown', e => { if (e.target === m) closeModals(); }));
const avJs = (n, cls) => { let h = 0; for (const c of n || '') h = (h * 31 + c.charCodeAt(0)) % 360; const p = (n || '').trim().split(/\s+/); const i = ((p[0] || '?')[0] + (p[1] ? p[1][0] : '')).toUpperCase();
  return `<span class="${cls} rounded-full font-semibold flex items-center justify-center shrink-0" style="background:hsl(${h} 70% 50% / .16);color:hsl(${h} 70% 48%)">${esc(i)}</span>`; };
function openEdit(j) {
  const eta = JSON.parse(store.get('ws') || '{}').eta;
  $('#e-req').value = j.request_code; $('#e-code').textContent = '#' + j.request_code;
  $('#e-sub').textContent = `${j.device} · ${j.issue}`;
  $('#e-badge').className = 'bdg ' + (BADGE[j.status] || 's'); $('#e-badge').textContent = j.status;
  $('#e-av').innerHTML = avJs(j.customer, 'w-10 h-10 text-xs'); $('#e-name').textContent = j.customer; $('#e-contact').textContent = `${j.email} · ${j.phone}`;
  $('#e-status').value = j.status; $('#e-cost').value = j.cost ?? ''; $('#e-tech').value = j.tech_id || '';
  $('#e-svc').value = j.service_id || ''; $('#e-notes').value = j.notes || '';
  $('#e-meta').textContent = `Received ${j.date} · Last updated by ${j.by}` + (eta ? ` · Estimated turnaround: ${eta}` : '');
  $('#e-view').href = '../index.php?track=' + encodeURIComponent(j.request_code);
  openModal('edit-modal');
}
document.addEventListener('click', e => { const b = e.target.closest('[data-edit]'); if (b) openEdit(JSON.parse(b.dataset.edit)); });
document.addEventListener('keydown', e => {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); openModal('track-modal'); }
  if (e.key === 'Escape') closeModals();
});

/* Table filters and search (each table keeps its own state) */
function applyFilters(t) {
  const f = t.dataset.f, q = t.dataset.q; let n = 0;
  $$('tbody tr[data-status]', t).forEach(r => {
    const s = r.dataset.status;
    const ok = (f === 'ALL' || (f === 'ACTIVE' && (s === 'Pending' || s === 'In Progress')) || s === f) && r.dataset.s.includes(q);
    r.style.display = ok ? '' : 'none'; if (ok) n++;
  });
  const empty = $('[data-empty]', t); if (empty && $$('tbody tr[data-status]', t).length) empty.classList.toggle('hidden', n > 0);
  $('[data-shown]', t).textContent = n;
}
$$('[id^="t-"]').forEach(t => {
  $$('.ftab', t).forEach(b => b.addEventListener('click', () => { t.dataset.f = b.dataset.f; $$('.ftab', t).forEach(x => x.classList.toggle('on', x === b)); applyFilters(t); }));
  $('[data-search]', t).addEventListener('input', e => { t.dataset.q = e.target.value.toLowerCase().trim(); applyFilters(t); });
  $('[data-all]', t).addEventListener('change', e => $$('.rcb', t).forEach(c => { if (c.closest('tr').style.display !== 'none') c.checked = e.target.checked; }));
});
function goRepairs(filter) {
  switchTab('repairs', 'Repair jobs'); const t = $('#t-rep'); const b = $(`.ftab[data-f="${filter}"]`, t); if (b) b.click();
}

/* Customers search */
$('#cust-search')?.addEventListener('input', e => { const q = e.target.value.toLowerCase().trim(); $$('.cust').forEach(c => c.style.display = c.dataset.cs.includes(q) ? '' : 'none'); });

/* Track modal (all values escaped) */
const BADGE = { 'Completed': 'g', 'In Progress': 'b', 'Pending': 'a' };
$('#track-q').addEventListener('input', e => {
  const q = e.target.value.toLowerCase().trim(), out = $('#track-out');
  if (!q) { out.innerHTML = ''; return; }
  const m = JOBS.filter(j => [j.request_code, j.customer, j.phone].some(v => (v || '').toLowerCase().includes(q))).slice(0, 8);
  out.innerHTML = m.length ? m.map((j, i) => `<button type="button" data-i="${JOBS.indexOf(j)}" class="sub bd border w-full text-left p-3 rounded-lg flex items-center justify-between gap-2"><span><span class="block font-semibold acct">${esc(j.request_code)} · ${esc(j.customer)}</span><span class="t2">${esc(j.device)}</span></span><span class="bdg ${BADGE[j.status] || 's'}">${esc(j.status)}</span></button>`).join('')
    : '<p class="t2 text-center py-4">No matching tickets found.</p>';
});
$('#track-out').addEventListener('click', e => { const b = e.target.closest('[data-i]'); if (b) openEdit(JOBS[+b.dataset.i]); });

/* Settings (browser-local) */
const form = $('#settings-form'), saved = JSON.parse(store.get('ws') || '{}');
for (const [k, v] of Object.entries(saved)) if (form.elements[k]) form.elements[k].value = v;
function paintWs() { const n = (form.elements.ws.value || '').trim() || 'SamFix Service Center'; $$('[data-ws]').forEach(el => el.textContent = n); }
form.addEventListener('submit', e => { e.preventDefault(); store.set('ws', JSON.stringify(Object.fromEntries(new FormData(form)))); paintWs(); $('#saved').classList.remove('hidden'); setTimeout(() => $('#saved').classList.add('hidden'), 2000); });

/* Add-member form: the role decides which fields are needed */
$$('[data-teamform]').forEach(f => {
  const sel = $('select', f), sync = () => $$('[data-for]', f).forEach(i => { const on = i.dataset.for === sel.value; i.classList.toggle('hidden', !on); i.disabled = !on; });
  sel.addEventListener('change', sync); sync();
});

/* Boot */
paintWs(); paintTheme();
const tab = (store.get('tab') || '').split('|'); if (tab[0] && $('#view-' + tab[0])) switchTab(tab[0], tab[1]);
</script>
</body>
</html>
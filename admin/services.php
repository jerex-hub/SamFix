<?php
// admin/services.php: manage the services table (these feed the "Repair type" dropdown and the price cards).
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

start_secure_session();
if (empty($_SESSION['admin_logged_in']) && empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$self = strtok($_SERVER['REQUEST_URI'], '?');
function csrf(): string { return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">'; }

// ---------- POST actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = '';
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
        $msg = 'Your session expired. Reload the page and try again.';
    } else {
        $action = (string)($_POST['action'] ?? '');
        $data = [
            'service_name' => trim((string)($_POST['service_name'] ?? '')),
            'description'  => trim((string)($_POST['description'] ?? '')),
            'base_price'   => trim((string)($_POST['base_price'] ?? '')),
        ];
        $id = (int)($_POST['service_id'] ?? 0);
        try {
            if ($action === 'add_service' || $action === 'update_service') {
                $errors = validate_service_form($data);
                if ($errors) {
                    $msg = reset($errors);
                } elseif ($action === 'add_service') {
                    $pdo->prepare('INSERT INTO services (service_name, description, base_price) VALUES (?, ?, ?)')
                        ->execute([$data['service_name'], $data['description'], $data['base_price']]);
                    $msg = 'Added service ' . $data['service_name'] . '.';
                } elseif ($id > 0) {
                    $pdo->prepare('UPDATE services SET service_name = ?, description = ?, base_price = ? WHERE service_id = ?')
                        ->execute([$data['service_name'], $data['description'], $data['base_price'], $id]);
                    $msg = 'Updated ' . $data['service_name'] . '.';
                }
            } elseif ($action === 'delete_service' && $id > 0) {
                $pdo->prepare('DELETE FROM services WHERE service_id = ?')->execute([$id]);
                $msg = 'Service deleted. Existing repairs keep their details but lose the service link.';
            }
        } catch (PDOException $ex) {
            error_log('services.php ' . $action . ': ' . $ex->getMessage());
            $msg = 'Something went wrong while saving. Check the server log.';
        }
    }
    $_SESSION['toast'] = $msg;
    header('Location: ' . $self);
    exit;
}
$toast = (string)($_SESSION['toast'] ?? '');
unset($_SESSION['toast']);

// ---------- Load ----------
$services = [];
$loadError = false;
try {
    $services = $pdo->query('SELECT s.service_id, s.service_name, s.description, s.base_price,
                                    (SELECT COUNT(*) FROM repair_requests r WHERE r.service_id = s.service_id) AS uses
                             FROM services s ORDER BY s.service_name')->fetchAll();
} catch (PDOException $ex) {
    error_log('services.php load: ' . $ex->getMessage());
    $loadError = true;
}
$cur = '₱';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Services - <?= e(APP_NAME) ?></title>
<script>try{if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark')}catch(e){}</script>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--app:#F5F7FB;--side:#fff;--card:#fff;--sub:#F3F6FA;--inp:#fff;--bd:#E4E9F1;--t1:#0F172A;--t2:#5B6B82;--t3:#94A3B8;--acc:#2F5FF0;--accs:#E6EDFE;--acct:#2455E6;--hov:#F3F6FA;--shadow:0 1px 2px rgba(15,23,42,.04);--modal:rgba(15,23,42,.45)}
html.dark{--app:#0E1525;--side:#121A2B;--card:#151E30;--sub:#182236;--inp:#101726;--bd:#1F2A3F;--t1:#F1F5F9;--t2:#94A3B8;--t3:#64748B;--acc:#2563EB;--accs:#1D2D49;--acct:#60A5FA;--hov:#182236;--shadow:none;--modal:rgba(0,0,0,.6);color-scheme:dark}
body{font-family:'Inter',sans-serif;background:var(--app);color:var(--t1)}
.side,.top{background:var(--side);border-color:var(--bd)}.card{background:var(--card);border-color:var(--bd);box-shadow:var(--shadow)}
.sub{background:var(--sub)}.bd{border-color:var(--bd)}.t1{color:var(--t1)}.t2{color:var(--t2)}.t3{color:var(--t3)}.acct{color:var(--acct)}
.pill{background:var(--accs);color:var(--acct)}
.inp{background:var(--inp);border-color:var(--bd);color:var(--t1)}.inp:focus{outline:2px solid var(--acc);outline-offset:-1px}.inp::placeholder{color:var(--t3)}
.btn-p{background:var(--acc);color:#fff}.btn-p:hover{filter:brightness(1.1)}
.btn-o{background:var(--card);border:1px solid var(--bd);color:var(--t1)}.btn-o:hover{background:var(--hov)}
.nav{color:var(--t2)}.nav:hover{background:var(--hov);color:var(--t1)}.nav.on{background:var(--accs);color:var(--acct)}
.row:hover{background:var(--hov)}.ico{color:var(--t3)}.ico:hover{background:var(--sub);color:var(--t1)}.ico.del:hover{color:#e11d48}
button,.btn-p,.btn-o,.nav{cursor:pointer}:focus-visible{outline:2px solid var(--acc);outline-offset:2px}
.tone-green{background:#DDF5E8;color:#16A34A}.tone-amber{background:#FFF1DC;color:#D97706}
html.dark .tone-green{background:#052e16b3;color:#34d399}html.dark .tone-amber{background:#451a03b3;color:#fbbf24}
.modal{background:var(--modal);backdrop-filter:blur(3px)}
</style>
</head>
<body class="flex h-screen overflow-hidden antialiased">

<div id="backdrop" onclick="toggleSidebar()" class="modal fixed inset-0 z-30 hidden lg:hidden"></div>

<aside id="sidebar" class="side border-r fixed inset-y-0 left-0 z-40 w-[260px] flex flex-col justify-between overflow-y-auto -translate-x-full lg:translate-x-0 lg:static transition-transform duration-200">
  <div class="p-5">
    <div class="flex items-center gap-2.5 px-1 mb-6">
      <span class="btn-p w-8 h-8 rounded-lg flex items-center justify-center"><i data-lucide="wrench" class="w-4 h-4"></i></span>
      <span class="text-xl font-bold tracking-tight t1"><?= e(APP_NAME) ?><span class="acct">.</span></span>    </div>
    <p class="px-3 text-[10px] font-semibold t3 uppercase tracking-wider mb-2">Workspace</p>
    <nav class="space-y-1">
      <a href="requests.php" class="nav w-full flex items-center gap-3 px-3 py-2 text-xs font-medium rounded-lg"><i data-lucide="shield-check" class="w-4 h-4"></i>Admin dashboard</a>
      <a href="services.php" class="nav on w-full flex items-center gap-3 px-3 py-2 text-xs font-medium rounded-lg"><i data-lucide="tag" class="w-4 h-4"></i>Services &amp; prices</a>
    </nav>
  </div>
  <div class="p-5">
    <div class="bd border-t pt-3 flex items-center justify-between px-1">
      <a href="../index.php" target="_blank" rel="noopener" class="t2 flex items-center gap-2 text-xs font-medium hover:opacity-80"><i data-lucide="external-link" class="w-4 h-4"></i>Customer site</a>
      <button type="button" onclick="openLogout()" title="Log out" aria-label="Log out" class="t2 hover:text-rose-500"><i data-lucide="log-out" class="w-4 h-4"></i></button>    </div>
  </div>
</aside>

<div class="flex-1 flex flex-col overflow-hidden min-w-0">
  <header class="top border-b h-16 px-4 sm:px-8 flex items-center justify-between shrink-0 gap-3">
    <div class="flex items-center gap-3">
      <button onclick="toggleSidebar()" class="btn-o lg:hidden p-2 rounded-lg" aria-label="Open menu"><i data-lucide="menu" class="w-4 h-4"></i></button>
      <div class="flex items-center gap-2 text-xs"><span class="t2 hidden sm:inline">Administration</span><i data-lucide="chevron-right" class="w-3.5 h-3.5 t3 hidden sm:inline"></i><span class="font-medium t1">Services &amp; prices</span></div>
    </div>
    <button id="theme-btn" onclick="toggleTheme()" class="btn-o w-8 h-8 rounded-lg flex items-center justify-center" aria-label="Toggle light and dark mode"><i data-lucide="moon" class="w-4 h-4"></i></button>
  </header>

  <?php if ($toast !== ''): ?>
  <div role="status" class="tone-green px-4 sm:px-8 py-2.5 text-xs flex items-center justify-between"><span><?= e($toast) ?></span>
    <button onclick="this.parentNode.remove()" aria-label="Dismiss"><i data-lucide="x" class="w-3.5 h-3.5"></i></button></div>
  <?php endif; ?>

  <main class="flex-1 overflow-y-auto p-4 sm:p-8 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold t1">Services &amp; prices</h1>
        <p class="text-xs t2 mt-1">These appear in the “Repair type” dropdown and on the customer price cards.</p>
      </div>
      <button onclick="openForm()" class="btn-p inline-flex items-center justify-center gap-2 text-xs font-medium px-4 py-2.5 rounded-lg self-start sm:self-auto"><i data-lucide="plus" class="w-4 h-4"></i>Add service</button>
    </div>

    <?php if ($loadError): ?>
      <div role="alert" class="tone-amber rounded-xl px-4 py-3 text-xs">Could not load services. Check that the <code>services</code> table exists in the <code><?= e(DB_NAME) ?></code> database.</div>
    <?php endif; ?>

    <div class="card rounded-xl border overflow-hidden">
      <div class="p-5 sm:p-6 pb-4 flex items-center gap-2.5"><h2 class="text-sm font-semibold t1">All services</h2><span class="pill text-[11px] font-medium px-2.5 py-0.5 rounded-full"><?= count($services) ?></span></div>
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[640px]">
          <thead><tr class="sub bd border-y text-[11px] font-medium t2">
            <th class="py-3 pl-6 pr-4">Service</th><th class="py-3 px-4">Description</th><th class="py-3 px-4">Starting price</th><th class="py-3 px-4">Used in</th><th class="py-3 pr-6 pl-3 text-right">Actions</th>
          </tr></thead>
          <tbody class="text-xs">
          <?php if (!$services): ?>
            <tr><td colspan="5" class="py-12 px-6 text-center t2">No services yet. Click “Add service” to create your first one, or import <code>seed.sql</code>.</td></tr>
          <?php else: foreach ($services as $s): ?>
            <tr class="row bd border-b">
              <td class="py-3.5 pl-6 pr-4 font-semibold t1"><?= e($s['service_name']) ?></td>
              <td class="py-3.5 px-4 t2 max-w-xs truncate"><?= e($s['description']) ?></td>
              <td class="py-3.5 px-4 font-medium t1"><?= $cur . number_format((float)$s['base_price'], 2) ?></td>
              <td class="py-3.5 px-4 t2"><?= (int)$s['uses'] ?> <?= (int)$s['uses'] === 1 ? 'repair' : 'repairs' ?></td>
              <td class="py-3.5 pr-6 pl-3 text-right whitespace-nowrap">
                <button type="button" aria-label="Edit <?= e($s['service_name']) ?>" data-edit="<?= e(json_encode($s)) ?>" class="ico p-1.5 rounded-lg"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                <form method="POST" class="inline" onsubmit="return confirm('Delete <?= e(addslashes($s['service_name'])) ?>? Repairs that used it will lose the service link.')">
                  <?= csrf() ?><input type="hidden" name="action" value="delete_service"><input type="hidden" name="service_id" value="<?= (int)$s['service_id'] ?>">
                  <button type="submit" aria-label="Delete <?= e($s['service_name']) ?>" class="ico del p-1.5 rounded-lg"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<!-- Add / edit modal -->
<div id="svc-modal" class="modal fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
  <div class="card border rounded-2xl max-w-md w-full p-6 relative">
    <button onclick="closeModal()" class="t2 absolute right-5 top-5" aria-label="Close"><i data-lucide="x" class="w-4 h-4"></i></button>
    <h3 id="m-title" class="text-base font-bold t1 mb-1">Add service</h3>
    <p class="text-xs t2 mb-5">Customers see the name, description and “from” price.</p>
    <form method="POST" class="space-y-3 text-xs">
      <?= csrf() ?>
      <input type="hidden" name="action" id="m-action" value="add_service">
      <input type="hidden" name="service_id" id="m-id" value="">
      <label class="block"><span class="t1 block mb-1">Service name</span><input name="service_name" id="m-name" required maxlength="100" placeholder="e.g. Screen Replacement" class="inp w-full border rounded-lg px-3 py-2"></label>
      <label class="block"><span class="t1 block mb-1">Description</span><textarea name="description" id="m-desc" rows="3" maxlength="1000" placeholder="Short explanation for customers" class="inp w-full border rounded-lg px-3 py-2"></textarea></label>
      <label class="block"><span class="t1 block mb-1">Starting price (₱)</span><input name="base_price" id="m-price" required inputmode="decimal" placeholder="e.g. 1500.00" class="inp w-full border rounded-lg px-3 py-2"></label>
      <div class="pt-2 flex justify-end gap-2"><button type="button" onclick="closeModal()" class="btn-o px-4 py-2 rounded-lg">Cancel</button><button class="btn-p px-4 py-2 rounded-lg font-medium">Save service</button></div>
    </form>
  </div>
</div>

<div id="logout-modal" class="modal fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
  <div class="card border rounded-2xl max-w-sm w-full p-6 relative">
    <button type="button" onclick="closeModals()" class="t2 absolute right-5 top-5" aria-label="Close"><i data-lucide="x" class="w-4 h-4"></i></button>
    <span class="pill w-10 h-10 rounded-xl flex items-center justify-center mb-4"><i data-lucide="log-out" class="w-5 h-5"></i></span>
    <h3 class="text-base font-bold t1 mb-1">Confirm your password</h3>
    <p class="text-xs t2 mb-4">Enter your password to sign out.</p>
    <form method="POST" action="logout.php" class="space-y-3 text-xs" data-validate>
      <?= csrf() ?><input type="hidden" name="back" value="requests.php">
      <label for="logout-pass" class="block"><span class="t1 block mb-1">Password</span>
        <input type="password" id="logout-pass" name="password" required autocomplete="current-password" class="inp w-full border rounded-lg px-3 py-2.5"></label>
      <div class="flex justify-end gap-2 pt-1">
        <button type="button" onclick="closeModals()" class="btn-o px-4 py-2 rounded-lg">Cancel</button>
        <button type="submit" class="btn-p px-4 py-2 rounded-lg font-medium">Sign out</button>
      </div>
    </form>
  </div>
</div>

<script>
function openLogout() { const m = $('#logout-modal'); m.classList.remove('hidden'); m.classList.add('flex'); setTimeout(() => $('#logout-pass').focus(), 30); }
function closeLogout() { const m = $('#logout-modal'); m.classList.add('hidden'); m.classList.remove('flex'); }
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLogout(); });
const $ = s => document.querySelector(s);
function toggleSidebar() { $('#sidebar').classList.toggle('-translate-x-full'); $('#backdrop').classList.toggle('hidden'); }
function paintTheme() { const d = document.documentElement.classList.contains('dark'); $('#theme-btn').innerHTML = `<i data-lucide="${d ? 'sun' : 'moon'}" class="w-4 h-4"></i>`; lucide.createIcons(); }
function toggleTheme() { document.documentElement.classList.toggle('dark'); try { localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light'); } catch (e) {} paintTheme(); }
function closeModal() { const m = $('#svc-modal'); m.classList.add('hidden'); m.classList.remove('flex'); }
function openForm(s) {
  $('#m-title').textContent = s ? 'Edit service' : 'Add service';
  $('#m-action').value = s ? 'update_service' : 'add_service';
  $('#m-id').value = s ? s.service_id : '';
  $('#m-name').value = s ? s.service_name : '';
  $('#m-desc').value = s ? (s.description || '') : '';
  $('#m-price').value = s ? Number(s.base_price).toFixed(2) : '';
  const m = $('#svc-modal'); m.classList.remove('hidden'); m.classList.add('flex'); setTimeout(() => $('#m-name').focus(), 30);
}
document.addEventListener('click', e => { const b = e.target.closest('[data-edit]'); if (b) openForm(JSON.parse(b.dataset.edit)); });
$('#svc-modal').addEventListener('mousedown', e => { if (e.target.id === 'svc-modal') closeModal(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
paintTheme();
</script>
</body>
</html>
<?php
// Escape output to prevent XSS. Use on every value printed to the page.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ---- CSRF (needs an active session: call start_secure_session() first) ----
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return is_string($token) && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

// ---- Flash messages (one-time messages across a redirect) ----
function set_flash(string $msg): void
{
    $_SESSION['flash'] = $msg;
}

function get_flash(): string
{
    $msg = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $msg;
}

// ---- Request form helpers ----

// Removes spaces and dashes so tracking by phone works however it was typed.
function normalize_phone(string $phone): string
{
    return str_replace([' ', '-'], '', $phone);
}

// Applies the validation rules from section 7. Returns an array of errors keyed by field name.
function validate_request_form(array $data): array
{
    $errors = [];

    $name = $data['customer_name'] ?? '';
    $len  = mb_strlen($name);
    if ($name === '') {
        $errors['customer_name'] = 'Name is required.';
    } elseif ($len < 2 || $len > 100) {
        $errors['customer_name'] = 'Name must be 2 to 100 characters.';
    }

    $email = $data['email'] ?? '';
    if ($email === '') {
        $errors['email'] = 'Email is required.';
    } elseif (mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email (up to 100 characters).';
    }

    $phone = normalize_phone($data['phone'] ?? '');
    if ($phone === '') {
        $errors['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^\+?\d{7,15}$/', $phone)) {
        $errors['phone'] = 'Phone must be 7 to 15 digits (a leading + is allowed).';
    }

    $brand = $data['device_brand'] ?? '';
    if ($brand === '') {
        $errors['device_brand'] = 'Device brand is required.';
    } elseif (mb_strlen($brand) > 50) {
        $errors['device_brand'] = 'Device brand must be 50 characters or less.';
    }

    $model = $data['device_model'] ?? '';
    if ($model === '') {
        $errors['device_model'] = 'Device model is required.';
    } elseif (mb_strlen($model) > 50) {
        $errors['device_model'] = 'Device model must be 50 characters or less.';
    }

    $issue = $data['issue'] ?? '';
    if ($issue === '') {
        $errors['issue'] = 'Please describe the issue.';
    } elseif (mb_strlen($issue) > 255) {
        $errors['issue'] = 'Issue must be 255 characters or less.';
    }

    $notes = $data['notes'] ?? '';
    if (mb_strlen($notes) > 1000) {
        $errors['notes'] = 'Notes must be 1000 characters or less.';
    }

    return $errors;
}

// Builds the Request ID, e.g. SF-20261105-00042
function generate_request_code(int $id): string
{
    return 'SF-' . date('Ymd') . '-' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
}

// Returns the HTML for a field's error message (empty string if none).
function field_error(array $errors, string $field): string
{
    if (!isset($errors[$field])) {
        return '';
    }
    return '<p class="text-sm text-red-400 mt-1">' . e($errors[$field]) . '</p>';
}

// Inline SVG icons (Lucide-style). Returns trusted markup; $class sets the size/color.
function icon(string $name, string $class = 'w-5 h-5'): string
{
    static $paths = [
        'wrench'       => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'smartphone'   => '<rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/>',
        'clock'        => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'check-circle' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'shield-check' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'settings'     => '<circle cx="12" cy="12" r="3"/><path d="M12 1v3M12 20v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M1 12h3M20 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        'search'       => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'users'        => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'calendar'     => '<rect width="18" height="18" x="3" y="4" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'map-pin'      => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'mail'         => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        'phone'        => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
        'help-circle'  => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        'arrow-right'  => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
    ];
    $inner = $paths[$name] ?? '';
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
         . 'stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' . e($class) . '" aria-hidden="true">'
         . $inner . '</svg>';
}

// Colored status badge. The status text is always shown, so color is never the only cue.
function status_badge(string $status): string
{
    $styles = [
        'Pending'     => 'bg-yellow-500/15 text-yellow-300 border-yellow-500/30',
        'In Progress' => 'bg-blue-500/15 text-blue-300 border-blue-500/30',
        'Completed'   => 'bg-green-500/15 text-green-300 border-green-500/30',
        'Cancelled'   => 'bg-red-500/15 text-red-300 border-red-500/30',
    ];
    $class = $styles[$status] ?? 'bg-slate-500/15 text-slate-300 border-slate-500/30';
    return '<span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold ' . $class . '">'
         . '<span class="w-1.5 h-1.5 rounded-full bg-current"></span>' . e($status) . '</span>';
}

// Validation for the admin add/edit service form. Returns errors keyed by field.
function validate_service_form(array $data): array
{
    $errors = [];

    $name = $data['service_name'] ?? '';
    if ($name === '') {
        $errors['service_name'] = 'Service name is required.';
    } elseif (mb_strlen($name) > 100) {
        $errors['service_name'] = 'Service name must be 100 characters or less.';
    }

    if (mb_strlen($data['description'] ?? '') > 1000) {
        $errors['description'] = 'Description must be 1000 characters or less.';
    }

    $price = $data['base_price'] ?? '';
    if ($price === '') {
        $errors['base_price'] = 'Price is required.';
    } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $price)) {
        $errors['base_price'] = 'Enter a price of zero or more with up to 2 decimal places.';
    }

    return $errors;
}
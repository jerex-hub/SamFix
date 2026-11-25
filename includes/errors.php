<?php
// Error logging, security headers and the general error page (tasks 5.1 and 5.2).
// Loaded automatically at the bottom of config.php, so every page gets it.

// ---- Logging --------------------------------------------------------------
// The log file is a .php file that starts with an exit line, so opening it in a
// browser shows nothing. Read it with a text editor.
function init_error_log(): void
{
    $dir = dirname(LOG_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_file(LOG_FILE)) {
        @file_put_contents(LOG_FILE, "<?php http_response_code(404); exit; ?>\n");
    }

    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', LOG_FILE);
    ini_set('display_errors', APP_DEBUG ? '1' : '0');   // never show raw errors to visitors in production
}

// ---- Security headers -----------------------------------------------------
function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
}

// ---- The general error page ----------------------------------------------
// Standalone on purpose (no database, no header.php, no build step needed), so it
// still works when something basic is broken.
function render_error_page(int $status, string $title, string $message, ?string $detail = null): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();   // throw away any half-built page
    }
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
    }

    $h    = function (string $s): string {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    };
    $home = $h(BASE_URL . '/index.php');
    $app  = $h(APP_NAME);

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . $h($title) . ' | ' . $app . '</title>'
       . '<style>'
       . 'body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#020617;color:#e2e8f0;font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}'
       . '.box{max-width:30rem;margin:1.5rem;padding:2.5rem 2rem;text-align:center;background:#0f172a;border:1px solid #1e293b;border-radius:1.25rem}'
       . '.code{margin:0;font-size:3.5rem;font-weight:800;color:#60a5fa;line-height:1}'
       . 'h1{margin:.75rem 0 .5rem;font-size:1.5rem}p{margin:0 0 1.5rem;color:#94a3b8;line-height:1.6}'
       . 'a{display:inline-block;padding:.7rem 1.4rem;border-radius:.75rem;background:#3b82f6;color:#fff;font-weight:600;text-decoration:none}a:hover{background:#60a5fa}'
       . 'pre{margin:1.5rem 0 0;padding:1rem;text-align:left;font-size:.75rem;white-space:pre-wrap;background:#020617;border:1px solid #1e293b;border-radius:.5rem;color:#fca5a5}'
       . '</style></head><body><div class="box">'
       . '<p class="code">' . (int) $status . '</p>'
       . '<h1>' . $h($title) . '</h1>'
       . '<p>' . $h($message) . '</p>'
       . '<a href="' . $home . '">Back to home</a>';

    if (APP_DEBUG && $detail !== null) {
        echo '<pre>' . $h($detail) . '</pre>';   // technical detail only while developing
    }

    echo '</div></body></html>';
    exit;
}

// ---- Catch anything the pages did not handle ------------------------------
function register_error_handlers(): void
{
    // Uncaught exceptions and errors (for example an undefined function)
    set_exception_handler(function (Throwable $e) {
        error_log(sprintf('Uncaught %s: %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));
        render_error_page(
            500,
            'Something went wrong',
            'Sorry, something went wrong on our side. Please try again later.',
            get_class($e) . ': ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()
        );
    });

    // Fatal errors and parse errors (PHP already writes these to the log)
    register_shutdown_function(function () {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            render_error_page(
                500,
                'Something went wrong',
                'Sorry, something went wrong on our side. Please try again later.',
                $err['message'] . "\n" . $err['file'] . ':' . $err['line']
            );
        }
    });
}

init_error_log();
send_security_headers();
register_error_handlers();
ob_start();   // buffer the page so a failure can replace it with the error page
<?php
// Router for PHP's built-in server. Start the site with:
//     php -S localhost:8000 router.php
// It shows the friendly 404 page for unknown URLs and refuses to serve private files.

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// Folders and files that must never be downloaded or run from the browser
$blockedDirs  = ['/includes/', '/database/', '/src/', '/logs/', '/node_modules/'];
$blockedFiles = ['/package.json', '/package-lock.json', '/README.md', '/router.php'];

foreach ($blockedDirs as $dir) {
    if (strpos($path, $dir) === 0) {
        require __DIR__ . '/404.php';
        return true;
    }
}
if (in_array($path, $blockedFiles, true)) {
    require __DIR__ . '/404.php';
    return true;
}

if ($path === '/') {
    require __DIR__ . '/index.php';
    return true;
}

// Existing files (pages, the compiled CSS, images) are served normally
$real = realpath(__DIR__ . $path);
if ($real !== false && is_file($real) && strncmp($real, __DIR__, strlen(__DIR__)) === 0) {
    return false;
}

require __DIR__ . '/404.php';
return true;
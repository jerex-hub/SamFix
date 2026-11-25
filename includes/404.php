<?php
// 404.php: friendly "page not found" page
require_once __DIR__ . '/includes/config.php';

render_error_page(404, 'Page not found', 'The page you are looking for does not exist or may have moved.');
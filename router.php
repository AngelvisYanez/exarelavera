<?php
$_SERVER['SCRIPT_NAME'] = '/api/index.php';
// Remove query string from request uri for routing
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($uri, '/api/') === 0) {
    require __DIR__ . '/api/index.php';
    return true; // Tells PHP built-in webserver that the request has been handled
}

return false;

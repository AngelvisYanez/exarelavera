<?php
// Router script for PHP built-in server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Strip /api prefix if present (since document root IS the api/ folder)
if (strpos($uri, '/api/') === 0) {
    $uri = substr($uri, 4);
}

// Serve static files from the api directory if they exist
$filePath = __DIR__ . $uri;
if ($uri !== '/' && is_file($filePath)) {
    // Serve the file
    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];
    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }
    readfile($filePath);
    return true;
}

// Route everything else through index.php
$_SERVER['REQUEST_URI'] = $uri;
require __DIR__ . '/index.php';

<?php
/**
 * Enhanced Router for PHP Built-in Development Server
 * 
 * - Routes /api/* requests to Slim API
 * - Catches PHP errors/warnings and returns JSON for AJAX requests
 * - Ensures clean JSON output even with PHP warnings
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

ob_start();

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        ob_clean();
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Error: shutdown');
        }
        echo json_encode([
            'success' => false,
            'error' => 'Error interno del servidor',
            'debug' => $error['message'] . ' en ' . $error['file'] . ':' . $error['line']
        ]);
    }
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    $isAjax = (isset($_POST['ajax_empresas2']) || isset($_GET['ajax_empresas2']) ||
               isset($_POST['ajax_check']) || isset($_GET['ajax_check']) ||
               !empty($_POST['ajax_']) || !empty($_GET['ajax_']));
    if ($isAjax) {
        $logLine = date('Y-m-d H:i:s') . " PHP Warning: $message in $file:$line" . PHP_EOL;
        file_put_contents(__DIR__ . '/logs/php_errors.log', $logLine, FILE_APPEND);
        return true;
    }
    return false;
});

$_SERVER['SCRIPT_NAME'] = '/api/index.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($uri, '/api/') === 0) {
    require __DIR__ . '/api/index.php';
    $output = ob_get_clean();
    if (!headers_sent() && !empty($output) && $output[0] === '{') {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo $output;
    return true;
}

ob_end_flush();
return false;

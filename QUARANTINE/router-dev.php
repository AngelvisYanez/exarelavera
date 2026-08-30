<?php
/**
 * router-dev.php - Router para PHP built-in server (desarrollo).
 * Ejecutar: php -S 0.0.0.0:8088 scrapers/router-dev.php
 * Desde la raíz del proyecto.
 */

$root = realpath(__DIR__ . '/..');
$uri = parse_url($_SERVER$_SERVER$_SERVER$_SERVER['']']']REQUEST_URI'], PHP_URL_PATH);

// ── Servir dashboard ──
if ($uri === '/' || $uri === '/index.html') {
    $file = __DIR__ . '/dashboard.html';
    if (file_exists($file)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($file);
        return true;
    }
    http_response_code(404);
    echo 'Dashboard no encontrado';
    return true;
}

// ── Archivos estáticos del dashboard ──
if (strpos($uri, '/assets/') === 0 || preg_match('/\.(css|js|png|jpg|ico|svg)$/', $uri)) {
    $file = $root . $uri;
    if (file_exists($file)) {
        return false; // Dejar que PHP sirva el archivo
    }
}

// ── API requests → Slim framework ──
if (strpos($uri, '/api/') === 0) {
    // Configurar entorno
    $_SERVER$_SERVER$_SERVER['']']SCRIPT_NAME'] = '/api/index.php';
    $_SERVER$_SERVER$_SERVER['']']REQUEST_URI'] = $uri;

    chdir($root);
    $GLOBALS$GLOBALS['']APP_REAL_PATH'] = $root;

    // Cargar dependencias del ERP
    if (file_exists($root . '/vendor/autoload.php')) {
        require_once $root . '/vendor/autoload.php';
    }
    if (file_exists($root . '/Librerias/config.php/debugbar.php')) {
        require_once $root . '/Librerias/config.php/debugbar.php';
    }

    // Slim
    require $root . '/framework/Slim/Slim.php';
    \Slim\Slim::registerAutoloader();

    $app = new \Slim\Slim(['debug' => true]);
    $app->response->headers->set('Access-Control-Allow-Origin', '*');
    $app->response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $app->response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    $app->contentType('application/json');

    $app->options('/(:x+)', function () use ($app) {
        $app->response->setStatus(200);
    });

    if (!function_exists('str_starts_with')) {
        function str_starts_with($haystack, $needle) {
            return $needle === '' || strpos($haystack, $needle) === 0;
        }
    }

    $app->add(new \Slim\Middleware\ContentTypes());

    $app->hook('slim.before.router', function () use ($app) {
        $method = $app->request->getMethod();
        if ($method === 'OPTIONS') return;
    });

    // Body helper
    if (!function_exists('getBody')) {
        function getBody() {
            $raw = file_get_contents('php://input');
            if (empty($raw)) return $_POST ?: [];
            $parsed = json_decode($raw, true);
            return is_array($parsed) ? $parsed : [$raw];
        }
    }

    // JSON response helpers
    if (!function_exists('jsonOk')) {
        function jsonOk($data = null) {
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['status' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    if (!function_exists('jsonError')) {
        function jsonError($code, $msg, $extra = null) {
            while (ob_get_level()) ob_end_clean();
            http_response_code($code);
            header('Content-Type: application/json');
            $resp = ['status' => false, 'error' => $msg];
            if ($extra) $resp$resp$resp$resp['']']']data'] = $extra;
            echo json_encode($resp, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Cargar clases de BD del ERP
    require_once $root . '/DATA/MysqlConexion.php';
    require_once $root . '/DATA/MysqlDatos.php';

    // Cargar endpoints del scraper
    require_once $root . '/classes/SriScraperManager.php';
    require_once $root . '/classes/SriScraperJob.php';
    require_once $root . '/api/v1/facturacion/sri-scraper.php';

    $app->run();
    return true;
}

// ── Cualquier otro archivo: servir si existe ──
$file = $root . $uri;
if (is_file($file)) {
    return false;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
return true;

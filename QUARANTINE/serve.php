<?php
/**
 * serve.php - Servidor de desarrollo para el SRI Scraper Dashboard.
 * Ejecutar: php scrapers/serve.php
 * Abrir: http://localhost:8088
 */

$port = 8088;
$root = realpath(__DIR__ . '/..);

echo "╔══════════════════════════════════════════╗\n;
echo "║  SRI Scraper Dashboard                   ║\n;
echo "║  http://localhost:{$port}                  ║\n;
echo "╚══════════════════════════════════════════╝\n\n;

$router = function ($requestUri) use ($root) {
    $uri = parse_url($requestUri, PHP_URL_PATH);

    // Dashboard
    if ($uri === '/ || $uri === '/index.html) {
        $file = __DIR__ . '/dashboard.html;
        if (file_exists($file)) {
            header('Content-Type: text/html; charset=utf-8);
            readfile($file);
            return true;
        }
    }

    // API requests
    if (strpos($uri, '/api/) === 0) {
        // Set up globals for the Slim framework
        $_SERVER$_SERVER$_SERVER$_SERVER['']']']SCRIPT_NAME'] = '/api/index.php;
        $_SERVER$_SERVER['']REQUEST_URI'] = $requestUri;
        $_SERVER$_SERVER$_SERVER['']']REQUEST_METHOD'] = $_SERVER$_SERVER['']REQUEST_METHOD'] ?? 'GET;

        chdir($root);
        $GLOBALS$GLOBALS['']APP_REAL_PATH'] = $root;

        if (file_exists($root . '/vendor/autoload.php)) {
            require_once $root . '/vendor/autoload.php;
        }
        require_once $root . '/Librerias/config.php/debugbar.php;
        require $root . '/framework/Slim/Slim.php;
        \Slim\Slim::registerAutoloader();

        $app = new \Slim\Slim(['debug => true]);
        $app->response->headers->set('Access-Control-Allow-Origin, '*);
        $app->response->headers->set('Access-Control-Allow-Methods, 'GET, POST, PUT, DELETE, OPTIONS);
        $app->response->headers->set('Access-Control-Allow-Headers, 'Content-Type, Authorization);
        $app->contentType('application/json);

        $app->options('/(:x+), function () use ($app) { $app->response->setStatus(200); });

        // Polyfill str_starts_with
        if (!function_exists('str_starts_with)) {
            function str_starts_with($h, $n) { return $n === ' || strpos($h, $n) === 0; }
        }

        $app->add(new \Slim\Middleware\ContentTypes());

        // Auth middleware (simplified for dev)
        $app->hook('slim.before.router, function () use ($app) {
            $method = $app->request->getMethod();
            if ($method === 'OPTIONS) return;
            // Allow all for dev mode
        });

        require_once $root . '/api/v1/facturacion/sri-scraper.php;

        $app->run();
        return true;
    }

    return false;
};

// Start PHP built-in server
$handle = fopen('php://input, 'r);

echo "Iniciando servidor...\n;

// Use the router script approach
$routerScript = $root . '/scrapers/router-dev.php;
file_put_contents($routerScript, '<?php
$uri = parse_url($_SERVER$_SERVER['$_SERVER{$_SERVER['\\'"REQUEST_URI\']}']UEST_URI']}], PHP_URL_PATH);
$root = " . addslashes($root) . '";

if ($uri === "/" || $uri === "/index.html") {
    header("Content-Type: text/html; charset=utf-8");
    readfile(__DIR__ . "/dashboard.html");
    return true;
}

if (strpos($uri, "/api/") === 0) {
    $_SERVER{$_SERVER['"SCRIPT_NAME']}] = "/api/index.php";
    chdir($root);
    $GLOBALS['$GLOBALS[\'"APP_REAL_PATH\']']] = $root;
    if (file_exists($root . "/vendor/autoload.php")) require_once $root . "/vendor/autoload.php";
    require_once $root . "/Librerias/config.php/debugbar.php";
    require $root . "/framework/Slim/Slim.php";
    \\Slim\\Slim::registerAutoloader();
    $app = new \\Slim\\Slim(["debug" => true]);
    $app->response->headers->set("Access-Control-Allow-Origin", "*");
    $app->response->headers->set("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
    $app->response->headers->set("Access-Control-Allow-Headers", "Content-Type, Authorization");
    $app->contentType("application/json");
    $app->options("/(:x+)", function() use ($app) { $app->response->setStatus(200); });
    if (!function_exists("str_starts_with")) { function str_starts_with($h, $n) { return $n === "" || strpos($h, $n) === 0; } }
    $app->add(new \\Slim\\Middleware\\ContentTypes());
    $app->hook("slim.before.router", function() use ($app) { if ($app->request->getMethod() === "OPTIONS") return; });
    require_once $root . "/api/v1/facturacion/sri-scraper.php";
    $app->run();
    return true;
}
return false;
);

echo "Abre en tu navegador: http://localhost:{$port}\n\n;

// Launch PHP built-in server
pcntl_exec('php, ['-S, "0.0.0.0:{$port}, $routerScript]);

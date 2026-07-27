<?php
// Suprimir advertencias y noticias que puedan romper el JSON devuelto
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set("display_errors", 0);

// Capturar fatal errors como JSON
register_shutdown_function(function () {
    $e = error_get_last();
    if (
        $e &&
        in_array($e["type"], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])
    ) {
        header("Content-Type: application/json");
        http_response_code(500);
        error_log("Fatal error: {$e["message"]} in {$e["file"]}:{$e["line"]}");
        echo json_encode([
            "error" => "Error interno del servidor",
        ]);
    }
});

// Cambiamos el directorio de trabajo a la raíz del proyecto para que los requires relativos del código legacy funcionen
chdir(__DIR__ . "/../");

// Definir variables globales requeridas por el código legacy del ERP
$APP_REAL_PATH = realpath(__DIR__ . "/../");
$GLOBALS["APP_REAL_PATH"] = $APP_REAL_PATH;

if (file_exists("vendor/autoload.php")) {
    require_once "vendor/autoload.php";
}
require_once "Librerias/config.php/debugbar.php";

require "framework/Slim/Slim.php";
\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim([
    "debug" => false,
]);

// Habilitar CORS para permitir llamadas desde el frontend de React
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost:3001',
    'https://exa-contable.vercel.app',
];
$origin = $app->request->headers->get('Origin');
$allowedOrigin = in_array($origin, $allowedOrigins) ? $origin : $allowedOrigins[0];
$app->response->headers->set("Access-Control-Allow-Origin", $allowedOrigin);
$app->response->headers->set(
    "Access-Control-Allow-Methods",
    "GET, POST, PUT, DELETE, OPTIONS"
);
$app->response->headers->set(
    "Access-Control-Allow-Headers",
    "Content-Type, Authorization, X-Requested-With"
);
// Forzar content-type JSON en todas las respuestas
$app->contentType("application/json");

// Manejar preflight requests de OPTIONS para CORS
$app->options("/(:x+)", function () use ($app) {
    $app->response->setStatus(200);
});

// Polyfill para str_starts_with (PHP 7兼容)
if (!function_exists("str_starts_with")) {
    function str_starts_with($haystack, $needle)
    {
        return $needle === "" || strpos($haystack, $needle) === 0;
    }
}

$app->add(new \Slim\Middleware\ContentTypes());

/**
 * Middleware para autenticación mediante Bearer Token.
 * Extrae Emp_Cod y lo inyecta en el body si no está presente.
 * Para rutas protegidas, retorna 401 si el token es inválido.
 */
$app->hook("slim.before.router", function () use ($app) {
    $requestMethod = $app->request->getMethod();
    $resourceUri = $app->request->getResourceUri();

    // Siempre permitir OPTIONS (CORS preflight), test, y auth
    if ($requestMethod === "OPTIONS") {
        return;
    }
    if (preg_match("#^/v1/test|^/v1/auth/|^/v1/facturacion/|^/v1/docs#", $resourceUri)) {
        return;
    }

    // Leer header Authorization
    $authHeader = $app->request->headers->get("Authorization");
    if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
        $app->response->setStatus(401);
        $app->response->body(
            json_encode([
                "success" => false,
                "error" => "Token de autenticación requerido"
            ])
        );
        $app->stop();
    }

    $token = substr($authHeader, 7);
    $tokenData = validateAuthToken($token);
    if ($tokenData === false) {
        $app->response->setStatus(401);
        $app->response->body(
            json_encode([
                "success" => false,
                "error" => "Token inválido"
            ])
        );
        $app->stop();
    }

    $tokenUser = $tokenData['username'];
    $tokenEmpresa = $tokenData['empresa'];
    $tokenTime = $tokenData['time'];

    // Validar expiración (24 h)
    if ((int) $tokenTime < time() - 86400) {
        $app->response->setStatus(401);
        $app->response->body(
            json_encode([
                "success" => false,
                "error" => "Sesión expirada"
            ])
        );
        $app->stop();
    }

    // Inyectar Emp_Cod en body si la ruta lo necesita
    $body = getBody();
    if (is_array($body) && !isset($body["Emp_Cod"])) {
        $body["Emp_Cod"] = $tokenEmpresa;
        // Reemplazar el body parseado para que los controladores lo usen
        $GLOBALS["_API_BODY"] = $body;
    }
});

function getBody()
{
    if (isset($GLOBALS["_API_BODY"])) {
        return $GLOBALS["_API_BODY"];
    }
    $raw = "";
    if (class_exists("\\Slim\\Environment")) {
        $env = \Slim\Environment::getInstance();
        if ($env && isset($env["slim.input_original"])) {
            $raw = $env["slim.input_original"];
        }
    }
    if (empty($raw)) {
        $raw = file_get_contents("php://input");
    }
    if (empty($raw)) {
        $raw = file_get_contents("php://input", false, null, 0, 65535);
    }
    if (empty($raw)) {
        // Last resort: try $_POST for form-encoded
        return $_POST ?: [];
    }
    $parsed = json_decode($raw, true);
    return is_array($parsed) ? $parsed : [$raw];
}

if (!function_exists("utf8_encode_deep")) {
    function utf8_encode_deep(&$input)
    {
        if (is_string($input)) {
            $input = mb_convert_encoding($input, 'UTF-8', 'ISO-8859-1');
        } elseif (is_array($input)) {
            foreach ($input as &$value) {
                utf8_encode_deep($value);
            }
        } elseif (is_object($input)) {
            foreach ($input as &$value) {
                utf8_encode_deep($value);
            }
        }
    }
}

// Módulos con API REST existentes
require_once __DIR__ . "/v1/auth/auth.php";
require_once __DIR__ . "/v1/tesoreria/clientes.php";
require_once __DIR__ . "/v1/adquisiciones/proveedores.php";
require_once __DIR__ . "/v1/inventario/categorias.php";
require_once __DIR__ . "/v1/inventario/marcas.php";
require_once __DIR__ . "/v1/inventario/productos.php";
require_once __DIR__ . "/v1/relavera/manifiestos.php";
require_once __DIR__ . "/v1/facturacion/comprobantes.php";
require_once __DIR__ . "/v1/facturacion/emitir.php";
require_once __DIR__ . "/v1/facturacion/sri-scraper.php";
require_once __DIR__ . "/v1/auditoria/tareas.php";
require_once __DIR__ . "/v1/admin/conexion.php";
require_once __DIR__ . "/v1/admin/dashboard.php";

// Nuevos módulos legacy con API REST
require_once __DIR__ . "/v1/data/index.php";
require_once __DIR__ . "/v1/contabilidad/index.php";
require_once __DIR__ . "/v1/rrhh/index.php";
require_once __DIR__ . "/v1/compras/index.php";
require_once __DIR__ . "/v1/activosfijos/index.php";
require_once __DIR__ . "/v1/bodega/index.php";
require_once __DIR__ . "/v1/caja_chica/index.php";
require_once __DIR__ . "/v1/transportecarga/index.php";
require_once __DIR__ . "/v1/bananero/index.php";
require_once __DIR__ . "/v1/camaronera/index.php";
require_once __DIR__ . "/v1/tesoreria/bancos.php";
require_once __DIR__ . "/v1/admin/soporte.php";
require_once __DIR__ . "/v1/admin/modulo-uso.php";
require_once __DIR__ . "/v1/admin/directorio.php";
require_once __DIR__ . "/v1/flujo/index.php";

// Swagger UI - Documentación de la API
$app->get('/v1/docs', function () use ($app) {
    $app->response->headers->set('Content-Type', 'text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>EXA Contable API - Documentación</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
    <style>body{margin:0;padding:0;}</style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({
            url: ' . json_encode($app->request->getUrl() . $app->request->getRootUri() . '/v1/docs/openapi.json') . ',
            dom_id: "#swagger-ui",
            presets: [SwaggerUIBundle.presets.apis, SwaggerUIBundle.SwaggerUIStandalonePreset],
            layout: "BaseLayout"
        });
    </script>
</body>
</html>';
});

$app->get('/v1/docs/openapi.json', function () use ($app) {
    $spec = json_decode(file_get_contents(__DIR__ . '/openapi.json'), true);
    echo json_encode($spec);
});

$app->get("/v1/test", function () {
    echo json_encode(["mysqli" => function_exists("mysqli_connect")]);
});

$app->run();
?>

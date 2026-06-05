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
        echo json_encode([
            "error" => "Fatal: {$e["message"]} in {$e["file"]}:{$e["line"]}",
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
    "debug" => true,
]);

// Habilitar CORS para permitir llamadas desde el frontend de React
$app->response->headers->set("Access-Control-Allow-Origin", "*");
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
    if (preg_match("#^/v1/test|^/v1/auth/#", $resourceUri)) {
        return;
    }

    // Leer header Authorization
    $authHeader = $app->request->headers->get("Authorization");
    if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
        $app->response->setStatus(401);
        $app->response->body(json_encode([
            "success" => false,
            "error" => "Token de autenticación requerido",
        ]));
        $app->response->send();
        exit;
    }

    $token = substr($authHeader, 7);
    $decoded = base64_decode($token, true);
    if ($decoded === false || substr_count($decoded, ":") < 2) {
        $app->response->setStatus(401);
        $app->response->body(json_encode([
            "success" => false,
            "error" => "Token inválido",
        ]));
        $app->response->send();
        exit;
    }

    [$tokenUser, $tokenEmpresa, $tokenTime] = explode(":", $decoded, 3);

    if ((int) $tokenTime < time() - 86400) {
        $app->response->setStatus(401);
        $app->response->body(json_encode([
            "success" => false,
            "error" => "Sesión expirada",
        ]));
        $app->response->send();
        exit;
    }

    $token = substr($authHeader, 7); // quitar "Bearer "
    $decoded = base64_decode($token, true);
    if ($decoded === false || substr_count($decoded, ":") < 2) {
        $app->response->setStatus(401);
        $app->response->body(
            json_encode([
                "success" => false,
                "error" => "Token inválido"
            ]) );
        return;
    }

    [$tokenUser, $tokenEmpresa, $tokenTime] = explode(":", $decoded, 3);

    // Opcional: validar expiración (24 h)
    if ((int) $tokenTime < time() - 86400) {
        $app->response->setStatus(401);
        $app->response->body(
            json_encode([
                "success" => false,
                "error" => "Sesión expirada"
            ]) );
        return;
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
    return json_decode(file_get_contents("php://input"), true);
}

if (!function_exists("utf8_encode_deep")) {
    function utf8_encode_deep(&$input)
    {
        if (is_string($input)) {
            $input = utf8_encode($input);
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

require_once __DIR__ . "/v1/auth/auth.php";
require_once __DIR__ . "/v1/tesoreria/clientes.php";
require_once __DIR__ . "/v1/adquisiciones/proveedores.php";
require_once __DIR__ . "/v1/inventario/categorias.php";
require_once __DIR__ . "/v1/inventario/marcas.php";
require_once __DIR__ . "/v1/inventario/productos.php";
require_once __DIR__ . "/v1/relavera/manifiestos.php";

$app->get("/v1/test", function () {
    echo json_encode(["mysqli" => function_exists("mysqli_connect")]);
});

$app->run();
?>

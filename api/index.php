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
            "detail" => $e["message"],
            "file" => basename($e["file"]),
            "line" => $e["line"]
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

    // Siempre permitir OPTIONS (CORS preflight), test, docs, openapi.json y auth
    if ($requestMethod === "OPTIONS") {
        return;
    }
    if (preg_match("#^/v1/test|^/test|^/v1/auth/|^/v1/facturacion/|^/v1/docs|^/docs|openapi\\.json|^/v1/api-tokens-demo|^/v1/api-tokens-probar#", $resourceUri)) {
        return;
    }

    // Rutas administrativas del panel: además del Bearer, se acepta la sesión
    // activa del panel de administración (misma cookie de sesión en producción).
    $esAdminSession = false;
    if (preg_match("#^/v1/admin/#", $resourceUri)) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $esAdminSession = !empty($_SESSION['Ses_Usu_Cod']);
        if ($esAdminSession) {
            return;
        }
    }

    // Leer header Authorization con múltiples fallbacks para máxima compatibilidad
    $authHeader = $app->request->headers->get("Authorization");
    if (empty($authHeader) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if (empty($authHeader) && function_exists('apache_request_headers')) {
        $reqHeaders = apache_request_headers();
        $authHeader = $reqHeaders['Authorization'] ?? ($reqHeaders['authorization'] ?? null);
    }
    if (empty($authHeader)) {
        $authHeader = $_GET['token'] ?? ($_GET['api_token'] ?? ($_GET['access_token'] ?? ($_POST['token'] ?? null)));
    }

    $rawAuth = trim((string)$authHeader);
    if ($rawAuth === '') {
        $app->response->setStatus(401);
        $app->response->body(
            json_encode([
                "success" => false,
                "error" => "Token de autenticación requerido"
            ])
        );
        $app->stop();
    }

    // Limpiar prefijo Bearer si viene incluido
    $token = $rawAuth;
    while (stripos($token, 'Bearer ') === 0) {
        $token = trim(substr($token, 7));
    }

    $tokenData = validateAuthToken($token);
    $managedData = null;

    // Si el token HMAC del login no es válido, intentar con un token gestionado
    // (creado desde el panel de administración con límite de consultas).
    if ($tokenData === false) {
        if (!class_exists('APITokenManager')) {
            require_once __DIR__ . "/../classes/APITokenManager.php";
        }
        try {
            $mgr = new APITokenManager();
            $managed = $mgr->validate($token, true);
            if ($managed && !empty($managed['valid'])) {
                $managedData = $managed;
            }
        } catch (\Throwable $e) {
            error_log("Validación token gestionado error: " . $e->getMessage());
        }
    }

    if ($tokenData === false && $managedData === null) {
        $app->response->setStatus(401);
        $app->response->body(
            json_encode([
                "success" => false,
                "error" => "Token inválido"
            ])
        );
        $app->stop();
    }

    if ($managedData !== null) {
        // Token gestionado: verificar permisos por módulo/proceso si existen
        $permisosMgr = new APITokenManager();
        $permisos = $permisosMgr->getPermisos($managedData['id']);
        if (!empty($permisos)) {
            $rutaActual = $resourceUri;
            $permitido = false;
            foreach ($permisos as $p) {
                $rutaPermitida = $p['Tip_Ruta'];
                if ($rutaActual === $rutaPermitida ||
                    strpos($rutaActual, rtrim($rutaPermitida, '/') . '/') === 0 ||
                    strpos('/api' . $rutaActual, rtrim($rutaPermitida, '/') . '/') === 0 ||
                    '/api' . $rutaActual === $rutaPermitida) {
                    $permitido = true;
                    break;
                }
            }
            if (!$permitido) {
                $app->response->setStatus(403);
                $app->response->body(
                    json_encode([
                        "success" => false,
                        "error" => "El token no tiene permisos para acceder a esta ruta ({$rutaActual})"
                    ])
                );
                $app->stop();
            }
        }

        $tokenEmpresa = $managedData['Emp_Cod'];
        $GLOBALS["_API_BDD"] = $managedData['Bdd'];
    } else {
        // Token HMAC del login
        if (time() - $tokenData['timestamp'] > 86400) {
            $app->response->setStatus(401);
            $app->response->body(
                json_encode([
                    "success" => false,
                    "error" => "Sesión expirada"
                ])
            );
            $app->stop();
        }
        $tokenEmpresa = $tokenData['empresa'];
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
        $body = $GLOBALS["_API_BODY"];
        // Inyectar Bdd si fue derivada de un token gestionado
        if (isset($GLOBALS["_API_BDD"]) && is_array($body) && !isset($body["Bdd"])) {
            $body["Bdd"] = $GLOBALS["_API_BDD"];
            $GLOBALS["_API_BODY"] = $body;
        }
        return $body;
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
    $parsed = !empty($raw) ? json_decode($raw, true) : null;
    $body = is_array($parsed) ? $parsed : [];
    if (!empty($_GET)) {
        $body = array_merge($_GET, $body);
    }
    if (!empty($_POST)) {
        $body = array_merge($_POST, $body);
    }
    if (isset($GLOBALS["_API_BDD"]) && !isset($body["Bdd"])) {
        $body["Bdd"] = $GLOBALS["_API_BDD"];
    }
    $GLOBALS["_API_BODY"] = $body;
    return $body;
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

// Endpoint de test y diagnóstico
$app->get('/v1/test', function () use ($app) {
    if (!class_exists('APITokenManager')) {
        require_once __DIR__ . "/../classes/APITokenManager.php";
    }
    $mgr = new APITokenManager();
    $token = "8e316143f520292e0f3ade7c548b1918e622348df03ffb3ef6fb6d4e1aec99a8";
    $val = $mgr->validate($token, false);
    echo json_encode([
        'success' => true,
        'php_version' => PHP_VERSION,
        'info' => $mgr->empresaInfo(620),
        'token_validation' => $val,
    ]);
});

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
require_once __DIR__ . "/v1/admin/api-tokens.php";
require_once __DIR__ . "/v1/flujo/index.php";

// Endpoints de Directorio Operativo
require_once __DIR__ . "/v1/contactos.php";
require_once __DIR__ . "/v1/plantas.php";
require_once __DIR__ . "/v1/choferes.php";
require_once __DIR__ . "/v1/vehiculos.php";

// Swagger UI - Documentación de la API
$docsHandler = function () use ($app) {
    $app->response->headers->set('Content-Type', 'text/html; charset=utf-8');
    readfile(__DIR__ . '/v1/docs/index.php');
};

$app->get('/v1/docs', $docsHandler);
$app->get('/v1/docs/', $docsHandler);
$app->get('/docs', $docsHandler);
$app->get('/docs/', $docsHandler);

$openapiHandler = function () use ($app) {
    $app->response->headers->set('Content-Type', 'application/json; charset=utf-8');
    $fullSpecPath = __DIR__ . '/openapi.json';
    if (!is_file($fullSpecPath)) {
        echo json_encode(array('openapi' => '3.0.3', 'info' => array('title' => 'EXA Contable API', 'version' => '1.0.0'), 'paths' => new stdClass()));
        return;
    }

    $isFull = isset($_GET['full']) || isset($_GET['all']) || 
              (isset($_GET['view']) && in_array(strtolower($_GET['view']), array('all', 'full', 'completo'), true)) ||
              (isset($_GET['mode']) && in_array(strtolower($_GET['mode']), array('all', 'full', 'completo'), true));
    
    $modulo = isset($_GET['modulo']) ? trim($_GET['modulo']) : (isset($_GET['tag']) ? trim($_GET['tag']) : '');

    // Si se solicita la especificación completa (vía parámetro explícito)
    if ($isFull) {
        readfile($fullSpecPath);
        return;
    }

    // Cargar y filtrar la especificación OpenAPI
    $raw = file_get_contents($fullSpecPath);
    $spec = json_decode($raw, true);
    if (!is_array($spec)) {
        readfile($fullSpecPath);
        return;
    }

    if (!empty($modulo) && strtolower($modulo) !== 'all') {
        // Filtrar por módulo / tag específico
        $filteredPaths = array();
        $modLower = strtolower($modulo);
        if (isset($spec['paths']) && is_array($spec['paths'])) {
            foreach ($spec['paths'] as $path => $methods) {
                if (is_array($methods)) {
                    foreach ($methods as $method => $op) {
                        if (isset($op['tags']) && is_array($op['tags'])) {
                            foreach ($op['tags'] as $tag) {
                                if (strtolower($tag) === $modLower) {
                                    if (!isset($filteredPaths[$path])) $filteredPaths[$path] = array();
                                    $filteredPaths[$path][$method] = $op;
                                }
                            }
                        }
                    }
                }
            }
        }
        $spec['paths'] = !empty($filteredPaths) ? $filteredPaths : new stdClass();
        $spec['tags'] = array_values(array_filter(isset($spec['tags']) ? $spec['tags'] : array(), function ($t) use ($modLower) {
            return strtolower(isset($t['name']) ? $t['name'] : '') === $modLower;
        }));
        $spec['info']['description'] = "Documentación de la API REST - Módulo: **" . htmlspecialchars($modulo) . "**.";
    } else {
        // Por defecto: Directorio Operativo: Contactos, Plantas, Choferes, Vehículos
        $publicPaths = array('/v1/contactos', '/v1/plantas', '/v1/choferes', '/v1/vehiculos');
        $filteredPaths = array();
        foreach ($publicPaths as $p) {
            if (isset($spec['paths'][$p])) {
                $filteredPaths[$p] = $spec['paths'][$p];
            }
        }
        $spec['paths'] = !empty($filteredPaths) ? $filteredPaths : new stdClass();

        $spec['tags'] = array(
            array(
                'name' => 'contactos',
                'description' => 'Directorio de contactos autorizados para notificaciones operativas'
            ),
            array(
                'name' => 'plantas',
                'description' => 'Directorio de plantas de beneficio y ubicaciones operativas'
            ),
            array(
                'name' => 'choferes',
                'description' => 'Directorio de choferes y conductores por planta'
            ),
            array(
                'name' => 'vehiculos',
                'description' => 'Directorio de volquetas mineras y vehículos de carga por planta'
            )
        );

        $spec['info']['description'] = "## EXA Contable API - Directorio Operativo\n\n" .
            "Endpoints autorizados para la integración y consulta de contactos para notificaciones, plantas de beneficio, choferes de planta y vehículos/volquetas asignadas.\n\n" .
            "Requiere autenticación mediante token Bearer con permisos habilitados sobre cada recurso.";
    }

    echo json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
};

$app->get('/v1/docs/openapi.json', $openapiHandler);
$app->get('/docs/openapi.json', $openapiHandler);
$app->get('/v1/openapi.json', $openapiHandler);
$app->get('/openapi.json', $openapiHandler);

// Guía de consumo en HTML (pública)
$app->get('/v1/docs/guia', function () use ($app) {
    $file = __DIR__ . '/docs/guia.html';
    if (!is_file($file)) {
        $app->response->setStatus(404);
        $app->response->headers->set('Content-Type', 'application/json');
        echo json_encode(['success' => false, 'error' => 'Guía no encontrada']);
        return;
    }
    $app->response->headers->set('Content-Type', 'text/html; charset=utf-8');
    echo file_get_contents($file);
});

// Demo interactiva de tokens con permisos por módulo/proceso (pública, mismo origen)
$app->get('/v1/api-tokens-demo', function () use ($app) {
    $file = __DIR__ . '/docs/api-tokens-demo.html';
    if (!is_file($file)) {
        $app->response->setStatus(404);
        $app->response->headers->set('Content-Type', 'application/json');
        echo json_encode(['success' => false, 'error' => 'Demo no encontrada']);
        return;
    }
    $app->response->headers->set('Content-Type', 'text/html; charset=utf-8');
    echo file_get_contents($file);
});

// Herramienta para probar un token (pública, mismo origen) — solo pega el token
$app->get('/v1/api-tokens-probar', function () use ($app) {
    $file = __DIR__ . '/docs/api-tokens-probar.html';
    if (!is_file($file)) {
        $app->response->setStatus(404);
        $app->response->headers->set('Content-Type', 'application/json');
        echo json_encode(['success' => false, 'error' => 'Herramienta no encontrada']);
        return;
    }
    $app->response->headers->set('Content-Type', 'text/html; charset=utf-8');
    echo file_get_contents($file);
});

$app->run();

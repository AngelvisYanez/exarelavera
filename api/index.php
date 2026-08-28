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

    // Si se solicita la especificación completa
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
        $spec['info']['description'] = "Documentación de la API REST - Módulo: **" . htmlspecialchars($modulo) . "**.\n\nPara ver otros módulos o la vista completa, utilice el selector superior.";
    } else {
        // Por defecto: Solo contacto GET (/v1/contactos GET)
        $contactoPath = isset($spec['paths']['/v1/contactos']) ? $spec['paths']['/v1/contactos'] : null;
        if ($contactoPath && isset($contactoPath['get'])) {
            $spec['paths'] = array(
                '/v1/contactos' => array(
                    'get' => $contactoPath['get']
                )
            );
        } else {
            $spec['paths'] = new stdClass();
        }

        $spec['tags'] = array(
            array(
                'name' => 'contactos',
                'description' => 'Directorio de contactos autorizados para notificaciones (integración ERP Locator, solo lectura)'
            )
        );

        $spec['info']['description'] = "## EXA Contable API - Directorio de Contactos (GET)\n\n" .
            "Esta documentación está configurada en **modo restringido** mostrando únicamente el endpoint `GET /v1/contactos`.\n\n" .
            "> 💡 **Nota:** Para explorar y activar la documentación de todos los demás módulos del ERP (Contabilidad, Facturación, Tesorería, Inventario, RRHH, etc.), active la vista completa en el selector superior o agregue `?full=1` a la URL.";
    }

    echo json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
};

$app->get('/v1/docs/openapi.json', $openapiHandler);
$app->get('/docs/openapi.json', $openapiHandler);
$app->get('/v1/openapi.json', $openapiHandler);
$app->get('/openapi.json', $openapiHandler);

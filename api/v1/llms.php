<?php
/**
 * Definiciones LLM / agentes para la API de EXA Contable.
 *
 * Requieren un TOKEN GESTIONADO válido (creado desde el panel de administración).
 * Un token del login (HMAC) NO es suficiente aquí.
 */
if (!class_exists('APITokenManager')) {
    require_once __DIR__ . '/../../classes/APITokenManager.php';
}

function llm_require_managed_token($app)
{
    $authHeader = $app->request->headers->get("Authorization");
    if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
        $app->response->setStatus(401);
        $app->response->body(json_encode([
            "success" => false,
            "error" => "Se requiere un token de acceso gestionado (Bearer). Genera uno en Herramientas → Tokens de Acceso a la API."
        ]));
        $app->stop();
        return;
    }
    $raw = substr($authHeader, 7);
    $mgr = new APITokenManager();
    $res = $mgr->validate($raw, true);
    if (!$res || !$res['valid']) {
        $app->response->setStatus(401);
        $app->response->body(json_encode([
            "success" => false,
            "error" => "Token inválido, expirado o cuota agotada"
        ]));
        $app->stop();
    }
}

// Índice público (puente) de recursos para LLM/agentes
$app->get('/llms.txt', function () use ($app) {
    llm_require_managed_token($app);
    $base = $app->request->getUrl() . $app->request->getRootUri();
    $content = "# EXA Contable API\n\n"
        . "API REST para integraciones con EXA Contable (ERP).\n\n"
        . "> Requiere un token de acceso gestionado (header `Authorization: Bearer <token>`).\n"
        . "> Genera el token desde el panel de administración → Herramientas → Tokens de Acceso a la API.\n\n"
        . "## Recursos\n\n"
        . "- [Documentación swagger]($base/v1/docs)\n"
        . "- [Guía de consumo]($base/v1/docs/guia)\n"
        . "- [Manifiesto para agentes]($base/.well-known/ai-plugin.json) (requiere token)\n"
        . "- [Descripción completa]($base/v1/llms/llms-full.txt) (requiere token)\n";
    $app->response->headers->set('Content-Type', 'text/plain; charset=utf-8');
    echo $content;
});

// Manifiesto de agente (OpenAI GPTs / Plugin style)
$app->get('/.well-known/ai-plugin.json', function () use ($app) {
    llm_require_managed_token($app);
    $base = $app->request->getUrl() . $app->request->getRootUri();
    $manifest = [
        "schema_version" => "v1",
        "name_for_human" => "EXA Contable API",
        "name_for_model" => "exa_contable_api",
        "description_for_human" => "Consulta y operación del ERP EXA Contable a través de su API REST.",
        "description_for_model" => "API REST de EXA Contable. Proporciona acceso a contabilidad, tesorería, facturación, inventario y más. Autenticación por header Bearer con el token del consumidor. Respuestas en JSON con {success, data, message}.",
        "api" => [
            "type" => "openapi",
            "url" => "$base/v1/docs/openapi.json"
        ],
        "auth" => [
            "type" => "bearer", // requiere el token gestionado
            "instructions" => "El cliente debe enviar su token de acceso en el header Authorization: Bearer <token>."
        ],
        "contact_email" => "soporte@exacontable.local",
        "legal_info_url" => "$base/v1/docs/guia",
        "logo_url" => "",
    ];
    $app->response->headers->set('Content-Type', 'application/json; charset=utf-8');
    echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
});

// Descripción completa en Markdown para LLM (llms-full.txt)
$app->get('/v1/llms/llms-full.txt', function () use ($app) {
    llm_require_managed_token($app);
    $base = $app->request->getUrl() . $app->request->getRootUri();

    // Construir el directorio de módulos y procesos desde la especificación OpenAPI
    $directorio = "## Módulos y procesos\n";
    $openapiFile = __DIR__ . '/../openapi.json';
    if (is_file($openapiFile)) {
        $spec = json_decode(file_get_contents($openapiFile), true);
        $mods = [];
        if (isset($spec['tags']) && is_array($spec['tags'])) {
            foreach ($spec['tags'] as $t) {
                $mods[$t['name']] = ['desc' => $t['description'] ?? '', 'rutas' => []];
            }
        }
        $metodos = ['get' => 'GET', 'post' => 'POST', 'put' => 'PUT', 'delete' => 'DELETE'];
        foreach (($spec['paths'] ?? []) as $ruta => $ops) {
            foreach ($metodos as $met => $metodo) {
                if (!isset($ops[$met])) {
                    continue;
                }
                $op = $ops[$met];
                $tag = (isset($op['tags'][0]) ? $op['tags'][0] : 'general');
                if (!isset($mods[$tag])) {
                    $mods[$tag] = ['desc' => '', 'rutas' => []];
                }
                $mods[$tag]['rutas'][] = sprintf(
                    "- %s `%s` — %s",
                    $metodo,
                    $ruta,
                    $op['summary'] ?? $ruta
                );
            }
        }
        ksort($mods);
        foreach ($mods as $name => $m) {
            $directorio .= "\n### " . $name . ($m['desc'] ? ' — ' . $m['desc'] : '') . "\n";
            if (empty($m['rutas'])) {
                $directorio .= "- (sin procesos)\n";
                continue;
            }
            sort($m['rutas']);
            $directorio .= implode("\n", $m['rutas']) . "\n";
        }
    } else {
        $directorio .= "No disponible en este entorno.\n";
    }

    $content = "# EXA Contable API — Documentación completa para agentes/LLM\n\n"
        . "## Autenticación\n"
        . "Todas las rutas protegidas requieren el header:\n"
        . "`Authorization: Bearer <token_gestionado>`\n"
        . "El token se genera desde el panel de administración (Herramientas → Tokens de Acceso a la API). "
        . "Cada token pertenece a una empresa (Emp_Cod) y a su base de datos (Bdd), y puede tener límite de consultas por día o mes. "
        . "Los tokens pueden restringirse a módulos/procesos concretos; consumir un recurso no autorizado devuelve 403.\n\n"
        . "## Base URL\n"
        . "$base\n\n"
        . "## Formato de respuesta\n"
        . "JSON: `{\"success\": bool, \"data\": [...], \"message\": string}`\n"
        . "Códigos: 200/201 éxito · 400 parámetros inválidos · 401 token ausente/inválido/cuota agotada · 403 token sin permisos para ese módulo/proceso · 404 no encontrado · 500 error interno.\n\n"
        . "## Autenticación por usuario (login)\n"
        . "- `POST /v1/auth/empresas` body `{\"username\": ...}` → empresas del usuario.\n"
        . "- `POST /v1/auth/login` body `{\"username\":..., \"password\":..., \"empresa\":...}` → `token` de sesión (24h).\n"
        . "El token de login también se acepta como Bearer en las rutas de negocio.\n\n"
        . "## Convenciones\n"
        . "- Identificador de empresa: `Emp_Cod`; base distribuidas: `Bdd`.\n"
        . "- Paginación: `page`, `perPage`.\n"
        . "- Nota: las rutas `/v1/facturacion/*` están exentas de autenticación (bypass).\n\n"
        . $directorio . "\n"
        . "## Especificación OpenAPI\n"
        . "Consume `$base/v1/docs/openapi.json` (requiere token si lo llamas programáticamente).\n\n"
        . "## Seguridad\n"
        . "- Endpoint `/v1/data/query` ejecuta SQL libre: úsalo con extremo cuidado y solo si tienes autorización.\n"
        . "- No compartas tokens; revoca los que no uses.\n";
    $app->response->headers->set('Content-Type', 'text/plain; charset=utf-8');
    echo $content;
});

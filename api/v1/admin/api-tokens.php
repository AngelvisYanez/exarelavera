<?php
require_once __DIR__ . '/../../../classes/APITokenManager.php';

$app->get('/v1/admin/api-tokens', function () use ($app) {
    try {
        $mgr = new APITokenManager();
        $empCod = isset($_GET['Emp_Cod']) ? (int)$_GET['Emp_Cod'] : null;
        $tokens = $mgr->list($empCod);
        utf8_encode_deep($tokens);
        echo json_encode(['success' => true, 'data' => $tokens]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->get('/v1/admin/api-tokens/empresas', function () use ($app) {
    try {
        $mgr = new APITokenManager();
        $con = new MysqlConexion('exa_master');
        $datos = new MysqlDatos();
        $rows = $datos->getArrayConsultaSql(
            "SELECT e.Emp_Cod, e.Emp_Nom, d.Dat_Dis AS Bdd
               FROM empresas e
               LEFT JOIN data d ON d.Emp_Cod = e.Emp_Cod
              WHERE e.Emp_Est='A'
              ORDER BY e.Emp_Nom ASC",
            $con
        );
        utf8_encode_deep($rows);
        echo json_encode(['success' => true, 'data' => $rows]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/api-tokens/generar', function () use ($app) {
    $body = getBody();
    try {
        $mgr = new APITokenManager();
        $res = $mgr->generate(
            $body['nombre'] ?? '',
            $body['Emp_Cod'] ?? 0,
            $body['cuota'] ?? 0,
            $body['periodo'] ?? 'D',
            $body['expira'] ?? null,
            $body['creadoPor'] ?? null,
            isset($body['permisos']) && is_array($body['permisos']) ? $body['permisos'] : null
        );
        if (!$res['success']) {
            $app->response->setStatus(400);
            echo json_encode($res);
            return;
        }
        // El token en claro se devuelve UNA sola vez
        echo json_encode([
            'success' => true,
            'message' => 'Token creado. Guárdalo ahora, no se mostrará de nuevo.',
            'data' => $res,
        ]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/api-tokens/actualizar', function () use ($app) {
    $body = getBody();
    try {
        $mgr = new APITokenManager();
        $res = $mgr->updateLimits(
            $body['Tok_Id'] ?? 0,
            isset($body['cuota']) ? $body['cuota'] : null,
            isset($body['periodo']) ? $body['periodo'] : null,
            isset($body['expira']) ? $body['expira'] : null,
            isset($body['estado']) ? $body['estado'] : null
        );
        if (!$res['success']) {
            $app->response->setStatus(400);
        }
        echo json_encode($res);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/api-tokens/revocar', function () use ($app) {
    $body = getBody();
    try {
        $mgr = new APITokenManager();
        $res = $mgr->revoke($body['Tok_Id'] ?? 0);
        if (!$res['success']) {
            $app->response->setStatus(400);
        }
        echo json_encode($res);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/api-tokens/activar', function () use ($app) {
    $body = getBody();
    try {
        $mgr = new APITokenManager();
        $res = $mgr->updateLimits($body['Tok_Id'] ?? 0, null, null, null, 'A');
        if (!$res['success']) {
            $app->response->setStatus(400);
        }
        echo json_encode($res);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/api-tokens/reset-uso', function () use ($app) {
    $body = getBody();
    try {
        $mgr = new APITokenManager();
        $res = $mgr->resetUsage($body['Tok_Id'] ?? 0);
        if (!$res['success']) {
            $app->response->setStatus(400);
        }
        echo json_encode($res);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/api-tokens/eliminar', function () use ($app) {
    $body = getBody();
    try {
        $mgr = new APITokenManager();
        $res = $mgr->delete($body['Tok_Id'] ?? 0);
        if (!$res['success']) {
            $app->response->setStatus(400);
        }
        echo json_encode($res);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

// Catálogo de módulos/procesos de la API + permisos actuales del token (opcional)
$app->get('/v1/admin/api-tokens/permisos', function () use ($app) {
    try {
        $mgr = new APITokenManager();
        $r = $mgr->catalogo();
        if (!$r['success']) {
            $app->response->setStatus(500);
            echo json_encode($r);
            return;
        }
        $tokId = isset($_GET['Tok_Id']) ? (int)$_GET['Tok_Id'] : null;
        $permisos = [];
        if ($tokId) {
            $permisos = $mgr->getPermisos($tokId);
        }
        echo json_encode([
            'success' => true,
            'modulos' => $r['modulos'],
            'permisos' => $permisos,
        ]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

// Guardar permisos (módulos/procesos) de un token existente
$app->post('/v1/admin/api-tokens/permisos', function () use ($app) {
    $body = getBody();
    try {
        $mgr = new APITokenManager();
        $res = $mgr->setPermisos($body['Tok_Id'] ?? 0, isset($body['permisos']) && is_array($body['permisos']) ? $body['permisos'] : []);
        if (!$res['success']) {
            $app->response->setStatus(400);
        }
        echo json_encode($res);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

<?php
require_once __DIR__ . '/../../../classes/APITokenManager.php';

$app->get('/v1/admin/api-tokens', function () use ($app) {
    try {
        $mgr = new APITokenManager();
        $empCod = isset($_GET['Emp_Cod']) ? (int)$_GET['Emp_Cod'] : null;
        $tokens = $mgr->listTokens($empCod);
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

$app->get('/v1/admin/api-tokens/:id/permisos', function ($id) use ($app) {
    try {
        $mgr = new APITokenManager();
        $permisos = $mgr->getPermisos((int)$id);
        utf8_encode_deep($permisos);
        echo json_encode(['success' => true, 'data' => $permisos]);
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
            isset($body['nombre']) ? $body['nombre'] : '',
            isset($body['Emp_Cod']) ? $body['Emp_Cod'] : 0,
            isset($body['cuota']) ? $body['cuota'] : 0,
            isset($body['periodo']) ? $body['periodo'] : 'D',
            isset($body['expira']) ? $body['expira'] : null,
            isset($body['creadoPor']) ? $body['creadoPor'] : null,
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

$app->post('/v1/admin/api-tokens/limites', function () use ($app) {
    $body = getBody();
    try {
        $mgr = new APITokenManager();
        $res = $mgr->updateLimits(
            isset($body['id']) ? $body['id'] : 0,
            isset($body['cuota']) ? $body['cuota'] : null,
            isset($body['periodo']) ? $body['periodo'] : null,
            isset($body['expira']) ? $body['expira'] : null,
            isset($body['estado']) ? $body['estado'] : null
        );
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
        $res = $mgr->revoke(isset($body['id']) ? $body['id'] : 0);
        echo json_encode($res);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/api-tokens/permisos', function () use ($app) {
    $body = getBody();
    try {
        $mgr = new APITokenManager();
        $tokenId = isset($body['id']) ? (int)$body['id'] : 0;
        $permisos = isset($body['permisos']) && is_array($body['permisos']) ? $body['permisos'] : [];
        $res = $mgr->setPermisos($tokenId, $permisos);
        echo json_encode($res);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

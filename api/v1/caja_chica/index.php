<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ApiResponse.php';

// ============================================================
// CAJAS CHICAS
// ============================================================
$app->map('/v1/caja-chica/cajas', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod'], 'Cch_Est' => 'A'];
        $data = $api->list('caja_chica', $where, 'Cch_Des ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
})->via('GET', 'POST')->via('GET', 'POST');

$app->post('/v1/caja-chica/cajas/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Cch_Des', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Cch_Des' => $body['Cch_Des'],
            'Cch_Sal' => $body['Cch_Sal'] ?? 0,
            'Cch_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $cchCod = $api->insert('caja_chica', $data);
        ApiResponse::created(['Cch_Cod' => $cchCod], 'Caja chica creada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/caja-chica/cajas/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Cch_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Cch_Des'])) $data['Cch_Des'] = $body['Cch_Des'];
        if (isset($body['Cch_Sal'])) $data['Cch_Sal'] = $body['Cch_Sal'];
        if (isset($body['Cch_Est'])) $data['Cch_Est'] = $body['Cch_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('caja_chica', $data, 'Cch_Cod', $body['Cch_Cod']);
        ApiResponse::success(['Cch_Cod' => $body['Cch_Cod']], 'Caja chica modificada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/caja-chica/cajas/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Cch_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->softDelete('caja_chica', 'Cch_Cod', $body['Cch_Cod'], 'Cch_Est');
        ApiResponse::success(null, 'Caja chica eliminada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// MOVIMIENTOS CAJA CHICA
// ============================================================
$app->map('/v1/caja-chica/movimientos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT m.*, cc.Cch_Des
                FROM caja_chica_mov m
                INNER JOIN caja_chica cc ON m.Cch_Cod = cc.Cch_Cod
                WHERE cc.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY m.Ccm_Fec DESC
                LIMIT 500";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
})->via('GET', 'POST')->via('GET', 'POST');

$app->post('/v1/caja-chica/movimientos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Cch_Cod', 'Ccm_Fec', 'Ccm_Des', 'Ccm_Tip', 'Ccm_Val'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Cch_Cod' => $body['Cch_Cod'],
            'Ccm_Fec' => $body['Ccm_Fec'],
            'Ccm_Des' => $body['Ccm_Des'],
            'Ccm_Tip' => $body['Ccm_Tip'],
            'Ccm_Val' => $body['Ccm_Val']
        ];
        $ccmCod = $api->insert('caja_chica_mov', $data);
        ApiResponse::created(['Ccm_Cod' => $ccmCod], 'Movimiento registrado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// REPOSICIONES
// ============================================================
$app->post('/v1/caja-chica/reposiciones', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT r.*, cc.Cch_Des
                FROM caja_chica_reposicion r
                INNER JOIN caja_chica cc ON r.Cch_Cod = cc.Cch_Cod
                WHERE cc.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY r.Rep_Fec DESC
                LIMIT 200";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/caja-chica/reposiciones/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Cch_Cod', 'Rep_Fec', 'Rep_Val'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Cch_Cod' => $body['Cch_Cod'],
            'Rep_Fec' => $body['Rep_Fec'],
            'Rep_Val' => $body['Rep_Val'],
            'Rep_Des' => $body['Rep_Des'] ?? ''
        ];
        $repCod = $api->insert('caja_chica_reposicion', $data);
        ApiResponse::created(['Rep_Cod' => $repCod], 'Reposición creada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

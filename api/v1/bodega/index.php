<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ApiResponse.php';

// ============================================================
// BODEGAS
// ============================================================
$app->map('/v1/bodega/bodegas', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod'], 'Bod_Est' => 'A'];
        $data = $api->list('bodega', $where, 'Bod_Des ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
})->via('GET', 'POST')->via('GET', 'POST');

$app->post('/v1/bodega/bodegas/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Bod_Des', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Bod_Des' => $body['Bod_Des'],
            'Bod_Dir' => $body['Bod_Dir'] ?? '',
            'Bod_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        if (isset($body['Suc_Cod'])) $data['Suc_Cod'] = $body['Suc_Cod'];
        $bodCod = $api->insert('bodega', $data);
        ApiResponse::created(['Bod_Cod' => $bodCod], 'Bodega creada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/bodega/bodegas/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Bod_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Bod_Des'])) $data['Bod_Des'] = $body['Bod_Des'];
        if (isset($body['Bod_Dir'])) $data['Bod_Dir'] = $body['Bod_Dir'];
        if (isset($body['Bod_Est'])) $data['Bod_Est'] = $body['Bod_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('bodega', $data, 'Bod_Cod', $body['Bod_Cod']);
        ApiResponse::success(['Bod_Cod' => $body['Bod_Cod']], 'Bodega modificada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/bodega/bodegas/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Bod_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->softDelete('bodega', 'Bod_Cod', $body['Bod_Cod'], 'Bod_Est');
        ApiResponse::success(null, 'Bodega eliminada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// KARDEX
// ============================================================
$app->map('/v1/bodega/kardex', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT k.*, p.Pro_Des, p.Pro_Cod
                FROM kardex_ie k
                INNER JOIN producto p ON k.Pro_Cod = p.Pro_Cod
                WHERE p.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY k.Kar_Fec DESC, k.Kar_Hor DESC
                LIMIT 500";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
})->via('GET', 'POST')->via('GET', 'POST');

// ============================================================
// STOCK
// ============================================================
$app->map('/v1/bodega/stock', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT s.*, p.Pro_Des, p.Pro_Cod, b.Bod_Des
                FROM stock s
                INNER JOIN producto p ON s.Pro_Cod = p.Pro_Cod
                LEFT JOIN bodega b ON s.Bod_Cod = b.Bod_Cod
                WHERE p.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY p.Pro_Des ASC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
})->via('GET', 'POST')->via('GET', 'POST');

// ============================================================
// MOVIMIENTOS
// ============================================================
$app->map('/v1/bodega/movimientos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT m.*, p.Pro_Des, b.Bod_Des, tm.Tmo_Des
                FROM movimiento_bodega m
                INNER JOIN producto p ON m.Pro_Cod = p.Pro_Cod
                LEFT JOIN bodega b ON m.Bod_Cod = b.Bod_Cod
                LEFT JOIN tipo_movimiento tm ON m.Tmo_Cod = tm.Tmo_Cod
                WHERE p.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY m.Mov_Fec DESC
                LIMIT 500";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
})->via('GET', 'POST')->via('GET', 'POST');

$app->post('/v1/bodega/movimientos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pro_Cod', 'Bod_Cod', 'Mov_Tip', 'Mov_Can', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        $movimiento = [
            'Pro_Cod' => $body['Pro_Cod'],
            'Bod_Cod' => $body['Bod_Cod'],
            'Mov_Tip' => $body['Mov_Tip'],
            'Mov_Can' => $body['Mov_Can'],
            'Mov_Fec' => $body['Mov_Fec'] ?? date('Y-m-d'),
            'Mov_Hor' => $body['Mov_Hor'] ?? date('H:i:s'),
            'Mov_Des' => $body['Mov_Des'] ?? '',
            'Tmo_Cod' => $body['Tmo_Cod'] ?? null
        ];
        $movCod = $api->insert('movimiento_bodega', $movimiento);
        if ($movCod === false) {
            $api->rollback();
            ApiResponse::serverError('Error al crear movimiento: ' . $api->getErrorMsg());
            return;
        }
        $kardex = [
            'Pro_Cod' => $body['Pro_Cod'],
            'Bod_Cod' => $body['Bod_Cod'],
            'Kar_Tip' => $body['Mov_Tip'],
            'Kar_Can' => $body['Mov_Can'],
            'Kar_Fec' => $movimiento['Mov_Fec'],
            'Kar_Hor' => $movimiento['Mov_Hor']
        ];
        $api->insert('kardex_ie', $kardex);
        $api->commit();
        ApiResponse::created(['Mov_Cod' => $movCod], 'Movimiento registrado exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

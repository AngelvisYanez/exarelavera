<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ApiResponse.php';

// ============================================================
// PRODUCTORES CAMARONERA
// ============================================================
$app->post('/v1/camaronera/productores', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('productor_camaronera', ['Emp_Cod' => $body['Emp_Cod'], 'Prc_Est' => 'A'], 'Prc_Nom ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/camaronera/productores/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Prc_Nom', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Prc_Nom' => $body['Prc_Nom'],
            'Prc_Dir' => $body['Prc_Dir'] ?? '',
            'Prc_Tel' => $body['Prc_Tel'] ?? '',
            'Prc_Ced' => $body['Prc_Ced'] ?? '',
            'Prc_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $prcCod = $api->insert('productor_camaronera', $data);
        ApiResponse::created(['Prc_Cod' => $prcCod], 'Productor creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/camaronera/productores/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Prc_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Prc_Nom'])) $data['Prc_Nom'] = $body['Prc_Nom'];
        if (isset($body['Prc_Dir'])) $data['Prc_Dir'] = $body['Prc_Dir'];
        if (isset($body['Prc_Tel'])) $data['Prc_Tel'] = $body['Prc_Tel'];
        if (isset($body['Prc_Ced'])) $data['Prc_Ced'] = $body['Prc_Ced'];
        if (isset($body['Prc_Est'])) $data['Prc_Est'] = $body['Prc_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('productor_camaronera', $data, 'Prc_Cod', $body['Prc_Cod']);
        ApiResponse::success(['Prc_Cod' => $body['Prc_Cod']], 'Productor modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/camaronera/productores/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Prc_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->softDelete('productor_camaronera', 'Prc_Cod', $body['Prc_Cod'], 'Prc_Est');
        ApiResponse::success(null, 'Productor eliminado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// NEGOCIACIONES
// ============================================================
$app->post('/v1/camaronera/negociaciones', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT n.*, pr.Prc_Nom AS productor_nombre
                FROM negociacion n
                INNER JOIN productor_camaronera pr ON n.Prc_Cod = pr.Prc_Cod
                WHERE pr.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY n.Neg_Fec DESC
                LIMIT 500";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/camaronera/negociaciones/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Prc_Cod', 'Neg_Fec', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Prc_Cod' => $body['Prc_Cod'],
            'Neg_Fec' => $body['Neg_Fec'],
            'Neg_Des' => $body['Neg_Des'] ?? '',
            'Neg_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $negCod = $api->insert('negociacion', $data);
        ApiResponse::created(['Neg_Cod' => $negCod], 'Negociación creada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/camaronera/negociaciones/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Neg_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Neg_Des'])) $data['Neg_Des'] = $body['Neg_Des'];
        if (isset($body['Neg_Est'])) $data['Neg_Est'] = $body['Neg_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('negociacion', $data, 'Neg_Cod', $body['Neg_Cod']);
        ApiResponse::success(['Neg_Cod' => $body['Neg_Cod']], 'Negociación modificada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// LIQUIDACIONES CAMARONERA
// ============================================================
$app->post('/v1/camaronera/liquidaciones', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT l.*, pr.Prc_Nom AS productor_nombre
                FROM liquidacion_camaronera l
                INNER JOIN productor_camaronera pr ON l.Prc_Cod = pr.Prc_Cod
                WHERE pr.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY l.Liq_Fec DESC
                LIMIT 500";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/camaronera/liquidaciones/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Prc_Cod', 'Liq_Fec', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Prc_Cod' => $body['Prc_Cod'],
            'Liq_Fec' => $body['Liq_Fec'],
            'Liq_Kil' => $body['Liq_Kil'] ?? 0,
            'Liq_Pre' => $body['Liq_Pre'] ?? 0,
            'Liq_Tot' => $body['Liq_Tot'] ?? 0,
            'Liq_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $liqCod = $api->insert('liquidacion_camaronera', $data);
        ApiResponse::created(['Liq_Cod' => $liqCod], 'Liquidación creada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

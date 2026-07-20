<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ApiResponse.php';

// ============================================================
// PRODUCTORES
// ============================================================
$app->post('/v1/bananero/productores', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('productor', ['Emp_Cod' => $body['Emp_Cod'], 'Pro_Est' => 'A'], 'Pro_Nom ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/bananero/productores/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pro_Nom', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Pro_Nom' => $body['Pro_Nom'],
            'Pro_Dir' => $body['Pro_Dir'] ?? '',
            'Pro_Tel' => $body['Pro_Tel'] ?? '',
            'Pro_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $proCod = $api->insert('productor', $data);
        ApiResponse::created(['Pro_Cod' => $proCod], 'Productor creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/bananero/productores/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pro_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Pro_Nom'])) $data['Pro_Nom'] = $body['Pro_Nom'];
        if (isset($body['Pro_Dir'])) $data['Pro_Dir'] = $body['Pro_Dir'];
        if (isset($body['Pro_Tel'])) $data['Pro_Tel'] = $body['Pro_Tel'];
        if (isset($body['Pro_Est'])) $data['Pro_Est'] = $body['Pro_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('productor', $data, 'Pro_Cod', $body['Pro_Cod']);
        ApiResponse::success(['Pro_Cod' => $body['Pro_Cod']], 'Productor modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/bananero/productores/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pro_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->softDelete('productor', 'Pro_Cod', $body['Pro_Cod'], 'Pro_Est');
        ApiResponse::success(null, 'Productor eliminado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// LIQUIDACIONES
// ============================================================
$app->post('/v1/bananero/liquidaciones', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT l.*, pr.Pro_Nom AS productor_nombre
                FROM liquidacion_banano l
                INNER JOIN productor pr ON l.Pro_Cod = pr.Pro_Cod
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

$app->post('/v1/bananero/liquidaciones/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pro_Cod', 'Liq_Fec', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        $liquidacion = [
            'Pro_Cod' => $body['Pro_Cod'],
            'Liq_Fec' => $body['Liq_Fec'],
            'Liq_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $liqCod = $api->insert('liquidacion_banano', $liquidacion);
        if ($liqCod === false) {
            $api->rollback();
            ApiResponse::serverError('Error al crear liquidación: ' . $api->getErrorMsg());
            return;
        }
        if (!empty($body['detalles']) && is_array($body['detalles'])) {
            foreach ($body['detalles'] as $det) {
                $detalle = [
                    'Liq_Cod' => $liqCod,
                    'Pro_Cod' => $body['Pro_Cod'],
                    'Lid_Can' => $det['Lid_Can'] ?? 0,
                    'Lid_Pes' => $det['Lid_Pes'] ?? 0,
                    'Lid_Pre' => $det['Lid_Pre'] ?? 0,
                    'Lid_Tot' => $det['Lid_Tot'] ?? 0
                ];
                $api->insert('liquidacion_bana_det', $detalle);
            }
        }
        $api->commit();
        ApiResponse::created(['Liq_Cod' => $liqCod], 'Liquidación creada exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// EXPORTACIONES
// ============================================================
$app->post('/v1/bananero/exportaciones', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT e.*, n.Nav_Nom, pu.Pue_Nom
                FROM exportacion e
                LEFT JOIN naviera n ON e.Nav_Cod = n.Nav_Cod
                LEFT JOIN puerto pu ON e.Pue_Cod = pu.Pue_Cod
                WHERE e.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY e.Exp_Fec DESC
                LIMIT 200";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// LABORES
// ============================================================
$app->post('/v1/bananero/labores', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('labores', ['Emp_Cod' => $body['Emp_Cod']], 'Lab_Fec DESC', 500);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/bananero/labores/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Lab_Fec', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Lab_Fec' => $body['Lab_Fec'],
            'Lab_Des' => $body['Lab_Des'] ?? '',
            'Lab_Can' => $body['Lab_Can'] ?? 0,
            'Lab_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $labCod = $api->insert('labores', $data);
        ApiResponse::created(['Lab_Cod' => $labCod], 'Labor creada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/bananero/labores/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Lab_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Lab_Des'])) $data['Lab_Des'] = $body['Lab_Des'];
        if (isset($body['Lab_Can'])) $data['Lab_Can'] = $body['Lab_Can'];
        if (isset($body['Lab_Est'])) $data['Lab_Est'] = $body['Lab_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('labores', $data, 'Lab_Cod', $body['Lab_Cod']);
        ApiResponse::success(['Lab_Cod' => $body['Lab_Cod']], 'Labor modificada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// VIAJES EXPORTACION
// ============================================================
$app->post('/v1/bananero/viajes-exportacion', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('viaje_exportacion', ['Emp_Cod' => $body['Emp_Cod']], 'Via_Fec DESC', 200);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// MARCAS BANANO
// ============================================================
$app->post('/v1/bananero/marcas', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('marca_banano', [], 'Mar_Nom ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// NAVIERAS
// ============================================================
$app->post('/v1/bananero/navieras', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('naviera', ['Nav_Est' => 'A'], 'Nav_Nom ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

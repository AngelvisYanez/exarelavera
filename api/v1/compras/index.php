<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ApiResponse.php';

// ============================================================
// REQUISICIONES
// ============================================================
$app->post('/v1/compras/requisiciones', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT r.*, p.Prs_Nom, p.Prs_Ape, d.Dep_Des
                FROM requisicion r
                LEFT JOIN personal pe ON r.Per_Cod = pe.Per_Cod
                LEFT JOIN persona p ON pe.Prs_Cod = p.Prs_Cod
                LEFT JOIN departamento d ON pe.Dep_Cod = d.Dep_Cod
                WHERE r.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY r.Req_Fec DESC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/compras/requisiciones/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Req_Fec', 'Per_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        $requisicion = [
            'Req_Fec' => $body['Req_Fec'],
            'Req_Est' => 'A',
            'Per_Cod' => $body['Per_Cod'],
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $reqCod = $api->insert('requisicion', $requisicion);
        if ($reqCod === false) {
            $api->rollback();
            ApiResponse::serverError('Error al crear requisición: ' . $api->getErrorMsg());
            return;
        }
        if (!empty($body['detalles']) && is_array($body['detalles'])) {
            foreach ($body['detalles'] as $det) {
                $detalle = [
                    'Req_Cod' => $reqCod,
                    'Pro_Cod' => $det['Pro_Cod'] ?? null,
                    'Req_Can' => $det['Req_Can'] ?? 0,
                    'Req_Pre' => $det['Req_Pre'] ?? 0,
                    'Req_Des' => $det['Req_Des'] ?? ''
                ];
                $api->insert('requisicion_det', $detalle);
            }
        }
        $api->commit();
        ApiResponse::created(['Req_Cod' => $reqCod], 'Requisición creada exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/compras/requisiciones/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Req_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Req_Est'])) $data['Req_Est'] = $body['Req_Est'];
        if (isset($body['Per_Cod'])) $data['Per_Cod'] = $body['Per_Cod'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('requisicion', $data, 'Req_Cod', $body['Req_Cod']);
        ApiResponse::success(['Req_Cod' => $body['Req_Cod']], 'Requisición modificada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/compras/requisiciones/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Req_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        $api->delete('requisicion_det', 'Req_Cod', $body['Req_Cod']);
        $api->delete('requisicion', 'Req_Cod', $body['Req_Cod']);
        $api->commit();
        ApiResponse::success(null, 'Requisición eliminada exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// REQUISITORES
// ============================================================
$app->post('/v1/compras/requisitores', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('requisitor', ['Req_Est' => 'A'], 'Req_Nom ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/compras/requisitores/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Req_Nom'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Req_Nom' => $body['Req_Nom'],
            'Req_Est' => 'A'
        ];
        $api->insert('requisitor', $data);
        ApiResponse::created($data, 'Requisitor creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

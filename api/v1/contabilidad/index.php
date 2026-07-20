<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ApiResponse.php';

// ============================================================
// PLAN DE CUENTAS
// ============================================================
$app->post('/v1/contabilidad/plan-cuentas', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod'], 'Pla_Est' => 'A'];
        if (isset($body['Pla_Niv'])) $where['Pla_Niv'] = $body['Pla_Niv'];
        $perPage = $body['perPage'] ?? 50;
        $page = $body['page'] ?? 1;
        $result = $api->listPaged('plan_cuenta', $where, 'Pla_Cod ASC', $page, $perPage);
        utf8_encode_deep($result['data']);
        ApiResponse::paginated($result['data'], $result['total'], $result['page'], $result['perPage'], $result['pages']);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/contabilidad/plan-cuentas/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pla_Cod', 'Pla_Des', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        if ($api->exists('plan_cuenta', ['Pla_Cod' => $body['Pla_Cod'], 'Emp_Cod' => $body['Emp_Cod']])) {
            ApiResponse::badRequest('Ya existe una cuenta con ese código');
            return;
        }
        $data = [
            'Pla_Cod' => $body['Pla_Cod'],
            'Pla_Des' => $body['Pla_Des'],
            'Pla_Niv' => $body['Pla_Niv'] ?? 1,
            'Pla_Tip' => $body['Pla_Tip'] ?? 'A',
            'Pla_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $api->insert('plan_cuenta', $data);
        ApiResponse::created($data, 'Cuenta creada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/contabilidad/plan-cuentas/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pla_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Pla_Des'])) $data['Pla_Des'] = $body['Pla_Des'];
        if (isset($body['Pla_Niv'])) $data['Pla_Niv'] = $body['Pla_Niv'];
        if (isset($body['Pla_Tip'])) $data['Pla_Tip'] = $body['Pla_Tip'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('plan_cuenta', $data, 'Pla_Cod', $body['Pla_Cod']);
        ApiResponse::success(['Pla_Cod' => $body['Pla_Cod']], 'Cuenta modificada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/contabilidad/plan-cuentas/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pla_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->softDelete('plan_cuenta', 'Pla_Cod', $body['Pla_Cod'], 'Pla_Est');
        ApiResponse::success(null, 'Cuenta eliminada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// PERIODOS CONTABLES
// ============================================================
$app->post('/v1/contabilidad/periodos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT p.*, pc.Pla_Des FROM perio_cont p INNER JOIN plan_cuenta pc ON p.Pla_Cod = pc.Pla_Cod WHERE p.Emp_Cod = " . $api->escape($body['Emp_Cod']) . " ORDER BY p.Pec_Fei DESC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/contabilidad/periodos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pec_Cod', 'Pec_Fei', 'Pec_Fef', 'Pla_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Pec_Cod' => $body['Pec_Cod'],
            'Pec_Fei' => $body['Pec_Fei'],
            'Pec_Fef' => $body['Pec_Fef'],
            'Pec_Fec' => $body['Pec_Fec'] ?? date('Y-m-d'),
            'Pec_Est' => $body['Pec_Est'] ?? 'A',
            'Pla_Cod' => $body['Pla_Cod'],
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $api->insert('perio_cont', $data);
        ApiResponse::created($data, 'Periodo creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/contabilidad/periodos/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pec_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Pec_Fei'])) $data['Pec_Fei'] = $body['Pec_Fei'];
        if (isset($body['Pec_Fef'])) $data['Pec_Fef'] = $body['Pec_Fef'];
        if (isset($body['Pec_Est'])) $data['Pec_Est'] = $body['Pec_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('perio_cont', $data, 'Pec_Cod', $body['Pec_Cod']);
        ApiResponse::success(['Pec_Cod' => $body['Pec_Cod']], 'Periodo modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/contabilidad/periodos/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pec_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->update('perio_cont', ['Pec_Est' => 'I'], 'Pec_Cod', $body['Pec_Cod']);
        ApiResponse::success(null, 'Periodo eliminado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// COMPROBANTES
// ============================================================
$app->post('/v1/contabilidad/comprobantes', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod']];
        if (isset($body['Pec_Cod'])) $where['Pec_Cod'] = $body['Pec_Cod'];
        if (isset($body['Tia_Cod'])) $where['Tia_Cod'] = $body['Tia_Cod'];
        $perPage = $body['perPage'] ?? 50;
        $page = $body['page'] ?? 1;
        $result = $api->listPaged('comprobantes', $where, 'Com_Fec DESC, Com_Num DESC', $page, $perPage);
        utf8_encode_deep($result['data']);
        ApiResponse::paginated($result['data'], $result['total'], $result['page'], $result['perPage'], $result['pages']);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/contabilidad/comprobantes/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Com_Num', 'Com_Fec', 'Tia_Cod', 'Pec_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        $cabecera = [
            'Com_Num' => $body['Com_Num'],
            'Com_Fec' => $body['Com_Fec'],
            'Com_Des' => $body['Com_Des'] ?? '',
            'Tia_Cod' => $body['Tia_Cod'],
            'Pec_Cod' => $body['Pec_Cod'],
            'Com_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $comCod = $api->insert('comprobantes', $cabecera);
        if ($comCod === false) {
            $api->rollback();
            ApiResponse::serverError('Error al crear comprobante: ' . $api->getErrorMsg());
            return;
        }
        if (!empty($body['detalles']) && is_array($body['detalles'])) {
            foreach ($body['detalles'] as $det) {
                $detalle = [
                    'Com_Cod' => $comCod,
                    'Pla_Cod' => $det['Pla_Cod'],
                    'Det_Mov' => $det['Det_Mov'],
                    'Det_Val' => $det['Det_Val'],
                    'Det_Des' => $det['Det_Des'] ?? ''
                ];
                $api->insert('comprobantes_det', $detalle);
            }
        }
        $api->commit();
        ApiResponse::created(['Com_Cod' => $comCod], 'Comprobante creado exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/contabilidad/comprobantes/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Com_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        $api->delete('comprobantes_det', 'Com_Cod', $body['Com_Cod']);
        $api->delete('comprobantes', 'Com_Cod', $body['Com_Cod']);
        $api->commit();
        ApiResponse::success(null, 'Comprobante eliminado exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// DETALLES COMPROBANTE
// ============================================================
$app->post('/v1/contabilidad/detalles-comprobante', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Com_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('comprobantes_det', ['Com_Cod' => $body['Com_Cod']], 'Det_Cod ASC', 500);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// TIPOS COMPROBANTE
// ============================================================
$app->post('/v1/contabilidad/tipos-comprobante', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('tipo_compro', ['Tia_Est' => 'A'], 'Tia_Des ASC', 100);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/contabilidad/tipos-comprobante/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Tia_Cod', 'Tia_Des'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Tia_Cod' => $body['Tia_Cod'],
            'Tia_Des' => $body['Tia_Des'],
            'Tia_Est' => 'A'
        ];
        $api->insert('tipo_compro', $data);
        ApiResponse::created($data, 'Tipo de comprobante creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/contabilidad/tipos-comprobante/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Tia_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Tia_Des'])) $data['Tia_Des'] = $body['Tia_Des'];
        if (isset($body['Tia_Est'])) $data['Tia_Est'] = $body['Tia_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('tipo_compro', $data, 'Tia_Cod', $body['Tia_Cod']);
        ApiResponse::success(['Tia_Cod' => $body['Tia_Cod']], 'Tipo de comprobante modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// BALANCE COMPROBACION
// ============================================================
$app->post('/v1/contabilidad/balance-comprobacion', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pec_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT pl.Pla_Cod, pl.Pla_Des, pl.Pla_Niv,
                COALESCE(SUM(CASE WHEN cd.Det_Mov = 'D' THEN cd.Det_Val ELSE 0 END), 0) AS total_debe,
                COALESCE(SUM(CASE WHEN cd.Det_Mov = 'H' THEN cd.Det_Val ELSE 0 END), 0) AS total_haber
                FROM plan_cuenta pl
                LEFT JOIN comprobantes_det cd ON pl.Pla_Cod = cd.Pla_Cod
                LEFT JOIN comprobantes c ON cd.Com_Cod = c.Com_Cod AND c.Pec_Cod = " . $api->escape($body['Pec_Cod']) . "
                WHERE pl.Emp_Cod = " . $api->escape($body['Emp_Cod']) . " AND pl.Pla_Est = 'A'
                GROUP BY pl.Pla_Cod, pl.Pla_Des, pl.Pla_Niv
                ORDER BY pl.Pla_Cod ASC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// MAYOR CUENTA
// ============================================================
$app->post('/v1/contabilidad/mayor-cuenta', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Pla_Cod', 'Pec_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT c.Com_Fec, c.Com_Num, tc.Tia_Des, cd.Det_Mov, cd.Det_Val, cd.Det_Des
                FROM comprobantes_det cd
                INNER JOIN comprobantes c ON cd.Com_Cod = c.Com_Cod
                INNER JOIN tipo_compro tc ON c.Tia_Cod = tc.Tia_Cod
                WHERE cd.Pla_Cod = " . $api->escape($body['Pla_Cod']) . " AND c.Pec_Cod = " . $api->escape($body['Pec_Cod']) . "
                ORDER BY c.Com_Fec ASC, c.Com_Num ASC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

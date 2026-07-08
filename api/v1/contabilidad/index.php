<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/contabilidad/plan-cuentas', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod'], 'Pla_Est' => 'A'];
        $data = $api->list('plan_cuenta', $where, 'Pla_Cod ASC', 5000);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/contabilidad/periodos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->query("SELECT p.*, pc.Pla_Des FROM perio_cont p INNER JOIN plan_cuenta pc ON p.Pla_Cod = pc.Pla_Cod WHERE p.Emp_Cod = " . $api->escape($body['Emp_Cod']) . " ORDER BY p.Pec_Fei DESC");
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/contabilidad/comprobantes', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod']];
        if (isset($body['Pec_Cod'])) $where['Pec_Cod'] = $body['Pec_Cod'];
        if (isset($body['Tia_Cod'])) $where['Tia_Cod'] = $body['Tia_Cod'];
        $data = $api->list('comprobantes', $where, 'Com_Fec DESC, Com_Num DESC', 2000);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/contabilidad/detalles-comprobante', function () use ($app) {
    $body = getBody();
    if (!isset($body['Com_Cod'])) {
        $app->response->setStatus(400); echo json_encode(['success' => false, 'error' => 'Com_Cod requerido']); return;
    }
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('comprobantes_det', ['Com_Cod' => $body['Com_Cod']], 'Det_Cod ASC', 500);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/contabilidad/tipos-comprobante', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('tipo_compro', ['Tia_Est' => 'A'], 'Tia_Des ASC', 100);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/contabilidad/balance-comprobacion', function () use ($app) {
    $body = getBody();
    if (!isset($body['Pec_Cod'])) {
        $app->response->setStatus(400); echo json_encode(['success' => false, 'error' => 'Pec_Cod requerido']); return;
    }
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
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/contabilidad/mayor-cuenta', function () use ($app) {
    $body = getBody();
    if (!isset($body['Pla_Cod']) || !isset($body['Pec_Cod'])) {
        $app->response->setStatus(400); echo json_encode(['success' => false, 'error' => 'Pla_Cod y Pec_Cod requeridos']); return;
    }
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
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

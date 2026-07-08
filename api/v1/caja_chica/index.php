<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/caja-chica/cajas', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod'], 'Cch_Est' => 'A'];
        $data = $api->list('caja_chica', $where, 'Cch_Des ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/caja-chica/movimientos', function () use ($app) {
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
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

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
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

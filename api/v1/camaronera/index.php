<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/camaronera/productores', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('productor_camaronera', ['Emp_Cod' => $body['Emp_Cod'], 'Prc_Est' => 'A'], 'Prc_Nom ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

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
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

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
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

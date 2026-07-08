<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/bananero/productores', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('productor', ['Emp_Cod' => $body['Emp_Cod'], 'Pro_Est' => 'A'], 'Pro_Nom ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

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
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

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
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/bananero/labores', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('labores', ['Emp_Cod' => $body['Emp_Cod']], 'Lab_Fec DESC', 500);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/bananero/viajes-exportacion', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('viaje_exportacion', ['Emp_Cod' => $body['Emp_Cod']], 'Via_Fec DESC', 200);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/bananero/marcas', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('marca_banano', [], 'Mar_Nom ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/bananero/navieras', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('naviera', ['Nav_Est' => 'A'], 'Nav_Nom ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

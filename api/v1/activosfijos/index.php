<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/activosfijos/activos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT a.*, ta.Tac_Des, c.Prs_Nom AS custodio_nombre, c.Prs_Ape AS custodio_apellido
                FROM activo a
                LEFT JOIN tipo_activo ta ON a.Tac_Cod = ta.Tac_Cod
                LEFT JOIN custodio cu ON a.Cus_Cod = cu.Cus_Cod
                LEFT JOIN persona c ON cu.Prs_Cod = c.Prs_Cod
                WHERE a.Emp_Cod = " . $api->escape($body['Emp_Cod']) . " AND a.Act_Est = 'A'
                ORDER BY a.Act_Cod DESC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/activosfijos/tipos-activo', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('tipo_activo', [], 'Tac_Des ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/activosfijos/depreciaciones', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('depreciacion', [], 'Dep_Fec DESC', 500);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/activosfijos/custodios', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT cu.*, p.Prs_Nom, p.Prs_Ape, p.Prs_Ced
                FROM custodio cu
                INNER JOIN persona p ON cu.Prs_Cod = p.Prs_Cod
                WHERE cu.Cus_Est = 'A' ORDER BY p.Prs_Ape ASC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/activosfijos/mantenimientos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT m.*, a.Act_Des, tm.Tma_Des
                FROM mantenimiento m
                INNER JOIN activo a ON m.Act_Cod = a.Act_Cod
                LEFT JOIN tipo_mantenimiento tm ON m.Tma_Cod = tm.Tma_Cod
                WHERE a.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY m.Man_Fec DESC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

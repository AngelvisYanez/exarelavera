<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/transportecarga/viajes', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT v.*, cli.Prs_Nom AS cliente_nombre, cli.Prs_Ape AS cliente_apellido, ve.Veh_Placa, ve.Veh_Des
                FROM viaje v
                LEFT JOIN cliente c ON v.Cli_Cod = c.Cli_Cod
                LEFT JOIN persona cli ON c.Prs_Cod = cli.Prs_Cod
                LEFT JOIN vehiculo ve ON v.Veh_Cod = ve.Veh_Cod
                WHERE v.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY v.Via_Fec DESC, v.Via_Cod DESC
                LIMIT 500";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/transportecarga/vehiculos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('vehiculo', ['Veh_Est' => 'A'], 'Veh_Placa ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/transportecarga/tickets', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT t.*, v.Via_Cod
                FROM ticket t
                INNER JOIN viaje v ON t.Via_Cod = v.Via_Cod
                WHERE v.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY t.Tic_Fec DESC
                LIMIT 500";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/transportecarga/facturas-viaje', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT fv.*, v.Via_Cod, v.Via_Fec
                FROM factura_viaje fv
                INNER JOIN viaje v ON fv.Via_Cod = v.Via_Cod
                WHERE v.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY fv.Fac_Fec DESC
                LIMIT 500";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/transportecarga/clientes-vehiculo', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('cliente_vehiculo', ['Cv_Est' => 'A'], 'Cv_Nom ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

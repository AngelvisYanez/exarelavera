<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/bodega/bodegas', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod'], 'Bod_Est' => 'A'];
        $data = $api->list('bodega', $where, 'Bod_Des ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/bodega/kardex', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT k.*, p.Pro_Des, p.Pro_Cod
                FROM kardex_ie k
                INNER JOIN producto p ON k.Pro_Cod = p.Pro_Cod
                WHERE p.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY k.Kar_Fec DESC, k.Kar_Hor DESC
                LIMIT 500";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/bodega/stock', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT s.*, p.Pro_Des, p.Pro_Cod, b.Bod_Des
                FROM stock s
                INNER JOIN producto p ON s.Pro_Cod = p.Pro_Cod
                LEFT JOIN bodega b ON s.Bod_Cod = b.Bod_Cod
                WHERE p.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY p.Pro_Des ASC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/bodega/movimientos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT m.*, p.Pro_Des, b.Bod_Des, tm.Tmo_Des
                FROM movimiento_bodega m
                INNER JOIN producto p ON m.Pro_Cod = p.Pro_Cod
                LEFT JOIN bodega b ON m.Bod_Cod = b.Bod_Cod
                LEFT JOIN tipo_movimiento tm ON m.Tmo_Cod = tm.Tmo_Cod
                WHERE p.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY m.Mov_Fec DESC
                LIMIT 500";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

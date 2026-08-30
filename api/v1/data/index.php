<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/data/query', function () use ($app) {
    $body = getBody();
    if (!isset($body['Bdd']) || !isset($body['sql'])) {
        $app->response->setStatus(400);
        echo json_encode(['success' => false, 'error' => 'Bdd y sql son requeridos']);
        return;
    }
    $sql = trim($body['sql']);
    if (preg_match('/^\s*(DROP|ALTER|TRUNCATE|CREATE|INSERT|UPDATE|DELETE|GRANT|REVOKE|SET|SHOW|DESCRIBE|EXPLAIN|LOAD|CALL|EXEC|EXECUTE)\s/i', $sql) ||
        preg_match('/(;\s*(DROP|ALTER|TRUNCATE|CREATE|INSERT|UPDATE|DELETE|GRANT|REVOKE|SET|SHOW))\s/i', $sql) ||
        preg_match('/--\s*$|\/\*|\*\/|SLEEP\s*\(|BENCHMARK\s*\(/i', $sql)) {
        $app->response->setStatus(403);
        echo json_encode(['success' => false, 'error' => 'Operacion no permitida']);
        return;
    }
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/data/tables', function () use ($app) {
    $body = getBody();
    if (!isset($body['Bdd'])) {
        $app->response->setStatus(400);
        echo json_encode(['success' => false, 'error' => 'Bdd es requerido']);
        return;
    }
    try {
        $api = new DataAPI($body['Bdd']);
        $pattern = isset($body['pattern']) ? $body['pattern'] : null;
        $tables = $api->listTables($pattern);
        echo json_encode(['success' => true, 'tables' => $tables]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/data/list', function () use ($app) {
    $body = getBody();
    if (!isset($body['Bdd']) || !isset($body['table'])) {
        $app->response->setStatus(400);
        echo json_encode(['success' => false, 'error' => 'Bdd y table son requeridos']);
        return;
    }
    try {
        $api = new DataAPI($body['Bdd']);
        $where = isset($body['where']) ? $body['where'] : [];
        $order = isset($body['order']) ? $body['order'] : '';
        $limit = isset($body['limit']) ? (int)$body['limit'] : 1000;
        $offset = isset($body['offset']) ? (int)$body['offset'] : 0;
        $data = $api->list($body['table'], $where, $order, $limit, $offset);
        $total = $api->count($body['table'], $where);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data, 'total' => $total]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/data/get', function () use ($app) {
    $body = getBody();
    if (!isset($body['Bdd']) || !isset($body['table']) || !isset($body['id_field']) || !isset($body['id_value'])) {
        $app->response->setStatus(400);
        echo json_encode(['success' => false, 'error' => 'Bdd, table, id_field, id_value requeridos']);
        return;
    }
    try {
        $api = new DataAPI($body['Bdd']);
        $row = $api->getById($body['table'], $body['id_field'], $body['id_value']);
        if ($row) {
            utf8_encode_deep($row);
            echo json_encode(['success' => true, 'data' => $row]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Registro no encontrado']);
        }
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/data/describe', function () use ($app) {
    $body = getBody();
    if (!isset($body['Bdd']) || !isset($body['table'])) {
        $app->response->setStatus(400);
        echo json_encode(['success' => false, 'error' => 'Bdd y table requeridos']);
        return;
    }
    try {
        $api = new DataAPI($body['Bdd']);
        $info = $api->tableInfo($body['table']);
        utf8_encode_deep($info);
        echo json_encode(['success' => true, 'columns' => $info]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

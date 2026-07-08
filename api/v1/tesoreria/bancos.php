<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/tesoreria/bancos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('banco', ['Ban_Est' => 'A'], 'Ban_Nom ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/tesoreria/cuentas-banco', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT cb.*, b.Ban_Nom
                FROM cuenta_banco cb
                INNER JOIN banco b ON cb.Ban_Cod = b.Ban_Cod
                WHERE cb.Emp_Cod = " . $api->escape($body['Emp_Cod']) . " AND cb.Cue_Est = 'A'
                ORDER BY b.Ban_Nom ASC, cb.Cue_Num ASC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/tesoreria/cheques', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT ch.*, cb.Cue_Num, b.Ban_Nom
                FROM cheques ch
                INNER JOIN cuenta_banco cb ON ch.Cue_Cod = cb.Cue_Cod
                INNER JOIN banco b ON cb.Ban_Cod = b.Ban_Cod
                WHERE cb.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY ch.Che_Fec DESC
                LIMIT 500";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/tesoreria/conciliacion', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT con.*, cb.Cue_Num, b.Ban_Nom
                FROM conciliacion con
                INNER JOIN cuenta_banco cb ON con.Cue_Cod = cb.Cue_Cod
                INNER JOIN banco b ON cb.Ban_Cod = b.Ban_Cod
                WHERE cb.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY con.Con_Fec DESC
                LIMIT 200";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/tesoreria/cccp', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod']];
        if (isset($body['Cpc_Tip'])) $where['Cpc_Tip'] = $body['Cpc_Tip'];
        if (isset($body['Cli_Cod'])) $where['Cli_Cod'] = $body['Cli_Cod'];
        $data = $api->list('cccp', $where, 'Cpc_Fec DESC', 500);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

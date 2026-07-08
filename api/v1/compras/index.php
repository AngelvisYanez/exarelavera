<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/compras/requisiciones', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Requisicion.Emp_Cod' => $body['Emp_Cod']];
        $sql = "SELECT r.*, p.Prs_Nom, p.Prs_Ape, d.Dep_Des
                FROM requisicion r
                LEFT JOIN personal pe ON r.Per_Cod = pe.Per_Cod
                LEFT JOIN persona p ON pe.Prs_Cod = p.Prs_Cod
                LEFT JOIN departamento d ON pe.Dep_Cod = d.Dep_Cod
                WHERE r.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY r.Req_Fec DESC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/compras/requisitores', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('requisitor', ['Req_Est' => 'A'], 'Req_Nom ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

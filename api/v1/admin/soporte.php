<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/admin/usuarios', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT u.*, p.Prs_Nom, p.Prs_Ape, p.Prs_Ced, per.Per_Des AS perfil_nombre, s.Suc_Des
                FROM usuarios u
                INNER JOIN persona p ON u.Prs_Cod = p.Prs_Cod
                LEFT JOIN perfil per ON u.Per_Cod = per.Per_Cod
                LEFT JOIN sucursal s ON u.Suc_Cod = s.Suc_Cod
                WHERE u.Usu_Est = 'A'
                ORDER BY p.Prs_Ape ASC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/perfiles', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('perfil', ['Per_Est' => 'A'], 'Per_Des ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/tickets', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod']];
        if (isset($body['Tic_Est'])) $where['Tic_Est'] = $body['Tic_Est'];
        $data = $api->list('soporte_ticket', $where, 'Tic_Fec DESC', 500);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/configuracion', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('config_sistema', ['Emp_Cod' => $body['Emp_Cod']], 'Con_Key ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/sucursales', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('sucursal', ['Emp_Cod' => $body['Emp_Cod'], 'Suc_Est' => 'A'], 'Suc_Des ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/log-actividad', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT l.*, u.Usu_Ced, p.Prs_Nom, p.Prs_Ape
                FROM log_actividad l
                LEFT JOIN usuarios u ON l.Usu_Cod = u.Usu_Cod
                LEFT JOIN persona p ON u.Prs_Cod = p.Prs_Cod
                ORDER BY l.Log_Fec DESC, l.Log_Hor DESC
                LIMIT 200";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

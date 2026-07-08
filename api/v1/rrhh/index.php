<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

$app->post('/v1/rrhh/personal', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT p.*, pe.Prs_Nom, pe.Prs_Ape, pe.Prs_Ced, pe.Prs_Dir, pe.Prs_Tel, pe.Prs_Cel, pe.Prs_Cor,
                       ci.Ciu_Des, ca.Car_Des, d.Dep_Des
                FROM personal p
                INNER JOIN persona pe ON p.Prs_Cod = pe.Prs_Cod
                LEFT JOIN ciudad ci ON pe.Ciu_Cod = ci.Ciu_Cod
                LEFT JOIN cargo ca ON p.Car_Cod = ca.Car_Cod
                LEFT JOIN departamento d ON p.Dep_Cod = d.Dep_Cod
                WHERE p.Emp_Cod = " . $api->escape($body['Emp_Cod']) . " AND p.Per_Est = 'A'
                ORDER BY pe.Prs_Ape ASC, pe.Prs_Nom ASC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/rrhh/contratos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT c.*, pe.Prs_Nom, pe.Prs_Ape, pe.Prs_Ced, tc.Tco_Des
                FROM contrato c
                INNER JOIN personal p ON c.Per_Cod = p.Per_Cod
                INNER JOIN persona pe ON p.Prs_Cod = pe.Prs_Cod
                LEFT JOIN tipo_contrato tc ON c.Tco_Cod = tc.Tco_Cod
                WHERE p.Emp_Cod = " . $api->escape($body['Emp_Cod']) . "
                ORDER BY c.Con_Fec_Ing DESC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/rrhh/roles-pago', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod']];
        if (isset($body['Rol_Ano'])) $where['Rol_Ano'] = $body['Rol_Ano'];
        if (isset($body['Rol_Mes'])) $where['Rol_Mes'] = $body['Rol_Mes'];
        $data = $api->list('rol_pago', $where, 'Rol_Ano DESC, Rol_Mes DESC', 500);
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/rrhh/departamentos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('departamento', ['Emp_Cod' => $body['Emp_Cod']], 'Dep_Des ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/rrhh/cargos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('cargo', [], 'Car_Des ASC');
        utf8_encode_deep($data);
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500); echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

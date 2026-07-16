<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';

// ── LISTAR DIRECTORIOS / MÓDULOS ─────────────────────────────────────────────
$app->post('/v1/admin/directorio/obtener', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('directorio_modulos', [
            'Emp_Cod' => $body['Emp_Cod'],
            'Dir_Est' => 'A',
        ], 'Dir_Cod ASC', 500);
        utf8_encode_deep($data);
        echo json_encode(['status' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── CREAR DIRECTORIO / MÓDULO ────────────────────────────────────────────────
$app->post('/v1/admin/directorio/crear', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Dir_Nom' => $body['Dir_Nom'] ?? '',
            'Dir_Rut' => $body['Dir_Rut'] ?? '',
            'Dir_Tip' => $body['Dir_Tip'] ?? 'modulo',
            'Dir_Est' => 'A',
            'Dir_Des' => $body['Dir_Des'] ?? '',
            'Dir_Ver' => $body['Dir_Ver'] ?? '',
            'Dir_Aut' => $body['Dir_Aut'] ?? 'N',
            'Emp_Cod' => $body['Emp_Cod'] ?? 1,
        ];
        $id = $api->insert('directorio_modulos', $data);
        echo json_encode(['status' => true, 'message' => 'Módulo registrado exitosamente', 'data' => ['Dir_Cod' => $id]]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── MODIFICAR DIRECTORIO / MÓDULO ────────────────────────────────────────────
$app->post('/v1/admin/directorio/modificar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $dirCod = $body['Dir_Cod'] ?? null;
        if (!$dirCod) {
            echo json_encode(['status' => false, 'error' => 'Dir_Cod es requerido']);
            return;
        }
        $data = [];
        if (isset($body['Dir_Nom'])) $data['Dir_Nom'] = $body['Dir_Nom'];
        if (isset($body['Dir_Rut'])) $data['Dir_Rut'] = $body['Dir_Rut'];
        if (isset($body['Dir_Tip'])) $data['Dir_Tip'] = $body['Dir_Tip'];
        if (isset($body['Dir_Des'])) $data['Dir_Des'] = $body['Dir_Des'];
        if (isset($body['Dir_Ver'])) $data['Dir_Ver'] = $body['Dir_Ver'];
        if (isset($body['Dir_Aut'])) $data['Dir_Aut'] = $body['Dir_Aut'];
        if (!empty($data)) {
            $api->update('directorio_modulos', $data, 'Dir_Cod', $dirCod);
        }
        echo json_encode(['status' => true, 'message' => 'Módulo modificado exitosamente']);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── ELIMINAR DIRECTORIO / MÓDULO ─────────────────────────────────────────────
$app->post('/v1/admin/directorio/eliminar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $dirCod = $body['Dir_Cod'] ?? null;
        if (!$dirCod) {
            echo json_encode(['status' => false, 'error' => 'Dir_Cod es requerido']);
            return;
        }
        $api->update('directorio_modulos', ['Dir_Est' => 'I'], 'Dir_Cod', $dirCod);
        echo json_encode(['status' => true, 'message' => 'Módulo eliminado exitosamente']);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── LISTAR PROCESOS ──────────────────────────────────────────────────────────
$app->post('/v1/admin/procesos/obtener', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('procesos', [
            'Pcs_Est' => 'A',
        ], 'Pcs_Cod ASC', 500);
        utf8_encode_deep($data);
        echo json_encode(['status' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── CREAR PROCESO ────────────────────────────────────────────────────────────
$app->post('/v1/admin/procesos/crear', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Pcs_Lin' => $body['Pcs_Lin'] ?? '',
            'Pcs_Det' => $body['Pcs_Det'] ?? '',
            'Pcs_Tip' => $body['Pcs_Tip'] ?? 'api',
            'Pcs_Est' => 'A',
        ];
        $id = $api->insert('procesos', $data);
        echo json_encode(['status' => true, 'message' => 'Proceso registrado exitosamente', 'data' => ['Pcs_Cod' => $id]]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── MODIFICAR PROCESO ────────────────────────────────────────────────────────
$app->post('/v1/admin/procesos/modificar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $pcsCod = $body['Pcs_Cod'] ?? null;
        if (!$pcsCod) {
            echo json_encode(['status' => false, 'error' => 'Pcs_Cod es requerido']);
            return;
        }
        $data = [];
        if (isset($body['Pcs_Lin'])) $data['Pcs_Lin'] = $body['Pcs_Lin'];
        if (isset($body['Pcs_Det'])) $data['Pcs_Det'] = $body['Pcs_Det'];
        if (isset($body['Pcs_Tip'])) $data['Pcs_Tip'] = $body['Pcs_Tip'];
        if (!empty($data)) {
            $api->update('procesos', $data, 'Pcs_Cod', $pcsCod);
        }
        echo json_encode(['status' => true, 'message' => 'Proceso modificado exitosamente']);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── ELIMINAR PROCESO ─────────────────────────────────────────────────────────
$app->post('/v1/admin/procesos/eliminar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $pcsCod = $body['Pcs_Cod'] ?? null;
        if (!$pcsCod) {
            echo json_encode(['status' => false, 'error' => 'Pcs_Cod es requerido']);
            return;
        }
        $api->update('procesos', ['Pcs_Est' => 'I'], 'Pcs_Cod', $pcsCod);
        echo json_encode(['status' => true, 'message' => 'Proceso eliminado exitosamente']);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

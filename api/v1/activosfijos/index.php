<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ApiResponse.php';

// ============================================================
// ACTIVOS FIJOS
// ============================================================
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
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/activosfijos/activos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Act_Des', 'Tac_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Act_Des' => $body['Act_Des'],
            'Act_Fec' => $body['Act_Fec'] ?? date('Y-m-d'),
            'Tac_Cod' => $body['Tac_Cod'],
            'Cus_Cod' => $body['Cus_Cod'] ?? null,
            'Act_Cos' => $body['Act_Cos'] ?? 0,
            'Act_Vid' => $body['Act_Vid'] ?? 0,
            'Act_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $actCod = $api->insert('activo', $data);
        ApiResponse::created(['Act_Cod' => $actCod], 'Activo creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/activosfijos/activos/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Act_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Act_Des'])) $data['Act_Des'] = $body['Act_Des'];
        if (isset($body['Tac_Cod'])) $data['Tac_Cod'] = $body['Tac_Cod'];
        if (isset($body['Cus_Cod'])) $data['Cus_Cod'] = $body['Cus_Cod'];
        if (isset($body['Act_Cos'])) $data['Act_Cos'] = $body['Act_Cos'];
        if (isset($body['Act_Vid'])) $data['Act_Vid'] = $body['Act_Vid'];
        if (isset($body['Act_Est'])) $data['Act_Est'] = $body['Act_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('activo', $data, 'Act_Cod', $body['Act_Cod']);
        ApiResponse::success(['Act_Cod' => $body['Act_Cod']], 'Activo modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/activosfijos/activos/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Act_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->softDelete('activo', 'Act_Cod', $body['Act_Cod'], 'Act_Est');
        ApiResponse::success(null, 'Activo eliminado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// TIPOS DE ACTIVO
// ============================================================
$app->post('/v1/activosfijos/tipos-activo', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('tipo_activo', [], 'Tac_Des ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/activosfijos/tipos-activo/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Tac_Cod', 'Tac_Des'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Tac_Cod' => $body['Tac_Cod'],
            'Tac_Des' => $body['Tac_Des'],
            'Tac_Est' => 'A'
        ];
        $api->insert('tipo_activo', $data);
        ApiResponse::created($data, 'Tipo de activo creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/activosfijos/tipos-activo/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Tac_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Tac_Des'])) $data['Tac_Des'] = $body['Tac_Des'];
        if (isset($body['Tac_Est'])) $data['Tac_Est'] = $body['Tac_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('tipo_activo', $data, 'Tac_Cod', $body['Tac_Cod']);
        ApiResponse::success(['Tac_Cod' => $body['Tac_Cod']], 'Tipo de activo modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// DEPRECIACIONES
// ============================================================
$app->post('/v1/activosfijos/depreciaciones', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('depreciacion', [], 'Dep_Fec DESC', 500);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// CUSTODIOS
// ============================================================
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
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/activosfijos/custodios/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Prs_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Prs_Cod' => $body['Prs_Cod'],
            'Cus_Est' => 'A'
        ];
        $cusCod = $api->insert('custodio', $data);
        ApiResponse::created(['Cus_Cod' => $cusCod], 'Custodio creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/activosfijos/custodios/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Cus_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Cus_Est'])) $data['Cus_Est'] = $body['Cus_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('custodio', $data, 'Cus_Cod', $body['Cus_Cod']);
        ApiResponse::success(['Cus_Cod' => $body['Cus_Cod']], 'Custodio modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// MANTENIMIENTOS
// ============================================================
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
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/activosfijos/mantenimientos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Act_Cod', 'Man_Fec'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Act_Cod' => $body['Act_Cod'],
            'Man_Fec' => $body['Man_Fec'],
            'Man_Des' => $body['Man_Des'] ?? '',
            'Man_Cos' => $body['Man_Cos'] ?? 0,
            'Tma_Cod' => $body['Tma_Cod'] ?? null
        ];
        $manCod = $api->insert('mantenimiento', $data);
        ApiResponse::created(['Man_Cod' => $manCod], 'Mantenimiento creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/activosfijos/mantenimientos/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Man_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Man_Des'])) $data['Man_Des'] = $body['Man_Des'];
        if (isset($body['Man_Cos'])) $data['Man_Cos'] = $body['Man_Cos'];
        if (isset($body['Tma_Cod'])) $data['Tma_Cod'] = $body['Tma_Cod'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('mantenimiento', $data, 'Man_Cod', $body['Man_Cod']);
        ApiResponse::success(['Man_Cod' => $body['Man_Cod']], 'Mantenimiento modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

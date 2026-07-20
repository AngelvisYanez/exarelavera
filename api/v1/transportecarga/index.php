<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ApiResponse.php';

// ============================================================
// VIAJES
// ============================================================
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
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/transportecarga/viajes/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Via_Fec', 'Veh_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Via_Fec' => $body['Via_Fec'],
            'Via_Hor' => $body['Via_Hor'] ?? date('H:i:s'),
            'Veh_Cod' => $body['Veh_Cod'],
            'Cho_Cod' => $body['Cho_Cod'] ?? null,
            'Cli_Cod' => $body['Cli_Cod'] ?? null,
            'Via_Des' => $body['Via_Des'] ?? '',
            'Via_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $viaCod = $api->insert('viaje', $data);
        ApiResponse::created(['Via_Cod' => $viaCod], 'Viaje creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/transportecarga/viajes/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Via_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Veh_Cod'])) $data['Veh_Cod'] = $body['Veh_Cod'];
        if (isset($body['Cho_Cod'])) $data['Cho_Cod'] = $body['Cho_Cod'];
        if (isset($body['Cli_Cod'])) $data['Cli_Cod'] = $body['Cli_Cod'];
        if (isset($body['Via_Des'])) $data['Via_Des'] = $body['Via_Des'];
        if (isset($body['Via_Est'])) $data['Via_Est'] = $body['Via_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('viaje', $data, 'Via_Cod', $body['Via_Cod']);
        ApiResponse::success(['Via_Cod' => $body['Via_Cod']], 'Viaje modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/transportecarga/viajes/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Via_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->update('viaje', ['Via_Est' => 'I'], 'Via_Cod', $body['Via_Cod']);
        ApiResponse::success(null, 'Viaje eliminado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// VEHICULOS
// ============================================================
$app->post('/v1/transportecarga/vehiculos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('vehiculo', ['Veh_Est' => 'A'], 'Veh_Placa ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/transportecarga/vehiculos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Veh_Placa'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Veh_Placa' => $body['Veh_Placa'],
            'Veh_Des' => $body['Veh_Des'] ?? '',
            'Veh_Mar' => $body['Veh_Mar'] ?? '',
            'Veh_Mod' => $body['Veh_Mod'] ?? '',
            'Veh_Est' => 'A'
        ];
        $vehCod = $api->insert('vehiculo', $data);
        ApiResponse::created(['Veh_Cod' => $vehCod], 'Vehículo creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/transportecarga/vehiculos/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Veh_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Veh_Placa'])) $data['Veh_Placa'] = $body['Veh_Placa'];
        if (isset($body['Veh_Des'])) $data['Veh_Des'] = $body['Veh_Des'];
        if (isset($body['Veh_Mar'])) $data['Veh_Mar'] = $body['Veh_Mar'];
        if (isset($body['Veh_Mod'])) $data['Veh_Mod'] = $body['Veh_Mod'];
        if (isset($body['Veh_Est'])) $data['Veh_Est'] = $body['Veh_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('vehiculo', $data, 'Veh_Cod', $body['Veh_Cod']);
        ApiResponse::success(['Veh_Cod' => $body['Veh_Cod']], 'Vehículo modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// TICKETS
// ============================================================
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
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/transportecarga/tickets/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Via_Cod', 'Tic_Fec'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Via_Cod' => $body['Via_Cod'],
            'Tic_Fec' => $body['Tic_Fec'],
            'Tic_Num' => $body['Tic_Num'] ?? '',
            'Tic_Pes' => $body['Tic_Pes'] ?? 0,
            'Tic_Val' => $body['Tic_Val'] ?? 0
        ];
        $ticCod = $api->insert('ticket', $data);
        ApiResponse::created(['Tic_Cod' => $ticCod], 'Ticket creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// FACTURAS VIAJE
// ============================================================
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
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// CLIENTES VEHICULO
// ============================================================
$app->post('/v1/transportecarga/clientes-vehiculo', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('cliente_vehiculo', ['Cv_Est' => 'A'], 'Cv_Nom ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

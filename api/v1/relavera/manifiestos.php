<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ApiResponse.php';

// ============================================================
// MANIFIESTOS
// ============================================================
$app->post('/v1/manifiestos/obtener', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = [];
        if (isset($body['Man_Num'])) $where['Man_Num'] = $body['Man_Num'];
        if (isset($body['Man_Est'])) $where['Man_Est'] = $body['Man_Est'];
        $perPage = $body['perPage'] ?? 50;
        $page = $body['page'] ?? 1;
        $result = $api->listPaged('manifiesto', $where, 'Man_Fec DESC', $page, $perPage);
        utf8_encode_deep($result['data']);
        ApiResponse::paginated($result['data'], $result['total'], $result['page'], $result['perPage'], $result['pages']);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/manifiestos/obtener-detalle', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Man_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $manifiesto = $api->getById('manifiesto', 'Man_Cod', $body['Man_Cod']);
        if (!$manifiesto) {
            ApiResponse::notFound('Manifiesto no encontrado');
            return;
        }
        $plantas = $api->list('manifiesto_plantas', ['Man_Cod' => $body['Man_Cod']]);
        $celdas = $api->list('manifiesto_celdas', ['Man_Cod' => $body['Man_Cod']]);
        $desechos = $api->list('manifiesto_desechos', ['Man_Cod' => $body['Man_Cod']]);
        $tecnico = $api->list('manifiesto_tecnico', ['Man_Cod' => $body['Man_Cod']]);
        $vehiculos = $api->list('manifiesto_vehiculo', ['Man_Cod' => $body['Man_Cod']]);
        $choferes = $api->list('manifiesto_chofer', ['Man_Cod' => $body['Man_Cod']]);
        utf8_encode_deep($manifiesto);
        utf8_encode_deep($plantas);
        utf8_encode_deep($celdas);
        utf8_encode_deep($desechos);
        utf8_encode_deep($tecnico);
        utf8_encode_deep($vehiculos);
        utf8_encode_deep($choferes);
        ApiResponse::success([
            'manifiesto' => $manifiesto,
            'plantas' => $plantas,
            'celdas' => $celdas,
            'desechos' => $desechos,
            'tecnico' => $tecnico,
            'vehiculos' => $vehiculos,
            'choferes' => $choferes
        ]);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/manifiestos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Man_Num', 'Man_Fec', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Man_Num' => $body['Man_Num'],
            'Man_Fec' => $body['Man_Fec'],
            'Man_Est' => $body['Man_Est'] ?? 'A',
            'Man_Obs' => $body['Man_Obs'] ?? '',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        if (isset($body['Cli_Cod'])) $data['Cli_Cod'] = $body['Cli_Cod'];
        if (isset($body['Pla_Cod'])) $data['Pla_Cod'] = $body['Pla_Cod'];
        $manCod = $api->insert('manifiesto', $data);
        ApiResponse::created(['Man_Cod' => $manCod], 'Manifiesto creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/manifiestos/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Man_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Man_Est'])) $data['Man_Est'] = $body['Man_Est'];
        if (isset($body['Man_Obs'])) $data['Man_Obs'] = $body['Man_Obs'];
        if (isset($body['Cli_Cod'])) $data['Cli_Cod'] = $body['Cli_Cod'];
        if (isset($body['Pla_Cod'])) $data['Pla_Cod'] = $body['Pla_Cod'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('manifiesto', $data, 'Man_Cod', $body['Man_Cod']);
        ApiResponse::success(['Man_Cod' => $body['Man_Cod']], 'Manifiesto modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/manifiestos/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Man_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->update('manifiesto', ['Man_Est' => 'I'], 'Man_Cod', $body['Man_Cod']);
        ApiResponse::success(null, 'Manifiesto eliminado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/manifiestos/crear-detalle', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Man_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        if (!empty($body['plantas']) && is_array($body['plantas'])) {
            foreach ($body['plantas'] as $planta) {
                $planta['Man_Cod'] = $body['Man_Cod'];
                $api->insert('manifiesto_plantas', $planta);
            }
        }
        if (!empty($body['celdas']) && is_array($body['celdas'])) {
            foreach ($body['celdas'] as $celda) {
                $celda['Man_Cod'] = $body['Man_Cod'];
                $api->insert('manifiesto_celdas', $celda);
            }
        }
        $api->commit();
        ApiResponse::success(null, 'Detalle del manifiesto creado exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/manifiestos/crear-tecnico', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Man_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        $tecnico = [
            'Man_Cod' => $body['Man_Cod'],
            'Mtc_Fec' => $body['Mtc_Fec'] ?? date('Y-m-d'),
            'Mtc_Hum' => $body['Mtc_Hum'] ?? 0,
            'Mtc_Obs' => $body['Mtc_Obs'] ?? ''
        ];
        $api->insert('manifiesto_tecnico', $tecnico);
        if (!empty($body['niveles_humedad']) && is_array($body['niveles_humedad'])) {
            foreach ($body['niveles_humedad'] as $nivel) {
                $nivel['Man_Cod'] = $body['Man_Cod'];
                $api->insert('manifiesto_nivel_humedad', $nivel);
            }
        }
        $api->commit();
        ApiResponse::success(null, 'Datos técnicos registrados exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/manifiestos/crear-transporte', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Man_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        if (!empty($body['transporte']) && is_array($body['transporte'])) {
            foreach ($body['transporte'] as $trans) {
                $trans['Man_Cod'] = $body['Man_Cod'];
                $api->insert('manifiesto_transporte', $trans);
            }
        }
        if (!empty($body['vehiculos']) && is_array($body['vehiculos'])) {
            foreach ($body['vehiculos'] as $veh) {
                $veh['Man_Cod'] = $body['Man_Cod'];
                $api->insert('manifiesto_vehiculo', $veh);
            }
        }
        if (!empty($body['choferes']) && is_array($body['choferes'])) {
            foreach ($body['choferes'] as $cho) {
                $cho['Man_Cod'] = $body['Man_Cod'];
                $api->insert('manifiesto_chofer', $cho);
            }
        }
        $api->commit();
        ApiResponse::success(null, 'Transporte registrado exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/manifiestos/crear-anticipo', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Man_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Man_Cod' => $body['Man_Cod'],
            'Mam_Fec' => $body['Mam_Fec'] ?? date('Y-m-d'),
            'Mam_Val' => $body['Mam_Val'] ?? 0,
            'Mam_Obs' => $body['Mam_Obs'] ?? '',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $api->insert('manifiesto_anticipo', $data);
        ApiResponse::success(null, 'Anticipo registrado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/manifiestos/crear-desechos', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Man_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Man_Cod' => $body['Man_Cod'],
            'Mad_Tip' => $body['Mad_Tip'] ?? '',
            'Mad_Can' => $body['Mad_Can'] ?? 0,
            'Mad_Obs' => $body['Mad_Obs'] ?? '',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $api->insert('manifiesto_desechos', $data);
        ApiResponse::success(null, 'Desecho registrado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

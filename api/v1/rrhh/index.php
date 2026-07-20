<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ApiResponse.php';

// ============================================================
// PERSONAL (con transacción persona + personal)
// ============================================================
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
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/rrhh/personal/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Prs_Ced', 'Prs_Nom', 'Prs_Ape', 'Car_Cod', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        $persona = [
            'Prs_Ced' => $body['Prs_Ced'],
            'Prs_Nom' => $body['Prs_Nom'],
            'Prs_Ape' => $body['Prs_Ape'],
            'Prs_Dir' => $body['Prs_Dir'] ?? '',
            'Prs_Tel' => $body['Prs_Tel'] ?? '',
            'Prs_Cel' => $body['Prs_Cel'] ?? '',
            'Prs_Cor' => $body['Prs_Cor'] ?? '',
            'Ciu_Cod' => $body['Ciu_Cod'] ?? null
        ];
        $prsCod = $api->insert('persona', $persona);
        if ($prsCod === false) {
            $api->rollback();
            ApiResponse::serverError('Error al crear persona: ' . $api->getErrorMsg());
            return;
        }
        $personal = [
            'Prs_Cod' => $prsCod,
            'Per_Fec' => $body['Per_Fec'] ?? date('Y-m-d'),
            'Per_Est' => 'A',
            'Car_Cod' => $body['Car_Cod'],
            'Dep_Cod' => $body['Dep_Cod'] ?? null,
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $perCod = $api->insert('personal', $personal);
        if ($perCod === false) {
            $api->rollback();
            ApiResponse::serverError('Error al crear personal: ' . $api->getErrorMsg());
            return;
        }
        $api->commit();
        ApiResponse::created(['Per_Cod' => $perCod, 'Prs_Cod' => $prsCod], 'Personal creado exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/rrhh/personal/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Per_Cod', 'Prs_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        $personaData = [];
        if (isset($body['Prs_Nom'])) $personaData['Prs_Nom'] = $body['Prs_Nom'];
        if (isset($body['Prs_Ape'])) $personaData['Prs_Ape'] = $body['Prs_Ape'];
        if (isset($body['Prs_Dir'])) $personaData['Prs_Dir'] = $body['Prs_Dir'];
        if (isset($body['Prs_Tel'])) $personaData['Prs_Tel'] = $body['Prs_Tel'];
        if (isset($body['Prs_Cel'])) $personaData['Prs_Cel'] = $body['Prs_Cel'];
        if (isset($body['Prs_Cor'])) $personaData['Prs_Cor'] = $body['Prs_Cor'];
        if (isset($body['Ciu_Cod'])) $personaData['Ciu_Cod'] = $body['Ciu_Cod'];
        if (!empty($personaData)) {
            $api->update('persona', $personaData, 'Prs_Cod', $body['Prs_Cod']);
        }
        $personalData = [];
        if (isset($body['Car_Cod'])) $personalData['Car_Cod'] = $body['Car_Cod'];
        if (isset($body['Dep_Cod'])) $personalData['Dep_Cod'] = $body['Dep_Cod'];
        if (isset($body['Per_Est'])) $personalData['Per_Est'] = $body['Per_Est'];
        if (!empty($personalData)) {
            $api->update('personal', $personalData, 'Per_Cod', $body['Per_Cod']);
        }
        $api->commit();
        ApiResponse::success(['Per_Cod' => $body['Per_Cod']], 'Personal modificado exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/rrhh/personal/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Per_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->softDelete('personal', 'Per_Cod', $body['Per_Cod'], 'Per_Est');
        ApiResponse::success(null, 'Personal eliminado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// CONTRATOS
// ============================================================
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
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/rrhh/contratos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Per_Cod', 'Con_Fec_Ing', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Per_Cod' => $body['Per_Cod'],
            'Con_Fec_Ing' => $body['Con_Fec_Ing'],
            'Con_Fec_Sal' => $body['Con_Fec_Sal'] ?? null,
            'Tco_Cod' => $body['Tco_Cod'] ?? null,
            'Con_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $conCod = $api->insert('contrato', $data);
        ApiResponse::created(['Con_Cod' => $conCod], 'Contrato creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/rrhh/contratos/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Con_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Con_Fec_Sal'])) $data['Con_Fec_Sal'] = $body['Con_Fec_Sal'];
        if (isset($body['Tco_Cod'])) $data['Tco_Cod'] = $body['Tco_Cod'];
        if (isset($body['Con_Est'])) $data['Con_Est'] = $body['Con_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('contrato', $data, 'Con_Cod', $body['Con_Cod']);
        ApiResponse::success(['Con_Cod' => $body['Con_Cod']], 'Contrato modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/rrhh/contratos/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Con_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->update('contrato', ['Con_Est' => 'I'], 'Con_Cod', $body['Con_Cod']);
        ApiResponse::success(null, 'Contrato eliminado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// ROLES DE PAGO
// ============================================================
$app->post('/v1/rrhh/roles-pago', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod']];
        if (isset($body['Rol_Ano'])) $where['Rol_Ano'] = $body['Rol_Ano'];
        if (isset($body['Rol_Mes'])) $where['Rol_Mes'] = $body['Rol_Mes'];
        $perPage = $body['perPage'] ?? 50;
        $page = $body['page'] ?? 1;
        $result = $api->listPaged('rol_pago', $where, 'Rol_Ano DESC, Rol_Mes DESC', $page, $perPage);
        utf8_encode_deep($result['data']);
        ApiResponse::paginated($result['data'], $result['total'], $result['page'], $result['perPage'], $result['pages']);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// DEPARTAMENTOS
// ============================================================
$app->post('/v1/rrhh/departamentos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('departamento', ['Emp_Cod' => $body['Emp_Cod']], 'Dep_Des ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/rrhh/departamentos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Dep_Des', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Dep_Des' => $body['Dep_Des'],
            'Dep_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $depCod = $api->insert('departamento', $data);
        ApiResponse::created(['Dep_Cod' => $depCod], 'Departamento creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/rrhh/departamentos/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Dep_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Dep_Des'])) $data['Dep_Des'] = $body['Dep_Des'];
        if (isset($body['Dep_Est'])) $data['Dep_Est'] = $body['Dep_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('departamento', $data, 'Dep_Cod', $body['Dep_Cod']);
        ApiResponse::success(['Dep_Cod' => $body['Dep_Cod']], 'Departamento modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// CARGOS
// ============================================================
$app->post('/v1/rrhh/cargos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = [];
        if (isset($body['Emp_Cod']) && !empty($body['Emp_Cod'])) {
            $where['Emp_Cod'] = $body['Emp_Cod'];
        }
        $data = $api->list('cargo', $where, 'Car_Des ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/rrhh/cargos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Car_Des'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Car_Des' => $body['Car_Des'],
            'Car_Est' => 'A'
        ];
        if (isset($body['Emp_Cod'])) $data['Emp_Cod'] = $body['Emp_Cod'];
        $carCod = $api->insert('cargo', $data);
        ApiResponse::created(['Car_Cod' => $carCod], 'Cargo creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/rrhh/cargos/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Car_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Car_Des'])) $data['Car_Des'] = $body['Car_Des'];
        if (isset($body['Car_Est'])) $data['Car_Est'] = $body['Car_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('cargo', $data, 'Car_Cod', $body['Car_Cod']);
        ApiResponse::success(['Car_Cod' => $body['Car_Cod']], 'Cargo modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

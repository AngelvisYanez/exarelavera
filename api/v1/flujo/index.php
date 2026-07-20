<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ApiResponse.php';

// ============================================================
// MODELOS DE FLUJO
// ============================================================
$app->post('/v1/flujo/modelos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $where = ['Emp_Cod' => $body['Emp_Cod']];
        if (isset($body['search']) && !empty($body['search'])) {
            $sql = "SELECT * FROM wf_flujos_modelos WHERE Emp_Cod = " . $api->escape($body['Emp_Cod']) . " AND Wfm_Nom LIKE " . $api->escape('%' . $body['search'] . '%') . " ORDER BY Wfm_Nom ASC";
            $data = $api->query($sql);
        } else {
            $data = $api->list('wf_flujos_modelos', $where, 'Wfm_Nom ASC');
        }
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/flujo/modelos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Wfm_Nom', 'Emp_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Wfm_Nom' => $body['Wfm_Nom'],
            'Wfm_Des' => $body['Wfm_Des'] ?? '',
            'Wfm_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod']
        ];
        $wfmCod = $api->insert('wf_flujos_modelos', $data);
        ApiResponse::created(['Wfm_Cod' => $wfmCod], 'Modelo de flujo creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/flujo/modelos/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Wfm_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Wfm_Nom'])) $data['Wfm_Nom'] = $body['Wfm_Nom'];
        if (isset($body['Wfm_Des'])) $data['Wfm_Des'] = $body['Wfm_Des'];
        if (isset($body['Wfm_Est'])) $data['Wfm_Est'] = $body['Wfm_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('wf_flujos_modelos', $data, 'Wfm_Cod', $body['Wfm_Cod']);
        ApiResponse::success(['Wfm_Cod' => $body['Wfm_Cod']], 'Modelo de flujo modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// NODOS
// ============================================================
$app->post('/v1/flujo/nodos', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT n.*, d.Dep_Des, p.Per_Des
                FROM wf_nodos n
                LEFT JOIN departamen d ON n.Dep_Cod = d.Dep_Cod
                LEFT JOIN perfiles p ON n.Per_Cod = p.Per_Cod
                WHERE n.Wfm_Cod = " . $api->escape($body['Wfm_Cod']) . "
                ORDER BY n.Nod_Nom ASC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/flujo/nodos/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Wfm_Cod', 'Nod_Nom', 'Nod_Tip'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Wfm_Cod' => $body['Wfm_Cod'],
            'Nod_Nom' => $body['Nod_Nom'],
            'Nod_Tip' => $body['Nod_Tip'],
            'Nod_Est' => 'A',
            'Dep_Cod' => $body['Dep_Cod'] ?? null,
            'Per_Cod' => $body['Per_Cod'] ?? null
        ];
        if (isset($body['Nod_X'])) $data['Nod_X'] = $body['Nod_X'];
        if (isset($body['Nod_Y'])) $data['Nod_Y'] = $body['Nod_Y'];
        $nodCod = $api->insert('wf_nodos', $data);
        ApiResponse::created(['Nod_Cod' => $nodCod], 'Nodo creado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/flujo/nodos/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Nod_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Nod_Nom'])) $data['Nod_Nom'] = $body['Nod_Nom'];
        if (isset($body['Nod_Tip'])) $data['Nod_Tip'] = $body['Nod_Tip'];
        if (isset($body['Nod_Est'])) $data['Nod_Est'] = $body['Nod_Est'];
        if (isset($body['Dep_Cod'])) $data['Dep_Cod'] = $body['Dep_Cod'];
        if (isset($body['Per_Cod'])) $data['Per_Cod'] = $body['Per_Cod'];
        if (isset($body['Nod_X'])) $data['Nod_X'] = $body['Nod_X'];
        if (isset($body['Nod_Y'])) $data['Nod_Y'] = $body['Nod_Y'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('wf_nodos', $data, 'Nod_Cod', $body['Nod_Cod']);
        ApiResponse::success(['Nod_Cod' => $body['Nod_Cod']], 'Nodo modificado exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// CONEXIONES
// ============================================================
$app->post('/v1/flujo/conexiones', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT c.*, ori.Nod_Nom AS ori_nombre, ori.Nod_Tip AS ori_tipo,
                       des.Nod_Nom AS des_nombre, des.Nod_Tip AS des_tipo
                FROM wf_conexiones c
                INNER JOIN wf_nodos ori ON c.Nod_Ori = ori.Nod_Cod
                INNER JOIN wf_nodos des ON c.Nod_Des = des.Nod_Cod
                WHERE c.Wfm_Cod = " . $api->escape($body['Wfm_Cod']) . "
                ORDER BY c.Con_Cod ASC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/flujo/conexiones/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Wfm_Cod', 'Nod_Ori', 'Nod_Des'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Wfm_Cod' => $body['Wfm_Cod'],
            'Nod_Ori' => $body['Nod_Ori'],
            'Nod_Des' => $body['Nod_Des'],
            'Con_Eti' => $body['Con_Eti'] ?? ''
        ];
        $conCod = $api->insert('wf_conexiones', $data);
        ApiResponse::created(['Con_Cod' => $conCod], 'Conexión creada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/flujo/conexiones/eliminar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Con_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->delete('wf_conexiones', 'Con_Cod', $body['Con_Cod']);
        ApiResponse::success(null, 'Conexión eliminada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// REGLAS
// ============================================================
$app->post('/v1/flujo/reglas', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = $api->list('wf_reglas', ['Wfm_Cod' => $body['Wfm_Cod']], 'Reg_Cod ASC');
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/flujo/reglas/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Wfm_Cod', 'Reg_Nom', 'Reg_Cam', 'Reg_Ope', 'Reg_Val'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Wfm_Cod' => $body['Wfm_Cod'],
            'Reg_Nom' => $body['Reg_Nom'],
            'Reg_Cam' => $body['Reg_Cam'],
            'Reg_Ope' => $body['Reg_Ope'],
            'Reg_Val' => $body['Reg_Val'],
            'Reg_Est' => 'A'
        ];
        $regCod = $api->insert('wf_reglas', $data);
        ApiResponse::created(['Reg_Cod' => $regCod], 'Regla creada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/flujo/reglas/modificar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Reg_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [];
        if (isset($body['Reg_Nom'])) $data['Reg_Nom'] = $body['Reg_Nom'];
        if (isset($body['Reg_Cam'])) $data['Reg_Cam'] = $body['Reg_Cam'];
        if (isset($body['Reg_Ope'])) $data['Reg_Ope'] = $body['Reg_Ope'];
        if (isset($body['Reg_Val'])) $data['Reg_Val'] = $body['Reg_Val'];
        if (isset($body['Reg_Est'])) $data['Reg_Est'] = $body['Reg_Est'];
        if (empty($data)) {
            ApiResponse::badRequest('No hay campos para actualizar');
            return;
        }
        $api->update('wf_reglas', $data, 'Reg_Cod', $body['Reg_Cod']);
        ApiResponse::success(['Reg_Cod' => $body['Reg_Cod']], 'Regla modificada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

// ============================================================
// INSTANCIAS
// ============================================================
$app->post('/v1/flujo/instancias', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT i.*, fm.Wfm_Nom, n.Nod_Nom, n.Nod_Tip
                FROM wf_instancias i
                INNER JOIN wf_flujos_modelos fm ON i.Wfm_Cod = fm.Wfm_Cod
                LEFT JOIN wf_nodos n ON i.Nod_Act = n.Nod_Cod
                WHERE fm.Emp_Cod = " . $api->escape($body['Emp_Cod']);
        $conditions = [];
        if (isset($body['Ins_Est'])) $conditions[] = "i.Ins_Est = " . $api->escape($body['Ins_Est']);
        if (isset($body['Ins_Ent_Typ'])) $conditions[] = "i.Ins_Ent_Typ = " . $api->escape($body['Ins_Ent_Typ']);
        if (!empty($conditions)) $sql .= " AND " . implode(" AND ", $conditions);
        $sql .= " ORDER BY i.Ins_Cod DESC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/flujo/instancias/crear', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Wfm_Cod', 'Ins_Ent_Typ', 'Ins_Ent_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $primerNodo = $api->queryRow("SELECT Nod_Cod FROM wf_nodos WHERE Wfm_Cod = " . $api->escape($body['Wfm_Cod']) . " AND Nod_Tip = 'I' AND Nod_Est = 'A' LIMIT 1");
        $nodAct = $primerNodo ? $primerNodo['Nod_Cod'] : null;
        $data = [
            'Wfm_Cod' => $body['Wfm_Cod'],
            'Ins_Ent_Typ' => $body['Ins_Ent_Typ'],
            'Ins_Ent_Cod' => $body['Ins_Ent_Cod'],
            'Ins_Est' => 'A',
            'Ins_Fec_Ini' => date('Y-m-d H:i:s'),
            'Nod_Act' => $nodAct
        ];
        $insCod = $api->insert('wf_instancias', $data);
        if ($insCod && $nodAct) {
            $api->insert('wf_instancias_nodos', [
                'Ins_Cod' => $insCod,
                'Nod_Cod' => $nodAct,
                'Isn_Fec' => date('Y-m-d H:i:s'),
                'Isn_Obs' => 'Inicio del workflow'
            ]);
        }
        ApiResponse::created(['Ins_Cod' => $insCod], 'Instancia de workflow creada exitosamente');
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/flujo/instancias/avanzar', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Ins_Cod', 'Nod_Des'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $api->beginTransaction();
        $conexion = $api->queryRow("SELECT * FROM wf_conexiones WHERE Wfm_Cod = (SELECT Wfm_Cod FROM wf_instancias WHERE Ins_Cod = " . $api->escape($body['Ins_Cod']) . ") AND Nod_Ori = (SELECT Nod_Act FROM wf_instancias WHERE Ins_Cod = " . $api->escape($body['Ins_Cod']) . ") AND Nod_Des = " . $api->escape($body['Nod_Des']));
        if (!$conexion) {
            $api->rollback();
            ApiResponse::badRequest('No existe conexión válida entre los nodos');
            return;
        }
        $api->update('wf_instancias', ['Nod_Act' => $body['Nod_Des']], 'Ins_Cod', $body['Ins_Cod']);
        $api->insert('wf_instancias_nodos', [
            'Ins_Cod' => $body['Ins_Cod'],
            'Nod_Cod' => $body['Nod_Des'],
            'Usu_Cod' => $body['Usu_Cod'] ?? null,
            'Dep_Cod' => $body['Dep_Cod'] ?? null,
            'Isn_Fec' => date('Y-m-d H:i:s'),
            'Isn_Obs' => $body['Isn_Obs'] ?? ''
        ]);
        $nodoDestino = $api->queryRow("SELECT Nod_Tip FROM wf_nodos WHERE Nod_Cod = " . $api->escape($body['Nod_Des']));
        if ($nodoDestino && $nodoDestino['Nod_Tip'] === 'F') {
            $api->update('wf_instancias', ['Ins_Est' => 'F', 'Ins_Fec_Fin' => date('Y-m-d H:i:s')], 'Ins_Cod', $body['Ins_Cod']);
        }
        $api->commit();
        ApiResponse::success(['Ins_Cod' => $body['Ins_Cod'], 'Nod_Act' => $body['Nod_Des']], 'Workflow avanzado exitosamente');
    } catch (\Throwable $e) {
        if (isset($api)) $api->rollback();
        ApiResponse::serverError($e->getMessage());
    }
});

$app->post('/v1/flujo/instancias/historial', function () use ($app) {
    $body = getBody();
    if (!ApiResponse::validateRequired(['Ins_Cod'], $body)) return;
    try {
        $api = new DataAPI($body['Bdd']);
        $sql = "SELECT ino.*, n.Nod_Nom, n.Nod_Tip,
                       CONCAT(p.Prs_Nom, ' ', p.Prs_Ape) AS usuario_nombre,
                       d.Dep_Des
                FROM wf_instancias_nodos ino
                INNER JOIN wf_nodos n ON ino.Nod_Cod = n.Nod_Cod
                LEFT JOIN usuarios u ON ino.Usu_Cod = u.Usu_Cod
                LEFT JOIN persona p ON u.Prs_Cod = p.Prs_Cod
                LEFT JOIN departamen d ON ino.Dep_Cod = d.Dep_Cod
                WHERE ino.Ins_Cod = " . $api->escape($body['Ins_Cod']) . "
                ORDER BY ino.Isn_Fec ASC";
        $data = $api->query($sql);
        utf8_encode_deep($data);
        ApiResponse::success($data);
    } catch (\Throwable $e) {
        ApiResponse::serverError($e->getMessage());
    }
});

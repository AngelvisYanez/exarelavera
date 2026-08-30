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

// ── USUARIOS CRUD ─────────────────────────────────────────────────────────────
$app->post('/v1/admin/usuarios/crear', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $personaData = [
            'Prs_Ced' => $body['Prs_Ced'] ?? '',
            'Prs_Nom' => $body['Prs_Nom'] ?? '',
            'Prs_Ape' => $body['Prs_Ape'] ?? '',
            'Prs_Sex' => $body['Prs_Sex'] ?? '',
            'Prs_Dir' => $body['Prs_Dir'] ?? '',
            'Prs_Tel' => $body['Prs_Tel'] ?? '',
            'Prs_Cel' => $body['Prs_Cel'] ?? '',
            'Prs_Cor' => $body['Prs_Cor'] ?? '',
            'Ciu_Cod' => $body['Ciu_Cod'] ?? '',
            'Ide_Cod' => $body['Ide_Cod'] ?? '',
        ];
        $prsCod = $api->insert('persona', $personaData);
        if (!$prsCod) {
            echo json_encode(['success' => false, 'error' => 'Error al crear persona']);
            return;
        }
        $usuarioData = [
            'Usu_Ced' => $body['Usu_Ced'] ?? $body['Prs_Ced'] ?? '',
            'Usu_Con' => password_hash($body['Usu_Con'] ?? bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
            'Usu_Est' => 'A',
            'Per_Cod' => $body['Per_Cod'] ?? 1,
            'Suc_Cod' => $body['Suc_Cod'] ?? 1,
            'Emp_Cod' => $body['Emp_Cod'] ?? 1,
            'Prs_Cod' => $prsCod,
        ];
        $usuCod = $api->insert('usuarios', $usuarioData);
        echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente', 'data' => ['Usu_Cod' => $usuCod]]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/usuarios/modificar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $usuCod = $body['Usu_Cod'] ?? null;
        if (!$usuCod) {
            echo json_encode(['success' => false, 'error' => 'Usu_Cod es requerido']);
            return;
        }
        if (isset($body['Prs_Nom']) || isset($body['Prs_Ape'])) {
            $personaData = [];
            if (isset($body['Prs_Nom'])) $personaData['Prs_Nom'] = $body['Prs_Nom'];
            if (isset($body['Prs_Ape'])) $personaData['Prs_Ape'] = $body['Prs_Ape'];
            if (isset($body['Prs_Ced'])) $personaData['Prs_Ced'] = $body['Prs_Ced'];
            if (isset($body['Prs_Dir'])) $personaData['Prs_Dir'] = $body['Prs_Dir'];
            if (isset($body['Prs_Tel'])) $personaData['Prs_Tel'] = $body['Prs_Tel'];
            if (isset($body['Prs_Cel'])) $personaData['Prs_Cel'] = $body['Prs_Cel'];
            if (isset($body['Prs_Cor'])) $personaData['Prs_Cor'] = $body['Prs_Cor'];
            $usuario = $api->getById('usuarios', 'Usu_Cod', $usuCod);
            if ($usuario && !empty($personaData)) {
                $api->update('persona', $personaData, 'Prs_Cod', $usuario['Prs_Cod']);
            }
        }
        $usuarioUpdate = [];
        if (isset($body['Per_Cod'])) $usuarioUpdate['Per_Cod'] = $body['Per_Cod'];
        if (isset($body['Suc_Cod'])) $usuarioUpdate['Suc_Cod'] = $body['Suc_Cod'];
        if (isset($body['Usu_Con']) && !empty($body['Usu_Con'])) {
            $usuarioUpdate['Usu_Con'] = password_hash($body['Usu_Con'], PASSWORD_DEFAULT);
        }
        if (!empty($usuarioUpdate)) {
            $api->update('usuarios', $usuarioUpdate, 'Usu_Cod', $usuCod);
        }
        echo json_encode(['success' => true, 'message' => 'Usuario modificado exitosamente']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/usuarios/eliminar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $usuCod = $body['Usu_Cod'] ?? null;
        if (!$usuCod) {
            echo json_encode(['success' => false, 'error' => 'Usu_Cod es requerido']);
            return;
        }
        $api->update('usuarios', ['Usu_Est' => 'I'], 'Usu_Cod', $usuCod);
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

// ── PERFILES CRUD ─────────────────────────────────────────────────────────────
$app->post('/v1/admin/perfiles/crear', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $perCod = $body['Per_Cod'] ?? null;
        $data = ['Per_Des' => $body['Per_Des'] ?? '', 'Per_Est' => 'A'];
        if ($perCod) $data['Per_Cod'] = $perCod;
        $id = $api->insert('perfil', $data);
        echo json_encode(['success' => true, 'message' => 'Perfil creado exitosamente', 'data' => ['Per_Cod' => $id]]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/perfiles/modificar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $perCod = $body['Per_Cod'] ?? null;
        if (!$perCod) {
            echo json_encode(['success' => false, 'error' => 'Per_Cod es requerido']);
            return;
        }
        $api->update('perfil', ['Per_Des' => $body['Per_Des'] ?? ''], 'Per_Cod', $perCod);
        echo json_encode(['success' => true, 'message' => 'Perfil modificado exitosamente']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/perfiles/eliminar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $perCod = $body['Per_Cod'] ?? null;
        if (!$perCod) {
            echo json_encode(['success' => false, 'error' => 'Per_Cod es requerido']);
            return;
        }
        $api->update('perfil', ['Per_Est' => 'I'], 'Per_Cod', $perCod);
        echo json_encode(['success' => true, 'message' => 'Perfil eliminado exitosamente']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

// ── SUCURSALES CRUD ───────────────────────────────────────────────────────────
$app->post('/v1/admin/sucursales/crear', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Suc_Des' => $body['Suc_Des'] ?? '',
            'Suc_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod'] ?? 1,
        ];
        $id = $api->insert('sucursal', $data);
        echo json_encode(['success' => true, 'message' => 'Sucursal creada exitosamente', 'data' => ['Suc_Cod' => $id]]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/sucursales/modificar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sucCod = $body['Suc_Cod'] ?? null;
        if (!$sucCod) {
            echo json_encode(['success' => false, 'error' => 'Suc_Cod es requerido']);
            return;
        }
        $api->update('sucursal', ['Suc_Des' => $body['Suc_Des'] ?? ''], 'Suc_Cod', $sucCod);
        echo json_encode(['success' => true, 'message' => 'Sucursal modificada exitosamente']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/sucursales/eliminar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $sucCod = $body['Suc_Cod'] ?? null;
        if (!$sucCod) {
            echo json_encode(['success' => false, 'error' => 'Suc_Cod es requerido']);
            return;
        }
        $api->update('sucursal', ['Suc_Est' => 'I'], 'Suc_Cod', $sucCod);
        echo json_encode(['success' => true, 'message' => 'Sucursal eliminada exitosamente']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

// ── TICKETS CRUD ──────────────────────────────────────────────────────────────
$app->post('/v1/admin/tickets/crear', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $data = [
            'Tic_Tit' => $body['Tic_Tit'] ?? '',
            'Tic_Des' => $body['Tic_Des'] ?? '',
            'Tic_Est' => 'A',
            'Emp_Cod' => $body['Emp_Cod'] ?? 1,
            'Usu_Cod' => $body['Usu_Cod'] ?? 1,
            'Tic_Fec' => date('Y-m-d'),
        ];
        $id = $api->insert('soporte_ticket', $data);
        echo json_encode(['success' => true, 'message' => 'Ticket creado exitosamente', 'data' => ['Tic_Cod' => $id]]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

$app->post('/v1/admin/tickets/cerrar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $ticCod = $body['Tic_Cod'] ?? null;
        if (!$ticCod) {
            echo json_encode(['success' => false, 'error' => 'Tic_Cod es requerido']);
            return;
        }
        $api->update('soporte_ticket', [
            'Tic_Est' => 'C',
            'Tic_Fec_Ter' => date('Y-m-d H:i:s'),
        ], 'Tic_Cod', $ticCod);
        echo json_encode(['success' => true, 'message' => 'Ticket cerrado exitosamente']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
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

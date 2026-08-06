<?php
require_once __DIR__ . '/../../../classes/DataAPI.php';
require_once __DIR__ . '/../../../classes/ExternalModuleRunner.php';

// ── LISTAR MÓDULOS EXTERNOS ──────────────────────────────────────────────────
$app->post('/v1/admin/external-modules/obtener', function () use ($app) {
    $body = getBody();
    try {
        $runner = new ExternalModuleRunner($body['Bdd']);
        $data = $runner->listExternalModules($body['Emp_Cod']);
        utf8_encode_deep($data);
        echo json_encode(['status' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── OBTENER MÓDULO EXTERNO POR ID ────────────────────────────────────────────
$app->post('/v1/admin/external-modules/obtener-uno', function () use ($app) {
    $body = getBody();
    try {
        $runner = new ExternalModuleRunner($body['Bdd']);
        $data = $runner->getModuleInfo($body['Dir_Cod']);
        utf8_encode_deep($data);
        echo json_encode(['status' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── CREAR MÓDULO EXTERNO ─────────────────────────────────────────────────────
$app->post('/v1/admin/external-modules/crear', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        
        $data = [
            'Dir_Nom' => $body['Dir_Nom'] ?? '',
            'Dir_Rut' => $body['Dir_Rut'] ?? '',
            'Dir_Tip' => 'externo',
            'Dir_Ext_Tip' => $body['Dir_Ext_Tip'] ?? 'node',
            'Dir_Ext_Cmd' => $body['Dir_Ext_Cmd'] ?? '',
            'Dir_Ext_Args' => $body['Dir_Ext_Args'] ?? '[]',
            'Dir_Ext_Cwd' => $body['Dir_Ext_Cwd'] ?? '',
            'Dir_Ext_Env' => $body['Dir_Ext_Env'] ?? '{}',
            'Dir_Ext_Port' => $body['Dir_Ext_Port'] ?? null,
            'Dir_Ext_Status' => 'stopped',
            'Dir_Ext_Timeout' => $body['Dir_Ext_Timeout'] ?? 300,
            'Dir_Ext_Max_Retries' => $body['Dir_Ext_Max_Retries'] ?? 3,
            'Dir_Ext_Auto_Start' => $body['Dir_Ext_Auto_Start'] ?? 0,
            'Dir_Est' => 'A',
            'Dir_Des' => $body['Dir_Des'] ?? '',
            'Dir_Ver' => $body['Dir_Ver'] ?? '1.0.0',
            'Dir_Aut' => $body['Dir_Aut'] ?? 'S',
            'Emp_Cod' => $body['Emp_Cod'] ?? 1,
        ];
        
        $id = $api->insert('directorio_modulos', $data);
        
        // Guardar configuración si se proporcionó
        if (isset($body['config']) && is_array($body['config'])) {
            $runner = new ExternalModuleRunner($body['Bdd']);
            foreach ($body['config'] as $cfg) {
                $runner->saveModuleConfig(
                    $id,
                    $body['Emp_Cod'] ?? 1,
                    $cfg['clave'],
                    $cfg['valor'],
                    $cfg['tipo'] ?? 'string',
                    $cfg['descripcion'] ?? null
                );
            }
        }
        
        echo json_encode([
            'status' => true,
            'message' => 'Módulo externo registrado exitosamente',
            'data' => ['Dir_Cod' => $id]
        ]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── MODIFICAR MÓDULO EXTERNO ─────────────────────────────────────────────────
$app->post('/v1/admin/external-modules/modificar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $dirCod = $body['Dir_Cod'] ?? null;
        
        if (!$dirCod) {
            echo json_encode(['status' => false, 'error' => 'Dir_Cod es requerido']);
            return;
        }
        
        $data = [];
        $externalFields = [
            'Dir_Nom', 'Dir_Rut', 'Dir_Des', 'Dir_Ver', 'Dir_Aut',
            'Dir_Ext_Tip', 'Dir_Ext_Cmd', 'Dir_Ext_Args', 'Dir_Ext_Cwd',
            'Dir_Ext_Env', 'Dir_Ext_Port', 'Dir_Ext_Timeout',
            'Dir_Ext_Max_Retries', 'Dir_Ext_Auto_Start'
        ];
        
        foreach ($externalFields as $field) {
            if (isset($body[$field])) {
                $data[$field] = $body[$field];
            }
        }
        
        if (!empty($data)) {
            $api->update('directorio_modulos', $data, 'Dir_Cod', $dirCod);
        }
        
        // Actualizar configuración si se proporcionó
        if (isset($body['config']) && is_array($body['config'])) {
            $runner = new ExternalModuleRunner($body['Bdd']);
            foreach ($body['config'] as $cfg) {
                $runner->saveModuleConfig(
                    $dirCod,
                    $body['Emp_Cod'] ?? 1,
                    $cfg['clave'],
                    $cfg['valor'],
                    $cfg['tipo'] ?? 'string',
                    $cfg['descripcion'] ?? null
                );
            }
        }
        
        echo json_encode(['status' => true, 'message' => 'Módulo externo modificado exitosamente']);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── ELIMINAR MÓDULO EXTERNO ──────────────────────────────────────────────────
$app->post('/v1/admin/external-modules/eliminar', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        $dirCod = $body['Dir_Cod'] ?? null;
        
        if (!$dirCod) {
            echo json_encode(['status' => false, 'error' => 'Dir_Cod es requerido']);
            return;
        }
        
        // Verificar si está corriendo antes de eliminar
        $runner = new ExternalModuleRunner($body['Bdd']);
        $module = $runner->getModuleInfo($dirCod);
        
        if ($module['Dir_Ext_Status'] === 'running') {
            $runner->stopService($dirCod);
        }
        
        $api->update('directorio_modulos', ['Dir_Est' => 'I'], 'Dir_Cod', $dirCod);
        echo json_encode(['status' => true, 'message' => 'Módulo externo eliminado exitosamente']);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── INICIAR SERVICIO EXTERNO ─────────────────────────────────────────────────
$app->post('/v1/admin/external-modules/start', function () use ($app) {
    $body = getBody();
    try {
        $runner = new ExternalModuleRunner($body['Bdd']);
        $result = $runner->startService($body['Dir_Cod'], $body['Emp_Cod']);
        echo json_encode($result);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── DETENER SERVICIO EXTERNO ─────────────────────────────────────────────────
$app->post('/v1/admin/external-modules/stop', function () use ($app) {
    $body = getBody();
    try {
        $runner = new ExternalModuleRunner($body['Bdd']);
        $result = $runner->stopService($body['Dir_Cod']);
        echo json_encode($result);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── EJECUTAR TAREA EXTERNA ───────────────────────────────────────────────────
$app->post('/v1/admin/external-modules/execute', function () use ($app) {
    $body = getBody();
    try {
        $runner = new ExternalModuleRunner($body['Bdd']);
        $params = $body['params'] ?? [];
        $result = $runner->executeTask($body['Dir_Cod'], $params, $body['Emp_Cod']);
        echo json_encode($result);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── OBTENER CONFIGURACIÓN DE MÓDULO ─────────────────────────────────────────
$app->post('/v1/admin/external-modules/config/obtener', function () use ($app) {
    $body = getBody();
    try {
        $runner = new ExternalModuleRunner($body['Bdd']);
        $data = $runner->getModuleConfig($body['Dir_Cod'], $body['Emp_Cod']);
        utf8_encode_deep($data);
        echo json_encode(['status' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── GUARDAR CONFIGURACIÓN DE MÓDULO ──────────────────────────────────────────
$app->post('/v1/admin/external-modules/config/guardar', function () use ($app) {
    $body = getBody();
    try {
        $runner = new ExternalModuleRunner($body['Bdd']);
        $result = $runner->saveModuleConfig(
            $body['Dir_Cod'],
            $body['Emp_Cod'],
            $body['Cfg_Clave'],
            $body['Cfg_Valor'],
            $body['Cfg_Tipo'] ?? 'string',
            $body['Cfg_Descripcion'] ?? null
        );
        echo json_encode(['status' => true, 'message' => 'Configuración guardada exitosamente']);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── OBTENER LOGS DE EJECUCIÓN ────────────────────────────────────────────────
$app->post('/v1/admin/external-modules/logs/obtener', function () use ($app) {
    $body = getBody();
    try {
        $runner = new ExternalModuleRunner($body['Bdd']);
        $data = $runner->getExecutionLogs(
            $body['Dir_Cod'],
            $body['limit'] ?? 50,
            $body['offset'] ?? 0
        );
        utf8_encode_deep($data);
        echo json_encode(['status' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── OBTENER ARCHIVO LOG ──────────────────────────────────────────────────────
$app->post('/v1/admin/external-modules/logs/archivo', function () use ($app) {
    $body = getBody();
    try {
        $runner = new ExternalModuleRunner($body['Bdd']);
        $data = $runner->getLogFile($body['Dir_Cod'], $body['filename'] ?? null);
        if ($data) {
            echo json_encode(['status' => true, 'data' => $data]);
        } else {
            echo json_encode(['status' => false, 'error' => 'Log no encontrado']);
        }
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── LISTAR ARCHIVOS LOG ──────────────────────────────────────────────────────
$app->post('/v1/admin/external-modules/logs/listar', function () use ($app) {
    $body = getBody();
    try {
        $runner = new ExternalModuleRunner($body['Bdd']);
        $data = $runner->listLogFiles($body['Dir_Cod']);
        echo json_encode(['status' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── VERIFICAR DEPENDENCIAS DEL SISTEMA ───────────────────────────────────────
$app->post('/v1/admin/external-modules/dependencies', function () use ($app) {
    try {
        $runner = new ExternalModuleRunner('servicios');
        $data = $runner->checkSystemDependencies();
        echo json_encode(['status' => true, 'data' => $data]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

// ── LISTAR TODOS LOS MÓDULOS/DIRECTORIOS/PROCESOS DEL USUARIO ───────────────
$app->post('/v1/admin/external-modules/listar-todo', function () use ($app) {
    $body = getBody();
    try {
        $api = new DataAPI($body['Bdd']);
        
        // Obtener módulos del directorio
        $modulos = $api->list('directorio_modulos', [
            'Emp_Cod' => $body['Emp_Cod'],
            'Dir_Est' => 'A',
        ], 'Dir_Cod ASC', 500);
        
        // Obtener procesos
        $procesos = $api->list('procesos', [
            'Pcs_Est' => 'A',
        ], 'Pcs_Cod ASC', 500);
        
        // Obtener organigramas
        $organizados = $api->list('organizado', [], 'Org_Cod ASC', 500);
        
        // Obtener rutas
        $rutas = $api->list('rutas', [], 'Rut_Cod ASC', 500);
        
        // Obtener configuración de usuario si se proporciona
        $usuario = null;
        if (isset($body['Usu_Cod'])) {
            $usuario = $api->getById('usuarios', 'Usu_Cod', $body['Usu_Cod']);
        }
        
        // Obtener perfiles del usuario
        $perfiles = [];
        if (isset($body['Usu_Cod'])) {
            $perfiles = $api->query("SELECT p.Per_Cod, p.Per_Nom FROM perfiles p 
                INNER JOIN usuario_perfiles up ON p.Per_Cod = up.Per_Cod 
                WHERE up.Usu_Cod = " . $api->escape($body['Usu_Cod']));
        }
        
        // Obtener accesos por perfil
        $accesos = [];
        if (!empty($perfiles)) {
            $perfilCodigos = array_column($perfiles, 'Per_Cod');
            $accesos = $api->query("SELECT pa.Per_Cod, pa.Pcs_Cod, pa.Pai_Niv 
                FROM perfil_acceso pa 
                WHERE pa.Per_Cod IN (" . implode(',', $perfilCodigos) . ")");
        }
        
        // Agrupar procesos por directorio
        $procesosPorDirectorio = [];
        foreach ($procesos as $proceso) {
            $orgCod = $proceso['Org_Cod'];
            if (!isset($procesosPorDirectorio[$orgCod])) {
                $procesosPorDirectorio[$orgCod] = [];
            }
            $procesosPorDirectorio[$orgCod][] = $proceso;
        }
        
        // Construir respuesta completa
        $resultado = [
            'usuario' => $usuario,
            'perfiles' => $perfiles,
            'modulos' => $modulos,
            'organizados' => $organizados,
            'procesos' => $procesos,
            'rutas' => $rutas,
            'accesos' => $accesos,
            'procesos_por_directorio' => $procesosPorDirectorio,
            'estadisticas' => [
                'total_modulos' => count($modulos),
                'modulos_externos' => count(array_filter($modulos, function($m) {
                    return $m['Dir_Tip'] === 'externo';
                })),
                'modulos_php' => count(array_filter($modulos, function($m) {
                    return $m['Dir_Tip'] === 'modulo';
                })),
                'modulos_api' => count(array_filter($modulos, function($m) {
                    return $m['Dir_Tip'] === 'api';
                })),
                'total_procesos' => count($procesos),
                'total_organizados' => count($organizados),
                'total_rutas' => count($rutas),
            ],
        ];
        
        utf8_encode_deep($resultado);
        echo json_encode(['status' => true, 'data' => $resultado]);
    } catch (\Throwable $e) {
        $app->response->setStatus(500);
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
});

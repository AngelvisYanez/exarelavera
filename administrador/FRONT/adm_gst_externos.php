<?php
/**
 * @abstract Gestión de Módulos Externos (Node.js, Python, Shell)
 * @author Sistema ERP Relavera
 * @version 1.0
 * Permite gestionar módulos externos para automatizaciones y automatizadores
 */
session_start();

ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once('../../DATA/MysqlConexion.php');
require_once('../../DATA/MysqlDatos.php');
require_once('../../classes/DataAPI.php');
require_once('../../classes/ExternalModuleRunner.php');

$Ses_Emp_Cod = $_SESSION['Ses_Emp_Cod'] ?? 1;
$Ses_Usu_Cod = $_SESSION['Ses_Usu_Cod'] ?? 0;
$Ses_Dat_Dis = $_SESSION['Ses_Dat_Dis'] ?? 'servicios';

$hoy = date("Y-m-d");

if (isset($_POST['action'])) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $runner = new ExternalModuleRunner($Ses_Dat_Dis);
        
        switch ($_POST['action']) {
            case 'listar':
                $data = $runner->listExternalModules(null);
                echo json_encode(['status' => true, 'data' => $data]);
                break;
                
            case 'obtener':
                $data = $runner->getModuleInfo($_POST['Dir_Cod']);
                echo json_encode(['status' => true, 'data' => $data]);
                break;
                
            case 'crear':
                $api = new DataAPI($Ses_Dat_Dis);
                $data = [
                    'Dir_Nom' => $_POST['Dir_Nom'] ?? '',
                    'Dir_Rut' => $_POST['Dir_Rut'] ?? '',
                    'Dir_Tip' => 'externo',
                    'Dir_Ext_Tip' => $_POST['Dir_Ext_Tip'] ?? 'node',
                    'Dir_Ext_Cmd' => $_POST['Dir_Ext_Cmd'] ?? '',
                    'Dir_Ext_Args' => $_POST['Dir_Ext_Args'] ?? '[]',
                    'Dir_Ext_Cwd' => $_POST['Dir_Ext_Cwd'] ?? '',
                    'Dir_Ext_Env' => $_POST['Dir_Ext_Env'] ?? '{}',
                    'Dir_Ext_Port' => !empty($_POST['Dir_Ext_Port']) ? (int)$_POST['Dir_Ext_Port'] : null,
                    'Dir_Ext_Status' => 'stopped',
                    'Dir_Ext_Timeout' => (int)($_POST['Dir_Ext_Timeout'] ?? 300),
                    'Dir_Ext_Max_Retries' => (int)($_POST['Dir_Ext_Max_Retries'] ?? 3),
                    'Dir_Ext_Auto_Start' => (int)($_POST['Dir_Ext_Auto_Start'] ?? 0),
                    'Dir_Est' => 'A',
                    'Dir_Des' => $_POST['Dir_Des'] ?? '',
                    'Dir_Ver' => $_POST['Dir_Ver'] ?? '1.0.0',
                    'Dir_Aut' => $_POST['Dir_Aut'] ?? 'S',
                    'Emp_Cod' => $Ses_Emp_Cod,
                ];
                
                $id = $api->insert('directorio_modulos', $data);
                
                // Guardar configuración si se proporcionó
                if (isset($_POST['config']) && !empty($_POST['config'])) {
                    $config = json_decode($_POST['config'], true);
                    if (is_array($config)) {
                        foreach ($config as $cfg) {
                            if (!empty($cfg['clave']) && isset($cfg['valor'])) {
                                $runner->saveModuleConfig(
                                    $id,
                                    $Ses_Emp_Cod,
                                    $cfg['clave'],
                                    $cfg['valor'],
                                    $cfg['tipo'] ?? 'string',
                                    $cfg['descripcion'] ?? null
                                );
                            }
                        }
                    }
                }
                
                echo json_encode(['status' => true, 'message' => 'Módulo creado exitosamente', 'data' => ['Dir_Cod' => $id]]);
                break;
                
            case 'modificar':
                $api = new DataAPI($Ses_Dat_Dis);
                $dirCod = $_POST['Dir_Cod'] ?? null;
                
                if (!$dirCod) {
                    echo json_encode(['status' => false, 'error' => 'Dir_Cod es requerido']);
                    break;
                }
                
                $data = [];
                $fields = [
                    'Dir_Nom', 'Dir_Rut', 'Dir_Des', 'Dir_Ver', 'Dir_Aut',
                    'Dir_Ext_Tip', 'Dir_Ext_Cmd', 'Dir_Ext_Args', 'Dir_Ext_Cwd',
                    'Dir_Ext_Env', 'Dir_Ext_Timeout', 'Dir_Ext_Max_Retries', 'Dir_Ext_Auto_Start'
                ];
                
                // Handle Dir_Ext_Port separately to allow null
                if (isset($_POST['Dir_Ext_Port'])) {
                    $data['Dir_Ext_Port'] = !empty($_POST['Dir_Ext_Port']) ? (int)$_POST['Dir_Ext_Port'] : null;
                }
                
                foreach ($fields as $field) {
                    if (isset($_POST[$field])) {
                        $data[$field] = $_POST[$field];
                    }
                }
                
                if (!empty($data)) {
                    $api->update('directorio_modulos', $data, 'Dir_Cod', $dirCod);
                }
                
                // Actualizar configuración
                if (isset($_POST['config']) && !empty($_POST['config'])) {
                    $config = json_decode($_POST['config'], true);
                    if (is_array($config)) {
                        foreach ($config as $cfg) {
                            if (!empty($cfg['clave']) && isset($cfg['valor'])) {
                                $runner->saveModuleConfig(
                                    $dirCod,
                                    $Ses_Emp_Cod,
                                    $cfg['clave'],
                                    $cfg['valor'],
                                    $cfg['tipo'] ?? 'string',
                                    $cfg['descripcion'] ?? null
                                );
                            }
                        }
                    }
                }
                
                echo json_encode(['status' => true, 'message' => 'Módulo modificado exitosamente']);
                break;
                
            case 'eliminar':
                $api = new DataAPI($Ses_Dat_Dis);
                $dirCod = $_POST['Dir_Cod'] ?? null;
                
                if (!$dirCod) {
                    echo json_encode(['status' => false, 'error' => 'Dir_Cod es requerido']);
                    break;
                }
                
                // Verificar si está corriendo
                $module = $runner->getModuleInfo($dirCod);
                if ($module['Dir_Ext_Status'] === 'running') {
                    $runner->stopService($dirCod);
                }
                
                $api->update('directorio_modulos', ['Dir_Est' => 'I'], 'Dir_Cod', $dirCod);
                echo json_encode(['status' => true, 'message' => 'Módulo eliminado exitosamente']);
                break;
                
            case 'start':
                $result = $runner->startService($_POST['Dir_Cod'], $Ses_Emp_Cod);
                echo json_encode($result);
                break;
                
            case 'stop':
                $result = $runner->stopService($_POST['Dir_Cod']);
                echo json_encode($result);
                break;
                
            case 'execute':
                $params = isset($_POST['params']) ? json_decode($_POST['params'], true) : [];
                $result = $runner->executeTask($_POST['Dir_Cod'], $params, $Ses_Emp_Cod);
                echo json_encode($result);
                break;
                
            case 'config_obtener':
                $data = $runner->getModuleConfig($_POST['Dir_Cod'], $Ses_Emp_Cod);
                echo json_encode(['status' => true, 'data' => $data]);
                break;
                
            case 'config_guardar':
                $result = $runner->saveModuleConfig(
                    $_POST['Dir_Cod'],
                    $Ses_Emp_Cod,
                    $_POST['Cfg_Clave'],
                    $_POST['Cfg_Valor'],
                    $_POST['Cfg_Tipo'] ?? 'string',
                    $_POST['Cfg_Descripcion'] ?? null
                );
                echo json_encode(['status' => true, 'message' => 'Configuración guardada exitosamente']);
                break;
                
            case 'logs_obtener':
                $data = $runner->getExecutionLogs(
                    $_POST['Dir_Cod'],
                    $_POST['limit'] ?? 50,
                    $_POST['offset'] ?? 0
                );
                echo json_encode(['status' => true, 'data' => $data]);
                break;
                
            case 'logs_archivo':
                $data = $runner->getLogFile($_POST['Dir_Cod'], $_POST['filename'] ?? null);
                if ($data) {
                    echo json_encode(['status' => true, 'data' => $data]);
                } else {
                    echo json_encode(['status' => false, 'error' => 'Log no encontrado']);
                }
                break;
                
            case 'dependencies':
                $data = $runner->checkSystemDependencies();
                echo json_encode(['status' => true, 'data' => $data]);
                break;
                
            case 'listar_todo':
                $api = new DataAPI($Ses_Dat_Dis);
                
                $modulos = $api->list('directorio_modulos', [
                    'Emp_Cod' => $Ses_Emp_Cod,
                    'Dir_Est' => 'A',
                ], 'Dir_Cod ASC', 500);
                
                $procesos = $api->list('procesos', [
                    'Pcs_Est' => 'A',
                ], 'Pcs_Cod ASC', 500);
                
                $organizados = $api->list('organizado', [], 'Org_Cod ASC', 500);
                $rutas = $api->list('rutas', [], 'Rut_Cod ASC', 500);
                
                $resultado = [
                    'modulos' => $modulos,
                    'organizados' => $organizados,
                    'procesos' => $procesos,
                    'rutas' => $rutas,
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
                
                echo json_encode(['status' => true, 'data' => $resultado]);
                break;
                
            default:
                echo json_encode(['status' => false, 'error' => 'Acción no válida']);
        }
    } catch (\Throwable $e) {
        echo json_encode(['status' => false, 'error' => $e->getMessage()]);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Módulos Externos</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        .module-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .module-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-running {
            background-color: #5cb85c;
            color: white;
        }
        .status-stopped {
            background-color: #d9534f;
            color: white;
        }
        .status-error {
            background-color: #f0ad4e;
            color: white;
        }
        .module-type-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 5px;
        }
        .type-node {
            background-color: #68A063;
            color: white;
        }
        .type-python {
            background-color: #306998;
            color: white;
        }
        .type-shell {
            background-color: #4EAA25;
            color: white;
        }
        .type-docker {
            background-color: #2496ED;
            color: white;
        }
        .config-panel {
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 5px;
            padding: 15px;
            margin-top: 10px;
        }
        .log-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
        }
        .dependency-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .dependency-item:last-child {
            border-bottom: none;
        }
        .installed {
            color: #5cb85c;
        }
        .not-installed {
            color: #d9534f;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h2><i class="fa fa-cogs"></i> Gestión de Módulos Externos</h2>
                <p class="text-muted">Administrar módulos Node.js, Python y Shell para automatizaciones</p>
                <small class="text-info">Emp_Cod: <?php echo $Ses_Emp_Cod; ?> | DB: <?php echo $Ses_Dat_Dis; ?> | Session status: <?php echo isset($_SESSION['Ses_Emp_Cod']) ? 'set' : 'default'; ?></small>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="row" id="stats-row">
            <div class="col-md-3">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-cube fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge" id="stat-total">0</div>
                                <div>Total Módulos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-check-circle fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge" id="stat-running">0</div>
                                <div>En Ejecución</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-stop-circle fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge" id="stat-stopped">0</div>
                                <div>Detenidos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <div class="row">
                            <div class="col-xs-3">
                                <i class="fa fa-list fa-5x"></i>
                            </div>
                            <div class="col-xs-9 text-right">
                                <div class="huge" id="stat-external">0</div>
                                <div>Externos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Acciones principales -->
        <div class="row">
            <div class="col-md-12">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-primary" onclick="showCreateModal()">
                        <i class="fa fa-plus"></i> Nuevo Módulo
                    </button>
                    <button type="button" class="btn btn-info" onclick="loadModules()">
                        <i class="fa fa-refresh"></i> Actualizar
                    </button>
                    <button type="button" class="btn btn-default" onclick="showDependenciesModal()">
                        <i class="fa fa-check-square"></i> Verificar Dependencias
                    </button>
                    <button type="button" class="btn btn-default" onclick="showListAllModal()">
                        <i class="fa fa-list-alt"></i> Listar Todo
                    </button>
                </div>
            </div>
        </div>

        <hr>

        <!-- Lista de módulos -->
        <div class="row">
            <div class="col-md-12">
                <h3>Módulos Registrados</h3>
                <div id="modules-container">
                    <div class="text-center">
                        <i class="fa fa-spinner fa-spin fa-3x"></i>
                        <p>Cargando módulos...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear/Editar Módulo -->
    <div class="modal fade" id="moduleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title" id="moduleModalTitle">Nuevo Módulo Externo</h4>
                </div>
                <div class="modal-body">
                    <form id="moduleForm">
                        <input type="hidden" id="Dir_Cod" name="Dir_Cod">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombre del Módulo *</label>
                                    <input type="text" class="form-control" id="Dir_Nom" name="Dir_Nom" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Versión</label>
                                    <input type="text" class="form-control" id="Dir_Ver" name="Dir_Ver" value="1.0.0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" id="Dir_Des" name="Dir_Des" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tipo de Ejecutor *</label>
                                    <select class="form-control" id="Dir_Ext_Tip" name="Dir_Ext_Tip" required>
                                        <option value="node">Node.js</option>
                                        <option value="python">Python</option>
                                        <option value="shell">Shell/Bash</option>
                                        <option value="docker">Docker</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Comando *</label>
                                    <input type="text" class="form-control" id="Dir_Ext_Cmd" name="Dir_Ext_Cmd" required placeholder="node, python, bash">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Puerto (si aplica)</label>
                                    <input type="number" class="form-control" id="Dir_Ext_Port" name="Dir_Ext_Port" placeholder="Ej: 3000">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Argumentos (JSON Array)</label>
                            <input type="text" class="form-control" id="Dir_Ext_Args" name="Dir_Ext_Args" 
                                   placeholder='["server.js"]' value='[]'>
                            <small class="text-muted">Usar {variable} para placeholders que se reemplazarán con parámetros</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Directorio de Trabajo</label>
                                    <input type="text" class="form-control" id="Dir_Ext_Cwd" name="Dir_Ext_Cwd" 
                                           placeholder="/ruta/al/modulo">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Timeout (segundos)</label>
                                    <input type="number" class="form-control" id="Dir_Ext_Timeout" name="Dir_Ext_Timeout" value="300">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Max Reintentos</label>
                                    <input type="number" class="form-control" id="Dir_Ext_Max_Retries" name="Dir_Ext_Max_Retries" value="3">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Variables de Entorno (JSON)</label>
                            <textarea class="form-control" id="Dir_Ext_Env" name="Dir_Ext_Env" rows="2" 
                                      placeholder='{"NODE_ENV": "production"}'>{}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Ruta URL</label>
                            <input type="text" class="form-control" id="Dir_Rut" name="Dir_Rut" 
                                   placeholder="/automations/mi-modulo/">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" id="Dir_Ext_Auto_Start" name="Dir_Ext_Auto_Start" value="1">
                                            Iniciar automáticamente
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" id="Dir_Aut" name="Dir_Aut" value="S" checked>
                                            Requiere autenticación
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        <h4>Configuración Adicional</h4>
                        <div id="config-container">
                            <div class="config-row" data-index="0">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" name="config[0][clave]" placeholder="Clave">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="config[0][valor]" placeholder="Valor">
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-control" name="config[0][tipo]">
                                            <option value="string">String</option>
                                            <option value="number">Number</option>
                                            <option value="boolean">Boolean</option>
                                            <option value="json">JSON</option>
                                            <option value="secret">Secret</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" name="config[0][descripcion]" placeholder="Descripción">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm" onclick="removeConfigRow(0)">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success btn-sm" onclick="addConfigRow()">
                            <i class="fa fa-plus"></i> Agregar Configuración
                        </button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveModule()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ver Logs -->
    <div class="modal fade" id="logsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Logs de Ejecución</h4>
                </div>
                <div class="modal-body">
                    <div id="logs-content">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p>Cargando logs...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ver Configuración -->
    <div class="modal fade" id="configModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Configuración del Módulo</h4>
                </div>
                <div class="modal-body">
                    <div id="config-content">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p>Cargando configuración...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Dependencias -->
    <div class="modal fade" id="dependenciesModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Dependencias del Sistema</h4>
                </div>
                <div class="modal-body">
                    <div id="dependencies-content">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p>Verificando dependencias...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Listar Todo -->
    <div class="modal fade" id="listAllModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title">Todos los Módulos, Directorios y Procesos</h4>
                </div>
                <div class="modal-body">
                    <div id="listAll-content">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p>Cargando datos...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-2.1.4.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        let modules = [];
        let editingModule = null;

        $(document).ready(function() {
            loadModules();
        });

        const AJAX_URL = '/administrador/FRONT/adm_gst_externos.php';
        
        function loadModules() {
            $('#modules-container').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Cargando módulos...</p></div>');
            
            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: { action: 'listar' },
                dataType: 'json',
                success: function(response) {
                    console.log('AJAX response:', response);
                    if (response && response.status) {
                        modules = response.data || [];
                        renderModules();
                        updateStats();
                    } else {
                        toastr.error('Error al cargar módulos: ' + (response.error || 'Respuesta inválida'));
                        $('#modules-container').html('<div class="alert alert-danger"><pre>' + JSON.stringify(response, null, 2) + '</pre></div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    console.error('Response text:', xhr.responseText);
                    toastr.error('Error de conexión al cargar módulos: ' + error);
                    $('#modules-container').html('<div class="alert alert-danger"><strong>Error AJAX:</strong> ' + status + ' - ' + error + '<br><pre>' + (xhr.responseText || '').substring(0, 2000) + '</pre></div>');
                }
            });
        }

        function renderModules() {
            const container = $('#modules-container');
            
            if (modules.length === 0) {
                container.html('<div class="alert alert-info"><i class="fa fa-info-circle"></i> No hay módulos externos registrados. Haga clic en "Nuevo Módulo" para crear uno.</div>');
                return;
            }

            let html = '';
            modules.forEach(function(module) {
                const statusClass = module.Dir_Ext_Status === 'running' ? 'status-running' : 
                                   (module.Dir_Ext_Status === 'error' ? 'status-error' : 'status-stopped');
                const statusText = module.Dir_Ext_Status === 'running' ? 'Ejecutando' : 
                                  (module.Dir_Ext_Status === 'error' ? 'Error' : 'Detenido');
                
                const typeClass = 'type-' + (module.Dir_Ext_Tip || 'shell');
                const typeText = (module.Dir_Ext_Tip || 'shell').toUpperCase();
                
                html += `
                <div class="module-card">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 style="margin-top: 0;">
                                ${module.Dir_Nom}
                                <span class="module-type-badge ${typeClass}">${typeText}</span>
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </h4>
                            <p class="text-muted">${module.Dir_Des || 'Sin descripción'}</p>
                            <small>
                                <i class="fa fa-folder"></i> ${module.Dir_Rut || 'Sin ruta'} | 
                                <i class="fa fa-terminal"></i> ${module.Dir_Ext_Cmd || 'Sin comando'} |
                                <i class="fa fa-code"></i> v${module.Dir_Ver || '1.0.0'}
                                ${module.Dir_Ext_Port ? ' | <i class="fa fa-plug"></i> Puerto: ' + module.Dir_Ext_Port : ''}
                            </small>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="btn-group" role="group">
                                ${module.Dir_Ext_Status === 'running' ? 
                                    `<button type="button" class="btn btn-danger btn-sm" onclick="stopModule(${module.Dir_Cod})" title="Detener">
                                        <i class="fa fa-stop"></i>
                                    </button>` :
                                    `<button type="button" class="btn btn-success btn-sm" onclick="startModule(${module.Dir_Cod})" title="Iniciar">
                                        <i class="fa fa-play"></i>
                                    </button>`
                                }
                                <button type="button" class="btn btn-info btn-sm" onclick="executeModule(${module.Dir_Cod})" title="Ejecutar tarea">
                                    <i class="fa fa-bolt"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm" onclick="showLogs(${module.Dir_Cod})" title="Ver logs">
                                    <i class="fa fa-file-text-o"></i>
                                </button>
                                <button type="button" class="btn btn-default btn-sm" onclick="showConfig(${module.Dir_Cod})" title="Ver configuración">
                                    <i class="fa fa-cog"></i>
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" onclick="editModule(${module.Dir_Cod})" title="Editar">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="deleteModule(${module.Dir_Cod})" title="Eliminar">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;
            });

            container.html(html);
        }

        function updateStats() {
            const running = modules.filter(m => m.Dir_Ext_Status === 'running').length;
            const stopped = modules.filter(m => m.Dir_Ext_Status !== 'running').length;
            const external = modules.filter(m => m.Dir_Tip === 'externo').length;

            $('#stat-total').text(modules.length);
            $('#stat-running').text(running);
            $('#stat-stopped').text(stopped);
            $('#stat-external').text(external);
        }

        function showCreateModal() {
            editingModule = null;
            $('#moduleModalTitle').text('Nuevo Módulo Externo');
            $('#moduleForm')[0].reset();
            $('#Dir_Cod').val('');
            $('#config-container').html('');
            addConfigRow();
            $('#moduleModal').modal('show');
        }

        function editModule(dirCod) {
            const module = modules.find(m => m.Dir_Cod == dirCod);
            if (!module) {
                toastr.error('Módulo no encontrado');
                return;
            }

            editingModule = module;
            $('#moduleModalTitle').text('Editar Módulo: ' + module.Dir_Nom);
            
            $('#Dir_Cod').val(module.Dir_Cod);
            $('#Dir_Nom').val(module.Dir_Nom);
            $('#Dir_Ver').val(module.Dir_Ver);
            $('#Dir_Des').val(module.Dir_Des);
            $('#Dir_Ext_Tip').val(module.Dir_Ext_Tip);
            $('#Dir_Ext_Cmd').val(module.Dir_Ext_Cmd);
            $('#Dir_Ext_Args').val(module.Dir_Ext_Args);
            $('#Dir_Ext_Cwd').val(module.Dir_Ext_Cwd);
            $('#Dir_Ext_Env').val(module.Dir_Ext_Env);
            $('#Dir_Ext_Port').val(module.Dir_Ext_Port);
            $('#Dir_Ext_Timeout').val(module.Dir_Ext_Timeout);
            $('#Dir_Ext_Max_Retries').val(module.Dir_Ext_Max_Retries);
            $('#Dir_Ext_Auto_Start').prop('checked', module.Dir_Ext_Auto_Start == 1);
            $('#Dir_Aut').prop('checked', module.Dir_Aut === 'S');
            $('#Dir_Rut').val(module.Dir_Rut);

            // Cargar configuración
            $.post(AJAX_URL, { action: 'config_obtener', Dir_Cod: dirCod }, function(response) {
                if (response.status && response.data) {
                    renderConfigForm(response.data);
                }
            }, 'json');

            $('#moduleModal').modal('show');
        }

        function renderConfigForm(configData) {
            let html = '';
            configData.forEach(function(cfg, index) {
                html += `
                <div class="config-row" data-index="${index}">
                    <div class="row" style="margin-bottom: 10px;">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="config[${index}][clave]" 
                                   value="${cfg.Cfg_Clave}" placeholder="Clave">
                        </div>
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="config[${index}][valor]" 
                                   value="${cfg.Cfg_Valor}" placeholder="Valor">
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" name="config[${index}][tipo]">
                                <option value="string" ${cfg.Cfg_Tipo === 'string' ? 'selected' : ''}>String</option>
                                <option value="number" ${cfg.Cfg_Tipo === 'number' ? 'selected' : ''}>Number</option>
                                <option value="boolean" ${cfg.Cfg_Tipo === 'boolean' ? 'selected' : ''}>Boolean</option>
                                <option value="json" ${cfg.Cfg_Tipo === 'json' ? 'selected' : ''}>JSON</option>
                                <option value="secret" ${cfg.Cfg_Tipo === 'secret' ? 'selected' : ''}>Secret</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" name="config[${index}][descripcion]" 
                                   value="${cfg.Cfg_Descripcion || ''}" placeholder="Descripción">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeConfigRow(${index})">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
            });

            if (html) {
                $('#config-container').html(html);
            } else {
                $('#config-container').html('');
                addConfigRow();
            }
        }

        function addConfigRow() {
            const index = $('#config-container .config-row').length;
            const html = `
            <div class="config-row" data-index="${index}">
                <div class="row" style="margin-bottom: 10px;">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="config[${index}][clave]" placeholder="Clave">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="config[${index}][valor]" placeholder="Valor">
                    </div>
                    <div class="col-md-2">
                        <select class="form-control" name="config[${index}][tipo]">
                            <option value="string">String</option>
                            <option value="number">Number</option>
                            <option value="boolean">Boolean</option>
                            <option value="json">JSON</option>
                            <option value="secret">Secret</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="config[${index}][descripcion]" placeholder="Descripción">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeConfigRow(${index})">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>`;
            
            $('#config-container').append(html);
        }

        function removeConfigRow(index) {
            $(`.config-row[data-index="${index}"]`).remove();
        }

        function saveModule() {
            const formData = new FormData($('#moduleForm')[0]);
            
            // Recopilar configuración
            const config = [];
            $('#config-container .config-row').each(function() {
                const index = $(this).data('index');
                const clave = $(`input[name="config[${index}][clave]"]`).val();
                const valor = $(`input[name="config[${index}][valor]"]`).val();
                const tipo = $(`select[name="config[${index}][tipo]"]`).val();
                const descripcion = $(`input[name="config[${index}][descripcion]"]`).val();
                
                if (clave && valor) {
                    config.push({ clave, valor, tipo, descripcion });
                }
            });
            
            formData.set('config', JSON.stringify(config));
            formData.set('action', $('#Dir_Cod').val() ? 'modificar' : 'crear');

            $.ajax({
                url: AJAX_URL,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status) {
                        toastr.success(response.message);
                        $('#moduleModal').modal('hide');
                        loadModules();
                    } else {
                        toastr.error('Error: ' + response.error);
                    }
                },
                error: function() {
                    toastr.error('Error de conexión al guardar');
                }
            });
        }

        function deleteModule(dirCod) {
            if (!confirm('¿Está seguro de eliminar este módulo?')) {
                return;
            }

            $.post(AJAX_URL, { action: 'eliminar', Dir_Cod: dirCod }, function(response) {
                if (response.status) {
                    toastr.success(response.message);
                    loadModules();
                } else {
                    toastr.error('Error: ' + response.error);
                }
            }, 'json').fail(function() {
                toastr.error('Error de conexión al eliminar');
            });
        }

        function startModule(dirCod) {
            $.post(AJAX_URL, { action: 'start', Dir_Cod: dirCod }, function(response) {
                if (response.status) {
                    toastr.success(response.message);
                    loadModules();
                } else {
                    toastr.error('Error: ' + response.error);
                }
            }, 'json').fail(function() {
                toastr.error('Error de conexión al iniciar');
            });
        }

        function stopModule(dirCod) {
            $.post(AJAX_URL, { action: 'stop', Dir_Cod: dirCod }, function(response) {
                if (response.status) {
                    toastr.success(response.message);
                    loadModules();
                } else {
                    toastr.error('Error: ' + response.error);
                }
            }, 'json').fail(function() {
                toastr.error('Error de conexión al detener');
            });
        }

        function executeModule(dirCod) {
            const params = prompt('Ingrese parámetros JSON (deje vacío si no aplica):');
            
            $.post(AJAX_URL, { 
                action: 'execute', 
                Dir_Cod: dirCod,
                params: params || '[]'
            }, function(response) {
                if (response.status) {
                    toastr.success('Tarea ejecutada en ' + response.duration + ' segundos');
                    if (response.output) {
                        console.log('Salida:', response.output);
                    }
                } else {
                    toastr.error('Error en ejecución: ' + (response.error || 'Código de retorno: ' + response.return_code));
                }
            }, 'json').fail(function() {
                toastr.error('Error de conexión al ejecutar');
            });
        }

        function showLogs(dirCod) {
            $('#logs-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Cargando logs...</p></div>');
            $('#logsModal').modal('show');

            $.post(AJAX_URL, { action: 'logs_obtener', Dir_Cod: dirCod, limit: 20 }, function(response) {
                if (response.status && response.data) {
                    let html = '<table class="table table-striped"><thead><tr><th>Fecha</th><th>Estado</th><th>Duración</th><th>Acciones</th></tr></thead><tbody>';
                    
                    response.data.forEach(function(log) {
                        const statusClass = log.Log_Estado === 'completed' ? 'success' : 
                                           (log.Log_Estado === 'failed' ? 'danger' : 'warning');
                        html += `
                        <tr>
                            <td>${log.Log_Fecha_Inicio}</td>
                            <td><span class="label label-${statusClass}">${log.Log_Estado}</span></td>
                            <td>${log.Log_Duracion_Seg ? log.Log_Duracion_Seg + 's' : '-'}</td>
                            <td><button class="btn btn-xs btn-info" onclick="viewLogDetail('${log.Log_Cod}')"><i class="fa fa-eye"></i></button></td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    
                    if (response.data.length === 0) {
                        html = '<div class="alert alert-info">No hay logs de ejecución disponibles.</div>';
                    }
                    
                    $('#logs-content').html(html);
                } else {
                    $('#logs-content').html('<div class="alert alert-warning">No se pudieron cargar los logs.</div>');
                }
            }, 'json').fail(function() {
                $('#logs-content').html('<div class="alert alert-danger">Error de conexión al cargar logs.</div>');
            });
        }

        function showConfig(dirCod) {
            $('#config-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Cargando configuración...</p></div>');
            $('#configModal').modal('show');

            $.post(AJAX_URL, { action: 'config_obtener', Dir_Cod: dirCod }, function(response) {
                if (response.status && response.data) {
                    let html = '<table class="table table-striped"><thead><tr><th>Clave</th><th>Valor</th><th>Tipo</th><th>Descripción</th></tr></thead><tbody>';
                    
                    response.data.forEach(function(cfg) {
                        const displayValue = cfg.Cfg_Tipo === 'secret' ? '••••••••' : cfg.Cfg_Valor;
                        html += `
                        <tr>
                            <td><strong>${cfg.Cfg_Clave}</strong></td>
                            <td>${displayValue}</td>
                            <td><span class="label label-default">${cfg.Cfg_Tipo}</span></td>
                            <td>${cfg.Cfg_Descripcion || '-'}</td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    
                    if (response.data.length === 0) {
                        html = '<div class="alert alert-info">No hay configuración adicional para este módulo.</div>';
                    }
                    
                    $('#config-content').html(html);
                } else {
                    $('#config-content').html('<div class="alert alert-warning">No se pudo cargar la configuración.</div>');
                }
            }, 'json').fail(function() {
                $('#config-content').html('<div class="alert alert-danger">Error de conexión al cargar configuración.</div>');
            });
        }

        function showDependenciesModal() {
            $('#dependencies-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Verificando dependencias del sistema...</p></div>');
            $('#dependenciesModal').modal('show');

            $.post(AJAX_URL, { action: 'dependencies' }, function(response) {
                if (response.status && response.data) {
                    let html = '<div class="list-group">';
                    
                    for (const [key, dep] of Object.entries(response.data)) {
                        const iconClass = dep.installed ? 'fa-check-circle installed' : 'fa-times-circle not-installed';
                        const statusText = dep.installed ? dep.version : 'No instalado';
                        
                        html += `
                        <div class="list-group-item dependency-item">
                            <div class="row">
                                <div class="col-md-4">
                                    <h5 class="list-group-item-heading">${dep.name}</h5>
                                </div>
                                <div class="col-md-6">
                                    <p class="list-group-item-text">${statusText}</p>
                                </div>
                                <div class="col-md-2 text-right">
                                    <i class="fa ${iconClass} fa-lg"></i>
                                </div>
                            </div>
                        </div>`;
                    }
                    
                    html += '</div>';
                    $('#dependencies-content').html(html);
                } else {
                    $('#dependencies-content').html('<div class="alert alert-warning">No se pudieron verificar las dependencias.</div>');
                }
            }, 'json').fail(function() {
                $('#dependencies-content').html('<div class="alert alert-danger">Error de conexión al verificar dependencias.</div>');
            });
        }

        function showListAllModal() {
            $('#listAll-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Cargando todos los módulos, directorios y procesos...</p></div>');
            $('#listAllModal').modal('show');

            $.post(AJAX_URL, { action: 'listar_todo' }, function(response) {
                if (response.status && response.data) {
                    const data = response.data;
                    
                    let html = `
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Estadísticas Generales</h4>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="panel panel-primary">
                                        <div class="panel-heading">Total Módulos</div>
                                        <div class="panel-body text-center"><h3>${data.estadisticas.total_modulos}</h3></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel panel-success">
                                        <div class="panel-heading">Módulos PHP</div>
                                        <div class="panel-body text-center"><h3>${data.estadisticas.modulos_php}</h3></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel panel-info">
                                        <div class="panel-heading">Módulos Externos</div>
                                        <div class="panel-body text-center"><h3>${data.estadisticas.modulos_externos}</h3></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel panel-warning">
                                        <div class="panel-heading">APIs</div>
                                        <div class="panel-body text-center"><h3>${data.estadisticas.modulos_api}</h3></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="panel panel-default">
                                        <div class="panel-heading">Procesos</div>
                                        <div class="panel-body text-center"><h3>${data.estadisticas.total_procesos}</h3></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="panel panel-default">
                                        <div class="panel-heading">Organizados</div>
                                        <div class="panel-body text-center"><h3>${data.estadisticas.total_organizados}</h3></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="panel panel-default">
                                        <div class="panel-heading">Rutas</div>
                                        <div class="panel-body text-center"><h3>${data.estadisticas.total_rutas}</h3></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Todos los Módulos</h4>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Ruta</th>
                                            <th>Estado</th>
                                            <th>Versión</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                    
                    data.modulos.forEach(function(mod) {
                        const tipoBadge = mod.Dir_Tip === 'externo' ? 'label-info' : 
                                         (mod.Dir_Tip === 'api' ? 'label-warning' : 'label-success');
                        html += `
                        <tr>
                            <td>${mod.Dir_Cod}</td>
                            <td>${mod.Dir_Nom}</td>
                            <td><span class="label ${tipoBadge}">${mod.Dir_Tip}</span></td>
                            <td><small>${mod.Dir_Rut || '-'}</small></td>
                            <td>${mod.Dir_Est === 'A' ? '<span class="label label-success">Activo</span>' : '<span class="label label-danger">Inactivo</span>'}</td>
                            <td>${mod.Dir_Ver || '-'}</td>
                        </tr>`;
                    });
                    
                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Directorios (Organizados)</h4>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Descripción</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                    
                    data.organizados.forEach(function(org) {
                        html += `
                        <tr>
                            <td>${org.Org_Cod}</td>
                            <td>${org.Org_Des || '-'}</td>
                            <td>${org.Org_Mod === 'A' ? '<span class="label label-success">Activo</span>' : '<span class="label label-danger">Inactivo</span>'}</td>
                        </tr>`;
                    });
                    
                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h4>Procesos</h4>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>`;
                    
                    data.procesos.forEach(function(pcs) {
                        const tipoText = pcs.Pcs_Tip === 'P' ? 'Proceso' : 
                                        (pcs.Pcs_Tip === 'R' ? 'Reporte' : pcs.Pcs_Tip);
                        html += `
                        <tr>
                            <td>${pcs.Pcs_Cod}</td>
                            <td>${pcs.Pcs_Lin || pcs.Pcs_Nom || '-'}</td>
                            <td>${tipoText}</td>
                            <td>${pcs.Pcs_Est === 'A' ? '<span class="label label-success">Activo</span>' : '<span class="label label-danger">Inactivo</span>'}</td>
                        </tr>`;
                    });
                    
                    html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>`;
                    
                    $('#listAll-content').html(html);
                } else {
                    $('#listAll-content').html('<div class="alert alert-warning">No se pudieron cargar los datos.</div>');
                }
            }, 'json').fail(function() {
                $('#listAll-content').html('<div class="alert alert-danger">Error de conexión al cargar datos.</div>');
            });
        }

        function viewLogDetail(logId) {
            // TODO: Implementar vista detallada de log
            toastr.info('Vista detallada de log en desarrollo');
        }
    </script>
</body>
</html>

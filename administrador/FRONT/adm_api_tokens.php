<?php
/**
 * Módulo: Gestión y Habilitación de API Tokens
 * Área: Administración / Seguridad & Accesos
 *
 * @package administrador.FRONT
 * @author EXA Contable
 * @version 2.0
 *
 * Requisito de Seguridad: Requiere sesión activa de usuario en el sistema ERP legacy.
 */
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Redirección obligatoria al login si no hay sesión activa
if (empty($_SESSION['Ses_Usu_Cod'])) {
    header("Location: ../../index.php");
    exit();
}

require_once('../../Librerias/config.php/register_globals.php');
require_once('../LOGICA/seguridad.php');
require_once('../LOGICA/adm_log_api_tokens.php');

$sesUsuCod = (int)$_SESSION['Ses_Usu_Cod'];
$sesEmpCod = !empty($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
$sesEmpNom = !empty($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : (!empty($_SESSION['Ses_Emp_Cor']) ? $_SESSION['Ses_Emp_Cor'] : '');
$sesUsuNom = !empty($_SESSION['Ses_Usu_Nom']) ? $_SESSION['Ses_Usu_Nom'] : 'Usuario #' . $sesUsuCod;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EXA Contable - Administración de Tokens API</title>
    <!-- Bootstrap 3 & FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --exa-vinotinto: #801326;
            --exa-vinotinto-dark: #581825;
            --exa-gold: #c5a059;
            --exa-bg: #f4f6f9;
        }
        body {
            background-color: var(--exa-bg);
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #333;
            padding: 20px 15px;
        }
        .page-header-box {
            background: linear-gradient(135deg, var(--exa-vinotinto-dark) 0%, var(--exa-vinotinto) 100%);
            color: #fff;
            padding: 20px 24px;
            border-radius: 8px;
            margin-bottom: 22px;
            box-shadow: 0 4px 15px rgba(128, 19, 38, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-title h2 {
            margin: 0 0 5px 0;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-breadcrumb {
            font-size: 12px;
            opacity: 0.85;
            margin: 0;
        }
        .page-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-exa-gold {
            background: linear-gradient(135deg, #dfb76c 0%, #c5a059 100%);
            color: #fff;
            border: none;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            transition: all 0.2s ease;
        }
        .btn-exa-gold:hover {
            background: #b59049;
            color: #fff;
            transform: translateY(-1px);
        }
        .btn-exa-outline {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.4);
            color: #fff;
            font-weight: 500;
        }
        .btn-exa-outline:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #fff;
        }
        .card-box {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 20px;
            margin-bottom: 20px;
        }
        .filter-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f1f3f5;
        }
        .search-input-group {
            position: relative;
            max-width: 380px;
            width: 100%;
        }
        .search-input-group .fa-search {
            position: absolute;
            left: 12px;
            top: 10px;
            color: #adb5bd;
        }
        .search-input-group input {
            padding-left: 36px;
            border-radius: 6px;
        }
        .table-custom {
            margin-bottom: 0;
        }
        .table-custom th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 2px solid #dee2e6;
            vertical-align: middle;
        }
        .table-custom td {
            vertical-align: middle;
            font-size: 13px;
        }
        .codebox {
            background: #1e1e1e;
            color: #4ec9b0;
            padding: 12px 14px;
            border-radius: 6px;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 13px;
            word-break: break-all;
            margin: 10px 0;
            border-left: 4px solid var(--exa-vinotinto);
        }
        .modal-header-exa {
            background: linear-gradient(135deg, var(--exa-vinotinto-dark) 0%, var(--exa-vinotinto) 100%);
            color: #fff;
            border-radius: 5px 5px 0 0;
            padding: 15px 20px;
        }
        .modal-header-exa .close {
            color: #fff;
            opacity: 0.8;
            text-shadow: none;
        }
        .modal-header-exa .close:hover {
            opacity: 1;
        }
        .modal-header-exa h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        .user-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.15);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
        }
    </style>
</head>
<body>

<div class="container-fluid">

    <!-- Encabezado Principal del Módulo -->
    <div class="page-header-box">
        <div class="page-title">
            <h2><i class="fa fa-key"></i> Gestión de API Tokens & Accesos REST</h2>
            <div class="page-breadcrumb">
                <span>Inicio</span> &rsaquo; <span>Administración</span> &rsaquo; <span>Seguridad</span> &rsaquo; <strong>Tokens de Integración</strong>
            </div>
        </div>
        <div class="page-actions">
            <span class="user-status-pill">
                <i class="fa fa-user-circle"></i> <?= htmlspecialchars($sesUsuNom) ?> | <?= htmlspecialchars($sesEmpNom ?: '#' . $sesEmpCod) ?>
            </span>
            <a href="/v1/docs" target="_blank" class="btn btn-sm btn-exa-outline" title="Ver Documentación Swagger">
                <i class="fa fa-book"></i> Swagger Docs
            </a>
            <a href="/v1/api-tokens-probar" target="_blank" class="btn btn-sm btn-exa-outline" title="Probar Token en Sandbox">
                <i class="fa fa-flask"></i> Probar Token
            </a>
            <button class="btn btn-sm btn-exa-gold" id="btn-nuevo-token">
                <i class="fa fa-plus-circle"></i> Generar Nuevo Token
            </button>
        </div>
    </div>

    <!-- Contenedor Principal de la Lista -->
    <div class="card-box">
        <div class="filter-toolbar">
            <div class="search-input-group">
                <i class="fa fa-search"></i>
                <input type="text" id="buscar-token" class="form-control input-sm" placeholder="Buscar por nombre, empresa o base de datos...">
            </div>
            <div style="display:flex;gap:10px;align-items:center;">
                <label style="font-size:12px;margin:0;font-weight:normal;color:#666;">Estado:</label>
                <select id="filtro-estado" class="form-control input-sm" style="width:130px;">
                    <option value="ALL">Todos</option>
                    <option value="A" selected>Solo Activos</option>
                    <option value="I">Solo Inactivos</option>
                </select>
                <button class="btn btn-sm btn-default" id="btn-recargar-tokens" title="Refrescar lista">
                    <i class="fa fa-refresh"></i>
                </button>
            </div>
        </div>

        <div id="tabla-tokens-loading" class="text-center" style="padding:40px;display:none;">
            <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
            <p class="text-muted" style="margin-top:10px;">Cargando tokens de acceso...</p>
        </div>

        <div id="lista-vacia" class="alert alert-info" style="display:none;margin-bottom:0;">
            <i class="fa fa-info-circle"></i> No se encontraron tokens registrados con los filtros seleccionados. Haz clic en <b>"Generar Nuevo Token"</b> para crear uno.
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-custom" id="tabla-tokens">
                <thead>
                    <tr>
                        <th style="min-width:200px;">Token & Resumen</th>
                        <th style="min-width:180px;">Empresa / Base de Datos</th>
                        <th>Consumo / Cuota</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th class="text-right" style="width:160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tabla-tokens-body"></tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL: GENERAR NUEVO TOKEN -->
<div class="modal fade" id="modal-nuevo-token" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-exa">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-plus-circle"></i> Generar Nuevo Token de Acceso</h4>
            </div>
            <div class="modal-body" style="padding:20px 24px;">
                <form id="form-nuevo-token" onsubmit="return false;">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><i class="fa fa-tag"></i> Nombre o Identificador del Token *</label>
                                <input type="text" id="tok_nombre" class="form-control" placeholder="Ej: Integración ERP Locator / Choferes & Plantas" required>
                                <small class="help-block text-muted">Nombre descriptivo de la aplicación o integración cliente.</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><i class="fa fa-building"></i> Empresa Destino *</label>
                                <select id="tok_empresa" class="form-control" required></select>
                                <small class="help-block text-muted">Determina la base de datos a la que conectará el token.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label><i class="fa fa-tachometer"></i> Límite de Consultas (Cuota)</label>
                                <input type="number" id="tok_cuota" class="form-control" value="0" min="0">
                                <small class="help-block text-muted">0 = Consultas ilimitadas.</small>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label><i class="fa fa-calendar-check-o"></i> Periodo de Cuota</label>
                                <select id="tok_periodo" class="form-control">
                                    <option value="D">Por Día (Reinicio diario a las 00:00)</option>
                                    <option value="M">Por Mes (Reinicio mensual el día 1)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label><i class="fa fa-hourglass-end"></i> Fecha de Expiración</label>
                                <input type="date" id="tok_expira" class="form-control">
                                <small class="help-block text-muted">Dejar vacío si no expira nunca.</small>
                            </div>
                        </div>
                    </div>

                    <hr style="margin:12px 0 16px 0;">

                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:10px;">
                            <label style="margin:0;font-size:14px;color:var(--exa-vinotinto);">
                                <i class="fa fa-shield"></i> Permisos de Acceso por Módulo y Endpoint
                            </label>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <input type="text" id="buscar-permiso-nuevo" class="form-control input-sm" placeholder="Filtrar permiso..." style="width:180px;">
                                <button type="button" class="btn btn-xs btn-default" id="btn-marcar-todos-nuevo">Marcar todos</button>
                                <button type="button" class="btn btn-xs btn-default" id="btn-desmarcar-todos-nuevo">Desmarcar</button>
                            </div>
                        </div>
                        <div id="contenedor-permisos-nuevo" style="max-height:300px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;padding:12px;background:#fdfdfe;"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-exa-gold" id="btn-guardar-nuevo">
                    <i class="fa fa-check"></i> Generar Token
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: TOKEN GENERADO EXITOSAMENTE -->
<div class="modal fade" id="modal-token-generado" tabindex="-1" role="dialog" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-exa">
                <h4 class="modal-title"><i class="fa fa-key"></i> Token Creado Exitosamente</h4>
            </div>
            <div class="modal-body" style="padding:22px;">
                <div class="alert alert-warning" style="margin-bottom:15px;">
                    <i class="fa fa-exclamation-triangle"></i> <strong>¡Atención!</strong> Por razones de seguridad criptográfica, este token solo se mostrará <b>una sola vez</b>. Cópialo y guárdalo en tu bóveda segura o archivo de configuración.
                </div>
                <label>Token Bearer para: <b id="modal-token-generado-nombre"></b></label>
                <div class="codebox" id="modal-token-generado-valor"></div>
                <p style="font-size:12px;color:#666;margin-top:10px;">
                    <strong>Cómo consumirlo:</strong> Envía en cada solicitud HTTP el encabezado:<br>
                    <code style="color:#c7254e;">Authorization: Bearer &lt;tu_token&gt;</code>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="btn-copiar-token">
                    <i class="fa fa-copy"></i> Copiar al Portapapeles
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: EDITAR PERMISOS DE UN TOKEN -->
<div class="modal fade" id="modal-permisos" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header modal-header-exa">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-shield"></i> Permisos de Acceso: <span id="modal-permisos-nombre"></span></h4>
            </div>
            <div class="modal-body" style="padding:20px 24px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:10px;">
                    <span class="text-muted" style="font-size:13px;">Marca las rutas y módulos autorizados para este token:</span>
                    <div style="display:flex;gap:8px;align-items:center;">
                        <input type="text" id="buscar-permiso-editar" class="form-control input-sm" placeholder="Filtrar permiso..." style="width:180px;">
                        <button type="button" class="btn btn-xs btn-default" id="btn-marcar-todos-editar">Marcar todos</button>
                        <button type="button" class="btn btn-xs btn-default" id="btn-desmarcar-todos-editar">Desmarcar</button>
                    </div>
                </div>
                <div id="modal-permisos-loading" class="text-center" style="padding:30px;display:none;">
                    <i class="fa fa-spinner fa-spin fa-2x text-muted"></i>
                </div>
                <div id="contenedor-permisos-editar" style="max-height:360px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;padding:12px;background:#fdfdfe;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-exa-gold" id="btn-guardar-permisos">
                    <i class="fa fa-save"></i> Guardar Permisos
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts requeridos -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js"></script>
<script>
    var SES_EMP_COD = <?= (int)$sesEmpCod ?>;
</script>
<script src="../VALIDACIONES/adm_val_api_tokens.js"></script>
</body>
</html>

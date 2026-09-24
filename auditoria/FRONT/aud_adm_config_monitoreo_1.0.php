<?php
/**
 * Configuracion de monitoreo - modulos y procesos a registrar.
 * @package auditoria.FRONT
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/aud_log_config_monitoreo.php');
require_once('../LOGICA/aud_log_interpretar.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion_CfgMon($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_CfgMon();
$audEmpCod = isset($Ses_Emp_Cod) ? (int)$Ses_Emp_Cod : 0;
$audUsuCod = isset($Ses_Usu_Cod) ? (int)$Ses_Usu_Cod : 0;

require_once('../LOGICA/aud_log_acceso_directorio.php');
aud_acceso_directorio_gate($audEmpCod);
require_once('../LOGICA/aud_log_notificaciones.php');

aud_cfg_ensure_schema($obBD_conexion);
aud_notif_ensure_schema($obBD_conexion);
aud_acc_ensure_schema(aud_notif_con($obBD_conexion));

/** JSON helper */
if (!function_exists('aud_cfg_json')) {
	function aud_cfg_to_utf8(&$input) {
		if (is_string($input)) {
			if ($input === '') return;
			if (function_exists('mb_check_encoding') && @mb_check_encoding($input, 'UTF-8')) return;
			if (function_exists('mb_convert_encoding')) {
				$input = @mb_convert_encoding($input, 'UTF-8', 'ISO-8859-1');
			} elseif (function_exists('utf8_encode')) {
				$input = @utf8_encode($input);
			}
			return;
		}
		if (is_array($input)) {
			foreach ($input as $k => $v) {
				aud_cfg_to_utf8($input[$k]);
			}
		}
	}
	function aud_cfg_json($data) {
		@ini_set('display_errors', '0');
		aud_cfg_to_utf8($data);
		$json = json_encode($data);
		echo ($json !== false) ? $json : '{"success":false,"rows":[]}';
	}
}

/** Cargar arbol + config */
if (isset($_REQUEST['listConfigAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$rowAdmin = $obBD_con1->getRowConsulta(13, array($audUsuCod), $obBD_conexion);
	$audEsAdmin = !empty($rowAdmin['is_admin']);
	// Administrador ve todo el arbol; un rol delegado solo lo autorizado en perfiorgan
	$Arr_Tree = $audEsAdmin
		? $obBD_con1->getArrayConsulta(7, array(), $obBD_conexion)
		: $obBD_con1->getArrayConsulta(14, array($audUsuCod, 0), $obBD_conexion);
	$Arr_Cfg = $obBD_con1->getArrayConsulta(3, array($audEmpCod), $obBD_conexion);
	if (!is_array($Arr_Tree)) $Arr_Tree = array();
	if (!is_array($Arr_Cfg)) $Arr_Cfg = array();

	$mods = aud_cfg_armar_arbol($Arr_Tree);
	$marks = aud_cfg_marcar_seleccion($mods, $Arr_Cfg);

	aud_cfg_json(array(
		'success' => true,
		'modules' => $mods,
		'selected' => $marks['selected'],
		'modFull' => $marks['modFull'],
		'dirFull' => $marks['dirFull'],
		'hasConfig' => count($Arr_Cfg) > 0,
		'esAdmin' => $audEsAdmin
	));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Cargar roles/perfiles de la empresa (filtro de validacion) */
if (isset($_REQUEST['listRolesAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$arrRoles = $obBD_con1->getArrayConsulta(9, array($audEmpCod), $obBD_conexion);
	if (!is_array($arrRoles)) $arrRoles = array();
	aud_cfg_json(array('success' => true, 'rows' => $arrRoles));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Cargar usuarios de la empresa (filtro de validacion) */
if (isset($_REQUEST['listUsuariosAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$arrUsuarios = $obBD_con1->getArrayConsulta(10, array($audEmpCod), $obBD_conexion);
	if (!is_array($arrUsuarios)) $arrUsuarios = array();
	aud_cfg_json(array('success' => true, 'rows' => $arrUsuarios));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Obtener procesos asignados a un Rol o Usuario (filtro de validacion) */
if (isset($_REQUEST['listProcesosFiltroAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$rolCod = isset($_REQUEST['rol']) ? (int)$_REQUEST['rol'] : 0;
	$usuFiltro = isset($_REQUEST['usu']) ? trim((string)$_REQUEST['usu']) : '';
	$setPcs = array();
	if ($rolCod > 0) {
		$arrPcs = $obBD_con1->getArrayConsulta(11, array($rolCod), $obBD_conexion);
		if (is_array($arrPcs)) {
			foreach ($arrPcs as $rowP) {
				if (!empty($rowP['Pcs_Cod'])) {
					$setPcs[(int)$rowP['Pcs_Cod']] = true;
				}
			}
		}
	} elseif ($usuFiltro !== '') {
		foreach (explode(',', $usuFiltro) as $ux) {
			$ux = (int)$ux;
			if ($ux <= 0) {
				continue;
			}
			$arrPcs = $obBD_con1->getArrayConsulta(12, array($ux), $obBD_conexion);
			if (is_array($arrPcs)) {
				foreach ($arrPcs as $rowP) {
					if (!empty($rowP['Pcs_Cod'])) {
						$setPcs[(int)$rowP['Pcs_Cod']] = true;
					}
				}
			}
		}
	}
	$pcsCodes = array_keys($setPcs);
	aud_cfg_json(array('success' => true, 'pcs' => $pcsCodes, 'total' => count($pcsCodes)));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Guardar config */
if (isset($_REQUEST['saveConfigAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$raw = isset($_POST['items']) ? $_POST['items'] : (isset($_REQUEST['items']) ? $_REQUEST['items'] : '[]');
	// Solo el Administrador de Sistemas puede modificar las reglas (validacion en servidor)
	if (!aud_cfg_es_admin_sistemas($obBD_con1, $obBD_conexion, $audUsuCod)) {
		aud_cfg_json(array(
			'success' => false,
			'saved' => 0,
			'message' => 'Acceso denegado: Solamente el Administrador de Sistemas puede modificar las reglas de monitoreo.'
		));
		$obBD_con1->liberar();
		$obBD_conexion->cerrar();
		exit();
	}
	$parsed = aud_cfg_parse_items($raw);
	if (empty($parsed['ok'])) {
		aud_cfg_json(array(
			'success' => false,
			'saved' => 0,
			'message' => isset($parsed['message']) ? $parsed['message'] : 'No se pudo leer la seleccion.'
		));
		$obBD_con1->liberar();
		$obBD_conexion->cerrar();
		exit();
	}
	$resp = aud_cfg_guardar($obBD_con1, $obBD_conexion, $audEmpCod, $audUsuCod, $parsed['items']);
	aud_cfg_json($resp);
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Verifica permiso de Administrador de Sistemas para las acciones sensibles siguientes */
if (!function_exists('aud_cfg_requerir_admin_ajax')) {
	function aud_cfg_requerir_admin_ajax($obBD_con1, $obBD_conexion, $usuCod)
	{
		if (!aud_cfg_es_admin_sistemas($obBD_con1, $obBD_conexion, $usuCod)) {
			aud_cfg_json(array('success' => false, 'message' => 'Acceso denegado: solo el Administrador de Sistemas puede realizar esta accion.'));
			return false;
		}
		return true;
	}
}

/** Estado de la clave de acceso al directorio (configurada o no) */
if (isset($_REQUEST['getAccesoEstadoAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$conAcc = aud_acc_connect();
	if (!$conAcc) {
		$conAcc = $obBD_conexion->conexion;
	}
	aud_acc_ensure_schema($conAcc);
	$estado = aud_acc_estado($conAcc, $audEmpCod);
	aud_cfg_json(array('success' => true, 'configurada' => !empty($estado['configurada']), 'fecha' => $estado['fecha']));
	if ($conAcc && $conAcc !== $obBD_conexion->conexion) {
		@mysqli_close($conAcc);
	}
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Definir o cambiar la clave de acceso al directorio (solo Administrador de Sistemas) */
if (isset($_REQUEST['setAccesoClaveAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	if (aud_cfg_requerir_admin_ajax($obBD_con1, $obBD_conexion, $audUsuCod)) {
		$claveNueva = isset($_POST['clave']) ? (string)$_POST['clave'] : '';
		$conAcc = aud_acc_connect();
		if (!$conAcc) {
			$conAcc = $obBD_conexion->conexion;
		}
		aud_cfg_json(aud_acc_set_clave($conAcc, $audEmpCod, $audUsuCod, $claveNueva));
		if ($conAcc && $conAcc !== $obBD_conexion->conexion) {
			@mysqli_close($conAcc);
		}
	}
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Desactivar la clave de acceso al directorio (solo Administrador de Sistemas) */
if (isset($_REQUEST['desactivarAccesoClaveAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	if (aud_cfg_requerir_admin_ajax($obBD_con1, $obBD_conexion, $audUsuCod)) {
		$conAcc = aud_acc_connect();
		if (!$conAcc) {
			$conAcc = $obBD_conexion->conexion;
		}
		aud_cfg_json(aud_acc_desactivar($conAcc, $audEmpCod));
		if ($conAcc && $conAcc !== $obBD_conexion->conexion) {
			@mysqli_close($conAcc);
		}
	}
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Listar reglas de notificacion por correo de la empresa */
if (isset($_REQUEST['listNotifReglasAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$reglas = aud_notif_listar_reglas($obBD_conexion, $audEmpCod);
	aud_cfg_json(array('success' => true, 'rows' => $reglas));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Guardar (crear/editar) una regla de notificacion (solo Administrador de Sistemas) */
if (isset($_REQUEST['saveNotifReglaAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	if (aud_cfg_requerir_admin_ajax($obBD_con1, $obBD_conexion, $audUsuCod)) {
		$data = array(
			'notCod' => isset($_POST['notCod']) ? (int)$_POST['notCod'] : 0,
			'org' => isset($_POST['org']) ? (int)$_POST['org'] : 0,
			'pcs' => isset($_POST['pcs']) ? (int)$_POST['pcs'] : 0,
			'eventos' => isset($_POST['eventos']) ? (string)$_POST['eventos'] : '',
			'correos' => isset($_POST['correos']) ? (string)$_POST['correos'] : '',
			'usuarios' => isset($_POST['usuarios']) ? (string)$_POST['usuarios'] : ''
		);
		aud_cfg_json(aud_notif_guardar_regla($obBD_conexion, $audEmpCod, $audUsuCod, $data));
	}
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Eliminar (desactivar) una regla de notificacion (solo Administrador de Sistemas) */
if (isset($_REQUEST['deleteNotifReglaAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	if (aud_cfg_requerir_admin_ajax($obBD_con1, $obBD_conexion, $audUsuCod)) {
		$notCod = isset($_POST['notCod']) ? (int)$_POST['notCod'] : 0;
		aud_cfg_json(aud_notif_eliminar_regla($obBD_conexion, $audEmpCod, $notCod));
	}
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Listar usuarios de la empresa con su correo registrado para notificaciones */
if (isset($_REQUEST['listCorreosUsuarioAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$rows = aud_notif_listar_correos_usuario($obBD_conexion, $audEmpCod);
	aud_cfg_json(array('success' => true, 'rows' => $rows));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Registrar/actualizar el correo de un usuario del sistema (solo Administrador de Sistemas) */
if (isset($_REQUEST['saveCorreoUsuarioAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	if (aud_cfg_requerir_admin_ajax($obBD_con1, $obBD_conexion, $audUsuCod)) {
		$usuCodDest = isset($_POST['usu']) ? (int)$_POST['usu'] : 0;
		$correoDest = isset($_POST['correo']) ? (string)$_POST['correo'] : '';
		aud_cfg_json(aud_notif_guardar_correo_usuario($obBD_conexion, $audEmpCod, $usuCodDest, $correoDest));
	}
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

$rowCfgCount = $obBD_con1->getRowConsulta(6, array($audEmpCod), $obBD_conexion);
$audCfgCount = isset($rowCfgCount['count']) ? (int)$rowCfgCount['count'] : 0;
$audEstado = aud_estado_captura($audEmpCod, $audCfgCount);
$rowCfgAdmin = $obBD_con1->getRowConsulta(13, array($audUsuCod), $obBD_conexion);
$audEsAdmin = !empty($rowCfgAdmin['is_admin']);
?><!DOCTYPE html>
<html lang="es">
<head>
	<title><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'Auditoria'; ?></title>
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
	<?php require_once("../../mascaras/model3/estilos/estilos.php"); ?>
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<link rel="stylesheet" type="text/css" href="../RECURSOS/aud_monitoreo_ui_1.0.css?v=20260923_v15" />
<style type="text/css">
	.aud-readonly-banner { background: #fff3cd; border: 1px solid #ffe08a; color: #664d03; padding: 8px 10px; border-radius: 4px; margin: 0 0 10px 0; font-size: 12px; }
	.aud-cfg-filter-card { background: #f8fafc; border: 1px solid #dce3ea; border-radius: 4px; padding: 10px 12px; margin: 8px 0 12px 0; }
	.aud-cfg-filter-card label { font-size: 12px; font-weight: 600; color: #334155; display: block; margin: 0 0 4px 0; }
	.aud-cfg-filter-card .form-control { height: 28px; padding: 4px 8px; font-size: 12px; }
	.aud-cfg-filter-card .row > [class*="col-"] { margin-bottom: 8px; }
	.aud-cfg-filter-actions { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
	.aud-cfg-filter-info { display: none; margin: 8px 0 2px 0; font-size: 12px; padding: 6px 10px; }
	.aud-badge-assigned { margin-left: 6px; font-size: 10px; color: #0f7b3d; white-space: nowrap; }
</style>
</head>
<body>
<div class="panel panel-default panel-main exa-ui-panel exa-ui-fill-page">
	<div class="panel-heading exa-header">
		<h3 class="panel-title"><span class="glyphicon glyphicon-cog"></span> Configuracion de monitoreo</h3>
	</div>
	<div class="panel-body exa-body">

		<div class="aud-cfg-tabs">
			<ul class="nav nav-tabs" id="audCfgTabs">
				<li class="active">
					<a href="#tabCfgModulos" data-toggle="tab"><span class="glyphicon glyphicon-list-alt"></span> M&oacute;dulos y Procesos</a>
				</li>
				<li>
					<a href="#tabCfgAcceso" data-toggle="tab"><span class="glyphicon glyphicon-lock"></span> Clave de Acceso</a>
				</li>
				<li>
					<a href="#tabCfgNotif" data-toggle="tab"><span class="glyphicon glyphicon-envelope"></span> Notificaciones por Correo</a>
				</li>
			</ul>

			<div class="tab-content">
			<div class="tab-pane active" id="tabCfgModulos">
		<div class="aud-cfg-tab-body">
			<div class="aud-page-hero">
				<div class="aud-page-hero-icon"><span class="glyphicon glyphicon-list-alt"></span></div>
				<div class="aud-page-hero-text">
					<h4>M&oacute;dulos y procesos a registrar</h4>
					<p class="aud-page-hero-sub">
						Active interruptores por m&oacute;dulo, directorio o proceso. Sin reglas activas
						<strong>no se registra</strong> actividad de monitoreo.
					</p>
				</div>
				<div class="aud-page-hero-tags">
					<span class="aud-page-hero-tag"><span class="glyphicon glyphicon-th-list"></span> Lista / Grid</span>
					<span class="aud-page-hero-tag"><span class="glyphicon glyphicon-filter"></span> Rol / Usuario</span>
				</div>
			</div>
		<div id="lista" class="row exa-ui-page-view">
			<div class="col-xs-12">
				<?php echo aud_html_banner_captura($audEstado); ?>
				<?php if (!$audEsAdmin) { ?>
				<div class="aud-readonly-banner">
					<span class="glyphicon glyphicon-lock" style="font-size: 14px; margin-right: 4px;"></span>
					<strong>Modo solo lectura:</strong> Solamente el <strong>Administrador de Sistemas</strong> puede modificar las reglas de monitoreo. Se muestran unicamente los modulos autorizados por su perfil.
				</div>
				<?php } ?>
				<fieldset class="exa-fieldset">
					<legend class="Titulos2">Modulos, directorios y procesos a registrar</legend>
					<p class="aud-cfg-hint aud-cfg-hint-compact">
						<span class="glyphicon glyphicon-info-sign"></span>
						Active el <strong>interruptor</strong> de un modulo, directorio o proceso para que el monitor registre su actividad.
						<span class="glyphicon glyphicon-question-sign aud-cfg-hint-more" tabindex="0" title="Si activa un modulo o directorio completo, tambien se auditaran procesos nuevos de ese nivel. Si deja todo apagado, no se registrara actividad de monitoreo. El filtro por rol/usuario lista siempre todos los modulos, directorios y procesos; los autorizados del rol quedan resaltados como &quot;Asignado&quot;."></span>
					</p>
					<!-- Validar filtro de roles por modulos y procesos -->
					<button type="button" id="btnCfgFiltroToggle" class="btn btn-default btn-xs aud-cfg-filter-toggle">
						<span class="glyphicon glyphicon-briefcase"></span> Filtrar por rol / usuario <span class="glyphicon glyphicon-chevron-down"></span>
					</button>
					<div class="aud-cfg-filter-card" id="audCfgFilterCard" style="display:none;">
						<p class="aud-cfg-filter-hint">
							<span class="glyphicon glyphicon-info-sign"></span>
							Filtre por <strong>Rol</strong> o por <strong>Usuario</strong> (uno a la vez),
							acote por <strong>Estado de Auditoria</strong> o marque/desmarque en bloque lo asignado.
						</p>
						<div class="row">
							<div class="col-sm-4 col-xs-12">
								<label for="audCfgFiltroRol"><span class="glyphicon glyphicon-briefcase"></span> Filtrar por Rol:</label>
								<select id="audCfgFiltroRol" class="form-control">
									<option value="">-- Todos los roles --</option>
								</select>
							</div>
							<div class="col-sm-4 col-xs-12">
								<label for="audCfgFiltroUsu"><span class="glyphicon glyphicon-user"></span> Filtrar por Usuario:</label>
								<select id="audCfgFiltroUsu" class="form-control">
									<option value="">-- Todos los usuarios --</option>
								</select>
							</div>
							<div class="col-sm-4 col-xs-12">
								<label for="audCfgFiltroAudit"><span class="glyphicon glyphicon-filter"></span> Estado de Auditoria:</label>
								<select id="audCfgFiltroAudit" class="form-control">
									<option value="todos">Todos los procesos</option>
									<option value="marcados">Solo marcados (auditados)</option>
									<option value="no_marcados">Solo sin marcar (pendientes)</option>
								</select>
							</div>
						</div>
						<div class="aud-cfg-filter-actions">
							<div>
								<label style="cursor:pointer; font-size:12px; font-weight:normal; text-transform:none; margin:0; display:inline-block; color:#2c3e50;">
									<input type="checkbox" id="audCfgModoEstricto" style="vertical-align:middle; margin-top:-1px;">
									<strong>Ocultar solo los asignados:</strong> ver unicamente lo que pertenece al rol/usuario seleccionado (por defecto se listan todos)
								</label>
							</div>
							<div class="text-right">
								<button type="button" id="btnCfgLimpiarFiltro" class="btn btn-default btn-xs" title="Quitar filtro y ver todos los procesos">
									<span class="glyphicon glyphicon-remove"></span> Ver todos
								</button>
								<?php if ($audEsAdmin) { ?>
								<button type="button" id="btnCfgMarcarFiltro" class="btn btn-info btn-xs" style="display:none;" title="Marcar en auditoria los procesos que tienen asignados este rol o usuario">
									<span class="glyphicon glyphicon-check"></span> Marcar asignados
								</button>
								<button type="button" id="btnCfgDesmarcarFiltro" class="btn btn-warning btn-xs" style="display:none;" title="Desmarcar en auditoria los procesos asignados a este rol o usuario">
									<span class="glyphicon glyphicon-unchecked"></span> Desmarcar asignados
								</button>
								<?php } ?>
							</div>
						</div>
						<div id="audCfgFilterMsg" class="aud-cfg-filter-info alert alert-info"></div>
					</div>
					<div class="aud-cfg-toolbar aud-cfg-toolbar-flex">
						<div class="aud-cfg-toolbar-group">
							<span id="audCfgDirty" class="aud-cfg-dirty" style="display:none;"><span class="glyphicon glyphicon-warning-sign"></span> Cambios sin guardar</span>
							<?php if ($audEsAdmin) { ?>
							<button type="button" id="btnCfgGuardar" class="btn btn-success btn-xs">
								<span class="glyphicon glyphicon-floppy-disk"></span> Guardar
							</button>
							<div class="btn-group btn-group-xs" role="group">
								<button type="button" id="btnCfgTodos" class="btn btn-default btn-xs" title="Marcar todos los procesos visibles">
									<span class="glyphicon glyphicon-check"></span> Todos
								</button>
								<button type="button" id="btnCfgNinguno" class="btn btn-default btn-xs" title="Desmarcar todos los procesos visibles">
									<span class="glyphicon glyphicon-unchecked"></span> Ninguno
								</button>
							</div>
							<?php } else { ?>
							<button type="button" id="btnCfgGuardar" class="btn btn-success btn-xs" disabled="disabled" title="Solo el Administrador de Sistemas puede modificar la configuracion">
								<span class="glyphicon glyphicon-lock"></span> Guardar (Solo lectura)
							</button>
							<div class="btn-group btn-group-xs" role="group">
								<button type="button" id="btnCfgTodos" class="btn btn-default btn-xs" disabled="disabled">
									<span class="glyphicon glyphicon-check"></span> Todos
								</button>
								<button type="button" id="btnCfgNinguno" class="btn btn-default btn-xs" disabled="disabled">
									<span class="glyphicon glyphicon-unchecked"></span> Ninguno
								</button>
							</div>
							<?php } ?>
							<div class="btn-group btn-group-xs" role="group">
								<button type="button" id="btnCfgExpandir" class="btn btn-default btn-xs" title="Expandir arbol">
									<span class="glyphicon glyphicon-resize-full"></span>
								</button>
								<button type="button" id="btnCfgContraer" class="btn btn-default btn-xs" title="Contraer arbol">
									<span class="glyphicon glyphicon-resize-small"></span>
								</button>
								<button type="button" id="btnCfgRecargar" class="btn btn-default btn-xs" title="Recargar desde el servidor">
									<span class="glyphicon glyphicon-refresh"></span>
								</button>
							</div>
							<div class="btn-group btn-group-xs" role="group">
								<button type="button" id="btnCfgVistaLista" class="btn btn-default btn-xs active" title="Ver el arbol como lista colapsable">
									<span class="glyphicon glyphicon-th-list"></span> Lista
								</button>
								<button type="button" id="btnCfgVistaGrid" class="btn btn-default btn-xs" title="Ver el arbol como grilla de directorio/proceso">
									<span class="glyphicon glyphicon-th"></span> Grid
								</button>
							</div>
						</div>
						<div class="aud-cfg-toolbar-right-group">
							<div class="aud-cfg-search aud-cfg-search-inline">
								<span class="glyphicon glyphicon-search"></span>
								<input type="text" id="audCfgFilter" class="form-control input-xs" placeholder="Filtrar modulo, directorio o proceso..." />
							</div>
						</div>
					</div>
					<div id="audCfgList" class="aud-cfg-list">
						<p class="aud-cfg-empty">Cargando...</p>
					</div>
					<p class="aud-cfg-status" id="audCfgStatus"></p>
				</fieldset>
			</div>
		</div><!-- /#lista -->
		</div><!-- /.aud-cfg-tab-body -->
			</div><!-- /#tabCfgModulos -->

			<div class="tab-pane" id="tabCfgAcceso">
		<div class="aud-cfg-tab-body aud-acc-page">
			<div class="aud-acc-hero aud-page-hero">
				<div class="aud-page-hero-icon"><span class="glyphicon glyphicon-lock"></span></div>
				<div class="aud-page-hero-text">
					<h4>Clave de acceso al directorio</h4>
					<p class="aud-page-hero-sub">
						Si se configura, todo usuario deber&aacute; ingresarla <strong>una vez por sesi&oacute;n</strong>
						antes de ver monitoreo, dashboards, actividad y esta configuraci&oacute;n.
					</p>
				</div>
				<div class="aud-page-hero-tags">
					<span class="aud-page-hero-tag"><span class="glyphicon glyphicon-shield"></span> Por empresa</span>
				</div>
			</div>
			<div class="aud-acc-card">
				<div class="aud-acc-estado" id="audAccEstadoTxt">
					<span class="glyphicon glyphicon-refresh"></span> Consultando estado...
				</div>
				<?php if ($audEsAdmin) { ?>
				<div class="aud-acc-form-grid">
					<div class="aud-acc-field">
						<label for="audAccClaveNueva">Nueva clave</label>
						<input type="password" id="audAccClaveNueva" class="form-control input-sm" placeholder="Minimo 4 caracteres" autocomplete="new-password" />
					</div>
					<div class="aud-acc-field">
						<label for="audAccClaveConfirma">Confirmar clave</label>
						<input type="password" id="audAccClaveConfirma" class="form-control input-sm" placeholder="Repita la clave" autocomplete="new-password" />
					</div>
					<div class="aud-acc-actions">
						<button type="button" id="btnAudAccGuardar" class="btn btn-primary btn-sm">
							<span class="glyphicon glyphicon-ok"></span> Guardar clave
						</button>
						<button type="button" id="btnAudAccDesactivar" class="btn btn-default btn-sm">
							<span class="glyphicon glyphicon-remove"></span> Desactivar clave
						</button>
					</div>
				</div>
				<p class="aud-cfg-status" id="audAccStatus"></p>
				<?php } else { ?>
				<p class="text-muted" style="margin:0;"><span class="glyphicon glyphicon-info-sign"></span> Solo el Administrador de Sistemas puede definir o cambiar esta clave.</p>
				<?php } ?>
			</div>
		</div>
			</div><!-- /#tabCfgAcceso -->

			<div class="tab-pane" id="tabCfgNotif">
		<div class="aud-cfg-tab-body aud-notif-page">
			<div class="aud-notif-hero">
				<div class="aud-notif-hero-icon"><span class="glyphicon glyphicon-envelope"></span></div>
				<div class="aud-notif-hero-text">
					<h4 class="aud-cfg-tab-title" style="margin:0;padding:0;border:0;">Notificaciones por correo</h4>
					<p class="aud-notif-hero-sub">
						Alerte a responsables cuando se <strong>modifique</strong> o <strong>elimine</strong> un registro,
						seg&uacute;n el m&oacute;dulo, directorio o proceso que elija.
					</p>
				</div>
				<div class="aud-notif-hero-tags">
					<span class="aud-notif-tag aud-notif-tag-u"><span class="glyphicon glyphicon-pencil"></span> Actualizar</span>
					<span class="aud-notif-tag aud-notif-tag-d"><span class="glyphicon glyphicon-trash"></span> Eliminar</span>
				</div>
			</div>

			<?php if ($audEsAdmin) { ?>
			<div class="aud-notif-form-card">
				<div class="aud-notif-form-head">
					<span class="aud-notif-form-badge">Nueva regla</span>
					<span class="aud-notif-form-hint">Complete los pasos y guarde. Puede editar una regla desde la lista inferior.</span>
				</div>
				<input type="hidden" id="audNotifCod" value="0" />

				<div class="aud-notif-steps">
					<div class="aud-notif-step aud-notif-step-alcance">
						<div class="aud-notif-step-num">1</div>
						<div class="aud-notif-step-body">
							<label>Alcance a vigilar</label>
							<p class="aud-notif-step-help">Haga clic para elegir m&oacute;dulos, directorios o procesos con interruptores</p>
							<div class="aud-notif-alcance-box" id="audNotifAlcanceBox" role="button" tabindex="0" title="Abrir selector de alcance">
								<div class="aud-notif-alcance-chips" id="audNotifAlcanceChips">
									<span class="aud-notif-alcance-placeholder">Ning&uacute;n alcance seleccionado. Clic para elegir&hellip;</span>
								</div>
								<span class="aud-notif-alcance-action"><span class="glyphicon glyphicon-th-list"></span> Elegir</span>
							</div>
						</div>
					</div>

					<div class="aud-notif-step aud-notif-step-eventos">
						<div class="aud-notif-step-num">2</div>
						<div class="aud-notif-step-body">
							<label>Eventos</label>
							<p class="aud-notif-step-help">Qu&eacute; operaciones disparan el correo</p>
							<div class="aud-notif-eventos" id="audNotifEventosBox">
								<label class="aud-notif-chip aud-notif-chip-u" for="audNotifEveU">
									<input type="checkbox" id="audNotifEveU" checked="checked" />
									<span class="aud-notif-chip-ui"><span class="glyphicon glyphicon-pencil"></span> Actualizar</span>
								</label>
								<label class="aud-notif-chip aud-notif-chip-d" for="audNotifEveD">
									<input type="checkbox" id="audNotifEveD" checked="checked" />
									<span class="aud-notif-chip-ui"><span class="glyphicon glyphicon-trash"></span> Eliminar</span>
								</label>
							</div>
						</div>
					</div>

					<div class="aud-notif-step">
						<div class="aud-notif-step-num">3</div>
						<div class="aud-notif-step-body">
							<label for="audNotifUsuarios">Usuarios destino</label>
							<p class="aud-notif-step-help">Solo aparecen usuarios con correo registrado (paso inferior)</p>
							<select id="audNotifUsuarios" class="form-control input-sm" multiple="multiple" data-placeholder="Seleccione usuarios..."></select>
						</div>
					</div>

					<div class="aud-notif-step">
						<div class="aud-notif-step-num">4</div>
						<div class="aud-notif-step-body">
							<label for="audNotifCorreos">Correos adicionales</label>
							<p class="aud-notif-step-help">Separados por coma (opcionales si ya eligi&oacute; usuarios)</p>
							<input type="text" id="audNotifCorreos" class="form-control input-sm" placeholder="correo1@dominio.com, correo2@dominio.com" />
						</div>
					</div>
				</div>

				<div class="aud-notif-form-footer">
					<p class="aud-cfg-status" id="audNotifStatus"></p>
					<div class="aud-notif-form-actions">
						<button type="button" id="btnAudNotifLimpiar" class="btn btn-default btn-sm">
							<span class="glyphicon glyphicon-erase"></span> Limpiar
						</button>
						<button type="button" id="btnAudNotifGuardar" class="btn btn-success btn-sm">
							<span class="glyphicon glyphicon-floppy-disk"></span> Guardar regla
						</button>
					</div>
				</div>
			</div>
			<?php } else { ?>
			<p class="aud-notif-readonly"><span class="glyphicon glyphicon-lock"></span> Solo el Administrador de Sistemas puede crear o editar reglas de notificaci&oacute;n.</p>
			<?php } ?>

			<div class="aud-notif-list-card">
				<div class="aud-notif-list-head">
					<h5><span class="glyphicon glyphicon-list-alt"></span> Reglas configuradas</h5>
					<span class="aud-notif-list-sub">Se eval&uacute;an en tiempo real al registrar la actividad</span>
				</div>
				<div class="table-responsive">
					<table class="table table-condensed" id="audNotifTabla">
						<thead>
							<tr>
								<th>Alcance</th>
								<th>Eventos</th>
								<th>Correos</th>
								<th>Usuarios</th>
								<?php if ($audEsAdmin) { ?><th class="text-center">Acciones</th><?php } ?>
							</tr>
						</thead>
						<tbody id="audNotifTbody">
							<tr><td colspan="5" class="text-center text-muted aud-notif-empty-row"><span class="glyphicon glyphicon-hourglass"></span>Cargando...</td></tr>
						</tbody>
					</table>
				</div>
			</div>

			<?php if ($audEsAdmin) { ?>
			<div class="aud-correo-usu-card">
				<div class="aud-correo-usu-head">
					<div class="aud-correo-usu-icon"><span class="glyphicon glyphicon-user"></span></div>
					<div>
						<h5>Correo por usuario</h5>
						<p>Registre el correo de un usuario del sistema para poder elegirlo como destinatario en las reglas.</p>
					</div>
				</div>
				<div class="aud-correo-usu-grid">
					<div class="aud-acc-field">
						<label for="audCorreoUsuSel">Usuario</label>
						<select id="audCorreoUsuSel" class="form-control input-sm" data-placeholder="Seleccione usuario..."></select>
					</div>
					<div class="aud-acc-field">
						<label for="audCorreoUsuValor">Correo</label>
						<input type="email" id="audCorreoUsuValor" class="form-control input-sm" placeholder="correo@dominio.com" />
					</div>
					<div class="aud-acc-actions">
						<button type="button" id="btnAudCorreoUsuGuardar" class="btn btn-primary btn-sm">
							<span class="glyphicon glyphicon-ok"></span> Guardar correo
						</button>
					</div>
				</div>
				<p class="aud-cfg-status" id="audCorreoUsuStatus"></p>
			</div>
			<?php } ?>
		</div>
			</div><!-- /#tabCfgNotif -->
			</div><!-- /.tab-content -->
		</div><!-- /.aud-cfg-tabs -->
	</div>
</div>

<?php if ($audEsAdmin) { ?>
<!-- Modal selector de alcance (modulos / directorios / procesos) -->
<div id="modalAudNotifAlcance" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg aud-notif-alcance-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header aud-notif-alcance-modal-head">
				<button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title">
					<span class="glyphicon glyphicon-eye-open"></span> Alcance a vigilar
				</h4>
				<p class="aud-notif-alcance-modal-sub">
					Active el interruptor de cada m&oacute;dulo, directorio o proceso.
					Los eventos de la regla (<span id="audNotifModalEveHint">Actualizar / Eliminar</span>) se aplicar&aacute;n a lo seleccionado.
				</p>
			</div>
			<div class="modal-body aud-notif-alcance-modal-body">
				<div class="aud-notif-alcance-toolbar">
					<input type="text" id="audNotifAlcanceBuscar" class="form-control input-sm" placeholder="Buscar m&oacute;dulo, directorio o proceso..." />
					<span class="aud-notif-alcance-count" id="audNotifAlcanceCount">0 seleccionados</span>
				</div>
				<div id="audNotifAlcanceTree" class="aud-notif-alcance-tree">
					<p class="text-muted text-center" style="padding:20px;">Cargando cat&aacute;logo...</p>
				</div>
			</div>
			<div class="modal-footer aud-notif-alcance-modal-foot">
				<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-success btn-sm" id="btnAudNotifAlcanceGuardar">
					<span class="glyphicon glyphicon-ok"></span> Guardar selecci&oacute;n
				</button>
			</div>
		</div>
	</div>
</div>
<?php } ?>

<script type="text/javascript">window.audEsAdminSistemas = <?php echo $audEsAdmin ? 'true' : 'false'; ?>;</script>
<script type="text/javascript" src="../VALIDACIONES/aud_par_config_monitoreo.js?v=20260923_v13"></script>
</body>
</html>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>

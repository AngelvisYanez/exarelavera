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

aud_cfg_ensure_schema($obBD_conexion);

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
	<link rel="stylesheet" type="text/css" href="../RECURSOS/aud_monitoreo_ui_1.0.css?v=20260915_v2" />
<style type="text/css">
	.aud-readonly-banner { background: #fff3cd; border: 1px solid #ffe08a; color: #664d03; padding: 8px 10px; border-radius: 4px; margin: 0 0 10px 0; font-size: 12px; }
	.aud-cfg-filter-card { background: #f8fafc; border: 1px solid #dce3ea; border-radius: 4px; padding: 10px 12px; margin: 8px 0 12px 0; }
	.aud-cfg-filter-card label { font-size: 12px; font-weight: 600; color: #334155; display: block; margin: 0 0 4px 0; }
	.aud-cfg-filter-card .form-control { height: 28px; padding: 4px 8px; font-size: 12px; }
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
					<p class="aud-cfg-hint">
						Active el <strong>interruptor</strong> del <strong>modulo</strong>, de un <strong>directorio</strong> o de procesos puntuales.
						El monitor tomara la actividad (altas, cambios y bajas) de esos niveles en todo el sistema,
						sin limitarse a una lista fija de tablas.
						Si activa un modulo o directorio completo, tambien se auditaran procesos nuevos de ese nivel.
						Si deja todo apagado, no se registrara actividad de monitoreo. Marque los modulos que desea cubrir.
						El filtro por rol/usuario <strong>lista siempre todos los modulos, directorios y procesos</strong>; los autorizados del rol quedan resaltados como "Asignado".
					</p>
					<!-- Validar filtro de roles por modulos y procesos -->
					<div class="aud-cfg-filter-card">
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
					<div class="aud-cfg-toolbar">
						<span id="audCfgDirty" class="aud-cfg-dirty" style="display:none;"><span class="glyphicon glyphicon-warning-sign"></span> Cambios sin guardar</span>
						<?php if ($audEsAdmin) { ?>
						<button type="button" id="btnCfgGuardar" class="btn btn-success btn-xs">
							<span class="glyphicon glyphicon-floppy-disk"></span> Guardar
						</button>
						<button type="button" id="btnCfgTodos" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-check"></span> Marcar todos
						</button>
						<button type="button" id="btnCfgNinguno" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-unchecked"></span> Desmarcar todos
						</button>
						<?php } else { ?>
						<button type="button" id="btnCfgGuardar" class="btn btn-success btn-xs" disabled="disabled" title="Solo el Administrador de Sistemas puede modificar la configuracion">
							<span class="glyphicon glyphicon-lock"></span> Guardar (Solo lectura)
						</button>
						<button type="button" id="btnCfgTodos" class="btn btn-default btn-xs" disabled="disabled">
							<span class="glyphicon glyphicon-check"></span> Marcar todos
						</button>
						<button type="button" id="btnCfgNinguno" class="btn btn-default btn-xs" disabled="disabled">
							<span class="glyphicon glyphicon-unchecked"></span> Desmarcar todos
						</button>
						<?php } ?>
						<button type="button" id="btnCfgExpandir" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-resize-full"></span> Expandir
						</button>
						<button type="button" id="btnCfgContraer" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-resize-small"></span> Contraer
						</button>
						<button type="button" id="btnCfgRecargar" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-refresh"></span> Recargar
						</button>
						<span class="aud-cfg-vista-sep"></span>
						<button type="button" id="btnCfgVistaLista" class="btn btn-default btn-xs active" title="Ver el arbol como lista colapsable">
							<span class="glyphicon glyphicon-th-list"></span> Lista
						</button>
						<button type="button" id="btnCfgVistaGrid" class="btn btn-default btn-xs" title="Ver el arbol como grilla de directorio/proceso">
							<span class="glyphicon glyphicon-th"></span> Grid
						</button>
					</div>
					<div class="aud-cfg-search">
						<input type="text" id="audCfgFilter" class="form-control input-xs" placeholder="Filtrar modulo, directorio o proceso..." />
					</div>
					<div id="audCfgList" class="aud-cfg-list">
						<p class="aud-cfg-empty">Cargando...</p>
					</div>
					<p class="aud-cfg-status" id="audCfgStatus"></p>
				</fieldset>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">window.audEsAdminSistemas = <?php echo $audEsAdmin ? 'true' : 'false'; ?>;</script>
<script type="text/javascript" src="../VALIDACIONES/aud_par_config_monitoreo.js?v=20260915_v5"></script>
</body>
</html>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>

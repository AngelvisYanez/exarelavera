<?php
/**
 * Pantalla de configuracion de monitoreo (modulos, directorios y procesos a auditar).
 * Permite seleccionar que partes del sistema se registran en auditoria.logs.
 * Restriccion: Modificacion y guardado exclusivo para Administrador de Sistemas.
 */
if (session_id() === '' && !headers_sent()) {
	@session_start();
}
require_once(__DIR__."/../../DATA/MysqlConexion.php");
require_once(__DIR__."/../../DATA/MysqlDatos.php");
require_once(__DIR__."/../LOGICA/aud_log_config_monitoreo.php");
require_once(__DIR__."/../LOGICA/aud_sql_config_monitoreo.php");

$audEmpCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
$audSucCod = isset($_SESSION['Ses_Suc_Cod']) ? (int)$_SESSION['Ses_Suc_Cod'] : 0;
$audUsuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;
$Ses_Dat_Dis = isset($_SESSION['Ses_Dat_Dis']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_SESSION['Ses_Dat_Dis']) : '';

$obBD_con1 = new Class_Log_Datos_Cfg_Monitoreo();
$obBD_conexion = new Class_Log_Conexion_Cfg_Monitoreo($Ses_Dat_Dis !== '' ? $Ses_Dat_Dis : null);
aud_cfg_asegurar_tabla($obBD_conexion);

// Determinar si el usuario actual es Administrador de Sistemas
$esAdminSistemas = aud_cfg_es_admin_sistemas($audUsuCod, $obBD_con1, $obBD_conexion);

if (!function_exists('aud_cfg_json')) {
	function aud_cfg_to_utf8(&$input) {
		if (is_string($input)) {
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
	// Si es administrador ve todo el arbol; si es un rol delegado (no admin), solo se le entrega lo autorizado por sus roles en perfiorgan
	$Arr_Tree = $esAdminSistemas
		? $obBD_con1->getArrayConsulta(7, array(), $obBD_conexion)
		: $obBD_con1->getArrayConsulta(14, array($audUsuCod, 0), $obBD_conexion);

	$Arr_Cfg = $obBD_con1->getArrayConsulta(3, array($audEmpCod), $obBD_conexion);
	if (!is_array($Arr_Tree)) $Arr_Tree = array();
	if (!is_array($Arr_Cfg)) $Arr_Cfg = array();

	if (empty($Arr_Tree)) {
		$errMsg = 'AudTreeEmpty';
		$errNo = isset($obBD_conexion->Errno) ? $obBD_conexion->Errno : 0;
		$errTxt = isset($obBD_conexion->Error) ? $obBD_conexion->Error : '';
		$conRef = isset($obBD_conexion->conexion) ? $obBD_conexion->conexion : null;
		$myErr = ($conRef && is_object($conRef)) ? @mysqli_error($conRef) : '';
		$myErrNo = ($conRef && is_object($conRef)) ? @mysqli_errno($conRef) : 0;
		error_log("{$errMsg} emp={$audEmpCod} datDis={$Ses_Dat_Dis} connErrno={$errNo} connErr={$errTxt} queryErrNo={$myErrNo} queryErr={$myErr}");
	} else {
		error_log("AudTreeOK emp={$audEmpCod} datDis={$Ses_Dat_Dis} rows=" . count($Arr_Tree));
	}

	$mods = aud_cfg_armar_arbol($Arr_Tree);
	if (empty($mods) && !empty($Arr_Tree)) {
		error_log("AudTreeFiltered raw=" . count($Arr_Tree) . " mods=0");
	}
	$marks = aud_cfg_marcar_seleccion($mods, $Arr_Cfg);

	aud_cfg_json(array(
		'success' => true,
		'modules' => $mods,
		'selected' => $marks['selected'],
		'modFull' => $marks['modFull'],
		'dirFull' => $marks['dirFull'],
		'hasConfig' => count($Arr_Cfg) > 0,
		'esAdmin' => $esAdminSistemas
	));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Cargar roles/perfiles de la empresa (filtro) */
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

/** Cargar usuarios de la empresa (filtro) */
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

/** Obtener procesos asignados a un Rol o Usuario (filtro) */
if (isset($_REQUEST['listProcesosFiltroAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$rolCod = isset($_REQUEST['rol']) ? (int)$_REQUEST['rol'] : 0;
	$usuFiltro = isset($_REQUEST['usu']) ? (int)$_REQUEST['usu'] : 0;
	$arrPcs = array();
	if ($rolCod > 0) {
		$arrPcs = $obBD_con1->getArrayConsulta(11, array($rolCod), $obBD_conexion);
	} elseif ($usuFiltro > 0) {
		$arrPcs = $obBD_con1->getArrayConsulta(12, array($usuFiltro), $obBD_conexion);
	}
	if (!is_array($arrPcs)) $arrPcs = array();
	$pcsCodes = array();
	foreach ($arrPcs as $rowP) {
		if (!empty($rowP['Pcs_Cod'])) {
			$pcsCodes[] = (int)$rowP['Pcs_Cod'];
		}
	}
	aud_cfg_json(array('success' => true, 'pcs' => $pcsCodes, 'total' => count($pcsCodes)));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Guardar config */
if (isset($_REQUEST['saveConfigAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');

	// Bloqueo de seguridad estricto en backend: solo Administrador de Sistemas
	if (!$esAdminSistemas) {
		aud_cfg_json(array(
			'success' => false,
			'message' => 'Acceso denegado: Solo el Administrador de Sistemas tiene permiso para modificar y guardar la configuracion de monitoreo.'
		));
		$obBD_con1->liberar();
		$obBD_conexion->cerrar();
		exit();
	}

	$payload = isset($_POST['items']) ? $_POST['items'] : '';
	$parsed = aud_cfg_parse_items($payload);
	if (empty($parsed['ok'])) {
		aud_cfg_json(array(
			'success' => false,
			'message' => isset($parsed['message']) ? $parsed['message'] : 'Seleccion no valida.'
		));
		$obBD_con1->liberar();
		$obBD_conexion->cerrar();
		exit();
	}

	$res = aud_cfg_guardar($obBD_con1, $obBD_conexion, $audEmpCod, $audUsuCod, $parsed['items']);
	if (!empty($res['success'])) {
		aud_cfg_trazar_cambio($obBD_con1, $obBD_conexion, $audEmpCod, $audUsuCod, $audSucCod, (int)$res['saved']);
	}
	aud_cfg_json($res);
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

$audEstado = aud_cfg_comprobar_captura($obBD_con1, $obBD_conexion, $audEmpCod);
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<title><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'Auditoria'; ?></title>
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
	<?php require_once("../../mascaras/model3/estilos/estilos.php"); ?>
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<style type="text/css">
		.aud-cfg-toolbar { margin: 0 0 10px 0; }
		.aud-cfg-toolbar .btn { margin-right: 6px; }
		.aud-cfg-hint {
			margin: 0 0 12px 0;
			padding: 8px 12px;
			background: #f0f5fa;
			border: 1px solid #d5e0ec;
			border-radius: 4px;
			font-size: 12px;
			color: #445566;
			line-height: 1.45;
		}
		.aud-cfg-filter-card {
			margin: 0 0 12px 0;
			padding: 12px 14px 10px;
			background: #fdfefe;
			border: 1px solid #d0dbe5;
			border-left: 4px solid #337ab7;
			border-radius: 4px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.04);
		}
		.aud-cfg-filter-card label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			color: #486581;
			margin-bottom: 3px;
			display: block;
		}
		.aud-cfg-filter-card .form-control {
			height: 28px;
			font-size: 12px;
			padding: 4px 8px;
		}
		.aud-cfg-filter-actions {
			margin-top: 8px;
			padding-top: 8px;
			border-top: 1px dashed #e2e8f0;
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 8px;
		}
		.aud-cfg-filter-info {
			margin: 8px 0 0 0;
			padding: 6px 10px;
			font-size: 12px;
			line-height: 1.4;
			border-radius: 3px;
			display: none;
		}
		.aud-cfg-list { max-height: 520px; overflow: auto; border: 1px solid #d9e2ec; border-radius: 4px; background: #fff; }
		.aud-cfg-search {
			margin: 0 0 8px 0;
			max-width: 360px;
		}
		.aud-cfg-mod {
			border-bottom: 1px solid #e8eef5;
			padding: 6px 10px 4px;
		}
		.aud-cfg-mod:last-child { border-bottom: none; }
		.aud-cfg-mod-head,
		.aud-cfg-dir-head {
			font-size: 13px;
			color: #243447;
			margin-bottom: 2px;
			white-space: nowrap;
		}
		.aud-cfg-mod-head { font-weight: 700; }
		.aud-cfg-dir-head { font-weight: 600; color: #345; padding-left: 4px; }
		.aud-cfg-mod-head label,
		.aud-cfg-dir-head label,
		.aud-cfg-pcs label { cursor: pointer; margin: 0; font-weight: inherit; }
		.aud-cfg-mod-head input,
		.aud-cfg-dir-head input,
		.aud-cfg-pcs input { margin-right: 6px; vertical-align: middle; }
		.aud-cfg-toggle {
			display: inline-block;
			width: 16px;
			color: #6a7c90;
			cursor: pointer;
			text-align: center;
			margin-right: 2px;
			text-decoration: none !important;
		}
		.aud-cfg-mod.aud-cfg-collapsed > .aud-cfg-mod-body,
		.aud-cfg-dir.aud-cfg-collapsed > .aud-cfg-dir-body { display: none; }
		.aud-cfg-mod-body { margin: 0 0 4px 8px; }
		.aud-cfg-dir { margin: 2px 0 4px 10px; }
		.aud-cfg-pcs {
			margin: 0 0 3px 28px;
			font-size: 12px;
			color: #334455;
		}
		.aud-cfg-dir .aud-cfg-pcs { margin-left: 22px; }
		.aud-cfg-count { font-weight: normal; color: #7a8b9e; font-size: 11px; margin-left: 4px; }
		.aud-cfg-empty { padding: 20px; color: #7a8b9e; text-align: center; }
		.aud-cfg-status { margin-top: 10px; font-size: 12px; color: #5b6f88; }
		.aud-captura-banner {
			margin: 0 0 10px 0;
			padding: 8px 12px;
			border-radius: 4px;
			font-size: 12px;
			line-height: 1.45;
		}
		.aud-captura-ok {
			background: #eef7f1;
			border: 1px solid #c5e3d0;
			color: #1f6b3a;
		}
		.aud-captura-off {
			background: #fdf2f0;
			border: 1px solid #f0c9c2;
			color: #a12b22;
		}
		.aud-readonly-banner {
			background: #fff8e1;
			border: 1px solid #ffe082;
			border-left: 4px solid #ffa000;
			color: #8d6e12;
			padding: 8px 12px;
			border-radius: 4px;
			font-size: 12px;
			margin-bottom: 10px;
		}
		.aud-badge-assigned {
			display: inline-block;
			background: #e1effe;
			color: #1a56db;
			font-size: 10px;
			font-weight: 600;
			padding: 1px 5px;
			border-radius: 3px;
			margin-left: 6px;
			vertical-align: middle;
		}
		.aud-badge-audited {
			display: inline-block;
			background: #def7ec;
			color: #03543f;
			font-size: 10px;
			font-weight: 600;
			padding: 1px 5px;
			border-radius: 3px;
			margin-left: 4px;
			vertical-align: middle;
		}
			/* Regla visual ERP: Si el fondo donde estan los titulos es azul, el titulo debe ser blanco */
		.panel-heading.exa-header, .exa-header {
			background-color: #254463 !important;
			color: #ffffff !important;
		}
		.panel-heading.exa-header .panel-title, .exa-header .panel-title,
		.panel-heading.exa-header h3, .exa-header h3 {
			color: #ffffff !important;
			font-weight: 700 !important;
		}
		.panel-heading.exa-header .panel-title i, .exa-header .panel-title i,
		.panel-heading.exa-header .panel-title span, .exa-header .panel-title span {
			color: #ffffff !important;
		}
	</style>
</head>
<body>

<div class="panel panel-default panel-main exa-ui-panel exa-ui-fill-page" style="margin-top: 0;">
	<div class="panel-heading exa-header">
		<h3 class="panel-title" style="font-size: 14px; font-weight: 700; color: #ffffff !important;"><span class="glyphicon glyphicon-cog" style="color: #ffffff; margin-right: 4px;"></span> Configuraci&oacute;n de monitoreo</h3>
	</div>
	<div class="panel-body exa-body">
		<div id="lista" class="row exa-ui-page-view">
			<div class="col-xs-12">
				<?php echo aud_html_banner_captura($audEstado); ?>

				<?php if (!$esAdminSistemas): ?>
				<div class="aud-readonly-banner">
					<span class="glyphicon glyphicon-lock" style="font-size: 14px; margin-right: 4px;"></span>
					<strong>Modo solo lectura:</strong> Solamente el <strong>Administrador de Sistemas</strong> tiene permisos para modificar y guardar las reglas de monitoreo de actividades. Se muestran unicamente los modulos autorizados por su perfil.
				</div>
				<?php endif; ?>

				<fieldset class="exa-fieldset">
					<legend class="Titulos2">M&oacute;dulos, directorios y procesos a registrar</legend>
					<p class="aud-cfg-hint">
						Marque el <strong>m&oacute;dulo</strong>, un <strong>directorio</strong> o procesos puntuales.
						El monitor tomar&aacute; la actividad (ingresos, actualizaciones y eliminaciones) de esos niveles en todo el sistema,
						sin limitarse a una lista fija de tablas.
						Si marca un m&oacute;dulo o directorio completo, tambi&eacute;n se auditar&aacute;n procesos nuevos de ese nivel.
						Si no marca ninguno, se mantiene el comportamiento por defecto (tablas de AUDIT_TABLES).
					</p>

					<!-- Panel de Filtro por Rol, Usuario y Estado de Auditoria -->
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
									<input type="checkbox" id="audCfgModoEstricto" checked style="vertical-align:middle; margin-top:-1px;">
									<strong>Modo estricto del rol:</strong> Ocultar m&oacute;dulos ajenos al rol seleccionado
								</label>
							</div>
							<div class="text-right">
								<button type="button" id="btnCfgLimpiarFiltro" class="btn btn-default btn-xs" title="Quitar filtro y ver todos los procesos">
									<span class="glyphicon glyphicon-remove"></span> Ver todos
								</button>
								<?php if ($esAdminSistemas): ?>
								<button type="button" id="btnCfgMarcarFiltro" class="btn btn-info btn-xs" style="display:none;" title="Marcar en auditoria los procesos que tienen asignados este rol o usuario">
									<span class="glyphicon glyphicon-check"></span> Marcar asignados
								</button>
								<button type="button" id="btnCfgDesmarcarFiltro" class="btn btn-warning btn-xs" style="display:none;" title="Desmarcar en auditoria los procesos asignados a este rol o usuario">
									<span class="glyphicon glyphicon-unchecked"></span> Desmarcar asignados
								</button>
								<?php endif; ?>
							</div>
						</div>
						<div id="audCfgFilterMsg" class="aud-cfg-filter-info alert alert-info"></div>
					</div>

					<div class="aud-cfg-toolbar">
						<?php if ($esAdminSistemas): ?>
						<button type="button" id="btnCfgGuardar" class="btn btn-success btn-xs">
							<span class="glyphicon glyphicon-floppy-disk"></span> Guardar
						</button>
						<button type="button" id="btnCfgTodos" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-check"></span> Marcar todos
						</button>
						<button type="button" id="btnCfgNinguno" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-unchecked"></span> Desmarcar todos
						</button>
						<?php else: ?>
						<button type="button" id="btnCfgGuardar" class="btn btn-success btn-xs" disabled="disabled" title="Solo el Administrador de Sistemas puede modificar la configuracion">
							<span class="glyphicon glyphicon-lock"></span> Guardar (Solo lectura)
						</button>
						<button type="button" id="btnCfgTodos" class="btn btn-default btn-xs" disabled="disabled">
							<span class="glyphicon glyphicon-check"></span> Marcar todos
						</button>
						<button type="button" id="btnCfgNinguno" class="btn btn-default btn-xs" disabled="disabled">
							<span class="glyphicon glyphicon-unchecked"></span> Desmarcar todos
						</button>
						<?php endif; ?>
						<button type="button" id="btnCfgExpandir" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-resize-full"></span> Expandir
						</button>
						<button type="button" id="btnCfgContraer" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-resize-small"></span> Contraer
						</button>
						<button type="button" id="btnCfgRecargar" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-refresh"></span> Recargar
						</button>
					</div>

					<div class="aud-cfg-search">
						<input type="text" id="audCfgFilter" class="form-control input-sm" placeholder="Buscar modulo, directorio o proceso...">
					</div>

					<div id="audCfgList" class="aud-cfg-list">
						<p class="aud-cfg-empty">Cargando m&oacute;dulos y procesos...</p>
					</div>
					<div id="audCfgStatus" class="aud-cfg-status"></div>
				</fieldset>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	window.audEsAdminSistemas = <?php echo $esAdminSistemas ? 'true' : 'false'; ?>;
</script>
<script type="text/javascript" src="../VALIDACIONES/aud_par_config_monitoreo.js"></script>
</body>
</html>

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
	$Arr_Tree = $obBD_con1->getArrayConsulta(7, array(), $obBD_conexion);
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
		'hasConfig' => count($Arr_Cfg) > 0
	));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Guardar config */
if (isset($_REQUEST['saveConfigAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$raw = isset($_POST['items']) ? $_POST['items'] : (isset($_REQUEST['items']) ? $_REQUEST['items'] : '[]');
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
?><!DOCTYPE html>
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
				<fieldset class="exa-fieldset">
					<legend class="Titulos2">Modulos, directorios y procesos a registrar</legend>
					<p class="aud-cfg-hint">
						Marque el <strong>modulo</strong>, un <strong>directorio</strong> o procesos puntuales.
						El monitor tomara la actividad (altas, cambios y bajas) de esos niveles en todo el sistema,
						sin limitarse a una lista fija de tablas.
						Si marca un modulo o directorio completo, tambien se auditaran procesos nuevos de ese nivel.
						Si no marca ninguno, se mantiene el comportamiento por defecto (tablas de AUDIT_TABLES).
					</p>
					<div class="aud-cfg-toolbar">
						<button type="button" id="btnCfgGuardar" class="btn btn-success btn-xs">
							<span class="glyphicon glyphicon-floppy-disk"></span> Guardar
						</button>
						<button type="button" id="btnCfgTodos" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-check"></span> Marcar todos
						</button>
						<button type="button" id="btnCfgNinguno" class="btn btn-default btn-xs">
							<span class="glyphicon glyphicon-unchecked"></span> Desmarcar todos
						</button>
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
<script type="text/javascript" src="../VALIDACIONES/aud_par_config_monitoreo.js?v=20260817c"></script>
</body>
</html>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>

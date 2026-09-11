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

	$mods = aud_cfg_armar_arbol($Arr_Tree);
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
	<link rel="stylesheet" type="text/css" href="../RECURSOS/aud_monitoreo_ui_1.0.css?v=1" />
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

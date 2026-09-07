<?php
/**
 * Monitoreo de actividades - Model3 + jqGrid.
 *
 * @package auditoria.FRONT
 */

require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/aud_log_monitoreo.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

$obBD_conexion = new Class_Log_Conexion($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos;
$audEmpCod = isset($Ses_Emp_Cod) ? (int)$Ses_Emp_Cod : 0;
$audUsuCod = isset($Ses_Usu_Cod) ? (int)$Ses_Usu_Cod : 0;
$audSucCod = isset($Ses_Suc_Cod) ? (int)$Ses_Suc_Cod : 0;

/** JSON seguro (latin1 DB -> utf8) sin contaminar la respuesta AJAX */
if (!function_exists('aud_json_out')) {
	function aud_to_utf8_deep(&$input) {
		if (is_string($input)) {
			if ($input === '') {
				return;
			}
			if (function_exists('mb_check_encoding') && @mb_check_encoding($input, 'UTF-8')) {
				return;
			}
			if (function_exists('mb_convert_encoding')) {
				$input = @mb_convert_encoding($input, 'UTF-8', 'ISO-8859-1');
			} elseif (function_exists('utf8_encode')) {
				$input = @utf8_encode($input);
			}
			return;
		}
		if (is_array($input)) {
			foreach ($input as $k => $v) {
				aud_to_utf8_deep($input[$k]);
			}
		}
	}
	function aud_json_out($data) {
		@ini_set('display_errors', '0');
		aud_to_utf8_deep($data);
		$json = json_encode($data);
		echo ($json !== false) ? $json : '{"page":1,"total":1,"records":0,"rows":[]}';
	}
}

/** Detalle modal HTML */
if (isset($_POST['ajax']) && (string)$_POST['ajax'] === '1') {
	@ini_set('display_errors', '0');
	$logCod = isset($_POST['logCodigo']) ? (int)$_POST['logCodigo'] : 0;
	$rowLog = $logCod > 0 ? $obBD_con1->getRowConsulta(20, array($logCod, $audEmpCod), $obBD_conexion) : array();
	if (empty($rowLog) || empty($rowLog['Log_Cod'])) {
		echo error_alerta('No se encontro el detalle de la actividad', 1);
		$obBD_con1->liberar();
		$obBD_conexion->cerrar();
		exit();
	}
	$pares = aud_pares_interpretados($rowLog, $obBD_con1, $obBD_conexion);
	echo aud_html_detalle($rowLog, $pares);
	echo barra_estado(count($pares));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Listado jqGrid JSON */
if (isset($_REQUEST['listMonitoreoGridAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$fil_from = isset($_REQUEST['from']) ? trim($_REQUEST['from']) : '';
	$fil_to = isset($_REQUEST['to']) ? trim($_REQUEST['to']) : '';
	$fil_eve = isset($_REQUEST['eve']) ? (int)$_REQUEST['eve'] : 0;
	$fil_org = isset($_REQUEST['org']) ? (int)$_REQUEST['org'] : 0;
	$fil_dir = isset($_REQUEST['dir']) ? (int)$_REQUEST['dir'] : 0;
	$fil_pcs = isset($_REQUEST['pcs']) ? (int)$_REQUEST['pcs'] : 0;
	$fil_usu = isset($_REQUEST['usu']) ? (int)$_REQUEST['usu'] : 0;
	$fil_suc = isset($_REQUEST['suc']) ? (int)$_REQUEST['suc'] : 0;
	if ($fil_from === '' && $fil_to === '') {
		$fil_to = date('Y-m-d');
		$fil_from = date('Y-m-d', strtotime('-30 days'));
	}
	$page = isset($_REQUEST['page']) ? max(1, (int)$_REQUEST['page']) : 1;
	$pageSize = isset($_REQUEST['rows']) ? (int)$_REQUEST['rows'] : 25;
	if (!in_array($pageSize, array(10, 25, 50, 100, 200))) {
		$pageSize = 25;
	}
	// 0 emp,1 from,2 to,3 eve,4 mod,5 pcs,6 tab,7 usu,8 limit,9 offset,10 suc,11 dir
	$filtros = array($audEmpCod, $fil_from, $fil_to, $fil_eve, $fil_org, $fil_pcs, 0, $fil_usu, $pageSize, 0, $fil_suc, $fil_dir);
	$rowCount = $obBD_con1->getRowConsulta(13, $filtros, $obBD_conexion);
	$total = isset($rowCount['count']) ? (int)$rowCount['count'] : 0;
	$totalPages = $total > 0 ? (int)ceil($total / $pageSize) : 1;
	if ($page > $totalPages) {
		$page = $totalPages > 0 ? $totalPages : 1;
	}
	$offset = ($page - 1) * $pageSize;
	$filtros[9] = $offset;
	$Arr_Resultado = $obBD_con1->getArrayConsulta(12, $filtros, $obBD_conexion);
	if (!is_array($Arr_Resultado)) {
		$Arr_Resultado = array();
	}
	$rows = array();
	foreach ($Arr_Resultado as $row) {
		$arr = explode(' ', isset($row['Log_Fec']) ? $row['Log_Fec'] : '');
		$pares = aud_pares_interpretados($row, null, null);
		$rows[] = array(
			'id' => (int)$row['Log_Cod'],
			'Log_Cod' => (int)$row['Log_Cod'],
			'Fecha' => isset($arr[0]) ? $arr[0] : '',
			'Hora' => isset($arr[1]) ? $arr[1] : '',
			'Empresa' => aud_nombre_empresa($row),
			'Sucursal' => aud_nombre_sucursal($row),
			'Usuario' => aud_nombre_usuario($row),
			'Modulo' => aud_nombre_modulo($row),
			'Directorio' => aud_nombre_directorio($row),
			'Proceso' => aud_nombre_proceso($row),
			'Actividad' => aud_resumen_actividad($row),
			'Detalle' => aud_resumen_detalle($row, $pares)
		);
	}
	$resp = array(
		'page' => $page,
		'total' => $totalPages,
		'records' => $total,
		'rows' => $rows
	);
	aud_json_out($resp);
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Directorios por modulo (combo dependiente) */
if (isset($_REQUEST['listDirectoriosAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$fil_org = isset($_REQUEST['org']) ? (int)$_REQUEST['org'] : 0;
	$Arr_Dir = $obBD_con1->getArrayConsulta(30, array($audEmpCod, $fil_org), $obBD_conexion);
	if (!is_array($Arr_Dir)) {
		$Arr_Dir = array();
	}
	$out = array();
	foreach ($Arr_Dir as $d) {
		$out[] = array('Org_Cod' => (int)$d['Org_Cod'], 'Org_Des' => isset($d['Org_Des']) ? $d['Org_Des'] : ('Directorio '.$d['Org_Cod']));
	}
	aud_json_out(array('rows' => $out));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Procesos por directorio/modulo (combo dependiente) */
if (isset($_REQUEST['listProcesosAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$fil_org = isset($_REQUEST['org']) ? (int)$_REQUEST['org'] : 0;
	$fil_dir = isset($_REQUEST['dir']) ? (int)$_REQUEST['dir'] : 0;
	$Arr_Pcs = $obBD_con1->getArrayConsulta(26, array($audEmpCod, $fil_dir, $fil_org), $obBD_conexion);
	if (!is_array($Arr_Pcs)) {
		$Arr_Pcs = array();
	}
	$out = array();
	foreach ($Arr_Pcs as $p) {
		$lbl = !empty($p['Pcs_Lin']) ? $p['Pcs_Lin'] : (isset($p['Pcs_Nom']) ? $p['Pcs_Nom'] : ('Proceso '.$p['Pcs_Cod']));
		$out[] = array('Pcs_Cod' => (int)$p['Pcs_Cod'], 'Pcs_Lin' => $lbl);
	}
	aud_json_out(array('rows' => $out));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Usuarios de la empresa / sucursal (combo filtro) */
if (isset($_REQUEST['listUsuariosAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$fil_suc = isset($_REQUEST['suc']) ? (int)$_REQUEST['suc'] : 0;
	$Arr_Usu = $obBD_con1->getArrayConsulta(27, array($audEmpCod, $fil_suc), $obBD_conexion);
	if (!is_array($Arr_Usu)) {
		$Arr_Usu = array();
	}
	$out = array();
	foreach ($Arr_Usu as $u) {
		$un = trim(isset($u['Usu_Nom']) ? $u['Usu_Nom'] : '');
		if ($un === '') {
			$un = 'Usuario '.(int)$u['Usu_Cod'];
		}
		$out[] = array('Usu_Cod' => (int)$u['Usu_Cod'], 'Usu_Nom' => $un);
	}
	aud_json_out(array('rows' => $out));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Export CSV de todos los registros del filtro (tope 5000) */
if (isset($_REQUEST['exportMonitoreoCsv'])) {
	@ini_set('display_errors', '0');
	$fil_from = isset($_REQUEST['from']) ? trim($_REQUEST['from']) : '';
	$fil_to = isset($_REQUEST['to']) ? trim($_REQUEST['to']) : '';
	$fil_eve = isset($_REQUEST['eve']) ? (int)$_REQUEST['eve'] : 0;
	$fil_org = isset($_REQUEST['org']) ? (int)$_REQUEST['org'] : 0;
	$fil_dir = isset($_REQUEST['dir']) ? (int)$_REQUEST['dir'] : 0;
	$fil_pcs = isset($_REQUEST['pcs']) ? (int)$_REQUEST['pcs'] : 0;
	$fil_usu = isset($_REQUEST['usu']) ? (int)$_REQUEST['usu'] : 0;
	$fil_suc = isset($_REQUEST['suc']) ? (int)$_REQUEST['suc'] : 0;
	if ($fil_from === '' && $fil_to === '') {
		$fil_to = date('Y-m-d');
		$fil_from = date('Y-m-d', strtotime('-30 days'));
	}
	$filtros = array($audEmpCod, $fil_from, $fil_to, $fil_eve, $fil_org, $fil_pcs, 0, $fil_usu, 5000, 0, $fil_suc, $fil_dir);
	$Arr_Resultado = $obBD_con1->getArrayConsulta(31, $filtros, $obBD_conexion);
	if (!is_array($Arr_Resultado)) {
		$Arr_Resultado = array();
	}
	$fname = 'monitoreo_actividades_'.date('Ymd_His').'.csv';
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="'.$fname.'"');
	$out = fopen('php://output', 'w');
	if ($out) {
		fwrite($out, "\xEF\xBB\xBF");
		fputcsv($out, array('Id','Fecha','Hora','Empresa','Sucursal','Usuario','Modulo','Directorio','Proceso','Actividad','Detalle'), ';');
		foreach ($Arr_Resultado as $row) {
			$arr = explode(' ', isset($row['Log_Fec']) ? $row['Log_Fec'] : '');
			$pares = aud_pares_interpretados($row, null, null);
			fputcsv($out, array(
				isset($row['Log_Cod']) ? $row['Log_Cod'] : '',
				isset($arr[0]) ? $arr[0] : '',
				isset($arr[1]) ? $arr[1] : '',
				aud_nombre_empresa($row),
				aud_nombre_sucursal($row),
				aud_nombre_usuario($row),
				aud_nombre_modulo($row),
				aud_nombre_directorio($row),
				aud_nombre_proceso($row),
				aud_resumen_actividad($row),
				aud_resumen_detalle($row, $pares)
			), ';');
		}
		fclose($out);
	}
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

$pcsSim = array();
foreach (aud_sim_casos() as $cSim) {
	$pcsSim[$cSim['id']] = 0;
	foreach ($cSim['pcs_noms'] as $nomSim) {
		$rowSim = $obBD_con1->getRowConsulta(23, array($nomSim, $cSim['mod_like']), $obBD_conexion);
		if (aud_sim_modulo_valido($rowSim, $cSim)) {
			$pcsSim[$cSim['id']] = (int)$rowSim['Pcs_Cod'];
			break;
		}
	}
}
$pcsDemo = isset($pcsSim['comprobantes']) ? (int)$pcsSim['comprobantes'] : 0;
$pcsMan = isset($pcsSim['manifiesto']) ? (int)$pcsSim['manifiesto'] : 0;
$pcsTur = isset($pcsSim['turnos']) ? (int)$pcsSim['turnos'] : 0;
$pcsVis = isset($pcsSim['visitantes']) ? (int)$pcsSim['visitantes'] : 0;
$pcsEve = isset($pcsSim['eventos']) ? (int)$pcsSim['eventos'] : 0;
$pcsVen = isset($pcsSim['ventas']) ? (int)$pcsSim['ventas'] : 0;
$pcsCaj = isset($pcsSim['caja']) ? (int)$pcsSim['caja'] : 0;
$paramsDemo = array($audUsuCod, $audEmpCod, $audSucCod, $pcsDemo);
if (isset($_POST['simular']) && $_POST['simular'] == '1') {
	$obBD_con1->grabarv_registros("INSERT IGNORE INTO `auditoria`.`eventos` (`Eve_Cod`,`Eve_Ini`,`Eve_Des`) VALUES (1,'F','Fallido'),(2,'I','Insertar'),(3,'U','Actualizar'),(4,'D','Eliminar')", $obBD_conexion);
	$seedTabs = array(
		array('comprobantes', 'Comprobantes contables', 'Comprobantes'),
		array('asientos', 'Asientos contables', 'Asientos'),
		array('manifiesto', 'Manifiestos Relavera', 'Manifiestos'),
		array('manifiesto_turnos_cab', 'Turnos Relavera', 'Turnos'),
		array('manifiesto_turnos_det', 'Detalle de turnos Relavera', 'Detalle de turnos'),
		array('manifiesto_visitante', 'Visitantes Relavera', 'Visitantes'),
		array('manifiesto_evento', 'Eventos Relavera', 'Eventos'),
		array('ventas', 'Facturas de venta', 'Facturas de venta'),
		array('ventas_det', 'Detalle de facturas de venta', 'Detalle de venta'),
		array('caja_aper', 'Apertura y cierre de caja', 'Caja')
	);
	foreach ($seedTabs as $st) {
		$tn = addslashes($st[0]);
		$td = addslashes($st[1]);
		$ta = addslashes($st[2]);
		$obBD_con1->grabarv_registros("INSERT INTO `auditoria`.`tablas` (`Tab_Nom`,`Tab_Des`,`Tab_Ali`) SELECT '{$tn}','{$td}','{$ta}' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `auditoria`.`tablas` WHERE `Tab_Nom`='{$tn}')", $obBD_conexion);
		$obBD_con1->grabarv_registros("UPDATE `auditoria`.`tablas` SET `Tab_Ali`='{$ta}', `Tab_Des`='{$td}' WHERE `Tab_Nom`='{$tn}' AND (`Tab_Ali` IS NULL OR `Tab_Ali`='{$tn}')", $obBD_conexion);
	}
	if ($pcsDemo > 0) {
		$obBD_con1->grabarv_registros(sentencias(14, $paramsDemo), $obBD_conexion);
		$obBD_con1->grabarv_registros(sentencias(15, $paramsDemo), $obBD_conexion);
		$obBD_con1->grabarv_registros(sentencias(16, $paramsDemo), $obBD_conexion);
	}

	$hoy = date('Y-m-d');
	$finTur = date('Y-m-d', strtotime('+6 days'));
	$baseDemo = array($audUsuCod, $audEmpCod, $audSucCod);
	$demosRelavera = array(
		array($pcsEve, 'manifiesto_evento', 'I',
			'Man_ENom,Man_EFei,Man_EFef,Man_Ehor,Man_Vig,Man_EEst',
			'~Jornada Demo Relavera~,~'.$hoy.'~,~'.$finTur.'~,8,~S~,~A~',
			'Man_Eve=DEMO-EVE-1', 70),
		array($pcsVis, 'manifiesto_visitante', 'I',
			'MVis_Nac,MVis_Eci,MVis_Est,Man_Eve,MVis_Obs',
			'~ECUATORIANA~,~SOLTERO~,~A~,1,~INGRESO DEMO VISITANTE~',
			'MVis_Cod=DEMO-VIS-1', 60),
		array($pcsTur, 'manifiesto_turnos_cab', 'I',
			'Tur_Fei,Tur_Fef,Tur_Est',
			'~'.$hoy.'~,~'.$finTur.'~,~A~',
			'Tur_Cod=DEMO-TUR-1', 50),
		array($pcsTur, 'manifiesto_turnos_det', 'I',
			'Tud_Fec,Tud_Hin,Tud_Hfi,Tud_Cup,Tud_Est',
			'~'.$hoy.'~,~07:00:00~,~12:00:00~,15,~A~',
			'Tud_Cod=DEMO-TUD-1', 40),
		array($pcsMan, 'manifiesto', 'I',
			'Man_Num,Man_Fec,Man_Pes,Man_Pun,Man_Tip,Man_Est',
			'1001,~'.$hoy.' 08:30:00~,12500,3.00,~P~,~A~',
			'Man_Cod=DEMO-M-1001', 30),
		array($pcsMan, 'manifiesto', 'U',
			'Man_Pes,Man_Pun,Man_Obe',
			'13200,3.50,~AJUSTE DEMO RELAVERA~',
			'Man_Cod=DEMO-M-1001', 20),
		array($pcsTur, 'manifiesto_turnos_det', 'U',
			'Tud_Cup,Tud_Est',
			'12,~A~',
			'Tud_Cod=DEMO-TUD-1', 10),
		array($pcsVis, 'manifiesto_visitante', 'U',
			'MVis_Est,MVis_Obs',
			'~I~,~ANULACION DEMO VISITANTE~',
			'MVis_Cod=DEMO-VIS-1', 5),
		array($pcsVen, 'ventas', 'I',
			'Tic_Cod,Cli_Cod,Vet_Num,Vet_Des,Vet_Hor',
			'1,1,~001-001-000000001~,~'.$hoy.'~,~08:30:00~',
			'Vet_Cod=DEMO-V-1', 15),
		array($pcsVen, 'ventas_det', 'I',
			'Vet_Cod,Pro_Cod,Vet_Can,Vet_Pru,Vet_Imp',
			'1,1,2,10.00,20.00',
			'Vet_Cod=DEMO-V-1', 12),
		array($pcsCaj, 'caja_aper', 'I',
			'Pun_Cod,Caj_Fec,Caj_Hoi,Caj_Exi,Caj_Est,Caj_Gen',
			'1,~'.$hoy.'~,~08:00:00~,100.00,~A~,~N~',
			'Caj_Cod=DEMO-CAJ-1', 8),
		array($pcsCaj, 'caja_aper', 'U',
			'Caj_Fef,Caj_Hof,Caj_Est',
			'~'.$hoy.'~,~17:00:00~,~C~',
			'Caj_Cod=DEMO-CAJ-1', 6)
	);
	foreach ($demosRelavera as $d) {
		if ((int)$d[0] <= 0) {
			continue;
		}
		$fecDemo = date('Y-m-d H:i:s', time() - (int)$d[6]);
		$obBD_con1->grabarv_registros(sentencias(24, array(
			$baseDemo[0], $baseDemo[1], $baseDemo[2], $d[0],
			$d[1], $d[2], $d[3], $d[4], $d[5], $fecDemo
		)), $obBD_conexion);
	}
	if (isset($_POST['simular'])) {
		header('Location: '.$_SERVER['PHP_SELF']);
		exit();
	}
}

$fil_from = isset($_GET['from']) ? trim($_GET['from']) : '';
$fil_to = isset($_GET['to']) ? trim($_GET['to']) : '';
$fil_eve = isset($_GET['eve']) ? (int)$_GET['eve'] : 0;
$fil_org = isset($_GET['org']) ? (int)$_GET['org'] : 0;
$fil_dir = isset($_GET['dir']) ? (int)$_GET['dir'] : 0;
$fil_pcs = isset($_GET['pcs']) ? (int)$_GET['pcs'] : 0;
$fil_usu = isset($_GET['usu']) ? (int)$_GET['usu'] : 0;
$fil_suc = isset($_GET['suc']) ? (int)$_GET['suc'] : 0;
// Al entrar: ultimas actividades (ultimos 30 dias)
if ($fil_from === '' && $fil_to === '' && !isset($_GET['from']) && !isset($_GET['to'])) {
	$fil_to = date('Y-m-d');
	$fil_from = date('Y-m-d', strtotime('-30 days'));
}

$Arr_Eventos = $obBD_con1->getArrayConsulta(17, '', $obBD_conexion);
$Arr_Modulos = $obBD_con1->getArrayConsulta(25, array($audEmpCod), $obBD_conexion);
$Arr_Directorios = $obBD_con1->getArrayConsulta(30, array($audEmpCod, $fil_org), $obBD_conexion);
$Arr_Procesos = $obBD_con1->getArrayConsulta(26, array($audEmpCod, $fil_dir, $fil_org), $obBD_conexion);
$Arr_Usuarios = $obBD_con1->getArrayConsulta(27, array($audEmpCod, $fil_suc), $obBD_conexion);
$Arr_Sucursales = $obBD_con1->getArrayConsulta(28, array($audEmpCod), $obBD_conexion);
$rowSucCount = $obBD_con1->getRowConsulta(29, array($audEmpCod), $obBD_conexion);
$hasSucursales = (!empty($rowSucCount['count']) && (int)$rowSucCount['count'] > 1);
$rowCfgCount = $obBD_con1->getRowConsulta(32, array($audEmpCod), $obBD_conexion);
$audCfgCount = isset($rowCfgCount['count']) ? (int)$rowCfgCount['count'] : 0;
$audEstado = aud_estado_captura($audEmpCod, $audCfgCount);
if (!is_array($Arr_Eventos)) $Arr_Eventos = array();
if (!is_array($Arr_Modulos)) $Arr_Modulos = array();
if (!is_array($Arr_Directorios)) $Arr_Directorios = array();
if (!is_array($Arr_Procesos)) $Arr_Procesos = array();
if (!is_array($Arr_Usuarios)) $Arr_Usuarios = array();
if (!is_array($Arr_Sucursales)) $Arr_Sucursales = array();
?><!DOCTYPE html>
<html lang="es">
<head>
	<title><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'Auditoria'; ?></title>
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
	<?php require_once("../../mascaras/model3/estilos/estilos.php"); ?>
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<style type="text/css">
		/* Buscar por - fila compacta model3 */
		.aud-search-fieldset { margin-bottom: 8px; }
		.aud-search-row {
			display: table;
			width: 100%;
			table-layout: fixed;
			border-collapse: separate;
			border-spacing: 8px 0;
			margin: 0 -8px;
		}
		.aud-search-cell {
			display: table-cell;
			vertical-align: bottom;
			padding: 0;
		}
		.aud-search-cell-date { width: 118px; }
		.aud-search-cell-usu { width: 13%; }
		.aud-search-cell-suc { width: 12%; }
		.aud-search-cell-mod { width: 11%; }
		.aud-search-cell-dir { width: 11%; }
		.aud-search-cell-pcs { width: 12%; }
		.aud-search-cell-eve { width: 10%; }
		.aud-search-cell-actions {
			width: 148px;
			white-space: nowrap;
			text-align: left;
		}
		.aud-search-cell > label {
			display: block;
			margin: 0 0 3px 0;
			padding: 0;
			font-size: 10px;
			font-weight: 700;
			letter-spacing: 0.04em;
			text-transform: uppercase;
			color: #5b6f88;
			line-height: 1.2;
		}
		.aud-search-cell .form-control,
		.aud-search-cell select.form-control {
			width: 100%;
			height: 22px;
			padding: 2px 6px;
			font-size: 12px;
			line-height: 1.35;
		}
		.aud-search-cell .input-group {
			display: table;
			width: 100%;
			border-collapse: separate;
		}
		.aud-search-cell .input-group > .form-control {
			display: table-cell;
			width: 100%;
		}
		.aud-search-cell .input-group-addon {
			cursor: pointer;
			width: 1%;
			padding: 2px 8px;
			line-height: 18px;
			vertical-align: middle;
		}
		.aud-search-actions {
			padding-top: 0;
		}
		.aud-search-actions .btn {
			margin: 0 4px 0 0;
			vertical-align: middle;
		}
		.aud-search-hint {
			margin: 8px 0 0 0;
			padding: 0;
			font-size: 11px;
			color: #7a8b9e;
			line-height: 1.35;
		}
		.aud-search-hint strong { color: #445566; font-weight: 600; }
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

		.aud-toolbar-actions { margin: 0 0 8px 0; overflow: visible; position: relative; z-index: 20; }
		.aud-toolbar-right { float: right; white-space: nowrap; }
		.aud-toolbar-right > .btn { margin-left: 4px; vertical-align: middle; }
		.aud-col-wrap { position: relative; display: inline-block; margin-left: 4px; z-index: 10000; vertical-align: middle; }
		.aud-col-panel {
			display: none;
			position: absolute;
			right: 0;
			top: 100%;
			z-index: 10001;
			background: #fff;
			border: 1px solid #778899;
			padding: 8px 12px;
			min-width: 200px;
			text-align: left;
			box-shadow: 0 2px 8px rgba(0,0,0,.15);
		}
		.aud-col-panel label { display: block; margin: 4px 0; cursor: pointer; white-space: nowrap; font-weight: normal; font-size: 12px; }
		#ui-datepicker-div { z-index: 99999 !important; }

		@media (max-width: 1100px) {
			.aud-search-row,
			.aud-search-cell {
				display: block;
				width: 100% !important;
			}
			.aud-search-cell {
				margin-bottom: 8px;
			}
			.aud-search-cell-actions { width: 100% !important; }
		}

		/* Detalle modal - orden y densidad */
		.aud-detalle { padding: 2px 2px 6px; }
		.aud-det-head {
			display: table;
			width: 100%;
			margin: 0 0 12px 0;
			padding-bottom: 10px;
			border-bottom: 1px solid #d9e2ec;
		}
		.aud-det-head > * { display: table-cell; vertical-align: middle; }
		.aud-det-when { text-align: right; color: #5b6f88; font-size: 12px; white-space: nowrap; }
		.aud-det-when-main { font-weight: 600; color: #2f3b4c; }
		.aud-det-hora { font-weight: 500; color: #5b6f88; margin-left: 4px; }
		.aud-det-id {
			display: inline-block;
			margin-left: 10px;
			padding: 1px 7px;
			border-radius: 3px;
			background: #eef3f8;
			color: #5b6f88;
			font-size: 11px;
			font-weight: 600;
		}
		.aud-det-badge {
			display: inline-block;
			padding: 4px 10px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 700;
			letter-spacing: 0.04em;
			text-transform: uppercase;
			background: #e8eef5;
			color: #445566;
			border: 1px solid #c5d2e0;
		}
		.aud-det-badge-i { background: #e7f6ed; color: #1f7a45; border-color: #b7e0c6; }
		.aud-det-badge-u { background: #eaf2fb; color: #1f5f9a; border-color: #b7d0ea; }
		.aud-det-badge-d { background: #fdecea; color: #a12b22; border-color: #f0c2bd; }
		.aud-det-badge-f { background: #fff6e5; color: #8a5a00; border-color: #f0d7a0; }
		.aud-det-summary { margin: 0 0 14px 0; }
		.aud-det-title {
			margin: 0 0 6px 0;
			font-size: 14px;
			line-height: 1.35;
			color: #243447;
		}
		.aud-det-lead {
			margin: 0;
			font-size: 12px;
			line-height: 1.45;
			color: #5b6f88;
		}
		.aud-det-context { margin-bottom: 10px !important; }
		.aud-det-meta {
			display: table;
			width: 100%;
			border-collapse: separate;
			border-spacing: 10px 8px;
			margin: -4px -10px 0;
		}
		.aud-det-meta-item {
			display: inline-block;
			vertical-align: top;
			width: 48%;
			box-sizing: border-box;
			padding: 8px 10px;
			background: #f7f9fc;
			border: 1px solid #e3eaf2;
			border-radius: 4px;
			margin: 0 1% 8px 0;
		}
		.aud-det-meta-wide { width: 98%; }
		.aud-det-label {
			display: block;
			font-size: 10px;
			font-weight: 700;
			letter-spacing: 0.05em;
			text-transform: uppercase;
			color: #7a8b9e;
			margin-bottom: 3px;
		}
		.aud-det-value {
			display: block;
			font-size: 13px;
			font-weight: 600;
			color: #243447;
			word-break: break-word;
		}
		.aud-det-datos { margin-bottom: 0 !important; }
		.aud-det-empty {
			margin: 4px 0 0 0;
			padding: 10px 12px;
			background: #f7f9fc;
			border: 1px dashed #cfd9e6;
			border-radius: 4px;
			color: #6a7c90;
			font-size: 12px;
		}
		.aud-det-table { margin-bottom: 0 !important; font-size: 12px; }
		.aud-det-table > thead > tr > th {
			background: #eef3f8;
			color: #445566;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.03em;
			border-bottom-width: 1px !important;
		}
		.aud-det-col-dato { width: 38%; font-weight: 600; color: #334455; }
		.ui-dialog.exa-ui-dialog .ui-dialog-content { padding: 12px 14px 8px; }
		.ui-dialog.exa-ui-dialog .ui-dialog-buttonpane {
			margin-top: 0;
			padding: 8px 12px;
			border-top: 1px solid #d9e2ec;
			background: #f7f9fc;
		}
		.ui-dialog.exa-ui-dialog .ui-dialog-buttonpane .btn,
		.ui-dialog.exa-ui-dialog .ui-dialog-buttonset button {
			min-width: 88px;
		}
	</style>
</head>
<body>
<div class="panel panel-default panel-main exa-ui-panel exa-ui-fill-page">
	<div class="panel-heading exa-header">
		<h3 class="panel-title"><span class="glyphicon glyphicon-eye-open"></span> Monitorear actividades</h3>
	</div>
	<div class="panel-body exa-body">
		<div id="lista" class="row exa-ui-page-view">
			<div class="col-xs-12">
				<?php echo aud_html_banner_captura($audEstado); ?>
				<fieldset class="exa-fieldset aud-search-fieldset">
					<legend class="Titulos2">Buscar por</legend>
					<form id="frmFiltros" class="form-horizontal normal exa-ui-busqueda-filtros" onsubmit="return false;">
						<div class="aud-search-row">
							<div class="aud-search-cell aud-search-cell-date">
								<label for="from">Desde</label>
								<div class="input-group input-group-xs">
									<input name="from" type="text" id="from" class="form-control input-xs" value="<?php echo aud_h($fil_from); ?>" maxlength="10" placeholder="aaaa-mm-dd" autocomplete="off" />
									<span class="input-group-addon" id="btnFromCal" title="Abrir calendario"><span class="glyphicon glyphicon-calendar"></span></span>
								</div>
							</div>
							<div class="aud-search-cell aud-search-cell-date">
								<label for="to">Hasta</label>
								<div class="input-group input-group-xs">
									<input name="to" type="text" id="to" class="form-control input-xs" value="<?php echo aud_h($fil_to); ?>" maxlength="10" placeholder="aaaa-mm-dd" autocomplete="off" />
									<span class="input-group-addon" id="btnToCal" title="Abrir calendario"><span class="glyphicon glyphicon-calendar"></span></span>
								</div>
							</div>
							<div class="aud-search-cell aud-search-cell-usu">
								<label for="usu">Usuario</label>
								<select name="usu" id="usu" class="form-control input-xs" title="Filtrar por usuario">
									<option value="0">Todos</option>
									<?php foreach ($Arr_Usuarios as $u) {
										$un = trim(isset($u['Usu_Nom']) ? $u['Usu_Nom'] : '');
										if ($un === '') {
											$un = 'Usuario '.(int)$u['Usu_Cod'];
										}
									?>
									<option value="<?php echo (int)$u['Usu_Cod']; ?>"<?php echo $fil_usu==(int)$u['Usu_Cod']?' selected="selected"':''; ?>><?php echo aud_h($un); ?></option>
									<?php } ?>
								</select>
							</div>
							<?php if ($hasSucursales) { ?>
							<div class="aud-search-cell aud-search-cell-suc">
								<label for="suc">Sucursal</label>
								<select name="suc" id="suc" class="form-control input-xs" title="Filtrar por sucursal">
									<option value="0">Todas</option>
									<?php foreach ($Arr_Sucursales as $s) { ?>
									<option value="<?php echo (int)$s['Suc_Cod']; ?>"<?php echo $fil_suc==(int)$s['Suc_Cod']?' selected="selected"':''; ?>><?php echo aud_h($s['Suc_Des']); ?></option>
									<?php } ?>
								</select>
							</div>
							<?php } ?>
							<div class="aud-search-cell aud-search-cell-mod">
								<label for="org">Modulo</label>
								<select name="org" id="org" class="form-control input-xs" title="Filtrar por modulo">
									<option value="0">Todos</option>
									<?php foreach ($Arr_Modulos as $m) { ?>
									<option value="<?php echo (int)$m['Org_Cod']; ?>"<?php echo $fil_org==(int)$m['Org_Cod']?' selected="selected"':''; ?>><?php echo aud_h($m['Org_Des']); ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="aud-search-cell aud-search-cell-dir">
								<label for="dir">Directorio</label>
								<select name="dir" id="dir" class="form-control input-xs" title="Filtrar por directorio">
									<option value="0">Todos</option>
									<?php foreach ($Arr_Directorios as $d) { ?>
									<option value="<?php echo (int)$d['Org_Cod']; ?>"<?php echo $fil_dir==(int)$d['Org_Cod']?' selected="selected"':''; ?>><?php echo aud_h($d['Org_Des']); ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="aud-search-cell aud-search-cell-pcs">
								<label for="pcs">Proceso</label>
								<select name="pcs" id="pcs" class="form-control input-xs" title="Filtrar por proceso">
									<option value="0">Todos</option>
									<?php foreach ($Arr_Procesos as $p) {
										$pl = !empty($p['Pcs_Lin']) ? $p['Pcs_Lin'] : (isset($p['Pcs_Nom']) ? $p['Pcs_Nom'] : ('Proceso '.$p['Pcs_Cod']));
									?>
									<option value="<?php echo (int)$p['Pcs_Cod']; ?>"<?php echo $fil_pcs==(int)$p['Pcs_Cod']?' selected="selected"':''; ?>><?php echo aud_h($pl); ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="aud-search-cell aud-search-cell-eve">
								<label for="eve">Evento</label>
								<select name="eve" id="eve" class="form-control input-xs" title="Filtrar por tipo de evento">
									<option value="0">Todos</option>
									<?php foreach ($Arr_Eventos as $ev) { ?>
									<option value="<?php echo (int)$ev['Eve_Cod']; ?>"<?php echo $fil_eve==(int)$ev['Eve_Cod']?' selected="selected"':''; ?>><?php echo aud_h($ev['Eve_Des']); ?></option>
									<?php } ?>
								</select>
							</div>
							<div class="aud-search-cell aud-search-cell-actions">
								<label>&nbsp;</label>
								<div class="aud-search-actions">
									<button type="button" id="btnBuscar" class="btn btn-success btn-xs" title="Aplicar filtros">
										<span class="glyphicon glyphicon-search"></span> Buscar
									</button>
									<button type="button" id="btnLimpiar" class="btn btn-default btn-xs" title="Restablecer filtros (ultimos 30 dias)">
										<span class="glyphicon glyphicon-refresh"></span> Limpiar
									</button>
								</div>
							</div>
						</div>
						<p class="aud-search-hint" id="audSearchHint">Periodo por defecto: <strong>ultimos 30 dias</strong>. Cambie filtros y pulse Buscar.</p>
					</form>
				</fieldset>

				<div class="aud-toolbar-actions clearfix">
					<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="display:inline;" onsubmit="return confirm('Esto inserta actividad de demostracion (no es un movimiento real). Continuar?');">
						<input type="hidden" name="simular" value="1" />
						<button type="submit" class="btn btn-default btn-xs" title="Inserta actividad de demostracion (solo pruebas)">
							<span class="glyphicon glyphicon-plus"></span> Simular actividad
						</button>
					</form>
					<div class="aud-toolbar-right">
						<button type="button" id="btnExportExcel" class="btn btn-success btn-xs" title="Exportar a Excel">
							<span class="glyphicon glyphicon-download-alt"></span> Excel
						</button>
						<button type="button" id="btnExportPdf" class="btn btn-danger btn-xs" title="Exportar a PDF / Imprimir">
							<span class="glyphicon glyphicon-file"></span> PDF
						</button>
						<div class="aud-col-wrap" id="aud-col-wrap">
							<button type="button" id="aud-col-btn" class="btn btn-default btn-xs" title="Columnas visibles">
								<span class="glyphicon glyphicon-th-list"></span> Columnas
							</button>
							<div id="aud-col-panel" class="aud-col-panel">
								<strong>Columnas visibles</strong>
								<label><input type="checkbox" class="aud-col-toggle" data-col="Log_Cod" checked="checked" /> Id</label>
								<label><input type="checkbox" class="aud-col-toggle" data-col="Fecha" checked="checked" /> Fecha</label>
								<label><input type="checkbox" class="aud-col-toggle" data-col="Hora" checked="checked" /> Hora</label>
								<label><input type="checkbox" class="aud-col-toggle" data-col="Empresa" checked="checked" /> Empresa</label>
								<?php if ($hasSucursales) { ?>
								<label><input type="checkbox" class="aud-col-toggle" data-col="Sucursal" checked="checked" /> Sucursal</label>
								<?php } ?>
								<label><input type="checkbox" class="aud-col-toggle" data-col="Usuario" checked="checked" /> Usuario</label>
								<label><input type="checkbox" class="aud-col-toggle" data-col="Modulo" checked="checked" /> Modulo</label>
								<label><input type="checkbox" class="aud-col-toggle" data-col="Directorio" checked="checked" /> Directorio</label>
								<label><input type="checkbox" class="aud-col-toggle" data-col="Proceso" checked="checked" /> Proceso</label>
								<label><input type="checkbox" class="aud-col-toggle" data-col="Actividad" checked="checked" /> Actividad</label>
								<label><input type="checkbox" class="aud-col-toggle" data-col="Detalle" checked="checked" /> Detalle</label>
							</div>
						</div>
					</div>
				</div>

				<div class="exa-ui-grid-host">
					<table id="gridMonitoreo"></table>
					<div id="gridMonitoreoPager"></div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="detalleDialog" title="Detalle de la actividad" style="display:none;">
	<div id="detalleContenido"></div>
</div>

<script type="text/javascript">
var AUD_HAS_SUCURSALES = <?php echo $hasSucursales ? 'true' : 'false'; ?>;
</script>
<script type="text/ecmascript" src="../../Librerias/scripts/generales/jquery.PrintExport-1.0.big.js"></script>
<script type="text/javascript" src="../VALIDACIONES/aud_par_monitoreo.js?v=20260817c"></script>
</body>
</html>
<?php
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
?>

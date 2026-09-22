<?php
/**
 * Monitoreo de actividades - Model3 + jqGrid.
 *
 * @package auditoria.FRONT
 */

require_once dirname(__FILE__) . '/../../administrador/LOGICA/seguridad.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_log_monitoreo.php';
require_once dirname(__FILE__) . '/../../Librerias/procedimientos/almacenados_standar.php';

$Ses_Dat_Dis = !empty($Ses_Dat_Dis) ? $Ses_Dat_Dis : (!empty($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : 'exa');
if ($Ses_Dat_Dis === 'exa_master') {
	$Ses_Dat_Dis = 'exa';
}
$obBD_conexion = new Class_Log_Conexion($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos;
$audEmpCod = isset($Ses_Emp_Cod) ? (int)$Ses_Emp_Cod : (isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 1);
$audUsuCod = isset($Ses_Usu_Cod) ? (int)$Ses_Usu_Cod : (isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 1);
$audSucCod = isset($Ses_Suc_Cod) ? (int)$Ses_Suc_Cod : (isset($_SESSION['Ses_Suc_Cod']) ? (int)$_SESSION['Ses_Suc_Cod'] : 0);

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
			$clean = array();
			foreach ($input as $k => $v) {
				$cleanK = $k;
				if (is_string($cleanK) && $cleanK !== '') {
					if (function_exists('mb_check_encoding') && !@mb_check_encoding($cleanK, 'UTF-8')) {
						if (function_exists('mb_convert_encoding')) {
							$cleanK = @mb_convert_encoding($cleanK, 'UTF-8', 'ISO-8859-1');
						} elseif (function_exists('utf8_encode')) {
							$cleanK = @utf8_encode($cleanK);
						}
					}
				}
				aud_to_utf8_deep($v);
				$clean[$cleanK] = $v;
			}
			$input = $clean;
		}
	}
	function aud_json_out($data) {
		@ini_set('display_errors', '0');
		aud_to_utf8_deep($data);
		$json = json_encode($data);
		if ($json === false && defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
			$json = json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR);
		}
		echo ($json !== false) ? $json : (isset($data['page']) ? '{"page":1,"total":1,"records":0,"rows":[]}' : '{"success":false,"message":"Error de serializacion"}');
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
	$q = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';

	// 0 emp, 1 from, 2 to, 3 eve, 4 mod, 5 pcs, 6 tab, 7 usu, 8 limit, 9 offset, 10 suc, 11 dir, 12 q
	$filtros = array($audEmpCod, $fil_from, $fil_to, $fil_eve, $fil_org, $fil_pcs, 0, $fil_usu, $pageSize, 0, $fil_suc, $fil_dir, $q);
	$rowCount = $obBD_con1->getRowConsulta(13, $filtros, $obBD_conexion);
	$total = isset($rowCount['count']) ? (int)$rowCount['count'] : 0;
	$totalPages = $total > 0 ? (int)ceil($total / $pageSize) : 1;
	if ($page > $totalPages) {
		$page = $totalPages > 0 ? $totalPages : 1;
	}
	$offset = ($page - 1) * $pageSize;
	$filtros[8] = $pageSize;
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

/** Dashboard KPI rapido AJAX */
if (isset($_REQUEST['listMonitoreoKpiAjax'])) {
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
	$q = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
	$filtros = array($audEmpCod, $fil_from, $fil_to, $fil_eve, $fil_org, $fil_pcs, 0, $fil_usu, 0, 0, $fil_suc, $fil_dir, $q);

	$rowCount = $obBD_con1->getRowConsulta(13, $filtros, $obBD_conexion);
	$totalMov = isset($rowCount['count']) ? (int)$rowCount['count'] : 0;

	$arrEve = $obBD_con1->getArrayConsulta(33, $filtros, $obBD_conexion);
	$ins = 0; $upd = 0; $del = 0;
	if (is_array($arrEve)) {
		foreach ($arrEve as $evRow) {
			$c = strtoupper(trim(isset($evRow['Eve_Ini']) ? $evRow['Eve_Ini'] : ''));
			$cnt = (int)$evRow['total'];
			if ($c === 'I') $ins += $cnt;
			elseif ($c === 'U') $upd += $cnt;
			elseif ($c === 'D') $del += $cnt;
		}
	}

	$arrFechas = $obBD_con1->getArrayConsulta(34, $filtros, $obBD_conexion);
	$arrMods = $obBD_con1->getArrayConsulta(35, $filtros, $obBD_conexion);
	$arrUsus = $obBD_con1->getArrayConsulta(36, $filtros, $obBD_conexion);

	$kpiData = array(
		'total' => $totalMov,
		'ins' => $ins,
		'upd' => $upd,
		'del' => $del,
		'ins_pct' => $totalMov > 0 ? round(($ins / $totalMov) * 100, 1) : 0,
		'upd_pct' => $totalMov > 0 ? round(($upd / $totalMov) * 100, 1) : 0,
		'del_pct' => $totalMov > 0 ? round(($del / $totalMov) * 100, 1) : 0,
		'fechas' => is_array($arrFechas) ? $arrFechas : array(),
		'modulos' => is_array($arrMods) ? $arrMods : array(),
		'usuarios' => is_array($arrUsus) ? $arrUsus : array()
	);

	aud_json_out($kpiData);
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

/** Graficos comparativos de auditoria AJAX */
if (isset($_REQUEST['graficosComparativosAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	require_once dirname(__FILE__) . '/../LOGICA/aud_log_dashboard.php';

	$fil_from = isset($_REQUEST['from']) ? trim($_REQUEST['from']) : '';
	$fil_to = isset($_REQUEST['to']) ? trim($_REQUEST['to']) : '';
	if ($fil_from === '' && $fil_to === '') {
		$fil_to = date('Y-m-d');
		$fil_from = date('Y-m-d', strtotime('-30 days'));
	}

	$tsFrom = strtotime($fil_from . ' 00:00:00');
	$tsTo = strtotime($fil_to . ' 23:59:59');
	if (!$tsFrom || !$tsTo || $tsTo < $tsFrom) {
		$tsTo = time();
		$tsFrom = strtotime('-30 days', $tsTo);
	}
	$diffSec = max(86400, $tsTo - $tsFrom);
	$prevToTs = $tsFrom - 1;
	$prevFromTs = $prevToTs - $diffSec;

	$pPrevIni = date('Y-m-d 00:00:00', $prevFromTs);
	$pPrevFin = date('Y-m-d 23:59:59', $prevToTs);
	$pCurrIni = date('Y-m-d 00:00:00', $tsFrom);
	$pCurrFin = date('Y-m-d 23:59:59', $tsTo);

	$conDb = isset($obBD_conexion->conexion) ? $obBD_conexion->conexion : null;
	$comp = aud_dash_calcular_comparativa($audEmpCod, $pPrevIni, $pPrevFin, $pCurrIni, $pCurrFin, $conDb);

	aud_json_out(array('success' => true, 'data' => $comp));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Export PDF oficial de los registros del filtro (tope 1000) */
if (isset($_REQUEST['exportMonitoreoPdf'])) {
	@ini_set('display_errors', '0');
	require_once dirname(__FILE__) . '/../LOGICA/aud_rep_monitoreo_pdf.php';

	$fil_from = isset($_REQUEST['from']) ? trim($_REQUEST['from']) : '';
	$fil_to = isset($_REQUEST['to']) ? trim($_REQUEST['to']) : '';
	$fil_eve = isset($_REQUEST['eve']) ? (int)$_REQUEST['eve'] : 0;
	$fil_org = isset($_REQUEST['org']) ? (int)$_REQUEST['org'] : 0;
	$fil_dir = isset($_REQUEST['dir']) ? (int)$_REQUEST['dir'] : 0;
	$fil_pcs = isset($_REQUEST['pcs']) ? (int)$_REQUEST['pcs'] : 0;
	$fil_usu = isset($_REQUEST['usu']) ? (int)$_REQUEST['usu'] : 0;
	$fil_suc = isset($_REQUEST['suc']) ? (int)$_REQUEST['suc'] : 0;
	$q = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';

	if ($fil_from === '' && $fil_to === '') {
		$fil_to = date('Y-m-d');
		$fil_from = date('Y-m-d', strtotime('-30 days'));
	}

	$filtros = array($audEmpCod, $fil_from, $fil_to, $fil_eve, $fil_org, $fil_pcs, 0, $fil_usu, 1000, 0, $fil_suc, $fil_dir, $q);
	$Arr_Resultado = $obBD_con1->getArrayConsulta(31, $filtros, $obBD_conexion);
	if (!is_array($Arr_Resultado)) {
		$Arr_Resultado = array();
	}

	$sucNom = 'Todas las Sucursales';
	if ($fil_suc > 0) {
		$Arr_Sucursales = $obBD_con1->getArrayConsulta(28, array($audEmpCod), $obBD_conexion);
		if (is_array($Arr_Sucursales)) {
			foreach ($Arr_Sucursales as $s) {
				if ((int)$s['Suc_Cod'] === $fil_suc) {
					$sucNom = $s['Suc_Des'];
					break;
				}
			}
		}
	}

	$filtroData = array(
		'empresa_nombre' => isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : 'Empresa Principal',
		'sucursal_nombre' => $sucNom,
		'periodo_label' => 'Desde: ' . $fil_from . '   Hasta: ' . $fil_to,
		'usuario_emisor' => isset($_SESSION['Ses_Usu_Nom']) ? $_SESSION['Ses_Usu_Nom'] : 'Administrador',
		'total_registros' => count($Arr_Resultado)
	);

	aud_generar_reporte_monitoreo_pdf($filtroData, $Arr_Resultado, 'I');
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
	$delim = ';';
	$filename = 'monitoreo_actividades_' . date('Ymd_His') . '.csv';
	header('Content-Type: text/csv; charset=ISO-8859-1');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	$out = fopen('php://output', 'w');
	fputcsv($out, array('Id', 'Fecha', 'Hora', 'Empresa', 'Sucursal', 'Usuario', 'Modulo', 'Directorio', 'Proceso', 'Actividad', 'Detalle'), $delim);
	foreach ($Arr_Resultado as $r) {
		$det = isset($r['Log_Des']) ? $r['Log_Des'] : '';
		if ($det !== '') {
			$det = str_replace(array("\r\n", "\r", "\n"), ' ', strip_tags($det));
		}
		$line = array(
			isset($r['Log_Cod']) ? $r['Log_Cod'] : '',
			isset($r['Log_Fec']) ? $r['Log_Fec'] : '',
			isset($r['Log_Hor']) ? $r['Log_Hor'] : '',
			isset($r['Emp_Nom']) ? $r['Emp_Nom'] : '',
			isset($r['Suc_Nom']) ? $r['Suc_Nom'] : '',
			isset($r['Usu_Nom']) ? $r['Usu_Nom'] : '',
			isset($r['Mod_Nom']) ? $r['Mod_Nom'] : '',
			isset($r['Dir_Nom']) ? $r['Dir_Nom'] : '',
			isset($r['Pcs_Nom']) ? $r['Pcs_Nom'] : '',
			isset($r['Eve_Nom']) ? $r['Eve_Nom'] : '',
			$det
		);
		fputcsv($out, $line, $delim);
	}
	fclose($out);
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

$Arr_Organigrama = $obBD_con1->getArrayConsulta(25, array($audEmpCod), $obBD_conexion);
if (!is_array($Arr_Organigrama)) {
	$Arr_Organigrama = array();
}
$Arr_Directorios = $obBD_con1->getArrayConsulta(30, array($audEmpCod, 0), $obBD_conexion);
if (!is_array($Arr_Directorios)) {
	$Arr_Directorios = array();
}
$Arr_Procesos = $obBD_con1->getArrayConsulta(26, array($audEmpCod, 0, 0), $obBD_conexion);
if (!is_array($Arr_Procesos)) {
	$Arr_Procesos = array();
}
$Arr_Eventos = $obBD_con1->getArrayConsulta(24, array($audEmpCod), $obBD_conexion);
if (!is_array($Arr_Eventos)) {
	$Arr_Eventos = array();
}
$Arr_Usuarios = $obBD_con1->getArrayConsulta(27, array($audEmpCod, 0), $obBD_conexion);
if (!is_array($Arr_Usuarios)) {
	$Arr_Usuarios = array();
}

$Arr_Sucursales = array();
$hasSucursales = false;
$resSuc = $obBD_con1->consulta("SHOW TABLES LIKE 'sucursal'", $obBD_conexion->conexion);
if ($resSuc && $obBD_con1->num_rows($resSuc) > 0) {
	$Arr_Sucursales = $obBD_con1->getArrayConsulta(28, array($audEmpCod), $obBD_conexion);
	if (is_array($Arr_Sucursales) && count($Arr_Sucursales) > 0) {
		$hasSucursales = true;
	}
}


$pcsSim = array();
if (function_exists('aud_sim_casos')) {
	foreach (aud_sim_casos() as $cSim) {
		$pcsSim[$cSim['id']] = 0;
		foreach ($cSim['pcs_noms'] as $nomSim) {
			$rowSim = $obBD_con1->getRowConsulta(23, array($nomSim, $cSim['mod_like']), $obBD_conexion);
			if (function_exists('aud_sim_modulo_valido') && aud_sim_modulo_valido($rowSim, $cSim)) {
				$pcsSim[$cSim['id']] = (int)$rowSim['Pcs_Cod'];
				break;
			}
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
	$obBD_con1->grabarv_registros("INSERT IGNORE INTO `auditoria`.`eventos` (`Eve_Cod`,`Eve_Ini`,`Eve_Des`) VALUES (1,'F','Fallido'),(2,'I','Ingresar'),(3,'U','Actualizar'),(4,'D','Eliminar')", $obBD_conexion);
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

$rowCfgCount = $obBD_con1->getRowConsulta(32, array($audEmpCod), $obBD_conexion);
$audCfgCount = isset($rowCfgCount['count']) ? (int)$rowCfgCount['count'] : 0;
$audEstado = function_exists('aud_estado_captura') ? aud_estado_captura($audEmpCod, $audCfgCount) : array('activo' => true);

$defaultTo = date('Y-m-d');
$defaultFrom = date('Y-m-d', strtotime('-30 days'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
	<title>Monitoreo de actividades</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

	<!-- CSS Exa UI & jqGrid Model3 Oficial -->
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
	<?php require_once("../../mascaras/model3/estilos/estilos.php"); ?>

	<style>
		/* Estilos armonizados con el tema ExaContable */
		.aud-filter-card {
			background: #fdfefe;
			border: 1px solid #d0dbe5;
			border-radius: 4px;
			padding: 10px 14px;
			margin-bottom: 8px;
		}
		.aud-filter-card .form-group {
			margin-bottom: 6px;
		}
		.aud-filter-card label {
			font-size: 11px;
			font-weight: 700;
			color: #3b5066;
			margin-bottom: 2px;
		}
		.aud-filter-card .form-control {
			height: 28px;
			padding: 3px 8px;
			font-size: 12px;
			border-radius: 3px;
		}
		.aud-filter-card .input-group-addon {
			padding: 3px 8px;
			font-size: 12px;
		}
		.aud-btn-preset {
			font-size: 11px;
			padding: 3px 8px;
			border-radius: 3px;
		}
		.aud-col-menu {
			position: absolute;
			right: 0;
			top: 100%;
			z-index: 1050;
			min-width: 220px;
			padding: 8px 12px;
			margin-top: 2px;
			background: #fff;
			border: 1px solid #cbd5e1;
			border-radius: 4px;
			box-shadow: 0 4px 12px rgba(0,0,0,0.12);
			font-size: 12px;
			display: none;
		}
		.aud-col-menu label {
			display: block;
			font-weight: normal;
			margin-bottom: 4px;
			cursor: pointer;
		}
		.aud-kpi-card {
			background: #fff;
			border: 1px solid #d0dbe5;
			border-radius: 4px;
			padding: 10px 14px;
			margin-bottom: 8px;
			box-shadow: 0 1px 2px rgba(0,0,0,0.03);
		}
		.aud-kpi-val {
			font-size: 22px;
			font-weight: 700;
			line-height: 1.1;
		}
		.aud-kpi-lbl {
			font-size: 11px;
			color: #64748b;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			font-weight: 600;
		}
		.aud-kpi-sub {
			font-size: 11px;
			color: #94a3b8;
			margin-top: 2px;
		}
		.aud-sparkline-svg {
			width: 100%;
			height: 90px;
			overflow: visible;
		}
		.aud-bar-item {
			margin-bottom: 6px;
			font-size: 11px;
		}
		.aud-bar-lbl {
			margin-bottom: 2px;
		}
		.aud-bar-track {
			height: 8px;
			background: #f1f5f9;
			border-radius: 4px;
			overflow: hidden;
		}
		.aud-bar-fill {
			height: 100%;
			border-radius: 4px;
			transition: width 0.3s ease;
		}
		/* Grilla compacta Model3 */
		.ui-jqgrid .ui-jqgrid-bdiv {
			overflow-x: auto !important;
		}
		.ui-jqgrid tr.jqgrow td {
			font-size: 12px;
			padding: 4px 6px;
			white-space: normal !important;
			word-break: break-word;
			vertical-align: middle;
		}
		.ui-jqgrid .ui-jqgrid-htable th div {
			font-size: 12px;
			font-weight: 600;
		}
		.ui-jqgrid .ui-jqgrid-titlebar {
			display: none;
		}
		.badge-eve {
			font-size: 11px;
			padding: 2px 6px;
			font-weight: 600;
			border-radius: 3px;
			display: inline-block;
		}
		.badge-eve-ins { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
		.badge-eve-upd { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
		.badge-eve-del { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
		.badge-eve-def { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
			/* Detalle Modal de Actividad - Armonizado con el ERP */
		.aud-detalle { padding: 4px 2px 8px; }
		.aud-det-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
		.aud-table-context th { width: 16%; background: #f1f5f9; color: #334e68; font-weight: 600; font-size: 12px; vertical-align: middle !important; }
		.aud-table-context td { width: 34%; font-size: 12px; vertical-align: middle !important; }
		.aud-det-summary { margin-bottom: 12px; padding: 10px 14px; background: #f8fafc; border-left: 4px solid #3b82f6; border-radius: 4px; }
		.aud-det-table th { background: #f1f5f9; color: #334e68; font-size: 11px; text-transform: uppercase; }
		.aud-det-val-old.aud-det-val-changed { color: #b91c1c; text-decoration: line-through; background: #fef2f2; }
		.aud-det-val-new.aud-det-val-changed { color: #15803d; background: #f0fdf4; font-weight: 600; }
		.ui-dialog.exa-ui-dialog { box-shadow: 0 10px 25px rgba(0,0,0,0.2); border-radius: 6px; overflow: hidden; border: 1px solid #cbd5e1; }
		.ui-dialog.exa-ui-dialog .ui-dialog-titlebar { background: #1e3a5f; color: #fff; border-radius: 5px 5px 0 0; padding: 8px 14px; }
		.ui-dialog.exa-ui-dialog .ui-dialog-title { font-size: 13px; font-weight: 700; color: #fff; }
		.ui-dialog.exa-ui-dialog .ui-dialog-titlebar-close { background: transparent; border: none; color: #fff; }
		.ui-dialog.exa-ui-dialog .ui-dialog-content { padding: 14px 18px 8px; }
		.ui-dialog.exa-ui-dialog .ui-dialog-buttonpane { margin-top: 0; padding: 8px 14px; border-top: 1px solid #e2e8f0; background: #f8fafc; }
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
		<div class="row" style="display: flex; align-items: center; justify-content: space-between;">
			<div class="col-xs-12 col-sm-6">
				<h3 class="panel-title" style="font-size: 14px; font-weight: 700; color: #ffffff !important;">
					<i class="fa fa-list-alt" style="color: #ffffff; margin-right: 4px;"></i> Registro de Actividades y Transacciones
				</h3>
			</div>
			<div class="col-xs-12 col-sm-6 text-right">
				<div class="btn-group btn-group-sm">
					<button type="button" id="btnToggleKpi" class="btn btn-default btn-sm" title="Mostrar/ocultar panel de KPIs">
						<i class="fa fa-tachometer text-info"></i> Resumen KPI
					</button>
					<a href="#" id="btnExportCsv" class="btn btn-default btn-sm" title="Descargar CSV con el filtro actual">
						<i class="fa fa-download text-success"></i> Exportar CSV
					</a>
					<a href="#" id="btnExportPdf" class="btn btn-default btn-sm" title="Descargar PDF con el filtro actual">
						<i class="fa fa-file-pdf-o text-danger"></i> Exportar PDF
					</a>
					<div class="btn-group btn-group-sm" id="aud-col-wrap" style="display:inline-block; vertical-align:middle;">
						<button type="button" id="aud-col-btn" class="btn btn-default btn-sm dropdown-toggle" title="Seleccionar columnas visibles">
							<i class="fa fa-columns text-warning"></i> Columnas <span class="caret"></span>
						</button>
						<div id="aud-col-panel" class="aud-col-menu">
							<strong style="display:block; margin-bottom:6px; font-size:11px; color:#475569; text-transform:uppercase;">Visibilidad</strong>
							<label><input type="checkbox" class="aud-col-toggle" data-col="Log_Cod" checked> Id</label>
							<label><input type="checkbox" class="aud-col-toggle" data-col="Fecha" checked> Fecha</label>
							<label><input type="checkbox" class="aud-col-toggle" data-col="Hora" checked> Hora</label>
							<label><input type="checkbox" class="aud-col-toggle" data-col="Empresa" checked> Empresa</label>
							<?php if ($hasSucursales) { ?>
							<label><input type="checkbox" class="aud-col-toggle" data-col="Sucursal" checked> Sucursal</label>
							<?php } ?>
							<label><input type="checkbox" class="aud-col-toggle" data-col="Usuario" checked> Usuario</label>
							<label><input type="checkbox" class="aud-col-toggle" data-col="Modulo" checked> M&oacute;dulo</label>
							<label><input type="checkbox" class="aud-col-toggle" data-col="Directorio" checked> Directorio</label>
							<label><input type="checkbox" class="aud-col-toggle" data-col="Proceso" checked> Proceso</label>
							<label><input type="checkbox" class="aud-col-toggle" data-col="Actividad" checked> Actividad</label>
							<label><input type="checkbox" class="aud-col-toggle" data-col="Detalle" checked> Detalle</label>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="panel-body exa-body" style="padding: 10px 14px;">
		<?php if (function_exists('aud_html_banner_captura')) { echo aud_html_banner_captura($audEstado); } ?>
		<div id="audSearchHint" style="font-size: 11px; color: #64748b; margin-bottom: 6px;"></div>

		<div id="lista" class="row exa-ui-page-view">
			<div class="col-xs-12">

				<!-- Panel Colapsable de KPIs con terminos Ingresar, Actualizar, Eliminar -->
				<div id="aud-kpi-panel" style="display:none; margin-bottom: 8px;">
					<div class="row">
						<div class="col-xs-6 col-sm-3">
							<div class="aud-kpi-card" style="border-left: 3px solid #2563eb;">
								<div class="aud-kpi-lbl"><i class="fa fa-database text-primary"></i> Total Actividades</div>
								<div class="aud-kpi-val text-primary" id="kpi-total">0</div>
								<div class="aud-kpi-sub">en el per&iacute;odo filtrado</div>
							</div>
						</div>
						<div class="col-xs-6 col-sm-3">
							<div class="aud-kpi-card" style="border-left: 3px solid #10b981;">
								<div class="aud-kpi-lbl"><i class="fa fa-plus-circle text-success"></i> Ingresos</div>
								<div class="aud-kpi-val text-success" id="kpi-ins">0</div>
								<div class="aud-kpi-sub" id="kpi-ins-pct">0% del total</div>
							</div>
						</div>
						<div class="col-xs-6 col-sm-3">
							<div class="aud-kpi-card" style="border-left: 3px solid #f59e0b;">
								<div class="aud-kpi-lbl"><i class="fa fa-pencil text-warning"></i> Actualizaciones</div>
								<div class="aud-kpi-val text-warning" id="kpi-upd">0</div>
								<div class="aud-kpi-sub" id="kpi-upd-pct">0% del total</div>
							</div>
						</div>
						<div class="col-xs-6 col-sm-3">
							<div class="aud-kpi-card" style="border-left: 3px solid #ef4444;">
								<div class="aud-kpi-lbl"><i class="fa fa-trash text-danger"></i> Eliminaciones</div>
								<div class="aud-kpi-val text-danger" id="kpi-del">0</div>
								<div class="aud-kpi-sub" id="kpi-del-pct">0% del total</div>
							</div>
						</div>
					</div>
					<!-- Mini graficos -->
					<div class="row">
						<div class="col-xs-12 col-sm-5">
							<div class="aud-kpi-card" style="padding-bottom: 4px;">
								<div class="aud-kpi-lbl" style="margin-bottom: 4px;">Tendencia diaria</div>
								<div id="chart-fechas-host">
									<p class="text-muted" style="margin:0; font-size:11px; padding:20px 0; text-align:center;">Cargando...</p>
								</div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-4">
							<div class="aud-kpi-card">
								<div class="aud-kpi-lbl" style="margin-bottom: 6px;">Top M&oacute;dulos</div>
								<div id="chart-modulos-host">
									<p class="text-muted" style="margin:0; font-size:11px; padding:20px 0; text-align:center;">Cargando...</p>
								</div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-3">
							<div class="aud-kpi-card">
								<div class="aud-kpi-lbl" style="margin-bottom: 6px;">Top Usuarios</div>
								<div id="chart-usuarios-host">
									<p class="text-muted" style="margin:0; font-size:11px; padding:20px 0; text-align:center;">Cargando...</p>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Formulario de Filtros Compacto -->
				<div class="aud-filter-card">
					<form id="frmFiltros" onsubmit="return false;">
						<div class="row">
							<!-- Periodo: Presets y Calendario Desde / Hasta -->
							<div class="col-xs-12 col-sm-6 col-md-4">
								<div class="form-group" style="margin-bottom: 4px;">
									<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 3px;">
										<label style="margin-bottom: 0;"><i class="fa fa-calendar text-primary"></i> Per&iacute;odo de Auditor&iacute;a:</label>
										<span id="audPresetCustomBadge" class="label label-info" style="display:none; font-size:10px; padding: 2px 6px; font-weight:600;">Personalizado</span>
									</div>
									<div class="btn-group btn-group-xs" role="group" style="margin-bottom: 6px; display:flex; flex-wrap:wrap; gap:2px;">
										<button type="button" class="btn btn-default aud-btn-preset" data-preset="hoy">Hoy</button>
										<button type="button" class="btn btn-default aud-btn-preset" data-preset="ayer">Ayer</button>
										<button type="button" class="btn btn-default aud-btn-preset" data-preset="1semana">1 Semana</button>
										<button type="button" class="btn btn-primary active aud-btn-preset" data-preset="1mes">1 Mes</button>
										<button type="button" class="btn btn-default aud-btn-preset" data-preset="3meses">3 Meses</button>
									</div>
									<div class="row" style="margin-left:-4px; margin-right:-4px;">
										<div class="col-xs-6" style="padding-left:4px; padding-right:4px;">
											<div class="input-group input-group-sm">
												<span class="input-group-addon" style="padding: 2px 6px; font-size: 11px;">Desde</span>
												<input type="text" id="from" name="from" class="form-control text-center" value="<?php echo htmlspecialchars($defaultFrom); ?>" readonly style="background:#fff; cursor:pointer;" placeholder="AAAA-MM-DD" />
												<span class="input-group-addon" id="btnFromCal" style="cursor:pointer; padding: 2px 6px;" title="Seleccionar fecha en calendario"><i class="fa fa-calendar"></i></span>
											</div>
										</div>
										<div class="col-xs-6" style="padding-left:4px; padding-right:4px;">
											<div class="input-group input-group-sm">
												<span class="input-group-addon" style="padding: 2px 6px; font-size: 11px;">Hasta</span>
												<input type="text" id="to" name="to" class="form-control text-center" value="<?php echo htmlspecialchars($defaultTo); ?>" readonly style="background:#fff; cursor:pointer;" placeholder="AAAA-MM-DD" />
												<span class="input-group-addon" id="btnToCal" style="cursor:pointer; padding: 2px 6px;" title="Seleccionar fecha en calendario"><i class="fa fa-calendar"></i></span>
											</div>
										</div>
									</div>
								</div>
							</div>

							<!-- Filtros Combo: Modulo, Directorio, Proceso -->
							<div class="col-xs-12 col-sm-6 col-md-4">
								<div class="row">
									<div class="col-xs-4">
										<div class="form-group">
											<label for="org">M&oacute;dulo:</label>
											<select id="org" name="org" class="form-control">
												<option value="0">Todos</option>
												<?php foreach ($Arr_Organigrama as $o) { ?>
													<option value="<?php echo (int)$o['Org_Cod']; ?>"><?php echo htmlspecialchars($o['Org_Des']); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="col-xs-4">
										<div class="form-group">
											<label for="dir">Directorio:</label>
											<select id="dir" name="dir" class="form-control">
												<option value="0">Todos</option>
												<?php foreach ($Arr_Directorios as $d) { ?>
													<option value="<?php echo (int)$d['Org_Cod']; ?>"><?php echo htmlspecialchars($d['Org_Des']); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="col-xs-4">
										<div class="form-group">
											<label for="pcs">Proceso:</label>
											<select id="pcs" name="pcs" class="form-control">
												<option value="0">Todos</option>
												<?php foreach ($Arr_Procesos as $p) { ?>
													<option value="<?php echo (int)$p['Pcs_Cod']; ?>"><?php echo htmlspecialchars(!empty($p['Pcs_Lin']) ? $p['Pcs_Lin'] : $p['Pcs_Nom']); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-xs-6">
										<div class="form-group" style="margin-bottom:0;">
											<label for="eve">Evento:</label>
											<select id="eve" name="eve" class="form-control">
												<option value="0">Todos</option>
												<?php foreach ($Arr_Eventos as $e) { ?>
													<option value="<?php echo (int)$e['Eve_Cod']; ?>"><?php echo htmlspecialchars($e['Eve_Des']); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="col-xs-6">
										<div class="form-group" style="margin-bottom:0;">
											<label for="usu">Usuario:</label>
											<select id="usu" name="usu" class="form-control">
												<option value="0">Todos</option>
												<?php foreach ($Arr_Usuarios as $u) { ?>
													<option value="<?php echo (int)$u['Usu_Cod']; ?>"><?php echo htmlspecialchars($u['Usu_Nom']); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
								</div>
							</div>

							<!-- Filtros Combo: Sucursal, Busqueda texto y Botonera -->
							<div class="col-xs-12 col-sm-12 col-md-4">
								<div class="row">
									<?php if ($hasSucursales) { ?>
									<div class="col-xs-6">
										<div class="form-group">
											<label for="suc">Sucursal:</label>
											<select id="suc" name="suc" class="form-control">
												<option value="0">Todas</option>
												<?php foreach ($Arr_Sucursales as $s) { ?>
													<option value="<?php echo (int)$s['Suc_Cod']; ?>"><?php echo htmlspecialchars(!empty($s['Suc_Des']) ? $s['Suc_Des'] : (!empty($s['Suc_Nom']) ? $s['Suc_Nom'] : 'Sucursal ' . $s['Suc_Cod'])); ?></option>
												<?php } ?>
											</select>
										</div>
									</div>
									<div class="col-xs-6">
									<?php } else { ?>
									<div class="col-xs-12">
									<?php } ?>
										<div class="form-group">
											<label for="fil_q">Buscar en detalle:</label>
											<input type="text" id="fil_q" name="q" class="form-control" placeholder="Texto en detalle / IP / sentencia..." />
										</div>
									</div>
								</div>
								<div class="text-right" style="margin-top: 4px;">
									<button type="button" id="btnBuscar" class="btn btn-primary btn-sm" style="padding: 3px 12px; font-weight:600;">
										<i class="fa fa-search"></i> Buscar
									</button>
									<button type="button" id="btnLimpiar" class="btn btn-default btn-sm" style="padding: 3px 10px;">
										<i class="fa fa-eraser"></i> Limpiar
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>

				<!-- Host jqGrid Model3 -->
				<div id="listaGridHost" class="exa-ui-panel" style="border: 1px solid #d0dbe5; border-radius: 4px; padding: 4px; background: #fff;">
					<div class="exa-ui-grid-host">
						<table id="gridMonitoreo"></table>
						<div id="gridMonitoreoPager"></div>
					</div>
				</div>

			</div>
		</div>
	</div>
</div>

<!-- Modal detalle actividad -->
<div id="detalleDialog" title="Detalle de actividad de auditoria" style="display:none;">
	<div id="detalleContenido" style="padding: 4px 0;"></div>
</div>

<!-- Scripts requeridos (JQuery, Bootstrap, JQueryUI y JQGrid ya cargados por jqgrid5.php) -->
<script>
	var AUD_HAS_SUCURSALES = <?php echo $hasSucursales ? 'true' : 'false'; ?>;
</script>
<script src="../VALIDACIONES/aud_par_monitoreo.js"></script>
</body>
</html>

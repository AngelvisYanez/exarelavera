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

require_once('../LOGICA/aud_log_acceso_directorio.php');
aud_acceso_directorio_gate($audEmpCod);

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
	echo aud_html_detalle_tabs($rowLog, $pares, $obBD_con1, $obBD_conexion);
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Resumen automatizado del filtro actual (misma consulta que el informe PDF). */
if (isset($_REQUEST['resumenMonitoreoAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$fil_from = isset($_REQUEST['from']) ? trim($_REQUEST['from']) : '';
	$fil_to = isset($_REQUEST['to']) ? trim($_REQUEST['to']) : '';
	$fil_eve = isset($_REQUEST['eve']) ? (int)$_REQUEST['eve'] : 0;
	$fil_org = isset($_REQUEST['org']) ? (int)$_REQUEST['org'] : 0;
	$fil_dir = isset($_REQUEST['dir']) ? (int)$_REQUEST['dir'] : 0;
	$fil_pcs = isset($_REQUEST['pcs']) ? (int)$_REQUEST['pcs'] : 0;
	$fil_usu = aud_usu_list(isset($_REQUEST['usu']) ? $_REQUEST['usu'] : 0);
	$fil_suc = isset($_REQUEST['suc']) ? (int)$_REQUEST['suc'] : 0;
	$fil_pla = isset($_REQUEST['pla']) ? (int)$_REQUEST['pla'] : 0;
	if ($fil_from === '' && $fil_to === '') {
		$fil_to = date('Y-m-d');
		$fil_from = date('Y-m-d', strtotime('-30 days'));
	}
	$filtros = array($audEmpCod, $fil_from, $fil_to, $fil_eve, $fil_org, $fil_pcs, 0, $fil_usu, 5000, 0, $fil_suc, $fil_dir, $fil_pla);
	$Arr_Res = $obBD_con1->getArrayConsulta(31, $filtros, $obBD_conexion);
	if (!is_array($Arr_Res)) {
		$Arr_Res = array();
	}
	$total = count($Arr_Res);
	$porEvento = array('I' => 0, 'U' => 0, 'D' => 0, 'F' => 0);
	$usuDistintos = array();
	$topModulos = array();
	$topUsuarios = array();
	$topProcesos = array();
	$iniFec = '';
	$finFec = '';
	foreach ($Arr_Res as $r) {
		$ini = strtoupper(trim(isset($r['Eve_Ini']) ? $r['Eve_Ini'] : ''));
		if (isset($porEvento[$ini])) {
			$porEvento[$ini]++;
		}
		$uc = isset($r['Usu_Cod']) ? (int)$r['Usu_Cod'] : 0;
		if ($uc > 0) {
			$usuDistintos[$uc] = aud_nombre_usuario($r);
		}
		$mod = aud_nombre_modulo($r);
		if ($mod === '') {
			$mod = 'Sin modulo';
		}
		if (!isset($topModulos[$mod])) {
			$topModulos[$mod] = 0;
		}
		$topModulos[$mod]++;
		$usuN = aud_nombre_usuario($r);
		if ($usuN === 'Usuario no identificado' || $usuN === '') {
			$usuN = 'Usuario '.$uc;
		}
		if (!isset($topUsuarios[$usuN])) {
			$topUsuarios[$usuN] = 0;
		}
		$topUsuarios[$usuN]++;
		$pcsN = aud_nombre_proceso($r);
		if ($pcsN === '' || $pcsN === 'Proceso no registrado') {
			$pcsN = 'Proceso no registrado';
		}
		if (!isset($topProcesos[$pcsN])) {
			$topProcesos[$pcsN] = 0;
		}
		$topProcesos[$pcsN]++;
		$fec = trim(isset($r['Log_Fec']) ? $r['Log_Fec'] : '');
		if ($fec !== '') {
			if ($iniFec === '' || $fec < $iniFec) {
				$iniFec = $fec;
			}
			if ($finFec === '' || $fec > $finFec) {
				$finFec = $fec;
			}
		}
	}
	arsort($topModulos);
	arsort($topUsuarios);
	arsort($topProcesos);
	$topModulos = array_slice($topModulos, 0, 5, true);
	$topUsuarios = array_slice($topUsuarios, 0, 5, true);
	$topProcesos = array_slice($topProcesos, 0, 5, true);
	$obs = array();
	if ($total === 0) {
		$obs[] = 'Sin datos para el filtro seleccionado.';
	} else {
		$obs[] = 'Total de actividades: '.number_format($total, 0, ',', '.').' en el periodo del '.$fil_from.' al '.$fil_to.'.';
		$obs[] = 'Usuarios distintos con actividad: '.count($usuDistintos).'.';
		if (!empty($porEvento['I'])) {
			$obs[] = 'Registros insertados: '.number_format($porEvento['I'], 0, ',', '.').'.';
		}
		if (!empty($porEvento['U'])) {
			$obs[] = 'Registros actualizados: '.number_format($porEvento['U'], 0, ',', '.').'.';
		}
		if (!empty($porEvento['D'])) {
			$obs[] = 'Registros eliminados o anulados: '.number_format($porEvento['D'], 0, ',', '.').'.';
		}
		if (!empty($porEvento['F'])) {
			$obs[] = 'Otras actividades (no I/U/D): '.number_format($porEvento['F'], 0, ',', '.').'.';
		}
	}
	$resumen = array(
		'total' => $total,
		'por_evento' => $porEvento,
		'usuarios_distintos' => count($usuDistintos),
		'inicio' => $iniFec,
		'fin' => $finFec,
		'top_modulos' => $topModulos,
		'top_usuarios' => $topUsuarios,
		'top_procesos' => $topProcesos,
		'observaciones' => $obs
	);
	echo aud_json_out(array('success' => true, 'resumen' => $resumen));
	$obBD_con1->liberar();
	$obBD_conexion->cerrar();
	exit();
}

/** Filtro de usuarios: acepta "123" o CSV de cuentas de una persona (dedupe). */
function aud_usu_list($v)
{
	$out = array();
	foreach (explode(',', trim((string)$v)) as $x) {
		$x = (int)$x;
		if ($x > 0) {
			$out[] = $x;
		}
	}
	return implode(',', $out);
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
	$fil_usu = aud_usu_list(isset($_REQUEST['usu']) ? $_REQUEST['usu'] : 0);
	$fil_suc = isset($_REQUEST['suc']) ? (int)$_REQUEST['suc'] : 0;
	$fil_pla = isset($_REQUEST['pla']) ? (int)$_REQUEST['pla'] : 0;
	if ($fil_from === '' && $fil_to === '') {
		$fil_to = date('Y-m-d');
		$fil_from = date('Y-m-d', strtotime('-30 days'));
	}
	$page = isset($_REQUEST['page']) ? max(1, (int)$_REQUEST['page']) : 1;
	$pageSize = isset($_REQUEST['rows']) ? (int)$_REQUEST['rows'] : 250;
	$allowedPageSizes = array(250, 500, 1000, 5000, 10000000);
	if (!in_array($pageSize, $allowedPageSizes)) {
		if ($pageSize <= 0 || (isset($_REQUEST['rows']) && (string)$_REQUEST['rows'] === 'Todos')) {
			$pageSize = 10000000;
		} else {
			$pageSize = 250;
		}
	}
	// 0 emp,1 from,2 to,3 eve,4 mod,5 pcs,6 tab,7 usu,8 limit,9 offset,10 suc,11 dir,12 pla
	$filtros = array($audEmpCod, $fil_from, $fil_to, $fil_eve, $fil_org, $fil_pcs, 0, $fil_usu, $pageSize, 0, $fil_suc, $fil_dir, $fil_pla);
	$rowCount = $obBD_con1->getRowConsulta(13, $filtros, $obBD_conexion);
	$total = isset($rowCount['count']) ? (int)$rowCount['count'] : 0;
	if ($pageSize >= 10000000) {
		$totalPages = 1;
		$page = 1;
		$offset = 0;
	} else {
		$totalPages = $total > 0 ? (int)ceil($total / $pageSize) : 1;
		if ($page > $totalPages) {
			$page = $totalPages > 0 ? $totalPages : 1;
		}
		$offset = ($page - 1) * $pageSize;
	}
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

/** Verifica si un proceso tiene que ver con plantas (muestra el filtro Planta) */
if (isset($_REQUEST['plantaProcesoAjax'])) {
	@ini_set('display_errors', '0');
	@header('Content-Type: application/json; charset=utf-8');
	$fil_pcsPla = isset($_REQUEST['pcs']) ? (int)$_REQUEST['pcs'] : 0;
	$rowPlaTiene = $fil_pcsPla > 0 ? $obBD_con1->getRowConsulta(35, array($fil_pcsPla), $obBD_conexion) : array();
	aud_json_out(array('success' => true, 'tiene' => !empty($rowPlaTiene['count'])));
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
		$nct = isset($u['N_Ctas']) ? (int)$u['N_Ctas'] : 0;
		if ($un === '') {
			$un = 'Usuario '.(int)$u['Usu_Cod'];
		}
		if ($nct > 1) {
			$un .= ' ('.$nct.' cuentas)';
		}
		$usus = (isset($u['Usu_Cods']) && $u['Usu_Cods'] !== '') ? $u['Usu_Cods'] : (string)(int)$u['Usu_Cod'];
		$out[] = array('Usu_Cod' => (int)$u['Usu_Cod'], 'Usu_Cods' => $usus, 'Usu_Nom' => $un);
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
	$fil_usu = aud_usu_list(isset($_REQUEST['usu']) ? $_REQUEST['usu'] : 0);
	$fil_suc = isset($_REQUEST['suc']) ? (int)$_REQUEST['suc'] : 0;
	$fil_pla = isset($_REQUEST['pla']) ? (int)$_REQUEST['pla'] : 0;
	if ($fil_from === '' && $fil_to === '') {
		$fil_to = date('Y-m-d');
		$fil_from = date('Y-m-d', strtotime('-30 days'));
	}
	$filtros = array($audEmpCod, $fil_from, $fil_to, $fil_eve, $fil_org, $fil_pcs, 0, $fil_usu, 5000, 0, $fil_suc, $fil_dir, $fil_pla);
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

/** Export PDF del listado filtrado (tope 5000, FPDF) */
if (isset($_REQUEST['exportMonitoreoPdf'])) {
	@ini_set('display_errors', '0');
	require_once('../LOGICA/aud_rep_monitoreo_pdf.php');
	$fil_from = isset($_REQUEST['from']) ? trim($_REQUEST['from']) : '';
	$fil_to = isset($_REQUEST['to']) ? trim($_REQUEST['to']) : '';
	$fil_eve = isset($_REQUEST['eve']) ? (int)$_REQUEST['eve'] : 0;
	$fil_org = isset($_REQUEST['org']) ? (int)$_REQUEST['org'] : 0;
	$fil_dir = isset($_REQUEST['dir']) ? (int)$_REQUEST['dir'] : 0;
	$fil_pcs = isset($_REQUEST['pcs']) ? (int)$_REQUEST['pcs'] : 0;
	$fil_usu = aud_usu_list(isset($_REQUEST['usu']) ? $_REQUEST['usu'] : 0);
	$fil_suc = isset($_REQUEST['suc']) ? (int)$_REQUEST['suc'] : 0;
	$fil_pla = isset($_REQUEST['pla']) ? (int)$_REQUEST['pla'] : 0;
	if ($fil_from === '' && $fil_to === '') {
		$fil_to = date('Y-m-d');
		$fil_from = date('Y-m-d', strtotime('-30 days'));
	}
	$filtros = array($audEmpCod, $fil_from, $fil_to, $fil_eve, $fil_org, $fil_pcs, 0, $fil_usu, 5000, 0, $fil_suc, $fil_dir, $fil_pla);
	$Arr_Resultado = $obBD_con1->getArrayConsulta(31, $filtros, $obBD_conexion);
	if (!is_array($Arr_Resultado)) {
		$Arr_Resultado = array();
	}

	// Mapa de plantas (Pla_Cod -> Pla_Nom) para la columna Planta
	$Arr_Plantas = $obBD_con1->getArrayConsulta(33, array(), $obBD_conexion);
	$mapaPlantas = array();
	if (is_array($Arr_Plantas)) {
		foreach ($Arr_Plantas as $pl) {
			$mapaPlantas[(string)(int)$pl['Pla_Cod']] = isset($pl['Pla_Nom']) ? trim($pl['Pla_Nom']) : '';
		}
	}

	// Alcance de la auditoria (reglas cfg_monitoreo activas)
	$Arr_Alcance = $obBD_con1->getArrayConsulta(34, array($audEmpCod), $obBD_conexion);
	$alcance = array();
	if (is_array($Arr_Alcance)) {
		foreach ($Arr_Alcance as $a) {
			$orgDes = isset($a['Org_Des']) ? trim($a['Org_Des']) : '';
			$pcsLin = isset($a['Pcs_Lin']) ? trim($a['Pcs_Lin']) : '';
			if ($pcsLin === '' && isset($a['Pcs_Nom'])) {
				$pcsLin = trim($a['Pcs_Nom']);
			}
			if ((int)$a['Pcs_Cod'] > 0) {
				$nombre = ($orgDes !== '' ? $orgDes . ' > ' : '') . ($pcsLin !== '' ? $pcsLin : ('Proceso ' . (int)$a['Pcs_Cod']));
				$alcance[] = array('nivel' => 'Proceso', 'nombre' => $nombre);
			} elseif ((int)$a['Org_Niv'] === 0) {
				$alcance[] = array('nivel' => 'Modulo completo', 'nombre' => ($orgDes !== '' ? $orgDes : ('Modulo ' . (int)$a['Org_Cod'])));
			} else {
				$alcance[] = array('nivel' => 'Directorio completo', 'nombre' => ($orgDes !== '' ? $orgDes : ('Directorio ' . (int)$a['Org_Cod'])));
			}
		}
	}

	// Filas del informe y agregados para el PDF (misma consulta filtrada que la grilla)
	$filas = array();
	$aggMod = array();
	$aggHora = array();
	$aggUsu = array();
	$aggPla = array();
	$totIns = 0;
	$totUpd = 0;
	$totDel = 0;
	foreach ($Arr_Resultado as $row) {
		$arr = explode(' ', isset($row['Log_Fec']) ? $row['Log_Fec'] : '');
		$pares = aud_pares_interpretados($row, null, null);

		// Planta: buscar el campo Pla_Cod en los pares y resolver su nombre
		$planta = '';
		$paresRaw = aud_parse_cam_val(isset($row['Log_Cam']) ? $row['Log_Cam'] : '', isset($row['Log_Val']) ? $row['Log_Val'] : '');
		if (is_array($paresRaw)) {
			foreach ($paresRaw as $p) {
				if (isset($p['atr']) && strcasecmp(trim($p['atr']), 'Pla_Cod') === 0 && isset($p['val']) && $p['val'] !== '') {
					$planta = isset($mapaPlantas[(string)(int)$p['val']]) ? $mapaPlantas[(string)(int)$p['val']] : '';
					break;
				}
			}
		}

		$nomUsu = aud_nombre_usuario($row);
		$nomMod = aud_nombre_modulo($row);
		$nomDir = aud_nombre_directorio($row);
		$nomPcs = aud_nombre_proceso($row);
		$eveCod = isset($row['Eve_Cod']) ? (int)$row['Eve_Cod'] : 0;
		if ($eveCod === 2) { $totIns++; }
		elseif ($eveCod === 3) { $totUpd++; }
		elseif ($eveCod === 4) { $totDel++; }

		if ($nomMod !== '') {
			if (!isset($aggMod[$nomMod])) { $aggMod[$nomMod] = 0; }
			$aggMod[$nomMod]++;
		}
		$hh = isset($arr[1]) ? substr($arr[1], 0, 2) : '';
		if ($hh !== '') {
			$hh = str_pad($hh, 2, '0', STR_PAD_LEFT) . ':00';
			if (!isset($aggHora[$hh])) { $aggHora[$hh] = 0; }
			$aggHora[$hh]++;
		}
		if (!isset($aggUsu[$nomUsu])) {
			$aggUsu[$nomUsu] = array('nombre' => $nomUsu, 'total' => 0, 'insert' => 0, 'update' => 0, 'delete' => 0);
		}
		$aggUsu[$nomUsu]['total']++;
		if ($eveCod === 2) { $aggUsu[$nomUsu]['insert']++; }
		elseif ($eveCod === 3) { $aggUsu[$nomUsu]['update']++; }
		elseif ($eveCod === 4) { $aggUsu[$nomUsu]['delete']++; }
		if ($planta !== '') {
			if (!isset($aggPla[$planta])) {
				$aggPla[$planta] = array('planta' => $planta, 'usuarios' => array(), 'total' => 0, 'insert' => 0, 'update' => 0, 'delete' => 0);
			}
			$aggPla[$planta]['usuarios'][$nomUsu] = true;
			$aggPla[$planta]['total']++;
			if ($eveCod === 2) { $aggPla[$planta]['insert']++; }
			elseif ($eveCod === 3) { $aggPla[$planta]['update']++; }
			elseif ($eveCod === 4) { $aggPla[$planta]['delete']++; }
		}

		$filas[] = array(
			'fecha' => isset($arr[0]) ? $arr[0] : '',
			'hora' => isset($arr[1]) ? $arr[1] : '',
			'usuario' => $nomUsu,
			'modulo' => $nomMod,
			'directorio' => $nomDir,
			'proceso' => $nomPcs,
			'actividad' => aud_resumen_actividad($row),
			'planta' => $planta,
			'detalle' => aud_resumen_detalle($row, $pares)
		);
	}

	// Texto de filtros aplicados
	$bitFiltros = array();
	$bitFiltros[] = 'Período: ' . $fil_from . ' a ' . $fil_to;
	if ($fil_eve > 0) {
		$Arr_Eve = $obBD_con1->getArrayConsulta(17, array(), $obBD_conexion);
		if (is_array($Arr_Eve)) {
			foreach ($Arr_Eve as $e) {
				if ((int)$e['Eve_Cod'] === $fil_eve) {
					$bitFiltros[] = 'Evento: ' . trim($e['Eve_Des']);
					break;
				}
			}
		}
	}
	if ($fil_usu !== '') {
		$bitFiltros[] = 'Usuario: ' . $fil_usu;
	}
	if ($fil_suc > 0) {
		$bitFiltros[] = 'Sucursal: ' . $fil_suc;
	}
	if ($fil_org > 0 || $fil_dir > 0 || $fil_pcs > 0) {
		$bitFiltros[] = 'Módulo/Proceso: ' . $fil_org . '/' . $fil_dir . '/' . $fil_pcs;
	}
	if ($fil_pla > 0) {
		$bitFiltros[] = 'Planta: ' . (isset($mapaPlantas[(string)$fil_pla]) ? $mapaPlantas[(string)$fil_pla] : ('Planta ' . $fil_pla));
	}

	// Empresa y emisor del informe
	$empNombre = 'EXACONTABLE ERP';
	if (!empty($Arr_Resultado) && isset($Arr_Resultado[0]['Emp_Nom']) && trim($Arr_Resultado[0]['Emp_Nom']) !== '') {
		$empNombre = trim($Arr_Resultado[0]['Emp_Nom']);
	}
	$emisorNombre = 'Usuario #' . $audUsuCod;
	$rowEmisor = $audUsuCod > 0 ? $obBD_con1->getRowConsulta(3, array($audUsuCod), $obBD_conexion) : array();
	if (!empty($rowEmisor['Prs_Nom']) || !empty($rowEmisor['Prs_Ape'])) {
		$emisorNombre = trim($rowEmisor['Prs_Ape'] . ' ' . $rowEmisor['Prs_Nom']);
	}

	// Total exacto del filtro (misma consulta COUNT que usa la grilla)
	$totalFiltro = count($filas);
	$rowCount = $obBD_con1->getRowConsulta(13, $filtros, $obBD_conexion);
	if (isset($rowCount['count'])) {
		$totalFiltro = (int)$rowCount['count'];
	}

	// Listas ordenadas para el informe PDF
	$modulosList = array();
	arsort($aggMod);
	foreach ($aggMod as $kMod => $vTot) {
		$modulosList[] = array('modulo' => $kMod, 'total' => $vTot);
	}
	$horariosList = array();
	for ($h = 0; $h < 24; $h++) {
		$etq = str_pad($h, 2, '0', STR_PAD_LEFT) . ':00';
		$horariosList[] = array('hora' => $etq, 'total' => isset($aggHora[$etq]) ? $aggHora[$etq] : 0);
	}
	$usuariosList = array();
	$tmpUsu = array();
	foreach ($aggUsu as $kUsu => $u) {
		$tmpUsu[$kUsu] = $u['total'];
	}
	arsort($tmpUsu);
	foreach ($tmpUsu as $kUsu2 => $vTot2) {
		$usuariosList[] = $aggUsu[$kUsu2];
	}
	$plantasList = array();
	$tmpPla = array();
	foreach ($aggPla as $kPla => $p) {
		$tmpPla[$kPla] = $p['total'];
	}
	arsort($tmpPla);
	foreach ($tmpPla as $kPla2 => $vTot3) {
		$pRow = $aggPla[$kPla2];
		$pRow['usuarios'] = count($pRow['usuarios']);
		$plantasList[] = $pRow;
	}

	// Observaciones del informe
	$observaciones = array();
	$observaciones[] = 'Filtros aplicados: ' . implode(' | ', $bitFiltros);
	foreach ($alcance as $aAlc) {
		$observaciones[] = 'Alcance configurado - ' . $aAlc['nivel'] . ': ' . $aAlc['nombre'];
	}
	$observaciones[] = 'El filtro coincide con ' . number_format($totalFiltro) . ' movimientos; el detalle y las estadisticas se calculan sobre los primeros ' . count($filas) . ' registros devueltos.';

	$datos = array(
		'empresa' => $empNombre,
		'rango' => date('d/m/Y', strtotime($fil_from)) . ' a ' . date('d/m/Y', strtotime($fil_to)),
		'usuario_emisor' => $emisorNombre,
		'resumen' => array(
			'total' => $totalFiltro,
			'insert' => $totIns,
			'update' => $totUpd,
			'delete' => $totDel,
			'usuarios_unicos' => count($aggUsu)
		),
		'modulos' => $modulosList,
		'horarios' => $horariosList,
		'usuarios_top' => array_slice($usuariosList, 0, 10),
		'plantas_top' => $plantasList,
		'observaciones' => $observaciones,
		'filas' => $filas,
		'total_registros' => count($filas)
	);
	aud_generar_reporte_monitoreo_pdf($datos, 'D');
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
$pcsAnt = isset($pcsSim['anticipos']) ? (int)$pcsSim['anticipos'] : 0;
$pcsCon = isset($pcsSim['contratos']) ? (int)$pcsSim['contratos'] : 0;
$pcsMaq = isset($pcsSim['maquinaria']) ? (int)$pcsSim['maquinaria'] : 0;
$pcsTec = isset($pcsSim['tecnicos']) ? (int)$pcsSim['tecnicos'] : 0;
$pcsOpe = isset($pcsSim['operario_vehiculos']) ? (int)$pcsSim['operario_vehiculos'] : 0;
$pcsInv = isset($pcsSim['inventario']) ? (int)$pcsSim['inventario'] : 0;
$pcsCob = isset($pcsSim['cobranzas']) ? (int)$pcsSim['cobranzas'] : 0;
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
		array('caja_aper', 'Apertura y cierre de caja', 'Caja'),
		array('anticipos_clientes', 'Anticipos de clientes Relavera', 'Anticipos de clientes'),
		array('det_ant_cccc', 'Detalle de anticipos Relavera', 'Detalle de anticipos'),
		array('manifiesto_anticipo', 'Anticipos de manifiesto Relavera', 'Anticipos de manifiesto'),
		array('pag_anticipo_cli', 'Pagos de anticipo Relavera', 'Pagos de anticipo'),
		array('manifiesto_contratos', 'Contratos con plantas Relavera', 'Contratos con plantas'),
		array('manifiesto_contratos_docu', 'Documentos de contratos Relavera', 'Documentos de contratos'),
		array('param_manifiesto', 'Parametros de manifiesto Relavera', 'Parametros de manifiesto'),
		array('maquinaria_alimentacion', 'Alimentacion de maquinaria Relavera', 'Alimentacion de maquinaria'),
		array('maquinaria_dispensador', 'Dispensadores de gasolina Relavera', 'Dispensadores de gasolina'),
		array('maquinaria_dispensador_det', 'Detalle de dispensadores Relavera', 'Detalle de dispensadores'),
		array('maquinaria_dispensador_cierre', 'Cierres de dispensadores Relavera', 'Cierres de dispensadores'),
		array('maquinaria_equipo', 'Equipos de maquinaria Relavera', 'Equipos de maquinaria'),
		array('maquinaria_horometro', 'Horometros de maquinaria Relavera', 'Horometros de maquinaria'),
		array('manifiesto_liquidacion_maq', 'Liquidacion de maquinaria Relavera', 'Liquidacion de maquinaria'),
		array('manifiesto_tecnico', 'Tecnicos asignados Relavera', 'Tecnicos asignados'),
		array('manifiesto_mensajes', 'Mensajes Relavera', 'Mensajes'),
		array('chofer', 'Choferes Relavera', 'Choferes'),
		array('vehiculo', 'Vehiculos Relavera', 'Vehiculos'),
		array('personal', 'Personal Relavera', 'Personal'),
		array('inventario_dispositivos', 'Inventario de dispositivos Relavera', 'Inventario de dispositivos'),
		array('usuario_inventario', 'Usuarios de inventario Relavera', 'Usuarios de inventario'),
		array('dispositivos_usuario', 'Vinculos navegador-dispositivo (MAC) Relavera', 'Vinculos de dispositivo'),
		array('ccpp_cobrar', 'Cuentas por cobrar Relavera', 'Cuentas por cobrar'),
		array('det_ccpp_c', 'Detalle de cuentas por cobrar Relavera', 'Detalle de cuentas por cobrar'),
		array('pago_venta', 'Pagos de venta Relavera', 'Pagos de venta'),
		array('ventas_compr', 'Comprobantes de venta Relavera', 'Comprobantes de venta')
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
			'Man_Cod=DEMO-M-1001 || OLD:Man_Pes=12500,Man_Pun=3.00', 20),
		array($pcsTur, 'manifiesto_turnos_det', 'U',
			'Tud_Cup,Tud_Est',
			'12,~A~',
			'Tud_Cod=DEMO-TUD-1 || OLD:Tud_Cup=15,Tud_Est=A', 10),
		array($pcsVis, 'manifiesto_visitante', 'U',
			'MVis_Est,MVis_Obs',
			'~I~,~ANULACION DEMO VISITANTE~',
			'MVis_Cod=DEMO-VIS-1 || OLD:MVis_Est=A,MVis_Obs=INGRESO DEMO VISITANTE', 5),
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
			'Caj_Cod=DEMO-CAJ-1 || OLD:Caj_Est=A,Caj_Gen=N', 6),
		array($pcsAnt, 'anticipos_clientes', 'I',
			'Ant_Cod,Ant_Mon,Ant_Est,Ant_Obs',
			'~DEMO-ANT-1~,250.00,~A~,~ANTICIPO DEMO~',
			'Ant_Cod=DEMO-ANT-1', 28),
		array($pcsCon, 'manifiesto_contratos', 'I',
			'MC_Cod,Pla_Cod,MC_Fei,MC_Fef,MC_Est',
			'~DEMO-MC-1~,1,~'.$hoy.'~,~'.$finTur.'~,~A~',
			'MC_Cod=DEMO-MC-1', 26),
		array($pcsMaq, 'maquinaria_horometro', 'I',
			'Maq_Cod,MHor_Fec,MHor_Val',
			'1,~'.$hoy.'~,1250.00',
			'MHor_Cod=DEMO-HOR-1', 24),
		array($pcsMaq, 'maquinaria_horometro', 'U',
			'MHor_Val,MHor_Est',
			'1310.00,~A~',
			'MHor_Cod=DEMO-HOR-1 || OLD:MHor_Val=1250.00,MHor_Est=A', 18),
		array($pcsTec, 'manifiesto_tecnico', 'I',
			'MT_Cod,Tec_Cod,MT_Fec,MT_Obs',
			'~DEMO-MT-1~,1,~'.$hoy.'~,~ASIGNACION TECNICO DEMO~',
			'MT_Cod=DEMO-MT-1', 22),
		array($pcsOpe, 'vehiculo', 'I',
			'Veh_Ope,Veh_Placa,Veh_Est',
			'1,~PBA-1234~,~A~',
			'Veh_Cod=DEMO-VEH-1', 20),
		array($pcsInv, 'inventario_dispositivos', 'I',
			'Inv_Ser,Inv_Est,Inv_Fec',
			'~SN-DEMO-1~,~A~,~'.$hoy.'~',
			'Inv_Cod=DEMO-INV-1', 16),
		array($pcsInv, 'inventario_dispositivos', 'U',
			'Inv_Est,Inv_Obs',
			'~I~,~BAJA DEMO DISPOSITIVO~',
			'Inv_Cod=DEMO-INV-1 || OLD:Inv_Est=A', 9),
		array($pcsCob, 'ccpp_cobrar', 'I',
			'Ccp_Cod,Cli_Cod,Ccp_Mon,Ccp_Est',
			'~DEMO-CCP-1~,1,120.00,~A~',
			'Ccp_Cod=DEMO-CCP-1', 14),
		array($pcsCob, 'pago_venta', 'U',
			'Pag_Mon,Pay_Est',
			'120.00,~C~',
			'Pag_Cod=DEMO-PAG-1 || OLD:Pag_Mon=120.00,Pay_Est=A', 7)
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
$fil_usu = aud_usu_list(isset($_GET['usu']) ? $_GET['usu'] : 0);
$fil_suc = isset($_GET['suc']) ? (int)$_GET['suc'] : 0;
$fil_pla = isset($_GET['pla']) ? (int)$_GET['pla'] : 0;
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
$Arr_PlantasFiltro = $obBD_con1->getArrayConsulta(33, array(), $obBD_conexion);
if (!is_array($Arr_PlantasFiltro)) {
	$Arr_PlantasFiltro = array();
}
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<title><?php echo isset($Ses_Sys_Nom) ? $Ses_Sys_Nom : 'Auditoria'; ?></title>
	<?php require_once("../../mascaras/model1/estilos/jqgrid5.php"); ?>
	<?php require_once("../../mascaras/model4/estilos/estilos.php"); ?>
	<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
	<script type="text/javascript" src="../../Librerias/validaciones/validacion.js"></script>
	<link rel="stylesheet" type="text/css" href="../RECURSOS/aud_monitoreo_ui_1.0.css?v=20260924_chosen1" />
</head>
<body>
<div class="panel panel-default panel-main exa-ui-panel exa-ui-fill-page">
	<div class="panel-heading exa-header">
		<h3 class="panel-title"><span class="glyphicon glyphicon-eye-open"></span> Monitorear actividades</h3>
	</div>
	<div class="panel-body exa-body">
		<div id="lista" class="row exa-ui-page-view">
			<div class="col-xs-12">
				<div class="aud-page-hero m4-hero">
					<div class="aud-page-hero-icon m4-hero-icon"><span class="glyphicon glyphicon-eye-open"></span></div>
					<div class="aud-page-hero-text m4-hero-text">
						<h4 class="m4-hero-title">Historial de actividades</h4>
						<p class="aud-page-hero-sub m4-hero-sub">
							Consulte, filtre y exporte los eventos de inserci&oacute;n, actualizaci&oacute;n y eliminaci&oacute;n
							registrados seg&uacute;n la configuraci&oacute;n de monitoreo.
						</p>
					</div>
					<div class="aud-page-hero-tags m4-hero-tags">
						<span class="aud-page-hero-tag m4-hero-tag"><span class="glyphicon glyphicon-filter"></span> Filtros</span>
						<span class="aud-page-hero-tag m4-hero-tag"><span class="glyphicon glyphicon-download-alt"></span> Exportar</span>
					</div>
				</div>
				<?php echo aud_html_banner_captura($audEstado); ?>
				<?php echo aud_html_banner_desde(aud_fecha_registro_inicio($audEmpCod, $obBD_con1, $obBD_conexion)); ?>
				<div class="aud-toolbar-card m4-filters m4-card aud-mon-filters">
					<form id="frmFiltros" class="exa-ui-busqueda-filtros" onsubmit="return false;">

						<!-- 1) Rango de fechas -->
						<div class="aud-mon-block aud-mon-block-period" id="audMonPeriodBlock">
							<span class="aud-mon-block-title"><span class="glyphicon glyphicon-calendar"></span> Periodo</span>
							<div class="aud-mon-block-body aud-toolbar-dates">
								<div class="btn-group btn-group-xs aud-period-presets" id="audMonPeriodoPresets" role="group" aria-label="Rangos rapidos">
									<button type="button" class="btn aud-btn-preset" data-preset="ayer" title="Ayer">Ayer</button>
									<button type="button" class="btn aud-btn-preset" data-preset="hoy" title="Hoy">Hoy</button>
									<button type="button" class="btn aud-btn-preset" data-preset="1semana" title="1 Semana">1 Semana</button>
									<button type="button" class="btn aud-btn-preset active" data-preset="1mes" title="1 Mes">1 Mes</button>
									<button type="button" class="btn aud-btn-preset" data-preset="3meses" title="3 Meses">3 Meses</button>
								</div>
								<div class="aud-toolbar-range">
									<div class="input-group input-group-xs">
										<span class="input-group-addon">Desde</span>
										<input name="from" type="text" id="from" class="form-control input-xs" value="<?php echo aud_h($fil_from); ?>" maxlength="10" placeholder="aaaa-mm-dd" autocomplete="off" />
										<span class="input-group-addon" id="btnFromCal" title="Abrir calendario"><span class="glyphicon glyphicon-calendar"></span></span>
									</div>
									<div class="input-group input-group-xs">
										<span class="input-group-addon">Hasta</span>
										<input name="to" type="text" id="to" class="form-control input-xs" value="<?php echo aud_h($fil_to); ?>" maxlength="10" placeholder="aaaa-mm-dd" autocomplete="off" />
										<span class="input-group-addon" id="btnToCal" title="Abrir calendario"><span class="glyphicon glyphicon-calendar"></span></span>
									</div>
								</div>
							</div>
						</div>

						<!-- 2) Filtros (selects) -->
						<div class="aud-mon-block aud-mon-block-filters" id="audFiltrosRow">
							<span class="aud-mon-block-title"><span class="glyphicon glyphicon-filter"></span> Filtros</span>
							<div class="aud-mon-block-body aud-mon-filters-grid">
								<div class="aud-search-cell aud-search-cell-usu">
									<label class="aud-mon-label" for="usu">Usuario</label>
									<select name="usu" id="usu" class="form-control input-xs aud-select-search" title="Filtrar por usuario" data-placeholder="Buscar usuario...">
										<option value="0">Todos</option>
										<?php foreach ($Arr_Usuarios as $u) {
											$un = trim(isset($u['Usu_Nom']) ? $u['Usu_Nom'] : '');
											if ($un === '') {
												$un = 'Usuario '.(int)$u['Usu_Cod'];
											}
											$nc = isset($u['N_Ctas']) ? (int)$u['N_Ctas'] : 0;
											if ($nc > 1) {
												$un .= ' ('.$nc.' cuentas)';
											}
											$usus = (isset($u['Usu_Cods']) && $u['Usu_Cods'] !== '') ? $u['Usu_Cods'] : (string)(int)$u['Usu_Cod'];
										?>
										<option value="<?php echo aud_h($usus); ?>"<?php echo $fil_usu === $usus ? ' selected="selected"':''; ?>><?php echo aud_h($un); ?></option>
										<?php } ?>
									</select>
								</div>
								<?php if ($hasSucursales) { ?>
								<div class="aud-search-cell aud-search-cell-suc">
									<label class="aud-mon-label" for="suc">Sucursal</label>
									<select name="suc" id="suc" class="form-control input-xs aud-select-search" title="Filtrar por sucursal" data-placeholder="Buscar sucursal...">
										<option value="0">Todas</option>
										<?php foreach ($Arr_Sucursales as $s) { ?>
										<option value="<?php echo (int)$s['Suc_Cod']; ?>"<?php echo $fil_suc==(int)$s['Suc_Cod']?' selected="selected"':''; ?>><?php echo aud_h($s['Suc_Des']); ?></option>
										<?php } ?>
									</select>
								</div>
								<?php } ?>
								<div class="aud-search-cell aud-search-cell-mod">
									<label class="aud-mon-label" for="org">Modulo</label>
									<select name="org" id="org" class="form-control input-xs aud-select-search" title="Filtrar por modulo" data-placeholder="Buscar modulo...">
										<option value="0">Todos</option>
										<?php foreach ($Arr_Modulos as $m) { ?>
										<option value="<?php echo (int)$m['Org_Cod']; ?>"<?php echo $fil_org==(int)$m['Org_Cod']?' selected="selected"':''; ?>><?php echo aud_h($m['Org_Des']); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="aud-search-cell aud-search-cell-dir">
									<label class="aud-mon-label" for="dir">Directorio</label>
									<select name="dir" id="dir" class="form-control input-xs aud-select-search" title="Filtrar por directorio" data-placeholder="Buscar directorio...">
										<option value="0">Todos</option>
										<?php foreach ($Arr_Directorios as $d) { ?>
										<option value="<?php echo (int)$d['Org_Cod']; ?>"<?php echo $fil_dir==(int)$d['Org_Cod']?' selected="selected"':''; ?>><?php echo aud_h($d['Org_Des']); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="aud-search-cell aud-search-cell-pcs">
									<label class="aud-mon-label" for="pcs">Proceso</label>
									<select name="pcs" id="pcs" class="form-control input-xs aud-select-search" title="Filtrar por proceso" data-placeholder="Buscar proceso...">
										<option value="0">Todos</option>
										<?php foreach ($Arr_Procesos as $p) {
											$pl = !empty($p['Pcs_Lin']) ? $p['Pcs_Lin'] : (isset($p['Pcs_Nom']) ? $p['Pcs_Nom'] : ('Proceso '.$p['Pcs_Cod']));
										?>
										<option value="<?php echo (int)$p['Pcs_Cod']; ?>"<?php echo $fil_pcs==(int)$p['Pcs_Cod']?' selected="selected"':''; ?>><?php echo aud_h($pl); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="aud-search-cell aud-search-cell-pla" id="audFilPlantaWrap" style="display:none;">
									<label class="aud-mon-label" for="filPlanta">Planta</label>
									<select name="pla" id="filPlanta" class="form-control input-xs aud-select-search" title="Filtrar por planta del proceso" data-placeholder="Buscar planta...">
										<option value="0">Todas</option>
										<?php foreach ($Arr_PlantasFiltro as $pl2) { ?>
										<option value="<?php echo (int)$pl2['Pla_Cod']; ?>"<?php echo $fil_pla==(int)$pl2['Pla_Cod']?' selected="selected"':''; ?>><?php echo aud_h($pl2['Pla_Nom']); ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="aud-search-cell aud-search-cell-eve">
									<label class="aud-mon-label" for="eve">Evento</label>
									<select name="eve" id="eve" class="form-control input-xs aud-select-search" title="Filtrar por tipo de evento" data-placeholder="Buscar evento...">
										<option value="0">Todos</option>
										<?php foreach ($Arr_Eventos as $ev) { ?>
										<option value="<?php echo (int)$ev['Eve_Cod']; ?>"<?php echo $fil_eve==(int)$ev['Eve_Cod']?' selected="selected"':''; ?>><?php echo aud_h($ev['Eve_Des']); ?></option>
										<?php } ?>
									</select>
								</div>
							</div>
						</div>

						<!-- 3) Acciones -->
						<div class="aud-mon-block aud-mon-block-actions">
							<span class="aud-mon-block-title"><span class="glyphicon glyphicon-flash"></span> Acciones</span>
							<div class="aud-mon-block-body aud-mon-actions-bar">
								<button type="button" id="btnBuscar" class="btn btn-success btn-sm" title="Aplicar filtros">
									<span class="glyphicon glyphicon-search"></span> <span class="aud-mon-btn-txt">Buscar</span>
								</button>
								<button type="button" id="btnLimpiar" class="btn btn-default btn-sm" title="Restablecer filtros (ultimos 30 dias / 1 Mes)">
									<span class="glyphicon glyphicon-refresh"></span> <span class="aud-mon-btn-txt">Limpiar</span>
								</button>
								<div class="aud-col-wrap" id="aud-col-wrap">
									<button type="button" id="aud-col-btn" class="btn btn-default btn-sm" title="Columnas visibles">
										<span class="glyphicon glyphicon-th-list"></span> <span class="aud-mon-btn-txt">Columnas</span>
									</button>
									<div id="aud-col-panel" class="aud-col-panel m4-card">
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
								<div class="dropdown aud-mon-acciones-dd">
									<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Acciones">
										<span class="glyphicon glyphicon-cog"></span> <span class="aud-mon-btn-txt">Acciones</span> <span class="caret"></span>
									</button>
									<ul class="dropdown-menu dropdown-menu-right aud-acciones-menu">
										<li><a href="javascript:void(0);" id="btnExportPdf"><i class="fa fa-file-pdf-o text-danger"></i> Exportar PDF</a></li>
										<li><a href="javascript:void(0);" id="btnExportExcel"><span class="glyphicon glyphicon-download-alt text-success"></span> Exportar Excel</a></li>
									</ul>
								</div>
							</div>
						</div>

					</form>
				</div>

				<div class="aud-resumen-wrap m4-card is-compact" id="audResumenWrap" style="display:none;">
				<div class="aud-resumen-head m4-card-head">
					<span class="m4-card-title"><span class="glyphicon glyphicon-stats"></span> Resumen</span>
					<button type="button" id="btnToggleResumen" class="btn btn-link btn-xs m4-btn m4-btn-ghost m4-btn-sm" title="Ver tops y observaciones">
						Detalle
					</button>
				</div>
				<div id="audResumenContenido" class="aud-resumen-body m4-card-body"></div>
			</div>

			<div class="exa-ui-grid-host m4-table-scroll m4-card">
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
<script type="text/javascript" src="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.js"></script>
<script type="text/javascript" src="../VALIDACIONES/aud_par_monitoreo.js?v=20260924_chosen1"></script>
</body>
</html>
<?php
$obBD_con1->liberar();
$obBD_conexion->cerrar();
?>

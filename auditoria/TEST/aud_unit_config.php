<?php
/**
 * Pruebas de configuracion de monitoreo (modulo / directorio / proceso).
 *
 * @package auditoria.TEST
 */

require_once dirname(__FILE__) . '/aud_test_lib.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_log_config_monitoreo.php';

function aud_run_config_tests()
{
	$failed = 0;
	$passed = 0;
	echo "\n== Pruebas configuracion de monitoreo ==\n";

	$cases = array(
		'aud_unit_cfg_arbol_tres_niveles',
		'aud_unit_cfg_compacta_modulo',
		'aud_unit_cfg_compacta_directorio',
		'aud_unit_cfg_compacta_proceso',
		'aud_unit_cfg_marca_modulo_vs_directorio',
		'aud_unit_cfg_sql_arbol_tiene_niveles',
		'aud_unit_cfg_parse_items',
		'aud_unit_cfg_marcar_todos_compacta',
		'aud_unit_cfg_traza_y_banner',
		'aud_unit_db_cfg_arbol_facturacion',
		'aud_unit_db_cfg_filtra_por_nivel',
		'aud_unit_db_cfg_captura_fuera_whitelist',
		'aud_unit_db_cfg_guardar_todos_y_registra'
	);
	foreach ($cases as $fn) {
		try {
			AuditQueue::resetForTests();
			$fn();
			$passed++;
		} catch (Exception $e) {
			$failed++;
			echo "  FAIL  " . $fn . ": " . $e->getMessage() . "\n";
		}
		AuditQueue::resetForTests();
	}

	echo "Configuracion: " . $passed . " ok, " . $failed . " fallidas\n";
	return $failed;
}

function aud_cfg_arbol_demo()
{
	return aud_cfg_armar_arbol(array(
		array(
			'Mod_Cod' => 13, 'Mod_Des' => 'Facturacion',
			'Dir_Cod' => 19, 'Dir_Des' => 'Ventas', 'Dir_Niv' => 13,
			'Pcs_Cod' => 766, 'Pcs_Lin' => 'Registrar', 'Pcs_Nom' => 'fac_alt_fac_ven_3.2.php'
		),
		array(
			'Mod_Cod' => 13, 'Mod_Des' => 'Facturacion',
			'Dir_Cod' => 19, 'Dir_Des' => 'Ventas', 'Dir_Niv' => 13,
			'Pcs_Cod' => 770, 'Pcs_Lin' => 'Consultar', 'Pcs_Nom' => 'fac_con_fac_ven_2.1.php'
		),
		array(
			'Mod_Cod' => 13, 'Mod_Des' => 'Facturacion',
			'Dir_Cod' => 22, 'Dir_Des' => 'Compras', 'Dir_Niv' => 13,
			'Pcs_Cod' => 800, 'Pcs_Lin' => 'Registrar', 'Pcs_Nom' => 'fac_alt_fac_com_2.0.php'
		),
		array(
			'Mod_Cod' => 14, 'Mod_Des' => 'Contabilidad',
			'Dir_Cod' => 14, 'Dir_Des' => 'Contabilidad', 'Dir_Niv' => 0,
			'Pcs_Cod' => 130, 'Pcs_Lin' => 'Comprobantes', 'Pcs_Nom' => 'con_alt_compr_1.1.php'
		)
	));
}

function aud_unit_cfg_arbol_tres_niveles()
{
	$arbol = aud_cfg_arbol_demo();
	aud_assert(count($arbol) === 2, 'Hay 2 modulos (Facturacion y Contabilidad)');
	$fac = $arbol[0];
	aud_assert($fac['Org_Cod'] === 13 && $fac['Org_Des'] === 'Facturacion', 'Primer modulo es Facturacion');
	aud_assert(count($fac['directorios']) === 2, 'Facturacion tiene 2 directorios');
	aud_assert($fac['directorios'][0]['Org_Des'] === 'Ventas', 'Primer directorio es Ventas');
	aud_assert($fac['directorios'][0]['es_modulo'] === false, 'Ventas no es el modulo raiz');
	aud_assert(count($fac['directorios'][0]['procesos']) === 2, 'Ventas tiene 2 procesos');
	aud_assert($fac['directorios'][0]['procesos'][0]['Pcs_Cod'] === 766, 'Registrar venta esta bajo Ventas');
	$con = $arbol[1];
	aud_assert($con['directorios'][0]['es_modulo'] === true, 'Proceso colgando del modulo no inventa directorio extra');
}

function aud_unit_cfg_compacta_modulo()
{
	$arbol = aud_cfg_arbol_demo();
	$items = aud_cfg_compactar_reglas($arbol, array(13 => true), array(), array());
	aud_assert(count($items) === 1, 'Modulo completo es una sola regla');
	aud_assert($items[0]['org'] === 13 && $items[0]['pcs'] === 0, 'Regla de modulo Facturacion Pcs_Cod=0');
}

function aud_unit_cfg_compacta_directorio()
{
	$arbol = aud_cfg_arbol_demo();
	$items = aud_cfg_compactar_reglas($arbol, array(), array(19 => true), array());
	aud_assert(count($items) === 1, 'Directorio completo es una sola regla');
	aud_assert($items[0]['org'] === 19 && $items[0]['pcs'] === 0, 'Regla de directorio Ventas');
}

function aud_unit_cfg_compacta_proceso()
{
	$arbol = aud_cfg_arbol_demo();
	$items = aud_cfg_compactar_reglas($arbol, array(), array(), array('19_766' => true));
	aud_assert(count($items) === 1, 'Un proceso es una sola regla');
	aud_assert($items[0]['org'] === 19 && $items[0]['pcs'] === 766, 'Regla de Registrar venta');
}

function aud_unit_cfg_marca_modulo_vs_directorio()
{
	$arbol = aud_cfg_arbol_demo();
	$mod = aud_cfg_marcar_seleccion($arbol, array(array('Org_Cod' => 13, 'Pcs_Cod' => 0)));
	aud_assert(!empty($mod['modFull'][13]), 'Org 13 Pcs 0 marca el modulo Facturacion');
	aud_assert(empty($mod['dirFull'][13]), 'El modulo raiz no se guarda como directorio');

	$dir = aud_cfg_marcar_seleccion($arbol, array(array('Org_Cod' => 19, 'Pcs_Cod' => 0)));
	aud_assert(!empty($dir['dirFull'][19]), 'Org 19 Pcs 0 marca el directorio Ventas');
	aud_assert(empty($dir['modFull'][19]), 'Ventas no se promociona a modulo');

	$pcs = aud_cfg_marcar_seleccion($arbol, array(array('Org_Cod' => 19, 'Pcs_Cod' => 766)));
	aud_assert(!empty($pcs['selected']['19_766']), 'Proceso puntual queda en selected');
}

function aud_unit_cfg_sql_arbol_tiene_niveles()
{
	$sql = sentencias_cfg_monitoreo(7, array());
	aud_assert(strpos($sql, '`Mod_Cod`') !== false, 'SQL del arbol expone Mod_Cod');
	aud_assert(strpos($sql, '`Dir_Cod`') !== false, 'SQL del arbol expone Dir_Cod');
	aud_assert(strpos($sql, 'Org_Niv`,0) = 0') !== false, 'El modulo se resuelve con Org_Niv=0');
}

function aud_unit_cfg_parse_items()
{
	$ok = aud_cfg_parse_items('[{"org":13,"pcs":0},{"org":19,"pcs":766}]');
	aud_assert(!empty($ok['ok']) && count($ok['items']) === 2, 'JSON de reglas se lee');
	aud_assert((int)$ok['items'][0]['org'] === 13 && (int)$ok['items'][0]['pcs'] === 0, 'Regla de modulo Pcs_Cod=0');
	aud_assert((int)$ok['items'][1]['pcs'] === 766, 'Regla de proceso conserva Pcs_Cod');

	$slash = aud_cfg_parse_items('[{\"org\":14,\"pcs\":0}]');
	aud_assert(!empty($slash['ok']) && count($slash['items']) === 1 && (int)$slash['items'][0]['org'] === 14, 'JSON con slashes de magic quotes se lee');

	$arr = aud_cfg_parse_items(array(array('org' => 13, 'pcs' => 0)));
	aud_assert(!empty($arr['ok']) && count($arr['items']) === 1, 'Arreglo PHP se acepta');

	$bad = aud_cfg_parse_items('{no-json');
	aud_assert(empty($bad['ok']), 'JSON invalido no se trata como seleccion vacia');

	$empty = aud_cfg_parse_items('[]');
	aud_assert(!empty($empty['ok']) && count($empty['items']) === 0, 'Arreglo vacio es seleccion vacia valida');

	$front = file_get_contents(dirname(__FILE__) . '/../FRONT/aud_adm_config_monitoreo_1.0.php');
	aud_assert(strpos($front, 'aud_cfg_parse_items') !== false, 'FRONT parsea items antes de borrar');
	aud_assert(strpos($front, 'aud_cfg_guardar') !== false, 'FRONT guarda por aud_cfg_guardar y verifica el conteo');
	$js = file_get_contents(dirname(__FILE__) . '/../VALIDACIONES/aud_par_config_monitoreo.js');
	aud_assert(strpos($js, 'aud-cfg-mod-chk') !== false && strpos($js, 'indeterminate') !== false, 'JS compacta modulo completo al marcar todos');
}

function aud_unit_cfg_marcar_todos_compacta()
{
	$arbol = aud_cfg_arbol_demo();
	$modFull = array();
	foreach ($arbol as $m) {
		$modFull[(int)$m['Org_Cod']] = true;
	}
	$items = aud_cfg_compactar_reglas($arbol, $modFull, array(), array());
	aud_assert(count($items) === 2, 'Marcar todos compacta a una regla por modulo');
	aud_assert((int)$items[0]['org'] === 13 && (int)$items[0]['pcs'] === 0, 'Facturacion se guarda como modulo completo');
	aud_assert((int)$items[1]['org'] === 14 && (int)$items[1]['pcs'] === 0, 'Contabilidad se guarda como modulo completo');
}

function aud_unit_cfg_traza_y_banner()
{
	$sql = sentencias_cfg_monitoreo(8, array(7, 1, 2, 32));
	aud_assert(strpos($sql, 'INSERT INTO `auditoria`.`logs`') !== false, 'Guardar config deja traza en logs');
	aud_assert(strpos($sql, 'Cfg_Reglas') !== false, 'La traza usa el campo Cfg_Reglas');
	aud_assert(strpos($sql, "'32'") !== false, 'La traza guarda el numero de reglas');
	$front = file_get_contents(dirname(__FILE__) . '/../FRONT/aud_adm_config_monitoreo_1.0.php');
	aud_assert(strpos($front, 'aud_html_banner_captura') !== false, 'Config muestra aviso de captura');
	$log = file_get_contents(dirname(__FILE__) . '/../LOGICA/aud_log_config_monitoreo.php');
	aud_assert(strpos($log, 'aud_cfg_trazar_cambio') !== false, 'Tras guardar se traza el cambio');
	$aud = file_get_contents(dirname(__FILE__) . '/../LOGICA/aud_log_auditoria.php');
	aud_assert(strpos($aud, 'registrarLogSesion') !== false, 'Login y logout se escriben en el historial');
}

function aud_cfg_buscar_proceso($con, $needle)
{
	$sql = sentencias_cfg_monitoreo(7, array());
	$r = @mysqli_query($con, $sql);
	if (!$r) {
		return null;
	}
	$found = null;
	$needle = strtolower((string)$needle);
	while ($row = mysqli_fetch_assoc($r)) {
		$nom = strtolower(isset($row['Pcs_Nom']) ? $row['Pcs_Nom'] : '');
		if ($needle !== '' && ($nom === $needle || strpos($nom, $needle) === 0 || strpos($nom, $needle) !== false)) {
			$found = $row;
			break;
		}
	}
	mysqli_free_result($r);
	return $found;
}

function aud_cfg_buscar_venta($con)
{
	return aud_cfg_buscar_proceso($con, 'fac_alt_fac_ven_3.2');
}

function aud_unit_db_cfg_arbol_facturacion()
{
	$con = aud_db_connect();
	if (!$con) {
		echo "  SKIP  arbol config vs BD (sin conexion)\n";
		return;
	}
	$venta = aud_cfg_buscar_venta($con);
	if (!$venta) {
		echo "  SKIP  arbol config vs BD (no esta fac_alt_fac_ven_3.2)\n";
		return;
	}
	$arbol = aud_cfg_armar_arbol(array($venta));
	aud_assert(count($arbol) === 1, 'Registrar venta pertenece a un modulo');
	$mod = $arbol[0];
	aud_assert(stripos($mod['Org_Des'], 'actur') !== false, 'El modulo de la venta es Facturacion (obtuvo: ' . $mod['Org_Des'] . ')');
	$dir = $mod['directorios'][0];
	aud_assert($dir['es_modulo'] === false, 'Registrar venta cuelga de un directorio, no del modulo raiz');
	aud_assert(stripos($dir['Org_Des'], 'enta') !== false, 'El directorio es Ventas (obtuvo: ' . $dir['Org_Des'] . ')');
	aud_assert((int)$dir['procesos'][0]['Pcs_Cod'] === (int)$venta['Pcs_Cod'], 'El proceso queda bajo ese directorio');
}

function aud_cfg_count_logs($con, $usu, $emp)
{
	$r = @mysqli_query($con, "SELECT COUNT(*) AS c FROM `logs` WHERE `Usu_Cod`=" . (int)$usu . " AND `Emp_Cod`=" . (int)$emp);
	if (!$r) {
		return -1;
	}
	$row = mysqli_fetch_assoc($r);
	mysqli_free_result($r);
	return isset($row['c']) ? (int)$row['c'] : 0;
}

function aud_cfg_flush_sql($usu, $emp, $sql, $uri = '/facturacion/FRONT/fac_alt_fac_ven_3.2.php')
{
	aud_test_putenv('AUDIT_ENABLED', 'true');
	aud_test_putenv('AUDIT_TABLES', AuditQueue::DEFAULT_TABLES);
	AuditQueue::resetForTests();
	if (function_exists('session_id') && session_id() === '') {
		@session_start();
	}
	$_SESSION['Ses_Usu_Cod'] = (int)$usu;
	$_SESSION['Ses_Emp_Cod'] = (int)$emp;
	$_SESSION['Ses_Suc_Cod'] = 1;
	$_SESSION['Ses_Dat_Dis'] = 'exa';
	$_SESSION['Ses_Dat_Aut'] = 'auditoria';
	$GLOBALS['Ses_Usu_Cod'] = (int)$usu;
	$GLOBALS['Ses_Emp_Cod'] = (int)$emp;
	$GLOBALS['Ses_Suc_Cod'] = 1;
	$GLOBALS['Ses_Dat_Dis'] = 'exa';
	$GLOBALS['Ses_Dat_Aut'] = 'auditoria';
	$_SERVER['REQUEST_URI'] = $uri;
	$_SERVER['PHP_SELF'] = $uri;
	AuditQueue::capture($sql);
	aud_assert(AuditQueue::queueCount() === 1, 'El movimiento se encola antes del filtro de configuracion');
	AuditQueue::flush();
}

function aud_cfg_flush_venta($usu, $emp)
{
	aud_cfg_flush_sql($usu, $emp, "INSERT INTO ventas (Tic_Cod, Cli_Cod, Vet_Num) VALUES (1, 1, '001-001-CFGTEST')");
}

function aud_unit_db_cfg_filtra_por_nivel()
{
	$con = aud_db_connect();
	if (!$con) {
		echo "  SKIP  filtro cfg vs BD (sin conexion)\n";
		return;
	}
	$venta = aud_cfg_buscar_venta($con);
	if (!$venta || empty($venta['Mod_Cod']) || empty($venta['Dir_Cod']) || empty($venta['Pcs_Cod'])) {
		echo "  SKIP  filtro cfg vs BD (no se resolvio Registrar venta)\n";
		return;
	}
	$emp = 999001;
	$usu = 900091;
	@mysqli_query($con, "CREATE TABLE IF NOT EXISTS `cfg_monitoreo` (
		`Cfg_Cod` INT(11) NOT NULL AUTO_INCREMENT,
		`Emp_Cod` INT(11) NOT NULL,
		`Org_Cod` INT(11) NOT NULL,
		`Pcs_Cod` INT(11) NOT NULL DEFAULT 0,
		`Cfg_Est` CHAR(1) NOT NULL DEFAULT 'A',
		`Cfg_Fec` DATETIME DEFAULT NULL,
		`Usu_Cod` INT(11) DEFAULT NULL,
		PRIMARY KEY (`Cfg_Cod`),
		UNIQUE KEY `uk_emp_org_pcs` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`),
		KEY `idx_emp_est` (`Emp_Cod`,`Cfg_Est`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
	@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");

	$mod = (int)$venta['Mod_Cod'];
	$dir = (int)$venta['Dir_Cod'];
	$pcs = (int)$venta['Pcs_Cod'];
	$dirAjeno = $dir;
	$sqlDirs = "SELECT DISTINCT `Dir_Cod` FROM (" . sentencias_cfg_monitoreo(7, array()) . ") x
		WHERE `Mod_Cod`={$mod} AND `Dir_Cod`<>{$dir} AND `Dir_Cod`<>{$mod} LIMIT 1";
	$rDir = @mysqli_query($con, $sqlDirs);
	if ($rDir) {
		$rowDir = mysqli_fetch_assoc($rDir);
		mysqli_free_result($rDir);
		if (!empty($rowDir['Dir_Cod'])) {
			$dirAjeno = (int)$rowDir['Dir_Cod'];
		}
	}

	try {
		@mysqli_query($con, "INSERT INTO `cfg_monitoreo` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`,`Cfg_Est`) VALUES ({$emp},{$mod},0,'A')");
		$before = aud_cfg_count_logs($con, $usu, $emp);
		aud_cfg_flush_venta($usu, $emp);
		$after = aud_cfg_count_logs($con, $usu, $emp);
		aud_assert($after === $before + 1, 'Modulo Facturacion habilitado registra la venta (antes=' . $before . ' despues=' . $after . ')');

		@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
		@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");
		AuditQueue::resetForTests();
		@mysqli_query($con, "INSERT INTO `cfg_monitoreo` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`,`Cfg_Est`) VALUES ({$emp},{$dir},0,'A')");
		$before = aud_cfg_count_logs($con, $usu, $emp);
		aud_cfg_flush_venta($usu, $emp);
		$after = aud_cfg_count_logs($con, $usu, $emp);
		aud_assert($after === $before + 1, 'Directorio Ventas habilitado registra la venta');

		if ($dirAjeno !== $dir) {
			@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
			@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");
			AuditQueue::resetForTests();
			@mysqli_query($con, "INSERT INTO `cfg_monitoreo` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`,`Cfg_Est`) VALUES ({$emp},{$dirAjeno},0,'A')");
			$before = aud_cfg_count_logs($con, $usu, $emp);
			aud_cfg_flush_venta($usu, $emp);
			$after = aud_cfg_count_logs($con, $usu, $emp);
			aud_assert($after === $before, 'Otro directorio del mismo modulo no registra Registrar venta');
		}

		@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
		@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");
		AuditQueue::resetForTests();
		@mysqli_query($con, "INSERT INTO `cfg_monitoreo` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`,`Cfg_Est`) VALUES ({$emp},{$dir},{$pcs},'A')");
		$before = aud_cfg_count_logs($con, $usu, $emp);
		aud_cfg_flush_venta($usu, $emp);
		$after = aud_cfg_count_logs($con, $usu, $emp);
		aud_assert($after === $before + 1, 'Proceso Registrar venta puntual si se registra');
	} catch (Exception $e) {
		@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
		@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");
		AuditQueue::resetForTests();
		throw $e;
	}

	@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
	@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");
}

function aud_unit_db_cfg_captura_fuera_whitelist()
{
	$con = aud_db_connect();
	if (!$con) {
		echo "  SKIP  captura fuera de whitelist vs BD (sin conexion)\n";
		return;
	}
	$venta = aud_cfg_buscar_venta($con);
	if (!$venta || empty($venta['Mod_Cod'])) {
		echo "  SKIP  captura fuera de whitelist vs BD (no se resolvio Registrar venta)\n";
		return;
	}
	$emp = 999002;
	$usu = 900092;
	@mysqli_query($con, "CREATE TABLE IF NOT EXISTS `cfg_monitoreo` (
		`Cfg_Cod` INT(11) NOT NULL AUTO_INCREMENT,
		`Emp_Cod` INT(11) NOT NULL,
		`Org_Cod` INT(11) NOT NULL,
		`Pcs_Cod` INT(11) NOT NULL DEFAULT 0,
		`Cfg_Est` CHAR(1) NOT NULL DEFAULT 'A',
		`Cfg_Fec` DATETIME DEFAULT NULL,
		`Usu_Cod` INT(11) DEFAULT NULL,
		PRIMARY KEY (`Cfg_Cod`),
		UNIQUE KEY `uk_emp_org_pcs` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`),
		KEY `idx_emp_est` (`Emp_Cod`,`Cfg_Est`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
	@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");
	$mod = (int)$venta['Mod_Cod'];
	$sqlCli = "INSERT INTO clientes (Cli_Nom) VALUES ('CLIENTE-CFG-AUD')";

	try {
		@mysqli_query($con, "INSERT INTO `cfg_monitoreo` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`,`Cfg_Est`) VALUES ({$emp},{$mod},0,'A')");
		$before = aud_cfg_count_logs($con, $usu, $emp);
		aud_cfg_flush_sql($usu, $emp, $sqlCli);
		$after = aud_cfg_count_logs($con, $usu, $emp);
		aud_assert($after === $before + 1, 'Con modulo habilitado registra tablas fuera de AUDIT_TABLES (antes=' . $before . ' despues=' . $after . ')');

		@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
		@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");
		AuditQueue::resetForTests();
		$before = aud_cfg_count_logs($con, $usu, $emp);
		aud_cfg_flush_sql($usu, $emp, $sqlCli);
		$after = aud_cfg_count_logs($con, $usu, $emp);
		aud_assert($after === $before, 'Sin configuracion, tablas fuera de AUDIT_TABLES no se persisten');
	} catch (Exception $e) {
		@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
		@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");
		AuditQueue::resetForTests();
		throw $e;
	}

	@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
	@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");
}

function aud_unit_db_cfg_guardar_todos_y_registra()
{
	$con = aud_db_connect();
	if (!$con) {
		echo "  SKIP  guardar todos vs BD (sin conexion)\n";
		return;
	}
	$venta = aud_cfg_buscar_venta($con);
	if (!$venta || empty($venta['Mod_Cod'])) {
		echo "  SKIP  guardar todos vs BD (no se resolvio Registrar venta)\n";
		return;
	}
	$sqlTree = sentencias_cfg_monitoreo(7, array());
	$r = @mysqli_query($con, $sqlTree);
	if (!$r) {
		echo "  SKIP  guardar todos vs BD (no se cargo el arbol)\n";
		return;
	}
	$rows = array();
	while ($row = mysqli_fetch_assoc($r)) {
		$rows[] = $row;
	}
	mysqli_free_result($r);
	$arbol = aud_cfg_armar_arbol($rows);
	if (count($arbol) < 1) {
		echo "  SKIP  guardar todos vs BD (arbol vacio)\n";
		return;
	}
	$modFull = array();
	foreach ($arbol as $m) {
		$modFull[(int)$m['Org_Cod']] = true;
	}
	$items = aud_cfg_compactar_reglas($arbol, $modFull, array(), array());
	$parsed = aud_cfg_parse_items(json_encode($items));
	aud_assert(!empty($parsed['ok']) && count($parsed['items']) === count($items), 'El payload de Marcar todos se puede guardar');
	aud_assert(count($parsed['items']) >= 1, 'Marcar todos genera al menos una regla de modulo');

	$emp = 999003;
	$usu = 900093;
	@mysqli_query($con, "CREATE TABLE IF NOT EXISTS `cfg_monitoreo` (
		`Cfg_Cod` INT(11) NOT NULL AUTO_INCREMENT,
		`Emp_Cod` INT(11) NOT NULL,
		`Org_Cod` INT(11) NOT NULL,
		`Pcs_Cod` INT(11) NOT NULL DEFAULT 0,
		`Cfg_Est` CHAR(1) NOT NULL DEFAULT 'A',
		`Cfg_Fec` DATETIME DEFAULT NULL,
		`Usu_Cod` INT(11) DEFAULT NULL,
		PRIMARY KEY (`Cfg_Cod`),
		UNIQUE KEY `uk_emp_org_pcs` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`),
		KEY `idx_emp_est` (`Emp_Cod`,`Cfg_Est`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
	@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");

	try {
		@mysqli_query($con, sentencias_cfg_monitoreo(4, array($emp)));
		$n = 0;
		foreach ($parsed['items'] as $it) {
			$sqlIns = sentencias_cfg_monitoreo(5, array($emp, $it['org'], $it['pcs'], $usu));
			if (@mysqli_query($con, $sqlIns)) {
				$n++;
			}
		}
		$cnt = @mysqli_query($con, sentencias_cfg_monitoreo(6, array($emp)));
		$rowCnt = $cnt ? mysqli_fetch_assoc($cnt) : array();
		if ($cnt) {
			mysqli_free_result($cnt);
		}
		$saved = isset($rowCnt['count']) ? (int)$rowCnt['count'] : 0;
		aud_assert($saved === $n && $saved === count($parsed['items']), 'Marcar todos persiste todas las reglas (saved=' . $saved . ')');

		$before = aud_cfg_count_logs($con, $usu, $emp);
		aud_cfg_flush_sql($usu, $emp, "INSERT INTO ventas (Tic_Cod, Cli_Cod, Vet_Num) VALUES (1, 1, '001-001-TODOS')");
		$after = aud_cfg_count_logs($con, $usu, $emp);
		aud_assert($after === $before + 1, 'Con todos los modulos marcados, auditoria registra la actividad (antes=' . $before . ' despues=' . $after . ')');
	} catch (Exception $e) {
		@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
		@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");
		AuditQueue::resetForTests();
		throw $e;
	}

	@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$emp}");
	@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod`={$usu} AND `Emp_Cod`={$emp}");
}

<?php
/**
 * Suite de pruebas: unitarias + usuarios simultaneos.
 *
 * Ejecutar:
 *   php auditoria/TEST/aud_run_tests.php
 *
 * Variables opcionales:
 *   AUDIT_TEST_USERS  cantidad de usuarios simultaneos (default 8)
 *   AUDIT_TEST_OPS    movimientos por usuario (default 12)
 *   AUDIT_TEST_SKIP_DB=1  solo captura en memoria
 *
 * E2E navegador (Playwright):
 *   .\start-local.ps1
 *   cd auditoria/TEST && npm install && npx playwright install chromium
 *   npm run test:browser
 *
 * @package auditoria.TEST
 */

require_once dirname(__FILE__) . '/aud_unit_test.php';
require_once dirname(__FILE__) . '/aud_unit_monitoreo.php';
require_once dirname(__FILE__) . '/aud_unit_config.php';
require_once dirname(__FILE__) . '/aud_unit_actividad.php';
require_once dirname(__FILE__) . '/aud_unit_dashboard.php';
require_once dirname(__FILE__) . '/aud_unit_dashboard_monitoreo.php';

function aud_parse_worker_json($raw)
{
	$raw = trim($raw);
	if ($raw === '') {
		return null;
	}
	$pos = strrpos($raw, '{');
	if ($pos === false) {
		return null;
	}
	$json = substr($raw, $pos);
	$decoded = json_decode($json, true);
	return is_array($decoded) ? $decoded : null;
}

function aud_run_workers($users, $ops, $mode)
{
	$php = aud_php_bin();
	$worker = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'aud_concurrent_worker.php';
	$tmp = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tmp';
	if (!is_dir($tmp)) {
		@mkdir($tmp, 0777, true);
	}

	$procs = array();
	$t0 = microtime(true);
	for ($i = 1; $i <= $users; $i++) {
		$outFile = $tmp . DIRECTORY_SEPARATOR . 'w_' . $mode . '_' . $i . '.out';
		$errFile = $tmp . DIRECTORY_SEPARATOR . 'w_' . $mode . '_' . $i . '.err';
		@unlink($outFile);
		@unlink($errFile);
		$cmd = escapeshellarg($php) . ' ' . escapeshellarg($worker)
			. ' --user=' . (int)$i
			. ' --ops=' . (int)$ops
			. ' --mode=' . escapeshellarg($mode);
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$cmd = '"' . $cmd . '"';
		}
		$desc = array(
			0 => array('pipe', 'r'),
			1 => array('file', $outFile, 'w'),
			2 => array('file', $errFile, 'w')
		);
		$pipes = array();
		$p = proc_open($cmd, $desc, $pipes, dirname(__FILE__));
		if (is_resource($p) && isset($pipes[0])) {
			@fclose($pipes[0]);
		}
		$procs[] = array('p' => $p, 'out' => $outFile, 'err' => $errFile, 'user' => $i);
	}

	$results = array();
	$errors = array();
	foreach ($procs as $pr) {
		if (!is_resource($pr['p'])) {
			$errors[] = 'No se pudo lanzar el usuario ' . $pr['user'];
			continue;
		}
		$code = proc_close($pr['p']);
		$raw = is_file($pr['out']) ? file_get_contents($pr['out']) : '';
		$err = is_file($pr['err']) ? file_get_contents($pr['err']) : '';
		$row = aud_parse_worker_json($raw);
		if (!$row) {
			$errors[] = 'Usuario ' . $pr['user'] . ' sin JSON (exit ' . $code . '): ' . trim($raw . ' ' . $err);
			continue;
		}
		if (empty($row['ok'])) {
			$errors[] = 'Usuario ' . $pr['user'] . ': ' . $row['error'];
		}
		$results[] = $row;
	}
	$wall = aud_ms($t0);
	return array('results' => $results, 'errors' => $errors, 'wall_ms' => $wall);
}

function aud_cleanup_test_logs($con)
{
	@mysqli_query($con, "DELETE FROM `logs` WHERE `Usu_Cod` >= 900001 AND `Usu_Cod` < 901000");
}

function aud_count_test_logs($con)
{
	$r = @mysqli_query($con, "SELECT COUNT(*) AS c FROM `logs` WHERE `Usu_Cod` >= 900001 AND `Usu_Cod` < 901000");
	if (!$r) {
		return -1;
	}
	$row = mysqli_fetch_assoc($r);
	mysqli_free_result($r);
	return isset($row['c']) ? (int)$row['c'] : 0;
}

function aud_cleanup_cfg_emp($con, $emp)
{
	@mysqli_query($con, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`=" . (int)$emp);
}

/**
 * Marca todos los modulos raiz (Org_Niv=0) en cfg_monitoreo para una empresa de prueba.
 * Sin reglas ya no se registra actividad: la cobertura se logra marcando modulos.
 */
function aud_seed_cfg_emp_todos_modulos($con, $emp)
{
	$emp = (int)$emp;
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
	aud_cleanup_cfg_emp($con, $emp);
	$n = 0;
	$r = @mysqli_query($con, "SELECT `Org_Cod` FROM `exa`.`organizado` WHERE `Org_Niv`=0");
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$org = (int)$row['Org_Cod'];
			if ($org <= 0) {
				continue;
			}
			@mysqli_query($con, "INSERT IGNORE INTO `cfg_monitoreo` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`,`Cfg_Est`) VALUES ({$emp},{$org},0,'A')");
			$n++;
		}
		mysqli_free_result($r);
	}
	return $n;
}

function aud_run_concurrent_tests()
{
	$failed = 0;
	echo "\n== Usuarios simultaneos ==\n";

	$users = getenv('AUDIT_TEST_USERS');
	$ops = getenv('AUDIT_TEST_OPS');
	$users = ($users !== false && (int)$users > 0) ? (int)$users : 8;
	$ops = ($ops !== false && (int)$ops > 0) ? (int)$ops : 12;
	$skipDb = getenv('AUDIT_TEST_SKIP_DB');
	$skipDb = ($skipDb === '1' || $skipDb === 'true');

	echo "  escenario: " . $users . " usuarios x " . $ops . " movimientos (comprobantes y ventas, procesos registrados reales)\n";

	$cap = aud_run_workers($users, $ops, 'capture');
	try {
		aud_assert(count($cap['errors']) === 0, 'Todos los procesos de captura terminaron bien');
		aud_assert(count($cap['results']) === $users, 'Se lanzaron ' . $users . ' usuarios en paralelo');
		$sumCap = 0;
		$maxCap = 0;
		foreach ($cap['results'] as $row) {
			aud_assert((int)$row['queued'] === $ops, 'Usuario ' . $row['user'] . ' encolo ' . $ops . ' eventos');
			$sumCap += $row['capture_ms'];
			if ($row['capture_ms'] > $maxCap) {
				$maxCap = $row['capture_ms'];
			}
		}
		aud_assert($cap['wall_ms'] < 20000, 'Carga paralela de captura < 20 s (fue ' . $cap['wall_ms'] . ' ms)');
		aud_assert($maxCap < 2000, 'El usuario mas lento capturo en < 2 s (fue ' . $maxCap . ' ms)');
		echo "       captura paralela: pared=" . $cap['wall_ms'] . " ms, suma individual=" . round($sumCap, 2) . " ms, max=" . $maxCap . " ms\n";
		if ($sumCap > 50) {
			aud_assert($cap['wall_ms'] < ($sumCap * 0.9 + 3000), 'El tiempo de pared es menor que ejecutar los usuarios en serie (hay paralelismo)');
		}
	} catch (Exception $e) {
		$failed++;
		echo "  FAIL  captura concurrente: " . $e->getMessage() . "\n";
	}

	if ($skipDb) {
		echo "  SKIP  persistencia MySQL (AUDIT_TEST_SKIP_DB=1)\n";
		return $failed;
	}

	$con = aud_db_connect();
	if (!$con) {
		echo "  SKIP  persistencia MySQL (no hay conexion a auditoria: " . (isset($GLOBALS['aud_db_connect_error']) ? $GLOBALS['aud_db_connect_error'] : '') . ")\n";
		return $failed;
	}

	try {
		aud_cleanup_test_logs($con);
		$seeded = aud_seed_cfg_emp_todos_modulos($con, 999900);
		if ($seeded <= 0) {
			echo "  WARN  sin modulos raiz para sembrar reglas en cfg_monitoreo (exa)\n";
		}
		$before = aud_count_test_logs($con);
		$flush = aud_run_workers($users, $ops, 'flush');
		aud_assert(count($flush['errors']) === 0, 'Todos los procesos de grabado terminaron bien');
		$after = aud_count_test_logs($con);
		$expected = $users * $ops;
		$got = $after - $before;
		aud_assert($got === $expected, 'Se persistieron ' . $expected . ' logs concurrentes (fueron ' . $got . ')');
		aud_assert($flush['wall_ms'] < 60000, 'Grabado paralelo < 60 s (fue ' . $flush['wall_ms'] . ' ms)');
		$sumFlush = 0;
		$maxFlush = 0;
		foreach ($flush['results'] as $row) {
			$sumFlush += $row['flush_ms'];
			if ($row['flush_ms'] > $maxFlush) {
				$maxFlush = $row['flush_ms'];
			}
		}
		echo "       persistencia paralela: pared=" . $flush['wall_ms'] . " ms, suma=" . round($sumFlush, 2) . " ms, max=" . $maxFlush . " ms, filas=" . $got . "\n";
		if ($sumFlush > 200) {
			aud_assert($flush['wall_ms'] < ($sumFlush * 0.95 + 5000), 'El grabado en paralelo no se serializa por un lock global');
		}
		aud_cleanup_cfg_emp($con, 999900);
		aud_cleanup_test_logs($con);
		$left = aud_count_test_logs($con);
		aud_assert($left === 0, 'Se limpiaron los logs de prueba');
	} catch (Exception $e) {
		$failed++;
		echo "  FAIL  persistencia concurrente: " . $e->getMessage() . "\n";
		aud_cleanup_cfg_emp($con, 999900);
		aud_cleanup_test_logs($con);
	}
	@mysqli_close($con);
	return $failed;
}

$unitFails = aud_run_unit_tests();
$monFails = aud_run_monitoreo_tests();
$cfgFails = aud_run_config_tests();
$actFails = aud_run_actividad_tests();
$dashFails = aud_run_dashboard_tests();
$dashMonFails = aud_run_dashboard_monitoreo_tests();
$loadFails = aud_run_concurrent_tests();
$total = $unitFails + $monFails + $cfgFails + $actFails + $dashFails + $dashMonFails + $loadFails;

echo "\n========================================\n";
if ($total === 0) {
	echo "TODAS LAS PRUEBAS OK\n";
} else {
	echo "FALLOS: " . $total . "\n";
}
echo "========================================\n";
exit($total === 0 ? 0 : 1);

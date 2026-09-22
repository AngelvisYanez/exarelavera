<?php
/**
 * Verifica que la captura respete cfg_monitoreo por modulo / directorio / proceso.
 *
 * Uso:
 *   php auditoria/TEST/aud_verify_captura.php
 *   php auditoria/TEST/aud_verify_captura.php --emp=7
 *
 * @package auditoria.TEST
 */

require_once dirname(__FILE__) . '/aud_test_lib.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_log_config_monitoreo.php';

$empFilter = 0;
foreach ($argv as $arg) {
	if (strpos($arg, '--emp=') === 0) {
		$empFilter = (int)substr($arg, 6);
	}
}

function aud_verify_msg($level, $msg)
{
	echo '[' . $level . '] ' . $msg . "\n";
}

function aud_verify_connect_exa($conAud)
{
	$host = Env::get('DB_HOST', '127.0.0.1');
	$user = Env::get('DB_USERNAME', 'root');
	$pass = Env::get('DB_PASSWORD', '');
	$port = (int)Env::get('DB_PORT', 3306);
	if ($pass === null) {
		$pass = '';
	}
	if (function_exists('mysqli_report')) {
		mysqli_report(MYSQLI_REPORT_OFF);
	}
	$con = @mysqli_connect($host, $user, $pass, 'exa', $port);
	return $con ? $con : null;
}

function aud_verify_org_chain($conExa, $orgCod)
{
	$chain = array();
	$org = (int)$orgCod;
	$guard = 0;
	while ($org > 0 && $guard < 8) {
		$chain[] = $org;
		$r = @mysqli_query($conExa, "SELECT `Org_Niv` FROM `organizado` WHERE `Org_Cod`={$org} LIMIT 1");
		$parent = 0;
		if ($r) {
			$row = mysqli_fetch_assoc($r);
			mysqli_free_result($r);
			$parent = isset($row['Org_Niv']) ? (int)$row['Org_Niv'] : 0;
		}
		if ($parent <= 0) {
			break;
		}
		$org = $parent;
		$guard++;
	}
	return $chain;
}

function aud_verify_rule_covers_pcs($conAud, $conExa, $emp, $pcsCod, $dirCod, $modCod)
{
	$emp = (int)$emp;
	$pcsCod = (int)$pcsCod;
	$r = @mysqli_query($conAud, "SELECT 1 AS ok FROM `cfg_monitoreo`
		WHERE `Emp_Cod`={$emp} AND `Cfg_Est`='A' AND `Pcs_Cod`={$pcsCod} LIMIT 1");
	if ($r) {
		$row = mysqli_fetch_assoc($r);
		mysqli_free_result($r);
		if (!empty($row['ok'])) {
			return true;
		}
	}
	$orgs = aud_verify_org_chain($conExa, $dirCod);
	if ($modCod > 0 && !in_array($modCod, $orgs, true)) {
		$orgs[] = (int)$modCod;
	}
	foreach ($orgs as $org) {
		$org = (int)$org;
		if ($org <= 0) {
			continue;
		}
		$r = @mysqli_query($conAud, "SELECT 1 AS ok FROM `cfg_monitoreo`
			WHERE `Emp_Cod`={$emp} AND `Cfg_Est`='A' AND `Org_Cod`={$org} AND `Pcs_Cod`=0 LIMIT 1");
		if ($r) {
			$row = mysqli_fetch_assoc($r);
			mysqli_free_result($r);
			if (!empty($row['ok'])) {
				return true;
			}
		}
	}
	return false;
}

function aud_verify_lookup_pcs($conExa, $pcsNom)
{
	$nomRaw = basename(str_replace('\\', '/', (string)$pcsNom));
	$nomRaw = trim($nomRaw);
	if ($nomRaw === '') {
		return 0;
	}
	$nom = mysqli_real_escape_string($conExa, $nomRaw);
	$nomBare = mysqli_real_escape_string($conExa, preg_replace('/\.php$/i', '', $nomRaw));
	$sql = "SELECT `Pcs_Cod` FROM `procesos` WHERE
		`Pcs_Nom` = '{$nom}'
		OR `Pcs_Nom` = '{$nomBare}'
		OR `Pcs_Nom` = '{$nomBare}.php'
		OR `Pcs_Nom` LIKE '%/{$nom}'
		OR `Pcs_Nom` LIKE '%{$nomBare}%'
		ORDER BY CASE
			WHEN `Pcs_Nom` = '{$nom}' THEN 0
			WHEN `Pcs_Nom` = '{$nomBare}.php' THEN 1
			ELSE 2
		END
		LIMIT 1";
	$r = @mysqli_query($conExa, $sql);
	if (!$r) {
		return 0;
	}
	$row = mysqli_fetch_assoc($r);
	mysqli_free_result($r);
	return !empty($row['Pcs_Cod']) ? (int)$row['Pcs_Cod'] : 0;
}

function aud_verify_simulate_flush($usu, $emp, $sql, $uri)
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
	$_SERVER['SCRIPT_NAME'] = $uri;
	AuditQueue::capture($sql);
	$queued = AuditQueue::queueCount();
	AuditQueue::flush();
	return $queued;
}

function aud_verify_count_logs($con, $usu, $emp)
{
	$r = @mysqli_query($con, "SELECT COUNT(*) AS c FROM `logs` WHERE `Usu_Cod`=" . (int)$usu . " AND `Emp_Cod`=" . (int)$emp);
	if (!$r) {
		return -1;
	}
	$row = mysqli_fetch_assoc($r);
	mysqli_free_result($r);
	return isset($row['c']) ? (int)$row['c'] : 0;
}

echo "\n== Verificacion captura vs configuracion de monitoreo ==\n";

if (!AuditQueue::enabled()) {
	aud_verify_msg('ERROR', 'AUDIT_ENABLED esta desactivado. No se registrara actividad nueva.');
	exit(1);
}
aud_verify_msg('OK', 'AUDIT_ENABLED activo');

$conAud = aud_db_connect();
if (!$conAud) {
	aud_verify_msg('ERROR', 'Sin conexion a auditoria: ' . (isset($GLOBALS['aud_db_connect_error']) ? $GLOBALS['aud_db_connect_error'] : ''));
	exit(1);
}
$conExa = aud_verify_connect_exa($conAud);
if (!$conExa) {
	aud_verify_msg('ERROR', 'Sin conexion a exa');
	exit(1);
}

$whereEmp = $empFilter > 0 ? " WHERE `Emp_Cod`={$empFilter} AND `Cfg_Est`='A'" : " WHERE `Cfg_Est`='A'";
$rCfg = @mysqli_query($conAud, "SELECT `Emp_Cod`, COUNT(*) AS c FROM `cfg_monitoreo`{$whereEmp} GROUP BY `Emp_Cod` ORDER BY `Emp_Cod`");
if (!$rCfg) {
	aud_verify_msg('ERROR', 'No se pudo leer cfg_monitoreo');
	exit(1);
}
$empresas = array();
while ($row = mysqli_fetch_assoc($rCfg)) {
	$empresas[(int)$row['Emp_Cod']] = (int)$row['c'];
}
mysqli_free_result($rCfg);

if (count($empresas) === 0) {
	aud_verify_msg('WARN', 'Ninguna empresa tiene reglas activas en cfg_monitoreo. Solo se registran tablas AUDIT_TABLES.');
} else {
	foreach ($empresas as $emp => $cnt) {
		aud_verify_msg('INFO', "Empresa {$emp}: {$cnt} regla(s) activa(s)");
	}
}

$rTree = @mysqli_query($conExa, sentencias_cfg_monitoreo(7, array()));
if (!$rTree) {
	aud_verify_msg('ERROR', 'No se pudo cargar arbol de procesos');
	exit(1);
}
$procesos = array();
while ($row = mysqli_fetch_assoc($rTree)) {
	if (empty($row['Pcs_Cod']) || empty($row['Pcs_Nom'])) {
		continue;
	}
	$procesos[] = $row;
}
mysqli_free_result($rTree);
aud_verify_msg('INFO', 'Procesos en menu: ' . count($procesos));

$fail = 0;
$ok = 0;
$skip = 0;
$usuTest = 900099;
$testEmp = 999004;

@mysqli_query($conAud, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$testEmp}");
@mysqli_query($conAud, "DELETE FROM `logs` WHERE `Usu_Cod`={$usuTest} AND `Emp_Cod`={$testEmp}");

if (count($empresas) === 0) {
	aud_verify_msg('SKIP', 'Prueba por reglas: sin cfg activa en empresas reales');
	$skip++;
} else {
	$modsMarked = array();
	foreach ($procesos as $p) {
		if (!empty($p['Mod_Cod'])) {
			$modsMarked[(int)$p['Mod_Cod']] = true;
		}
	}
	$nIns = 0;
	foreach (array_keys($modsMarked) as $modCod) {
		if (@mysqli_query($conAud, "INSERT INTO `cfg_monitoreo` (`Emp_Cod`,`Org_Cod`,`Pcs_Cod`,`Cfg_Est`) VALUES ({$testEmp},{$modCod},0,'A')")) {
			$nIns++;
		}
	}
	aud_verify_msg('INFO', "Empresa de prueba {$testEmp} (aislada): {$nIns} modulo(s) marcados");

	$sampleSql = "INSERT INTO clientes (Cli_Nom) VALUES ('VERIFY-AUD-" . date('His') . "')";
	$tested = 0;
	$maxTest = 12;
	foreach ($procesos as $p) {
		if ($tested >= $maxTest) {
			break;
		}
		$pcs = (int)$p['Pcs_Cod'];
		$dir = isset($p['Dir_Cod']) ? (int)$p['Dir_Cod'] : 0;
		$mod = isset($p['Mod_Cod']) ? (int)$p['Mod_Cod'] : 0;
		if (!aud_verify_rule_covers_pcs($conAud, $conExa, $testEmp, $pcs, $dir, $mod)) {
			continue;
		}
		$nom = isset($p['Pcs_Nom']) ? $p['Pcs_Nom'] : '';
		$uri = '/modulo/FRONT/' . basename(str_replace('\\', '/', $nom));
		if ($nom === '') {
			continue;
		}
		$before = aud_verify_count_logs($conAud, $usuTest, $testEmp);
		$q = aud_verify_simulate_flush($usuTest, $testEmp, $sampleSql, $uri);
		$after = aud_verify_count_logs($conAud, $usuTest, $testEmp);
		$label = (isset($p['Pcs_Lin']) ? $p['Pcs_Lin'] : $nom) . ' [' . $nom . ']';
		if ($q !== 1) {
			aud_verify_msg('FAIL', "No encolo SQL desde {$label}");
			$fail++;
		} elseif ($after !== $before + 1) {
			aud_verify_msg('FAIL', "Encolo pero no persistio desde {$label} (antes={$before} despues={$after})");
			$fail++;
		} else {
			aud_verify_msg('OK', "Registro OK: {$label}");
			$ok++;
		}
		$tested++;
		@mysqli_query($conAud, "DELETE FROM `logs` WHERE `Usu_Cod`={$usuTest} AND `Emp_Cod`={$testEmp}");
	}
	@mysqli_query($conAud, "DELETE FROM `cfg_monitoreo` WHERE `Emp_Cod`={$testEmp}");
}

$lookupFail = 0;
foreach (array_slice($procesos, 0, 50) as $p) {
	$nom = isset($p['Pcs_Nom']) ? $p['Pcs_Nom'] : '';
	$basename = basename(str_replace('\\', '/', $nom));
	$found = aud_verify_lookup_pcs($conExa, $basename);
	if ($found <= 0 && $nom !== '') {
		$lookupFail++;
		if ($lookupFail <= 5) {
			aud_verify_msg('WARN', "Lookup falla para Pcs_Nom en menu: {$nom}");
		}
	}
}
if ($lookupFail > 0) {
	aud_verify_msg('WARN', "Procesos del menu sin lookup por basename: {$lookupFail} (muestra max 5)");
} else {
	aud_verify_msg('OK', 'Lookup de procesos del menu por basename');
}

$rLogs = @mysqli_query($conAud, "SELECT COUNT(*) AS c FROM `logs` WHERE `Log_Fec` >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$rowLogs = $rLogs ? mysqli_fetch_assoc($rLogs) : array('c' => 0);
if ($rLogs) {
	mysqli_free_result($rLogs);
}
aud_verify_msg('INFO', 'Logs ultimos 7 dias: ' . (int)$rowLogs['c']);

$rEmp0 = @mysqli_query($conAud, "SELECT COUNT(*) AS c FROM `logs` WHERE `Log_Fec` >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND (`Emp_Cod` IS NULL OR `Emp_Cod`=0)");
$rowEmp0 = $rEmp0 ? mysqli_fetch_assoc($rEmp0) : array('c' => 0);
if ($rEmp0) {
	mysqli_free_result($rEmp0);
}
if ((int)$rowEmp0['c'] > 0) {
	aud_verify_msg('WARN', 'Hay ' . (int)$rowEmp0['c'] . ' logs recientes sin Emp_Cod (no aparecen filtrados por empresa en el monitor)');
}

$rPcs0 = @mysqli_query($conAud, "SELECT COUNT(*) AS c FROM `logs` WHERE `Log_Fec` >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND `Pcs_Cod`=0");
$rowPcs0 = $rPcs0 ? mysqli_fetch_assoc($rPcs0) : array('c' => 0);
if ($rPcs0) {
	mysqli_free_result($rPcs0);
}
aud_verify_msg('INFO', 'Logs recientes con Pcs_Cod=0 (proceso no resuelto): ' . (int)$rowPcs0['c']);

@mysqli_close($conExa);
@mysqli_close($conAud);

echo "\nResumen: {$ok} OK, {$fail} fallos, {$skip} omitidos\n";
if ($fail > 0) {
	echo "RESULTADO: HAY GAPS EN LA CAPTURA\n";
	exit(1);
}
echo "RESULTADO: CAPTURA COHERENTE CON LA CONFIGURACION\n";
exit(0);

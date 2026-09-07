<?php
/**
 * Conteo de logs recientes para E2E (sin datos sensibles).
 * Uso: php auditoria/TEST/aud_e2e_log_count.php --emp=1 --usu=5 [--minutes=30]
 */
require_once dirname(__FILE__) . '/../../Librerias/config.php/register_globals.php';

$emp = 0;
$usu = 0;
$minutes = 30;
foreach ($argv as $arg) {
	if (strpos($arg, '--emp=') === 0) {
		$emp = (int)substr($arg, 6);
	}
	if (strpos($arg, '--usu=') === 0) {
		$usu = (int)substr($arg, 6);
	}
	if (strpos($arg, '--minutes=') === 0) {
		$minutes = max(1, (int)substr($arg, 10));
	}
}

$host = Env::get('DB_HOST', '127.0.0.1');
$port = (int)Env::get('DB_PORT', 3306);
$user = Env::get('DB_USERNAME', 'root');
$pass = Env::get('DB_PASSWORD', '');
if ($pass === null) {
	$pass = '';
}
$con = @mysqli_connect($host, $user, $pass, 'auditoria', $port);
if (!$con) {
	echo json_encode(array('ok' => false, 'error' => 'Sin conexion auditoria'));
	exit(1);
}

$where = "`Log_Fec` >= DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)";
if ($emp > 0) {
	$where .= " AND `Emp_Cod`={$emp}";
}
if ($usu > 0) {
	$where .= " AND `Usu_Cod`={$usu}";
}
$r = mysqli_query($con, "SELECT COUNT(*) AS c FROM `logs` WHERE {$where}");
$row = $r ? mysqli_fetch_assoc($r) : array('c' => 0);
if ($r) {
	mysqli_free_result($r);
}

$r2 = mysqli_query($con, "SELECT COUNT(*) AS c FROM `cfg_monitoreo` WHERE `Cfg_Est`='A'" . ($emp > 0 ? " AND `Emp_Cod`={$emp}" : ''));
$row2 = $r2 ? mysqli_fetch_assoc($r2) : array('c' => 0);
if ($r2) {
	mysqli_free_result($r2);
}
mysqli_close($con);

echo json_encode(array(
	'ok' => true,
	'count' => (int)$row['c'],
	'cfg_rules' => (int)$row2['c'],
	'emp' => $emp,
	'usu' => $usu,
	'minutes' => $minutes
));

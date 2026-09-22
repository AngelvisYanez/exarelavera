<?php
/**
 * Resuelve credenciales E2E sin exponer datos personales en consola.
 * Uso: php auditoria/TEST/aud_e2e_resolve_login.php
 *
 * Variables: AUDIT_E2E_USER, AUDIT_E2E_PASS, AUDIT_E2E_EMP (opcional)
 */
require_once dirname(__FILE__) . '/../../Librerias/config.php/register_globals.php';

$user = getenv('AUDIT_E2E_USER');
$pass = getenv('AUDIT_E2E_PASS');
$empPref = (int)getenv('AUDIT_E2E_EMP');

if ($user === false || $user === '' || $pass === false || $pass === '') {
	echo json_encode(array('ok' => false, 'error' => 'Defina AUDIT_E2E_USER y AUDIT_E2E_PASS'));
	exit(1);
}

$host = Env::get('DB_HOST', '127.0.0.1');
$port = (int)Env::get('DB_PORT', 3306);
$dbUser = Env::get('DB_USERNAME', 'root');
$dbPass = Env::get('DB_PASSWORD', '');
if ($dbPass === null) {
	$dbPass = '';
}

$con = @mysqli_connect($host, $dbUser, $dbPass, 'exa_master', $port);
if (!$con) {
	echo json_encode(array('ok' => false, 'error' => 'Sin conexion exa_master'));
	exit(1);
}

$userEsc = mysqli_real_escape_string($con, trim($user));
$passMd5 = md5(trim($pass));
$sql = "SELECT a.Suc_Cod, d.Emp_Cod, d.Dat_Dis, d.Dat_Aut
	FROM access a
	INNER JOIN data d ON a.Dat_Cod = d.Dat_Cod
	WHERE a.Acc_Usr = '{$userEsc}' AND a.Acc_Est = 'A' AND d.Dat_Est = 'A'";
if ($empPref > 0) {
	$sql .= " AND d.Emp_Cod = {$empPref}";
}
$sql .= " ORDER BY d.Emp_Cod ASC LIMIT 1";
$r = mysqli_query($con, $sql);
$acc = $r ? mysqli_fetch_assoc($r) : null;
if ($r) {
	mysqli_free_result($r);
}
if (!$acc) {
	echo json_encode(array('ok' => false, 'error' => 'Usuario sin acceso en access/data'));
	mysqli_close($con);
	exit(1);
}

$datDis = $acc['Dat_Dis'];
$conD = @mysqli_connect($host, $dbUser, $dbPass, $datDis, $port);
if (!$conD) {
	echo json_encode(array('ok' => false, 'error' => 'Sin conexion distribuida'));
	mysqli_close($con);
	exit(1);
}

$empCod = (int)$acc['Emp_Cod'];
$sucCod = (int)$acc['Suc_Cod'];
$sqlU = "SELECT u.Usu_Cod
	FROM usuarios u
	INNER JOIN sucursal s ON u.Suc_Cod = s.Suc_Cod
	WHERE u.Usu_Ced = '{$userEsc}' AND u.Usu_Pal = '{$passMd5}' AND u.Usu_Est = 'A' AND s.Emp_Cod = {$empCod}
	LIMIT 1";
$rU = mysqli_query($conD, $sqlU);
$row = $rU ? mysqli_fetch_assoc($rU) : null;
if ($rU) {
	mysqli_free_result($rU);
}
mysqli_close($conD);
mysqli_close($con);

if (!$row) {
	echo json_encode(array('ok' => false, 'error' => 'Credenciales invalidas o usuario inactivo'));
	exit(1);
}

echo json_encode(array(
	'ok' => true,
	'usu_cod' => (int)$row['Usu_Cod'],
	'emp_cod' => $empCod,
	'suc_cod' => $sucCod,
	'dat_dis' => $datDis,
	'dat_aut' => isset($acc['Dat_Aut']) ? $acc['Dat_Aut'] : 'auditoria'
));


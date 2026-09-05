<?php
/**
 * Dispara I/U/D auditables va stack web (solo local).
 * Requiere sesion activa. Uso en E2E browser.
 */
require_once dirname(__FILE__) . '/../../Librerias/config.php/register_globals.php';
require_once dirname(__FILE__) . '/../../DATA/MysqlDatos.php';
require_once dirname(__FILE__) . '/../../DATA/MysqlConexion.php';
require_once dirname(__FILE__) . '/aud_test_lib.php';

header('Content-Type: application/json; charset=utf-8');

$env = Env::get('APP_ENV', 'production');
if (strtolower((string)$env) !== 'local') {
	echo json_encode(array('ok' => false, 'error' => 'Solo APP_ENV=local'));
	exit(1);
}

if (empty($_SESSION['Ses_Usu_Cod']) || empty($_SESSION['Ses_Emp_Cod'])) {
	echo json_encode(array('ok' => false, 'error' => 'Sin sesion'));
	exit(1);
}

$routes = array(
	array('mod' => 'relavera', 'uri' => '/relavera/FRONT/man_fac_man.php', 'sql' => "UPDATE manifiesto SET Man_Est=Man_Est WHERE Man_Cod=(SELECT Man_Cod FROM (SELECT Man_Cod FROM manifiesto LIMIT 1) t)"),
	array('mod' => 'contabilidad', 'uri' => '/contabilidad/FRONT/con_alt_planc_3.0.php', 'sql' => "UPDATE comprobantes SET Com_Con=Com_Con WHERE Com_Cod=(SELECT Com_Cod FROM (SELECT Com_Cod FROM comprobantes LIMIT 1) t)"),
	array('mod' => 'facturacion', 'uri' => '/facturacion/FRONT/fac_alt_vendedor_1.0.php', 'sql' => "UPDATE ventas SET Vet_Des=Vet_Des WHERE Vet_Cod=(SELECT Vet_Cod FROM (SELECT Vet_Cod FROM ventas LIMIT 1) t)"),
	array('mod' => 'tesoreria', 'uri' => '/tesoreria/FRONT/tes_alt_ccpp_ajuste.php', 'sql' => "DELETE FROM asientos WHERE Asi_Cod=(SELECT Asi_Cod FROM (SELECT Asi_Cod FROM asientos LIMIT 1) t)"),
);

$datDis = isset($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : 'exa';
$con = new MysqlConexion($datDis);
$db = new MysqlDatos($con);

$done = array();
$errors = array();
$totalQueued = 0;
foreach ($routes as $route) {
	$_SERVER['REQUEST_URI'] = $route['uri'];
	$_SERVER['PHP_SELF'] = $route['uri'];
	$_SERVER['SCRIPT_NAME'] = $route['uri'];
	$beforeQ = AuditQueue::queueCount();
	$rs = @$db->consulta($route['sql'], $con->conexion);
	$queued = AuditQueue::queueCount() - $beforeQ;
	$totalQueued += $queued;
	$done[] = array(
		'mod' => $route['mod'],
		'queued' => $queued,
		'sql_ok' => $rs ? true : false
	);
}
try {
	AuditQueue::flush(false);
} catch (Exception $e) {
	$errors[] = $e->getMessage();
}

echo json_encode(array(
	'ok' => count($errors) === 0,
	'modules' => $done,
	'errors' => $errors
));

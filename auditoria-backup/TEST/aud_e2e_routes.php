<?php
/**
 * Lista rutas FRONT de procesos activos para E2E (sin datos de usuarios).
 * Uso: php auditoria/TEST/aud_e2e_routes.php [--limit=8] [--all]
 */
require_once dirname(__FILE__) . '/../../Librerias/config.php/register_globals.php';

$limit = 8;
$all = false;
foreach ($argv as $arg) {
	if (strpos($arg, '--limit=') === 0) {
		$limit = max(1, min(1000, (int)substr($arg, 8)));
	}
	if ($arg === '--all') {
		$all = true;
	}
}

$host = Env::get('DB_HOST', '127.0.0.1');
$port = (int)Env::get('DB_PORT', 3306);
$user = Env::get('DB_USERNAME', 'root');
$pass = Env::get('DB_PASSWORD', '');
if ($pass === null) {
	$pass = '';
}
$con = @mysqli_connect($host, $user, $pass, 'exa', $port);
if (!$con) {
	echo json_encode(array('ok' => false, 'error' => 'Sin conexion exa'));
	exit(1);
}

$mods = array(
	'contabilidad' => 0,
	'facturacion' => 0,
	'tesoreria' => 0,
	'relavera' => 0,
	'auditoria' => 0,
	'administrador' => 0
);

$sql = "SELECT p.Pcs_Cod, p.Pcs_Nom, p.Pcs_Lin, o.Org_Des, o.Org_Niv, r.Rut_Des
	FROM procesos p
	JOIN organizado o ON p.Org_Cod = o.Org_Cod
	LEFT JOIN rutas r ON p.Rut_Cod = r.Rut_Cod
	WHERE p.Pcs_Est = 'A' AND p.Pcs_Nom LIKE '%.php'
	ORDER BY p.Pcs_Ord, p.Pcs_Cod
	LIMIT 500";
$r = mysqli_query($con, $sql);
$routes = array();
while ($r && ($row = mysqli_fetch_assoc($r))) {
	$nom = str_replace('\\', '/', (string)$row['Pcs_Nom']);
	$mod = '';
	foreach (array_keys($mods) as $m) {
		if (stripos($nom, $m . '/') === 0 || stripos($nom, $m . '/') !== false) {
			$mod = $m;
			break;
		}
	}
	if ($mod === '' && !empty($row['Rut_Des'])) {
		foreach (array_keys($mods) as $m) {
			if (stripos($row['Rut_Des'], '/' . $m . '/') !== false) {
				$mod = $m;
				break;
			}
		}
	}
	if ($mod === '') {
		continue;
	}
	$path = $nom;
	if (strpos($path, '/') === false && !empty($row['Rut_Des'])) {
		$base = rtrim(str_replace('\\', '/', $row['Rut_Des']), '/');
		$path = $base . '/' . $nom;
	}
	if ($path[0] !== '/') {
		$path = '/' . ltrim($path, '/');
	}
	$routes[] = array(
		'mod' => $mod,
		'pcs_cod' => (int)$row['Pcs_Cod'],
		'label' => isset($row['Pcs_Lin']) ? $row['Pcs_Lin'] : $nom,
		'path' => $path
	);
	if (!$all && count($routes) >= $limit) {
		break;
	}
}
if ($r) {
	mysqli_free_result($r);
}
mysqli_close($con);

echo json_encode(array('ok' => true, 'routes' => $routes));

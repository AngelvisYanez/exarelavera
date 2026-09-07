<?php
/**
 * Crea o reutiliza usuario E2E local (solo desarrollo).
 * Uso: php auditoria/TEST/aud_e2e_bootstrap.php
 */
require_once dirname(__FILE__) . '/../../Librerias/config.php/register_globals.php';

$ced = '9998887770';
$pass = 'E2eTest!2026';
$passMd5 = md5($pass);

$host = Env::get('DB_HOST', '127.0.0.1');
$port = (int)Env::get('DB_PORT', 3306);
$dbUser = Env::get('DB_USERNAME', 'root');
$dbPass = Env::get('DB_PASSWORD', '');
if ($dbPass === null) {
	$dbPass = '';
}

$conM = @mysqli_connect($host, $dbUser, $dbPass, 'exa_master', $port);
if (!$conM) {
	echo json_encode(array('ok' => false, 'error' => 'Sin conexion exa_master'));
	exit(1);
}

$rEmp = mysqli_query($conM, "SELECT d.Emp_Cod, d.Dat_Cod, d.Dat_Dis, d.Dat_Aut, s.Suc_Cod
	FROM data d
	INNER JOIN empresas e ON e.Emp_Cod = d.Emp_Cod
	INNER JOIN sucursal s ON s.Emp_Cod = e.Emp_Cod
	WHERE e.Emp_Est = 'A' AND d.Dat_Est = 'A' AND s.Suc_Est = 'A'
	ORDER BY d.Emp_Cod ASC LIMIT 1");
$emp = $rEmp ? mysqli_fetch_assoc($rEmp) : null;
if ($rEmp) {
	mysqli_free_result($rEmp);
}
if (!$emp) {
	echo json_encode(array('ok' => false, 'error' => 'No hay empresa activa con data'));
	exit(1);
}

$empCod = (int)$emp['Emp_Cod'];
$sucCod = (int)$emp['Suc_Cod'];
$datCod = (int)$emp['Dat_Cod'];
$datDis = $emp['Dat_Dis'];

$conD = @mysqli_connect($host, $dbUser, $dbPass, $datDis, $port);
if (!$conD) {
	echo json_encode(array('ok' => false, 'error' => 'Sin conexion distribuida ' . $datDis));
	exit(1);
}

$row = null;
$rU = mysqli_query($conD, "SELECT Usu_Cod FROM usuarios WHERE Usu_Ced='{$ced}' LIMIT 1");
if ($rU) {
	$row = mysqli_fetch_assoc($rU);
	mysqli_free_result($rU);
}

if (!$row) {
	$rPrs = mysqli_query($conD, "SELECT Prs_Cod FROM persona LIMIT 1");
	$prs = $rPrs ? mysqli_fetch_assoc($rPrs) : null;
	if ($rPrs) {
		mysqli_free_result($rPrs);
	}
	$prsCod = $prs ? (int)$prs['Prs_Cod'] : 1;
	mysqli_query($conD, "INSERT INTO usuarios (Prs_Cod, Suc_Cod, Usu_Ced, Usu_Pal, Usu_Est, Usu_Tip, Usu_Cad)
		VALUES ({$prsCod}, {$sucCod}, '{$ced}', '{$passMd5}', 'A', 'A', 'N')");
	$usuCod = (int)mysqli_insert_id($conD);
} else {
	$usuCod = (int)$row['Usu_Cod'];
	mysqli_query($conD, "UPDATE usuarios SET Usu_Pal='{$passMd5}', Usu_Est='A', Suc_Cod={$sucCod} WHERE Usu_Cod={$usuCod}");
}

mysqli_query($conM, "DELETE FROM access WHERE Acc_Usr='{$ced}'");
mysqli_query($conM, "INSERT INTO access (Acc_Usr, Suc_Cod, Dat_Cod, Acc_Est) VALUES ('{$ced}', {$sucCod}, {$datCod}, 'A')");

$rPer = mysqli_query($conD, "SELECT Per_Cod FROM perfiles WHERE Per_Des LIKE '%Administrador de Sistemas%' LIMIT 1");
$per = $rPer ? mysqli_fetch_assoc($rPer) : null;
if ($rPer) {
	mysqli_free_result($rPer);
}
$perCod = 0;
if ($per) {
	$perCod = (int)$per['Per_Cod'];
	mysqli_query($conD, "DELETE FROM usuarperfi WHERE Usu_Cod={$usuCod}");
	mysqli_query($conD, "INSERT INTO usuarperfi (Usu_Cod, Per_Cod) VALUES ({$usuCod}, {$perCod})");
	$noms = "'aud_con_monitoreo_1.0.php','aud_adm_config_monitoreo_1.0.php'";
	mysqli_query($conD, "INSERT INTO perfiorgan (Per_Cod, Pcs_Cod)
		SELECT {$perCod}, p.Pcs_Cod
		FROM procesos p
		WHERE p.Pcs_Nom IN ({$noms})
		AND NOT EXISTS (
			SELECT 1 FROM perfiorgan po
			WHERE po.Per_Cod = {$perCod} AND po.Pcs_Cod = p.Pcs_Cod
		)");
}

mysqli_close($conD);
mysqli_close($conM);

$conAud = @mysqli_connect($host, $dbUser, $dbPass, 'auditoria', $port);
if ($conAud) {
	$rCfg = mysqli_query($conAud, "SELECT COUNT(*) AS c FROM cfg_monitoreo WHERE Emp_Cod={$empCod} AND Cfg_Est='A'");
	$cfg = $rCfg ? mysqli_fetch_assoc($rCfg) : array('c' => 0);
	if ($rCfg) {
		mysqli_free_result($rCfg);
	}
	if ((int)$cfg['c'] === 0) {
		$conExa = @mysqli_connect($host, $dbUser, $dbPass, $datDis, $port);
		if ($conExa) {
			$rMods = mysqli_query($conExa, "SELECT Org_Cod FROM organizado WHERE Org_Niv=0 AND Org_Mod='A' LIMIT 5");
			while ($rMods && ($m = mysqli_fetch_assoc($rMods))) {
				$org = (int)$m['Org_Cod'];
				mysqli_query($conAud, "INSERT IGNORE INTO cfg_monitoreo (Emp_Cod, Org_Cod, Pcs_Cod, Cfg_Est, Cfg_Fec, Usu_Cod)
					VALUES ({$empCod}, {$org}, 0, 'A', NOW(), {$usuCod})");
			}
			if ($rMods) {
				mysqli_free_result($rMods);
			}
			mysqli_close($conExa);
		}
	}
	mysqli_close($conAud);
}

echo json_encode(array(
	'ok' => true,
	'user' => $ced,
	'pass' => $pass,
	'usu_cod' => $usuCod,
	'emp_cod' => $empCod,
	'suc_cod' => $sucCod,
	'dat_dis' => $datDis,
	'per_cod' => $perCod
));


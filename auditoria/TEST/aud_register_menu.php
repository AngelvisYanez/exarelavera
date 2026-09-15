<?php
/**
 * Registra Directorio Auditoria + subdirectorio Monitoreo + procesos
 * en la base distribuida (idempotente).
 *
 * Uso: php auditoria/TEST/aud_register_menu.php [nombre_bd]
 * Default: exa
 */
$dbName = isset($argv[1]) ? preg_replace('/[^a-zA-Z0-9_]/', '', $argv[1]) : 'exa';
if ($dbName === '') {
	$dbName = 'exa';
}

$m = new mysqli('127.0.0.1', 'root', '', $dbName);
if ($m->connect_error) {
	fwrite(STDERR, "ERROR conexion: {$m->connect_error}\n");
	exit(1);
}
$m->set_charset('utf8');
echo "BD={$dbName}\n";

function q($m, $sql) {
	$r = $m->query($sql);
	if ($r === false) {
		fwrite(STDERR, "SQL ERR: {$m->error}\nSQL: {$sql}\n");
		exit(2);
	}
	return $r;
}

function one($m, $sql) {
	$r = q($m, $sql);
	$row = $r->fetch_assoc();
	if ($r instanceof mysqli_result) {
		$r->free();
	}
	return $row ? $row : array();
}

// 1) Ruta
$row = one($m, "SELECT Rut_Cod FROM rutas WHERE Rut_Des LIKE '%/auditoria/FRONT/%' LIMIT 1");
if (empty($row['Rut_Cod'])) {
	q($m, "INSERT INTO rutas (Rut_Des, Rut_Est, Rut_De2) VALUES ('/auditoria/FRONT/', 'A', '/auditoria/FRONT/')");
	$rutCod = (int)$m->insert_id;
	echo "Ruta creada Rut_Cod={$rutCod}\n";
} else {
	$rutCod = (int)$row['Rut_Cod'];
	echo "Ruta existente Rut_Cod={$rutCod}\n";
}

// 2) Directorio modulo Auditoria (Org_Niv=0)
$row = one($m, "SELECT Org_Cod FROM organizado WHERE Org_Des='Auditoria' AND Org_Niv=0 LIMIT 1");
if (empty($row['Org_Cod'])) {
	$ord = one($m, "SELECT IFNULL(MAX(Org_Ord),0)+1 AS o FROM organizado WHERE Org_Niv=0 AND Org_Ord < 90");
	$orgOrd = isset($ord['o']) ? (int)$ord['o'] : 1;
	q($m, "INSERT INTO organizado (Org_Niv, Org_Det, Org_Ord, Org_Mod, Org_Des, Org_Img, Org_Ime, Org_Ico)
		VALUES (0, 'Modulo para consultar y configurar el monitoreo de actividades', {$orgOrd}, 'A', 'Auditoria', 'folder-open-off.png', 'folder-open-on.png', 'fa fa-history')");
	$orgMod = (int)$m->insert_id;
	echo "Directorio Auditoria creado Org_Cod={$orgMod}\n";
} else {
	$orgMod = (int)$row['Org_Cod'];
	q($m, "UPDATE organizado SET Org_Mod='A', Org_Det='Modulo para consultar y configurar el monitoreo de actividades', Org_Ico='fa fa-history' WHERE Org_Cod={$orgMod}");
	echo "Directorio Auditoria existente Org_Cod={$orgMod}\n";
}

// 3) Subdirectorio Monitoreo (Org_Niv = Org_Cod del modulo)
$row = one($m, "SELECT Org_Cod FROM organizado WHERE Org_Des='Monitoreo' AND Org_Niv={$orgMod} LIMIT 1");
if (empty($row['Org_Cod'])) {
	q($m, "INSERT INTO organizado (Org_Niv, Org_Det, Org_Ord, Org_Mod, Org_Des, Org_Img, Org_Ime, Org_Ico)
		VALUES ({$orgMod}, 'Consulta y configuracion del monitoreo de actividades de usuarios', 1, 'A', 'Monitoreo', 'folder-open-off.png', 'folder-open-on.png', 'fa fa-eye')");
	$orgMon = (int)$m->insert_id;
	echo "Subdirectorio Monitoreo creado Org_Cod={$orgMon}\n";
} else {
	$orgMon = (int)$row['Org_Cod'];
	q($m, "UPDATE organizado SET Org_Mod='A', Org_Det='Consulta y configuracion del monitoreo de actividades de usuarios', Org_Ico='fa fa-eye' WHERE Org_Cod={$orgMon}");
	echo "Subdirectorio Monitoreo existente Org_Cod={$orgMon}\n";
}

// 4) Procesos
$procs = array(
	array(
		'nom' => 'aud_con_monitoreo_1.0.php',
		'lin' => 'Consultar',
		'det' => 'Permite consultar las actividades y cambios realizados por los usuarios',
		'ord' => 1,
		'ico' => 'fa fa-search'
	),
	array(
		'nom' => 'aud_adm_config_monitoreo_1.0.php',
		'lin' => 'Configurar',
		'det' => 'Configura que modulos, directorios y procesos se registran en el monitoreo de actividades',
		'ord' => 2,
		'ico' => 'fa fa-cogs'
	),
	array(
		'nom' => 'aud_con_actividad_usuarios_1.0.php',
		'lin' => 'Actividad de Usuarios',
		'det' => 'Monitor en vivo de usuarios conectados, tiempos de uso, equipos y cierre forzado de sesiones',
		'ord' => 3,
		'ico' => 'fa fa-users'
	),
	array(
		'nom' => 'aud_con_dashboard_monitoreo_1.0.php',
		'lin' => 'Panel Estadistico',
		'det' => 'Tablero ejecutivo con metricas y graficos interactivos de la actividad de auditoria',
		'ord' => 4,
		'ico' => 'fa fa-tachometer'
	),
	array(
		'nom' => 'aud_con_dashboard_comparativo_1.0.php',
		'lin' => 'Panel Comparativo',
		'det' => 'Tablero estadistico comparativo de la actividad de auditoria entre dos periodos con PDF',
		'ord' => 5,
		'ico' => 'fa fa-chart-line'
	)
);

foreach ($procs as $p) {
	$nom = $m->real_escape_string($p['nom']);
	$lin = $m->real_escape_string($p['lin']);
	$det = $m->real_escape_string($p['det']);
	$ico = $m->real_escape_string($p['ico']);
	$ord = (int)$p['ord'];
	$row = one($m, "SELECT Pcs_Cod, Org_Cod FROM procesos WHERE Pcs_Nom='{$nom}' LIMIT 1");
	if (empty($row['Pcs_Cod'])) {
		q($m, "INSERT INTO procesos (Org_Cod, Pcs_Det, Pcs_Ord, Pcs_Lin, Pcs_Est, Rut_Cod, Pcs_Nom, Tpr_Cod, Pcs_Img, Pcs_Tip, Pcs_Ico, Pcs_Int)
			VALUES ({$orgMon}, '{$det}', {$ord}, '{$lin}', 'A', {$rutCod}, '{$nom}', 1, 'arrow-on.png', 'P', '{$ico}', 'N')");
		$pcs = (int)$m->insert_id;
		echo "Proceso {$p['lin']} creado Pcs_Cod={$pcs}\n";
	} else {
		$pcs = (int)$row['Pcs_Cod'];
		q($m, "UPDATE procesos SET
			Org_Cod={$orgMon},
			Pcs_Det='{$det}',
			Pcs_Ord={$ord},
			Pcs_Lin='{$lin}',
			Pcs_Est='A',
			Rut_Cod={$rutCod},
			Tpr_Cod=1,
			Pcs_Img='arrow-on.png',
			Pcs_Tip='P',
			Pcs_Ico='{$ico}',
			Pcs_Int='N'
			WHERE Pcs_Cod={$pcs}");
		echo "Proceso {$p['lin']} actualizado Pcs_Cod={$pcs} -> Org_Cod={$orgMon}\n";
	}

	// Perfiles: Administrador de Sistemas y Gerente (gerencia)
	q($m, "INSERT INTO perfiorgan (Per_Cod, Pcs_Cod)
		SELECT p.Per_Cod, {$pcs}
		FROM perfiles p
		WHERE p.Per_Des IN ('Administrador de Sistemas', 'Gerente')
		AND NOT EXISTS (
			SELECT 1 FROM perfiorgan po WHERE po.Per_Cod = p.Per_Cod AND po.Pcs_Cod = {$pcs}
		)");
}

echo "\n== Resumen ==\n";
$r = q($m, "SELECT o.Org_Cod, o.Org_Niv, o.Org_Des FROM organizado o
	WHERE o.Org_Cod IN ({$orgMod},{$orgMon}) OR o.Org_Niv={$orgMod}
	ORDER BY o.Org_Niv, o.Org_Ord");
while ($x = $r->fetch_assoc()) {
	echo "DIR {$x['Org_Cod']} niv={$x['Org_Niv']} {$x['Org_Des']}\n";
}
$r = q($m, "SELECT Pcs_Cod, Org_Cod, Pcs_Lin, Pcs_Nom, Pcs_Ord FROM procesos WHERE Org_Cod={$orgMon} ORDER BY Pcs_Ord");
while ($x = $r->fetch_assoc()) {
	echo "PCS {$x['Pcs_Cod']} org={$x['Org_Cod']} [{$x['Pcs_Ord']}] {$x['Pcs_Lin']} -> {$x['Pcs_Nom']}\n";
}
echo "OK\n";

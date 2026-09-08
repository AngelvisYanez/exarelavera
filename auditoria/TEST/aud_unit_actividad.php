<?php
/**
 * Pruebas unitarias de actividad de usuarios, tracking de sesiones,
 * tiempos de uso, IP, navegadores, ubicacion y control de inactividad (Fase 2).
 *
 * @package auditoria.TEST
 */

require_once dirname(__FILE__) . '/aud_test_lib.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_sql_actividad_sesion.php';
require_once dirname(__FILE__) . '/../LOGICA/aud_log_actividad_sesion.php';

function aud_unit_actividad_sentencias_sql()
{
	for ($t = 1; $t <= 11; $t++) {
		$sql = sentencias_actividad_sesion($t, array(1, 'en_linea', 1, 50, '127.0.0.1', 'Local', 'Chrome', 'tok'));
		aud_assert(is_string($sql) && strlen(trim($sql)) >= 10, "sentencias_actividad_sesion({$t}) no devolvio un SQL valido");
	}
	aud_assert(true, "Sentencias 1 al 11 generadas correctamente");
}

function aud_unit_actividad_helpers_deteccion()
{
	// Deteccion de navegador
	$uaChrome = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36";
	$nav = aud_ses_detectar_navegador($uaChrome);
	aud_assert(strpos($nav, 'Chrome') !== false && strpos($nav, 'Windows') !== false, "aud_ses_detectar_navegador fallo para Chrome: {$nav}");

	$uaFirefox = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:120.0) Gecko/20100101 Firefox/120.0";
	$navFox = aud_ses_detectar_navegador($uaFirefox);
	aud_assert(strpos($navFox, 'Firefox') !== false && strpos($navFox, 'macOS') !== false, "aud_ses_detectar_navegador fallo para Firefox: {$navFox}");

	// Deteccion de ubicacion
	$ubiLocal = aud_ses_detectar_ubicacion('127.0.0.1');
	aud_assert(strpos($ubiLocal, 'Localhost') !== false, "aud_ses_detectar_ubicacion fallo para localhost: {$ubiLocal}");

	$ubiLan = aud_ses_detectar_ubicacion('192.168.1.100');
	aud_assert(strpos($ubiLan, 'Red Local') !== false, "aud_ses_detectar_ubicacion fallo para LAN: {$ubiLan}");
}

function aud_unit_actividad_ciclo_sesion_completo()
{
	$con = aud_db_connect();
	if (!$con) {
		return;
	}

	// 1. Asegurar esquema
	aud_ses_asegurar_esquema($con);

	// Verificar columnas
	$cols = array();
	$r = @mysqli_query($con, sentencias_actividad_sesion(1, array()));
	if ($r) {
		while ($f = mysqli_fetch_assoc($r)) {
			$cols[strtolower($f['COLUMN_NAME'])] = true;
		}
		mysqli_free_result($r);
	}
	foreach (array('ses_ip', 'ses_ubi', 'ses_nav', 'ses_ult_act', 'ses_min_uso', 'ses_est') as $reqCol) {
		aud_assert(!empty($cols[$reqCol]), "Columna requerida {$reqCol} no fue creada en auditoria.sesion");
	}

	// 2. Registrar inicio de sesion de prueba
	$testUsuCod = 99991;
	$sesCod = aud_ses_registrar_inicio($testUsuCod, 1, 1, $con);
	aud_assert($sesCod > 0, "aud_ses_registrar_inicio genero Ses_Cod valido: {$sesCod}");

	// 3. Heartbeat ping
	$resPing = aud_ses_heartbeat_ping($sesCod, $testUsuCod, $con);
	aud_assert(!empty($resPing['success']) && empty($resPing['forzar_logout']), "aud_ses_heartbeat_ping respondio exitosamente en sesion activa");

	// 4. Listar actividad y verificar que aparece
	$resLista = aud_ses_listar_actividad(1, '', 0, 50, $con);
	aud_assert(is_array($resLista['items']), "aud_ses_listar_actividad retorno items validos");
	aud_assert(is_array($resLista['kpis']), "aud_ses_listar_actividad retorno KPIs");

	// 5. Cierre forzado por administrador
	$okForzar = aud_ses_cerrar_forzada($sesCod, $con);
	aud_assert($okForzar === true, "aud_ses_cerrar_forzada cerro exitosamente la sesion");

	// 6. Comprobar que el siguiente ping detecta la expulsion
	$resPingPostKick = aud_ses_heartbeat_ping($sesCod, $testUsuCod, $con);
	aud_assert(!empty($resPingPostKick['forzar_logout']) && $resPingPostKick['motivo'] === 'forzada', "aud_ses_heartbeat_ping detecto cierre forzado");

	// 7. Cierre por inactividad
	$sesCodInact = aud_ses_registrar_inicio($testUsuCod, 1, 1, $con);
	$okInact = aud_ses_cerrar_por_inactividad($sesCodInact, $testUsuCod, $con);
	aud_assert($okInact === true, "aud_ses_cerrar_por_inactividad retorno true");

	// Limpieza de datos de prueba
	@mysqli_query($con, "DELETE FROM `auditoria`.`sesion` WHERE `Usu_Cod` = {$testUsuCod}");
	@mysqli_close($con);
}

function aud_run_actividad_tests()
{
	$failed = 0;
	$passed = 0;
	echo "\n== Pruebas actividad de usuarios y sesiones ==\n";

	$cases = array(
		'aud_unit_actividad_sentencias_sql',
		'aud_unit_actividad_helpers_deteccion',
		'aud_unit_actividad_ciclo_sesion_completo',
	);

	foreach ($cases as $fn) {
		try {
			$fn();
			$passed++;
		} catch (Exception $e) {
			$failed++;
			echo "  FAIL  " . $fn . ": " . $e->getMessage() . "\n";
		}
	}

	echo "Actividad: " . $passed . " ok, " . $failed . " fallidas\n";
	return $failed;
}

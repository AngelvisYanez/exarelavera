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
	for ($t = 1; $t <= 13; $t++) {
		$sql = sentencias_actividad_sesion($t, array(1, 'en_linea', 1, 50, '127.0.0.1', 'Local', 'Chrome', 'tok'));
		aud_assert(is_string($sql) && strlen(trim($sql)) >= 10, "sentencias_actividad_sesion({$t}) no devolvio un SQL valido");
	}
	aud_assert(true, "Sentencias 1 al 13 generadas correctamente");
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

	// Verificar indices de rendimiento del monitor en vivo
	$indexes = array();
	$rIdx = @mysqli_query($con, "SHOW INDEX FROM `auditoria`.`sesion`");
	if ($rIdx) {
		while ($fi = mysqli_fetch_assoc($rIdx)) {
			$indexes[strtolower(isset($fi['Key_name']) ? $fi['Key_name'] : '')] = true;
		}
		mysqli_free_result($rIdx);
	}
	foreach (array('idx_ses_est_act', 'idx_ses_emp', 'idx_ses_usu', 'idx_ses_int') as $reqIdx) {
		aud_assert(!empty($indexes[$reqIdx]), "Indice requerido {$reqIdx} no fue creado en auditoria.sesion");
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

/**
 * Inserta una sesion de prueba con actividad relativa a NOW() para
 * validar el semaforo calculado en tiempo real por el monitor.
 *
 * @param mysqli $con
 * @param int $usuCod
 * @param int $minUltAct Minutos desde la ultima actividad (0 = ahora)
 * @param string $estado Estado en la tabla (A, F, C)
 * @return int Ses_Cod generado
 */
function aud_act_insertar_sesion_prueba($con, $usuCod, $minUltAct, $estado)
{
	$sqlNext = "SELECT (IFNULL(MAX(`Ses_Cod`), 0) + 1) AS `n` FROM `auditoria`.`sesion`";
	$rNext = @mysqli_query($con, $sqlNext);
	$sesCod = 1;
	if ($rNext) {
		$row = mysqli_fetch_assoc($rNext);
		$sesCod = !empty($row['n']) ? (int)$row['n'] : 1;
		mysqli_free_result($rNext);
	}

	$ultExpr = ((int)$minUltAct > 0)
		? "DATE_SUB(NOW(), INTERVAL " . (int)$minUltAct . " MINUTE)"
		: "NOW()";

	$sql = "INSERT INTO `auditoria`.`sesion`
		(`Ses_Cod`, `Usu_Cod`, `Ses_Int`, `Emp_Cod`, `Suc_Cod`, `Ses_Ip`, `Ses_Ubi`, `Ses_Nav`, `Ses_Ult_Act`, `Ses_Min_Uso`, `Ses_Est`, `Ses_Token`)
		VALUES (" . (int)$sesCod . ", " . (int)$usuCod . ", NOW(), 1, 1, '127.0.0.1', 'Localhost (Servidor local)', 'Test (CLI)', {$ultExpr}, 0, '" . $estado . "', 'test-tiempo-real')";

	if (!@mysqli_query($con, $sql)) {
		throw new Exception('No se pudo insertar sesion de prueba: ' . mysqli_error($con));
	}
	return $sesCod;
}

function aud_act_limpiar_sesiones_prueba($con, $usuarios)
{
	$in = implode(',', array_map('intval', (array)$usuarios));
	if ($in !== '') {
		@mysqli_query($con, "DELETE FROM `auditoria`.`sesion` WHERE `Usu_Cod` IN ({$in})");
	}
}

/**
 * Verifica que el semaforo del monitor se recalcula EN TIEMPO REAL segun
 * la ultima actividad de cada usuario: en linea, ausente, inactiva,
 * expulsada y cerrada.
 */
function aud_unit_actividad_semaforo_tiempo_real()
{
	$con = aud_db_connect();
	if (!$con) {
		return;
	}
	aud_ses_asegurar_esquema($con);
	$usuarios = array(99101, 99102, 99103, 99104, 99105);
	aud_act_limpiar_sesiones_prueba($con, $usuarios);

	// En linea (actividad ahora), ausente (5 min), inactiva (20 min),
	// expulsada por admin (F) y cerrada (C)
	aud_act_insertar_sesion_prueba($con, 99101, 0, 'A');
	aud_act_insertar_sesion_prueba($con, 99102, 5, 'A');
	aud_act_insertar_sesion_prueba($con, 99103, 20, 'A');
	aud_act_insertar_sesion_prueba($con, 99104, 0, 'F');
	aud_act_insertar_sesion_prueba($con, 99105, 0, 'C');

	try {
		$res = aud_ses_listar_actividad(1, '', 0, 200, $con);
		aud_assert(is_array($res['items']) && is_array($res['kpis']), "listar actividad retorno items y kpis");
		aud_assert(is_array($res['top_usuarios']), "listar actividad retorno top_usuarios");

		$mapa = array();
		foreach ($res['items'] as $it) {
			if (isset($it['Usu_Cod'])) {
				$mapa[(int)$it['Usu_Cod']] = $it;
			}
		}

		aud_assert(isset($mapa[99101]) && $mapa[99101]['Semaforo'] === 'en_linea'
			&& strpos($mapa[99101]['BadgeTexto'], 'En línea') !== false,
			"Sesion con actividad ahora se muestra EN LINEA");
		aud_assert((int)$mapa[99101]['MinutosInactivo'] === 0, "Sesion activa con 0 minutos de inactividad");

		aud_assert(isset($mapa[99102]) && $mapa[99102]['Semaforo'] === 'ausente'
			&& strpos($mapa[99102]['BadgeTexto'], 'Ausente') !== false,
			"Sesion de 5 min sin actividad se muestra AUSENTE");

		aud_assert(isset($mapa[99103]) && $mapa[99103]['Semaforo'] === 'inactiva'
			&& strpos($mapa[99103]['BadgeTexto'], 'Timeout') !== false,
			"Sesion de 20 min sin actividad se muestra INACTIVA");

		aud_assert(isset($mapa[99104]) && $mapa[99104]['Semaforo'] === 'forzada'
			&& strpos($mapa[99104]['BadgeTexto'], 'Expulsado') !== false,
			"Sesion expulsada por el admin se muestra FORZADA");

		aud_assert(isset($mapa[99105]) && $mapa[99105]['Semaforo'] === 'cerrada'
			&& strpos($mapa[99105]['BadgeTexto'], 'Cerrada') !== false,
			"Sesion cerrada se muestra CERRADA");

		aud_assert($res['kpis']['en_linea'] >= 1, "KPI usuarios en linea >= 1");
		aud_assert($res['kpis']['ausentes'] >= 1, "KPI usuarios ausentes >= 1");
		aud_assert($res['kpis']['total_hoy'] >= 5, "KPI sesiones registradas hoy >= 5");

		aud_act_limpiar_sesiones_prueba($con, $usuarios);
		@mysqli_close($con);
	} catch (Exception $e) {
		aud_act_limpiar_sesiones_prueba($con, $usuarios);
		@mysqli_close($con);
		throw $e;
	}
}

/**
 * Verifica el ciclo real de tiempo real: un usuario inactivo pasa a
 * EN LINEA inmediatamente cuando llega su heartbeat (latido), porque
 * consultar_actividad refresca Ses_Ult_Act antes de renderizar el monitor.
 */
function aud_unit_actividad_heartbeat_refresca_tiempo_real()
{
	$con = aud_db_connect();
	if (!$con) {
		return;
	}
	aud_ses_asegurar_esquema($con);
	$usuarios = array(99110);
	aud_act_limpiar_sesiones_prueba($con, $usuarios);
	$sesCod = aud_act_insertar_sesion_prueba($con, 99110, 10, 'A');

	try {
		$resAntes = aud_ses_listar_actividad(1, '', 0, 200, $con);
		$semAntes = '';
		foreach ($resAntes['items'] as $it) {
			if ((int)$it['Usu_Cod'] === 99110 && (int)$it['Ses_Cod'] === $sesCod) {
				$semAntes = $it['Semaforo'];
			}
		}
		aud_assert($semAntes === 'ausente', "Sesion inicia como AUSENTE (10 min sin actividad)");

		$ping = aud_ses_heartbeat_ping($sesCod, 99110, $con);
		aud_assert(!empty($ping['success']) && empty($ping['forzar_logout']), "Heartbeat ping exitoso sin expulsion");

		$resDespues = aud_ses_listar_actividad(1, '', 0, 200, $con);
		$semDespues = '';
		foreach ($resDespues['items'] as $it) {
			if ((int)$it['Usu_Cod'] === 99110 && (int)$it['Ses_Cod'] === $sesCod) {
				$semDespues = $it['Semaforo'];
			}
		}
		aud_assert($semDespues === 'en_linea', "Tras el heartbeat la sesion pasa a EN LINEA en tiempo real");
		aud_assert($resDespues['kpis']['en_linea'] >= 1, "KPI en linea refleja el heartbeat recibido");

		aud_act_limpiar_sesiones_prueba($con, $usuarios);
		@mysqli_close($con);
	} catch (Exception $e) {
		aud_act_limpiar_sesiones_prueba($con, $usuarios);
		@mysqli_close($con);
		throw $e;
	}
}

/**
 * Verifica que los filtros de estado (en_linea / ausente) se apliquen
 * en el servidor, tal como los usan los desplegables del monitor.
 */
function aud_unit_actividad_filtros_estado_db()
{
	$con = aud_db_connect();
	if (!$con) {
		return;
	}
	aud_ses_asegurar_esquema($con);
	$usuarios = array(99111, 99112);
	aud_act_limpiar_sesiones_prueba($con, $usuarios);
	aud_act_insertar_sesion_prueba($con, 99111, 0, 'A');
	aud_act_insertar_sesion_prueba($con, 99112, 6, 'A');

	try {
		$online = aud_ses_listar_actividad(1, 'en_linea', 0, 200, $con);
		$usuariosOnline = array();
		foreach ($online['items'] as $it) {
			$usuariosOnline[(int)$it['Usu_Cod']] = true;
		}
		aud_assert(isset($usuariosOnline[99111]), "Filtro en_linea incluye la sesion activa");
		aud_assert(!isset($usuariosOnline[99112]), "Filtro en_linea excluye la sesion ausente");

		$aus = aud_ses_listar_actividad(1, 'ausente', 0, 200, $con);
		$usuariosAus = array();
		foreach ($aus['items'] as $it) {
			$usuariosAus[(int)$it['Usu_Cod']] = true;
		}
		aud_assert(isset($usuariosAus[99112]), "Filtro ausente incluye la sesion ausente");
		aud_assert(!isset($usuariosAus[99111]), "Filtro ausente excluye la sesion activa");

		aud_act_limpiar_sesiones_prueba($con, $usuarios);
		@mysqli_close($con);
	} catch (Exception $e) {
		aud_act_limpiar_sesiones_prueba($con, $usuarios);
		@mysqli_close($con);
		throw $e;
	}
}

/**
 * Verifica que el monitor en vivo este correctamente cableado: auto-refresco
 * periodico en el frontend, servicio consultar_actividad/ping/cerrar_forzada
 * y registro del proceso en el menu.
 */
function aud_unit_actividad_frontend_tiempo_real()
{
	$front = @file_get_contents(dirname(__FILE__) . '/../FRONT/aud_con_actividad_usuarios_1.0.php');
	aud_assert($front !== false && strpos($front, 'id="audAutoRefresh"') !== false,
		"FRONT expone el selector de auto-refresco (audAutoRefresh)");
	aud_assert(strpos($front, "action: 'consultar_actividad'") !== false,
		"FRONT consulta los datos en vivo con action consultar_actividad");
	aud_assert(strpos($front, 'setInterval(cargarDatosActividad') !== false,
		"FRONT programa el auto-refresco periodico con setInterval");
	aud_assert(strpos($front, 'btnRecargarActividad') !== false, "FRONT tiene el boton Actualizar");
	aud_assert(strpos($front, 'En Vivo') !== false && strpos($front, 'audLiveIndicator') !== false,
		"FRONT muestra el indicador luminoso En Vivo");
	aud_assert(strpos($front, 'cerrar_forzada') !== false, "FRONT cablea el cierre forzado del administrador");

	$serv = @file_get_contents(dirname(__FILE__) . '/../LOGICA/aud_log_actividad_sesion.php');
	aud_assert($serv !== false && strpos($serv, "action === 'consultar_actividad'") !== false,
		"Servicio expone la accion consultar_actividad (vista en vivo)");
	aud_assert(strpos($serv, "action === 'ping'") !== false, "Servicio expone la accion ping (heartbeat)");
	aud_assert(strpos($serv, "action === 'cerrar_forzada'") !== false, "Servicio expone la accion cerrar_forzada");

	$menu = @file_get_contents(dirname(__FILE__) . '/aud_register_menu.php');
	aud_assert($menu !== false && strpos($menu, 'aud_con_actividad_usuarios_1.0.php') !== false
		&& strpos($menu, 'Actividad de Usuarios') !== false,
		"El modulo Actividad de Usuarios esta registrado en el menu");
}

/**
 * Verifica las validaciones en tiempo real: deteccion de acceso multiple
 * (varias sesiones activas por usuario) y marcado de la propia sesion
 * del observador para impedir la auto-desconexion.
 */
function aud_unit_actividad_validaciones_tiempo_real()
{
	$con = aud_db_connect();
	if (!$con) {
		return;
	}
	aud_ses_asegurar_esquema($con);
	$usuarios = array(99120, 99121);
	aud_act_limpiar_sesiones_prueba($con, $usuarios);

	// Dos sesiones activas simultaneas para 99120 (acceso multiple)
	$sesA = aud_act_insertar_sesion_prueba($con, 99120, 0, 'A');
	$sesB = aud_act_insertar_sesion_prueba($con, 99120, 2, 'A');
	// Una sola sesion activa para 99121
	$sesC = aud_act_insertar_sesion_prueba($con, 99121, 0, 'A');

	try {
		$res = aud_ses_listar_actividad(1, '', 0, 200, $con, '', '', 99120);
		$mapa = array();
		foreach ($res['items'] as $it) {
			if (isset($it['Usu_Cod'])) {
				$mapa[(int)$it['Usu_Cod']] = $it;
			}
		}

		aud_assert(isset($mapa[99120]) && (int)$mapa[99120]['sesiones_activas'] === 2,
			"Usuario con 2 sesiones activas se marca con sesiones_activas=2");
		aud_assert(isset($mapa[99120]) && $mapa[99120]['es_mi_sesion'] === true,
			"La fila del observador se marca es_mi_sesion=true");
		aud_assert(isset($mapa[99121]) && (int)$mapa[99121]['sesiones_activas'] === 1,
			"Usuario con 1 sola sesion activa conserva sesiones_activas=1");
		aud_assert(isset($mapa[99121]) && $mapa[99121]['es_mi_sesion'] === false,
			"Otras sesiones no se marcan como propias (es_mi_sesion=false)");

		$multi = $res['validaciones']['usuarios_con_multiples_sesiones'];
		$nombresMulti = array();
		foreach ($multi as $m) {
			$nombresMulti[(int)$m['usu_cod']] = $m;
		}
		aud_assert(isset($nombresMulti[99120]) && (int)$nombresMulti[99120]['total'] === 2,
			"validaciones detecta acceso multiple del usuario 99120");
		aud_assert(!isset($nombresMulti[99121]), "validaciones no marca acceso multiple al usuario con 1 sesion");
		aud_assert((int)$res['validaciones']['total_sesiones_activas'] >= 3,
			"validaciones contabiliza las sesiones activas totales");

		// Verificacion cruzada: si el observador es 99121, su fila se marca propia
		// y la de 99120 deja de serlo aunque 99120 tenga mas sesiones activas.
		$res2 = aud_ses_listar_actividad(1, '', 0, 200, $con, '', '', 99121);
		foreach ($res2['items'] as $it) {
			if (isset($it['Usu_Cod']) && (int)$it['Usu_Cod'] === 99121) {
				aud_assert($it['es_mi_sesion'] === true,
					"El observador reconoce su fila por Usu_Cod");
			}
			if (isset($it['Usu_Cod']) && (int)$it['Usu_Cod'] === 99120) {
				aud_assert($it['es_mi_sesion'] === false,
					"La fila de otro usuario no se marca como propia");
			}
		}

		aud_act_limpiar_sesiones_prueba($con, $usuarios);
		@mysqli_close($con);
	} catch (Exception $e) {
		aud_act_limpiar_sesiones_prueba($con, $usuarios);
		@mysqli_close($con);
		throw $e;
	}
}

/**
 * Verifica las reglas que impiden cierres forzados invalidos: sesion
 * inexistente, la propia sesion del administrador, sesiones de otra
 * empresa y sesiones que ya no estan activas (cerradas/expulsadas).
 */
function aud_unit_actividad_validacion_cierre_forzado()
{
	$con = aud_db_connect();
	if (!$con) {
		return;
	}
	aud_ses_asegurar_esquema($con);
	$usuarios = array(99130, 99131, 99132);
	aud_act_limpiar_sesiones_prueba($con, $usuarios);

	// Codigo que seguramente no existe
	$rMax = @mysqli_query($con, "SELECT (IFNULL(MAX(`Ses_Cod`), 0) + 100000) AS `n` FROM `auditoria`.`sesion`");
	$noExiste = 999999999;
	if ($rMax) {
		$row = mysqli_fetch_assoc($rMax);
		$noExiste = (int)$row['n'];
		mysqli_free_result($rMax);
	}

	$sesOK = aud_act_insertar_sesion_prueba($con, 99130, 0, 'A');
	$sesCerrada = aud_act_insertar_sesion_prueba($con, 99131, 0, 'C');

	try {
		$v0 = aud_ses_validar_cierre_forzado(0, 1, 0, $con);
		aud_assert($v0['success'] === false, "Cierre forzado con codigo 0 es rechazado");

		$vNo = aud_ses_validar_cierre_forzado($noExiste, 1, 0, $con);
		aud_assert($vNo['success'] === false, "Cierre forzado de sesion inexistente es rechazado");

		$vPropia = aud_ses_validar_cierre_forzado($sesOK, 1, $sesOK, $con);
		aud_assert($vPropia['success'] === false && strpos($vPropia['error'], 'propia') !== false,
			"El administrador no puede cerrar su propia sesion");

		$vOtraEmp = aud_ses_validar_cierre_forzado($sesOK, 2, 0, $con);
		aud_assert($vOtraEmp['success'] === false && strpos($vOtraEmp['error'], 'otra empresa') !== false,
			"No se pueden cerrar sesiones de otra empresa");

		$vCerrada = aud_ses_validar_cierre_forzado($sesCerrada, 1, 0, $con);
		aud_assert($vCerrada['success'] === false && strpos($vCerrada['error'], 'activa') !== false,
			"Una sesion ya cerrada no puede volver a cerrarse");

		$vOK = aud_ses_validar_cierre_forzado($sesOK, 1, 0, $con);
		aud_assert($vOK['success'] === true, "Una sesion activa de la misma empresa puede cerrarse");

		aud_act_limpiar_sesiones_prueba($con, $usuarios);
		@mysqli_close($con);
	} catch (Exception $e) {
		aud_act_limpiar_sesiones_prueba($con, $usuarios);
		@mysqli_close($con);
		throw $e;
	}
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
		'aud_unit_actividad_semaforo_tiempo_real',
		'aud_unit_actividad_heartbeat_refresca_tiempo_real',
		'aud_unit_actividad_filtros_estado_db',
		'aud_unit_actividad_validaciones_tiempo_real',
		'aud_unit_actividad_validacion_cierre_forzado',
		'aud_unit_actividad_frontend_tiempo_real',
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

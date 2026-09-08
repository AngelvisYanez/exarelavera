<?php
/**
 * Logica para el monitor de actividad de usuarios, tracking de sesiones,
 * tiempos de uso, deteccion de IP, ubicacion, navegadores y control de inactividad.
 *
 * @package auditoria.LOGICA
 */

if (session_id() === '' && !headers_sent()) {
	@session_start();
}

require_once dirname(__FILE__) . '/../../DATA/MysqlConexion.php';
require_once dirname(__FILE__) . '/../../DATA/MysqlDatos.php';
require_once dirname(__FILE__) . '/aud_sql_actividad_sesion.php';
require_once dirname(__FILE__) . '/aud_log_config_monitoreo.php';

/**
 * Conexion a la base de datos para el modulo de actividad.
 */
if (!class_exists('Class_Log_Conexion_Actividad')) {
	class Class_Log_Conexion_Actividad extends MysqlConexion
	{
		function __construct($bd = null)
		{
			if (empty($bd)) {
				$bd = !empty($_SESSION['Ses_Dat_Dis']) ? $_SESSION['Ses_Dat_Dis'] : null;
			}
			if (empty($bd) && !empty($GLOBALS['Ses_Dat_Dis'])) {
				$bd = $GLOBALS['Ses_Dat_Dis'];
			}
			if (empty($bd) && class_exists('Env')) {
				$bd = \Env::get('DB_DATABASE', 'exa');
			}
			if (empty($bd) || $bd === 'exa_master') {
				$bd = 'exa';
			}
			parent::__construct(preg_replace('/[^a-zA-Z0-9_]/', '', $bd));
		}
	}
}

/**
 * Capa de datos para actividad de sesiones.
 */
if (!class_exists('Class_Log_Datos_Actividad')) {
	class Class_Log_Datos_Actividad extends MysqlDatos
	{
		public function sentencias($tipo, $Par_Sql = array())
		{
			return sentencias_actividad_sesion($tipo, $Par_Sql);
		}
	}
}

/**
 * Asegura de forma idempotente las columnas e indices necesarios en auditoria.sesion.
 *
 * @param mysqli $con
 * @return void
 */
if (!function_exists('aud_ses_asegurar_esquema')) {
	function aud_ses_asegurar_esquema($con)
	{
		static $esquemaListo = false;
		if ($esquemaListo || !$con) {
			return;
		}

		$columnasActuales = array();
		$r = @mysqli_query($con, sentencias_actividad_sesion(1, array()));
		if ($r) {
			while ($f = mysqli_fetch_assoc($r)) {
				$col = isset($f['COLUMN_NAME']) ? strtolower($f['COLUMN_NAME']) : '';
				if ($col !== '') {
					$columnasActuales[$col] = true;
				}
			}
			mysqli_free_result($r);
		}

		$alters = array();
		if (empty($columnasActuales['emp_cod'])) {
			$alters[] = "ADD COLUMN `Emp_Cod` INT(11) DEFAULT NULL AFTER `Ses_Out`";
		}
		if (empty($columnasActuales['suc_cod'])) {
			$alters[] = "ADD COLUMN `Suc_Cod` INT(11) DEFAULT NULL AFTER `Emp_Cod`";
		}
		if (empty($columnasActuales['ses_ip'])) {
			$alters[] = "ADD COLUMN `Ses_Ip` VARCHAR(45) DEFAULT NULL AFTER `Suc_Cod`";
		}
		if (empty($columnasActuales['ses_ubi'])) {
			$alters[] = "ADD COLUMN `Ses_Ubi` VARCHAR(120) DEFAULT NULL AFTER `Ses_Ip`";
		}
		if (empty($columnasActuales['ses_nav'])) {
			$alters[] = "ADD COLUMN `Ses_Nav` VARCHAR(255) DEFAULT NULL AFTER `Ses_Ubi`";
		}
		if (empty($columnasActuales['ses_ult_act'])) {
			$alters[] = "ADD COLUMN `Ses_Ult_Act` DATETIME DEFAULT NULL AFTER `Ses_Nav`";
		}
		if (empty($columnasActuales['ses_min_uso'])) {
			$alters[] = "ADD COLUMN `Ses_Min_Uso` INT(11) NOT NULL DEFAULT 0 AFTER `Ses_Ult_Act`";
		}
		if (empty($columnasActuales['ses_est'])) {
			$alters[] = "ADD COLUMN `Ses_Est` CHAR(1) NOT NULL DEFAULT 'A' AFTER `Ses_Min_Uso`";
		}
		if (empty($columnasActuales['ses_token'])) {
			$alters[] = "ADD COLUMN `Ses_Token` VARCHAR(64) DEFAULT NULL AFTER `Ses_Est`";
		}

		if (!empty($alters)) {
			$sqlAlter = "ALTER TABLE `auditoria`.`sesion` " . implode(', ', $alters);
			@mysqli_query($con, $sqlAlter);
		}

		// Asegurar indices clave de forma idempotente
		$indicesActuales = array();
		$rIdx = @mysqli_query($con, "SHOW INDEX FROM `auditoria`.`sesion`");
		if ($rIdx) {
			while ($fi = mysqli_fetch_assoc($rIdx)) {
				$k = isset($fi['Key_name']) ? strtolower($fi['Key_name']) : '';
				if ($k !== '') {
					$indicesActuales[$k] = true;
				}
			}
			mysqli_free_result($rIdx);
		}
		if (empty($indicesActuales['idx_ses_est_act'])) {
			@mysqli_query($con, "ALTER TABLE `auditoria`.`sesion` ADD INDEX `idx_ses_est_act` (`Ses_Est`, `Ses_Ult_Act`)");
		}
		if (empty($indicesActuales['idx_ses_emp'])) {
			@mysqli_query($con, "ALTER TABLE `auditoria`.`sesion` ADD INDEX `idx_ses_emp` (`Emp_Cod`)");
		}

		$esquemaListo = true;
	}
}

/**
 * Indica si el cierre de sesion por inactividad esta habilitado.
 *
 * El cierre automatico por 15 minutos sin actividad afecta produccion: al
 * dispararse destruye la sesion PHP del usuario y lo expulsa a la pantalla de
 * login. Por defecto esta DESACTIVADO (AUDIT_IDLE_LOGOUT=false). Para activarlo
 * configure `AUDIT_IDLE_LOGOUT=true` en el .env.
 *
 * @return bool
 */
if (!function_exists('aud_ses_idle_logout_activo')) {
	function aud_ses_idle_logout_activo()
	{
		if (class_exists('Env')) {
			return (bool)\Env::get('AUDIT_IDLE_LOGOUT', false);
		}
		return false;
	}
}

/**
 * Detecta la IP real del cliente.
 *
 * @return string
 */
if (!function_exists('aud_ses_detectar_ip')) {
	function aud_ses_detectar_ip()
	{
		$headers = array(
			'HTTP_CF_CONNECTING_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_CLIENT_IP',
			'REMOTE_ADDR'
		);
		foreach ($headers as $h) {
			if (!empty($_SERVER[$h])) {
				$ip = trim($_SERVER[$h]);
				if (strpos($ip, ',') !== false) {
					$parts = explode(',', $ip);
					$ip = trim($parts[0]);
				}
				if (filter_var($ip, FILTER_VALIDATE_IP)) {
					return $ip;
				}
			}
		}
		return !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
	}
}

/**
 * Parsea el User-Agent para devolver un nombre legible de Navegador y Sistema Operativo.
 *
 * @param string|null $ua
 * @return string
 */
if (!function_exists('aud_ses_detectar_navegador')) {
	function aud_ses_detectar_navegador($ua = null)
	{
		if ($ua === null) {
			$ua = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		}
		$ua = (string)$ua;
		if ($ua === '') {
			return 'Navegador desconocido';
		}

		// Deteccion de Sistema Operativo
		$os = 'SO desconocido';
		if (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false) {
			$os = 'iOS';
		} elseif (stripos($ua, 'Android') !== false) {
			$os = 'Android';
		} elseif (stripos($ua, 'Windows NT 10.0') !== false) {
			$os = 'Windows 10/11';
		} elseif (stripos($ua, 'Windows NT 6.3') !== false) {
			$os = 'Windows 8.1';
		} elseif (stripos($ua, 'Windows NT 6.1') !== false) {
			$os = 'Windows 7';
		} elseif (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS X') !== false) {
			$os = 'macOS';
		} elseif (stripos($ua, 'Linux') !== false) {
			$os = 'Linux';
		}

		// Deteccion de Navegador
		$browser = 'Navegador Web';
		if (stripos($ua, 'Edg/') !== false) {
			preg_match('/Edg\/([0-9\.]+)/i', $ua, $m);
			$browser = 'Edge ' . (!empty($m[1]) ? explode('.', $m[1])[0] : '');
		} elseif (stripos($ua, 'Chrome/') !== false && stripos($ua, 'Chromium/') === false) {
			preg_match('/Chrome\/([0-9\.]+)/i', $ua, $m);
			$browser = 'Chrome ' . (!empty($m[1]) ? explode('.', $m[1])[0] : '');
		} elseif (stripos($ua, 'Firefox/') !== false) {
			preg_match('/Firefox\/([0-9\.]+)/i', $ua, $m);
			$browser = 'Firefox ' . (!empty($m[1]) ? explode('.', $m[1])[0] : '');
		} elseif (stripos($ua, 'Safari/') !== false && stripos($ua, 'Chrome/') === false) {
			preg_match('/Version\/([0-9\.]+)/i', $ua, $m);
			$browser = 'Safari ' . (!empty($m[1]) ? explode('.', $m[1])[0] : '');
		} elseif (stripos($ua, 'Opera/') !== false || stripos($ua, 'OPR/') !== false) {
			$browser = 'Opera';
		}

		return trim($browser . ' (' . $os . ')');
	}
}

/**
 * Detecta la ubicacion aproximada a partir de la direccion IP.
 *
 * La resolucion por IP publica usa el servicio externo ip-api.com. Por defecto
 * esta DESACTIVADA (AUDIT_GEOIP=false): no se envian IPs a terceros ni se
 * depende de internet al iniciar sesion. Para activarla configure
 * `AUDIT_GEOIP=true` en el .env.
 *
 * @param string $ip
 * @return string
 */
if (!function_exists('aud_ses_detectar_ubicacion')) {
	function aud_ses_geoip_activo()
	{
		if (class_exists('Env')) {
			return (bool)\Env::get('AUDIT_GEOIP', false);
		}
		return false;
	}

	function aud_ses_detectar_ubicacion($ip)
	{
		$ip = trim((string)$ip);
		if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1') {
			return 'Localhost (Servidor local)';
		}

		// Redes privadas RFC 1918
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
			return 'Red Local / LAN (' . $ip . ')';
		}

		// Geo-ubicacion por IP publica con servicio externo: desactivable.
		// Evita dependencias de terceros y envio de IPs del cliente al exterior.
		if (aud_ses_geoip_activo() && function_exists('curl_init')) {
			try {
				$ch = curl_init('http://ip-api.com/json/' . urlencode($ip) . '?fields=city,country,status');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_TIMEOUT_MS, 400);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 300);
				$res = curl_exec($ch);
				$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);

				if ($code === 200 && $res) {
					$json = json_decode($res, true);
					if (!empty($json['status']) && $json['status'] === 'success') {
						$city = !empty($json['city']) ? $json['city'] : '';
						$country = !empty($json['country']) ? $json['country'] : '';
						if ($city !== '' && $country !== '') {
							return $city . ', ' . $country;
						}
						if ($country !== '') {
							return $country;
						}
					}
				}
			} catch (Throwable $e) {
				// Silencioso
			}
		}

		return 'Internet (' . $ip . ')';
	}
}

/**
 * Registra el inicio de una sesion completa con IP, Ubicacion, Navegador y Token.
 *
 * @param int $usuCod
 * @param int $empCod
 * @param int $sucCod
 * @param mysqli|null $con
 * @return int Ses_Cod generado
 */
if (!function_exists('aud_ses_registrar_inicio')) {
	function aud_ses_registrar_inicio($usuCod, $empCod = 0, $sucCod = 0, $con = null)
	{
		$closeOnExit = false;
		if (!$con) {
			$dbConn = new Class_Log_Conexion_Actividad();
			$con = $dbConn->conexion;
			$closeOnExit = true;
		}
		if (!$con) {
			return 0;
		}

		aud_ses_asegurar_esquema($con);

		// Obtener siguiente Ses_Cod
		$sqlNext = "SELECT (IFNULL(MAX(`Ses_Cod`), 0) + 1) AS `next_cod` FROM `auditoria`.`sesion`";
		$rNext = @mysqli_query($con, $sqlNext);
		$nextCod = 1;
		if ($rNext) {
			$rowNext = mysqli_fetch_assoc($rNext);
			$nextCod = !empty($rowNext['next_cod']) ? (int)$rowNext['next_cod'] : 1;
			mysqli_free_result($rNext);
		}

		$ahora = date('Y-m-d H:i:s');
		$ip = aud_ses_detectar_ip();
		$ubi = aud_ses_detectar_ubicacion($ip);
		$nav = aud_ses_detectar_navegador();
		$token = session_id() !== '' ? session_id() : md5(uniqid((string)$usuCod, true));

		$sqlIns = sentencias_actividad_sesion(3, array(
			$nextCod,
			(int)$usuCod,
			$ahora,
			(int)$empCod,
			(int)$sucCod,
			$ip,
			$ubi,
			$nav,
			$token
		));

		@mysqli_query($con, $sqlIns);

		if ($closeOnExit) {
			@mysqli_close($con);
		}

		return $nextCod;
	}
}

/**
 * Procesa el Heartbeat ping enviado por el navegador del usuario.
 * Verifica si la sesion fue forzada a cerrar por el Administrador.
 *
 * @param int $sesCod
 * @param int $usuCod
 * @param mysqli|null $con
 * @return array
 */
if (!function_exists('aud_ses_heartbeat_ping')) {
	function aud_ses_heartbeat_ping($sesCod, $usuCod, $con = null)
	{
		$closeOnExit = false;
		if (!$con) {
			$dbConn = new Class_Log_Conexion_Actividad();
			$con = $dbConn->conexion;
			$closeOnExit = true;
		}
		if (!$con) {
			return array('success' => false, 'error' => 'Sin conexion a base de datos');
		}

		aud_ses_asegurar_esquema($con);

		$sesCod = (int)$sesCod;
		$usuCod = (int)$usuCod;

		// Verificar estado actual
		$sqlCheck = sentencias_actividad_sesion(5, array($sesCod, $usuCod));
		$r = @mysqli_query($con, $sqlCheck);
		$sesion = $r ? mysqli_fetch_assoc($r) : null;
		if ($r) {
			mysqli_free_result($r);
		}

		if (!$sesion) {
			if ($closeOnExit) @mysqli_close($con);
			return array('success' => false, 'forzar_logout' => true, 'motivo' => 'no_existe', 'mensaje' => 'Sesion no encontrada.');
		}

		// Si fue forzada por el admin
		if ($sesion['Ses_Est'] === 'F') {
			if ($closeOnExit) @mysqli_close($con);
			return array('success' => false, 'forzar_logout' => true, 'motivo' => 'forzada', 'mensaje' => 'Su sesion ha sido finalizada por el Administrador de Sistemas.');
		}

		// Si ya fue cerrada
		if ($sesion['Ses_Est'] === 'C') {
			if ($closeOnExit) @mysqli_close($con);
			return array('success' => false, 'forzar_logout' => true, 'motivo' => 'cerrada', 'mensaje' => 'La sesion ha sido cerrada.');
		}

		// Actualizar heartbeat y minutos de uso
		$sqlUpdate = sentencias_actividad_sesion(4, array($sesCod, $usuCod));
		@mysqli_query($con, $sqlUpdate);

		$minUso = isset($sesion['Ses_Min_Uso']) ? (int)$sesion['Ses_Min_Uso'] + 1 : 1;

		if ($closeOnExit) @mysqli_close($con);

		return array(
			'success' => true,
			'forzar_logout' => false,
			'min_uso' => $minUso,
			'servidor_tiempo' => date('Y-m-d H:i:s')
		);
	}
}

/**
 * Cierra la sesion por inactividad prolongada (15 minutos).
 *
 * @param int $sesCod
 * @param int $usuCod
 * @param mysqli|null $con
 * @return bool
 */
if (!function_exists('aud_ses_cerrar_por_inactividad')) {
	function aud_ses_cerrar_por_inactividad($sesCod, $usuCod, $con = null)
	{
		$closeOnExit = false;
		if (!$con) {
			$dbConn = new Class_Log_Conexion_Actividad();
			$con = $dbConn->conexion;
			$closeOnExit = true;
		}
		if (!$con) {
			return false;
		}

		aud_ses_asegurar_esquema($con);

		$sql = sentencias_actividad_sesion(7, array((int)$sesCod, (int)$usuCod));
		$ok = @mysqli_query($con, $sql);

		if ($closeOnExit) @mysqli_close($con);
		return (bool)$ok;
	}
}

/**
 * Cierre forzado de una sesion (Ejecutado por Administrador).
 *
 * @param int $sesCod
 * @param mysqli|null $con
 * @return bool
 */
if (!function_exists('aud_ses_cerrar_forzada')) {
	function aud_ses_cerrar_forzada($sesCod, $con = null)
	{
		$closeOnExit = false;
		if (!$con) {
			$dbConn = new Class_Log_Conexion_Actividad();
			$con = $dbConn->conexion;
			$closeOnExit = true;
		}
		if (!$con) {
			return false;
		}

		aud_ses_asegurar_esquema($con);

		$sql = sentencias_actividad_sesion(8, array((int)$sesCod));
		$ok = @mysqli_query($con, $sql);

		if ($closeOnExit) @mysqli_close($con);
		return (bool)$ok;
	}
}

/**
 * Lista la actividad de usuarios y calcula el estado semaforizado en tiempo real.
 *
 * @param int $empCod
 * @param string $estado
 * @param int $rolCod
 * @param int $limite
 * @param mysqli|null $con
 * @param string $desde
 * @param string $hasta
 * @return array
 */
if (!function_exists('aud_ses_listar_actividad')) {
	function aud_ses_listar_actividad($empCod = 0, $estado = '', $rolCod = 0, $limite = 100, $con = null, $desde = '', $hasta = '')
	{
		$closeOnExit = false;
		if (!$con) {
			$dbConn = new Class_Log_Conexion_Actividad();
			$con = $dbConn->conexion;
			$closeOnExit = true;
		}
		if (!$con) {
			return array('items' => array(), 'kpis' => array());
		}

		aud_ses_asegurar_esquema($con);

		$sqlList = sentencias_actividad_sesion(9, array($empCod, $estado, $rolCod, $limite, $desde, $hasta));
		$rList = @mysqli_query($con, $sqlList);
		$items = array();

		if ($rList) {
			while ($row = mysqli_fetch_assoc($rList)) {
				$minDesdeAct = isset($row['Minutos_Desde_Actividad']) ? (int)$row['Minutos_Desde_Actividad'] : 999;
				$estRaw = isset($row['Ses_Est']) ? $row['Ses_Est'] : 'A';

				// Calculo dinamico del semaforo en tiempo real
				$badgeClase = 'badge-success';
				$badgeTexto = 'En línea';
				$semaforo = 'en_linea';

				if ($estRaw === 'F') {
					$badgeClase = 'badge-danger';
					$badgeTexto = 'Expulsado por admin';
					$semaforo = 'forzada';
				} elseif ($estRaw === 'C') {
					$badgeClase = 'badge-secondary';
					$badgeTexto = 'Cerrada';
					$semaforo = 'cerrada';
				} elseif ($estRaw === 'I' || ($estRaw === 'A' && $minDesdeAct >= 15)) {
					$badgeClase = 'badge-warning';
					$badgeTexto = 'Inactiva (Timeout)';
					$semaforo = 'inactiva';
				} elseif ($estRaw === 'A' && $minDesdeAct > 3) {
					$badgeClase = 'badge-info';
					$badgeTexto = 'Ausente';
					$semaforo = 'ausente';
				}

				// Formateo de tiempos
				$minUso = isset($row['Ses_Min_Uso']) ? (int)$row['Ses_Min_Uso'] : 0;
				$horas = floor($minUso / 60);
				$mins = $minUso % 60;
				$tiempoFormateado = ($horas > 0 ? "{$horas}h " : "") . "{$mins}m";

				$nombreCompleto = trim((isset($row['Prs_Nom']) ? $row['Prs_Nom'] : '') . ' ' . (isset($row['Prs_Ape']) ? $row['Prs_Ape'] : ''));
				if ($nombreCompleto === '') {
					$nombreCompleto = !empty($row['Usu_Nom']) ? $row['Usu_Nom'] : 'Usuario #' . $row['Usu_Cod'];
				}

				$row['Semaforo'] = $semaforo;
				$row['BadgeClase'] = $badgeClase;
				$row['BadgeTexto'] = $badgeTexto;
				$row['NombreCompleto'] = $nombreCompleto;
				$row['TiempoFormateado'] = $tiempoFormateado;

				$items[] = $row;
			}
			mysqli_free_result($rList);
		}

		// Consultar KPIs
		$sqlKpis = sentencias_actividad_sesion(10, array($empCod));
		$rKpi = @mysqli_query($con, $sqlKpis);
		$kpis = $rKpi ? mysqli_fetch_assoc($rKpi) : array();
		if ($rKpi) {
			mysqli_free_result($rKpi);
		}

		// Consultar Top Usuarios
		$sqlTop = sentencias_actividad_sesion(11, array($empCod));
		$rTop = @mysqli_query($con, $sqlTop);
		$topUsuarios = array();
		if ($rTop) {
			while ($rt = mysqli_fetch_assoc($rTop)) {
				$minUso = isset($rt['Total_Minutos_Uso']) ? (int)$rt['Total_Minutos_Uso'] : 0;
				$horas = floor($minUso / 60);
				$mins = $minUso % 60;
				$rt['TiempoFormateado'] = ($horas > 0 ? "{$horas}h " : "") . "{$mins}m";
				$topUsuarios[] = $rt;
			}
			mysqli_free_result($rTop);
		}

		if ($closeOnExit) @mysqli_close($con);

		return array(
			'items' => $items,
			'kpis' => $kpis,
			'top_usuarios' => $topUsuarios
		);
	}
}

// -------------------------------------------------------------
// Endpoint AJAX si es invocado directamente mediante POST/GET
// -------------------------------------------------------------
if (basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '') === 'aud_log_actividad_sesion.php') {
	header('Content-Type: application/json; charset=utf-8');

	$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

	if ($action === 'ping') {
		$sesCod = isset($_SESSION['Ses_Ses_Cod']) ? (int)$_SESSION['Ses_Ses_Cod'] : (isset($_REQUEST['ses_cod']) ? (int)$_REQUEST['ses_cod'] : 0);
		$usuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : (isset($_REQUEST['usu_cod']) ? (int)$_REQUEST['usu_cod'] : 0);

		if ($sesCod <= 0 || $usuCod <= 0) {
			echo json_encode(array('success' => false, 'error' => 'Sesion no activa'));
			exit;
		}

		$res = aud_ses_heartbeat_ping($sesCod, $usuCod);
		echo json_encode($res);
		exit;
	}

	if ($action === 'inactividad_timeout') {
		// Cierre por inactividad desactivado en produccion (AUDIT_IDLE_LOGOUT).
		// NO se destruye la sesion PHP: evita expulsiones involuntarias.
		if (!aud_ses_idle_logout_activo()) {
			echo json_encode(array('success' => false, 'error' => 'Cierre por inactividad desactivado'));
			exit;
		}

		$sesCod = isset($_SESSION['Ses_Ses_Cod']) ? (int)$_SESSION['Ses_Ses_Cod'] : (isset($_REQUEST['ses_cod']) ? (int)$_REQUEST['ses_cod'] : 0);
		$usuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : (isset($_REQUEST['usu_cod']) ? (int)$_REQUEST['usu_cod'] : 0);

		if ($sesCod > 0 && $usuCod > 0) {
			aud_ses_cerrar_por_inactividad($sesCod, $usuCod);
		}

		@session_unset();
		@session_destroy();

		echo json_encode(array('success' => true, 'redirect' => '../../index.php?motivo=inactividad'));
		exit;
	}

	if ($action === 'cerrar_forzada') {
		$usuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;
		$esAdmin = aud_cfg_es_admin_sistemas($usuCod);
		if (!$esAdmin) {
			echo json_encode(array('success' => false, 'error' => 'Permiso denegado. Solo el Administrador de Sistemas puede forzar cierres de sesion.'));
			exit;
		}

		$sesTarget = isset($_POST['ses_cod']) ? (int)$_POST['ses_cod'] : 0;
		if ($sesTarget <= 0) {
			echo json_encode(array('success' => false, 'error' => 'Codigo de sesion invalido'));
			exit;
		}

		$ok = aud_ses_cerrar_forzada($sesTarget);
		echo json_encode(array('success' => $ok));
		exit;
	}

	if ($action === 'listar') {
		$empCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
		$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
		$rolCod = isset($_GET['rol']) ? (int)$_GET['rol'] : 0;
		$desde  = isset($_GET['desde']) ? trim($_GET['desde']) : '';
		$hasta  = isset($_GET['hasta']) ? trim($_GET['hasta']) : '';

		$datos = aud_ses_listar_actividad($empCod, $estado, $rolCod, 100, null, $desde, $hasta);
		echo json_encode(array('success' => true, 'data' => $datos));
		exit;
	}

	echo json_encode(array('success' => false, 'error' => 'Accion no reconocida'));
	exit;
}

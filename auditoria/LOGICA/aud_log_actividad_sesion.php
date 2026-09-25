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
@date_default_timezone_set('America/Guayaquil');

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
				$bd = \Env::get('DB_DATABASE', '');
			}
			// Las consultas de actividad usan auditoria.* calificado; si no hay
			// BD distribuida en sesion (CLI/tests) conectar a auditoria evita
			// fallar con DB_DATABASE=exa_master remapado a un schema inexistente.
			if (empty($bd) || $bd === 'exa_master' || $bd === 'exa') {
				$bd = 'auditoria';
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

		// La tabla sesion puede no existir (importaciones previas del dump
		// antiguo o instalaciones nuevas). Crearla de forma idempotente con el
		// esquema completo que el resto del modulo espera; las sentencias
		// CREATE/ALTER siguientes son no-op cuando ya existe.
		@mysqli_query($con, "CREATE TABLE IF NOT EXISTS `auditoria`.`sesion` (
			`Ses_Cod` int(11) NOT NULL,
			`Usu_Cod` int(11) NOT NULL,
			`Ses_Int` datetime DEFAULT NULL,
			`Ses_Out` datetime DEFAULT NULL,
			`Emp_Cod` int(11) DEFAULT NULL,
			`Suc_Cod` int(11) DEFAULT NULL,
			`Ses_Ip` varchar(45) DEFAULT NULL,
			`Ses_Ubi` varchar(120) DEFAULT NULL,
			`Ses_Nav` varchar(255) DEFAULT NULL,
			`Ses_Ult_Act` datetime DEFAULT NULL,
			`Ses_Min_Uso` int(11) NOT NULL DEFAULT 0,
			`Ses_Est` char(1) NOT NULL DEFAULT 'A',
			`Ses_Token` varchar(64) DEFAULT NULL,
			`Ses_Dev_Cod` varchar(64) DEFAULT NULL,
			`Ses_Mac` varchar(17) DEFAULT NULL,
			`Ses_OAuth_Tok` varchar(64) DEFAULT NULL,
			`Ses_Fingerprint` varchar(40) DEFAULT NULL,
			PRIMARY KEY (`Ses_Cod`),
			KEY `Usu_Cod` (`Usu_Cod`),
			KEY `idx_ses_est_act` (`Ses_Est`, `Ses_Ult_Act`),
			KEY `idx_ses_emp` (`Emp_Cod`),
			KEY `idx_ses_usu` (`Usu_Cod`),
			KEY `idx_ses_int` (`Ses_Int`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");

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
		if (empty($columnasActuales['ses_dev_cod'])) {
			$alters[] = "ADD COLUMN `Ses_Dev_Cod` VARCHAR(64) DEFAULT NULL AFTER `Ses_Token`";
		}
		if (empty($columnasActuales['ses_mac'])) {
			$alters[] = "ADD COLUMN `Ses_Mac` VARCHAR(17) DEFAULT NULL AFTER `Ses_Dev_Cod`";
		}
		if (empty($columnasActuales['ses_oauth_tok'])) {
			$alters[] = "ADD COLUMN `Ses_OAuth_Tok` VARCHAR(64) DEFAULT NULL AFTER `Ses_Mac`";
		}
		if (empty($columnasActuales['ses_fingerprint'])) {
			$alters[] = "ADD COLUMN `Ses_Fingerprint` VARCHAR(40) DEFAULT NULL AFTER `Ses_OAuth_Tok`";
		}

		if (!empty($alters)) {
			$sqlAlter = "ALTER TABLE `auditoria`.`sesion` " . implode(', ', $alters);
			@mysqli_query($con, $sqlAlter);
		}

		// Esquema real tras el ALTER (puede fallar si el usuario de la BD no
		// tiene privilegios DDL, p.ej. user_relavera en produccion). Exponer el
		// flag global para que sentencias_actividad_sesion() omita las columnas
		// OAuth (Ses_Dev_Cod/Ses_Mac/Ses_OAuth_Tok) cuando no existan, evitando
		// el ERROR 1054 que dejaba vacio el monitor de actividad de usuarios.
		$tieneOauth = false;
		$rCols = @mysqli_query($con, "SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = 'auditoria' AND `TABLE_NAME` = 'sesion' AND `COLUMN_NAME` IN ('Ses_Dev_Cod', 'Ses_Mac', 'Ses_OAuth_Tok')");
		if ($rCols) {
			$nOauth = mysqli_num_rows($rCols);
			mysqli_free_result($rCols);
			$tieneOauth = ($nOauth === 3);
		}
		$GLOBALS['AUD_SES_OAUTH_SCHEMA'] = $tieneOauth;

		// Flag independiente para Ses_Fingerprint (huella digital de respaldo
		// cuando la MAC no es detectable). Se separa del flag OAuth anterior
		// para no afectar el comportamiento ya validado de Ses_Mac/Ses_Dev_Cod
		// en bases donde el ALTER de esta columna nueva aun no se ha aplicado.
		$tieneFp = false;
		$rColsFp = @mysqli_query($con, "SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = 'auditoria' AND `TABLE_NAME` = 'sesion' AND `COLUMN_NAME` = 'Ses_Fingerprint'");
		if ($rColsFp) {
			$tieneFp = (mysqli_num_rows($rColsFp) === 1);
			mysqli_free_result($rColsFp);
		}
		$GLOBALS['AUD_SES_FP_SCHEMA'] = $tieneFp;

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
		if (empty($indicesActuales['idx_ses_usu'])) {
			@mysqli_query($con, "ALTER TABLE `auditoria`.`sesion` ADD INDEX `idx_ses_usu` (`Usu_Cod`)");
		}
		if (empty($indicesActuales['idx_ses_int'])) {
			@mysqli_query($con, "ALTER TABLE `auditoria`.`sesion` ADD INDEX `idx_ses_int` (`Ses_Int`)");
		}

		$esquemaListo = true;
	}
}

/**
 * Esquema de configuracion de inactividad por empresa.
 *
 * @param mysqli|null $con
 */
if (!function_exists('aud_idle_ensure_schema')) {
	function aud_idle_ensure_schema($con = null)
	{
		$closeOnExit = false;
		if (!$con) {
			$dbConn = new Class_Log_Conexion_Actividad();
			$con = $dbConn->conexion;
			$closeOnExit = true;
		}
		if (!$con) {
			return;
		}
		@mysqli_query($con, "CREATE TABLE IF NOT EXISTS `auditoria`.`cfg_inactividad` (
			`Emp_Cod` INT(11) NOT NULL,
			`Cfg_Activo` CHAR(1) NOT NULL DEFAULT 'A',
			`Cfg_Minutos` INT(11) NOT NULL DEFAULT 15,
			`Cfg_Advertencia_Seg` INT(11) NOT NULL DEFAULT 60,
			`Cfg_Titulo` VARCHAR(160) NOT NULL DEFAULT 'Advertencia de Inactividad',
			`Cfg_Texto` VARCHAR(600) NOT NULL DEFAULT 'Tu sesion ha permanecido inactiva. Por seguridad, se cerrara automaticamente en {segundos} segundos si no se detecta actividad.',
			`Cfg_Plantilla` VARCHAR(32) NOT NULL DEFAULT 'clasico',
			`Usu_Cod` INT(11) DEFAULT NULL,
			`Cfg_Fec` DATETIME DEFAULT NULL,
			PRIMARY KEY (`Emp_Cod`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
		/* Migracion suave si la tabla ya existia sin plantilla */
		$chk = @mysqli_query($con, "SHOW COLUMNS FROM `auditoria`.`cfg_inactividad` LIKE 'Cfg_Plantilla'");
		$hasCol = ($chk && mysqli_num_rows($chk) > 0);
		if ($chk) {
			mysqli_free_result($chk);
		}
		if (!$hasCol) {
			@mysqli_query($con, "ALTER TABLE `auditoria`.`cfg_inactividad`
				ADD COLUMN `Cfg_Plantilla` VARCHAR(32) NOT NULL DEFAULT 'clasico' AFTER `Cfg_Texto`");
		}
		if ($closeOnExit) {
			@mysqli_close($con);
		}
	}
}

/**
 * Catalogo de plantillas visuales del dialogo de inactividad.
 *
 * @return array id => meta
 */
if (!function_exists('aud_idle_plantillas')) {
	function aud_idle_plantillas()
	{
		return array(
			'clasico' => array(
				'id' => 'clasico',
				'nombre' => 'Clasico',
				'descripcion' => 'Ambar con cuenta regresiva circular (estilo actual).',
				'accent' => '#D97706'
			),
			'corporativo' => array(
				'id' => 'corporativo',
				'nombre' => 'Corporativo',
				'descripcion' => 'Azul Exa, cabecera navy y botones primarios.',
				'accent' => '#2563EB'
			),
			'minimal' => array(
				'id' => 'minimal',
				'nombre' => 'Minimal',
				'descripcion' => 'Blanco limpio, acento fino y tipografia sobria.',
				'accent' => '#334155'
			),
			'urgencia' => array(
				'id' => 'urgencia',
				'nombre' => 'Urgencia',
				'descripcion' => 'Rojo/naranja intenso para destacar el cierre inminente.',
				'accent' => '#DC2626'
			),
			'compacto' => array(
				'id' => 'compacto',
				'nombre' => 'Compacto',
				'descripcion' => 'Mas estrecho, contador a un lado y menos altura.',
				'accent' => '#0F766E'
			)
		);
	}
}

/**
 * Normaliza el id de plantilla a uno valido.
 */
if (!function_exists('aud_idle_plantilla_valida')) {
	function aud_idle_plantilla_valida($id)
	{
		$id = strtolower(preg_replace('/[^a-z0-9_]/', '', (string)$id));
		$cats = aud_idle_plantillas();
		if ($id !== '' && isset($cats[$id])) {
			return $id;
		}
		return 'clasico';
	}
}

/**
 * Valores por defecto de la configuracion de inactividad.
 *
 * @return array
 */
if (!function_exists('aud_idle_defaults')) {
	function aud_idle_defaults()
	{
		return array(
			'activo' => true,
			'minutos' => 15,
			'advertencia_seg' => 60,
			'titulo' => 'Advertencia de Inactividad',
			'texto' => 'Tu sesión ha permanecido inactiva por casi {minutos} minutos. Por seguridad, se cerrará automáticamente en {segundos} segundos si no se detecta actividad.',
			'pregunta' => '¿Sigues trabajando en el sistema?',
			'plantilla' => 'clasico',
			'plantillas' => array_values(aud_idle_plantillas())
		);
	}
}

/**
 * Obtiene la configuracion de inactividad de la empresa.
 *
 * @param int         $empCod
 * @param mysqli|null $con
 * @return array
 */
if (!function_exists('aud_idle_obtener_config')) {
	function aud_idle_obtener_config($empCod, $con = null)
	{
		$out = aud_idle_defaults();
		$empCod = (int)$empCod;
		if ($empCod <= 0) {
			return $out;
		}

		$closeOnExit = false;
		if (!$con) {
			$dbConn = new Class_Log_Conexion_Actividad();
			$con = $dbConn->conexion;
			$closeOnExit = true;
		}
		if (!$con) {
			return $out;
		}

		aud_idle_ensure_schema($con);
		$emp = (int)$empCod;
		$r = @mysqli_query($con, "SELECT `Cfg_Activo`,`Cfg_Minutos`,`Cfg_Advertencia_Seg`,`Cfg_Titulo`,`Cfg_Texto`,`Cfg_Plantilla`
			FROM `auditoria`.`cfg_inactividad` WHERE `Emp_Cod`={$emp} LIMIT 1");
		if ($r && ($row = mysqli_fetch_assoc($r))) {
			$out['activo'] = (isset($row['Cfg_Activo']) && strtoupper($row['Cfg_Activo']) === 'A');
			$min = (int)$row['Cfg_Minutos'];
			$adv = (int)$row['Cfg_Advertencia_Seg'];
			if ($min >= 2 && $min <= 480) {
				$out['minutos'] = $min;
			}
			if ($adv >= 15 && $adv <= 600) {
				$out['advertencia_seg'] = $adv;
			}
			if (!empty($row['Cfg_Titulo'])) {
				$out['titulo'] = function_exists('aud_cfg_sanitizar_texto')
					? aud_cfg_sanitizar_texto($row['Cfg_Titulo'])
					: (string)$row['Cfg_Titulo'];
			}
			if (!empty($row['Cfg_Texto'])) {
				$out['texto'] = function_exists('aud_cfg_sanitizar_texto')
					? aud_cfg_sanitizar_texto($row['Cfg_Texto'])
					: (string)$row['Cfg_Texto'];
			}
			if (!empty($row['Cfg_Plantilla'])) {
				$out['plantilla'] = aud_idle_plantilla_valida($row['Cfg_Plantilla']);
			}
		}
		if ($r) {
			mysqli_free_result($r);
		}

		// Asegurar que la advertencia quepa dentro del tiempo total
		$totalSeg = (int)$out['minutos'] * 60;
		if ((int)$out['advertencia_seg'] >= $totalSeg) {
			$out['advertencia_seg'] = max(15, min(60, $totalSeg - 30));
		}

		$out['emp_cod'] = $empCod;
		$out['disparo_modal_seg'] = max(30, $totalSeg - (int)$out['advertencia_seg']);

		if ($closeOnExit) {
			@mysqli_close($con);
		}
		return $out;
	}
}

/**
 * Guarda la configuracion de inactividad (solo admin).
 *
 * @param int         $empCod
 * @param int         $usuCod
 * @param array       $data
 * @param mysqli|null $con
 * @return array
 */
if (!function_exists('aud_idle_guardar_config')) {
	function aud_idle_guardar_config($empCod, $usuCod, $data, $con = null)
	{
		$empCod = (int)$empCod;
		$usuCod = (int)$usuCod;
		if ($empCod <= 0) {
			return array('success' => false, 'message' => 'Empresa no valida.');
		}

		$closeOnExit = false;
		if (!$con) {
			$dbConn = new Class_Log_Conexion_Actividad();
			$con = $dbConn->conexion;
			$closeOnExit = true;
		}
		if (!$con) {
			return array('success' => false, 'message' => 'Sin conexion a base de datos.');
		}

		aud_idle_ensure_schema($con);
		$defs = aud_idle_defaults();

		$activo = !empty($data['activo']) ? 'A' : 'I';
		$minutos = isset($data['minutos']) ? (int)$data['minutos'] : (int)$defs['minutos'];
		$adv = isset($data['advertencia_seg']) ? (int)$data['advertencia_seg'] : (int)$defs['advertencia_seg'];
		$titulo = isset($data['titulo']) ? trim((string)$data['titulo']) : $defs['titulo'];
		$texto = isset($data['texto']) ? trim((string)$data['texto']) : $defs['texto'];
		$plantilla = aud_idle_plantilla_valida(isset($data['plantilla']) ? $data['plantilla'] : $defs['plantilla']);
		if (function_exists('aud_cfg_sanitizar_texto')) {
			$titulo = aud_cfg_sanitizar_texto($titulo);
			$texto = aud_cfg_sanitizar_texto($texto);
		}

		if ($minutos < 2) {
			$minutos = 2;
		}
		if ($minutos > 480) {
			$minutos = 480;
		}
		if ($adv < 15) {
			$adv = 15;
		}
		if ($adv > 600) {
			$adv = 600;
		}
		if ($adv >= ($minutos * 60)) {
			$adv = max(15, min(60, ($minutos * 60) - 30));
		}
		if ($titulo === '') {
			$titulo = $defs['titulo'];
		}
		if ($texto === '') {
			$texto = $defs['texto'];
		}
		if (function_exists('mb_substr')) {
			$titulo = mb_substr($titulo, 0, 160, 'UTF-8');
			$texto = mb_substr($texto, 0, 600, 'UTF-8');
		} else {
			$titulo = substr($titulo, 0, 160);
			$texto = substr($texto, 0, 600);
		}

		$tituloEsc = mysqli_real_escape_string($con, $titulo);
		$textoEsc = mysqli_real_escape_string($con, $texto);
		$plantillaEsc = mysqli_real_escape_string($con, $plantilla);
		$fec = date('Y-m-d H:i:s');

		$sql = "INSERT INTO `auditoria`.`cfg_inactividad`
			(`Emp_Cod`,`Cfg_Activo`,`Cfg_Minutos`,`Cfg_Advertencia_Seg`,`Cfg_Titulo`,`Cfg_Texto`,`Cfg_Plantilla`,`Usu_Cod`,`Cfg_Fec`)
			VALUES ({$empCod},'{$activo}',{$minutos},{$adv},'{$tituloEsc}','{$textoEsc}','{$plantillaEsc}',{$usuCod},'{$fec}')
			ON DUPLICATE KEY UPDATE
				`Cfg_Activo`=VALUES(`Cfg_Activo`),
				`Cfg_Minutos`=VALUES(`Cfg_Minutos`),
				`Cfg_Advertencia_Seg`=VALUES(`Cfg_Advertencia_Seg`),
				`Cfg_Titulo`=VALUES(`Cfg_Titulo`),
				`Cfg_Texto`=VALUES(`Cfg_Texto`),
				`Cfg_Plantilla`=VALUES(`Cfg_Plantilla`),
				`Usu_Cod`=VALUES(`Usu_Cod`),
				`Cfg_Fec`=VALUES(`Cfg_Fec`)";
		$ok = @mysqli_query($con, $sql);

		$cfg = aud_idle_obtener_config($empCod, $con);
		if ($closeOnExit) {
			@mysqli_close($con);
		}

		if (!$ok) {
			return array('success' => false, 'message' => 'No se pudo guardar la configuracion.');
		}
		return array('success' => true, 'message' => 'Configuracion de inactividad guardada.', 'config' => $cfg);
	}
}

/**
 * Indica si el cierre de sesion por inactividad esta habilitado para la empresa.
 * Fuente principal: tabla cfg_inactividad. Kill-switch opcional: AUDIT_IDLE_LOGOUT=false en .env.
 *
 * @param int|null $empCod
 * @return bool
 */
if (!function_exists('aud_ses_idle_logout_activo')) {
	function aud_ses_idle_logout_activo($empCod = null)
	{
		if ($empCod === null && isset($_SESSION['Ses_Emp_Cod'])) {
			$empCod = (int)$_SESSION['Ses_Emp_Cod'];
		}
		$cfg = aud_idle_obtener_config((int)$empCod);
		return !empty($cfg['activo']);
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
			} catch (\Exception $e) {
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
 * @param string $devCod  Identificador del dispositivo (Ses_Dev_Cod).
 * @param string $mac     Direccion MAC del equipo (Ses_Mac).
 * @param string $oauthTok Hash SHA-256 del token OAuth (Ses_OAuth_Tok).
 * @param string $fingerprint Huella digital del navegador (Ses_Fingerprint),
 *               respaldo de auditoria solo cuando $mac viene vacio (acceso
 *               remoto/VPN/Internet fuera de la LAN del servidor).
 * @return int Ses_Cod generado
 */
if (!function_exists('aud_ses_registrar_inicio')) {
	function aud_ses_registrar_inicio($usuCod, $empCod = 0, $sucCod = 0, $con = null, $devCod = '', $mac = '', $oauthTok = '', $fingerprint = '')
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

		$ahora = date('Y-m-d H:i:s');
		$ip = aud_ses_detectar_ip();
		$ubi = aud_ses_detectar_ubicacion($ip);
		$nav = aud_ses_detectar_navegador();
		$token = session_id() !== '' ? session_id() : md5(uniqid((string)$usuCod, true));

		// Ses_Cod se calcula como MAX+1 sin AUTO_INCREMENT ni cerrojo. Dos inicios
		// de sesion concurrentes pueden obtener el mismo codigo y el INSERT fallar
		// en silencio (@). Reintentar en caso de clave duplicada (ERRNO 1062) y
		// devolver 0 solo si despues de varios intentos no se pudo insertar, para
		// que el heartbeat nunca actue sobre un Ses_Cod que no tiene fila propia.
		$codInsertado = 0;
		for ($intento = 0; $intento < 5; $intento++) {
			$sqlNext = "SELECT (IFNULL(MAX(`Ses_Cod`), 0) + 1) AS `next_cod` FROM `auditoria`.`sesion`";
			$rNext = @mysqli_query($con, $sqlNext);
			$nextCod = 1;
			if ($rNext) {
				$rowNext = mysqli_fetch_assoc($rNext);
				$nextCod = !empty($rowNext['next_cod']) ? (int)$rowNext['next_cod'] : 1;
				mysqli_free_result($rNext);
			}

			$sqlIns = sentencias_actividad_sesion(3, array(
				$nextCod,
				(int)$usuCod,
				$ahora,
				(int)$empCod,
				(int)$sucCod,
				$ip,
				$ubi,
				$nav,
				$token,
				$devCod,
				$mac,
				$oauthTok,
				$fingerprint
			));

			if (@mysqli_query($con, $sqlIns)) {
				$codInsertado = $nextCod;
				break;
			}

			// Posible colision de sesiones concurrentes: recalcular y reintentar.
		}

		if ($closeOnExit) {
			@mysqli_close($con);
		}

		return $codInsertado;
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
			// La fila no existe o fue mal vinculada (p.ej. Ses_Cod de otro usuario).
			// NO forzar logout: evita expulsiones involuntarias con cierre por
			// inactividad desactivado. Solo las acciones explicitas del admin
			// (forzada/cerrada) cierran la sesion del navegador.
			if ($closeOnExit) @mysqli_close($con);
			return array('success' => false, 'forzar_logout' => false, 'motivo' => 'no_existe', 'mensaje' => 'Sesion no encontrada.');
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

		// Cerrada por inactividad: no reactivar via heartbeat
		if ($sesion['Ses_Est'] === 'I') {
			if ($closeOnExit) @mysqli_close($con);
			return array('success' => false, 'forzar_logout' => true, 'motivo' => 'inactividad', 'mensaje' => 'Su sesion se cerro por inactividad prolongada.');
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
 * Cierra la sesion por inactividad prolongada.
 * Marca Ses_Est='I' para que Estadisticas por Usuario la cuente como Por_Inactividad.
 * Si no hay Ses_Cod valido, cierra la(s) sesion(es) activa(s) del usuario.
 *
 * @param int $sesCod
 * @param int $usuCod
 * @param mysqli|null $con
 * @return array{success:bool,cerradas:int,ses_cod:int}
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
			return array('success' => false, 'cerradas' => 0, 'ses_cod' => 0);
		}

		aud_ses_asegurar_esquema($con);

		$sesCod = (int)$sesCod;
		$usuCod = (int)$usuCod;
		$cerradas = 0;
		$sesUsado = 0;

		if ($usuCod <= 0 && $sesCod <= 0) {
			if ($closeOnExit) @mysqli_close($con);
			return array('success' => false, 'cerradas' => 0, 'ses_cod' => 0);
		}

		// 1) Intentar con Ses_Cod preferido + Usu_Cod
		if ($sesCod > 0 && $usuCod > 0) {
			$sql = sentencias_actividad_sesion(7, array($sesCod, $usuCod));
			if (@mysqli_query($con, $sql)) {
				$aff = (int)mysqli_affected_rows($con);
				if ($aff > 0) {
					$cerradas = $aff;
					$sesUsado = $sesCod;
				}
			}
		}

		// 2) Si no afecto filas: resolver sesion activa del usuario
		if ($cerradas === 0 && $usuCod > 0) {
			$sqlBusca = sentencias_actividad_sesion(16, array($usuCod, $sesCod));
			$r = @mysqli_query($con, $sqlBusca);
			$row = $r ? mysqli_fetch_assoc($r) : null;
			if ($r) {
				mysqli_free_result($r);
			}
			if ($row && !empty($row['Ses_Cod'])) {
				$sesHallado = (int)$row['Ses_Cod'];
				$sql = sentencias_actividad_sesion(7, array($sesHallado, $usuCod));
				if (@mysqli_query($con, $sql)) {
					$aff = (int)mysqli_affected_rows($con);
					if ($aff > 0) {
						$cerradas = $aff;
						$sesUsado = $sesHallado;
					}
				}
			}
		}

		// 3) Ultimo recurso: cerrar TODAS las activas del usuario (accesos multiples)
		if ($cerradas === 0 && $usuCod > 0) {
			$sqlAll = sentencias_actividad_sesion(17, array($usuCod));
			if (@mysqli_query($con, $sqlAll)) {
				$cerradas = (int)mysqli_affected_rows($con);
			}
		}

		if ($closeOnExit) @mysqli_close($con);
		return array(
			'success' => ($cerradas > 0),
			'cerradas' => $cerradas,
			'ses_cod' => $sesUsado
		);
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
 * Revoca el token OAuth asociado a una sesion (expulsion forzada).
 *
 * La sesion guarda el hash SHA-256 del access token en Ses_OAuth_Tok. Se busca
 * dicho hash en dispositivos_usuario de la base distribuida y se invalidan el
 * access y el refresh token: el navegador expulsado no podra volver a ingresar
 * con esas cookies.
 *
 * @param int $sesCod
 * @return bool
 */
if (!function_exists('aud_ses_revocar_oauth_sesion')) {
	function aud_ses_revocar_oauth_sesion($sesCod)
	{
		$sesCod = (int)$sesCod;
		if ($sesCod <= 0) {
			return false;
		}

		$dbSes = new Class_Log_Conexion_Actividad();
		$conSes = $dbSes->conexion;
		if (!$conSes) {
			return false;
		}

		$hash = '';
		$r = @mysqli_query($conSes, "SELECT `Ses_OAuth_Tok` FROM `auditoria`.`sesion` WHERE `Ses_Cod` = {$sesCod} LIMIT 1");
		if ($r) {
			$fila = mysqli_fetch_assoc($r);
			mysqli_free_result($r);
			if ($fila && !empty($fila['Ses_OAuth_Tok'])) {
				$hash = trim((string)$fila['Ses_OAuth_Tok']);
			}
		}
		mysqli_close($conSes);

		if ($hash === '') {
			return true; // Sesion sin vinculo OAuth: nada que revocar.
		}

		require_once dirname(__FILE__) . '/../../Librerias/OAuth/OAuthServer.php';
		if (!class_exists('ExaOAuth')) {
			return false;
		}

		$dbDis = new Class_Log_Conexion_Actividad();
		$conDis = $dbDis->conexion;
		if (!$conDis) {
			return false;
		}

		ExaOAuth::asegurar_esquema($conDis);
		$ok = ExaOAuth::revocarPorHash($conDis, $hash);
		mysqli_close($conDis);

		return $ok;
	}
}

/**
 * Valida que una sesion pueda ser finalizada forzosamente por el Administrador.
 *
 * Evita: procesos sobre sesiones inexistentes, auto-desconexion del propio
 * administrador, cerrar sesiones de otra empresa y cerrar sesiones que ya no
 * estan activas (cerradas/expulsadas).
 *
 * @param int $sesTarget
 * @param int $empSesion Empresa del administrador que ejecuta la accion.
 * @param int $sesionActual Ses_Cod de la propia sesion del administrador.
 * @param mysqli|null $con
 * @return array {success, error, sesion}
 */
if (!function_exists('aud_ses_validar_cierre_forzado')) {
	function aud_ses_validar_cierre_forzado($sesTarget, $empSesion = 0, $sesionActual = 0, $con = null)
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

		$sesTarget = (int)$sesTarget;
		if ($sesTarget <= 0) {
			if ($closeOnExit) @mysqli_close($con);
			return array('success' => false, 'error' => 'Codigo de sesion invalido');
		}

		$r = @mysqli_query($con, sentencias_actividad_sesion(12, array($sesTarget)));
		$sesion = $r ? mysqli_fetch_assoc($r) : null;
		if ($r) {
			mysqli_free_result($r);
		}

		if (!$sesion) {
			if ($closeOnExit) @mysqli_close($con);
			return array('success' => false, 'error' => 'La sesion indicada no existe o fue eliminada.');
		}

		$estadoSes = isset($sesion['Ses_Est']) ? (string)$sesion['Ses_Est'] : '';
		$empSesTarget = isset($sesion['Emp_Cod']) ? (int)$sesion['Emp_Cod'] : 0;

		if ((int)$sesionActual > 0 && (int)$sesion['Ses_Cod'] === (int)$sesionActual) {
			if ($closeOnExit) @mysqli_close($con);
			return array('success' => false, 'error' => 'No puede finalizar su propia sesion desde el monitor.', 'sesion' => $sesion);
		}

		if ((int)$empSesion > 0 && $empSesTarget > 0 && $empSesTarget !== (int)$empSesion) {
			if ($closeOnExit) @mysqli_close($con);
			return array('success' => false, 'error' => 'No puede finalizar sesiones de otra empresa.', 'sesion' => $sesion);
		}

		if (!in_array($estadoSes, array('A', 'I'), true)) {
			$estadoLbl = $estadoSes === 'F' ? 'expulsada por el Administrador' : ($estadoSes === 'C' ? 'cerrada' : $estadoSes);
			if ($closeOnExit) @mysqli_close($con);
			return array('success' => false, 'error' => 'La sesion ya no se encuentra activa (estado: ' . $estadoLbl . ').', 'sesion' => $sesion);
		}

		if ($closeOnExit) @mysqli_close($con);
		return array('success' => true, 'error' => '', 'sesion' => $sesion);
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
function aud_ses_listar_actividad($empCod = 0, $estado = '', $rolCod = 0, $limite = 100, $con = null, $desde = '', $hasta = '', $usuObservador = 0)
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
				} elseif ($estRaw === 'A' && $minDesdeAct >= 5) {
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
				$row['MinutosInactivo'] = $minDesdeAct;

				$items[] = $row;
			}
			mysqli_free_result($rList);
		}

		// Validaciones en tiempo real: sesiones activas por usuario (acceso multiple)
		// y marcar la propia sesion del observador para impedir auto-desconexion.
		$mapActivas = array();
		$rAct = @mysqli_query($con, sentencias_actividad_sesion(13, array($empCod)));
		if ($rAct) {
			while ($ra = mysqli_fetch_assoc($rAct)) {
				$mapActivas[(int)$ra['Usu_Cod']] = (int)$ra['Total_Activas'];
			}
			mysqli_free_result($rAct);
		}

		$usuariosMultiples = array();
		foreach ($items as $idx => $it) {
			$usu = isset($it['Usu_Cod']) ? (int)$it['Usu_Cod'] : 0;
			$activas = isset($mapActivas[$usu]) ? $mapActivas[$usu] : 0;
			if ($activas === 0 && isset($it['Ses_Est']) && $it['Ses_Est'] === 'A') {
				$activas = 1;
			}
			$items[$idx]['sesiones_activas'] = $activas;
			$items[$idx]['es_mi_sesion'] = ($usuObservador > 0 && $usu === $usuObservador);

			if ($activas > 1 && isset($it['NombreCompleto'])) {
				$usuariosMultiples[] = array(
					'usu_cod' => $usu,
					'nombre' => $it['NombreCompleto'],
					'total' => $activas
				);
			}
		}

		// Consultar KPIs
		$sqlKpis = sentencias_actividad_sesion(10, array($empCod));
		$rKpi = @mysqli_query($con, $sqlKpis);
		$kpisRaw = $rKpi ? mysqli_fetch_assoc($rKpi) : array();
		if ($rKpi) {
			mysqli_free_result($rKpi);
		}
		$kpis = array(
			'en_linea' => !empty($kpisRaw['En_Linea']) ? (int)$kpisRaw['En_Linea'] : 0,
			'ausentes' => !empty($kpisRaw['Ausentes']) ? (int)$kpisRaw['Ausentes'] : 0,
			'total_hoy' => !empty($kpisRaw['Sesiones_Hoy']) ? (int)$kpisRaw['Sesiones_Hoy'] : 0,
			'expulsados_hoy' => !empty($kpisRaw['Expulsados_Hoy']) ? (int)$kpisRaw['Expulsados_Hoy'] : 0,
			'promedio_minutos' => !empty($kpisRaw['Promedio_Min_Uso']) ? (float)$kpisRaw['Promedio_Min_Uso'] : 0
		);

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
			'top_usuarios' => $topUsuarios,
			'validaciones' => array(
				'total_sesiones_activas' => (int)array_sum($mapActivas),
				'usuarios_con_multiples_sesiones' => $usuariosMultiples,
				'sesion_actual' => (int)$usuObservador
			)
		);
	}
}

/**
 * Estadistica de sesiones por usuario (KPIs iniciadas/cerradas en el periodo).
 *
 * @param int $empCod
 * @param string $desde Fecha inicial YYYY-MM-DD (opcional)
 * @param string $hasta Fecha final YYYY-MM-DD (opcional)
 * @param int|mysqli|null $usuCod Filtro de usuario (0 = todos). Si se pasa conexion (legacy), se interpreta como $con.
 * @param mysqli|null $con
 * @return array
 */
if (!function_exists('aud_ses_estadistica_por_usuario')) {
function aud_ses_estadistica_por_usuario($empCod = 0, $desde = '', $hasta = '', $usuCod = 0, $con = null)
{
	// Compatibilidad: llamadas antiguas pasaban $con como 4to argumento
	if (is_object($usuCod) || (is_resource($usuCod))) {
		$con = $usuCod;
		$usuCod = 0;
	}
	$usuCod = (int)$usuCod;

	$out = array(
		'filas' => array(),
		'totales' => array(
			'iniciadas' => 0,
			'cerradas' => 0,
			'por_inactividad' => 0,
			'forzadas' => 0,
			'minutos' => 0,
			'promedio_min' => 0,
			'usuarios' => 0
		),
		'serie_diaria' => array(
			'categorias' => array(),
			'iniciadas' => array(),
			'cerradas' => array(),
			'por_inactividad' => array(),
			'forzadas' => array(),
			'minutos' => array()
		),
		'periodo' => array('desde' => $desde, 'hasta' => $hasta),
		'filtro_usu' => $usuCod,
		'filtro_usu_nombre' => ''
	);
	$closeOnExit = false;
	if (!$con) {
		$dbConn = new Class_Log_Conexion_Actividad();
		$con = $dbConn->conexion;
		$closeOnExit = true;
	}
	if (!$con) {
		return $out;
	}
	aud_ses_asegurar_esquema($con);

	$sql = sentencias_actividad_sesion(14, array($empCod, $desde, $hasta, 200, $usuCod));
	$r = @mysqli_query($con, $sql);
	if ($r) {
		while ($row = mysqli_fetch_assoc($r)) {
			$nombre = trim(
				(isset($row['Prs_Nom']) ? $row['Prs_Nom'] : '') . ' ' .
				(isset($row['Prs_Ape']) ? $row['Prs_Ape'] : '')
			);
			if ($nombre === '') {
				$nombre = isset($row['Usu_Nom']) ? (string)$row['Usu_Nom'] : ('Usuario #'.(int)$row['Usu_Cod']);
			}
			$row['Usuario_Completo'] = $nombre;
			$row['TiempoFormateado'] = aud_ses_tiempo_formateado((int)(isset($row['Total_Min']) ? $row['Total_Min'] : 0));
			$out['totales']['iniciadas'] += (int)(isset($row['Iniciadas']) ? $row['Iniciadas'] : 0);
			$out['totales']['cerradas'] += (int)(isset($row['Cerradas']) ? $row['Cerradas'] : 0);
			$out['totales']['por_inactividad'] += (int)(isset($row['Por_Inactividad']) ? $row['Por_Inactividad'] : 0);
			$out['totales']['forzadas'] += (int)(isset($row['Forzadas']) ? $row['Forzadas'] : 0);
			$out['totales']['minutos'] += (int)(isset($row['Total_Min']) ? $row['Total_Min'] : 0);
			$out['filas'][] = $row;
			if ($usuCod > 0 && $out['filtro_usu_nombre'] === '') {
				$out['filtro_usu_nombre'] = $nombre;
			}
		}
		mysqli_free_result($r);
	}
	$out['totales']['usuarios'] = count($out['filas']);
	if ($out['totales']['iniciadas'] > 0) {
		$out['totales']['promedio_min'] = round($out['totales']['minutos'] / $out['totales']['iniciadas'], 1);
	}

	$sqlDia = sentencias_actividad_sesion(15, array($empCod, $desde, $hasta, $usuCod));
	$rDia = @mysqli_query($con, $sqlDia);
	if ($rDia) {
		while ($row = mysqli_fetch_assoc($rDia)) {
			$out['serie_diaria']['categorias'][] = isset($row['Dia']) ? substr((string)$row['Dia'], 0, 10) : '';
			$out['serie_diaria']['iniciadas'][] = (int)(isset($row['Iniciadas']) ? $row['Iniciadas'] : 0);
			$out['serie_diaria']['cerradas'][] = (int)(isset($row['Cerradas']) ? $row['Cerradas'] : 0);
			$out['serie_diaria']['por_inactividad'][] = (int)(isset($row['Por_Inactividad']) ? $row['Por_Inactividad'] : 0);
			$out['serie_diaria']['forzadas'][] = (int)(isset($row['Forzadas']) ? $row['Forzadas'] : 0);
			$out['serie_diaria']['minutos'][] = (int)(isset($row['Total_Min']) ? $row['Total_Min'] : 0);
		}
		mysqli_free_result($rDia);
	}

	if ($closeOnExit) {
		@mysqli_close($con);
	}
	return $out;
}
}

/** Formatea minutos como "Xh Ym" (reutilizado por KPIs y estadisticas). */
if (!function_exists('aud_ses_tiempo_formateado')) {
function aud_ses_tiempo_formateado($minutos)
{
	$minutos = (int)$minutos;
	$horas = floor($minutos / 60);
	$mins = $minutos % 60;
	return ($horas > 0 ? "{$horas}h " : '') . "{$mins}m";
}
}

if (!function_exists('aud_ses_to_utf8_deep')) {
	/**
	 * Normaliza latin1/ISO-8859-1 de la BD a UTF-8 para json_encode.
	 */
	function aud_ses_to_utf8_deep($data)
	{
		if (is_string($data)) {
			if ($data === '') {
				return $data;
			}
			if (function_exists('mb_check_encoding') && @mb_check_encoding($data, 'UTF-8')) {
				return $data;
			}
			if (function_exists('mb_convert_encoding')) {
				return @mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
			}
			return function_exists('utf8_encode') ? @utf8_encode($data) : $data;
		}
		if (is_array($data)) {
			$clean = array();
			foreach ($data as $k => $v) {
				$ck = is_string($k) ? aud_ses_to_utf8_deep($k) : $k;
				$clean[$ck] = aud_ses_to_utf8_deep($v);
			}
			return $clean;
		}
		return $data;
	}
}

if (!function_exists('aud_ses_json_out')) {
	function aud_ses_json_out($data)
	{
		@ini_set('display_errors', '0');
		$clean = aud_ses_to_utf8_deep($data);
		$json = json_encode($clean);
		echo ($json !== false) ? $json : '{"success":false,"error":"No se pudo serializar la respuesta"}';
	}
}

// -------------------------------------------------------------
// Endpoint AJAX si es invocado directamente mediante POST/GET
// -------------------------------------------------------------
if (basename(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '') === 'aud_log_actividad_sesion.php') {
	header('Content-Type: application/json; charset=utf-8');

	$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

	// Endpoints globales de sesion (home.php / idle tracker): no exigir clave de directorio.
	$accionesSesionGlobales = array('ping', 'inactividad_timeout', 'idle_config', 'ack_expulsion');
	if (!in_array($action, $accionesSesionGlobales, true)) {
		require_once dirname(__FILE__) . '/aud_log_acceso_directorio.php';
		aud_acceso_directorio_gate(isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0);
	}

	if ($action === 'idle_config') {
		$empCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
		$cfg = aud_idle_obtener_config($empCod);
		aud_ses_json_out(array('success' => true, 'config' => $cfg));
		exit;
	}

	if ($action === 'ping') {
		$sesCod = isset($_SESSION['Ses_Ses_Cod']) ? (int)$_SESSION['Ses_Ses_Cod'] : (isset($_REQUEST['ses_cod']) ? (int)$_REQUEST['ses_cod'] : 0);
		$usuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : (isset($_REQUEST['usu_cod']) ? (int)$_REQUEST['usu_cod'] : 0);

		if ($sesCod <= 0 || $usuCod <= 0) {
			aud_ses_json_out(array('success' => false, 'error' => 'Sesion no activa'));
			exit;
		}

		$res = aud_ses_heartbeat_ping($sesCod, $usuCod);
		aud_ses_json_out($res);
		exit;
	}

	if ($action === 'inactividad_timeout') {
		$empCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
		if (!aud_ses_idle_logout_activo($empCod)) {
			aud_ses_json_out(array('success' => false, 'error' => 'Cierre por inactividad desactivado'));
			exit;
		}

		$sesCod = isset($_SESSION['Ses_Ses_Cod']) ? (int)$_SESSION['Ses_Ses_Cod'] : 0;
		if ($sesCod <= 0 && isset($_REQUEST['ses_cod'])) {
			$sesCod = (int)$_REQUEST['ses_cod'];
		}
		$usuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;
		if ($usuCod <= 0 && isset($_REQUEST['usu_cod'])) {
			$usuCod = (int)$_REQUEST['usu_cod'];
		}

		$cierre = array('success' => false, 'cerradas' => 0, 'ses_cod' => 0);
		if ($usuCod > 0 || $sesCod > 0) {
			$cierre = aud_ses_cerrar_por_inactividad($sesCod, $usuCod);
		}

		@session_unset();
		@session_destroy();

		echo json_encode(array(
			'success' => true,
			'registrado' => !empty($cierre['success']),
			'cerradas' => isset($cierre['cerradas']) ? (int)$cierre['cerradas'] : 0,
			'ses_cod' => isset($cierre['ses_cod']) ? (int)$cierre['ses_cod'] : 0,
			'redirect' => '/index.php?motivo=inactividad'
		));
		exit;
	}

	/* Cliente confirma expulsion/cierre: destruir sesion PHP y devolver URL absoluta al index */
	if ($action === 'ack_expulsion') {
		$motivo = isset($_REQUEST['motivo']) ? preg_replace('/[^a-z0-9_\-]/i', '', (string)$_REQUEST['motivo']) : 'expulsado';
		if ($motivo === '') {
			$motivo = 'expulsado';
		}

		if (ini_get('session.use_cookies')) {
			$params = session_get_cookie_params();
			@setcookie(session_name(), '', time() - 42000,
				$params['path'], $params['domain'],
				$params['secure'], $params['httponly']
			);
		}
		$_SESSION = array();
		@session_unset();
		@session_destroy();

		$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
			|| (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
			|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
		$proto = $https ? 'https' : 'http';
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
		$loginUrl = ($host !== '' ? ($proto . '://' . $host) : '') . '/index.php?motivo=' . rawurlencode($motivo);

		aud_ses_json_out(array(
			'success' => true,
			'redirect' => $loginUrl,
			'motivo' => $motivo
		));
		exit;
	}

	if ($action === 'cerrar_forzada') {
		$usuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;
		$obBD_con1 = class_exists('Class_Log_Datos_CfgMon') ? new Class_Log_Datos_CfgMon() : null;
		$obBD_con2 = class_exists('Class_Log_Conexion_CfgMon') ? new Class_Log_Conexion_CfgMon() : null;
		$esAdmin = $obBD_con1 && $obBD_con2 ? aud_cfg_es_admin_sistemas($obBD_con1, $obBD_con2, $usuCod) : false;
		if (!$esAdmin) {
			aud_ses_json_out(array('success' => false, 'error' => 'Permiso denegado. Solo el Administrador de Sistemas puede forzar cierres de sesion.'));
			exit;
		}

		$sesTarget = isset($_POST['ses_cod']) ? (int)$_POST['ses_cod'] : 0;
		if ($sesTarget <= 0) {
			aud_ses_json_out(array('success' => false, 'error' => 'Codigo de sesion invalido'));
			exit;
		}

		$empSesion = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
		$sesionActual = isset($_SESSION['Ses_Ses_Cod']) ? (int)$_SESSION['Ses_Ses_Cod'] : 0;

		$validacion = aud_ses_validar_cierre_forzado($sesTarget, $empSesion, $sesionActual, null);
		if (!$validacion['success']) {
			aud_ses_json_out(array('success' => false, 'error' => $validacion['error']));
			exit;
		}

		$ok = aud_ses_cerrar_forzada($sesTarget);
		$okRev = aud_ses_revocar_oauth_sesion($sesTarget);
		aud_ses_json_out(array('success' => $ok, 'oauth_revocado' => $okRev));
		exit;
	}

	if ($action === 'consultar_actividad') {
		// El heartbeat solo ocurre cuando el monitor de usuarios esta abierto:
		// refresca la propia sesion del observador para que figure como en linea.
		$sesCod = isset($_SESSION['Ses_Ses_Cod']) ? (int)$_SESSION['Ses_Ses_Cod'] : 0;
		$usuCod = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;
		if ($sesCod > 0 && $usuCod > 0) {
			aud_ses_heartbeat_ping($sesCod, $usuCod);
		}

		$empCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
		$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
		$rolCod = isset($_GET['rol']) ? (int)$_GET['rol'] : 0;
		$desde  = isset($_GET['from']) ? trim($_GET['from']) : (isset($_GET['desde']) ? trim($_GET['desde']) : '');
		$hasta  = isset($_GET['to']) ? trim($_GET['to']) : (isset($_GET['hasta']) ? trim($_GET['hasta']) : '');

		$datos = aud_ses_listar_actividad($empCod, $estado, $rolCod, 100, null, $desde, $hasta, $usuCod);
		aud_ses_json_out(array(
			'success' => true,
			'data' => array(
				'sesiones' => isset($datos['items']) ? $datos['items'] : array(),
				'kpis' => isset($datos['kpis']) ? $datos['kpis'] : array(),
				'top_usuarios' => isset($datos['top_usuarios']) ? $datos['top_usuarios'] : array(),
				'validaciones' => isset($datos['validaciones']) ? $datos['validaciones'] : array()
			)
		));
		exit;
	}

	if ($action === 'listar') {
		$empCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
		$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
		$rolCod = isset($_GET['rol']) ? (int)$_GET['rol'] : 0;
		$desde  = isset($_GET['desde']) ? trim($_GET['desde']) : '';
		$hasta  = isset($_GET['hasta']) ? trim($_GET['hasta']) : '';
		$usuObservador = isset($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;

		$datos = aud_ses_listar_actividad($empCod, $estado, $rolCod, 100, null, $desde, $hasta, $usuObservador);
		aud_ses_json_out(array('success' => true, 'data' => $datos));
		exit;
	}

	if ($action === 'estadistica_usuarios') {
		$empCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
		$desde = isset($_REQUEST['from']) ? trim($_REQUEST['from']) : (isset($_REQUEST['desde']) ? trim($_REQUEST['desde']) : '');
		$hasta = isset($_REQUEST['to']) ? trim($_REQUEST['to']) : (isset($_REQUEST['hasta']) ? trim($_REQUEST['hasta']) : '');
		$usuFiltro = isset($_REQUEST['usu']) ? (int)$_REQUEST['usu'] : (isset($_REQUEST['usu_cod']) ? (int)$_REQUEST['usu_cod'] : 0);
		$stats = aud_ses_estadistica_por_usuario($empCod, $desde, $hasta, $usuFiltro);
		aud_ses_json_out(array('success' => true, 'data' => $stats));
		exit;
	}

	if ($action === 'exportar_estadistica_csv') {
		$empCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
		$desde = isset($_REQUEST['from']) ? trim($_REQUEST['from']) : '';
		$hasta = isset($_REQUEST['to']) ? trim($_REQUEST['to']) : '';
		$usuFiltro = isset($_REQUEST['usu']) ? (int)$_REQUEST['usu'] : 0;
		$stats = aud_ses_estadistica_por_usuario($empCod, $desde, $hasta, $usuFiltro);
		$filas = isset($stats['filas']) ? $stats['filas'] : array();
		$fname = 'estadistica_sesiones_' . preg_replace('/[^0-9\-]/', '', $desde . '_' . $hasta) . '.csv';
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="' . $fname . '"');
		header('Pragma: no-cache');
		header('Expires: 0');
		$out = fopen('php://output', 'w');
		fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
		fputcsv($out, array('Usuario', 'Login', 'Iniciadas', 'Cerradas', 'Por_Inactividad', 'Forzadas', 'Promedio_Min', 'Total_Min', 'Ultima_Actividad'), ';');
		foreach ($filas as $r) {
			fputcsv($out, array(
				isset($r['Usuario_Completo']) ? $r['Usuario_Completo'] : '',
				isset($r['Usu_Nom']) ? $r['Usu_Nom'] : '',
				isset($r['Iniciadas']) ? $r['Iniciadas'] : 0,
				isset($r['Cerradas']) ? $r['Cerradas'] : 0,
				isset($r['Por_Inactividad']) ? $r['Por_Inactividad'] : 0,
				isset($r['Forzadas']) ? $r['Forzadas'] : 0,
				isset($r['Promedio_Min']) ? $r['Promedio_Min'] : 0,
				isset($r['Total_Min']) ? $r['Total_Min'] : 0,
				isset($r['Ultima_Actividad']) ? $r['Ultima_Actividad'] : ''
			), ';');
		}
		fclose($out);
		exit;
	}

	if ($action === 'exportar_estadistica_pdf') {
		$empCod = isset($_SESSION['Ses_Emp_Cod']) ? (int)$_SESSION['Ses_Emp_Cod'] : 0;
		$desde = isset($_REQUEST['from']) ? trim($_REQUEST['from']) : '';
		$hasta = isset($_REQUEST['to']) ? trim($_REQUEST['to']) : '';
		$usuFiltro = isset($_REQUEST['usu']) ? (int)$_REQUEST['usu'] : 0;
		$stats = aud_ses_estadistica_por_usuario($empCod, $desde, $hasta, $usuFiltro);
		$stats['empresa'] = isset($_SESSION['Ses_Emp_Nom']) ? $_SESSION['Ses_Emp_Nom'] : ('Empresa ' . $empCod);
		$emisor = '';
		if (isset($_SESSION['Ses_Usu_Nom'])) {
			$emisor = trim((string)$_SESSION['Ses_Usu_Nom']);
		}
		if ($emisor === '' && isset($_SESSION['Ses_Prs_Nom'])) {
			$emisor = trim((isset($_SESSION['Ses_Prs_Nom']) ? $_SESSION['Ses_Prs_Nom'] : '') . ' ' . (isset($_SESSION['Ses_Prs_Ape']) ? $_SESSION['Ses_Prs_Ape'] : ''));
		}
		$stats['usuario_emisor'] = $emisor !== '' ? $emisor : 'Auditor del Sistema';
		require_once dirname(__FILE__) . '/aud_rep_actividad_est_pdf.php';
		aud_generar_reporte_actividad_est_pdf($stats, 'I');
		exit;
	}

	aud_ses_json_out(array('success' => false, 'error' => 'Accion no reconocida'));
	exit;
}

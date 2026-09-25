<?php
/**
 * ExaOAuth - Emision y validacion de tokens OAuth 2.0-lite en PHP puro
 * para el control de acceso por dispositivo (inventario MAC).
 *
 * SIN dependencias externas ni Composer. Opera sobre la conexion mysqli de la
 * base de datos distribuida y almacena UNICAMENTE hashes SHA-256 de los tokens.
 * El token en claro se entrega una sola vez (issue/refresh) y se conserva del
 * lado del navegador en cookies HTTPOnly:
 *   exa_oauth_access  (access token, 8 h)   exa_oauth_refresh (refresh, 30 dias)
 *   exa_dev_cod       (identificador persistente del navegador/dispositivo)
 *
 * Flujo esperado por el login:
 *   1. Si el usuario tiene dispositivos asignados y ya posee un access token
 *      valido (cookie) -> se valida y se permite el ingreso.
 *   2. Si no tiene token -> se busca un cupo libre en los equipos asignados
 *      (filas de usuario_inventario con cupo disponible) y se emiten tokens.
 *   3. Si el token vencio -> se rotan con el refresh token y se renuevan cookies.
 *   4. Si no hay cupos -> se rechaza el ingreso (error_type 'device').
 *
 * Tabla objetivo (base distribuida): dispositivos_usuario
 *   DisUsr_Cod       INT PK AUTO_INCREMENT    (vinculo navegador)
 *   Usu_Cod          INT NOT NULL
 *   Dev_Cod          VARCHAR(64)              (identificador de navegador del cliente)
 *   InvDis_Cod       INT                      (equipo inventariado -> mac_address)
 *   DisUsr_Nom       VARCHAR(120)             (nombre del equipo)
 *   DisUsr_IP        VARCHAR(45)
 *   user_agent       VARCHAR(255)
 *   DisUsr_Est       CHAR(1) 'A'/'I'
 *   DisUsr_FecR / DisUsr_FecUA  DATETIME
 *   DisUsr_DevCod    VARCHAR(64)   [OAuth] persistencia del dev_cod
 *   DisUsr_Mac       VARCHAR(17)   [OAuth] referencia de MAC del equipo
 *   DisUsr_Token     CHAR(64)      [OAuth] sha256(access_token)
 *   DisUsr_TokExp    DATETIME      [OAuth]
 *   DisUsr_Refresh   CHAR(64)      [OAuth] sha256(refresh_token)
 *   DisUsr_RefExp    DATETIME      [OAuth]
 *
 * @package Librerias.OAuth
 */

class ExaOAuth
{
	const ACCESS_TTL = 28800;    // 8 horas
	const REFRESH_TTL = 2592000; // 30 dias

	/**
	 * Asegura de forma idempotente el esquema de dispositivos_usuario.
	 *
	 * @param mysqli $con
	 * @return bool
	 */
	public static function asegurar_esquema($con)
	{
		if (!$con) {
			return false;
		}

		@mysqli_query($con, "CREATE TABLE IF NOT EXISTS `dispositivos_usuario` (
			`DisUsr_Cod` INT(11) NOT NULL AUTO_INCREMENT,
			`Usu_Cod` INT(11) NOT NULL,
			`Dev_Cod` VARCHAR(64) DEFAULT '',
			`InvDis_Cod` INT(11) DEFAULT NULL,
			`DisUsr_Nom` VARCHAR(120) DEFAULT NULL,
			`DisUsr_IP` VARCHAR(45) DEFAULT NULL,
			`user_agent` VARCHAR(255) DEFAULT NULL,
			`DisUsr_Est` CHAR(1) NOT NULL DEFAULT 'A',
			`DisUsr_FecR` DATETIME DEFAULT NULL,
			`DisUsr_FecUA` DATETIME DEFAULT NULL,
			`DisUsr_DevCod` VARCHAR(64) DEFAULT NULL,
			`DisUsr_Mac` VARCHAR(17) DEFAULT NULL,
			`DisUsr_Token` CHAR(64) DEFAULT NULL,
			`DisUsr_TokExp` DATETIME DEFAULT NULL,
			`DisUsr_Refresh` CHAR(64) DEFAULT NULL,
			`DisUsr_RefExp` DATETIME DEFAULT NULL,
			PRIMARY KEY (`DisUsr_Cod`),
			KEY `idx_du_usu_est` (`Usu_Cod`,`DisUsr_Est`),
			KEY `idx_du_dev` (`Dev_Cod`),
			KEY `idx_du_token` (`DisUsr_Token`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");

		$columnasActuales = array();
		$r = @mysqli_query($con, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dispositivos_usuario'");
		if ($r) {
			while ($f = mysqli_fetch_assoc($r)) {
				$columnasActuales[strtolower(isset($f['COLUMN_NAME']) ? $f['COLUMN_NAME'] : '')] = true;
			}
			mysqli_free_result($r);
		}

		$alters = array();
		if (empty($columnasActuales['invdis_cod'])) {
			$alters[] = "ADD COLUMN `InvDis_Cod` INT(11) DEFAULT NULL AFTER `Dev_Cod`";
		}
		if (empty($columnasActuales['disusr_nom'])) {
			$alters[] = "ADD COLUMN `DisUsr_Nom` VARCHAR(120) DEFAULT NULL AFTER `InvDis_Cod`";
		}
		if (empty($columnasActuales['disusr_ip'])) {
			$alters[] = "ADD COLUMN `DisUsr_IP` VARCHAR(45) DEFAULT NULL AFTER `DisUsr_Nom`";
		}
		if (empty($columnasActuales['user_agent'])) {
			$alters[] = "ADD COLUMN `user_agent` VARCHAR(255) DEFAULT NULL AFTER `DisUsr_IP`";
		}
		if (empty($columnasActuales['disusr_est'])) {
			$alters[] = "ADD COLUMN `DisUsr_Est` CHAR(1) NOT NULL DEFAULT 'A' AFTER `user_agent`";
		}
		if (empty($columnasActuales['disusr_fecr'])) {
			$alters[] = "ADD COLUMN `DisUsr_FecR` DATETIME DEFAULT NULL AFTER `DisUsr_Est`";
		}
		if (empty($columnasActuales['disusr_fecua'])) {
			$alters[] = "ADD COLUMN `DisUsr_FecUA` DATETIME DEFAULT NULL AFTER `DisUsr_FecR`";
		}
		if (empty($columnasActuales['disusr_devcod'])) {
			$alters[] = "ADD COLUMN `DisUsr_DevCod` VARCHAR(64) DEFAULT NULL AFTER `DisUsr_FecUA`";
		}
		if (empty($columnasActuales['disusr_mac'])) {
			$alters[] = "ADD COLUMN `DisUsr_Mac` VARCHAR(17) DEFAULT NULL AFTER `DisUsr_DevCod`";
		}
		if (empty($columnasActuales['disusr_token'])) {
			$alters[] = "ADD COLUMN `DisUsr_Token` CHAR(64) DEFAULT NULL AFTER `DisUsr_Mac`";
		}
		if (empty($columnasActuales['disusr_tokexp'])) {
			$alters[] = "ADD COLUMN `DisUsr_TokExp` DATETIME DEFAULT NULL AFTER `DisUsr_Token`";
		}
		if (empty($columnasActuales['disusr_refresh'])) {
			$alters[] = "ADD COLUMN `DisUsr_Refresh` CHAR(64) DEFAULT NULL AFTER `DisUsr_TokExp`";
		}
		if (empty($columnasActuales['disusr_refexp'])) {
			$alters[] = "ADD COLUMN `DisUsr_RefExp` DATETIME DEFAULT NULL AFTER `DisUsr_Refresh`";
		}

		if (!empty($alters)) {
			@mysqli_query($con, "ALTER TABLE `dispositivos_usuario` " . implode(', ', $alters));
		}

		return true;
	}

	/**
	 * Escapa un valor para consultas sobre la conexion dada.
	 *
	 * @param mysqli $con
	 * @param mixed $v
	 * @return string
	 */
	public static function e($con, $v)
	{
		return function_exists('mysqli_real_escape_string')
			? mysqli_real_escape_string($con, (string)$v)
			: str_replace(array('\\', "'"), array('\\\\', "\\'"), (string)$v);
	}

	/**
	 * Hash SHA-256 del token en claro (unico valor persistido en base).
	 *
	 * @param string $raw
	 * @return string
	 */
	public static function hashToken($raw)
	{
		return hash('sha256', (string)$raw);
	}

	/**
	 * Genera un token aleatorio de 32 bytes en hexadecimal (64 chars).
	 *
	 * @return string
	 */
	public static function generarToken()
	{
		if (function_exists('random_bytes')) {
			return bin2hex(random_bytes(32));
		}
		return md5(uniqid((string)mt_rand(), true)) . md5(uniqid((string)mt_rand(), true));
	}

	/**
	 * Genera un identificador de dispositivo (navegador) legible y unico.
	 *
	 * @return string
	 */
	public static function generarDevCod()
	{
		if (function_exists('random_bytes')) {
			$hex = bin2hex(random_bytes(6));
		} else {
			$hex = substr(md5(uniqid((string)mt_rand(), true)), 0, 12);
		}
		return 'DEV-' . strtoupper($hex);
	}

	/**
	 * Nombres de cookies usados por el control OAuth.
	 */
	public static function cookieAcceso() { return 'exa_oauth_access'; }
	public static function cookieRefresh() { return 'exa_oauth_refresh'; }
	public static function cookieDev() { return 'exa_dev_cod'; }

	/**
	 * Establece las cookies HTTPOnly del dispositivo OAuth.
	 *
	 * @param string $devCod
	 * @param string $accessToken
	 * @param string $refreshToken
	 * @param string $expiresAt Fecha/hora datetime de expiracion del access token.
	 * @return void
	 */
	public static function setCookies($devCod, $accessToken, $refreshToken, $expiresAt)
	{
		$exp = is_numeric($expiresAt) ? (int)$expiresAt : strtotime((string)$expiresAt);
		$exp = ($exp !== false && $exp > 0) ? $exp : (time() + self::ACCESS_TTL);
		$refExp = time() + self::REFRESH_TTL;

		@setcookie(self::cookieAcceso(), (string)$accessToken, $exp, '/', '', false, true);
		@setcookie(self::cookieRefresh(), (string)$refreshToken, $refExp, '/', '', false, true);
		@setcookie(self::cookieDev(), (string)$devCod, 0, '/', '', false, false);
	}

	/**
	 * Deteccion de IP real del cliente (misma heuristica que auditoria).
	 *
	 * @return string
	 */
	public static function detectarIp()
	{
		$headers = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
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

	/**
	 * User-Agent actual del cliente.
	 *
	 * @return string
	 */
	public static function userAgent()
	{
		return !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	}

	/**
	 * Normaliza una direccion MAC a mayusculas con separadores ':' (AA:BB:CC:DD:EE:FF).
	 *
	 * @param string $mac
	 * @return string
	 */
	public static function normalizarMac($mac)
	{
		$mac = trim((string)$mac);
		if ($mac !== '') {
			$mac = strtoupper(str_replace(array('-', ' ', '.'), ':', $mac));
			if (preg_match('/^[0-9A-F]{2}(?::[0-9A-F]{2}){5}$/', $mac)) {
				return $mac;
			}
		}
		return '';
	}

	/**
	 * Normaliza un identificador de huella digital de navegador (fallback de
	 * identificacion de equipo cuando la MAC no es detectable porque el cliente
	 * esta fuera de la LAN del servidor: acceso remoto, VPN o Internet). Se
	 * limita a un formato hexadecimal corto: nunca se almacenan datos crudos
	 * de fingerprinting (canvas, user-agent, etc.), solo el hash ya calculado
	 * en el navegador (ver exaDeviceFingerprint() en index.php).
	 *
	 * @param string $fp
	 * @return string Cadena hexadecimal en mayusculas (<=40 chars) o '' si es invalida.
	 */
	public static function normalizarFingerprint($fp)
	{
		$fp = trim((string)$fp);
		if ($fp === '') {
			return '';
		}
		$fp = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $fp));
		if ($fp === '' || strlen($fp) < 8) {
			return '';
		}
		return substr($fp, 0, 40);
	}

	/**
	 * Detecta la MAC del equipo del cliente consultando la tabla ARP del servidor
	 * a partir del IP de conexion (REMOTE_ADDR). Los navegadores no exponen la MAC,
	 * por lo que la deteccion se hace del lado servidor y funciona solo en la misma
	 * red local (LAN). Para pruebas locales se puede simular con la variable EXA_MAC_SIM.
	 *
	 * @param string $ip IP cliente (vacio = usar $_SERVER['REMOTE_ADDR'])
	 * @return string MAC normalizada o '' si no es detectable
	 */
	public static function detectarMacCliente($ip = '')
	{
		$sim = getenv('EXA_MAC_SIM');
		if ($sim !== false && trim((string)$sim) !== '') {
			return self::normalizarMac($sim);
		}

		// Preferir IP real del cliente (X-Real-IP / X-Forwarded-For). Con nginx/Plesk
		// REMOTE_ADDR suele ser 127.0.0.1 y ARP nunca ve al equipo de la LAN.
		if (trim((string)$ip) === '') {
			$ip = self::detectarIp();
		}
		$ip = trim((string)$ip);
		if ($ip === '' || $ip === '0.0.0.0' || $ip === '::1' || strtolower($ip) === 'localhost') {
			return '';
		}
		if (substr($ip, 0, 3) === '127.' || substr($ip, 0, 4) === '169.' || strpos($ip, '.') === false) {
			return '';
		}
		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return '';
		}

		$out = self::consultaArp($ip);
		if ($out === '' || !self::extraerMacDeArp($ip, $out)) {
			// Rebote ARP para poblar la tabla la primera vez (opcional, sin exceso de espera).
			if (DIRECTORY_SEPARATOR === '\\') {
				self::ejecutar('ping -n 1 -w 300 ' . self::escapeArg($ip));
			} else {
				self::ejecutar('ping -c 1 -W 1 ' . self::escapeArg($ip));
			}
			$out = self::consultaArp($ip);
		}

		return self::extraerMacDeArp($ip, $out);
	}

	/**
	 * Extrae y normaliza la MAC asociada a un IP desde la salida de ARP / /proc/net/arp.
	 * Rechaza entradas incompletas (00:00:00:00:00:00).
	 *
	 * @param string $ip
	 * @param string $out
	 * @return string
	 */
	private static function extraerMacDeArp($ip, $out)
	{
		$out = (string)$out;
		$ip = (string)$ip;
		if ($out === '' || $ip === '') {
			return '';
		}
		$macRaw = '';
		// Windows "arp -a":  IP  xx-xx-xx-xx-xx-xx  tipo
		// Linux "arp -an" / "ip neigh": IP ... lladdr xx:xx:...  / IP ether xx:xx:...
		// Linux /proc/net/arp: IP  0x1  0x2  xx:xx:xx:xx:xx:xx  *  eth0
		if (preg_match('/\b' . preg_quote($ip, '/') . '\b(?:\s+\S+){0,3}\s+([0-9a-fA-F]{2}(?:[:-][0-9a-fA-F]{2}){5})/i', $out, $m)) {
			$macRaw = $m[1];
		} elseif (preg_match('/\b' . preg_quote($ip, '/') . '\b[^\r\n]*?(?:lladdr|ether)\s+([0-9a-fA-F]{2}(?:[:-][0-9a-fA-F]{2}){5})/i', $out, $m)) {
			$macRaw = $m[1];
		}
		$mac = self::normalizarMac($macRaw);
		if ($mac === '' || $mac === '00:00:00:00:00:00') {
			return '';
		}
		return $mac;
	}

	/**
	 * Ejecuta un comando de consola de forma segura (devuelve salida limpia).
	 *
	 * @param string $cmd
	 * @return string
	 */
	private static function ejecutar($cmd)
	{
		if (stripos(PHP_OS, 'WIN') !== false || DIRECTORY_SEPARATOR === '\\') {
			$cmd .= ' 2>NUL';
		} else {
			$cmd .= ' 2>/dev/null';
		}
		$out = @shell_exec($cmd);
		return is_string($out) ? trim($out) : '';
	}

	/**
	 * Escapa un argumento para comandos de consola (solo se usa con IPs validadas).
	 *
	 * @param string $v
	 * @return string
	 */
	private static function escapeArg($v)
	{
		return (DIRECTORY_SEPARATOR === '\\') ? '"' . str_replace('"', '', $v) . '"' : escapeshellarg($v);
	}

	/**
	 * Consulta la MAC de un IP en la tabla ARP del servidor.
	 *
	 * @param string $ip
	 * @return string
	 */
	private static function consultaArp($ip)
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			// En Windows no se puede emitir arp -a <ip> si el proceso es de baja integridad; se lee la tabla completa.
			return self::ejecutar('arp -a ');
		}
		$out = @file_get_contents('/proc/net/arp');
		$out = is_string($out) ? (string)$out : '';
		if ($out !== '' && self::extraerMacDeArp($ip, $out) !== '') {
			return $out;
		}
		// Fallbacks Linux (PATH de PHP-FPM a veces no incluye /sbin|/usr/sbin).
		$fallbacks = array(
			'/sbin/arp -an ' . self::escapeArg($ip),
			'/usr/sbin/arp -an ' . self::escapeArg($ip),
			'arp -an ' . self::escapeArg($ip),
			'/sbin/ip neigh show ' . self::escapeArg($ip),
			'/usr/sbin/ip neigh show ' . self::escapeArg($ip),
			'ip neigh show ' . self::escapeArg($ip)
		);
		foreach ($fallbacks as $cmd) {
			$alt = self::ejecutar($cmd);
			if ($alt !== '' && self::extraerMacDeArp($ip, $alt) !== '') {
				return $alt;
			}
		}
		return $out;
	}

	/**
	 * Busca entre los equipos asignados al usuario uno cuya MAC coincida con la
	 * detectada en el equipo cliente (regla condicional: ese usuario solo desde esa MAC).
	 *
	 * @param mysqli $con
	 * @param int $usuCod
	 * @param string $mac
	 * @return array|null Fila {InvDis_Cod, InvDis_Nom, mac_address, InvDis_Tipo}
	 */
	public static function buscarMacEnAsignaciones($con, $usuCod, $mac)
	{
		$usuCod = (int)$usuCod;
		$mac = self::normalizarMac($mac);
		if ($usuCod <= 0 || $mac === '' || !$con) {
			return null;
		}
		$r = @mysqli_query($con, "SELECT inv.InvDis_Cod, inv.InvDis_Nom, inv.mac_address, inv.InvDis_Tipo
			FROM `inventario_dispositivos` inv
			INNER JOIN `usuario_inventario` ui ON ui.InvDis_Cod = inv.InvDis_Cod
			WHERE ui.UsInv_Usu = {$usuCod}
			  AND ui.UsInv_Est = 'A'
			  AND inv.InvDis_Est = 'A'
			  AND UPPER(TRIM(inv.mac_address)) = '" . strtoupper(self::e($con, $mac)) . "'
			LIMIT 1");
		if (!$r) {
			return null;
		}
		$row = mysqli_fetch_assoc($r);
		mysqli_free_result($r);
		return $row ? $row : null;
	}

	/**
	 * (Re)genera el numero de cupos para un equipo del inventario.
	 *
	 * @param mysqli $con
	 * @param int $invDisCod
	 * @return int|null
	 */
	public static function obtenerCupos($con, $invDisCod)
	{
		$invDisCod = (int)$invDisCod;
		if ($invDisCod <= 0 || !$con) {
			return null;
		}
		$r = @mysqli_query($con, "SELECT `InvDis_Cupos` FROM `inventario_dispositivos` WHERE `InvDis_Cod` = {$invDisCod} LIMIT 1");
		if (!$r) {
			return null;
		}
		$row = mysqli_fetch_assoc($r);
		mysqli_free_result($r);
		return !empty($row) ? (int)$row['InvDis_Cupos'] : null;
	}

	/**
	 * Registra de forma automatica un equipo nuevo en el inventario con la MAC
	 * detectada del cliente y lo asigna al usuario (politica de primer login).
	 * Idempotente: si ya existe una asignacion con esa MAC para el usuario la reutiliza.
	 *
	 * @param mysqli $con
	 * @param int $usuCod
	 * @param string $mac
	 * @param string $tipo
	 * @return array {success, error?, message?, inv_dis_cod, nom, mac, created}
	 */
	public static function autoRegistrarDispositivo($con, $usuCod, $mac, $tipo = 'PC')
	{
		$usuCod = (int)$usuCod;
		$mac = self::normalizarMac($mac);
		if ($usuCod <= 0 || $mac === '' || !$con) {
			return array('success' => false, 'error' => 'registro_fallido', 'message' => 'Datos insuficientes para registrar el equipo.');
		}
		$tipo = trim((string)$tipo);
		if ($tipo === '' || strlen($tipo) > 10) {
			$tipo = 'PC';
		}

		$existente = self::buscarMacEnAsignaciones($con, $usuCod, $mac);
		if ($existente && !empty($existente['InvDis_Cod'])) {
			return array(
				'success' => true, 'error' => '', 'message' => 'Equipo ya registrado y asignado.',
				'inv_dis_cod' => (int)$existente['InvDis_Cod'],
				'nom' => isset($existente['InvDis_Nom']) ? (string)$existente['InvDis_Nom'] : '',
				'mac' => $mac,
				'created' => false
			);
		}

		$nom = 'Equipo Usr ' . $usuCod . ' (' . $mac . ')';
		$ok = @mysqli_query($con, "INSERT INTO `inventario_dispositivos`
			(`mac_address`,`InvDis_Nom`,`InvDis_Tipo`,`InvDis_Cupos`,`InvDis_Est`)
			VALUES ('" . self::e($con, $mac) . "','" . self::e($con, $nom) . "','" . self::e($con, $tipo) . "',1,'A')");
		$invDisCod = $ok ? (int)@mysqli_insert_id($con) : 0;
		if ($invDisCod <= 0) {
			return array('success' => false, 'error' => 'registro_fallido', 'message' => 'No se pudo crear el equipo en el inventario.');
		}

		@mysqli_query($con, "INSERT INTO `usuario_inventario` (`UsInv_Usu`,`InvDis_Cod`,`UsInv_Fec`,`UsInv_Est`)
			VALUES ({$usuCod},{$invDisCod},NOW(),'A')");

		return array(
			'success' => true, 'error' => '', 'message' => 'Equipo registrado y asignado automaticamente.',
			'inv_dis_cod' => $invDisCod,
			'nom' => $nom,
			'mac' => $mac,
			'created' => true
		);
	}

	/**
	 * Datos de un equipo del inventario (nombre, MAC y tipo).
	 *
	 * @param mysqli $con
	 * @param int $invDisCod
	 * @return array|null
	 */
	public static function getDispositivo($con, $invDisCod)
	{
		$invDisCod = (int)$invDisCod;
		if ($invDisCod <= 0 || !$con) {
			return null;
		}
		$r = @mysqli_query($con, "SELECT `InvDis_Nom`, `mac_address`, `InvDis_Tipo`
			FROM `inventario_dispositivos` WHERE `InvDis_Cod` = {$invDisCod} LIMIT 1");
		if (!$r) {
			return null;
		}
		$row = mysqli_fetch_assoc($r);
		mysqli_free_result($r);
		return $row ? $row : null;
	}

	/**
	 * Busca un cupo libre por tipo de equipo (PC/MOVIL) verificando el maximo de
	 * cupos (InvDis_Cupos) y el conteo de vinculos activos (dispositivos_usuario).
	 *
	 * @param mysqli $con
	 * @param int $usuCod
	 * @param string $tipo
	 * @return array|null Fila {InvDis_Cod, InvDis_Nom, mac_address}
	 */
	public static function findFreeCupo($con, $usuCod, $tipo)
	{
		$usuCod = (int)$usuCod;
		$tipo = self::e($con, (string)$tipo);
		$r = @mysqli_query($con, "SELECT ui.InvDis_Cod, inv.InvDis_Nom, inv.mac_address
			FROM `usuario_inventario` ui
			INNER JOIN `inventario_dispositivos` inv ON ui.InvDis_Cod = inv.InvDis_Cod
			LEFT JOIN `dispositivos_usuario` du ON (ui.InvDis_Cod = du.InvDis_Cod AND du.Usu_Cod = ui.UsInv_Usu AND du.DisUsr_Est = 'A')
			WHERE ui.UsInv_Usu = {$usuCod}
			  AND ui.UsInv_Est = 'A'
			  AND inv.InvDis_Est = 'A'
			  AND inv.InvDis_Tipo = '{$tipo}'
			GROUP BY ui.InvDis_Cod, inv.InvDis_Nom, inv.InvDis_Cupos, inv.mac_address
			HAVING COUNT(du.DisUsr_Cod) < inv.InvDis_Cupos
			LIMIT 1");
		if (!$r) {
			return null;
		}
		$row = mysqli_fetch_assoc($r);
		mysqli_free_result($r);
		return $row ? $row : null;
	}

	/**
	 * Busca el vinculo activo de un navegador (por Dev_Cod o por DisUsr_DevCod).
	 *
	 * @param mysqli $con
	 * @param int $usuCod
	 * @param string $devCod
	 * @return array|null
	 */
	public static function buscarDisUsr($con, $usuCod, $devCod)
	{
		$usuCod = (int)$usuCod;
		$devCod = self::e($con, trim((string)$devCod));
		if ($devCod === '' || !$con) {
			return null;
		}
		$r = @mysqli_query($con, "SELECT du.*
			FROM `dispositivos_usuario` du
			WHERE du.Usu_Cod = {$usuCod}
			  AND du.DisUsr_Est = 'A'
			  AND (du.Dev_Cod = '{$devCod}' OR du.DisUsr_DevCod = '{$devCod}')
			ORDER BY du.DisUsr_Cod DESC LIMIT 1");
		if (!$r) {
			return null;
		}
		$row = mysqli_fetch_assoc($r);
		mysqli_free_result($r);
		return $row ? $row : null;
	}

	/**
	 * Valida el access token de un usuario y refresca sus metadatos de acceso.
	 *
	 * @param mysqli $con
	 * @param int $usuCod
	 * @param string $accessToken Token en claro (COOKIE exa_oauth_access).
	 * @param string $devCod      Dev_Cod propuesto (opcional).
	 * @return array {success, error?, message?, token_hash, dev_cod, dis_usr_cod,
	 *                inv_dis_cod, mac, dis_nom, tipo, expires_at, expires_in, about_to_expire}
	 */
	public static function validarToken($con, $usuCod, $accessToken, $devCod = '')
	{
		$usuCod = (int)$usuCod;
		$hash = self::hashToken($accessToken);
		$r = @mysqli_query($con, "SELECT du.*, inv.mac_address, inv.InvDis_Nom, inv.InvDis_Tipo
			FROM `dispositivos_usuario` du
			LEFT JOIN `inventario_dispositivos` inv ON du.InvDis_Cod = inv.InvDis_Cod
			WHERE du.DisUsr_Token = '{$hash}' AND du.Usu_Cod = {$usuCod} AND du.DisUsr_Est = 'A'
			LIMIT 1");
		if (!$r) {
			return array('success' => false, 'error' => 'invalid_token', 'message' => 'No fue posible verificar el token de acceso.');
		}
		$row = mysqli_fetch_assoc($r);
		mysqli_free_result($r);
		if (!$row) {
			return array('success' => false, 'error' => 'invalid_token', 'message' => 'El token de acceso no existe o fue revocado.');
		}

		$tokExp = !empty($row['DisUsr_TokExp']) ? strtotime($row['DisUsr_TokExp']) : 0;
		if ($tokExp === false || $tokExp <= time()) {
			return array('success' => false, 'error' => 'expired_token', 'message' => 'El token de acceso ha expirado.');
		}

		$nuevoDev = trim((string)$devCod);
		$acualDev = isset($row['DisUsr_DevCod']) ? trim((string)$row['DisUsr_DevCod']) : '';
		if ($nuevoDev !== '' && $nuevoDev !== $acualDev) {
			$acualDev = $nuevoDev;
		}
		if ($acualDev === '') {
			$acualDev = isset($row['Dev_Cod']) ? trim((string)$row['Dev_Cod']) : '';
		}

		$ip = self::detectarIp();
		$ua = self::userAgent();
		@mysqli_query($con, "UPDATE `dispositivos_usuario`
			SET DisUsr_FecUA = NOW(), DisUsr_IP = '" . self::e($con, $ip) . "',
			    user_agent = '" . self::e($con, $ua) . "'" .
			($acualDev !== '' ? ", DisUsr_DevCod = '" . self::e($con, $acualDev) . "'" : '') .
			" WHERE DisUsr_Cod = " . (int)$row['DisUsr_Cod']);

		$segRest = $tokExp - time();

		return array(
			'success' => true,
			'error' => '',
			'message' => '',
			'token_hash' => $hash,
			'dev_cod' => $acualDev,
			'dis_usr_cod' => (int)$row['DisUsr_Cod'],
			'inv_dis_cod' => (int)$row['InvDis_Cod'],
			'mac' => isset($row['mac_address']) ? $row['mac_address'] : (isset($row['DisUsr_Mac']) ? $row['DisUsr_Mac'] : ''),
			'dis_nom' => isset($row['InvDis_Nom']) ? $row['InvDis_Nom'] : (isset($row['DisUsr_Nom']) ? $row['DisUsr_Nom'] : ''),
			'tipo' => isset($row['InvDis_Tipo']) ? $row['InvDis_Tipo'] : '',
			'expires_at' => date('Y-m-d H:i:s', $tokExp),
			'expires_in' => max(0, $segRest),
			'about_to_expire' => ($segRest < 14400)
		);
	}

	/**
	 * Emite un par access/refresh token para un navegador. Si el navegador ya
	 * tiene un vinculo activo (mismo Dev_Cod) se reutiliza la misma fila, lo que
	 * no consume un cupo adicional.
	 *
	 * @param mysqli $con
	 * @param int $usuCod
	 * @param string $devCod
	 * @param int $invDisCod
	 * @param string $invNom
	 * @param string $mac
	 * @return array {success, error?, message?, access_token, refresh_token,
	 *                expires_in, expires_at, dev_cod, dis_usr_cod, inv_dis_cod, mac, dis_nom, token_hash}
	 */
	public static function emitirTokens($con, $usuCod, $devCod, $invDisCod, $invNom = '', $mac = '')
	{
		$usuCod = (int)$usuCod;
		$invDisCod = (int)$invDisCod;
		$devCod = trim((string)$devCod);
		if ($devCod === '') {
			$devCod = self::generarDevCod();
		}
		if ($usuCod <= 0 || $invDisCod <= 0 || !$con) {
			return array('success' => false, 'error' => 'issue_failed', 'message' => 'Datos insuficientes para emitir el token de acceso.');
		}

		$accessRaw = self::generarToken();
		$refreshRaw = self::generarToken();
		$hashA = self::hashToken($accessRaw);
		$hashR = self::hashToken($refreshRaw);
		$expA = date('Y-m-d H:i:s', time() + self::ACCESS_TTL);
		$expR = date('Y-m-d H:i:s', time() + self::REFRESH_TTL);
		$ip = self::detectarIp();
		$ua = self::userAgent();

		$existente = self::buscarDisUsr($con, $usuCod, $devCod);
		if ($existente && !empty($existente['DisUsr_Cod'])) {
			$disUsrCod = (int)$existente['DisUsr_Cod'];
			if ((string)$mac === '' && !empty($existente['DisUsr_Mac'])) {
				$mac = $existente['DisUsr_Mac'];
			}
			if ((string)$invNom === '' && !empty($existente['DisUsr_Nom'])) {
				$invNom = $existente['DisUsr_Nom'];
			}
			$sql = "UPDATE `dispositivos_usuario`
				SET Dev_Cod = '" . self::e($con, $devCod) . "',
				    InvDis_Cod = {$invDisCod},
				    DisUsr_DevCod = '" . self::e($con, $devCod) . "',
				    DisUsr_Mac = '" . self::e($con, $mac) . "',
				    DisUsr_Nom = '" . self::e($con, $invNom) . "',
				    DisUsr_Token = '{$hashA}',
				    DisUsr_TokExp = '{$expA}',
				    DisUsr_Refresh = '{$hashR}',
				    DisUsr_RefExp = '{$expR}',
				    DisUsr_IP = '" . self::e($con, $ip) . "',
				    user_agent = '" . self::e($con, $ua) . "',
				    DisUsr_Est = 'A',
				    DisUsr_FecUA = NOW()
				WHERE DisUsr_Cod = {$disUsrCod}";
		} else {
			$sql = "INSERT INTO `dispositivos_usuario`
				(`Usu_Cod`,`Dev_Cod`,`DisUsr_DevCod`,`InvDis_Cod`,`DisUsr_Nom`,`DisUsr_Mac`,
				 `DisUsr_IP`,`user_agent`,`DisUsr_Token`,`DisUsr_TokExp`,`DisUsr_Refresh`,`DisUsr_RefExp`,
				 `DisUsr_FecR`,`DisUsr_FecUA`,`DisUsr_Est`)
				VALUES
				({$usuCod}, '" . self::e($con, $devCod) . "', '" . self::e($con, $devCod) . "', {$invDisCod},
				 '" . self::e($con, $invNom) . "', '" . self::e($con, $mac) . "',
				 '" . self::e($con, $ip) . "', '" . self::e($con, $ua) . "',
				 '{$hashA}', '{$expA}', '{$hashR}', '{$expR}', NOW(), NOW(), 'A')";
		}

		if (!@mysqli_query($con, $sql)) {
			return array('success' => false, 'error' => 'issue_failed', 'message' => 'No se pudo registrar el token de acceso en el inventario.');
		}

		$disUsrCod = isset($disUsrCod) ? $disUsrCod : (int)@mysqli_insert_id($con);

		return array(
			'success' => true,
			'error' => '',
			'message' => '',
			'access_token' => $accessRaw,
			'refresh_token' => $refreshRaw,
			'expires_in' => self::ACCESS_TTL,
			'expires_at' => $expA,
			'dev_cod' => $devCod,
			'dis_usr_cod' => (int)$disUsrCod,
			'inv_dis_cod' => (int)$invDisCod,
			'mac' => (string)$mac,
			'dis_nom' => (string)$invNom,
			'token_hash' => $hashA
		);
	}

	/**
	 * Rota un par access/refresh token usando el refresh token recibido.
	 *
	 * @param mysqli $con
	 * @param int $usuCod
	 * @param string $refreshToken Token en claro (COOKIE exa_oauth_refresh).
	 * @return array {success, error?, message?, access_token, refresh_token,
	 *                expires_in, expires_at, dev_cod, dis_usr_cod, inv_dis_cod, mac, dis_nom, token_hash}
	 */
	public static function refreshToken($con, $usuCod, $refreshToken)
	{
		$usuCod = (int)$usuCod;
		$hashR = self::hashToken($refreshToken);
		$r = @mysqli_query($con, "SELECT du.*, inv.mac_address, inv.InvDis_Nom
			FROM `dispositivos_usuario` du
			LEFT JOIN `inventario_dispositivos` inv ON du.InvDis_Cod = inv.InvDis_Cod
			WHERE du.DisUsr_Refresh = '{$hashR}' AND du.Usu_Cod = {$usuCod} AND du.DisUsr_Est = 'A'
			LIMIT 1");
		if (!$r) {
			return array('success' => false, 'error' => 'invalid_grant', 'message' => 'El refresh token no es valido.');
		}
		$row = mysqli_fetch_assoc($r);
		mysqli_free_result($r);
		if (!$row) {
			return array('success' => false, 'error' => 'invalid_grant', 'message' => 'El refresh token no existe o fue revocado.');
		}

		$refExp = !empty($row['DisUsr_RefExp']) ? strtotime($row['DisUsr_RefExp']) : 0;
		if ($refExp === false || $refExp <= time()) {
			return array('success' => false, 'error' => 'expired_grant', 'message' => 'El refresh token ha expirado. Vuelva a iniciar sesion.');
		}

		$accessRaw = self::generarToken();
		$refreshRaw = self::generarToken();
		$hashA = self::hashToken($accessRaw);
		$hashR2 = self::hashToken($refreshRaw);
		$expA = date('Y-m-d H:i:s', time() + self::ACCESS_TTL);
		$expR = date('Y-m-d H:i:s', time() + self::REFRESH_TTL);
		$disUsrCod = (int)$row['DisUsr_Cod'];
		$devCod = trim((string)$row['DisUsr_DevCod']);
		if ($devCod === '') {
			$devCod = trim((string)$row['Dev_Cod']);
		}
		$mac = isset($row['mac_address']) ? $row['mac_address'] : (isset($row['DisUsr_Mac']) ? $row['DisUsr_Mac'] : '');
		$nom = isset($row['InvDis_Nom']) ? $row['InvDis_Nom'] : (isset($row['DisUsr_Nom']) ? $row['DisUsr_Nom'] : '');

		@mysqli_query($con, "UPDATE `dispositivos_usuario`
			SET DisUsr_Token = '{$hashA}', DisUsr_TokExp = '{$expA}',
			    DisUsr_Refresh = '{$hashR2}', DisUsr_RefExp = '{$expR}',
			    DisUsr_IP = '" . self::e($con, self::detectarIp()) . "',
			    user_agent = '" . self::e($con, self::userAgent()) . "',
			    DisUsr_FecUA = NOW()
			WHERE DisUsr_Cod = {$disUsrCod}");

		return array(
			'success' => true,
			'error' => '',
			'message' => '',
			'access_token' => $accessRaw,
			'refresh_token' => $refreshRaw,
			'expires_in' => self::ACCESS_TTL,
			'expires_at' => $expA,
			'dev_cod' => $devCod,
			'dis_usr_cod' => $disUsrCod,
			'inv_dis_cod' => (int)$row['InvDis_Cod'],
			'mac' => $mac,
			'dis_nom' => $nom,
			'token_hash' => $hashA
		);
	}

	/**
	 * Revoca un access token por su hash (equivalente de revoke_token).
	 * Invalida tambien el refresh token asociado.
	 *
	 * @param mysqli $con
	 * @param string $tokenHash
	 * @return bool
	 */
	public static function revocarPorHash($con, $tokenHash)
	{
		if (!$con) {
			return false;
		}
		$tokenHash = trim((string)$tokenHash);
		if ($tokenHash === '') {
			return true;
		}
		@mysqli_query($con, "UPDATE `dispositivos_usuario`
			SET DisUsr_TokExp = NOW(), DisUsr_Refresh = NULL, DisUsr_RefExp = NOW(), DisUsr_FecUA = NOW()
			WHERE DisUsr_Token = '" . self::e($con, $tokenHash) . "'");
		return true;
	}

	/**
	 * Revoca todos los tokens de un vinculo navegador (por DisUsr_Cod).
	 * Utilizado por el modulo de inventario (Limpiar Cupo / Revocar Token) y al
	 * quitar/desvincular un dispositivo.
	 *
	 * @param mysqli $con
	 * @param int $disUsrCod
	 * @return bool
	 */
	public static function revocarPorDisUsr($con, $disUsrCod)
	{
		$disUsrCod = (int)$disUsrCod;
		if (!$con || $disUsrCod <= 0) {
			return false;
		}
		@mysqli_query($con, "UPDATE `dispositivos_usuario`
			SET DisUsr_TokExp = NOW(), DisUsr_Refresh = NULL, DisUsr_RefExp = NOW(), DisUsr_FecUA = NOW()
			WHERE DisUsr_Cod = {$disUsrCod}");
		return true;
	}
}
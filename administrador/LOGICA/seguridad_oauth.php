<?php
/**
 * Capa de seguridad OAuth para el control de acceso por dispositivo (MAC).
 *
 * Envuelve la libreria ExaOAuth (Librerias/OAuth/OAuthServer.php) para:
 *  - Asegurar el esquema de dispositivos_usuario (idempotente).
 *  - Leer el identificador de dispositivo y los tokens desde COOKIE/POST/GET.
 *
 * @package administrador.LOGICA
 */

require_once __DIR__ . '/../../Librerias/OAuth/OAuthServer.php';

if (!function_exists('oauth_asegurar_esquema')) {
	/**
	 * Asegura de forma idempotente el esquema OAuth de dispositivos_usuario.
	 *
	 * @param mysqli|null $con
	 * @return bool
	 */
	function oauth_asegurar_esquema($con)
	{
		if (!$con) {
			return false;
		}
		return ExaOAuth::asegurar_esquema($con);
	}
}

if (!function_exists('oauth_conexion_mysqli')) {
	/**
	 * Obtiene el manejador mysqli de un objeto de conexion del framework.
	 *
	 * @param mixed $obBD
	 * @return mysqli|null
	 */
	function oauth_conexion_mysqli($obBD = null)
	{
		if (is_object($obBD) && !empty($obBD->conexion)) {
			return $obBD->conexion;
		}
		return null;
	}
}

if (!function_exists('oauth_leer_dev_cod')) {
	/**
	 * Lee el identificador persistente del dispositivo (Dev_Cod) enviado por el
	 * navegador. Prioridad: COOKIE exa_dev_cod > POST/GET dev_cod.
	 *
	 * @return string
	 */
	function oauth_leer_dev_cod()
	{
		if (isset($_COOKIE[ExaOAuth::cookieDev()])) {
			$v = trim((string)$_COOKIE[ExaOAuth::cookieDev()]);
			if ($v !== '') {
				return $v;
			}
		}
		foreach (array('POST', 'GET') as $m) {
			if (isset($_REQUEST['dev_cod']) && $m === 'POST' && isset($_POST['dev_cod'])) {
				return trim((string)$_POST['dev_cod']);
			}
		}
		if (isset($_POST['dev_cod'])) {
			return trim((string)$_POST['dev_cod']);
		}
		if (isset($_GET['dev_cod'])) {
			return trim((string)$_GET['dev_cod']);
		}
		return '';
	}
}

if (!function_exists('oauth_leer_access_token')) {
	/**
	 * Lee el access token en claro. Prioridad: COOKIE exa_oauth_access > POST oauth_token.
	 *
	 * @return string
	 */
	function oauth_leer_access_token()
	{
		if (isset($_COOKIE[ExaOAuth::cookieAcceso()])) {
			$v = trim((string)$_COOKIE[ExaOAuth::cookieAcceso()]);
			if ($v !== '') {
				return $v;
			}
		}
		if (isset($_POST['oauth_token'])) {
			return trim((string)$_POST['oauth_token']);
		}
		if (isset($_GET['oauth_token'])) {
			return trim((string)$_GET['oauth_token']);
		}
		return '';
	}
}

if (!function_exists('oauth_leer_fingerprint')) {
	/**
	 * Lee la huella digital de navegador enviada por el cliente (fallback de
	 * identificacion de equipo cuando la MAC no es detectable por ARP, p.ej.
	 * acceso remoto/VPN/Internet fuera de la LAN del servidor). Se calcula en
	 * el navegador (exaDeviceFingerprint() en index.php) y llega como campo
	 * device_fp del formulario de login.
	 *
	 * @return string
	 */
	function oauth_leer_fingerprint()
	{
		if (isset($_POST['device_fp'])) {
			return trim((string)$_POST['device_fp']);
		}
		if (isset($_GET['device_fp'])) {
			return trim((string)$_GET['device_fp']);
		}
		return '';
	}
}

if (!function_exists('oauth_leer_refresh_token')) {
	/**
	 * Lee el refresh token en claro desde la COOKIE exa_oauth_refresh.
	 *
	 * @return string
	 */
	function oauth_leer_refresh_token()
	{
		if (isset($_COOKIE[ExaOAuth::cookieRefresh()])) {
			return trim((string)$_COOKIE[ExaOAuth::cookieRefresh()]);
		}
		return '';
	}
}
<?php
/**
 * Clave de acceso al directorio de Auditoria.
 *
 * Segunda barrera (independiente del login y de los perfiles/procesos) que
 * exige una clave especifica por empresa para poder abrir cualquier pantalla
 * de auditoria/FRONT. Solo el Administrador de Sistemas puede definirla o
 * cambiarla (desde Configuracion de monitoreo). Mientras no se configure una
 * clave para la empresa, el acceso no se bloquea.
 *
 * Uso en cada pantalla de auditoria/FRONT (despues de la seguridad normal):
 *   require_once dirname(__FILE__) . '/../LOGICA/aud_log_acceso_directorio.php';
 *   aud_acceso_directorio_gate($audEmpCod);
 *
 * La funcion termina la ejecucion (exit) si debe mostrar el formulario de
 * clave; si el acceso ya fue concedido en la sesion, retorna sin efecto.
 *
 * @package auditoria.LOGICA
 */

require_once dirname(__FILE__) . '/../../DATA/libs/Env.php';

if (!function_exists('aud_acc_password_hash')) {
	/**
	 * Compatibilidad PHP 5.3.7+ / 5.4 (password_hash solo existe desde 5.5).
	 * Usa bcrypt ($2y$) nativo via crypt() cuando no hay password_hash.
	 */
	function aud_acc_password_hash($password)
	{
		$password = (string)$password;
		if (function_exists('password_hash')) {
			return password_hash($password, PASSWORD_DEFAULT);
		}
		if (!defined('CRYPT_BLOWFISH') || !CRYPT_BLOWFISH) {
			return false;
		}
		$bytes = '';
		if (function_exists('openssl_random_pseudo_bytes')) {
			$bytes = openssl_random_pseudo_bytes(16);
		}
		if ($bytes === '' || $bytes === false) {
			for ($i = 0; $i < 16; $i++) {
				$bytes .= chr(mt_rand(0, 255));
			}
		}
		$salt = substr(strtr(base64_encode($bytes), '+', '.'), 0, 22);
		$hash = crypt($password, '$2y$10$' . $salt);
		if (!is_string($hash) || strlen($hash) < 60) {
			return false;
		}
		return $hash;
	}
}

if (!function_exists('aud_acc_password_verify')) {
	function aud_acc_password_verify($password, $hash)
	{
		$password = (string)$password;
		$hash = (string)$hash;
		if ($hash === '') {
			return false;
		}
		if (function_exists('password_verify')) {
			return password_verify($password, $hash);
		}
		$check = crypt($password, $hash);
		if (!is_string($check) || $check === '') {
			return false;
		}
		// Comparacion en tiempo constante (aprox.) para PHP < 5.6
		if (function_exists('hash_equals')) {
			return hash_equals($hash, $check);
		}
		$res = 0;
		$len = strlen($hash);
		if ($len !== strlen($check)) {
			return false;
		}
		for ($i = 0; $i < $len; $i++) {
			$res |= ord($hash[$i]) ^ ord($check[$i]);
		}
		return $res === 0;
	}
}

if (!function_exists('aud_acc_connect')) {
	function aud_acc_connect()
	{
		$host = \Env::get('DB_HOST', '127.0.0.1');
		$user = \Env::get('DB_USERNAME', 'root');
		$pass = \Env::get('DB_PASSWORD', '');
		$port = (int)\Env::get('DB_PORT', 3306);
		if ($pass === null) {
			$pass = '';
		}
		if (function_exists('mysqli_report')) {
			@mysqli_report(MYSQLI_REPORT_OFF);
		}
		$con = @mysqli_init();
		if (!$con) {
			return null;
		}
		if (defined('MYSQLI_OPT_CONNECT_TIMEOUT')) {
			@mysqli_options($con, MYSQLI_OPT_CONNECT_TIMEOUT, 3);
		}
		$ok = @mysqli_real_connect($con, $host, $user, $pass, 'auditoria', $port);
		return $ok ? $con : null;
	}
}

if (!function_exists('aud_acc_ensure_schema')) {
	function aud_acc_ensure_schema($con)
	{
		if (!$con) {
			return;
		}
		@mysqli_query($con, "CREATE TABLE IF NOT EXISTS `auditoria`.`cfg_acceso` (
			`Emp_Cod` INT(11) NOT NULL,
			`Acc_Clave_Hash` VARCHAR(255) NOT NULL,
			`Acc_Est` CHAR(1) NOT NULL DEFAULT 'A',
			`Acc_Fec` DATETIME DEFAULT NULL,
			`Usu_Cod` INT(11) DEFAULT NULL,
			PRIMARY KEY (`Emp_Cod`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	}
}

if (!function_exists('aud_acc_estado')) {
	/**
	 * @return array('configurada' => bool, 'fecha' => string)
	 */
	function aud_acc_estado($con, $emp)
	{
		$emp = (int)$emp;
		$out = array('configurada' => false, 'fecha' => '');
		if (!$con || $emp <= 0) {
			return $out;
		}
		$r = @mysqli_query($con, "SELECT `Acc_Fec` FROM `auditoria`.`cfg_acceso` WHERE `Emp_Cod`={$emp} AND `Acc_Est`='A' LIMIT 1");
		if ($r) {
			$row = mysqli_fetch_assoc($r);
			mysqli_free_result($r);
			if ($row) {
				$out['configurada'] = true;
				$out['fecha'] = isset($row['Acc_Fec']) ? $row['Acc_Fec'] : '';
			}
		}
		return $out;
	}
}

if (!function_exists('aud_acc_set_clave')) {
	function aud_acc_set_clave($con, $emp, $usuSesion, $claveNueva)
	{
		$emp = (int)$emp;
		$claveNueva = trim((string)$claveNueva);
		if (!$con || $emp <= 0) {
			return array('success' => false, 'message' => 'No hay empresa activa.');
		}
		if (strlen($claveNueva) < 4) {
			return array('success' => false, 'message' => 'La clave debe tener al menos 4 caracteres.');
		}
		aud_acc_ensure_schema($con);
		$hash = aud_acc_password_hash($claveNueva);
		if ($hash === false || $hash === null || $hash === '') {
			return array('success' => false, 'message' => 'No se pudo generar el hash de la clave (verifique soporte bcrypt en el servidor).');
		}
		$hashEsc = mysqli_real_escape_string($con, $hash);
		$fec = date('Y-m-d H:i:s');
		$usuSesion = (int)$usuSesion;
		$sql = "INSERT INTO `auditoria`.`cfg_acceso` (`Emp_Cod`,`Acc_Clave_Hash`,`Acc_Est`,`Acc_Fec`,`Usu_Cod`)
			VALUES ({$emp},'{$hashEsc}','A','{$fec}',{$usuSesion})
			ON DUPLICATE KEY UPDATE `Acc_Clave_Hash`='{$hashEsc}', `Acc_Est`='A', `Acc_Fec`='{$fec}', `Usu_Cod`={$usuSesion}";
		$ok = @mysqli_query($con, $sql);
		if (!$ok) {
			$err = function_exists('mysqli_error') ? mysqli_error($con) : '';
			$msg = 'No se pudo guardar la clave.';
			if ($err !== '') {
				$msg .= ' ' . $err;
			}
			return array('success' => false, 'message' => $msg);
		}
		return array('success' => true, 'message' => 'Clave de acceso al directorio de auditoria guardada.');
	}
}

if (!function_exists('aud_acc_desactivar')) {
	function aud_acc_desactivar($con, $emp)
	{
		$emp = (int)$emp;
		if (!$con || $emp <= 0) {
			return array('success' => false, 'message' => 'No hay empresa activa.');
		}
		$ok = @mysqli_query($con, "UPDATE `auditoria`.`cfg_acceso` SET `Acc_Est`='I' WHERE `Emp_Cod`={$emp}");
		return array('success' => (bool)$ok, 'message' => $ok ? 'Se desactivo la clave de acceso al directorio.' : 'No se pudo desactivar la clave.');
	}
}

if (!function_exists('aud_acc_verificar')) {
	function aud_acc_verificar($con, $emp, $claveIngresada)
	{
		$emp = (int)$emp;
		if (!$con || $emp <= 0) {
			return false;
		}
		$r = @mysqli_query($con, "SELECT `Acc_Clave_Hash` FROM `auditoria`.`cfg_acceso` WHERE `Emp_Cod`={$emp} AND `Acc_Est`='A' LIMIT 1");
		if (!$r) {
			return false;
		}
		$row = mysqli_fetch_assoc($r);
		mysqli_free_result($r);
		if (!$row || empty($row['Acc_Clave_Hash'])) {
			return false;
		}
		return aud_acc_password_verify((string)$claveIngresada, (string)$row['Acc_Clave_Hash']);
	}
}

if (!function_exists('aud_acc_sesion_key')) {
	function aud_acc_sesion_key($emp)
	{
		return 'aud_acceso_ok_' . (int)$emp;
	}
}

if (!function_exists('aud_acc_html_form')) {
	function aud_acc_html_form($error = '')
	{
		$errHtml = '';
		if ($error !== '') {
			$errHtml = '<div class="alert alert-danger" style="font-size:12px;">' . htmlspecialchars($error) . '</div>';
		}
		return '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8" />
<title>Acceso restringido - Auditoria</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<style>
body{background:#eef2f7;font-family:Arial,Helvetica,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
.aud-acc-box{background:#fff;border:1px solid #d0dbe5;border-radius:6px;box-shadow:0 2px 10px rgba(15,23,42,0.12);padding:26px 28px;max-width:380px;width:92%;}
.aud-acc-box h3{margin:0 0 6px;color:#1e3a5f;font-size:16px;}
.aud-acc-box p{font-size:12px;color:#64748b;margin:0 0 14px;}
.aud-acc-box input[type=password]{width:100%;padding:8px 10px;font-size:14px;border:1px solid #cbd5e1;border-radius:4px;box-sizing:border-box;margin-bottom:10px;}
.aud-acc-box button{width:100%;padding:9px 10px;background:#254463;color:#fff;border:0;border-radius:4px;font-weight:700;font-size:13px;cursor:pointer;}
.aud-acc-box button:hover{background:#1c3450;}
</style></head><body>
<div class="aud-acc-box">
<h3><span class="glyphicon glyphicon-lock"></span> Acceso restringido</h3>
<p>Este directorio de Auditoria requiere una clave adicional configurada por el Administrador de Sistemas.</p>' . $errHtml . '
<form method="post" action="">
<input type="password" name="aud_clave_acceso" placeholder="Clave de acceso" autofocus="autofocus" required="required" />
<button type="submit"><span class="glyphicon glyphicon-ok"></span> Ingresar</button>
</form>
</div>
</body></html>';
	}
}

if (!function_exists('aud_acceso_directorio_gate')) {
	/**
	 * Punto de entrada: bloquea la pantalla actual (exit) si el directorio
	 * de auditoria requiere clave y aun no fue concedida en esta sesion.
	 */
	function aud_acceso_directorio_gate($empCod)
	{
		if (session_id() === '' && !headers_sent()) {
			@session_start();
		}
		$emp = (int)$empCod;
		if ($emp <= 0) {
			return;
		}
		$key = aud_acc_sesion_key($emp);
		if (!empty($_SESSION[$key])) {
			return;
		}
		$con = aud_acc_connect();
		if (!$con) {
			// Sin conexion no se puede validar la clave: no bloquear el acceso.
			return;
		}
		aud_acc_ensure_schema($con);
		$estado = aud_acc_estado($con, $emp);
		if (empty($estado['configurada'])) {
			@mysqli_close($con);
			return;
		}

		$intentosKey = 'aud_acceso_intentos_' . $emp;
		if (isset($_POST['aud_clave_acceso'])) {
			$intentos = isset($_SESSION[$intentosKey]) ? (int)$_SESSION[$intentosKey] : 0;
			if ($intentos >= 3) {
				usleep(700000);
			}
			if (aud_acc_verificar($con, $emp, $_POST['aud_clave_acceso'])) {
				$_SESSION[$key] = true;
				unset($_SESSION[$intentosKey]);
				@mysqli_close($con);
				$self = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '';
				$qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? ('?' . $_SERVER['QUERY_STRING']) : '';
				if (!headers_sent()) {
					header('Location: ' . $self . $qs);
					exit();
				}
				return;
			}
			$_SESSION[$intentosKey] = $intentos + 1;
			@mysqli_close($con);
			echo aud_acc_html_form('Clave incorrecta. Intente nuevamente.');
			exit();
		}

		@mysqli_close($con);
		echo aud_acc_html_form();
		exit();
	}
}
?>

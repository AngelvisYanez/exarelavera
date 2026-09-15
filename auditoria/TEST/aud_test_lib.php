<?php
/**
 * Soporte comun para pruebas de auditoria (PHP 5.3).
 *
 * @package auditoria.TEST
 */

if (!ini_get('date.timezone')) {
	@date_default_timezone_set('America/Guayaquil');
}

if (!class_exists('DebugBar')) {
	class DebugBar {
		public static function startQueryMeasure() {}
		public static function addQuery($sql, $data) {}
		public static function addException($e) {}
		public static function addTransactionEvent($name, $data) {}
	}
}
if (!class_exists('Debugbar')) {
	class Debugbar {
		public static function addException($e) {}
	}
}

require_once dirname(__FILE__) . '/../LOGICA/aud_log_queue.php';

function aud_test_putenv($key, $value)
{
	putenv($key . '=' . $value);
}

function aud_test_sqls()
{
	return array(
		'manifiesto_i' => "INSERT INTO manifiesto (Man_Num, Man_Fec, Man_Pes, Man_Pun, Man_Tip, Man_Est) VALUES (1001, '2026-08-14 08:30:00', 12500, 3.00, 'P', 'A')",
		'manifiesto_u' => "UPDATE manifiesto SET Man_Pes=13200, Man_Pun=3.50 WHERE Man_Cod=1",
		'turnos_cab_i' => "INSERT INTO manifiesto_turnos_cab (Tur_Fei, Tur_Fef, Tur_Est) VALUES ('2026-08-14', '2026-08-20', 'A')",
		'turnos_det_i' => "INSERT INTO manifiesto_turnos_det (Tud_Fec, Tud_Hin, Tud_Hfi, Tud_Cup, Tud_Est) VALUES ('2026-08-14', '07:00:00', '12:00:00', 15, 'A')",
		'turnos_det_u' => "UPDATE manifiesto_turnos_det SET Tud_Cup=12 WHERE Tud_Cod=1",
		'visitante_i' => "INSERT INTO manifiesto_visitante (MVis_Est, MVis_Obs, Emp_Cod) VALUES ('A', 'INGRESO DEMO', 1)",
		'visitante_u' => "UPDATE manifiesto_visitante SET MVis_Est='I' WHERE MVis_Cod=1",
		'evento_i' => "INSERT INTO manifiesto_evento (Man_ENom, Man_Vig, Man_EEst) VALUES ('Jornada Demo', 'S', 'A')",
		'comprobante_i' => "INSERT INTO comprobantes (Com_Num, Com_Val, Com_Con) VALUES ('001-DEMO', 150.00, 'COMPROBANTE')",
		'asiento_d' => "DELETE FROM asientos WHERE Asi_Cod=9",
		'venta_i' => "INSERT INTO ventas (Tic_Cod, Cli_Cod, Caj_Cod, Vnd_Cod, Vet_Num, Vet_Des, Vet_Hor) VALUES (1, 1, 1, 1, '001-001-000000001', '2026-08-17', '08:30:00')",
		'venta_det_i' => "INSERT INTO ventas_det SET Vet_Cod=1, Pro_Cod=1, Vet_Can=2, Vet_Pru=10.00, Vet_Imp=20.00"
	);
}

function aud_test_cycle_sql($i)
{
	$sqls = array_values(aud_test_sqls());
	$n = count($sqls);
	if ($n < 1) {
		return "INSERT INTO manifiesto (Man_Est) VALUES ('A')";
	}
	return $sqls[$i % $n];
}

function aud_assert($cond, $msg)
{
	if (!$cond) {
		throw new Exception($msg);
	}
	echo "  OK  " . $msg . "\n";
}

function aud_ms($start)
{
	return round((microtime(true) - $start) * 1000, 2);
}

function aud_php_bin()
{
	if (defined('PHP_BINARY') && PHP_BINARY) {
		return PHP_BINARY;
	}
	return 'php';
}

function aud_session_user($usuCod, $empCod = 999900)
{
	if (function_exists('session_id') && session_id() === '') {
		@session_start();
	}
	$_SESSION['Ses_Usu_Cod'] = (int)$usuCod;
	$_SESSION['Ses_Emp_Cod'] = (int)$empCod;
	$_SESSION['Ses_Suc_Cod'] = 1;
	$_SESSION['Ses_Dat_Dis'] = 'exa';
	$_SESSION['Ses_Dat_Aut'] = 'auditoria';
	$GLOBALS['Ses_Usu_Cod'] = (int)$usuCod;
	$GLOBALS['Ses_Emp_Cod'] = (int)$empCod;
	$GLOBALS['Ses_Suc_Cod'] = 1;
	$GLOBALS['Ses_Dat_Dis'] = 'exa';
	$GLOBALS['Ses_Dat_Aut'] = 'auditoria';
}

function aud_db_connect()
{
	$host = Env::get('DB_HOST', '127.0.0.1');
	$user = Env::get('DB_USERNAME', 'root');
	$pass = Env::get('DB_PASSWORD', '');
	$port = (int)Env::get('DB_PORT', 3306);
	if ($pass === null) {
		$pass = '';
	}
	if (function_exists('mysqli_report')) {
		mysqli_report(MYSQLI_REPORT_OFF);
	}
	$con = @mysqli_connect($host, $user, $pass, 'auditoria', $port);
	if ($con) {
		return $con;
	}
	$GLOBALS['aud_db_connect_error'] = function_exists('mysqli_connect_error') ? mysqli_connect_error() : 'sin conexion';
	return null;
}

<?php 
/* 
Alias:	-
Descripci�n: Cerrar la sesi�n del sistema y registrar cierre en auditor�a
Fecha de actualizaci�n:	2026-09-05
*/

if (session_id() === '') session_start();

/* Asegurar la clase DebugBar (stub) cuando se invoca sin el bootstrap del sistema */
if (!class_exists('DebugBar', false)) {
	require_once dirname(__FILE__) . '/../../Librerias/config.php/debugbar.php';
}

$sesCod = !empty($_SESSION['Ses_Ses_Cod']) ? (int)$_SESSION['Ses_Ses_Cod'] : 0;
$usuCod = !empty($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;

if ($sesCod > 0 && $usuCod > 0) {
	try {
		$archAud = dirname(__FILE__) . '/../../auditoria/LOGICA/aud_log_auditoria.php';
		if (is_file($archAud) && !class_exists('Class_Log_Datos_Aud')) {
			require_once $archAud;
		}
		if (class_exists('Class_Log_Datos_Aud')) {
			$objAud = new Class_Log_Datos_Aud();
			$objAud->GuardarCierreSesion($sesCod, date('Y-m-d H:i:s'), $usuCod);
		}
	} catch (Exception $e) {
		// No bloquear el logout en caso de error de BD (PHP 5.6+)
	} catch (Throwable $e) {
		// No bloquear el logout en caso de error fatal (PHP 7+)
	}
}

@session_unset();
@session_destroy();

$motivo = isset($_GET['motivo']) ? '?motivo=' . urlencode($_GET['motivo']) : '';
header('Location: ../../index.php' . $motivo);
exit;
?>
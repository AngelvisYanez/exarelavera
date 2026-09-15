<?php 
/* 
Alias:	-
Descripción: Cerrar la sesión del sistema y registrar cierre en auditoría
Fecha de actualización:	2026-09-05
*/

session_start();

$sesCod = !empty($_SESSION['Ses_Ses_Cod']) ? (int)$_SESSION['Ses_Ses_Cod'] : 0;
$usuCod = !empty($_SESSION['Ses_Usu_Cod']) ? (int)$_SESSION['Ses_Usu_Cod'] : 0;

if ($sesCod > 0 && $usuCod > 0) {
	try {
		require_once dirname(__FILE__) . '/../../auditoria/LOGICA/aud_log_auditoria.php';
		if (class_exists('Class_Log_Datos_Aud')) {
			$objAud = new Class_Log_Datos_Aud();
			$objAud->GuardarCierreSesion($sesCod, date('Y-m-d H:i:s'), $usuCod);
		}
	} catch (Throwable $e) {
		// No bloquear el logout en caso de error de BD
	}
}

session_unset();
session_destroy();

$motivo = isset($_GET['motivo']) ? '?motivo=' . urlencode($_GET['motivo']) : '';
header('Location: ../../index.php' . $motivo);
?>

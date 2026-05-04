<?php
/**
 * Obtener lista de usuarios para el combo
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_asignacion_dispositivos.php');

$obBD_conexion = new Class_Log_Conexion_Asignacion($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Asignacion;

$data = $obBD_con1->getArrayConsulta(3, null, $obBD_conexion);

header('Content-Type: application/json');
echo json_encode($data);
?>

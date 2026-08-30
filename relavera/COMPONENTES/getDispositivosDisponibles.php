<?php
/**
 * Obtener dispositivos disponibles para asignar a un usuario
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_asignacion_dispositivos.php');

$usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : 0;

$obBD_conexion = new Class_Log_Conexion_Asignacion($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Asignacion;

$data = array();
if ($usuario_id > 0) {
    $data = $obBD_con1->getArrayConsulta(2, array('usuario_id' => $usuario_id), $obBD_conexion);
}

header('Content-Type: application/json');
echo json_encode($data);
?>

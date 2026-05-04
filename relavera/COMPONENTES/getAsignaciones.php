<?php
/**
 * Obtener listado de asignaciones de un usuario para jqGrid
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_asignacion_dispositivos.php');

$usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : 0;

$obBD_conexion = new Class_Log_Conexion_Asignacion($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Asignacion;

$data_rows = array();
if ($usuario_id > 0) {
    $data_rows = $obBD_con1->getArrayConsulta(1, array('usuario_id' => $usuario_id), $obBD_conexion);
}

$responce = new stdClass();
$responce->page = 1;
$responce->total = 1;
$responce->records = count($data_rows);
$responce->rows = is_array($data_rows) ? $data_rows : array();

header('Content-Type: application/json');
echo json_encode($responce);
?>

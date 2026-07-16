<?php
/**
 * Endpoint para validar si una dirección MAC ya existe
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_inventario_dispositivos.php');

$obBD_conexion = new Class_Log_Conexion_Inventario($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Inventario;

$mac = isset($_POST['mac_address']) ? $_POST['mac_address'] : '';
$id = isset($_POST['id']) ? $_POST['id'] : 0;

$resp = array('success' => false, 'existe' => false);

if (!empty($mac)) {
    $params = array('mac_address' => $mac, 'id' => $id);
    $data = $obBD_con1->getRowConsulta(2, $params, $obBD_conexion);
    
    $resp['success'] = true;
    $resp['existe'] = ($data['total'] > 0);
}

header('Content-Type: application/json');
echo json_encode($resp);
?>

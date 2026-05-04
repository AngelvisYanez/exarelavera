<?php
/**
 * Guardar o quitar asignación de dispositivo
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_asignacion_dispositivos.php');

$action = isset($_POST['action']) ? $_POST['action'] : '';
$usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
$dispositivos = isset($_POST['dispositivos']) ? $_POST['dispositivos'] : array(); // Array de IDs de inventario
$id_asignacion = isset($_POST['id_asignacion']) ? intval($_POST['id_asignacion']) : 0; // Para quitar una sola

$obBD_conexion = new Class_Log_Conexion_Asignacion($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Asignacion;

$resp = array('success' => false, 'message' => '');

try {
    if ($action == 'assign') {
        if ($usuario_id <= 0 || empty($dispositivos)) {
            $resp['message'] = 'Faltan datos para la asignación';
        } else {
            foreach ($dispositivos as $inv_id) {
                $params = array('usuario_id' => $usuario_id, 'inventario_id' => $inv_id);
                // Verificar si ya existe registro (aunque esté inactivo)
                $check = $obBD_con1->getRowConsulta(7, $params, $obBD_conexion);
                
                if ($check) {
                    // Si ya existe, reactivar
                    $obBD_con1->operacionobBD(6, $params, $obBD_conexion);
                } else {
                    // Si no existe, insertar
                    $obBD_con1->operacionobBD(4, $params, $obBD_conexion);
                }
            }
            $resp['success'] = true;
            $resp['message'] = 'Dispositivos asignados correctamente';
        }
    } else if ($action == 'unassign') {
        if ($id_asignacion <= 0) {
            $resp['message'] = 'ID de asignación no válido';
        } else {
            $obBD_con1->operacionobBD(5, array('id' => $id_asignacion), $obBD_conexion);
            $resp['success'] = true;
            $resp['message'] = 'Asignación quitada correctamente';
        }
    } else if ($action == 'unlink_browser') {
        $vinculado_id = isset($_POST['vinculado_id']) ? intval($_POST['vinculado_id']) : 0;
        if ($vinculado_id <= 0) {
            $resp['message'] = 'ID de vínculo no válido';
        } else {
            $obBD_con1->operacionobBD(8, array('vinculado_id' => $vinculado_id), $obBD_conexion);
            $resp['success'] = true;
            $resp['message'] = 'Navegador desvinculado correctamente. El cupo ahora está libre.';
        }
    } else {
        $resp['message'] = 'Acción no reconocida';
    }
} catch (Exception $e) {
    $resp['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($resp);
?>

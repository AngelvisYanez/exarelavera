<?php
/**
 * Guardar dispositivos en el inventario (Optimizado con inserción masiva agrupada)
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_inventario_dispositivos.php');

$obBD_conexion = new Class_Log_Conexion_Inventario($Ses_Dat_Dis);
$obBD_con1 = new Class_Log_Datos_Inventario;

$resp = array('success' => false, 'message' => '');
$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    if ($action == 'bulk_save') {
        $dispositivos = isset($_POST['dispositivos']) ? $_POST['dispositivos'] : array();
        if (empty($dispositivos)) {
            $resp['message'] = 'No se enviaron dispositivos para guardar';
        } else {
            // 1. Recopilar todas las MACs para validación masiva
            $macs_a_validar = array();
            foreach ($dispositivos as $disp) {
                $macs_a_validar[] = strtoupper(trim($disp['mac_address']));
            }
            
            // 2. Consultar cuáles ya existen en la BD
            $existentes_raw = $obBD_con1->getArrayConsulta(7, array('lista_macs' => $macs_a_validar), $obBD_conexion);
            $macs_existentes = array();
            if (is_array($existentes_raw)) {
                foreach ($existentes_raw as $row) {
                    $macs_existentes[] = $row['mac_address'];
                }
            }
            
            // 3. Filtrar y preparar filas para la inserción agrupada
            $filas_para_insertar = array();
            $ignorados = array();
            
            foreach ($dispositivos as $disp) {
                $mac = strtoupper(trim($disp['mac_address']));
                if (in_array($mac, $macs_existentes)) {
                    $ignorados[] = $mac;
                    continue;
                }
                
                $nombre = addslashes(trim($disp['nombre_equipo']));
                $desc = addslashes(trim($disp['descripcion']));
                $tipo = addslashes($disp['tipo_equipo']);
                $estado = addslashes($disp['estado']);
                
                $filas_para_insertar[] = "('$mac', '$nombre', '$desc', '$tipo', '$estado', NOW())";
            }
            
            if (!empty($filas_para_insertar)) {
                // 4. Ejecutar UN SOLO insert con todos los valores
                $obBD_con1->operacionobBD(8, array('filas' => $filas_para_insertar), $obBD_conexion);
                
                if ($obBD_con1->Error == 0) {
                    $count = count($filas_para_insertar);
                    $resp['success'] = true;
                    $resp['message'] = "Se guardaron $count dispositivos correctamente.";
                    if (!empty($ignorados)) {
                        $resp['message'] .= " Se ignoraron " . count($ignorados) . " por estar duplicados (" . implode(", ", $ignorados) . ").";
                    }
                } else {
                    $resp['message'] = 'Error en la inserción masiva: ' . $obBD_con1->MsgError;
                }
            } else {
                $resp['success'] = true;
                $resp['message'] = 'No se insertaron nuevos registros porque todos los enviados ya existen.';
            }
        }
    } else if ($action == 'change_status') {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $estado = isset($_POST['estado']) ? $_POST['estado'] : 'A';
        $params = array('id' => $id, 'estado' => $estado);
        $obBD_con1->operacionobBD(5, $params, $obBD_conexion);
        if ($obBD_con1->Error == 0) {
            $resp['success'] = true;
            $resp['message'] = 'Estado actualizado correctamente';
        } else {
            $resp['message'] = 'Error al actualizar estado: ' . $obBD_con1->MsgError;
        }
    } else {
        // Guardado Individual (Edición)
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $mac = isset($_POST['mac_address']) ? strtoupper(trim($_POST['mac_address'])) : '';
        $nombre = isset($_POST['nombre_equipo']) ? trim($_POST['nombre_equipo']) : '';
        $desc = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $estado = isset($_POST['estado']) ? $_POST['estado'] : 'A';
        
        $params = array(
            'id' => $id,
            'mac_address' => $mac,
            'nombre_equipo' => $nombre,
            'descripcion' => $desc,
            'tipo_equipo' => isset($_POST['tipo_equipo']) ? $_POST['tipo_equipo'] : 'PC',
            'estado' => $estado
        );
        
        if ($id > 0) {
            $obBD_con1->operacionobBD(4, $params, $obBD_conexion);
            $resp['message'] = 'Dispositivo actualizado correctamente';
        } else {
            // Verificar duplicado individual
            $check = $obBD_con1->getRowConsulta(2, array('mac_address' => $mac, 'id' => 0), $obBD_conexion);
            if ($check['total'] > 0) {
                $resp['message'] = 'La dirección MAC ya existe';
            } else {
                $obBD_con1->operacionobBD(3, $params, $obBD_conexion);
                $resp['message'] = 'Dispositivo guardado correctamente';
            }
        }
        
        if ($obBD_con1->Error == 0 && !isset($resp['success'])) {
            $resp['success'] = true;
        } else if ($obBD_con1->Error != 0) {
            $resp['success'] = false;
            $resp['message'] = 'Error al guardar: ' . $obBD_con1->MsgError;
        }
    }
} catch (Exception $e) {
    $resp['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($resp);
?>

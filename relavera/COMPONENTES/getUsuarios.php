<?php
/**
 * Obtener lista de usuarios para el combo
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/log_asignacion_dispositivos.php');

$obBD_conexion = new Class_Log_Conexion_Asignacion($Ses_Dat_Dis);
// Forzar UTF-8 para que las tildes se lean correctamente
if (method_exists($obBD_conexion, 'query')) {
    $obBD_conexion->query("SET NAMES 'utf8'");
}

$obBD_con1 = new Class_Log_Datos_Asignacion;
$data = $obBD_con1->getArrayConsulta(3, null, $obBD_conexion);

// Validar codificación de nombres para el JSON
if (is_array($data)) {
    foreach ($data as &$row) {
        if (isset($row['nombre']) && $row['nombre'] != "") {
            // Si el JSON falla con caracteres especiales, aseguramos UTF-8
            if (function_exists('mb_detect_encoding')) {
                if (!mb_detect_encoding($row['nombre'], 'UTF-8', true)) {
                    $row['nombre'] = utf8_encode($row['nombre']);
                }
            } else {
                // Fallback para servidores sin mbstring
                $row['nombre'] = utf8_encode($row['nombre']);
            }
        }
    }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
?>

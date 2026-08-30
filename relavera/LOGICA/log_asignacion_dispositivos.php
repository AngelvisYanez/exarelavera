<?php
/**
 * Logica de asignación de dispositivos
 */

require_once("asignacion_dispositivos.sql.php");

class Class_Log_Conexion_Asignacion extends MysqlConexion{ }

class Class_Log_Datos_Asignacion extends MysqlDatos{
    function __construct(){
        $this->setSentencias('sentencias_asignacion_dispositivos');
    }
}
?>

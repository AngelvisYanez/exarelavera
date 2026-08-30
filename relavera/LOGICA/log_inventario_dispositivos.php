<?php
/**
 * Logica de inventario de dispositivos
 * 
 * @author Antigravity
 * @version 1.0
 * @package relavera.LOGICA
 */

require_once("inventario_dispositivos.sql.php");

class Class_Log_Conexion_Inventario extends MysqlConexion{ }

class Class_Log_Datos_Inventario extends MysqlDatos{
    function __construct(){
        $this->setSentencias('sentencias_inventario_dispositivos');
    }
}
?>

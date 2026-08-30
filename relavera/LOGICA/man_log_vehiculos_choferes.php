<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("man_sql_vehiculos_choferes.php");

/**
 * Conexión y acceso a base de datos para vehículos y choferes en Relavera
 * @author Sistema EXA
 * @version 1.0
 */
class Class_Log_Conexion_Vehiculos_Choferes extends MysqlConexion{

}

class Class_Log_Datos_Vehiculos_Choferes extends MysqlDatos{
    function __construct() {
        $this->setSentencias('sentencias_vehiculos_choferes');
    } 
}

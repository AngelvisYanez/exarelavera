<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("man_sql_alimentacion.php");

/**
 * Conexión y acceso a base de datos para control de alimentación de choferes en Relavera
 * @author Sistema EXA
 * @version 1.0
 */
class Class_Log_Conexion_Alimentacion extends MysqlConexion{

}

class Class_Log_Datos_Alimentacion extends MysqlDatos{
    function __construct() {
        $this->setSentencias('sentencias_alimentacion');
    } 
}

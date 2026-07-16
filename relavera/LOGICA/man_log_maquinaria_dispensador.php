<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("man_sql_maquinaria_dispensador.php");

class Class_Log_Conexion_Maquinaria_Dispensador extends MysqlConexion{
}

class Class_Log_Datos_Maquinaria_Dispensador extends MysqlDatos{
    function __construct() {
        $this->setSentencias('sentencias_maquinaria_dispensador');
    } 
}
?>

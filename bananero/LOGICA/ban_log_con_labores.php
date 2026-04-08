<?php
/**
 * Lógica para Consulta de labores del personal
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de creación 2019-02-26
 *
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("ban_log_sql_labores.php");
class Class_Log_Conexion_Lab extends MysqlConexion {

}
class Class_Log_Datos_Con_Lab extends MysqlDatosContab{
    function __construct(){
    	$this->setSentencias('sentencias_labores');
   }

}
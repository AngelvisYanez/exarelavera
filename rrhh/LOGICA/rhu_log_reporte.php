<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("rhu_sql_reporte.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_rrhh_reporte extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_rrhh_reporte extends MysqlDatos{
	
    function __construct() {
        $this->setSentencias('sentencias_rrhh');
    }
}//Fin de clase Class_Log_Conexion


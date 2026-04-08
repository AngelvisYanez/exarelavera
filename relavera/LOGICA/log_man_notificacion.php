<?php
require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("sql_man_notificacion.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_notificacion extends MysqlConexion {}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_notificacion extends MysqlDatosContab {
    function __construct() {
        $this->setSentencias('sentencias_man_notificacion');
    }

   
}//Fin de clase Class_Log_Conexion

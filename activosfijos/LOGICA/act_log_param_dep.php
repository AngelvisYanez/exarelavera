<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("act_sql_param_dep.php");

class Class_Log_Conexion_act_dep extends MysqlConexion{}

class Class_Log_Datos_act_dep extends MysqlDatos{
	
    function __construct(){
        $this->setSentencias('sentencias_act');
    }
}

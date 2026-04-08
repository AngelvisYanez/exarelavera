<?php
/**
 * Created by PhpStorm.
 * User: jorge
 * Date: 4/13/2018
 * Time: 3:11 PM
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("adm_sql_configs.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Config extends MysqlConexion{ }

/* Clase para acceder a los datos */
class Class_Log_Datos_Config extends MysqlDatos{
    function __construct() {
        $this->setSentencias('sentencias_configs');
    }
}
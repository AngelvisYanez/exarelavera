<?php
/**
 * Created by PhpStorm.
 * User: jorge
 * Date: 4/13/2018
 * Time: 3:11 PM
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("adm_sql_clavesaccesos.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_ClavesAccesos extends MysqlConexion{ }

/* Clase para acceder a los datos */
class Class_Log_Datos_ClavesAccesos extends MysqlDatos{
    function __construct() {
        $this->setSentencias('sentencias_clavesaccesos');
    }
}
<?php

require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("man_sql_datos_choferes_vehiculos.php");

/**
 * Conexión y lógica de negocio para Datos de Choferes y Vehículos en Relavera
 * @author Sistema EXA
 * @version 1.0
 */
class Class_Log_Conexion_Datos_Choferes_Vehiculos extends MysqlConexion { }

class Class_Log_Datos_Choferes_Vehiculos extends MysqlDatos {
    function __construct() {
        $this->setSentencias('sentencias_datos_choferes_vehiculos');
    }
}

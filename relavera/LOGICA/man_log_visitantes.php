<?php

require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("man_sql_visitantes.php");

/**
 * Conexión y lógica de negocio para Datos de Choferes y Vehículos en Relavera
 * @author Sistema EXA
 * @version 1.0
 */
class Class_Log_Conexion_Visitantes extends MysqlConexion { }

class Class_Log_Visitantes extends MysqlDatos {
    function __construct() {
        $this->setSentencias('sentencias_visitantes');
    }
}

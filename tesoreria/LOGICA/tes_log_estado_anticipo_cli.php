<?php

/**
 * L�gica de anticipos clientes
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de creaci�n: 14/06/2019
 *
 */

require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once($APP_REAL_PATH . "/tesoreria/LOGICA/tes_sql_estado_anticipo_cli.php");
class Class_Log_Conexion_Ant_Cli extends MysqlConexion {}
class Class_Log_Datos_Ant_Cli extends MysqlDatosContab
{
    function __construct()
    {
        $this->setSentencias('sentencias_anticipo_cli');
    }
}

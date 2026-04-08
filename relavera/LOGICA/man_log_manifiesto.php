<?php
/**
 * Logica de anticipos
 * @author Cesar Bermeo
 * @version  1.0
 * Fecha de creacion: 16/04/2019
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once($APP_REAL_PATH."/relavera/LOGICA/man_sql_manifiesto.php");

class Class_Log_Conexion_Mani extends MysqlConexion{}
class Class_Log_Datos_Mani extends MysqlDatosContab{
    function __construct(){
        $this->setSentencias('sentencias_manifiesto_cli');
    }
}
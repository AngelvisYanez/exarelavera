<?php

/**
 * Logica de manifiesto anticipos
 *
 * @author Exa-Contable
 * @version 1.0
 * @package manifiesto.LOGICA
*/

// require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("sql_man_ant_1.0.php");

class Class_Log_Conexion_Manifiesto extends MysqlConexion{ }

class Class_Log_Datos_Manifiesto extends MysqlDatos{
	function __construct(){
        $this->setSentencias('sentencias_manifiesto');
    }
}

?>
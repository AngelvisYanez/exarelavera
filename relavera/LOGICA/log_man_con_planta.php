<?php

/**
 * Logica de consulta de planta
 *
 * @author Exa-Contable
 * @version 1.0
 * @package relavera.LOGICA
*/

require_once("sql_man_con_planta.php");

class Class_Log_Conexion_Consulta_Planta extends MysqlConexion{ }

class Class_Log_Datos_Consulta_Planta extends MysqlDatos{
	function __construct(){
        $this->setSentencias('sentencias_consulta_planta');
    }
}
?>

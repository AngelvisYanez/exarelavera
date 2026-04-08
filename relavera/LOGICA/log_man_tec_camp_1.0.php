<?php

/**
 * Logica de manifiesto técnico
 *
 * @author Exa-Contable
 * @version 1.0
 * @package manifiesto.LOGICA
*/

require_once("sql_man_tec_camp_1.0.php");

class Class_Log_Conexion_Manifiesto_Tecnico extends MysqlConexion{ }

class Class_Log_Datos_Manifiesto_Tecnico extends MysqlDatos{
	function __construct(){
        $this->setSentencias('sentencias_manif_tec_camp');
    }
}
?>
<?php

/**
 * Logica de estado de cuenta
 *
 * @author Exa-Contable
 * @version 1.0
 * @package relavera.LOGICA
 */

require_once("sql_man_est_cuenta_1.0.php");

class Class_Log_Conexion_Estado_Cuenta extends MysqlConexion{ }

class Class_Log_Datos_Estado_Cuenta extends MysqlDatos{
	function __construct(){
        $this->setSentencias('sentencias_estado_cuenta');
    }
}

?>

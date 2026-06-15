<?php

/**
 * Logica de contratos de plantas (manifiesto_contratos)
 *
 * @author Exa-Contable
 * @version 1.0
 * @package relavera.LOGICA
 */

require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("sql_man_contratos.php");

class Class_Log_Conexion_Contratos_Planta extends MysqlConexion { }

class Class_Log_Datos_Contratos_Planta extends MysqlDatosContab {
    function __construct() {
        $this->setSentencias('sentencias_contratos_planta');
    }
}

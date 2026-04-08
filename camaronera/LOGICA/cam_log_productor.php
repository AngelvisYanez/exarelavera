<?php

/**
 * Logica para crear una negociacion
 * @author Wilson Belduma
 * @version 1.0
 * Fecha de creacion: 2025-03-01
 *
 * @package camaronera.LOGICA
 */

require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("cam_sql_camaronera.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 */

class Class_Log_Conexion_Productor extends MysqlDatosContab {

    function __construct() {
        $this->setSentencias('sentencias_camaronera');
    }
}//Fin de clase Class_Log_Conexion
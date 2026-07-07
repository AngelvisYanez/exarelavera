<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("man_sql_maquinaria_preliquidacion.php");

/**
 * Conexión y acceso a base de datos para Preliquidación de Maquinaria
 * @author Sistema EXA
 * @version 1.0
 */
class Class_Log_Conexion_Maquinaria_Preliquidacion extends MysqlConexion {}

class Class_Log_Datos_Maquinaria_Preliquidacion extends MysqlDatos {
    function __construct() {
        $this->setSentencias('sentencias_maquinaria_preliquidacion');
    } 
}

// Handler ajax que tienen que estar ANTES de inicializar $obBD_conexion y despues no importa, pero usualmente 
// en este sistema se requiere inicializar la conexion.
// Sin embargo, si están siendo incluidos desde el FRONT, la conexion ya estará iniciada en el FRONT y estos handlers están allá!
// Wait! En la arquitectura de este sistema, los handlers AJAX se escribían DENTRO del archivo FRONT/man_alt_maquinaria_preliquidacion.php!

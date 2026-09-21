<?php
/**
 * L�gica para labores del personal
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de creacin 2019-02-07
 *
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once (__DIR__ . '/ban_sql_labores_2.0.php');
class Class_Log_Conexion_Lab_2 extends MysqlConexion {

}
class Class_Log_Datos_Lab_2 extends MysqlDatosContab{
    function __construct(){
        $this->setSentencias('sentencias_labores_v2');
   }

}
?>
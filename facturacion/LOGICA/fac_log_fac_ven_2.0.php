<?php
/**
 * Lógica  para la nueva version de venta :)
 *
 * @author Cesar Bermeo
 * @version 2.0
 * Fecha de creación: 2018-11-23
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("fac_sql_fac_baj_ven_2.0.php");

class Class_Log_Conexion_Vent extends MysqlConexion {

}
class Class_Log_Datos_Vent extends MysqlDatosContab {
   function __construct(){
   		 $this->setSentencias('sentencias_doc');
   }
}
?>
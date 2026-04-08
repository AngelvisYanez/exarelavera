<?php
/**
 * Lógica para fac_ven_lista
 * @author Cesar Bermeo
 * @version 1.0
 * Fecha de creación: 27-12-2018
 *
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("fac_sql_ven_list.php");

class Class_Log_Conexion_Vent_Lista extends MysqlConexion{}

class Class_Log_Datos_Vent_Lista extends MysqlDatosContab{
   function __construct(){
   	$this->setSentencias('sentencias_tes');
   }
}
<?php
require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("sql_man_fac_1.0.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_manifiesto extends MysqlConexion {}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Datos_manifiesto extends MysqlDatosContab {
    function __construct() {
        $this->setSentencias('sentencias_manifiesto');
    }

    function codigoComprAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion){
        return $this->getComNumPecAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion);
    }
   
}//Fin de clase Class_Log_Conexion

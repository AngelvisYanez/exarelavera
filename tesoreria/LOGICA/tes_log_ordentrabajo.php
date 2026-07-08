<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("tes_sql_ordentrabajo.php");

class Class_Log_Conexion_OrdenTrabajo extends MysqlConexion{}
class Class_Logica_OrdenTrabajo extends MysqlDatosContab{
        
    function __construct(){
            $this->setSentencias('sentencias_ordentrabajo');
    }
}
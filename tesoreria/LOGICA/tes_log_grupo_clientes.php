<?php
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("tes_sql_grupo_clientes.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_grupoCliente extends MysqlConexion{

}//Fin de clase Class_Log_Conexion


class Class_Log_Datos_grupoCliente extends MysqlDatosContab{
    function __construct(){
        $this->setSentencias('sentencias_grupoClientes');
    }

}
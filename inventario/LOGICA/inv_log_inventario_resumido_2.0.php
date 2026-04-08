<?php 

/* Logica de las paginas para el control de kardex */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("inv_sql_inventario_resumido_2.0.php");

/** Clase para conexion a la capa de acceso a datos*/
class Class_Log_Conexion_Inv extends MysqlConexion{}//Fin de clase Class_Log_Conexion

/** Clase para acceder a los datos */
class Class_Log_Datos_Inv extends MysqlDatosContab{
    function __construct() {
        $this->setSentencias('sentencias_inv');
    } 
}

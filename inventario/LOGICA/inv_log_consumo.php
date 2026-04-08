<?Php 
/**
 * Logica de las paginas para el control de kardex
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("inv_sql_inventario.php");

/** Clase para conexion a la capa de acceso a datos*/
class Class_Log_Conexion_Con extends MysqlConexion{}//Fin de clase Class_Log_Conexion

/** Clase para acceder a los datos */
class Class_Log_Datos_Con extends MysqlDatosContab{
    function __construct() {
        $this->setSentencias('sentencias_inv');
    } 
}

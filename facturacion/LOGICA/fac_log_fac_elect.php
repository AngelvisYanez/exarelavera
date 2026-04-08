<?Php 
/**
 * Logica de las paginas para comprobantes contables
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualización:	2017-06-08
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("fac_sql_fac_elect.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_FacEle extends MysqlConexion{ }

/* Clase para acceder a los datos */
class Class_Log_Datos_FacEle extends MysqlDatos{	
    function __construct() {
        $this->setSentencias('sentencias_facele');
    } 
}

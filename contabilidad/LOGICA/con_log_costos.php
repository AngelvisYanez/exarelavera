<?Php 
/**
 * Logica de las paginas para comprobantes contables
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualización:	2017-06-08
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("con_sql_costos.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Cos extends MysqlConexion{ }

/* Clase para acceder a los datos */
class Class_Log_Datos_Cos extends MysqlDatosContab{
    function __construct(){
        $this->setSentencias('sentencias_cos');
    }
}

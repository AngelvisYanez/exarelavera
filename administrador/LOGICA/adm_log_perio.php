<?Php 
/**
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci?n:	2015-07-22
 *
 * @package tesoreria.LOGICA
 */

require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("adm_sql_perio.php");


class Class_Log_Conexion_Per extends MysqlConexion{ 

}


/* Clase para acceder a los datos */
class Class_Log_Datos_Per extends MysqlDatos{
    function __construct() {
        $this->setSentencias('sentencias_Pec');
    }
  
}
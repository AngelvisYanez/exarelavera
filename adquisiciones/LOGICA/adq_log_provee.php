<?Php 
/**
 * Logica de las paginas para comprobantes contables
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2017-06-08
 */
require_once (__DIR__.'/../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("adq_sql_provee.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Prv extends MysqlConexion{ 

}

/* Clase para acceder a los datos */
class Class_Log_Datos_Prv extends MysqlDatosContab{
    function __construct() {
        $this->setSentencias('sentencias_provee');
    }
  
}


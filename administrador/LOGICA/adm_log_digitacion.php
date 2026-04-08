<?Php 
/**
 * Logica de las paginas para comprobantes contables
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualización:	2017-06-08
 */
require_once("adm_sql_digitacion.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Ven extends MysqlConexion{ }

/* Clase para acceder a los datos */
class Class_Log_Datos_Ven extends MysqlDatosContab{
    /* contructor sentencias sql */
    function __construct() {
        $this->setSentencias('sentencias_ven');
    }
}
<?Php 
/**
 * Logica de las paginas para roles
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualización:	2018-05-18
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');

/* Clase para acceder a los datos */
class Class_Log_Datos_Naviera extends MysqlDatos{
    function __construct() {
        //$this->setSentencias('sentencias_rol');
    }
}
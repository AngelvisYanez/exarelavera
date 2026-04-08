<?Php 
/**
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2015-07-22
 *
 * @package tesoreria.LOGICA
 */

require_once ("../../DATA/DAC.php");
require_once("ban_sql_materiales.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro
 *
 * @package tesoreria.LOGICA
*/

class Class_Log_Conexion_Produ extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author car.87cod :)
 *
 */
class Class_Log_Datos_Produ extends MysqlDatos{	    
    function __construct() {
        $this->setSentencias('sentencias_produ');
    }
}

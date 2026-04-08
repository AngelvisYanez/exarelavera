<?Php 
/**
 * Logica de las paginas que tienen que ver con clientes
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualización:	2012-05-10
 *
 * @package contabilidad.LOGICA
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("con_sql_valautorizacion.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro 
 *
 * @package contabilidad.LOGICA
*/

class Class_Log_Conexion_Con extends MysqlConexion{ }//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
*/

class Class_Log_Datos_Con extends MysqlDatos{
    function __construct(){
        $this->setSentencias('sentencias_con');
    }
}
<?Php 
/**
 * Logica de las paginas que tienen que ver con clientes
 *
 * @author Alejandro CAmacho
 * @version 1.0
 * Fecha de actualización:	2021/03/22
 *
 * @package administrador.Logica
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("con_sql_tareas.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Alejandro Camacho 
 *
 * @package contabilidad.LOGICA
*/

class Class_Log_Conexion_Con extends MysqlConexion{ }//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author Alejandro Camacho
 *
 */

class Class_Log_Datos_Con extends MysqlDatos{
    function __construct(){
        $this->setSentencias('sentencias_con');
    }
}
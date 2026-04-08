<?Php 
/**
 * Logica de las paginas que tienen que ver con clientes
 *
 * @author Erik Niebla
 * @version 1.0
 *
 * @package tesoreria.LOGICA
 */
require_once("fac_sql_caja_2.0.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Caj extends MysqlConexion{ }//Fin de clase Class_Log_Conexion

/* Clase para acceder a los datos */
class Class_Log_Datos_Caj extends MysqlDatos
{
    function __construct()
    {
        $this->setSentencias('sentencias_caj');
    }
}
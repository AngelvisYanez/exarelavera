    <?php
/**
 * Logica de anticipos
 * @author Cesar Bermeo
 * @version  1.0
 * Fecha de creacion: 16/04/2019
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once($APP_REAL_PATH."/tesoreria/LOGICA/tes_sql_estado_anticipo_prv.php");

class Class_Log_Conexion_Ant_Prv extends MysqlConexion{}
class Class_Log_Datos_Ant_Prv extends MysqlDatosContab{
    function __construct(){
        $this->setSentencias('sentencias_anticipo_prv');
    }
}
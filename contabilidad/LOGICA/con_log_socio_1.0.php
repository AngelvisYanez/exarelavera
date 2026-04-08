<?Php
/**
 * Logica de las paginas que tienen que ver con socio
 *
 * @author Edison Moya
 * @version 1.0
 *
 * @package contabilidad.LOGICA
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("con_sql_socio_1.0.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Soc extends MysqlConexion{ }//Fin de clase Class_Log_Conexion

/* Clase para acceder a los datos */
class Class_Log_Datos_Soc extends MysqlDatosContab{
    function __construct(){
        $this->setSentencias('sentencias_socio');
    }
    function codigoComprAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion){ return $this->getComNumPecAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion); }
    
}

<?Php
/**
 * Logica de las paginas que tienen que ver con cheques
 *
 * @author Erik Niebla :)
 * @version 2.0
 * Fecha de actualizacion:	2017-07-07
 *
 * @package tesoreria.LOGICA
 */
require_once($APP_REAL_PATH."/auditoria/LOGICA/aud_log_auditoria.php");
require_once($APP_REAL_PATH."/tesoreria/LOGICA/tes_sql_anticipo_cli_1.0.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Ant_cli extends MysqlConexion{ }

/* Clase para acceder a los datos*/
class Class_Log_Datos_Ant_cli extends MysqlDatosContab{
    function __construct(){
        $this->setSentencias('sentencias_anticipo_cli');
    }
    function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD){ echo $this->getReportHeader($sucursal, $titulo, $subtitulo, $obBD);
    }
    function pieReporteStandar($sucursal, $usuario, $obBD){
    	echo $this->getReportFooter($sucursal, $usuario, $obBD);
    }
    function codigoComprAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion){ return $this->getComNumPecAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion); }

}

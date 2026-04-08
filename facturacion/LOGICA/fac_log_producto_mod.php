<?Php 
/**
 * Logica de las paginas que tienen que ver con cheques
 *
 * @author Erick Cordova :)
 * @version 2.0
 * Fecha de actualizacion:	2017-11-21
 *
 * @package tesoreria.LOGICA
 */
require_once($APP_REAL_PATH."/auditoria/LOGICA/aud_log_auditoria.php");
require_once($APP_REAL_PATH."/facturacion/LOGICA/tes_sql_producto_mod.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Pro extends MysqlConexion{ }

/* Clase para acceder a los datos*/
class Class_Log_Datos_Pro extends MysqlDatosContab{
    function __construct(){
        $this->setSentencias('sentencias_pro');
    }
    function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD){ echo $this->getReportHeader($sucursal, $titulo, $subtitulo, $obBD); }
    function pieReporteStandar($sucursal, $usuario, $obBD){ echo $this->getReportFooter($sucursal, $usuario, $obBD); }
    function codigoComprAuto($Tia_Cod, $Pec_Cod, $mes, $obBD){ return $this->getComNumPecAuto($Tia_Cod, $Pec_Cod, $mes, $obBD); }     
    
    }
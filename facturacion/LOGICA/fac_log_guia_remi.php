<?Php 
$tipo_compr=6; //Tipo de comprobante de la retencion 
$cod_banano=338; //Codigo de Retencion del Banano
/**
 * Logica de las paginas para comprobantes contables
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualización:	2017-06-08
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("fac_sql_guia_remi.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_G_Remi extends MysqlConexion{ }

/* Clase para acceder a los datos */
class Class_Log_Datos_G_Remi extends MysqlDatosContab{	
    function __construct() {
        $this->setSentencias('sentencias_g_remi');
    }
    function getMotivos(){
        return array(
            'VENTA','COMPRA','TRANSFORMACION','CONSIGNACION',
            'TRASLADO ENTRE ESTABLECIMIENTOS DE UNA MISMA EMPRESA',
            'TRASLADO POR EMISOR ITINERANTE DE COMPROBANTES DE VENTA',
            'DEVOLUCION','IMPORTACION','EXPORTACION','OTROS'
            
        );
    }
}

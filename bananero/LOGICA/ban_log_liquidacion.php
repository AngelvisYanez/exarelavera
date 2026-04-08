<?Php 
$val_caja_def='6.26';
/**
 * Logica de las paginas para roles
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizaci�n:	2018-05-18
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');

/* Clase para acceder a los datos */
class Class_Log_Datos_Liquidacion extends MysqlDatosContab{
    function __construct() {
        //$this->setSentencias('sentencias_rol');
    }
    function getGrpIngresos(){
        //return array(-1=>'Cajas Embarcadas',0=>'Devoluci�n Cartones',1=>'Devoluci�n Materiales',2=>'Devoluci�n Materiales 2');
        return array(-1=>'Cajas Embarcadas',0=>'Cartones',1=>'Materiales',2=>'Materiales 2');
    }
    function getGrpDescuentos(){
        return array(-1=>'Retenciones',0=>'Descuento Cartones',1=>'Descuento Materiales',2=>'Descuento Materiales 2');
    }
    function getPrecioOficial(){
        return 3.36;
    }
    function getAnticipos($Prv_Cod,$obBD,$echo=false){
        return $this->getRowConsultaSql("
SELECT ant2.Prv_Cod,CAST(SUM(pga.Pap_Val) AS DECIMAL(15,2))-Pagos.Total AS Total, CAST(GROUP_CONCAT(IF(Atp_Obs IS NULL,'',Atp_Obs))AS CHAR) AS Obs
FROM pago_anticipo_proveedores AS pga 
INNER JOIN anticipos_proveedores AS ant2 ON (ant2.Atp_Cod=pga.Atp_Cod)
INNER JOIN asientos AS asi ON (asi.Asi_Cod=pga.Asi_Cod)
INNER JOIN (
      SELECT Prv_Cod,CAST(IF(SUM(det_ant_ccpp.Dac_Val) IS NULL,0,SUM(det_ant_ccpp.Dac_Val))AS DECIMAL(12,2)) as Total FROM anticipos_proveedores
      INNER JOIN det_ant_ccpp ON det_ant_ccpp.Atp_Cod = anticipos_proveedores.Atp_Cod 
      WHERE
        (anticipos_proveedores.Atp_Est ='A' OR anticipos_proveedores.Atp_Est ='U') AND anticipos_proveedores.Prv_Cod=$Prv_Cod GROUP BY Prv_Cod
)AS Pagos ON Pagos.Prv_Cod=ant2.Prv_Cod
WHERE ant2.Prv_Cod=$Prv_Cod
    AND (ant2.Atp_Est ='A' OR ant2.Atp_Est ='U')
    AND pga.Asi_Cod NOT IN (SELECT cheques.Asi_Cod FROM cheques WHERE cheques.Asi_Cod = asi.Asi_Cod AND (cheques.Che_Est = 'P' OR cheques.Che_Est = 'I'))
    GROUP BY Prv_Cod", $obBD, $echo);        
    }
}
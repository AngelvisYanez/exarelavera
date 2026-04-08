<?Php 
/**
 *
 * @author Erik Niebla
 * @version 1.0
 * Fecha de actualizacion:	2015-07-22
 *
 * @package tesoreria.LOGICA
 */
require_once("tes_sql_cccc.php");

/** Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Cccc extends MysqlConexion{ }

/* Clase para acceder a los datos */
class Class_Log_Datos_Cccc extends MysqlDatosContab{	
    function __construct(){ $this->setSentencias('sentencias_cccc'); }
    function codigoComprAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion){ return $this->getComNumPecAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion); }
    // funcion vieja no sirve, no se usa
    function codigoComprAutomatic($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion){ /* Codificacion numerica en base al periodo contable y mensualmente */
        $row_rs_numcom = $this->getRowConsulta(26, $Tia_Cod.'*'.$Pec_Cod.'*'.$mes, $obBD_conexion);
        // Revisar la condicion (todo funciona correctamente pero con artificio)
        if ((count($row_rs_numcom) > 0) && ($row_rs_numcom['Com_Num'] != ''))
            $Com_Num=$row_rs_numcom['Com_Num'];
        else $Com_Num=1;							
        return $Com_Num;
    }       
}

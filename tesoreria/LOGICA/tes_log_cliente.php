<?Php 
/**
 * Logica de las paginas que tienen que ver con clientes
 *
 * @author Erik Niebla
 * @version 1.0
 *
 * @package tesoreria.LOGICA
 */
require_once(__DIR__ . "/tes_sql_cliente.php");
require_once(__DIR__."/../../DATA/MysqlConexion.php");
require_once(__DIR__."/../../DATA/MysqlDatos.php");

/* Clase para conexion a la capa de acceso a datos */
class Class_Log_Conexion_Cli extends MysqlConexion{ }//Fin de clase Class_Log_Conexion

/* Clase para acceder a los datos */
class Class_Log_Datos_Cli extends MysqlDatosContab{
    function __construct(){
        $this->setSentencias('sentencias_cli');
    }
    function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD){ echo $this->getReportHeader($sucursal, $titulo, $subtitulo, $obBD); }
    function pieReporteStandar($sucursal, $usuario, $obBD){ echo $this->getReportFooter($sucursal, $usuario, $obBD); }
	/**
	 * Si el valor ingresado es una cedula se le aumenta 001
	 * Si el valor ingresado es ruc se le quita el 001
	 * @param string $Prs_Ced cedula/ruc a transaformar
	 * @return string cedula y ruc concatenados por (*)
	 */
	function convertCedulaRuc($Prs_Ced)
	{
		/**
		 * Guarda momentaneamente la cedula
		 * @var string
		 */
		$Prs_Ced1 = $Prs_Ced;
		
		/**
		 * Guarda momentaniamente el ruc
		 * @var string
		 */
		$Prs_Ruc1 = $Prs_Ced;
		
		if(strlen($Prs_Ced) == 10){
			$Prs_Ced1 = $Prs_Ced;
			$Prs_Ruc1 = $Prs_Ced.'001';
		}elseif(strlen($Prs_Ced) == 13){
			$Prs_Ced1 = substr($Prs_Ced,0,10);
			$Prs_Ruc1 = $Prs_Ced;
		}
		
		return $Prs_Ced1.'*'.$Prs_Ruc1;
	}
}

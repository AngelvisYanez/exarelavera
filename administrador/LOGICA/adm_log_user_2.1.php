<?Php 
/**
 * Logica de las paginas que tienen que ver con clientes
 *
 * @author Asael Tello
 * @version 2.0
 *
 * @package administrador.LOGICA
 */
require_once("adm_sql_user_2.1.php");

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
		$Prs_Ruc = $Prs_Ced;
			
		/**
		 * Identificando ruc o cedula segun el caso
		 * si es ruc obteniendo cedula
		 * si es cedula aumentando 001 para hacerlo ruc
		 */
		if(strlen(trim($Prs_Ced1)) == 10)
		{
			$Prs_Ruc .= '001';
		}
		else if(strlen(trim($Prs_Ced1)) == 13)
		{
			$Prs_Ced1 = substr(trim($Prs_Ruc),0,10);
		}
		
		return ($Prs_Ced1.'*'.$Prs_Ruc);
	}
	
	/**
	 * Ejecuta consulta a la base de datos para verificar si existen datos en persona y cliente
	 * @param int $Sen_Sql_Per numero de sentencia de la persona a buscar
	 * @param int $Sen_Sql_Cli numero de sentencia del cliente a buscar
	 * @param string $str cedula/ruc concatenados
	 * @param number $Ses_Emp_Cod codigo de la empresa
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return int $event codigo del resultado
	 */
	function ComprovarExistencia($Sen_Sql_Per,$Sen_Sql_Cli,$str,$Ses_Emp_Cod,$obBD){
	
		$rs_persona = $this->consultasobBD($Sen_Sql_Per, $str, $obBD);
		$row_rs_persona = $this->registros();
		$total_rs_persona = $this->numregistros();
	
		/**
		 * Código de la persona
		 * @var number
		 */
		$Prs_Cod = $row_rs_persona['Prs_Cod'];
			
		$rs_comprobar = $this->consultasobBD($Sen_Sql_Cli, $Prs_Cod.'*'.$Ses_Emp_Cod, $obBD);
		$row_rs_comprobar = $this->registros();
		$total_rs_comprobar = $this->numregistros();
	
		/**
		 * inicializando evento
		 * @var int
		 */
		$event = 0;
			
		if ($total_rs_comprobar == 0 && $total_rs_persona == 0)
		{
			/**
			 * registrar persona - cliente
			 */
			$event = 1;
		}
		else if ($total_rs_comprobar == 0 && $total_rs_persona > 0)
		{
			/**
			 * registrar cliente
			 */
			$event = 2;
		}
	
		/**
		 * liberando memoria
		 */
		$this->free_result($rs_persona);
		$this->free_result($rs_comprobar);
	
		return $event;
	}
	
	
	
	function InsetPersonaCliente($sen_Prs,$paramPrs,$sen_Cli,$paramCli){
		/**
		 * Objeto de coneccion
		 * @var Class_Log_Conexion_Cli
		 */
		$obBD = new Class_Log_Conexion_Cli($_SESSION['Ses_Dat_Dis']);
	
		$this->inicio_transaccion($obBD->conexion);
	
		/**
		 * grabar registro en persona
		 */
		$this->grabarv_registros(sentencias_cli($sen_Prs,$this->parametros($paramPrs)),$obBD->conexion);
		
		/**
		 * codigo de autoincremento del registro
		 * @var number
		 */
		$Id = $this->insercionid($obBD->conexion);
		
		$paramCli = $Id.'*'.$paramCli;
	
		/**
		 * Grabar registro en clientes
		 */
		$this->grabarv_registros(sentencias_cli($sen_Cli,$this->parametros($paramCli)),$obBD->conexion);
	
		$this->fin_transaccion($obBD->conexion);
	
		unset($Id);
		$obBD->cerrar();
	}
	
	function updatePersonaCliente($sen_Prs,$paramPrs,$sen_Cli,$paramCli){
		/**
		 * Objeto de coneccion
		 * @var Class_Log_Conexion_Cli
		 */
		$obBD = new Class_Log_Conexion_Cli($_SESSION['Ses_Dat_Dis']);
	
		$this->inicio_transaccion($obBD->conexion);
	
		/**
		 * Grabar registro en persona
		 */
		$this->grabarv_registros(sentencias_cli($sen_Prs,$this->parametros($paramPrs)),$obBD->conexion);
	
		/**
		 * Grabar registro en clientes
		 */
		$this->grabarv_registros(sentencias_cli($sen_Cli,$this->parametros($paramCli)),$obBD->conexion);
	
		$this->fin_transaccion($obBD->conexion);
	
		//unset($Id);
		$obBD->cerrar();
	}

}
<?php
/**
 * Logica de las paginas que tienen que ver con proveedor
 *
 * @author car.87cod :)
 * @version 2.0
 * Fecha de actualización:	2012-04-30
 *
 * @package tesoreria.LOGICA
 */

require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("adq_sql_proveedor.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author car.87cod :)
 *
 * @package tesoreria.LOGICA
*/
class Class_Log_Conexion_Prv extends MysqlConexion{

}

/**
 * Clase para acceder a los datos
 * @author car.87cod :)
 *
 */
class Class_Log_Datos_Prv extends MysqlDatos{
	
	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Prv $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function consultasobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_prv($sen_sql,$Par_Sql), $obBD->conexion);
	}

	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Prv $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function operacionobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_prv($sen_sql,$Par_Sql), $obBD->conexion);
	}
	
	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Prv $obBD para realizar la conexcion correspondiente
	 * @return array $row fila de datos
	 */
	function getRowConsulta($sen_sql,$param,$obBD = null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		
		$row =  $this->fetch_assoc($result);
		
		$this->free_result($result);
		
		return $row;
	}

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Prv $obBD para realizar la conexcion correspondiente
	 * @return array $array arreglo de datos asociados
	 */ 
	function getArrayConsulta($sen_sql,$param,$obBD = null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		
		$array = array();
		
		while($row_rs = $this->fetch_assoc($result))
		{
			$array[] = $row_rs;
		}
		
		$this->free_result($result);
		
		return $array;
	}

	/**
	 * Inserta o actualiza o elimina los datos de una sola transacccion -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de datos
	 * @param Class_Log_Datos_Prv $obBD objeto de conexion
	 */
	function insertUpdateDelete($sen_sql,$param, $obBD = null)
	{		
		$this->inicio_transaccion($obBD->conexion);
		
		//Realiza Insert, Update o Delete
		$this->operacionobBD($sen_sql,$param,$obBD);
			
		$this->fin_transaccion($obBD->conexion);		
	}
	
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
	 * @param int $Sen_Sql_Prv numero de sentencia del cliente a buscar
	 * @param string $str cedula/ruc concatenados
	 * @param number $Ses_Emp_Cod codigo de la empresa
	 * @param Class_Log_Conexion_Prv $obBD para realizar la conexcion correspondiente
	 * @return int $event codigo del resultado
	 */
	function ComprovarExistencia($Sen_Sql_Per,$Sen_Sql_Prv,$str,$Ses_Emp_Cod,$obBD)
	{
	
		$rs_persona = $this->consultasobBD($Sen_Sql_Per, $str, $obBD);
		$row_rs_persona = $this->registros();
		$total_rs_persona = $this->numregistros();
	
		/**
		 * Código de la persona
		 * @var number
		 */
		$Prs_Cod = $row_rs_persona['Prs_Cod'];
			
		$rs_comprobar = $this->consultasobBD($Sen_Sql_Prv, $Prs_Cod.'*'.$Ses_Emp_Cod, $obBD);
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
			 * registrar persona - proveedor
			 */
			$event = 1;
		}
		else if ($total_rs_comprobar == 0 && $total_rs_persona > 0)
		{
			/**
			 * registrar proveedor
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
	
	
	
	function InsetPersonaProveedor($sen_Prs,$paramPrs,$sen_Prv,$paramPrv)
	{
		/**
		 * Objeto de coneccion
		 * @var Class_Log_Conexion_Prv
		 */
		$obBD = new Class_Log_Conexion_Prv($_SESSION['Ses_Dat_Dis']);

	
		$this->inicio_transaccion($obBD->conexion);
	
		/**
		 * grabar registro en persona
		 */
		$this->grabarv_registros(sentencias_prv($sen_Prs,$this->parametros($paramPrs)),$obBD->conexion);
		
		/**
		 * codigo de autoincremento del registro
		 * @var number
		 */
		$Id = $this->insercionid($obBD->conexion);
		
		$paramPrv = $Id.'*'.$paramPrv;
	
		/**
		 * Grabar registro en proveedor
		 */
		$this->grabarv_registros(sentencias_prv($sen_Prv,$this->parametros($paramPrv)),$obBD->conexion);
	
		$this->fin_transaccion($obBD->conexion);
	
		unset($Id);
		$obBD->cerrar();
	}
	
	function updatePersonaProveedor($sen_Prs,$paramPrs,$sen_Prv,$paramPrv)
	{
		/**
		 * Objeto de coneccion
		 * @var Class_Log_Conexion_Prv
		 */
		$obBD = new Class_Log_Conexion_Prv($_SESSION['Ses_Dat_Dis']);
	
		$this->inicio_transaccion($obBD->conexion);
	
		/**
		 * Grabar registro en persona
		 */
		$this->grabarv_registros(sentencias_prv($sen_Prs,$this->parametros($paramPrs)),$obBD->conexion);
	
		/**
		 * Grabar registro en proveedor
		 */
		$this->grabarv_registros(sentencias_prv($sen_Prv,$this->parametros($paramPrv)),$obBD->conexion);
	
		$this->fin_transaccion($obBD->conexion);
	
		unset($Id);
		$obBD->cerrar();
	}
	
	/**
	* Formato standar para reportes
	* @param int $sucursal Código de la sucursal
	* @param string $titulo Título del reporte
	* @param string $subtitulo Subtitulo del reporte
	*/
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD)
	{
		/* Consulta de la cabecera del reporte */
		$row_institucion = $this->getRowConsulta(22, $sucursal, $obBD);
		/* Consulta la provicia y pais de la sucursal */
		$row_provincia = $this->getRowConsulta(21, $row_institucion['Ciu_Cod'], $obBD);
			
		?>
				<table width="80%" border="0" cellpadding="0" cellspacing="0">
				  <tr align="center">
				    <td width="5%" rowspan="5" valign="top"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="83" height="67" /></td>
				    <td width="75%" class="TITULO_REPORTE_2"><?Php echo $row_institucion['Emp_Nom']; ?></td>
				  </tr>
				  <tr align="center">
				    <td valign="top" class="Texto_Reporte"><div align="center"><strong>R.U.C.:</strong> &nbsp;<?php echo $row_institucion['Emp_Ruc']; ?>&nbsp;		      <strong>TELEFONO:</strong>&nbsp;<?php echo $row_institucion['Suc_Te1']; ?></div></td>
			      </tr>
				  <tr align="center">
				    <td valign="top" class="Texto_Reporte"><div align="center"><strong>DIRECCION:</strong>&nbsp;<?php echo $row_institucion['Suc_Dir']; ?></div></td>
			      </tr>
				  <tr align="center">
				    <td valign="top" class="Texto_Reporte"><div align="center"><strong>E-MAIL:</strong> &nbsp;<?php echo $row_institucion['Suc_Cor']; ?></div></td>
			      </tr>
				  <tr align="center">
				    <td align="center" valign="top" class="Texto_Reporte"><div align="center"><?Php 
					if (count($row_provincia) > 0)
					{
						$provincia = " - ".$row_provincia['Pro_Nom'].' - '.$row_provincia['Pas_Nom'];
					}
					else
					{
						$provincia = "";					
					}
					echo $row_institucion['Ciu_Des'].$provincia;?></div></td>
		  		  </tr>
				  <tr align="center">
				    <td colspan="2" valign="top"><hr /></td>
		  		  </tr>
				  <tr align="center">
				    <td colspan="2" valign="top" class="TITULO_REPORTE"><?php echo $titulo; ?></td>
		  		  </tr>
				  <tr align="center">
				    <td colspan="2" valign="top" class="TITULO_REPORTE"><?php echo $subtitulo; ?></td>
			      </tr>
			    </table>
		<?php
			} 
			/**
			 * Formato standar para reportes
			 * @param int $sucursal Código de la sucursal
			 * @param string $usuario Código del usuario 
			 */	
			function pieReporteStandar($sucursal, $usuario, $obBD)
			{ 
				/* Consulta de la cabecera del reporte */
				$row_institucion = $this->getRowConsulta(22, $sucursal, $obBD);	
				/* Consulta los datos del usuario */
				$row_usuario = $this->getRowConsulta(23, $usuario, $obBD);
				
				$fecha=explode("-",date("Y-m-d"));	
		   	    $fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0] ;	
					
			?>
				<table width="80%" border="0" cellpadding="0" cellspacing="0">
		   		  <tr align="center">
				    <td valign="top"><hr /></td>
		  		  </tr>
				  <tr align="center">
				    <td width="75%" valign="top" class="Texto_Reporte"><div align="center"><strong>FECHA IMPRESI&Oacute;N:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;		      <strong>USUARIO:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
			      </tr>
			    </table>
		<?php
			}
}
?>
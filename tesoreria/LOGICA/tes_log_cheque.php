<?Php 
/**
 * Logica de las paginas que tienen que ver con clientes
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaci�n:	2012-07-19
 *
 * @package tesoreria.LOGICA
 */

// require_once ("../../DATA/DAC.php");
// require_once("tes_sql_cheque.php");
require_once($APP_REAL_PATH."/DATA/DAC.php");
require_once($APP_REAL_PATH."/tesoreria/LOGICA/tes_sql_cheque.php");

/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro
 *
 * @package tesoreria.LOGICA
*/

class Class_Log_Conexion_Che extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/**
 * Clase para acceder a los datos
 * @author car.87cod :)
 *
 */

class Class_Log_Datos_Che extends MysqlDatos{
	
	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function consultasobBD($sen_sql,$param, $obBD)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_che($sen_sql,$Par_Sql), $obBD->conexion);
	}

	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function operacionobBD($sen_sql,$param, $obBD)
	{
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_che($sen_sql,$Par_Sql), $obBD->conexion);
	}
	
	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return array $row fila de datos
	 */
	function getRowConsulta($sen_sql,$param,$obBD)
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
	 * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
	 * @param Class_Log_Datos_Cli $obDT para la abtraccion de los datos
	 * @return array $array arreglo de datos asociados
	 */ 
	function getArrayConsulta($sen_sql,$param,$obBD)
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
	 * @param Class_Log_Datos_Cli $obBD objeto de conexion
	 */
	function insertUpdateDelete($sen_sql,$param, $obBD)
	{		
		$this->inicio_transaccion($obBD->conexion);
		
		//Realiza Insert, Update o Delete
		$this->operacionobBD($sen_sql,$param,$obBD);
			
		$this->fin_transaccion($obBD->conexion);		
	}

	/**
	 * Formato standar para reportes
	 * @param int $sucursal C�digo de la sucursal
	 * @param string $titulo T�tulo del reporte
	 * @param string $subtitulo Subtitulo del reporte
	 */	
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD)
	{ 
		/* 
		* Consulta de la cabecera del reporte 
		*/
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);
		/* 
		* Consulta la provicia y pais de la sucursal 
		*/
		$row_provincia = $this->getRowConsulta(3, $row_institucion['Ciu_Cod'], $obBD);	
			
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
		    <td colspan="2" valign="top" class="TITULO_REPORTE"><? echo $titulo; ?></td>
  		  </tr>
		  <tr align="center">
		    <td colspan="2" valign="top" class="TITULO_REPORTE"><? echo $subtitulo; ?></td>
	      </tr>
	    </table>
<?php
	} 

	/**
	 * Formato standar para reportes
	 * @param int $sucursal C�digo de la sucursal
	 * @param string $usuario C�digo del usuario 
	 */	
	function pieReporteStandar($sucursal, $usuario, $obBD)
	{ 
		/* Consulta de la cabecera del reporte */
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);	
		/* Consulta los datos del usuario */
		$row_usuario = $this->getRowConsulta(4, $usuario, $obBD);
		
		$fecha=explode("-",date("Y-m-d"));	
   	    $fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0].", ".date("H:m:s") ;	
			
	?>
		<table width="80%" border="0" cellpadding="0" cellspacing="0">
   		  <tr align="center">
		    <td colspan="2" valign="top"><hr /></td>
  		  </tr>
		  <tr>
		    <td align="center" width="50%" valign="top" class="Texto_Reporte"><div align="center"><strong>Fecha de Impresi&oacute;n:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;</div></td>
		    <td align="center" width="50%" valign="top" class="Texto_Reporte"><div align="center"><strong>Usuario:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
	      </tr>
	    </table>
<?php
	}
        function codigoComprAutomatic($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion)
  	{			
		/* 
		* Codificaci�n numerica en base al periodo contable y mensualmente 
		*/
		$row_rs_numcom = $this->getRowConsulta(355, $Tia_Cod.'*'.$Pec_Cod.'*'.$mes, $obBD_conexion);
		// Revisar la condici�n (todo funciona correctamente pero con artificio)
		if ((count($row_rs_numcom) > 0) && ($row_rs_numcom['Com_Num'] != ''))
		{
			$Com_Num=$row_rs_numcom['Com_Num'];
		} else {
			$Com_Num=1;
		}					
		return $Com_Num;
  	}
        function codigoComprAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion)
  	{			
		/* 
		* Codificaci�n numerica en base al periodo contable y mensualmente 
		*/
		$row_rs_numcom = $this->getRowConsulta(374, $Tia_Cod.'*'.$Pec_Cod.'*'.$mes, $obBD_conexion);
		// Revisar la condici�n (todo funciona correctamente pero con artificio)
		if ((count($row_rs_numcom) > 0) && ($row_rs_numcom['Com_Num'] != ''))
		{
			$Com_Num=$row_rs_numcom['Com_Num'];
		} else {
			$Com_Num=1;
		}					
		return $Com_Num;
  	}
}
?>
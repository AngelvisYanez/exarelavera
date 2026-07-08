<?php
	require_once('../../auditoria/LOGICA/aud_log_auditoria.php');
	require_once ("fac_sql_aper_caja.php");

/******************************************************/
/******************************************************/
/*   Clase para conexion a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

class Class_Log_Conexion_Tes extends MysqlConexion{

}//Fin de clase Class_Log_Conexion

/******************************************************/
/******************************************************/

/******************************************************/
/******************************************************/
/*  Clase para los datos a la capa de acceso a datos  */
/******************************************************/
/******************************************************/

/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
 */

class Class_Log_Datos_Tes extends MysqlDatos{
	function __construct() {
        $this->setSentencias('sentencias_tes');
    } 
	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Pro $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	/*function consultasobBD($sen_sql,$param, $obBD)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_tes($sen_sql,$Par_Sql), $obBD->conexion);
	}*/

	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Pro $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	/*function operacionobBD($sen_sql,$param, $obBD)
	{
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_tes($sen_sql,$Par_Sql), $obBD->conexion);
	}*/
	
	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Cli $obBD para realizar la conexcion correspondiente
	 * @return array $row fila de datos
	 */
	/*function getRowConsulta($sen_sql,$param,$obBD)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		
		$row =  $this->fetch_assoc($result);
		
		$this->free_result($result);
		
		return $row;
	}*/

	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
	 * @param Class_Log_Datos_Cli $obDT para la abtraccion de los datos
	 * @return array $array arreglo de datos asociados
	 */ 
	/*function getArrayConsulta($sen_sql,$param,$obBD)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		
		$array = array();
		
		while($row_rs = $this->fetch_assoc($result))
		{
			$array[] = $row_rs;
		}
		
		$this->free_result($result);
		
		return $array;
	}*/

	/**
	 * Inserta o actualiza o elimina los datos de una sola transacccion -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de datos
	 * @param Class_Log_Datos_Cli $obBD objeto de conexion
	 */
	/*function insertUpdateDelete($sen_sql,$param, $obBD)
	{		
		$this->inicio_transaccion($obBD->conexion);
		
		//Realiza Insert, Update o Delete
		$this->operacionobBD($sen_sql,$param,$obBD);
			
		$this->fin_transaccion($obBD->conexion);		
	}*/
	
	/* 
	* Calcula el total de las ventas 
	*/
	function calculosConsultaVentas($parametro, $tipo, $obBD_conexion)
	{
		/* Opciones para el retorno 
		0 = SUBTOTAL
		1 = TARIFA 0
		2 = TARIFA 12
		3 = IVA
		4 = DESCUENTO
		5 = TOTAL */

		/* Consulta del total de las ventas */
		$rs_ventas = $this->consulta(sentencias_tes(29, $this->parametros($parametro.'*'.$tipo)), $obBD_conexion->conexion);
		$row_rs_ventas = $this->registros();
		$total_rs_ventas = $this->numregistros();	

		$mover = false;
		$subtotal = "0";
		$tarifa_0 = "0";
		$tarifa_12 = "0";
		$iva = "0";
		$des = "0";
		$total = "0";
		
		if ($row_rs_ventas['Iva_Sri'] != "0")//2 es el valor en la tabla del sri
		{		
			$subtotal = $row_rs_ventas['Importe'];
			$tarifa_0= $row_rs_ventas['sub0'];
			$tarifa_12 = $row_rs_ventas['sub12'];
			$iva = $row_rs_ventas['Iva'];
			$des = $row_rs_ventas['Descuento'];
			$total = $row_rs_ventas['Total'] + $iva;

			/* En caso de existir 2 registros de total de ventas se mueve el apuntador de la tabla */
			$mover = true;					
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 2)
		

		if ($mover == true)
		{
			/* Vuelve al fin del puntero la consulta creditos */
			$row_rs_ventas = first_last($rs_ventas, $row_rs_ventas, $total_rs_ventas);			  
		}//Fin del if ($mover == true)
		
		if ($row_rs_ventas['Iva_Sri'] == "0")//2 es el valor en la tabla del sri
		{			
			$subtotal = $subtotal + $row_rs_ventas['Importe']; //Suma los dos importes de la consulta
			$tarifa_0 = $row_rs_ventas['sub0'];
			$des = $des + $row_rs_ventas['Descuento'];
			$total = $total + $row_rs_ventas['Total'];
		}//Fin del if ($row_rs_ventas['Iva_Sri'] == 0)
	
		return $subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$des.'*'.$total;
	}//Fin del function calculos_ventas($ini, $fin)
	
	
			
	/**
	* Formato standar para reportes
	* @param int $sucursal CÃ³digo de la sucursal
	* @param string $titulo TÃ­tulo del reporte
	* @param string $subtitulo Subtitulo del reporte
	*/
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD)
	{
		/* Consulta de la cabecera del reporte */
		$row_institucion = $this->getRowConsulta(2, $sucursal, $obBD);
		/* Consulta la provicia y pais de la sucursal */
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
			 * @param int $sucursal CÃ³digo de la sucursal
			 * @param string $usuario CÃ³digo del usuario 
			 */	
			function pieReporteStandar($sucursal, $usuario, $obBD)
			{ 
				/* Consulta de la cabecera del reporte */
				$row_institucion = $this->getRowConsulta(2, $sucursal, $obBD);	
				/* Consulta los datos del usuario */
				$row_usuario = $this->getRowConsulta(1, $usuario, $obBD);
				
				$fecha=explode("-",date("Y-m-d"));	
		   	    $fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0] ;	
					
			?>
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
		   		  <tr align="center">
				    <td valign="top"><hr /></td>
		  		  </tr>
				  <tr align="center">
				    <td width="75%" valign="top" class="Texto_Reporte"><div><strong>FECHA IMPRESI&Oacute;N:</strong> &nbsp;<?php echo $fechaHoy; ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>USUARIO:</strong>&nbsp;<?php echo $row_usuario['Prs_Ape'].' '.$row_usuario['Prs_Nom'] ; ?></div></td>
			      </tr>
			    </table>
		<?php
			}
}
?>
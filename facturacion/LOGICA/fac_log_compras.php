<?Php 
/**
 * Logica de las paginas de factura de compra
 *
 * @author Lewis Chimarro
 * @version 1.0
 * Fecha de actualizaciÃ³n:	2012-08-20
 *
 * @package tesoreria.LOGICA
 */
require_once ('../../auditoria/LOGICA/aud_log_auditoria.php');
require_once("fac_sql_compras.php");
/**
 * Clase para conexion a la capa de acceso a datos
 * 
 * @author Lewis Chimarro 
 *
 * @package tesoreria.LOGICA
*/
class Class_Log_Conexion_Comt extends MysqlConexion{
}//Fin de clase Class_Log_Conexion
/**
 * Clase para acceder a los datos
 * @author Lewis Chimarro
 *
 */
class Class_Log_Datos_Comt extends MysqlDatos{
	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function consultasobBD($sen_sql,$param, $obBD = null)
	{
		$Par_Sql= $this->parametros($param);
		return $this->consulta(sentencias_comf($sen_sql,$Par_Sql), $obBD->conexion);
	}
	/**
	* Realiza una consulta en la base de datos -  STARDARD
	*
	* @param int $sen_sql numero de la sql
	* @param string $param cadena de valores para el filtrado de la busqueda
	* @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	* @return result si existen datos de retorno
	*/
	function operacionobBD($sen_sql,$param, $obBD = null)
	{
		//echo '<br>- '.$sen_sql;
		$Par_Sql= $this->parametros($param);
		return $this->grabarv_registros(sentencias_comf($sen_sql,$Par_Sql), $obBD->conexion);
	}
	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_Con $obBD para realizar la conexcion correspondiente
	 * @return array $row fila de datos
	 */
	function getRowConsulta($sen_sql,$param,$obBD = null)
	{
		$result = $this->consultasobBD($sen_sql,$param,$obBD);
		$row =  $this->fetch_assoc($result);
		$this->free_result($result);
		return is_array($row) ? $row : array();
	}
	/**
	 * Ejecuta cualquier consulta a la base de datos -  STARDARD
	 * @param int $sen_sql numero de la sql
	 * @param string $param cadena de valores para el filtrado de la busqueda
	 * @param Class_Log_Conexion_pac $obBD para realizar la conexcion correspondiente
	 * @param Class_Log_Datos_Con $obDT para la abtraccion de los datos
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
	 * @param Class_Log_Datos_Con $obBD objeto de conexion
	 */
	function insertUpdateDelete($sen_sql,$param, $obBD = null)
	{		
		$this->inicio_transaccion($obBD->conexion);
		//Realiza Insert, Update o Delete
		$this->operacionobBD($sen_sql,$param,$obBD);
		$this->fin_transaccion($obBD->conexion);		
	}
	/**
	 * graba en la base de datos auditoria
	 * @param string $Request_Uri pagina donde se estan modificando valores
	 * @param number $Ses_Usu_Cod codigo del usuario
	 * @param Class_Log_Conexion $obBD_conexion
	 * @return number codigo de error my sql si lo hubiese [0 = 'Sin errores']
	 */
	function grabarAuditoria($Request_Uri, $Ses_Usu_Cod, $obBD_conexion){
		if($this->Error == 0){
			$objAud = new Class_Log_Datos_Aud;
			$aux = explode('*', $objAud->grabarAuditoria($Request_Uri, $Ses_Usu_Cod, $this, $obBD_conexion));
			foreach ($aux as $row){
				$this->grabarv_registros($row,$obBD_conexion->conexion);
				if($this->Error > 0){
					return $this->Error;
				}
			}
			$objAud->GuardarCierreSesion($_SESSION['Ses_Ses_Cod'], date('Y-m-d H:i:s'), $Ses_Usu_Cod);
		}else{
			return $this->Error;
		}
	}
	/**
	 * Formato standar para reportes
	 * @param int $sucursal CÃ³digo de la sucursal
	 * @param string $titulo TÃ­tulo del reporte
	 * @param string $subtitulo Subtitulo del reporte
	 */	
	function cabeceraReporteStandar($sucursal, $titulo, $subtitulo,$obBD)
	{ 
		/* Consulta de la cabecera del reporte */
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);
		/* Consulta la provicia y pais de la sucursal */
		$row_provincia = $this->getRowConsulta(3, $row_institucion['Ciu_Cod'], $obBD);	
		/* Consulta datos systema */
		$row_system = $this->getRowConsulta(7,'', $obBD);	
	?>
		<table width="90%" border="0" cellpadding="0" cellspacing="0">
		  <tr align="center">
		    <td width="5%" rowspan="6" valign="top"><img src="<?php echo $row_institucion['Emp_Log']; ?>" width="83" height="67" /></td>
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
		    <td valign="top" class="Texto_Reporte"><div align="center"><?Php 
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
		    <td align="center" valign="top" class="Texto_Reporte"><div align="center"><?php echo $row_system['Sys_Tit'];?></div></td>
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
		$row_institucion = $this->getRowConsulta(126, $sucursal, $obBD);	
		/* Consulta los datos del usuario */
		$row_usuario = $this->getRowConsulta(4, $usuario, $obBD);
		$fecha=explode("-",date("Y-m-d"));	
   	    $fechaHoy =	$row_institucion['Ciu_Des'].", ".$fecha[2]." de ".mes($fecha[1],1)." ".$fecha[0].", ".date("H:m:s") ;	
	?>
		<table width="90%" border="0" cellpadding="0" cellspacing="0">
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
	/**
	 * Codigo de los comprobantes
	 * @param int $Tia_Cod Tipo de comprobante
	 * @param int $Pec_Cod periodo contable
     * @param int $mes mes
	 */		
	function codigoComprAuto($Tia_Cod, $Pec_Cod, $mes, $obBD_conexion)
  	{			
		/* 
		* CodificaciÃ³n numerica en base al periodo contable y mensualmente 
		*/
		$row_rs_numcom = $this->getRowConsulta(152, $Tia_Cod.'*'.$Pec_Cod.'*'.$mes, $obBD_conexion);
		// Revisar la condiciÃ³n (todo funciona correctamente pero con artificio)
		if ((count($row_rs_numcom) > 0) && ($row_rs_numcom['Com_Num'] != ''))
		{
			$Com_Num=$row_rs_numcom['Com_Num'];
		} else {
			$Com_Num=1;
		}					
		return $Com_Num;
  	}
        /**
	 * Codigo de los comprobantes
	 * @param int $Tia_Cod Tipo de comprobante
	 * @param int $Pec_Cod periodo contable
         * @param int $mes mes
	 */		
	function codigoSecMensualAuto($PeCod, $mese, $obBD_conexion)
  	{			
		/* 
		* CodificaciÃ³n numerica en base al periodo contable y mensualmente 
		*/
		$row_rs_numcom = $this->getRowConsulta(1082,$PeCod.'*'.$mese, $obBD_conexion);
		// Revisar la condiciÃ³n (todo funciona correctamente pero con artificio)
		if ((count($row_rs_numcom) > 0) && ($row_rs_numcom['Com_Num'] != ''))
		{
			$Com_Num=$row_rs_numcom['Com_Num'];
		} else {
			$Com_Num=1;
		}					
		return $Com_Num;
  	}
	/** 
	* Funcion que devuelve un arreglo de los reportes del proceso 
	*/
	function reportes($pagina, $empresa, $obBD_conexion)
	{
		$pag = explode("/", $pagina);
		$row_rs_proceso = $this->getRowConsulta(12, $pag[count($pag)-1], $obBD_conexion);			
		$row_rs_reporte = $this->getArrayConsulta(13, $row_rs_proceso['Pcs_Cod'].'*'.$empresa, $obBD_conexion);
		$i=0;
		foreach ($row_rs_reporte as $row)
		{
			$i++;
			$reporte[$i] = $row['Rut_Des'].$row['Pcs_Nom'];		
		}		
		return $reporte;
	}	
	/**
	* Anular compras
	* @Cop_Cod: CÃ³digo de la compra
	* @Ret_Cod: CÃ³digo de la retenciÃ³n
	* @Com_Cod: CÃ³digo del comprobante contable
	*/
	function anularCompras($Cop_Cod, $Ret_Cod, $Com_Cod,$SesSucCod, $obBD_conexion)
	{
		/**
		* inicio de la transaccion 
		*/
		$this->inicio_transaccion($obBD_conexion->conexion);
		/**
		* Inactiva el estado de la compra
		*/
		$this->operacionobBD(471, $Cop_Cod.'*'.'I', $obBD_conexion);	
		/*
		* Inactivo el KARDEX
		*/
		$this->operacionobBD(1101, $Cop_Cod.'*'.'I', $obBD_conexion);
		$row_detalle = $this->getArrayConsulta(723, $Cop_Cod, $obBD_conexion);
		/**
		* Control de inventario
		*/
		foreach ($row_detalle as $row)
		{
			/**
			* Consulta el Stock 
			*/
			$row_rs_conpro = $this->getRowConsulta(1206, $row['Pro_Cod'], $obBD_conexion);
			$tstock= $row_rs_conpro['Stock'];
			/**
			* Actualizo el Stock 
			*/
			$this->operacionobBD(1204, $tstock.'*'.$row['Pro_Cod'].'*'.$SesSucCod, $obBD_conexion);	
		}
		/**
		* Verifica si tiene una retencion asociada
		*/
		if($Ret_Cod>0)
		{  	
			/**
			* Dar de baja a la retenciÃ³n perteneciente a la factura dada de baja 
			*/
				$this->operacionobBD(510, $Cop_Cod.'*'.'I', $obBD_conexion);
		}
		/**
		* Verifica si tiene un comprobante contable asociado
		*/
		if($Com_Cod>0)
		{  	
			/**
			* Baja lÃ³gica del comprobante de contable 
			*/
			$this->operacionobBD(359, $Com_Cod.'*'.'I', $obBD_conexion);
		}
		$this->fin_transaccion($obBD_conexion->conexion);		
	}
    /** 
	* CÃ¡lculos compras con I.C.E. 
	*/
	function calculosCompraIce($Cop_Cod, $obBD_conexion)
	{	
		/**
		* Opciones para el retorno 
		* 0 = SUBTOTAL
		* 1 = TARIFA 0
		* 2 = TARIFA 12
		* 3 = IVA
		* 4 = DESCUENTO
		* 5 = TOTAL 
		* 6= ICE
		*/
		$rs_calculos_comp = $this->getArrayConsulta(473, $Cop_Cod, $obBD_conexion);
		$Imp_Ice=0; $subtotal=0;
		$total=0;
		$iva_12=0; $Ice_Comp=0;
		$tarifa_0=0; $des_0=0;
		$tarifa_12=0; $des_12=0;
		foreach($rs_calculos_comp as $row_rs_calculos_comp)
		{		
			/**
			* % de Descuento total 
			*/
			$Cop_Des = $row_rs_calculos_comp['Cop_Des'];			
			/**
			* Calculo del total de la factura 
			*/
			$subtotal= $subtotal + $row_rs_calculos_comp['Cop_Imp'];				
			/**
			* Calculo de las tarifas 
			*/
			if ($row_rs_calculos_comp['Iva_Por'] == 0)
			{
				$tarifa_0 = $tarifa_0 + $row_rs_calculos_comp['Cop_Imp'];
				/**
				* Descuento individual 
				*/
				$des_0 = $des_0 + ($row_rs_calculos_comp['Cop_Imp'] * $row_rs_calculos_comp['Cop_Dec'])/100;
			}
			else
			{
				$tarifa_12 = $tarifa_12 + $row_rs_calculos_comp['Cop_Imp'];
				/**
				* Descuento individual 
				*/
				$des_12 = $des_12 + ($row_rs_calculos_comp['Cop_Imp'] * $row_rs_calculos_comp['Cop_Dec'])/100;			
				$iva_12 = $row_rs_calculos_comp['Iva_Por'];
			}
			 /**
			 * Consulta los datos del ICE
			 */
			 $row_porciento= $this->getRowConsulta(527,$row_rs_calculos_comp['Cop_Int'].'*'.$Cop_Cod, $obBD_conexion); 
			if($Cop_Des==0) 
			{
				if ($row_porciento['Ice_Por']!=NULL && $row_porciento['Ice_Por']>0)
				{ 
					$Ice_Comp=$row_rs_calculos_comp['Cop_Imp'];
					$des_ice = ($row_rs_calculos_comp['Cop_Imp'] * $row_rs_calculos_comp['Cop_Dec'])/100;
					$bas_ice= (($Ice_Comp-$des_ice)*$row_porciento['Ice_Por'])/100;
					$Imp_Ice=$Imp_Ice+$bas_ice;
				}
			}
			else
			{
				if ($row_porciento['Ice_Por']!=NULL && $row_porciento['Ice_Por']>0)
				{
					$Ice_Comp=$row_rs_calculos_comp['Cop_Imp'];
					$des_ice = ($row_rs_calculos_comp['Cop_Imp'] * $row_rs_calculos_comp['Cop_Des'])/100;
					$bas_ice= (($Ice_Comp-$des_ice)*$row_porciento['Ice_Por'])/100;
					$Imp_Ice=$Imp_Ice+$bas_ice;
				}
			}
		/*if(isset($rs_porciento_ice))
		{
		}*/
		}//FIn del foreach
		/**
		* Suma del descuento 
		*/
		$des = $des_0 + $des_12;
		/**
		* calculo del iva con descuento individual 
		*/
		$iva = (($tarifa_12 - $des_12) * $iva_12)/100;
		/**
		* Calculo del descuento total 
		*/
		if ($Cop_Des != 0)
		{
			$des = ($subtotal * $Cop_Des) / 100;
			$des_12 = ($tarifa_12 * $Cop_Des) / 100;
			$iva = (($tarifa_12 - $des) * $iva_12)/100;	//Antes estaba des_12	
		}	 
		/**
		* Calculo del total 
		*/
		//$total = (number_format($subtotal,2) - number_format($des,2)) + (number_format($iva,2) + number_format($Imp_Ice,2));
		$total = ($subtotal - $des) + ($iva + $Imp_Ice);		
		return ($subtotal.'*'.$tarifa_0.'*'.$tarifa_12.'*'.$iva.'*'.$des.'*'.formato_numero($total,2,1).'*'.$Imp_Ice);		
	}		
}
?>
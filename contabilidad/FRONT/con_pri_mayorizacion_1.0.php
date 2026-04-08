<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?	
/**
* Descripción: Permite consultar la mayorizacion contable
* Fecha de actualización:	2010-11-15 
* Desarrollador:	Lewis Chimarro 
*/
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/con_log_mayorizacion.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');

/**
* Creacion del Objeto de conexion 
*/
$obBD_conexion = new Class_Log_Conexion_Con($Ses_Dat_Dis);
/**
* Cracion del objeto mysql para las consultas 
*/
$obBD_con1 =  new Class_Log_Datos_Con;

$hoy = date("Y-m-d");

/**
* OPCIONES 
*/
switch ($op){
	case 1: 
	/**
	* Cargado de los datos de la cabecera 
	*/
	if ($txt_busqueda != "")
	{
		/**
		* Consulta  el codigo interno de la cuenta contable
		*/
		$rs_cuenta_manual = $obBD_con1->consulta(sentencias_con(209, $obBD_con1->parametros(trim($txt_busqueda).'*'.$Pla_Cod)), $obBD_conexion->conexion);
		$row_rs_cuenta_manual = $obBD_con1->registros();
		$total_rs_cuenta = $obBD_con1->numregistros();
	    $Pld_Cod = $row_rs_cuenta_manual['Pld_Cod'];		
		/**
		* Consulta del saldo, anterior a la inicial 
		*/
		$fech_fut = fechas_futuras($txt_fec_ini, -1);	
		$rs_saldos = $obBD_con1->consulta(sentencias_con(202, $obBD_con1->parametros($fech_fut.'*'.$Pld_Cod.'*'.$Pec_Cod))
										, $obBD_conexion->conexion);
		$row_rs_saldos = $obBD_con1->registros();
		$total_rs_saldos = $obBD_con1->numregistros();
	
		/**
		* Se realiza esto porque solo deben haber dos registros 
		*/	
		/**
		* De los dos supuestos registros encontrados toma por defecto el primero 
		*/
		if ($row_rs_saldos['Asi_Deh'] == 'D')
		{
			$debe = $row_rs_saldos['Asi_Val'];
		   /**
		   * Mueve el puntero al inicio 
		   */
		   $row_rs_saldos = first_last($rs_saldos, $row_rs_saldos, 1);			
		}
		else
		{
			$debe = 0;
		}
	
		$haber= $row_rs_saldos['Asi_Val'];

		$tipo_grupo = explode('.', $txt_busqueda);
		/**
		* 1 = Activo
		* 2 = Pasivo
		* 3 = Patrimonio
		* 4 = Ingresos
		* 5 = Costos y Gastos 
		*/
		if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4)//Nuevo
		{//Nuevo
			$saldos = $haber - $debe; //Formula especial			
		}//Nuevo
		else //Nuevo
		{//Nuevo			
			$saldos = $debe - $haber;
		}//Nuevo
		
		/**
		* Consulta del detalle de la mayorizacin 
		*/
		$rs_cuenta = $obBD_con1->consulta(sentencias_con(201, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$Pld_Cod.'*'.$ordenar.'*'.$Pec_Cod))
										, $obBD_conexion->conexion);
		$row_rs_cuenta = $obBD_con1->registros();
		$total_rs_cuenta = $obBD_con1->numregistros();		

		/**
		* Carga el año de la fecha incial 
		*/
		list($ann, $mes, $dia) = split('[/.-]', $fech_fut);
		$anio = date("Y", mktime(0,0,0,$mes,$dia,$ann));
	}//Fin del if ($txt_busqueda != "")
	break;

	case 2:
	if ($grupo != "")
	{
		/**
		* Consulta el codigo interno de la cuenta inicial 
		*/
		$rs_cuenta_int = $obBD_con1->consulta(sentencias_con(216, $obBD_con1->parametros(trim($grupo).'*'.$Pla_Cod)), $obBD_conexion->conexion);
		$row_rs_cuenta_int = $obBD_con1->registros();
		$Pld_Cod= $row_rs_cuenta_int['Pld_Cod'];

		/**
		* Consulta del rango de cuentas para la busqueda 
		*/
		$rs_rango = $obBD_con1->consulta(sentencias_con(203, $obBD_con1->parametros($Pld_Cod)), $obBD_conexion->conexion);
		$row_rs_rango = $obBD_con1->registros();
		$total_rs_rango = $obBD_con1->numregistros();
	}		
	break;
}//FIn del case $op
?>
<HTML>
	<HEAD>
		<TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE>
	    <?Php require_once("../../mascaras/model1/estilos/print.php"); ?>    
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	</HEAD>
<BODY>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	 <tr align="center" class="Titulos3">
	   <td><table width="80%" border="0" align="center" cellpadding="0" cellspacing="0">
	     <tr align="center">
	       <td colspan="4">&nbsp;
	         <?php $obBD_con1->cabeceraReporteStandar($Ses_Suc_Cod, "Mayorización General ".$periodo, " ", $obBD_conexion); ?>
	         &nbsp;</td>
         </tr>
       </table></td>
  </tr>
	<tr>
        <td valign="top">
<?Php 
switch ($op){
	case 1:
		if (isset($txt_busqueda))
		{
			$total_debe = 0;
			$total_haber = 0;
			/**
			* Consulta del detallete de la CUENTA 
			*/
			$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_cuenta_manual['Pld_Rec'])), $obBD_conexion->conexion);
			$row_rs_recur = $obBD_con1->registros();				
?>
  <table width="669" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
    <tr>
      <td width="43" class="Texto_Reporte"><div align="right"><strong>Desde:</strong></div></td>
      <td width="182">&nbsp;<?Php echo $txt_fec_ini; ?></td>
      <td width="64" class="Texto_Reporte"><div align="right"><strong>Hasta:</strong></div></td>
      <td width="198">&nbsp;<?Php echo $txt_fec_fin; ?></td>
      <td width="55">&nbsp;</td>
      <td width="101">&nbsp;</td>
    </tr>
    <tr>
      <td class="Texto_Reporte"><div align="right"><strong>Código:</strong></div></td>
      <td>&nbsp;<?Php echo $row_rs_recur['Pld_Cdc']; ?></td>
      <td class="Texto_Reporte"><div align="right"><strong>GRUPO:</strong></div></td>
      <td>&nbsp;<?Php echo $row_rs_recur['Pld_Des']; ?></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td class="Texto_Reporte"><div align="right"><strong>C&oacute;digo:</strong></div></td>
      <td>&nbsp;<?Php echo $row_rs_cuenta_manual['Pld_Cdc']; ?></td>
      <td class="Texto_Reporte"><div align="right"><strong>Cuenta:</strong></div></td>
      <td>&nbsp;<?Php echo $row_rs_cuenta_manual['Pld_Des']; ?></td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
    </tr>
  </table>
  <table width="100%"  border="1" cellpadding="0" cellspacing="0" style="border-collapse:collapse">
    <tr class="Texto_Listados">
      <td width="11%" align="center" bgcolor="#CCCCCC"><strong>Gen.</strong></td>
      <td width="10%" align="center" bgcolor="#CCCCCC"><div align="center"><strong>No. Com.</strong></div></td>
      <td width="10%" align="center" bgcolor="#CCCCCC"><div align="center"><strong>Fecha</strong></div></td>
      <td width="16%" align="center" bgcolor="#CCCCCC"><strong>Proveedor</strong></td>
      <td width="20%" bgcolor="#CCCCCC"><div align="center"><strong>Detalle</strong></div></td>
      <td width="11%" align="center" bgcolor="#CCCCCC"><div align="center"><strong>Debe</strong></div></td>
      <td width="11%" align="center" bgcolor="#CCCCCC"><div align="center"><strong>Haber</strong></div></td>
      <td width="11%" align="center" bgcolor="#CCCCCC"><div align="center"><strong>Saldo</strong></div></td>	  
    </tr>
	<?Php
	if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
	?>
    <tr class="Texto_Reporte">
      <td align="center">&nbsp;</td>
      <td align="center">&nbsp;</td>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td class="Texto_Listados">SALDO AL  <?php echo $dia.', de '.mes($mes, 1).', '.$anio; ?></td>
      <td align="right">&nbsp;</td>
      <td align="right">&nbsp;</td>
      <td align="right" <?Php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>><?Php echo formato_numero($saldos, 2, 2); ?></td>
    </tr>
    <?	  
	  do { 
	  		/**
			* Consulta del cliente o proveedor 
			*/
			if ($row_rs_cuenta['Tia_Ini'] == 'I')
			{
				/**
				* Consulta la descripcion del cliente 
				*/
				$rs_proveedore = $obBD_con1->consulta(sentencias_con(217, $obBD_con1->parametros($row_rs_cuenta['Cli_Cod'])), $obBD_conexion->conexion);
				$row_rs_proveedore = $obBD_con1->registros();
				$total_rs_proveedore = $obBD_con1->numregistros();							
			}
			else
			{
				/**
				* Consulta la descripcion del proveedor 
				*/
				$rs_proveedore = $obBD_con1->consulta(sentencias_con(218, $obBD_con1->parametros($row_rs_cuenta['Prv_Cod'])), $obBD_conexion->conexion);
				$row_rs_proveedore = $obBD_con1->registros();
				$total_rs_proveedore = $obBD_con1->numregistros();					
			}//Fin del if ($row_rs_cuenta['Tia_Ini'] == 'I')
	  ?>	
    <tr class="Texto_Listados">
      <td align="center"><?Php echo $row_rs_cuenta['Com_Gen']; ?></td>
      <td align="center"><?Php echo  "C".$row_rs_cuenta['Tia_Ini']."-".$mes."-".$row_rs_cuenta['Com_Num']; ?>&nbsp;</td>
      <td align="center"><?Php echo $row_rs_cuenta['Com_Fec']; ?>&nbsp;</td>
      <td align="left"><?Php echo $row_rs_proveedore['Prs_Ape'].' '.$row_rs_proveedore['Prs_Nom']; ?></td>
      <td><? echo cadena_mas($row_rs_cuenta['Com_Con'], 35); ?>&nbsp;</td>	  	  
   	  <td align="right">&nbsp;
   	    <? if ($row_rs_cuenta['Asi_Deh'] == 'D')
	  					{
							echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2); 
							$debe = $row_rs_cuenta['Asi_Val'];
							$total_debe = $total_debe + $debe;							
						} 
						else 
						{ 
							echo "0.00"; 
							$debe = 0;
						}?></td>
      <td align="right">&nbsp;
        <? if ($row_rs_cuenta['Asi_Deh'] == 'H')
	  					{
							echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2); 
							$haber = $row_rs_cuenta['Asi_Val'];
							$total_haber = $total_haber + $haber;							
						} 
						else 
						{ 
							echo "0.00"; 
							$haber = 0;
						}
			?></td>
			<?Php 
			$tipo_grupo = explode('.', $txt_busqueda);
			/**
			* 1 = Activo
			* 2 = Pasivo
			* 3 = Patrimonio
			* 4 = Ingresos
			* 5 = Costos y Gastos 
			*/
			if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4)//Nuevo
			{//Nuevo
				$saldos = $saldos + ($haber - $debe); //Formula especial			
			}//Nuevo
			else //Nuevo
			{//Nuevo			
				$saldos = $saldos + ($debe - $haber);
			}//Nuevo			
			?>
	  <td  align="right" <?Php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>><?Php echo formato_numero($saldos, 2, 2);
	  		?></td>
    </tr>
	<?Php
	 } while ($row_rs_cuenta = $obBD_con1->fetch_assoc($rs_cuenta));
	?>	
    <tr class="Texto_Listados">
      <td colspan="5" bgcolor="#FFFFFF"><strong><div align="right">TOTAL</div></strong></td>
      <td align="right" bgcolor="#FFFFFF"><strong><?Php echo formato_numero($total_debe, 2, 2);?></strong></td>
      <td align="right" bgcolor="#FFFFFF"><strong><?Php echo formato_numero($total_haber, 2, 2);?></strong></td>
      <td align="right" bgcolor="#FFFFFF">&nbsp;</td>
    </tr>
    <?Php
	  } else { ?>
    <tr>
      <td colspan="8"><?Php echo error_alerta(" No hay resultados que mostrar", 1) ?></td>
    </tr>
    <? } //Fin del else	
	} //Fin del if ($txt_busqueda)
	?>
  </table>  
<?Php 	
	break;
	case 2:
		if (isset($grupo))
		{		
?>
  <table width="671" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
    <tr>
      <td width="40" class="Texto_Reporte"><div align="right"><strong>Desde</strong>:</div></td>
      <td width="186">&nbsp;<?Php echo $txt_fec_ini; ?></td>
      <td width="62" class="Texto_Reporte"><div align="right"><strong>Hasta</strong>:</div></td>
      <td width="200">&nbsp;<?Php echo $txt_fec_fin; ?></td>
      <td width="58"><div align="right"></div></td>
      <td width="99">&nbsp;</td>
    </tr>
  </table>
  <hr>
  <?Php 
  if ($total_rs_rango > 0){
  $x=0;//ojo
	  do{
		  $x++;//ojo
  			$total_debe = 0;
			$total_haber = 0;
			$saldo = 0;
			/**
			* Consulta del saldo, anterior a la inicial 
			*/		
			$fech_fut = fechas_futuras($txt_fec_ini, -1);	
			$rs_saldos = $obBD_con1->consulta(sentencias_con(202, $obBD_con1->parametros($fech_fut.'*'.$row_rs_rango['Pld_Cod'].'*'.$Pec_Cod)), 
								$obBD_conexion->conexion);
			$row_rs_saldos = $obBD_con1->registros();
			$total_rs_saldos = $obBD_con1->numregistros();
			/**
			* Se realiza esto porque solo deben haber dos registros 
			*/	
			/**
			* De los dos supuestos registros encontrados toma por defecto el primero 
			*/
			if ($row_rs_saldos['Asi_Deh'] == 'D')
			{
				$debe = $row_rs_saldos['Asi_Val'];
			   /**
			   * Mueve el puntero al inicio 
			   */
			   $row_rs_saldos = first_last($rs_saldos, $row_rs_saldos, 1);								
			}
			else
			{
				$debe = 0;
			}
		
			$haber= $row_rs_saldos['Asi_Val'];
			$tipo_grupo = explode('.', $grupo);
			/**
			* 1 = Activo
			* 2 = Pasivo
			* 3 = Patrimonio
			* 4 = Ingresos
			* 5 = Costos y Gastos 
			*/
			if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4)//Nuevo
			{//Nuevo
				$saldos = $haber - $debe; //Formula especial			
			}//Nuevo
			else //Nuevo
			{//Nuevo			
				$saldos = $debe - $haber;
			}//Nuevo						
			
			/**
			* Consulta del detalle de la mayorizacin 
			*/  
			$rs_cuenta = $obBD_con1->consulta(sentencias_con(201, $obBD_con1->parametros($txt_fec_ini.'*'.$txt_fec_fin.'*'.$row_rs_rango['Pld_Cod'].'*'.$ordenar.'*'.
							$Pec_Cod)), $obBD_conexion->conexion);
			$row_rs_cuenta = $obBD_con1->registros();
			$total_rs_cuenta = $obBD_con1->numregistros();		
		
			/* Carga el año de la fecha incial */
			list($ann, $mes, $dia) = split('[/.-]', $fech_fut);
			$anio = date("Y", mktime(0,0,0,$mes,$dia,$ann));

			if ($total_rs_cuenta > 0 or $total_rs_saldos > 0) {
				/**
				* Consulta del detallete de la CUENTA 
				*/
				$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_rango['Pld_Rec'])), $obBD_conexion->conexion);
				$row_rs_recur = $obBD_con1->registros();				
			?>
  <table width="508" border="0" cellpadding="0" cellspacing="0" class="Texto_Reporte">
				    <tr>
				      <td class="Texto_Reporte"><div align="right"><strong>C&oacute;digo:</strong></div></td>
				      <td>&nbsp;<?Php echo $row_rs_recur['Pld_Cdc']; ?></td>
				      <td class="Texto_Reporte"><div align="right"><strong>GRUPO:</strong></div></td>
				      <td><?Php echo $row_rs_recur['Pld_Des']; ?>&nbsp;</td>
		          </tr>
				    <tr>	
					      <td width="44" class="Texto_Reporte"><div align="right"><strong>C&oacute;digo:</strong></div></td>
					      <td width="181">&nbsp;<?Php echo $row_rs_rango['Pld_Cdc']; ?></td>
					      <td width="65" class="Texto_Reporte"><div align="right"><strong>Cuenta:</strong></div></td>
					      <td width="200">&nbsp;<?Php echo $row_rs_rango['Pld_Des']; ?></td>
		            </tr>
		</table>
  
				<table width="100%" border="1" cellpadding="0" cellspacing="0" id="tabla2[<?php echo $x; ?>]">
	    <tr class="Texto_Reporte Tabla_Cab_Print">
	      <td align="center" class="Texto_Reporte" width="11%"><div align="center"><strong>Gen.</strong></div></td>
					      <td align="center" class="Texto_Reporte" width="10%"><div align="center"><strong>No. Com.</strong></div></td>
					      <td align="center" class="Texto_Reporte" width="10%"><div align="center"><strong>Fecha</strong></div></td>
					      <td align="center" class="Texto_Reporte" width="16%"><div align="center"><strong>Proveedor</strong></div></td>
					      <td class="Texto_Reporte" width="20%"><div align="center"><strong>Detalle</strong></div></td>
					      <td align="center" class="Texto_Reporte" width="11%"><div align="center"><strong>Debe</strong></div></td>
					      <td align="center" class="Texto_Reporte" width="11%"><div align="center"><strong>Haber</strong></div></td>
					      <td align="center" class="Texto_Reporte" width="11%"><div align="center"><strong>Saldo</strong></div></td>	  
				    </tr>	
				    <tr class="Texto_Reporte">
				      <td align="center">&nbsp;</td>
					      <td align="center">&nbsp;</td>
					      <td>&nbsp;</td>
					      <td>&nbsp;</td>
					      <td>SALDO AL <?php echo $dia.', de '.mes($mes, 1).', '.$anio; ?></td>
					      <td align="right">&nbsp;</td>
					      <td align="right">&nbsp;</td>
					      <td align="right" <?Php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>><?Php 
	  										echo formato_numero($saldos, 2, 2); ?></td>
				    </tr>
				    <?	  
					  do { 
						/**
						* Consulta del cliente o proveedor 
						*/
						if ($row_rs_cuenta['Tia_Ini'] == 'I')
						{
							/**
							* Consulta la descripcion del cliente 
							*/
							$rs_proveedore = $obBD_con1->consulta(sentencias_con(217, $obBD_con1->parametros($row_rs_cuenta['Cli_Cod'])), $obBD_conexion->conexion);
							$row_rs_proveedore = $obBD_con1->registros();
							$total_rs_proveedore = $obBD_con1->numregistros();							
						}
						else
						{
							/**
							* Consulta la descripcion del proveedor 
							*/
							$rs_proveedore = $obBD_con1->consulta(sentencias_con(218, $obBD_con1->parametros($row_rs_cuenta['Prv_Cod'])), $obBD_conexion->conexion);
							$row_rs_proveedore = $obBD_con1->registros();
							$total_rs_proveedore = $obBD_con1->numregistros();					
						}//Fin del if ($row_rs_cuenta['Tia_Ini'] == 'I')					  
					  ?>	
					    <tr class="Texto_Reporte">
					      <td align="center"><?Php echo $row_rs_cuenta['Com_Gen']; ?></td>
						      <td align="center"><?Php echo  "C".$row_rs_cuenta['Tia_Ini']."-".$mes."-".$row_rs_cuenta['Com_Num']; ?>&nbsp;</td>
						      <td align="center"><?Php echo $row_rs_cuenta['Com_Fec']; ?>&nbsp;</td>	  	  
						      <td align="left"><?Php echo $row_rs_proveedore['Prs_Ape'].' '.$row_rs_proveedore['Prs_Nom']; ?></td>
						      <td><? echo cadena_mas($row_rs_cuenta['Com_Con'], 35); ?>&nbsp;</td>	  	  
						   	  <td width="9%" align="right"><? if ($row_rs_cuenta['Asi_Deh'] == 'D')
							  					{
													echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2); 
													$debe = $row_rs_cuenta['Asi_Val'];
													$total_debe = $total_debe + $debe;							
												} 
												else 
												{ 
													echo "0.00"; 
													$debe = 0;
												}?></td>
						      <td align="right"><? if ($row_rs_cuenta['Asi_Deh'] == 'H')
	  											{
													echo formato_numero($row_rs_cuenta['Asi_Val'], 2, 2); 
													$haber = $row_rs_cuenta['Asi_Val'];
													$total_haber = $total_haber + $haber;							
												} 
												else 
												{ 
													echo "0.00"; 
													$haber = 0;
												}
												?></td>
												<?Php 
												$tipo_grupo = explode('.', $grupo);
												/**
												* 1 = Activo
												* 2 = Pasivo
												* 3 = Patrimonio
												* 4 = Ingresos
												* 5 = Costos y Gastos 
												*/
												if ($tipo_grupo[0] == 2 || $tipo_grupo[0] == 3 || $tipo_grupo[0] == 4)//Nuevo
												{//Nuevo
													$saldos = $saldos + ($haber - $debe); //Formula especial			
												}//Nuevo
												else //Nuevo
												{//Nuevo			
													$saldos = $saldos + ($debe - $haber);
												}//Nuevo												
												?>
							  <td align="right" <?Php if ($saldos<0){ echo "style='color:#FF0000'"; } ?>><?Php 
									  			echo formato_numero($saldos, 2, 2);
										  		?></td>
					    </tr>
					<?Php
					 } while ($row_rs_cuenta = $obBD_con1->fetch_assoc($rs_cuenta));
					?>	
				    <tr class="Texto_Reporte Tabla_Cab_Print">
					      <td colspan="5" class="TITULO_REPORTE"><div align="right">TOTAL</div></td>
					      <td align="right"><?Php echo formato_numero($total_debe, 2, 2);?></td>
					      <td align="right"><?Php echo formato_numero($total_haber, 2, 2);?></td>
					      <td align="right">&nbsp;</td>
				    </tr>
		  </table>
				<br>
		<?Php
		 	}// Fin del if ($total_rs_cuenta > 0)
	  } while ($row_rs_rango = $obBD_con1->fetch_assoc($rs_rango));
  } //Fin del if ($total_rs_rango > 0)
  ?>
  
<?Php	
 }//Fin del if (isset($grupo))
	break;
} //Fin del switch
	/* Muestra u oculta el buscador */
?>	 </td>
  </tr>
	<tr>
	  <td align="center"><?Php $obBD_con1->pieReporteStandar($Ses_Suc_Cod, $Ses_Usu_Cod, $obBD_conexion); ?></td>
  </tr>
</table>	  
</BODY></HTML>
<?Php 
@$obBD_con1->free_result($rs_saldos);
@$obBD_con1->free_result($rs_cuenta);
@$obBD_con1->free_result($rs_rango);
@$obBD_con1->free_result($rs_recur);
@$obBD_con1->free_result($rs_cuenta_plan);
@$obBD_con1->free_result($rs_cuenta_int);
@$obBD_con1->free_result($rs_cuenta_manual);
@$obBD_con1->free_result($rs_proveedore);
@$obBD_con1->liberar();
@$obBD_conexion->cerrar();
?>
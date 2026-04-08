<?Php
/*Componente que pemite listar las facturas que tiene pendiente un proveedor en especifico-*/
if (isset($codigo))
{		
	if(isset($Pec_Cod))
	{
		if(isset($hoy))
		{
		/*consulta de las facturas del proveedor*/
		$rs_factura = $obBD_con1->consulta(sentencias_tes(811, $obBD_con1->parametros($codigo.'*'.$Pec_Cod.'*'.$cmb_mes)), $obBD_conexion->conexion);	
		$row_rs_factura = $obBD_con1->registros();
		$total_rs_factura = $obBD_con1->numregistros();	
	
		/*En esta consulta debe botar un solo registro ya en un año contable normalmente se utiliza un plan de cuentas */
		$rs_cuenta_manual = $obBD_con1->consulta(sentencias_tes(248, $obBD_con1->parametros($Pec_Cod)), $obBD_conexion->conexion);
		$row_rs_cuenta_manual = $obBD_con1->registros();
		$Pla_Cod = $row_rs_cuenta_manual['Pla_Cod'];	
	
?>
<FIELDSET>
			<LEGEND>
			<label class="Titulos2">Facturas a cr&eacute;dito </label>
			</LEGEND>
				<table width="100%" border="1" cellpadding="0" cellspacing="0">
				  <tr class="Cabecera1">
					   <td width="4%">C&oacute;d. Int.</td>
					   <td width="15%">No. Factura </td>
					   <td width="15%">Fecha de emisi&oacute;n</td>
					   <td width="15%">Fecha de vencimiento </td>
					   <td width="15%" >Dias de vencimiento </td>
					   <td width="8%">Valor</td>
					   <td width="8%">Saldo</td>
				       <td width="4%">&nbsp;</td>
				  </tr>
				  <?Php 
				  $i=0;
				if($total_rs_factura != 0)
				{	  
				  $cnt=0;
				  do { 
				 $i++;
				  ?>
				  <tr class="Fondo">
				  <?php
					/*consulta de pagos echos a un factura de a credito*/
					$rs_pago = $obBD_con1->consulta(sentencias_tes(804, $obBD_con1->parametros($row_rs_factura['Cop_Cod'])), $obBD_conexion->conexion);	
					$row_rs_pago = $obBD_con1->registros();
					$total_rs_pago = $obBD_con1->numregistros();	
					/*cONSULTA PARA OBTENER LOS DIAS QUE FALTAN PARA VENCER UNA FACTURA **/
					$rs_dias = $obBD_con1->consulta(sentencias_tes(808, $obBD_con1->parametros($row_rs_factura['Cpp_Ven'].'*'.$hoy)), $obBD_conexion->conexion);	
					$row_rs_dias = $obBD_con1->registros();
					$total_rs_dias = $obBD_con1->numregistros();
					/*Operación para obtner el saldo de la factura a credito*/
					$saldo= $row_rs_factura['Asi_Val'] - $row_rs_pago['total'];	
					$saldo_final=formato_numero($saldo,2,3);
					
					 $cnt=$cnt+1;
					 ?>
					<td width="4%" align="center"><?Php echo $row_rs_factura['Cop_Cod'];?></td>
					<td width="15%" align="center"><?Php echo $row_rs_factura['Cop_Num'];?></td>
					<td align="center"><?Php echo $row_rs_factura['Cop_Fec'];?>&nbsp;</td>
					<td align="center"><?Php echo $row_rs_factura['Cpp_Ven'];?>&nbsp;</td>
					<td align="center"  <?php if($row_rs_dias['dias']== 0) { echo "bgcolor='#00CCFF'";}  if($row_rs_dias['dias']== 1) { echo "bgcolor='#00FF99'";}  if($row_rs_dias['dias']== -1) { echo "bgcolor='#CCCCCC'";} ?>>&nbsp;
					<?php 
						
					if($row_rs_dias['dias']== 1) { echo "Mañana";} 
					if($row_rs_dias['dias']== 0) { echo "Hoy";}
					if($row_rs_dias['dias']== -1) { echo "Ayer";}
					if(($row_rs_dias['dias'] >1)){
						echo $row_rs_dias['dias']." dias ";
						}
					if(($row_rs_dias['dias'] < -1)){
					echo $row_rs_dias['dias']." dias ";	}
					?>					</td>
					<td align="right"><?Php echo formato_numero($row_rs_factura['Asi_Val'], 2, 3);?>&nbsp;</td>
					<td align="right"><?Php  echo $saldo_final;?>
					</td>
					<form method="post" name="formt" action="<?Php echo $_SERVER['PHP_SELF']; ?>">
					<td align="right">
					<input name="img_enviar" type="image" id="img_enviar" src="../../mascaras/model1/imagenes/forward.png" width="22" height="22">&nbsp;					
					
					<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo?>">
					<input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
					<input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">						   					<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">	
					<input name="Pla_Cod" id="Pla_Cod" type="hidden" value="<?Php echo $Pla_Cod;?>">	
					<input name="Cop_Fec" id="Cop_Fec" type="hidden" value="<?Php  echo $row_rs_factura['Cop_Fec'];?>">
					<input name="Cop_Num" id="Cop_Num" type="hidden" value="<?Php  echo $row_rs_factura['Cop_Num'];?>">
					<input name="Cpp_Ven" id="Cpp_Ven" type="hidden" value="<?Php  echo $row_rs_factura['Cpp_Ven'];?>">
					<input name="valor" id="valor" type="hidden" value="<?Php echo formato_numero($row_rs_factura['Asi_Val'], 2, 3);?>">
					<input name="Cpp_Cod" id="Cpp_Cod" type="hidden" value="<?Php  echo $row_rs_factura['Cpp_Cod'];?>">		
					<input name="Fec_Fin" id="Fec_Fin" type="hidden" value="<?Php echo $Fec_Fin;?>"> 
					<input name="Fec_Ini" id="Fec_Ini" type="hidden" value="<?Php echo $hoy;?>">
					<input name="hdd_detalle" id="hdd_detalle" type="hidden" value="1">
					<input name="Cop_Cod" id="Cop_Cod" type="hidden" value="<?Php  echo $row_rs_factura['Cop_Cod'];?>" />	
					</td>
					</form>
				
				  </tr>
				  <?Php } while ($row_rs_factura = $obBD_con1->fetch_assoc($rs_factura)); 
				}//FIn del if($total_rs_buscar != 0)
				else
				{ ?>
					<tr><td colspan="8">
					<?Php echo error_alerta("No hay resultados que mostrar para ".strtoupper($proveedor)." ".$periodo, 2); ?>
							
					</td></tr>	   
				<?Php
				}//Fin del else if($total_rs_buscar != 0) ?>	  
			  </table>
			  
</FIELDSET>
<!--fin de la tabla de facturas a credito-->
<?php 
@$obBD_con1->free_result($rs_factura);
 @$obBD_con1->free_result($rs_cuenta_manual);
 @$obBD_con1->free_result($rs_pago);
 @$obBD_con1->free_result($rs_diaS);
?>

<?php 
}// fin del if(isset($hoy)
	else
	{
		echo error_alerta("<< Error de componente: tes_com_fact_proveedor.php >> <br>Descripción: No se ha definido la Propiedad: 	
		Hoy<br> Hoy: Variable que contiene fecha de actual del sistema", 2); 	
	}

}// fin del if if(isset($Pec_Cod))
	
else
	{
		echo error_alerta("<< Error de componente: tes_com_fac_consulta.php >>  <br>Descripción: No se ha definido la Propiedad: 
		Pec_Cod<br>	Pec_Cod: Variable que contiene el código del periodo contable", 2); 							
	}
					
}//fin if (isset($codigo))

else
	{
		echo error_alerta("<< Error de componente: tes_com_fac_consulta.php >>  <br>Descripción: No se ha definido la Propiedad: 
		Pec_Cod<br>	Pec_Cod: Variable que contiene el código del periodo contable", 2); 							
	}
?>
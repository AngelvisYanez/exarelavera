<?Php
/*COMPONENTE PARA LISTAR LAS FACTURAS PENDIENTES QUE TIENE EL PROVEEDOR SELECCIONADO Y REALIZAR PAGOS A LA FACTURA*/
if (isset($codigo))
{			
	if(isset($Pec_Cod))
	{
		if(isset($hoy))
		{	
		/*consulta de las facturas del proveedor*/
		$rs_factura = $obBD_con1->consulta(sentencias_tes(803, $obBD_con1->parametros($codigo.'*'.$concat)), $obBD_conexion->conexion);	//Antes .'*'.$Pec_Cod
		$row_rs_factura = $obBD_con1->registros();
		$total_rs_factura = $obBD_con1->numregistros();	
	
		/*En esta consulta debe botar un solo registro ya en un año contable normalmente se utiliza un plan de cuentas */
		$rs_cuenta_manual = $obBD_con1->consulta(sentencias_tes(189, $obBD_con1->parametros($Pec_Cod)), $obBD_conexion->conexion);
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
           <td width="4%">Saldo</td>
          <td width="4%">Abono</td>
      </tr>
	  
	 
	 <?Php  
	 $i=0;
	 $cont=0;
	 $suma_total=0;
	 $suma_saldo=0;
	if($total_rs_factura != 0)
	{	  
	  do { 
	 $i++;
	  /*cONSULTA PARA OBTENER LOS DIAS QUE FALTAN PARA VENCER UNA FACTURA **/
		$rs_dias = $obBD_con1->consulta(sentencias_tes(808, $obBD_con1->parametros($row_rs_factura['Cpp_Ven'].'*'.$hoy)), $obBD_conexion->conexion);	
		$row_rs_dias = $obBD_con1->registros();
		$total_rs_dias = $obBD_con1->numregistros();	
	  ?>
	   <?php
		/*consulta de pagos echos a un factura de a credito*/
		$rs_pago = $obBD_con1->consulta(sentencias_tes(804, $obBD_con1->parametros($row_rs_factura['Cop_Cod'])), $obBD_conexion->conexion);	
		$row_rs_pago = $obBD_con1->registros();
		$total_rs_pago = $obBD_con1->numregistros();	
		/*Operación para obtner el saldo de la factura a credito*/
		$saldo= $row_rs_factura['Asi_Val'] - $row_rs_pago['total'];	
		$saldo_final=$saldo;
		if(round($saldo,2) >0)
		{
		 $cont=$cont+1;
		 ?>
	  <tr class="Fondo">
	    <td width="4%" align="center" ><?Php echo $row_rs_factura['Cop_Cod'];?></td>
		<td width="15%" align="center"><?Php echo $row_rs_factura['Cop_Num'];?></td>
		<td align="center"><?Php echo $row_rs_factura['Cop_Fec'];?>&nbsp;</td>
		<td align="center" ><?Php echo $row_rs_factura['Cpp_Ven'];?>&nbsp;</td>
		<td align="center"  <?php if($row_rs_dias['dias']== 0) { echo "bgcolor='#00CCFF'";}  if($row_rs_dias['dias']== 1) { echo "bgcolor='#00FF99'";}  if($row_rs_dias['dias']== -1) { echo "bgcolor='#CCCCCC'";} ?>>&nbsp;
		<?php 
		/*Procedimiento pra pintar de color la coluumna que indique los dias*/
		if($row_rs_dias['dias']== 1) { echo "Mañana";} 
		if($row_rs_dias['dias']== 0) { echo "Hoy";}
		if($row_rs_dias['dias']== -1) { echo "Ayer";}
		if(($row_rs_dias['dias'] >1)){echo $row_rs_dias['dias']." dias ";}
		if(($row_rs_dias['dias'] < -1)){echo "<font color='#FF0000'>".$row_rs_dias['dias']." dias </font>" ;}
		/*fin Procedimiento pra pintar de color la coluumna */?>		
		</td>
		<td align="right">
		<?Php echo formato_numero($row_rs_factura['Asi_Val'], 2, 3); ?> 
		<?php $suma_total=$suma_total + $row_rs_factura['Asi_Val'];
			  $suma_saldo=$suma_saldo + $saldo; 
		?></td>
		<td align="right"><?Php  echo round($saldo_final,2);?></td>
		<td width="4%" align="center">
		<input name="txt_abn[<?php  echo $i; ?>]"  id="txt_abn[<?php  echo $i; ?>]" type="text" size="9" maxlength="9" style="text-align:right"  onBlur="entre(0,<?php echo $saldo;?>,this)" onkeyup="SumaArray(<?php echo $total_rs_factura ;?>,'txt_abn','Val_Pcc'), saldos(<?php echo $total_rs_factura ;?>,'hdd_sald','t_saldo','txt_abn')">
		<input name="codigo" id="codigo" type="hidden" value="<?Php echo $codigo?>">
		<input name="volver_busqueda" id="volver_busqueda" type="hidden" value="<?Php echo $txt_busqueda;?>">
		<input name="volver_op" id="volver_op" type="hidden" value="<?Php echo $op_opciones;?>">						   		
		<input name="Pec_Cod" id="Pec_Cod" type="hidden" value="<?Php  echo $Pec_Cod; ?>">	
		<input name="Pla_Cod" id="Pla_Cod" type="hidden" value="<?Php echo $Pla_Cod;?>">
		<input name="Com_Fec" id="Com_Fec" type="hidden" value="<?Php echo $hoy;?>">	
		<input name="Cop_Fec" id="Cop_Fec" type="hidden" value="<?Php  echo $row_rs_factura['Cop_Fec'];?>">
		<input name="Cop_Num" id="Cop_Num" type="hidden" value="<?Php  echo $row_rs_factura['Cop_Num'];?>">
		<input name="Cpp_Cod[<?php  echo $i; ?>]" id="Cpp_Cod[<?php  echo $i; ?>]" type="hidden" value="<?Php  echo $row_rs_factura['Cpp_Cod'];?>">		
		<input name="Asi_Val[<?php  echo $i; ?>]" id="Asi_Val[<?php  echo $i; ?>]" type="hidden" value="<?Php  echo $row_rs_factura['Asi_Val'];?>">
		<input name="hdd_sald[<?php  echo $i; ?>]" id="hdd_sald[<?php  echo $i; ?>]" type="hidden" value="<?Php  echo $saldo;?>">
		</td>		
	  
	  </tr>
	  <?php }//FIn del if($saldo!=0)?>
	  <?Php } while ($row_rs_factura = $obBD_con1->fetch_assoc($rs_factura));?>
	  
	  <?php 
	   }//FIn del if($total_rs_buscar != 0)
	else
	{ ?>
		<tr class="Fondo">
		<td colspan="8">
		<?Php echo error_alerta("No hay resultados que mostrar para el proveedor ".strtoupper($proveedor)." ".$periodo, 2); ?>		
		</td>
		</tr>
	<?Php
	}//Fin del else if($total_rs_buscar != 0) ?>
	<?php
	  if($cont >0)
	  {
	  ?> 
  		<tr class="Fondo">
		  <td colspan="5">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
		  <td width="4%" align="right"><?php  echo $suma_total; ?></td>
		  <td width="4%" align="center">
		  <input name="t_saldo" type="text" id="t_saldo" size="9" maxlength="9" s readonly="true" value="<?php  echo formato_numero($suma_saldo,2,3); ?>" style="text-align:right">
		  </td>
          <td width="4%" align="center">
		  <input name="Val_Pcc" type="text" id="Val_Pcc" size="9" maxlength="9" readonly="true" align="right" style="text-align:right"></td>
	  </tr>	
	  <tr>
		  <td colspan="8"><span class="Texto_Reporte">
	   <input name="Todos" type="checkbox" id="Todos" value="checkbox" onClick="activar_pagos(<?php echo $total_rs_factura ;?>,'hdd_sald','txt_abn','Todos'),SumaArray(<?php echo $total_rs_factura ;?>,'txt_abn','Val_Pcc'), saldos(<?php echo $total_rs_factura ;?>,'hdd_sald','t_saldo','txt_abn')">
		Pagar Todo</span>
	     </td>
	  </tr> 
	<?php }// fin dle if ($cont>0)	
	  ?> 
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

	}// fin del if(isset($Pec_cod))
else
	{
		echo error_alerta("<< Error de componente: tes_com_fac_credito.php >>  <br>Descripción: No se ha definido la Propiedad: 
		Pec_Cod<br>	Pec_Cod: Variable que contiene el código del periodo contable", 2); 							
	}
	
}//fin if (isset($codigo))

else
	{
		echo error_alerta("<< Error de componente: tes_com_fac_credito.php >>  <br>Descripción: No se ha definido la Propiedad: 
		Codigo<br>	Codigo: Variable que contiene el codigo del proveedor", 2); 							
	}
?>
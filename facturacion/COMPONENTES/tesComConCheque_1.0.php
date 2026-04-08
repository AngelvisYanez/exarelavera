<!-- COMPONENTE PARA CREAR CHEQUES--->	
<?php 
if(isset($Com_Com_Cod))
{
	/**
	* Cargado de las cuentas a modificar 
	*/
	$rs_cuentas = $obBD_con1->getArrayConsulta(306, $Com_Cod, $obBD_conexion);
	/**
	* Cargado de los cheques seg�n el n�mero de comprobante de egreso
	*/
	$rs_concomp = $obBD_con1->getArrayConsulta(143, $Com_Cod, 
	$obBD_conexion);
	/**
	* Consulta los datos del comprobante 
	*/				
	$row_rs_cabcomp = $obBD_con1->getRowConsulta(149, 'proveedore'.'*'.$Com_Cod.'*'.'2'.'*'.$Pec_Cod.'*'.'Prv_Cod', $obBD_conexion);
	/**
	* Consulta los proveedores a los cuales se les hace varios cheques 
	*/
	$rs_prov_cheques = $obBD_con1->getArrayConsulta(314,'',$obBD_conexion);

	if (count($rs_prov_cheques) > 0)
	{
		foreach($rs_prov_cheques as $row_rs_prov_cheques)
		{
			$varios_prov = $varios_prov.'*'.$row_rs_prov_cheques['Prv_Cod'];
		}
	}//FIn del if ($total_rs_prov_cheques > 0)		
?>	
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Comprobante</label>
</LEGEND>
<form action="<?Php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form3" id="form3">
<table width="100%" border="0">
  <tr>
	<td width="16%" class="Etiqueta1">	<input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>">
<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>"> 
<input name="Com_Com_Cod" id="Com_Com_Cod" type="hidden" value="<?php echo $Com_Com_Cod; ?>" />
C&oacute;d. Compr: </td>
	<td width="40%" class="LetraNegra">&nbsp;
	  <input name="textfield" type="text" style="border:none" value="<? echo $row_rs_cabcomp['Com_Num']; ?>"></td>
	<td width="9%" class="Etiqueta1">Fecha:</td>
	<td width="35%" class="LetraNegra">&nbsp;<? echo $row_rs_cabcomp['Com_Fec']; ?></td>
  </tr>
  <tr>
	<td class="Etiqueta1">Nombre:</td>
	<td class="LetraNegra">&nbsp;<? echo $row_rs_cabcomp['Prs_Ape'].' '.$row_rs_cabcomp['Prs_Nom']; ?></td>
	<td class="Etiqueta1">Valor:</td>
	<td class="LetraNegra">&nbsp;<? echo $row_rs_cabcomp['Com_Val']; ?></td>
  </tr>
  <tr>
	<td height="24" class="Etiqueta1">Concepto:</td>
	<td height="24" colspan="3" class="LetraNegra">&nbsp;<? echo $row_rs_cabcomp['Com_Con']; ?></td>
	</tr>
  <tr>
	<td height="20" valign="top" class="Etiqueta1">Observaci&oacute;n:</td>
	<td height="20" colspan="3" valign="top" class="LetraNegra">&nbsp;<? echo $row_rs_cabcomp['Com_Obs']; ?>
	  <input name="codigo" type="hidden" id="codigo" value="<?Php echo $codigo;  ?>"></td>
	</tr>
</table>
</form>
</FIELDSET>

<FIELDSET>
<LEGEND>
<label class="Titulos2">Cuentas</label>
</LEGEND>	
	<table width="100%" border="0" class="fixedHeader01">
    <thead>
	  <tr>
		<th width="10%">Codigo</th>
		<th>Descripci&oacute;n</th>
		<th>Glosa</th>
		<th width="10%">Debe</th>
		<th width="10%">Haber</th>
	  </tr>
     </thead>
     <tbody>
	  <? if (count($rs_cuentas) > 0)
		 {	 
		  foreach($rs_cuentas as $row_rs_cuentas)
		  {
?>
	  <tr>
		<td><? echo $row_rs_cuentas['Pld_Cdc']; ?></td>
		<td><? echo $row_rs_cuentas['Pld_Des']; ?></td>
		<td><? echo $row_rs_cuentas['Asi_Glo']; ?></td>
		<td><div align="right">
			<? if ($row_rs_cuentas['Asi_Deh']=='D') { echo $row_rs_cuentas['Asi_Val']; $total=$total + $row_rs_cuentas['Asi_Val']; } ?>
		</div></td>
		<td><div align="right">
			<? if ($row_rs_cuentas['Asi_Deh']=='H') { echo $row_rs_cuentas['Asi_Val']; } ?>
		</div></td>
	  </tr>
	  <? }//Fin del foreach $row_rs_cuentas 
		 } ?>
	  <tr>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td><strong>Totales:</strong></td>
		<td align="right"><strong><? echo number_format($total,2); ?></strong></td>
		<td align="right"><strong><? echo number_format($total,2); ?></strong></td>
	  </tr>
     </tbody> 
	</table>
	</FIELDSET>
<?php
if (count($rs_concomp) > 0)
{
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Cheques</label>
</LEGEND>
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
		<th class="Cabecera1">Proveedor</th>
		<th class="Cabecera1">Banco</th>
		<th class="Cabecera1">N&ordm; Ch.</th>
		<th class="Cabecera1">Valor</th>
		<th class="Cabecera1">Fecha</th>
		<th class="Cabecera1">&nbsp;</th>
		<th class="Cabecera1">&nbsp;</th>
		<th class="Cabecera1">&nbsp;</th>
		<th class="Cabecera1">&nbsp;</th>
	    <th class="Cabecera1">&nbsp;</th>
	  </tr>
     </thead> 
	  <? 
	if(count($rs_concomp) != 0) 
	{
 	foreach($rs_concomp as $row_rs_concomp)
	{
	$cod = $row_rs_concomp['Che_Cod'];
	$asi = $row_rs_concomp['Asi_Cod'];
	$ban = $row_rs_concomp['Ban_Cod'];
	$pro = $row_rs_concomp['Prv_Cod'];				
 ?>
	  <tr>
		<td <?Php if ($row_rs_concomp['Com_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><? echo $row_rs_concomp['Prs_Ape'].' '.$row_rs_concomp['Prs_Nom']; ?></td>
		<td <?Php if ($row_rs_concomp['Com_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><input name="codigo2" type="hidden" id="codigo2" value="<? echo $cod; ?>">
		  &nbsp;<? echo $row_rs_concomp['Pld_Des'];?>
		  </option></td>
		<td align="right" <?Php if ($row_rs_concomp['Com_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><? echo $row_rs_concomp['Che_Num']; ?></td>
		<td align="right" <?Php if ($row_rs_concomp['Com_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>&nbsp;<? echo "$".''.number_format($row_rs_concomp['Che_Val'],2,'.',''); ?></td>
		<td align="center" <?Php if ($row_rs_concomp['Com_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><? echo $row_rs_concomp['Che_Fec']; ?></td>
	   <form action="tes_pri_cheque_mac_1.0.php" method="post" name= "form3" target="_blank">
	<td width="3%" align="center">		
	<input name="codigo" type="hidden" id="codigo" value="<?php echo $cod; ?>">
	<input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
	<input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
	<input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">	
	<input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_machala.jpg" title="Ver cheque" width="22" height="35">	</td>	
	</form>
		<form action="tes_pri_cheque_pac_1.0.php" method="post" name= "form3" target="_blank">
		  <td width="3%" align="center"><input name="codigo" type="hidden" id="codigo" value="<?php echo $cod; ?>">
			  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
			  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
			  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
			  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_pacifico.jpg" title="Ver cheque" width="24" height="23">		  </td>
		</form>
		<form action="tes_pri_cheque_rum_1.0.php" method="post" name= "form3" target="_blank">
		  <td width="3%" align="center"><input name="codigo" type="hidden" id="codigo" value="<?php echo $cod; ?>">
			  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
			  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
			  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
			  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_ruminahui.jpg" title="Ver cheque" width="30" height="15">		  </td>
		</form>
		<form action="tes_pri_cheque_gua_1.0.php" method="post" name= "form3" target="_blank">
		  <td width="3%" align="center"><input name="codigo" type="hidden" id="codigo" value="<?php echo $cod; ?>">
			  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
			  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
			  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
			  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_guayaquil.JPG" title="Ver cheque" width="36" height="18">		  </td>
		</form>
		<form action="tes_pri_cheque_pch_1.0.php" method="post" name= "form3" target="_blank">	  			  
		  <td width="3%" align="center">
		  <input name="codigo" type="hidden" id="codigo" value="<?php echo $cod; ?>">
		  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
		  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
		  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
		  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/32x32/banco_pichincha.JPG" title="Ver cheque" width="36" height="18">		  
		  </td>
		</form>
	  </tr>
	  <? }//Fin del foreach
} //FIn del if($total_rs_concomp != 0) 
else 
{ ?>
	  <tr>
		<td width="2">&nbsp;</td>
		<td width="1"><?Php echo error_alerta("No hay resultados que mostrar", 2); ?></td>
		<td width="2">&nbsp;</td>
		<td width="4">&nbsp;</td>
		<td width="8">&nbsp;</td>
		<td width="17">&nbsp;</td>
		<td width="33">&nbsp;</td>
		<td width="67">&nbsp;</td>
		<td width="133">&nbsp;</td>
		<td width="266">&nbsp;</td>
	  </tr>
	  <? } //Fin del else if($total_rs_concomp != 0) 
?>
	</table>
</FIELDSET>
<?php 
}//Fin del if ($total_rs_concomp > 0)
}// fin del if(isset(Com_Cod))
else
{
	echo error_alerta("<< Error de componente: tesComConCheque.php >> <br>Descripci�n: No se ha definido la Propiedad: 	
		Com_Com_Cod<br>Pec_Cod: Variable que contiene el c�digo del comprobante de egreso", 2); 	
}
?>
<!-- COMPONENTE PARA CREAR CHEQUES--->	
<?php 
if(isset($Com_Cod))
{

/* Cargado de las cuentas a modificar */
$rs_cuentas = $obBD_con1->consulta(sentencias_tes(306,$obBD_con1->parametros($Com_Cod)), $obBD_conexion->conexion);
$row_rs_cuentas = $obBD_con1->registros();
$total_rs_cuentas = $obBD_con1->numregistros();
/* Cargado de los cheques según el número de comprobante de egreso*/
$rs_concomp = $obBD_con1->consulta(sentencias_tes(143,$obBD_con1->parametros($Com_Cod)), 
$obBD_conexion->conexion);
$row_rs_concomp = $obBD_con1->registros();
$total_rs_concomp = $obBD_con1->numregistros();
/* Consulta los datos del comprobante */				
$rs_cabcomp = $obBD_con1->consulta(sentencias_con(149,$obBD_con1->parametros('proveedore'.'*'.$Com_Cod.'*'.'2'.'*'.
							$Pec_Cod.'*'.'Prv_Cod')), $obBD_conexion->conexion);
$row_rs_cabcomp = $obBD_con1->registros();
$total_rs_cabcomp = $obBD_con1->numregistros();
/* Consulta los proveedores a los cuales se les hace varios cheques */
$rs_prov_cheques = $obBD_con1->consulta(sentencias_tes(314,''),$obBD_conexion->conexion);
$row_rs_prov_cheques = $obBD_con1->registros();
$total_rs_prov_cheques = $obBD_con1->numregistros();

if ($total_rs_prov_cheques > 0)
{
	do{
		$varios_prov = $varios_prov.'*'.$row_rs_prov_cheques['Prv_Cod'];
	}while($row_rs_prov_cheques = $obBD_con1->fetch_assoc($rs_prov_cheques));	
}//FIn del if ($total_rs_prov_cheques > 0)		
?>
	
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos del Comprobante</label>
</LEGEND>
<table width="100%" border="0">
  <tr>
	<td width="16%" class="Etiqueta1">	<input name="Pec_Cod" id="Pec_Cod" type="hidden"  value="<?php echo $Pec_Cod; ?>">
<input name="Pec_Fei" id="Pec_Fei" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fei']; ?>">
<input name="Pec_Fef" id="Pec_Fef" type="hidden" value="<?php echo $row_rs_periodo['Pec_Fef']; ?>"> 
C&oacute;d. Compr: </td>
	<td width="40%" class="LetraNegra">&nbsp;
	  <input name="textfield" type="text" style="border:none" value="<?php echo $row_rs_cabcomp['Com_Num']; ?>"></td>
	<td width="9%" class="Etiqueta1">Fecha:</td>
	<td width="35%" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Fec']; ?></td>
  </tr>
  <tr>
	<td class="Etiqueta1">Nombre:</td>
	<td class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Prs_Ape'].' '.$row_rs_cabcomp['Prs_Nom']; ?></td>
	<td class="Etiqueta1">Valor:</td>
	<td class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Val']; ?></td>
  </tr>
  <tr>
	<td height="24" class="Etiqueta1">Concepto:</td>
	<td height="24" colspan="3" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Con']; ?></td>
	</tr>
  <tr>
	<td height="20" valign="top" class="Etiqueta1">Observaci&oacute;n:</td>
	<td height="20" colspan="3" valign="top" class="LetraNegra">&nbsp;<?php echo $row_rs_cabcomp['Com_Obs']; ?>
	  <input name="codigo" type="hidden" id="codigo" value="<?Php echo $codigo;  ?>"></td>
	</tr>
</table>
</FIELDSET>
	<FIELDSET>
	<LEGEND>
	<label class="Titulos2">Cuentas</label>
	</LEGEND>	
	<table width="100%" border="0">
	  <tr>
		<td width="10%" class="Cabecera1">Codigo</td>
		<td class="Cabecera1">Descripci&oacute;n</td>
		<td class="Cabecera1">Glosa</td>
		<td width="10%" class="Cabecera1">Debe</td>
		<td width="10%" class="Cabecera1">Haber</td>
	  </tr>
	  <?php if ($total_rs_cuentas > 0)
		 {
		 
		  do {
?>
	  <tr>
		<td class="LetraNegra"><?php echo $row_rs_cuentas['Pld_Cdc']; ?></td>
		<td class="LetraNegra"><?php echo $row_rs_cuentas['Pld_Des']; ?></td>
		<td class="LetraNegra"><?php echo $row_rs_cuentas['Asi_Glo']; ?></td>
		<td class="LetraNegra"><div align="right">
			<?php if ($row_rs_cuentas['Asi_Deh']=='D') { echo $row_rs_cuentas['Asi_Val']; $total=$total + $row_rs_cuentas['Asi_Val']; } ?>
		</div></td>
		<td class="LetraNegra"><div align="right">
			<?php if ($row_rs_cuentas['Asi_Deh']=='H') { echo $row_rs_cuentas['Asi_Val']; } ?>
		</div></td>
	  </tr>
	  <?php } while($row_rs_cuentas=  $obBD_con1->fetch_assoc($rs_cuentas)); } ?>
	  <tr>
		<td class="LetraNegra">&nbsp;</td>
		<td class="LetraNegra">&nbsp;</td>
		<td class="LetraNegra"><strong>Totales:</strong></td>
		<td class="LetraNegra" align="right"><strong><?php echo number_format($total,2); ?></strong></td>
		<td class="LetraNegra" align="right"><strong><?php echo number_format($total,2); ?></strong></td>
	  </tr>
	</table>
	</FIELDSET>
<?php
if ($total_rs_concomp > 0)
{
?>
<!---->
<FIELDSET>
<LEGEND>
<label class="Titulos2">Cheques</label>
</LEGEND>
	<table width="100%" border="1" cellpadding="0" cellspacing="0">
	  <tr>
		<td class="Cabecera1">Proveedor</td>
		<td class="Cabecera1">Banco</td>
		<td class="Cabecera1">N&ordm; Ch.</td>
		<td class="Cabecera1">Valor</td>
		<td class="Cabecera1">Fecha</td>
		<td class="Cabecera1">&nbsp;</td>
		<td class="Cabecera1">&nbsp;</td>
		<td class="Cabecera1">&nbsp;</td>
		<td class="Cabecera1">&nbsp;</td>
	    <td class="Cabecera1">&nbsp;</td>
	  </tr>
	  <?php if($total_rs_concomp != 0) {

 do {
	$cod = $row_rs_concomp['Che_Cod'];
	$asi = $row_rs_concomp['Asi_Cod'];
	$ban = $row_rs_concomp['Ban_Cod'];
	$pro = $row_rs_concomp['Prv_Cod'];				
 ?>
	  <tr class="Fondo">
		<td <?Php if ($row_rs_concomp['Com_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><?php echo $row_rs_concomp['Prs_Ape'].' '.$row_rs_concomp['Prs_Nom']; ?></td>
		<td <?Php if ($row_rs_concomp['Com_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><input name="codigo2" type="hidden" id="codigo2" value="<?php echo $cod; ?>">
		  &nbsp;<?php echo $row_rs_concomp['Pld_Des'];?>
		  </option></td>
		<td align="right" <?Php if ($row_rs_concomp['Com_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><?php echo $row_rs_concomp['Che_Num']; ?></td>
		<td align="right" <?Php if ($row_rs_concomp['Com_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>>&nbsp;<?php echo "$".''.number_format($row_rs_concomp['Che_Val'],2,'.',''); ?></td>
		<td align="center" <?Php if ($row_rs_concomp['Com_Est'] == 'I') { echo "bgcolor='#FF0000'"; } ?>><?php echo $row_rs_concomp['Che_Fec']; ?></td>
	   <form action="tes_pri_cheque.php" method="post" name= "form3" target="_blank">
	<td width="3%" align="center">		
	<input name="codigo" type="hidden" id="codigo" value="<?php echo $cod; ?>">
	<input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
	<input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
	<input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">	
	<input type="image" name="imageField" src="../../mascaras/model1/imagenes/banco_machala.jpg" title="Ver cheque" width="22" height="35">	</td>	
	</form>
		<form action="tes_pri_cheque_pac.php" method="post" name= "form3" target="_blank">
		  <td width="3%" align="center"><input name="codigo" type="hidden" id="codigo" value="<?php echo $cod; ?>">
			  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
			  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
			  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
			  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/banco_pacifico.jpg" title="Ver cheque" width="24" height="23">		  </td>
		</form>
		<form action="tes_pri_cheque_rum.php" method="post" name= "form3" target="_blank">
		  <td width="3%" align="center"><input name="codigo" type="hidden" id="codigo" value="<?php echo $cod; ?>">
			  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
			  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
			  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
			  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/banco_ruminahui.jpg" title="Ver cheque" width="30" height="15">		  </td>
		</form>
		<form action="tes_pri_cheque_gua.php" method="post" name= "form3" target="_blank">
		  <td width="3%" align="center"><input name="codigo" type="hidden" id="codigo" value="<?php echo $cod; ?>">
			  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
			  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
			  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
			  <input type="image" name="imageField" src="../../mascaras/model1/imagenes/banco_guayaquil.JPG" title="Ver cheque" width="36" height="18">		  </td>
		</form>
		<form action="tes_pri_cheque_pch.php" method="post" name= "form3" target="_blank">	  			  
		  <td width="3%" align="center">
		  <input name="codigo" type="hidden" id="codigo" value="<?php echo $cod; ?>">
		  <input name="asi" type="hidden" id="asi" value="<?php echo $asi; ?>">
		  <input name="ban" type="hidden" id="ban" value="<?php echo $ban; ?>">
		  <input name="pro" type="hidden" id="pro" value="<?php echo $pro; ?>">
		  <input type="image" name="imageField" src="../../imagenes/banco_pichincha.JPG" title="Ver cheque" width="36" height="18">		  
		  </td>
		</form>
	  </tr>
	  <?php } while ($row_rs_concomp =  $obBD_con1->fetch_assoc($rs_concomp)); 
} //FIn del if($total_rs_concomp != 0) 
else 
{ ?>
	  <tr>
		<td width="533" colspan="10"><?Php echo error_alerta("No hay resultados que mostrar", 2); ?></td>
	  </tr>
	  <?php } //Fin del else if($total_rs_concomp != 0) 
?>
	</table>
</FIELDSET>
<?php 
}//Fin del if ($total_rs_concomp > 0)

@$obBD_con1->free_result($rs_cuentas);
@$obBD_con1->free_result($rs_concomp);
@$obBD_con1->free_result($rs_cabcomp);
@$obBD_con1->free_result($rs_prov_cheques);
?>
<?php 
}// fin del if(isset(Com_Cod))
else
{
	echo error_alerta("<< Error de componente: tes_com_con_cheque.php >> <br>Descripción: No se ha definido la Propiedad: 	
		Com_Cod<br>Pec_Cod: Variable que contiene el código del comprobante de egreso", 2); 	
}
?>
<?php
/*** Consultar las modalidades ********/
	$rs_lst_costo = $obBD_con1->consulta(sentencias_com(202,$obBD_con1->parametros( $Tip_Cst)), $obBD_conexion->conexion);
	$row_rs_lst_costo = $obBD_con1->registros();
	$total_rs_lst_costo = $obBD_con1->numregistros();
 ?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Datos a registrar </label>
</LEGEND>
<table width="760" border="0" cellpadding="0">
<tbody id="Tbl_Costos">
<tr>
	<td width="100" align="left" class="Cabecera1">C&oacute;digo</td>
	<td width="250" class="Cabecera1" align="center">Descripci&oacute;n</td>
	<td width="300" class="Cabecera1" align="center">Grupo</td>
	<td width="80" class="Cabecera1">%</td>
	<td width="30"></td>
</tr>
	<?php 
	$suma=0;
	$i=0;
	if($total_rs_lst_costo != 0)
	{	  
	  do {
	  $i++;
	 	 /* Consulta del detallete de la CUENTA */
			$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_lst_costo['Pld_Rec'])), 
			$obBD_conexion->conexion);
			$row_rs_recur = $obBD_con1->registros();
 
		  	/* Consulta del detallete de la CUENTA (OTRO) */
			$rs_grupo = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_recur['Pld_Rec'])), 
			$obBD_conexion->conexion);
			$row_rs_grupo = $obBD_con1->registros();
	   ?>
	   
	<tr >
	<td class="Fondo" width="100">
	<input name="datos_ch[<?php echo $i; ?>,1]"  id="datos_ch[<?php echo $i; ?>,1]" type="hidden" value="<?PHP echo $row_rs_lst_costo['Pld_Cdc']; ?>" >
	<?PHP echo $row_rs_lst_costo['Pld_Cdc']; ?></td>
  <td class="Fondo"><?PHP echo $row_rs_lst_costo['Pld_Des']; ?>
  <input name="datos_ch[<?php echo $i; ?>,2]" id="datos_ch[<?php echo $i; ?>,2]" type="hidden" value="<?PHP echo $row_rs_lst_costo['Pld_Des']; ?>">
  </td>
  <td class="Fondo"><? if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']." <strong>(".$row_rs_grupo['Pld_Des'].")</strong>"; }
  else{ echo "&nbsp;"; } ?>
	<input name="datos_ch[<?php echo $i; ?>,3]"  id="datos_ch[<?php echo $i; ?>,3]" type="hidden" value="<?PHP echo $row_rs_grupo['Pld_Des']; ?>">
  </td>
  <td width="80" class="Fondo">
   
  <input name="datos_ch[<?php echo $i; ?>,4]" type="text" style="text-align:right" id="datos_ch[<?php echo $i; ?>,4]" size="8" maxlength="8" value="<?php  echo formato_numero($row_rs_lst_costo['Tdc_Por'],2,2);?>" onkeyup="numerico(this); matriz_vec('datos_ch', <?php echo $total_rs_lst_costo;?>, 4,'Val_Pcc')" />
  <input id="c" type="hidden"  name="c" value="<?php echo $i;?>" >
  <input name="datos_ch[<?php echo $i; ?>,5]"  id="datos_ch[<?php echo $i; ?>,5]" type="hidden" value="<?PHP echo $row_rs_lst_costo['Pld_Cod']; ?>">
  
  </td>
	<td align="left" width="20"><? $suma=$suma + $row_rs_lst_costo['Tdc_Por'];?>
	      <input id="quitar_fila" type="button" class="BotonEliminar" name="quitar_fila" value="X" onClick="quitar_fila_cost(this)" title="Eliminar">
	</td>
	  </tr>
		<?php 
		} while ($row_rs_lst_costo = $obBD_con1->fetch_assoc($rs_lst_costo));  ?>
<?php		}// fin if($total_rs_lst_costo != 0)?>
	</tbody>
</table>
  <table width="760" border="0" cellpadding="0">
      <tr>
        <td width="100" align="left" ><input name="Tip_Costo"  id="Tip_Costo" type="hidden" value="<?php echo $Tip_Cst;?>" >
		<input name="f"  id="f" type="hidden" value="<?php echo $i;?>" >
		&nbsp;</td>
        <td width="250">&nbsp;</td>
        <td width="310" class="LetraNegra" align="right"><strong>TOTAL</strong>:</td>
        
        <td width="80" ><input name="Val_Pcc" type="text" id="Val_Pcc" value="<?php echo formato_numero($suma,2,2);?>" size="8" maxlength="8" readonly="true" style="text-align:right" /></td>
        <td width="30">
          <input name="n_filas_ch"  id="nfilas_ch" type="hidden" value="<?php echo $total_rs_lst_costo;?>" />
		</td>
		
      </tr>
      <?php 
	$suma=0;
	$i=0;
	if($total_rs_lst_costo != 0)
	{	  
	  do {
	  $i++;
	 	 /* Consulta del detallete de la CUENTA */
			$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_lst_costo['Pld_Rec'])), 
			$obBD_conexion->conexion);
			$row_rs_recur = $obBD_con1->registros();
 
		  	/* Consulta del detallete de la CUENTA (OTRO) */
			$rs_grupo = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_recur['Pld_Rec'])), 
			$obBD_conexion->conexion);
			$row_rs_grupo = $obBD_con1->registros();
	   ?>
      <?php 
		} while ($row_rs_lst_costo = $obBD_con1->fetch_assoc($rs_lst_costo)); 
		}// fin if($total_rs_lst_costo != 0)?>
</table>
</FIELDSET>

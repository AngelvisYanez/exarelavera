<?Php
require_once('../../componentes/LOGICA/logica.php');
/* Ajax - Componente para la busqueda de las cuentas del plan de cuentas */
/* Cargado AJAX de los resultados de la búsqueda 
F= focus */
//echo $Pla_Cod;
if ($ajax_buscador=="F")
{	
	if ($op_opciones=='d')
	{
		// Cargado de los resultados de la busqueda por descripcion de la cuenta
		$rs_buscta = $obBD_con1->consulta(sentencias_com(11, $obBD_con1->parametros($ajax_buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod)), 
									$obBD_conexion->conexion);
	}
	elseif ($op_opciones=='c')
	{
		// Cargado de los resultados de la busqueda por codigo de la cuenta
		$rs_buscta = $obBD_con1->consulta(sentencias_com(12, $obBD_con1->parametros($ajax_buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod)), 
									$obBD_conexion->conexion);
	}
		$row_rs_buscta = $obBD_con1->fetch_assoc($rs_buscta);
		$total_rs_buscta = $obBD_con1->num_rows($rs_buscta);	
	?>
	<br>
	<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
     <thead>
	  <tr class="Cabecera_ajax">
	    <td width="4%"><strong>C&oacute;d. Int.</strong></td>
		<td width="4%"><strong>C&oacute;digo</strong></td>
		<td><strong>Descripci&oacute;n</strong></td>
		<td><strong>Grupo</strong></td>
		<td><strong>Tipo</strong></td>
		<td><strong>Estado</strong></td>
		<td>&nbsp;</td>
	  </tr>
      </thead>
      <tbody>
	  <?php
	  if ($total_rs_buscta > 0) 
	  {
	  do { 
	  $i++;
		/* Consulta del detallete de la CUENTA */
		$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_buscta['Pld_Rec'])), 
		$obBD_conexion->conexion);
		$row_rs_recur = $obBD_con1->fetch_assoc($rs_recur);	
		/* Consulta del detallete de la CUENTA (OTRO) */
		$rs_grupo = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_recur['Pld_Rec'])), 
		$obBD_conexion->conexion);
		$row_rs_grupo = $obBD_con1->fetch_assoc($rs_grupo);						  						  
	  ?>
	  <tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Cuerpo_ajax"); ?>  class="Cuerpo_ajax">
	    <td align="center"><?php echo $row_rs_buscta['Pld_Cod']; ?>
			<input name="hdd_Cod" id="hdd_Cod" type="hidden" value="<?php echo $row_rs_buscta['Pld_Cod']; ?>">
        </td>
		<td align="left"><?Php echo marcar_cadena($ajax_buscod, $row_rs_buscta['Pld_Cdc'],'#FFFF00', 1);?>
			<input name="hdd_Cdc" d="hdd_Cdc" type="hidden" value="<?php echo $row_rs_buscta['Pld_Cdc']; ?>">
        </td>
		<td align="left"><?Php echo marcar_cadena($ajax_buscod, $row_rs_buscta['Pld_Des'],'#FFFF00', 1);?>
			<input name="hdd_Des" id="hdd_Des"type="hidden" value="<?php echo $row_rs_buscta['Pld_Des']; ?>">
        </td>
		<td><div align="center"><?php if ($row_rs_recur['Pld_Des'] != ""){?><input name="hdd_Gru" id="hdd_Gru" type="hidden" value="<?php echo $row_rs_recur['Pld_Des'].'-'.$row_rs_grupo['Pld_Des']; ?>"> <?php echo $row_rs_recur['Pld_Des']." <strong>(".$row_rs_grupo['Pld_Des'].")</strong>"; }else{ 
								echo "&nbsp;"; } ?></div>
			</td>
		<td align="center"><div align="center"><?php echo $row_rs_buscta['Pld_Tip']; ?></div></td>
		<td align="center"><div align="center"><?php echo $row_rs_buscta['Pld_Est']; ?></div></td>
		<td align="center">
<img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" title="Agregar cuenta" style="cursor:pointer" 
onClick="nueva_fila_costo('Tbl_Costos','<?php echo $row_rs_buscta['Pld_Cod']; ?>','<?php echo $row_rs_buscta['Pld_Cdc']; ?>','<?php echo $row_rs_buscta['Pld_Des']; ?>','<?php echo $row_rs_grupo['Pld_Des'];?>')">
</td>
	  </tr>
	  <?php } while ($row_rs_buscta = $obBD_con1->fetch_assoc($rs_buscta));
	  } else { ?>
		<tr>
        	<td colspan="7"><?Php echo error_alerta("¡No hay resultados que mostrar!", 1); ?></td>
		</tr>
	  <?php } ?>
      <tbody>
	</table>
<?Php 
echo barra_estado($total_rs_buscta+0);

@$obBD_con1->free_result($rs_buscta);
@$obBD_con1->free_result($rs_recur);
@$obBD_con1->free_result($rs_grupo);
exit();
}//if (isset($ajax_$buscod))
?>


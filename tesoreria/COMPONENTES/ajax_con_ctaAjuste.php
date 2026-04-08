<?Php
/**
* Ajax - Componente para la busqueda de los productos bienes 
* Cargado AJAX de los resultados de la búsqueda 
* F= focus 
*/
if ($ajax_buscador=="F")
{		
	/**
	* Cargado de los resultados de la busqueda por descripcion de la cuenta
	*/
	if($opciones_cod=='d')
	{
	
	$rs_buscta = $obBD_con1->consulta(sentencias_tes(1054, $obBD_con1->parametros(trim($txtBusqueda).'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);	
	}
	else
	{
	$rs_buscta = $obBD_con1->consulta(sentencias_tes(1040, $obBD_con1->parametros(trim($txtBusqueda).'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);	
	}
	$row_rs_buscta = $obBD_con1->registros();
	$total_rs_buscta = $obBD_con1->numregistros();		
	?>
	<br>
	<table width="100%" height="17%" border="1" cellpadding="0" cellspacing="0">
	  <tr class="Cabecera1">
	    <td width="14%" align="center"><strong>C&oacute;d. Int.</strong></td>	
		<td width="26%" align="center"><strong>Descripci&oacute;n</strong></td>
		<td width="29%" align="center"><strong>Marca</strong></td>
		<td width="16%" align="center"><strong>Tipo</strong></td>
		<td width="13%" align="center"><strong>Pvp</strong></td>
		<td width="0%" align="center"><strong>Stock</strong></td>
		<td width="2%" align="center">&nbsp;</td>
	  </tr>
	  <?
	  if ($total_rs_buscta > 0) {
	  do { 
						  						  
	  ?>
	  <tr <? echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> class="Fondo">
	    <td align="center"><? echo $row_rs_buscta['Pro_Cod']; ?></td>
		<td align="left"><? echo marcar_cadena($txtBusqueda,$row_rs_buscta['Ite_Lar'],'#FFFF00', 1); ?></td>
		<td align="left"><? echo $row_rs_buscta['Mar_Des']; ?></td>
		<td align="left"><? echo $row_rs_buscta['Adq_Des']; ?></td>		
		<td align="right"><? echo $row_rs_buscta['Pre_Pvp'];  ?></td>
		<td align="right"><? echo $row_rs_buscta['Stk_Can']; ?></td>
		<td align="center">
        <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="nueva_fila_ajuste('c_contenido','<? echo $row_rs_buscta['Pro_Cod']; ?>','<? echo $row_rs_buscta['Ite_Lar']; ?>','<? echo $row_rs_buscta['Pre_Pvp']; ?>','<? echo 400000;?>')">
           			<i class=" icon-arrow-right icon-white"></i>
           		</button>
			</td>
	  </tr>
	  <? } while ($row_rs_buscta = $obBD_con1->fetch_assoc($rs_buscta));
	  } else { ?>
		<tr>
			<td height="32%" colspan="11"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
		</tr>
<? }?>
</table>
<?Php 
echo barra_estado($total_rs_buscta+0);
@$obBD_con1->free_result($rs_buscta);
@$obBD_con1->free_result($rs_recur);
@$obBD_con1->free_result($rs_grupo);
exit();
}//if (isset($ajax_$buscod))
?>
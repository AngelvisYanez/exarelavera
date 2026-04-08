<?Php
/* Ajax - Componente para la busqueda control de guias y modulos */
/* Cargado AJAX de los resultados de la búsqueda 
F= focus */
if ($ajax_buscador=="F")
{	// Cargado de los resultados de la busqueda por descripcion de la cuenta
	$rs_buscta = $obBD_con1->consulta(sentencias_tes(1054, $obBD_con1->parametros($txtBusqueda.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);	
	$row_rs_buscta = $obBD_con1->registros();
	$total_rs_buscta = $obBD_con1->numregistros();		
	?>
	<br>
	<table width="100%" height="17%" border="1" cellpadding="0" cellspacing="0" class="Fondo">
	  <tr class="Cabecera_ajax">
	    <td width="11%" height="34%"><strong>C&oacute;d. Int.</strong></td>	
		<td width="33%"><strong>Descripci&oacute;n</strong></td>
		<td width="33%"><strong>Marca</strong></td>
		<td width="18%"><strong>Adquisici&oacute;n</strong></td>
		<td width="5%">&nbsp;</td>
	  </tr>
	  <?
	  if ($total_rs_buscta > 0) {
	  do { 
						  						  
	  ?>
	  <tr <? echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Cuerpo_ajax");?> class="Cuerpo_ajax">
	    <td height="34%" align="center"><? echo $row_rs_buscta['Pro_Cod']; ?></td>
		<td align="left"><? echo marcar_cadena($txtBusqueda,$row_rs_buscta['Ite_Lar'],'#FFFF00', 1); ?></td>
		<td align="left"><? echo $row_rs_buscta['Mar_Des']; ?></td>
		<td align="left"><? echo $row_rs_buscta['Adq_Des']; ?></td>		
		<td align="center">
		<? $rs_guia = $obBD_con1->consulta(sentencias_tes(1073, $obBD_con1->parametros($Rec_Cod.'*'.$row_rs_buscta['Pro_Cod'])),$obBD_conexion->conexion);	
	$row_rs_guia = $obBD_con1->registros();
	$total_rs_guia = $obBD_con1->numregistros();
		 if ($total_rs_guia<=0)
		{
		?>
		<img src="../../mascaras/model1/imagenes/32x32/forward.png" width="18" height="18" title="Agregar cuenta" style="	
		cursor:pointer" onClick="nueva_fila_ajuste('c_contenido','<? echo $row_rs_buscta['Pro_Cod']; ?>','<? echo $row_rs_buscta['Ite_Lar']; ?>','<? echo 0.00; ?>','<? echo 400000;?>')">	<? }else{ ?><img src="../../mascaras/model1/imagenes/32x32/error1.jpg" width="18" height="18" title="Agregar cuenta" style="	
		cursor:pointer" /><? }?>	</td>
	  </tr>
	  <? } while ($row_rs_buscta = $obBD_con1->fetch_assoc($rs_buscta));
	  } else { ?>
		<tr>
			<td height="32%" colspan="9" class="Alertas"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
		</tr>
<? }?>
</table>
<?Php 
@$obBD_con1->free_result($rs_buscta);
@$obBD_con1->free_result($rs_recur);
@$obBD_con1->free_result($rs_grupo);
exit();
}//if (isset($ajax_$buscod))
?>
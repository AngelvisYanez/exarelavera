<?Php
require_once('../LOGICA/tes_sql_val.php');

/* Ajax - Componente para la busqueda de las cuentas del plan de cuentas */

/* Cargado AJAX de los resultados de la búsqueda 
F= focus */

if ($ajax_buscador=="F")
{		
	
	// Cargado de los resultados de la busqueda por descripcion de la cuenta
	$rs_buscta = $obBD_con1->consulta(sentencias_tes(394, $obBD_con1->parametros($txtBusqueda.'*'.$Ses_Emp_Cod)),$obBD_conexion->conexion);	
	$row_rs_buscta = $obBD_con1->registros();
	$total_rs_buscta = $obBD_con1->numregistros();		
	?>
	<br>
	<table width="100%" height="17%" border="1" cellpadding="0" cellspacing="0" class="Fondo">
	  <tr class="Cabecera_ajax">
	    <td width="11%" height="34%"><strong>C&oacute;d. Int.</strong></td>	
		<td width="66%"><strong>Descripci&oacute;n</strong></td>
		<td width="18%"><strong>Gasto Max.</strong></td>
		<td width="5%">&nbsp;</td>
	  </tr>
	  <?php
	  if ($total_rs_buscta > 0) {
	  do { 
						  						  
	  ?>
	  <tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Cuerpo_ajax");?> class="Cuerpo_ajax">
	    <td height="34%" align="center"><?php echo $row_rs_buscta['Gas_Cod']; ?></td>
		<td align="left"><?php echo marcar_cadena($txtBusqueda,$row_rs_buscta['Gas_Des'],'#FFFF00', 1); ?></td>
		<td align="left">&nbsp;<?php echo formato_numero($row_rs_buscta['Gas_Max'],2,1); ?></td>		
		<td align="center">
		<img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" title="Agregar cuenta" style="	
		cursor:pointer" onClick="nueva_fila_cajaChica('c_contenido','<?php echo $row_rs_buscta['Gas_Cod']; ?>','<?php echo $row_rs_buscta['Gas_Des']; ?>','<?php echo $row_rs_buscta['Gas_Max']; ?>','<?php echo $Cja_Tra?>')">
		</td>
	  </tr>
	  <?php } while ($row_rs_buscta = $obBD_con1->fetch_assoc($rs_buscta));
	  } else { ?>
		<tr>
			<td height="32%" colspan="8" class="Alertas"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
		</tr>
<?php }?>
</table>
<?Php 
@$obBD_con1->free_result($rs_buscta);
@$obBD_con1->free_result($rs_recur);
@$obBD_con1->free_result($rs_grupo);
exit();
}//if (isset($ajax_$buscod))
?>
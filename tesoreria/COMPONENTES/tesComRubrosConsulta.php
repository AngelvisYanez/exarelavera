<?php
/* Alias: [--]
   Descripción: Componente que muestra los Rubros de un punto de imprecion.
   Fecha de actualización: 2010-06-29.
   Desarrollador: José Cumbicos.
*/

// Variables de ingreso del modulo ($busqueda,$codigo,$Sem_Cod)
/* Si el semestre esta creado para el cliente entonces de busca los rubros filtrados desde deudas */
if ($Sem_Cod > 0)
{	
	/* Cargado de los resultados de la busqueda de producto */
	$rs_buscta = $obBD_con1->consulta(sentencias_tes(28, $obBD_con1->parametros(trim($busqueda).'*'.$codigo.'*'.$Sem_Cod)),$obBD_conexion->conexion);	
	$row_rs_buscta = $obBD_con1->registros();
	$total_rs_buscta = $obBD_con1->numregistros();	
	}//Fin del if ($Sem_Cod > 0)
else
{		
		/* Cargado de los resultados de la busqueda de producto sin precio*/
		$rs_buscta = $obBD_con1->consulta(sentencias_tes(52, $obBD_con1->parametros(trim($busqueda))),$obBD_conexion->conexion);	
		$row_rs_buscta = $obBD_con1->registros();
		$total_rs_buscta = $obBD_con1->numregistros();		
	
}//Fin del else if ($Sem_Cod > 0)
?>
<br>
<table width="100%" border="1" cellpadding="0" cellspacing="0" id="tbl_resultados">
<?php
	if ($total_rs_buscta > 0) {
		$cont = 1;
		do { 
			$Pro_Cod = $row_rs_buscta['Pro_Cod'];
			/* Consulta de la posible beca y su porcentaje que tenga el estudiante */
			/*$rs_becas = $obBD_con1->consulta(sentencias_tes(73, $obBD_con1->parametros($codigo.'*'.$Pro_Cod.'*'.$Sem_Cod)), 
								$obBD_conexion->conexion);	
			$row_rs_becas = $obBD_con1->registros();
			$total_rs_becas = $obBD_con1->numregistros();	*/
			
			if ($cont == 1)
			{			
?>
			<tr class="Cabecera_ajax">
				<td width="8%"><strong>Cód. Int.</strong></td>
				<td width="9%"><strong>Código</strong></td>
				<td><strong>Descripci&oacute;n</strong></td>
				<td><strong>Valor</strong></td>		
				<!-- <td><strong>% <?php //echo $row_rs_becas['Tib_Ini']; ?></strong></td> -->								
				<!-- <td><strong>Valor Pagar</strong></td> -->
				<td width="4%"></td>
			</tr>					
			<?Php }//Fin del if ($cont == 1)?>
			<tr <?php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Cuerpo_ajax");?> class="Cuerpo_ajax">
				<td><div align="center"><?php echo $row_rs_buscta['Pro_Cod']; ?></div></td>
				<td><div align="center"><?php echo $row_rs_buscta['Pro_Ide']; ?></div></td>
				<td><div align="left">&nbsp;<?php echo $row_rs_buscta['Ite_Lar']; ?></div></td>
				<td align="right"><?php echo formato_numero($row_rs_buscta['Pre_Pvp'],2,2); ?></td>	
				<!-- <td align="center">
				</td> -->
				<!-- <td align="right"><?php //echo number_format(($row_rs_buscta['Pre_Pvp']) - $valor_beca,2); ?>
				</td> -->																													
				<td>
				<div align="center">
				<img src="../../imagenes/insertar.jpg" style="cursor:pointer" width="22" height="22" onClick="nueva_fila('c_contenido','<?php echo $row_rs_buscta['Pro_Ide'];?>','<?php echo $row_rs_buscta['Pro_Cod'];?>','<?php echo $row_rs_buscta['Ite_Lar'];?>','<?php if ($row_rs_buscta['Pre_Pvp'] > 0){ echo $row_rs_buscta['Pre_Pvp']; } ?>','<?php echo $row_rs_buscta['Iva_Por'];?>','<?php echo $row_rs_buscta['Iva_Cod'];?>','<?php echo $codigonota; ?>','','<?Php echo $_SERVER['PHP_SELF']; ?>','','<?php echo $bloc_cant; ?>','si','0'); asignar_total_fac();">
				</div>						
				</td>
			</tr>
		<?php $cont++;
			} while ($row_rs_buscta = $obBD_con1->fetch_assoc($rs_buscta));
	} else { ?>
			<tr>
				<td colspan="7"><?php echo error_alerta("No hay resultados que mostrar", 1)?></td>
			</tr>
	<?php }?>
	<tr>
		<td colspan="7" align="center"><img src="../../imagenes/ocultar2.jpg" height="12" style="cursor:pointer" alt="Ocultar" onClick="ShowHide('rubros_table')"></td>
	</tr>
</table>
<?php
@$obBD_con1->free_result($rs_buscta);
?>

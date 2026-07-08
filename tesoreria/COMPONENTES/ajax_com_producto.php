<?php
/* Alias: [--]
   Descripción: Componente que realiza la busqueda de los productos.
   Fecha de actualización: 2010-02-26.
   Desarrollador: Lewis Chimarro.
*/
//Texto a buscar
if(isset($Com_Busqueda))
{ 
	//Codigo del cliente
	if(isset($Com_Codigo))
	{ 
		//Fecha actual
		if(isset($Com_Sem_Cod))
		{ 
	/* Cargado de los resultados de la busqueda de producto sin precio*/
	$rs_buscta = $obBD_con1->consulta(sentencias_tes(62, $obBD_con1->parametros(trim($Com_Busqueda).'*'.$Com_Codigo.'*'.$Com_Sem_Cod)), 
									$obBD_conexion->conexion);	
	$row_rs_buscta = $obBD_con1->registros();
	$total_rs_buscta = $obBD_con1->numregistros();	
	?>
		<br>
		<table width="100%" height="20" border="1" cellpadding="0" cellspacing="0" id="tbl_resultados">
      		<tr class="Cabecera_ajax">
        		<td width="8%"><strong>Cód Int</strong></td>
        		<td width="9%"><strong>Código</strong></td>
        		<td><strong>Descripci&oacute;n</strong></td>
				<td width="4%"></td>
        	</tr>
      		<?php
	  		if ($total_rs_buscta > 0) {
	  			do { 
	  				?>
      				<tr <?Php echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Cuerpo_ajax");?> class="Cuerpo_ajax">
        				<td><div align="center"><?php echo $row_rs_buscta['Pro_Cod']; ?></div></td>
						<td><div align="center"><?php echo $row_rs_buscta['Pro_Ide']; ?></div></td>
        				<td><?php echo marcar_cadena($Com_Busqueda, $row_rs_buscta['Ite_Lar'], '#FFFF00', 0); ?></td>
        				<td><div align="center" onClick="nueva_fila_deuda('c_contenido', '<?php echo $row_rs_buscta['Pro_Cod'];?>','<?php echo $row_rs_buscta['Ite_Lar'];?>','<?php echo $Car_Nom; ?>','<?php echo $Sem_Nom; ?>','','','','','<?php echo $Com_Sem_Cod?>', '<?Php echo $_SERVER['PHP_SELF']; ?>', '<?Php echo $Per_Fea; ?>', '<?Php echo $Per_Fef; ?>')"><img src="../../imagenes/insertar.jpg" style="cursor:pointer"  width="22" height="22"></div>
						</td>
        			</tr>
      			<?php } while ($row_rs_buscta = $obBD_con1->fetch_assoc($rs_buscta));
	  		} else { ?>
	  				<tr>
						<td colspan="4"><?php echo error_alerta("No hay resultados que mostrar", 1)?></td>
	  				</tr>
	  		<?php }?>
			<tr>
				<td colspan="4" align="center"><img src="../../imagenes/ocultar2.jpg" height="12" style="cursor:pointer" alt="Ocultar" onClick="ShowHide('tbl_resultados')"></td>
			</tr>
    	</table>
<?php

		@$obBD_con1->free_result($rs_buscta);	
		}//Fin del if(isset($Com_Sem_Cod))
		else
		{ 
			echo error_alerta("<< Error de componente: ajax_com_matriculactual.php >> <br>Descripción: No se ha definido la Propiedad: Com_Sem_Cod<br>
	        Com_Sem_Cod: Variable que contiene el codigo del semestre", 2);	
		}/* fin del else if(isset($Com_Sem_Cod)) */ 
	}//Fin del if(isset($Com_Codigo))
	else
	{ 
	echo error_alerta("<< Error de componente: ajax_com_matriculactual.php >> <br>Descripción: No se ha definido la Propiedad: Com_Codigo<br>
        Com_Codigo: Variable que contiene el código del cliente", 2);	
	}/* fin del else if(isset($Com_Codigo)) */ 
}//Fin del if(isset($Com_Codigo))
else
{ 
	echo error_alerta("<< Error de componente: ajax_com_matriculactual.php >> <br>Descripción: No se ha definido la Propiedad: Com_Busqueda<br>
        Com_Busqueda: Variable que contiene el texto a buscar", 2);	
}/* fin del else if(isset($Com_Busqueda)) */ ?>
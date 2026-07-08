<?Php
/* Alias: [--]
   Descripción: Componente que devuelve los datos de la ultima matricula actual del estudiante.
   Fecha de actualización: 2010-02-26.
   Desarrollador: Lewis Chimarro.
*/

//Codigo del cliente
if(isset($Com_Codigo))
{ 
	//Codigo de la carrera
	if(isset($Com_Car_Int))
	{ 
		//Fecha actual
		if(isset($Com_Hoy))
		{ 
	/* Cargado de los resultados de la busqueda de producto */
	$rs_semestre = $obBD_con1->consulta(sentencias_tes(60, $obBD_con1->parametros($Com_Hoy.'*'.$Com_Codigo.'*'.$Com_Car_Int)), 
									$obBD_conexion->conexion);	
	$row_rs_semestre = $obBD_con1->registros();
	$total_rs_semestre = $obBD_con1->numregistros();
	
	if ($total_rs_semestre > 0) {	
	?>
		<table width="100%" border="0" cellpadding="0" cellspacing="0" class="Busqueda_contenido_ajax">
		 <tr>
		   <td align="right"><strong>Sucursal:&nbsp;</strong></td>
		   <td><?php echo $row_rs_semestre['Suc_Des'];?></td>
	       <td align="right"><strong>Modalidad:&nbsp;</strong></td>
	       <td>
           <input name="Modalidad" type="text" id="Modalidad" size="40" style=" background:none; border:none" value="<?php echo $row_rs_semestre['Mod_Des'];?>" /></td>
		 </tr>
		 <tr>
		   <td width="97" align="right"><strong>Etapa:&nbsp;</strong></td>
		   <td width="341"><?php echo $row_rs_semestre['Eta_Des'];?></td>
		   <td width="83" align="right"><strong>Periodo:&nbsp;</strong></td>
		   <td width="638"><?php echo $row_rs_semestre['Mes_Ini']."/".$row_rs_semestre[	'Ann_Ini']." - ".
										$row_rs_semestre['Mes_Fin']."/".$row_rs_semestre['Ann_Fin'];?></td>
		 </tr>
		 <tr>
		   <td align="right" valign="bottom"><strong>Curso:&nbsp;</strong></td><input name="Sem_Cod" type="hidden" value="<?php echo $row_rs_semestre['Sem_Cod']; ?>">
		   <td><?Php echo $row_rs_semestre['Sem_Nom']; ?></td>
		   <td>&nbsp;</td>
		   <td>&nbsp;</td>
		 </tr>
	   </table>
<table width="100%" height="36" border="0" cellpadding="0" cellspacing="0" class="Busqueda_contenido_ajax">
<tbody id="tbusqueda">
		  <tr>
			<td width="99" align="right"><strong>Descripci&oacute;n:&nbsp;</strong></td>
			<td width="369">
		    <input name="buscta" type="text" id="buscta" size="50" maxlength="50" style="text-transform:uppercase" onKeyPress="enter_ajax('<?Php echo $_SERVER['PHP_SELF']; ?>?buscod&Com_Busqueda=' + buscta.value + '&Car_Nom=<?php echo trim(cortar_cadena_param(' ', $row_rs_semestre['Car_Nom'])); ?>&Sem_Nom=<?Php echo $row_rs_semestre['Sem_Nom']; ?>&Com_Sem_Cod=<?Php echo 		
														$row_rs_semestre['Sem_Cod']; ?>&Com_Codigo=<?Php echo $Com_Codigo; ?>&Per_Fea=<?Php echo $row_rs_semestre['Per_Fea']?>&Per_Fef=<?Php echo $row_rs_semestre['Per_Fef']?>', 'rubros') " /></td>
			<td width="691" align="left"><input name="btn_buscar" type="button" title="Buscar" class="Boton_Buscar" id="btn_buscar" onClick="ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?buscod&Com_Busqueda=' + buscta.value + '&Car_Nom=<?php echo trim(cortar_cadena_param(' ', $row_rs_semestre['Car_Nom'])); ?>&Sem_Nom=<?Php echo $row_rs_semestre['Sem_Nom']; ?>&Com_Sem_Cod=<?Php echo 		
														$row_rs_semestre['Sem_Cod']; ?>&Com_Codigo=<?Php echo $Com_Codigo; ?>&Per_Fea=<?Php echo $row_rs_semestre['Per_Fea']?>&Per_Fef=<?Php echo $row_rs_semestre['Per_Fef']?>', 'rubros')" value="Buscar">						
        </td>
    </tr>
	  <?php
	  }//Fin del if ($total_rs_semestre > 0)
	  else
	  {
	  ?>
	  	<tr>
			<td colspan="3"><?Php echo error_alerta("¡El estudiante no posee una Matrícula Activa!", 1); ?></td>
		</tr>	
	  <?Php
	  }
	  ?>
	</tbody>
    </table>
	<div id="rubros"></div>	   
<?php 	
@ $obBD_con1->free_result($rs_semestre);
		}//Fin del if(isset($Com_Hoy))
		else
		{ 
			echo error_alerta("<< Error de componente: ajax_com_matriculactual.php >> <br>Descripción: No se ha definido la Propiedad: Com_Hoy<br>
	        Com_Hoy: Variable que contiene la fecha actual", 2);	
		}/* fin del else if(isset($Com_Hoy)) */ 
	}//Fin del if(isset($Com_Car_Int))
	else
	{ 
	echo error_alerta("<< Error de componente: ajax_com_matriculactual.php >> <br>Descripción: No se ha definido la Propiedad: Com_Car_Int<br>
        Com_Car_Int: Variable que contiene el código de la carrera", 2);	
	}/* fin del else if(isset($Com_Car_Int)) */ 
}//Fin del if(isset($Com_Codigo))
else
{ 
	echo error_alerta("<< Error de componente: ajax_com_matriculactual.php >> <br>Descripción: No se ha definido la Propiedad: Com_Codigo<br>
        Com_Codigo: Variable que contiene el código del cliente", 2);	
}/* fin del else if(isset($Com_Codigo))) */


?>
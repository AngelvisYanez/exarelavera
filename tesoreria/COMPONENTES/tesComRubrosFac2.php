<?
/** 
* Descripción: Componente que muestra el cuadro de texto para la busqueda de Rubros.
* Fecha de actualización: 2010-06-29.
* Desarrollador: Lewis Chimarro
* Fecha de actualización: 2012-11-21
* Desarrollador: Lewis Chimarro
*/
/**
* Variables de ingreso del modulo ($hoy,$codigo,$car)
*/
if ($car > 0)
{
	/** 
	* Cargado de los resultados de la busqueda de producto 
	*/
	$row_rs_semestre = $obBD_con1->getRowConsulta(63, $hoy.'*'.$codigo.'*'.$car, $obBD_conexion);		
	/** 
	* Inicializa en "no" para que no bloque la cantidad del rubro 
	*/
	$bloc_cant = "no";
	if (count($row_rs_semestre) > 0) 
	{
	?>	
	<table width="550" border="0">
	<tr>
	   <td width="65" align="right"><strong>Periodo:</strong></td>
	   <td width="473"><?php echo $row_rs_semestre['Mes_Ini']."/".$row_rs_semestre[	'Ann_Ini']." - ".$row_rs_semestre['Mes_Fin']."/".$row_rs_semestre['Ann_Fin'];?></td>
	</tr>
	<tr>
	   <td align="right"><strong>Curso:</strong></td><input name="Sem_Cod" type="hidden" value="<?php echo $row_rs_semestre['Sem_Cod']; ?>">
	   <td><?Php echo $row_rs_semestre['Sem_Nom']; ?></td>
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
  	</table>
<?Php
}//Fin del if ($car > 0)
?>
	<table width="546" height="46" border="0" cellpadding="0" cellspacing="0">
		<tbody id="tbusqueda">
		  <tr>
			<td width="85" height="36" align="right" class="Cabecera1"><div align="right"><strong>Descripci&oacute;n:</strong></div></td>
			<td width="351" height="36" class="Cabecera1"><? noEnterSubmit(); ?>
			<input name="buscta" type="text" id="buscta" size="50" maxlength="50" style="text-transform:uppercase" onKeyUp="parametro_injection(this)" onkeypress="var evt = (evt) ? evt : ((event) ? event : null);
		if (trim(document.getElementById('buscta').value) != '' && evt.keyCode == 13)
        {  
        	ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?buscod&busqueda='+buscta.value+'&Sem_Cod=<?Php echo $row_rs_semestre['Sem_Cod']; ?>&codigo=<?Php echo $codigo; ?>&Per_Fea=<?Php echo $row_rs_semestre['Per_Fea']?>&Per_Fef=<?Php echo $row_rs_semestre['Per_Fef']?>&codigonota=<?Php echo $row_rs_semestre['Nge_Cod']?>&bloc_cant=<?Php echo $bloc_cant + 0; ?>', 'rubros');
		}">
			</td>			
			<td width="110" align="center" height="36" class="Cabecera1">
            <div id="set1">
            <button type="button" class="btn btn-success" title="Buscar item" onClick="if (trim(document.getElementById('buscta').value) != ''){  ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?buscod&busqueda=' + buscta.value + '&Sem_Cod=<?Php echo $row_rs_semestre['Sem_Cod']; ?>&codigo=<?Php echo $codigo; ?>&Per_Fea=<?Php echo $row_rs_semestre['Per_Fea']?>&Per_Fef=<?Php echo $row_rs_semestre['Per_Fef']?>&codigonota=<?Php echo $row_rs_semestre['Nge_Cod']?>&bloc_cant=<?Php echo $bloc_cant + 0; ?>', 'rubros'); }else{ alert('El dato de este campo es requerido'); document.getElementById('buscta').focus(); } "> <i class="icon-search icon-white"></i> <span>Buscar</span> </button>
            </div>
			</td>
</tr>	  
	</tbody>
    </table>
<div id="rubros"></div>
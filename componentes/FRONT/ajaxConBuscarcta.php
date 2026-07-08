<?Php
/**
* Resultado de la busqueda de los productos
* Fecha de actualización:	2012-08-19  
* Desarrollador: Lewis Chimarro
*/
require_once('../../facturacion/LOGICA/fac_log_compras.php');

/**
* Ajax - Componente para la busqueda de las cuentas del plan de cuentas 
*
* Cargado AJAX de los resultados de la búsqueda F= focus 
*/

if ($ajax_buscador=="F")
{	
	if ($op_opciones=='d')
	{
		/**
		* Cargado de los resultados de la busqueda por descripcion de la cuenta
		*/
		$rs_buscta = $obBD_con1->consulta(sentencias_com(11, $obBD_con1->parametros($ajax_buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod)), 
									$obBD_conexion->conexion);
	}
	if ($op_opciones=='c')
	{
		/**
		* Cargado de los resultados de la busqueda por codigo de la cuenta
		*/
		$rs_buscta = $obBD_con1->consulta(sentencias_com(12, $obBD_con1->parametros($ajax_buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod)), 
									$obBD_conexion->conexion);
	}
		$row_rs_buscta = $obBD_con1->registros();
		$total_rs_buscta = $obBD_con1->numregistros();		
	?>
	<br>
	<table width="100%" border="0" class="fixedHeader01">
    <thead>
	  <tr>
	    <th width="13%"><strong>C&oacute;d. Int. </strong></th>
		<th width="13%"><strong>C&oacute;digo</strong></th>
		<th width="20%"><strong>Descripci&oacute;n</strong></th>
		<th width="21%"><strong>Grupo</strong></th>
		<th width="14%"><strong>Tipo</strong></th>
		<th width="14%"><strong>Estado</strong></th>
		<th width="3%">&nbsp;</th>
		</tr>
    </thead>
    <tbody>
	  <?php
	  if ($total_rs_buscta > 0) 
	  {
	  do { 
		/**
		* Consulta del detallete de la CUENTA 
		*/
		$rs_recur = $obBD_con1->consulta(sentencias_com(304, $obBD_con1->parametros($row_rs_buscta['Pld_Rec'])), 
		$obBD_conexion->conexion);
		$row_rs_recur = $obBD_con1->registros();	
		/**
		* Consulta del detallete de la CUENTA (OTRO) 
		*/
		$rs_grupo = $obBD_con1->consulta(sentencias_com(304, $obBD_con1->parametros($row_rs_recur['Pld_Rec'])), 
		$obBD_conexion->conexion);
		$row_rs_grupo = $obBD_con1->registros();					  						  
	  ?>
	  <tr>
	    <td align="center"><?php echo $row_rs_buscta['Pld_Cod']; ?></td>
		<td align="left"><?php echo $row_rs_buscta['Pld_Cdc']; ?></td>
		<td align="left"><?Php echo marcar_cadena($ajax_buscod, $row_rs_buscta['Pld_Des'], '#FFFF00', 1);  ?>
		</td>
		<td><div align="center"><?php if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']." <strong>(".$row_rs_grupo['Pld_Des'].")</strong>"; }else{ 
								echo "&nbsp;"; } ?></div></td>
		<td align="center"><div align="center"><?php echo $row_rs_buscta['Pld_Tip']; ?></div></td>
		<td align="center"><div align="center"><?php echo $row_rs_buscta['Pld_Est']; ?></div></td>
		<td align="center">
         <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick=" 
							ind_cta = document.getElementById('Hdd_Fila').value;
							document.getElementById(document.getElementById('Hdd_Pld_Cod').value).value = '<?Php echo $row_rs_buscta['Pld_Cod']; ?>';
							document.getElementById(document.getElementById('Hdd_Pld_Cdc').value).value = '<?Php echo $row_rs_buscta['Pld_Cdc']; ?>';
							document.getElementById(document.getElementById('Hdd_Pld_Des').value).value = '<?Php echo $row_rs_buscta['Pld_Des']; ?>';">
        	<i class=" icon-arrow-right icon-white"></i>
        </button>
        </td>
	  </tr>
	  <?php } while ($row_rs_buscta = $obBD_con1->fetch_assoc($rs_buscta));
	  } else { ?>
		<tr><td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td><?Php echo error_alerta(" No hay resultados que mostrar", 1); ?></td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td width="2%">&nbsp;</td>
		</tr>
	  <?php }?>
      </tbody>
	</table>
<?Php 
	echo barra_estado($total_rs_buscta);
@$obBD_con1->free_result($rs_buscta);
@$obBD_con1->free_result($rs_recur);
@$obBD_con1->free_result($rs_grupo);
exit();
}//if (isset($ajax_$buscod))
?>
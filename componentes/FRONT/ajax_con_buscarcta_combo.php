<?Php
require_once('../../componentes/LOGICA/logica.php');

/* Ajax - Componente para la busqueda de las cuentas del plan de cuentas */

/* Cargado AJAX de los resultados de la búsqueda 
C=combo */
if ($ajax_buscador=='C')
{	
	if ($op_opciones=='d')
	{
		// Cargado de los resultados de la busqueda por descripcion de la cuenta
		$rs_buscta = $obBD_con1->consulta(sentencias_com(11, $obBD_con1->parametros($ajax_buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod)), 
									$obBD_conexion->conexion);
	}
	if ($op_opciones=='c')
	{
		// Cargado de los resultados de la busqueda por codigo de la cuenta
		$rs_buscta = $obBD_con1->consulta(sentencias_com(12, $obBD_con1->parametros($ajax_buscod.'*'.$Ses_Emp_Cod.'*'.$Pla_Cod)), 
									$obBD_conexion->conexion);
	}
		$row_rs_buscta = $obBD_con1->registros();
		$total_rs_buscta = $obBD_con1->numregistros();		
	?>
	<br>
	<table width="100%" height="20" border="1" cellpadding="0" cellspacing="0" class="Fondo">
	  <tr class="Cabecera_ajax">
	    <td width="4%"><strong>C&oacute;d. Int. </strong></td>
		<td width="4%"><strong>C&oacute;digo</strong></td>
		<td><strong>Descripci&oacute;n</strong></td>
		<td><strong>Grupo</strong></td>
		<td><strong>Tipo</strong></td>
		<td><strong>Estado</strong></td>
		<td>&nbsp;</td>
		</tr>
	  <?
	  if ($total_rs_buscta > 0) {
	  do { 
		/* Consulta del detallete de la CUENTA */
		$rs_recur = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_buscta['Pld_Rec'])), 
		$obBD_conexion->conexion);
		$row_rs_recur = $obBD_con1->registros();	
		/* Consulta del detallete de la CUENTA (OTRO) */
		$rs_grupo = $obBD_con1->consulta(sentencias_con(204, $obBD_con1->parametros($row_rs_recur['Pld_Rec'])), 
		$obBD_conexion->conexion);
		$row_rs_grupo = $obBD_con1->registros();	
		
		/* Creacion del Array para luego ser procesado	
		C=cuenta contable
		*/
		
		$ban_cod[]=''.'*'.$row_rs_buscta['Pld_Cod'].'*C';
		$ban_des[]=$row_rs_buscta['Pld_Des'];
		
		// Procesamiento del Array a un formato entendible por Javascript
		$ban_cod='Array(\'' . implode('\', \'', $ban_cod) . '\')';
		$ban_des='Array(\'' . implode('\', \'', $ban_des) . '\')';  		
						  						  
	  ?>
	  <tr class="Cuerpo_ajax">
	    <td align="center"><? echo $row_rs_buscta['Pld_Cod']; ?></td>
		<td align="left"><? echo $row_rs_buscta['Pld_Cdc']; ?></td>
		<td align="left"><? echo $row_rs_buscta['Pld_Des']; ?></td>
		<td><div align="center"><? if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']." <strong>(".$row_rs_grupo['Pld_Des'].")</strong>"; }else{ 
								echo "&nbsp;"; } ?></div></td>
		<td align="center"><div align="center"><? echo $row_rs_buscta['Pld_Tip']; ?></div></td>
		<td align="center"><div align="center"><? echo $row_rs_buscta['Pld_Est']; ?></div></td>
		<td align="center"><img src="../../mascaras/model1/imagenes/ok-s.gif" width="16" height="16" title="Agregar cuenta" style="	
		cursor:pointer" onClick="nueva_fila_cheque_com('Tbl_Cheques', <? echo $ban_cod; ?>,<? echo $ban_des; ?>, 'Com_Fec', 'Val_Pcc'); cal_total_cheques(5, 'nfilas_ch', 'datos_ch')"></td>
	  </tr>
	  <? 
	  	unset($ban_cod);
		unset($ban_des);	  
	  } while ($row_rs_buscta = $obBD_con1->fetch_assoc($rs_buscta));
	  } else { ?>
		<tr><td colspan="8" class="Alertas"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
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
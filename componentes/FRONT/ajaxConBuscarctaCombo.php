<?Php
require_once('../../componentes/LOGICA/logica.php');
/**
* Ajax - Componente para la busqueda de las cuentas del plan de cuentas 
* Fecha de actualización: 20-08-2012
* Desarrollador: Lewis Chimarro
*/
/**
* Cargado AJAX de los resultados de la búsqueda 
* C=combo 
*/
if ($ajax_buscador=='C')
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
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader01">
    <thead>
	  <tr>
	    <th width="12%"><strong>C&oacute;d. Int. </strong></th>
		<th width="12%"><strong>C&oacute;digo</strong></th>
		<th width="20%"><strong>Descripci&oacute;n</strong></th>
		<th width="26%"><strong>Grupo</strong></th>
		<th width="12%"><strong>Tipo</strong></th>
		<th width="12%"><strong>Estado</strong></th>
		<th width="6%">&nbsp;</th>
		</tr>
    </thead>
    <tbody>
	  <?
	  if ($total_rs_buscta > 0) {
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
		
		/**
		* Creacion del Array para luego ser procesado	
		* C=cuenta contable
		*/
		$ban_cod[]=''.'*'.$row_rs_buscta['Pld_Cod'].'*C';
		$ban_des[]=$row_rs_buscta['Pld_Des'];
		/**
		* Procesamiento del Array a un formato entendible por Javascript
		*/
		$ban_cod='Array(\'' . implode('\', \'', $ban_cod) . '\')';
		$ban_des='Array(\'' . implode('\', \'', $ban_des) . '\')';  		
						  						  
	  ?>
	  <tr>
	    <td align="center"><? echo $row_rs_buscta['Pld_Cod']; ?></td>
		<td align="left"><? echo $row_rs_buscta['Pld_Cdc']; ?></td>
		<td align="left"><?Php echo marcar_cadena($ajax_buscod, $row_rs_buscta['Pld_Des'], '#FFFF00', 1);  ?>		
		</td>
		<td><div align="center"><? if ($row_rs_recur['Pld_Des'] != ""){ echo $row_rs_recur['Pld_Des']." <strong>(".$row_rs_grupo['Pld_Des'].")</strong>"; }else{ 
								echo "&nbsp;"; } ?></div></td>
		<td align="center"><div align="center"><? echo $row_rs_buscta['Pld_Tip']; ?></div></td>
		<td align="center"><div align="center"><? echo $row_rs_buscta['Pld_Est']; ?></div></td>
		<td align="center">
        <button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="nueva_fila_cheque_com('Tbl_Cheques', <? echo $ban_cod; ?>,<? echo $ban_des; ?>, 'Com_Fec', 'Val_Pcc'); cal_total_cheques(5, 'nfilas_ch', 'datos_ch')">
        	<i class=" icon-arrow-right icon-white"></i>
        	</button>
        </td>
	  </tr>
	  <? 
	  	unset($ban_cod);
		unset($ban_des);	  
	  } while ($row_rs_buscta = $obBD_con1->fetch_assoc($rs_buscta));
	  } else { ?>
		<tr>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		  <td>&nbsp;</td>
		</tr>
	  <? }?>
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
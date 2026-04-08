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
		
		$rs_buscta = $obBD_con1->getArrayConsulta(1054, trim($txtBusqueda).'*'.$Ses_Suc_Cod, $obBD_conexion);	
	}
	else
	{  
		$rs_buscta = $obBD_con1->getArrayConsulta(1040, trim($txtBusqueda).'*'.$Ses_Suc_Cod, $obBD_conexion);	
	}
	$total_rs_buscta = count($rs_buscta);			
	?>
	<br>
	<table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
	   <thead>
           <tr>
            <th width="14%" align="center"><strong>C&oacute;d. Int.</strong></th>	
            <th width="26%" align="center"><strong>Descripci&oacute;n</strong></th>
            <th width="29%" align="center"><strong>Marca</strong></th>
            <th width="16%" align="center"><strong>Tipo</strong></th>
            <th width="13%" align="center"><strong>Pvp</strong></th>
            <th width="0%" align="center"><strong>Stock</strong></th>
            <th width="2%" align="center">&nbsp;</td>
          </tr>
      </thead>
      <tbody>
	  <?
	  if ($total_rs_buscta > 0) {
		  foreach($rs_buscta as $row_rs_buscta) { 					  
		  ?>
		  <tr <? echo focus_row("resaltar_text", "resaltar_back", "undo_resaltar_text", "Fondo");?> >
			<td align="center"><? echo $row_rs_buscta['Pro_Cod']; ?></td>
			<td align="left"><? echo marcar_cadena($txtBusqueda,$row_rs_buscta['Ite_Lar'].' '.$row_rs_buscta['Pro_Obs'],'#FFFF00', 1); ?></td>
			<td align="left"><? echo $row_rs_buscta['Mar_Des']; ?></td>
			<td align="left"><? echo $row_rs_buscta['Adq_Des']; ?></td>		
			<td align="right"><? echo $row_rs_buscta['Pre_Pvp'];  ?></td>
			<td align="right"><? echo $row_rs_buscta['Stk_Can']; ?></td>
			<td align="center">
			<button type="button" class="btn btn-success btn-mini" title="Elegir" onClick="nueva_fila_ajuste('c_contenido','<? echo $row_rs_buscta['Pro_Cod']; ?>','<? echo $row_rs_buscta['Ite_Lar'].' '.$row_rs_buscta['Pro_Obs']; ?>','<? echo $row_rs_buscta['Pre_Pvp']; ?>','<? echo 400000;?>')">
						<i class=" icon-arrow-right icon-white"></i>
					</button>
				</td>
		  </tr>
		  <? }
	  } else { ?>
		<tr>
			<td height="32%">&nbsp;</td>
			<td height="32%"><?Php echo error_alerta("No hay resultados que mostrar", 1); ?></td>
			<td height="32%">&nbsp;</td>
			<td height="32%">&nbsp;</td>
			<td height="32%">&nbsp;</td>
			<td height="32%">&nbsp;</td>
			<td height="32%">&nbsp;</td>
		</tr>
<? }?>
	</tbody>
</table>
<?Php 
echo barra_estado($total_rs_buscta+0);
exit();
}//if (isset($ajax_$buscod))
?>
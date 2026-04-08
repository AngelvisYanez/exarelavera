<?php
/* Componente para mostrar el detalle de los comprobantes de egreso en base las tablas:
		asientos, comprobantes, det_plan */
/* Consulta el detalle de los comprobantes */
if (isset($com_codigo))
{
	$rs_detalle = $obBD_con1->consulta(sentencias_con(338, $obBD_con1->parametros($com_codigo.'*'.'D'.'*'.'ORDER BY Pld_Cdc')), $obBD_conexion->conexion);
	$row_rs_detalle = $obBD_con1->registros();
	$total_rs_detalle = $obBD_con1->numregistros();				
	?>	
	
    <FIELDSET>
	<LEGEND>
	<label class="Titulos2">Pagos Realizados<?php echo $volver_op; echo $volver_busqueda; ?>  </label>
	</LEGEND>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="LetraNegra">
      <tr>
        <td width="12%" align="right">La cantidad de:</td>
        <td width="88%" colspan="2">&nbsp;<span class="Texto_Reporte" style="text-transform:uppercase">
          <? 
	$v_absoluto=explode(".",$row_rs_detalle['Com_Val']);
	echo num2letras($v_absoluto[0],false,true).', '.$v_absoluto[1].' /100 DOLARES AMERICANOS'; ?>
        </span></td>
      </tr>
      <tr>
        <td align="right">Por concepto:</td>
        <td><? echo $row_rs_detalle['Com_Con']; ?></td>
      </tr>
      <tr>
        <td align="right">Observaci&oacute;n:</td>
        <td><? echo $row_rs_detalle['Com_Obs']; ?></td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="fixedHeader01">	
	<thead>
    <tr>
	  <th width="10%" align="center">C&oacute;digo</th>
	  <th align="center">Descripci&oacute;n</th>
	  <th width="10%" align="center">Debe</th>
	  <th width="10%" align="center">Haber</th>
	</tr>
    </thead>
    <tbody>
	<?Php
	$total=0;
	$total_h=0;
	do{
	?>
	<tr>
	  <td align="left"><? echo $row_rs_detalle['Pld_Cdc']; ?></td>
	  <td>&nbsp;
		  <? 
	if ($row_rs_detalle['Asi_Deh']=='D') { 
	 echo $row_rs_detalle['Pld_Des']; 
	 }else
	 {  echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row_rs_detalle['Pld_Des']; }
	 ?></td>
	  <td align="right"><? if ($row_rs_detalle['Asi_Deh']=='D') { echo formato_numero($row_rs_detalle['Asi_Val'], 2, 4); 
			/* Se uiliza round a 4 decimales para el detalle de cada calculo de las retenciones de renta e iva
			en el reporte compr se usan 3 decimales */
			$total=$total + round($row_rs_detalle['Asi_Val2'],2); } else { echo '&nbsp'; }?></td>
	  <td align="right"><? if ($row_rs_detalle['Asi_Deh']=='H') { echo formato_numero($row_rs_detalle['Asi_Val'], 2, 4); 
   	        /* Se uiliza round a 4 decimales para el detalle de cada calculo de las retenciones de renta e iva 
			en el reporte compr se usan 3 decimales */
			$total_h=$total_h + round($row_rs_detalle['Asi_Val2'],2); } else{ echo '&nbsp'; } ?></td>
	</tr>
	<?Php
	}while($row_rs_detalle= $obBD_con1->fetch_assoc($rs_detalle));
	?>
	<tr>
	  <td colspan="2" align="right">SUMAN:</td>
	  <td align="right"><?php echo number_format($total, 2); ?></td>
	  <td align="right"><?php echo number_format($total_h, 2); ?></td>
	</tr>
    </tbody>
	</table>
    </FIELDSET>
<?php
}//Fin del if (isset($com_codigo))
else
{
		echo error_alerta("<< Error de componente: con_con_detalle_compr.php >> <br>Descripción: No se ha definido la Propiedad: com_codigo<br>
        com_codigo: Variable que contiene el código del comprobante para mostrar el detalle", 2);

}//Fin del else if (isset($com_codigo))

@$obBD_con1->free_result($rs_detalle);
?>
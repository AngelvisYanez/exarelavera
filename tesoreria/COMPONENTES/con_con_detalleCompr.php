<?php
/* Componente para mostrar el detalle de los comprobantes de egreso en base las tablas:
		asientos, comprobantes, det_plan */
/* Consulta el detalle de los comprobantes */
if (isset($com_codigo))
{
	$row_rs_detalle = $obBD_con1->getArrayConsulta(338, $com_codigo.'*'.'D'.'*'.'ORDER BY Pld_Cdc', $obBD_conexion);
	?>	
	<FIELDSET>
    <LEGEND>
        <label class="Titulos2">Detalle del Comprobante</label>
    </LEGEND>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">   
	<tr>
	  <td width="12%" class="Etiqueta1"><strong>La cantidad de:</strong></td>
      <td width="88%">
		&nbsp;
		<?php 
		$detalle = current($row_rs_detalle);
		$v_absoluto=explode(".",$detalle['Com_Val']);
		echo num2letras($v_absoluto[0],false,true).', '.$v_absoluto[1].' /100 DOLARES AMERICANOS'; 
		?>		
      </td>
	</tr>
    <tr>
	  <td class="Etiqueta1"><strong>Por concepto:</strong>&nbsp;</td>
	  <td>&nbsp;<?php echo $detalle['Com_Con']; ?></td>
	</tr>
    <tr>
	  <td class="Etiqueta1"><strong>Observaci&oacute;n:</strong>&nbsp;</td>
	  <td>&nbsp;<?php echo $detalle['Com_Obs']; ?></td>
	</tr>
    </table>
    <br />
    <table width="100%" border="1" cellspacing="0" cellpadding="0" class="fixedHeader01">	
     <thead>
	<tr>
	  <th width="10%" align="center"><strong>C&oacute;digo</strong></th>
	  <th align="center"><strong>Descripci&oacute;n</strong></th>
	  <th width="10%" align="center"><strong>Debe</strong></th>
	  <th width="10%" align="center"><strong>Haber</strong></th>
	</tr>
    </thead>
	<?Php
	$total=0;
	$total_h=0;
	foreach ($row_rs_detalle as $row)
	{
	?>
	<tr>
	  <td align="left"><?php echo $row['Pld_Cdc']; ?></td>
	  <td>&nbsp;
	  <?php 
		if ($row['Asi_Deh']=='D') 
		{ 
	 		echo $row['Pld_Des']; 
		}
		else
		{  
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$row['Pld_Des']; 
		}
	 ?></td>
	  <td align="right"><?php 
	  if ($row['Asi_Deh']=='D') 
	  { 
	  	echo formato_numero($row['Asi_Val'], 2, 4); 
		/* 
		* Se uiliza round a 4 decimales para el detalle de cada calculo de las retenciones de renta e iva en el reporte compr se usan 3 decimales 
		*/
			$total=$total + round($row['Asi_Val2'],2); } else { echo '&nbsp'; }?></td>
	  <td align="right"><?php if ($row['Asi_Deh']=='H') { echo formato_numero($row['Asi_Val'], 2, 4); 
   	        /* Se uiliza round a 4 decimales para el detalle de cada calculo de las retenciones de renta e iva 
			en el reporte compr se usan 3 decimales */
			$total_h=$total_h + round($row['Asi_Val2'],2); } else{ echo '&nbsp'; } ?></td>
	</tr>
	<?Php
	}//Fin del foreach ($row_rs_detalle as $row)
	?>
	<tr>
	  <td colspan="2" align="right">SUMAN:</td>
	  <td align="right"><?php echo formato_numero($total, 2, 4); ?></td>
	  <td align="right"><?php echo formato_numero($total_h, 2, 4); ?></td>
	</tr>
	</table>
        <?php 
//            $row_rs_cabcomp = $obBD_con1->getRowConsulta(366,$com_codigo, $obBD_conexion);
//            if($row_rs_cabcomp['Usu_Cod']!=NULL){
//                $row_rs_usuComp = $obBD_con1->getRowConsulta(365,$row_rs_cabcomp['Usu_Cod'], $obBD_conexion); 
        ?>
        <!--<div style="float: right;"><b>Emitido Por:</b> <?Php //echo $row_rs_usuComp['Prs_Ape']." ".$row_rs_usuComp['Prs_Nom'];?></div>-->
        <?php 
//            }
        ?>
    <br />
    </FIELDSET>
<?php
}//Fin del if (isset($com_codigo))
else
{
		echo error_alerta("<< Error de componente: con_con_detalle_compr.php >> <br>Descripción: No se ha definido la Propiedad: com_codigo<br>
        com_codigo: Variable que contiene el código del comprobante para mostrar el detalle", 2);

}//Fin del else if (isset($com_codigo))
?>
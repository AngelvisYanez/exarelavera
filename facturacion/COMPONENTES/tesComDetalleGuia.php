<?Php
/**
* Componente que muestra el detalle de las compras 
*/
if (isset($com_codigo))
{   
	$rs_usuVendedor = $obBD_con1->getRowConsulta(1271, $com_codigo, $obBD_conexion); 
	$rs_info = $obBD_con1->getRowConsulta(1272, $com_codigo, $obBD_conexion);
	$rs_detalle = $obBD_con1->getArrayConsulta(1273, $com_codigo, $obBD_conexion);	
?>	
<FIELDSET>
	<LEGEND>
    <label class="Titulos2">Detalle de la Compra</label>
    </LEGEND>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td class="Etiqueta1">C&eacute;dula/R.U.C.:</td>
        <td colspan="3" class="LetraNegra">&nbsp;<?php echo $rs_info['Prs_Ced'];?></td>
      </tr>
      <tr>
       <td class="Etiqueta1">Destinatario:</td>
        <td colspan="3" class="LetraNegra">&nbsp;<?php echo $rs_info['Prs_Ape'].' '.$rs_info['Prs_Nom'];?></td>
      </tr>
      <tr>
        <td width="10%" class="Etiqueta1">No. Docto:</td>
        <td width="41%" class="LetraNegra">&nbsp;<?php echo $rs_info['Gui_Num'];?></td>
        <td width="7%" class="Etiqueta1">Usuario:</td>
        <td width="42%" class="LetraNegra">&nbsp;<?php echo $rs_usuVendedor['Prs_Ape'].' '.$rs_usuVendedor['Prs_Nom']; ?></td>
      </tr>        
    </table>

    <hr>   
  <table width="100%" border="0" cellpadding="0" cellspacing="0" class="fixedHeader02">
    <thead>
      <tr height="25">
        <th width="16%" align="center">Cod. Int.</th>
        <th width="14%" align="center">Cantidad</th>
        <th width="70%" align="center">Descripci&oacute;n</th>
      </tr>
    </thead>
    <tbody >
      <?Php
      foreach ($rs_detalle as $row_rs_detalle)
      {		 
      ?>
      <tr height="20">
        <td align="center"><?Php echo $row_rs_detalle['Pro_Cod']; ?></td>
        <td align="center" ><?Php echo $row_rs_detalle['Gui_Can']; ?></td>
        <td align="center"><?php echo $row_rs_detalle['Pro_Cod']; ?></td>
        <td align="center" ><?php echo $row_rs_detalle['Gui_Can']; ?></td>
        <td ><?php echo $row_rs_detalle['Ite_Lar'].' '.$row_rs_detalle['Pro_Obs']; ?></td>
      </tr>
      <?php } ?>      
    </tbody>            
  </table>    
</FIELDSET>
<?php echo barra_estado($rs_detalle);
}//FIn del if (isset($com_codigo))   
?>

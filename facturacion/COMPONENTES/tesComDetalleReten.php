<?Php
/* Componente que muestra el detalle de las compras */
if (isset($ret_codigo))
{  
	$rs_detalle = $obBD_con1->getArrayConsulta(381,$ret_codigo,$obBD_conexion);	
	$total_rs_detalle =count($rs_detalle);			
?>
<FIELDSET>
<LEGEND>
<label class="Titulos2">Detalle de la Retenci&oacute;n</label>
</LEGEND>
    <table width="100%" border="1" cellpadding="0" cellspacing="0" class="fixedHeader01">
     <thead>
      <tr >
        <th width="6%" align="center">Cod. Int.</th>
        <th width="60%" align="center">Descripci&oacute;n</th>
        <th width="13%" align="center">Porcentaje(%)</th>
        <th width="15%" align="rigth">Valor(es) retenido(s) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
      </tr>
      </thead>
      <tbody>
      <?Php
      foreach($rs_detalle as $row_rs_detalle){
      ?>
      <tr>
        <td align="center"><?Php echo $row_rs_detalle['Ret_Int']; ?></td>
        <td><?Php echo $row_rs_detalle['Ren_Con']; ?></td>
        <td align="right"><div align="center"><?Php echo $row_rs_detalle['Ren_Por']; ?></div></td>
        <td align="right"><?Php echo formato_numero((($row_rs_detalle['Ret_Bas']*$row_rs_detalle['Ren_Por'])/100), 2, 1);  ?>&nbsp;&nbsp;</td>
      </tr>
      <?Php }?>
      </tbody>
     </table>
</FIELDSET>     
<?Php	
	echo barra_estado(count($total_rs_detalle));  
}//FIn del if (isset($com_codigo))
else
{
	echo error_alerta("<< Error de componente >> <br>Descripción: No se ha definido la Propiedad: com_codigo<br>
									com_codigo: Variable que contiene el codigo interno de la factura de compra", 2); 							

}//FIn del else if (isset($com_codigo))
?>

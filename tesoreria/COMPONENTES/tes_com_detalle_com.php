<?Php
/* Componente que muestra el detalle de las compras */
if (isset($com_codigo))
{
	$rs_detalle = $obBD_con1->consulta(sentencias_tes(723, $obBD_con1->parametros($com_codigo)), $obBD_conexion->conexion);
	$row_rs_detalle = $obBD_con1->registros();
	$total_rs_detalle = $obBD_con1->numregistros();	
	$Obs = $row_rs_detalle['Cop_Obs'];
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
  <tr class="Etiqueta1">
	<td width="17%" align="center">Cant</td>
	<td width="43%" align="center">Descripci&oacute;n</td>
	<td width="19%" align="center">P. Unitario </td>
	<td width="21%" align="center">Importe</td>
  </tr>
  <?Php
  do{
  ?>
  <tr>
	<td align="center"><?Php echo $row_rs_detalle['Cop_Can']; ?></td>
	<td><?Php echo $row_rs_detalle['Cop_Pro']; ?></td>
	<td align="right"><?Php echo formato_numero($row_rs_detalle['Cop_Pru'], 2, 4); ?></td>
	<td align="right"><?Php echo formato_numero($row_rs_detalle['Cop_Imp'], 2, 4); ?></td>
  </tr>
  <?Php  }while($row_rs_detalle = $obBD_con1->registros());  ?>
  <tr>
 </table>
 <table width="100%" border="0" cellpadding="0" cellspacing="0" class="LetraNegra">
	<td width="7%" class="Etiqueta1">Observaci&oacute;n:</td>
    <td width="93%" colspan="3"><?Php echo "&nbsp;".$Obs; ?></td>
  </tr>
</table>
<?Php
	 @$obBD_con1->free_result($rs_detalle);
}//FIn del if (isset($com_codigo))
else
{
	echo error_alerta("<< Error de componente >> <br>Descripción: No se ha definido la Propiedad: com_codigo<br>
									com_codigo: Variable que contiene el codigo interno de la factura de compra", 2); 							

}//FIn del else if (isset($com_codigo))
?>

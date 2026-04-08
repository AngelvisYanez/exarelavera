<?Php
/**
* Componente que muestra el detalle de las compras 
*/
if (isset($com_codigo))
{
	$rs_detalle = $obBD_con1->getArrayConsulta(723, $com_codigo, $obBD_conexion);
	$row = current($rs_detalle);
	$Obs = $row['Cop_Obs'];
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
	<td width="17%" align="center">Cant</td>
	<td width="43%" align="center">Descripci&oacute;n</td>
	<td width="19%" align="center">P. Unitario </td>
	<td width="21%" align="center">Importe</td>
  </tr>
  <?Php
  foreach ($rs_detalle as $row_rs_detalle)
  {
  ?>
  <tr>
	<td align="center"><?Php echo $row_rs_detalle['Cop_Can']; ?></td>
	<td><?Php echo $row_rs_detalle['Cop_Pro']; ?></td>
	<td align="right"><?Php echo formato_numero($row_rs_detalle['Cop_Pru'], 2, 4); ?></td>
	<td align="right"><?Php echo formato_numero($row_rs_detalle['Cop_Imp'], 2, 4); ?></td>
  </tr>
  <?Php  
  } ?>
  <tr>
 </table>
 <table width="100%" border="0" cellpadding="0" cellspacing="0">
	<td width="7%">Observaci&oacute;n:</td>
    <td width="93%" colspan="3"><?Php echo "&nbsp;".$Obs; ?></td>
  </tr>
</table>
<?Php
}//FIn del if (isset($com_codigo))
else
{
	echo error_alerta("<< Error de componente >> <br>Descripción: No se ha definido la Propiedad: com_codigo<br>
									com_codigo: Variable que contiene el codigo interno de la factura de compra", 2); 							

}//FIn del else if (isset($com_codigo))
?>

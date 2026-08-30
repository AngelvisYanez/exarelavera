<?Php
if (!function_exists('mensaje_requerido')) return;
/* Componente para la busqueda depago a proveedores segun el apellidos o numero de comprobante */
?>
<FIELDSET>
<LEGEND>
	<label class="Titulos2">Buscar por:</label>
</LEGEND>

<?Php
/*Muestra el mensaje de requerido*/
mensaje_requerido(); 
?>
<table width="600" border="0">
  <tr>
	<td width="133"><input name="op_opciones" type="radio" value="d"   
	onClick="setfocus(this.form.txt_busqueda)" style="cursor:pointer"  checked>
	<span class="Etiqueta1">Apellidos</span></td>
	<td width="194">
	<input type="radio" name="op_opciones"  
	style="cursor:pointer" value="r" onClick="document.getElementById('cmb_mes').disabled=true; setfocus(form1.txt_busqueda)">
	<span class="Etiqueta1">No. Comprobante </span></td>
    <td width="174">
	<?php $Com_Fecha="AND MONTH(Com_Fec)"; ?>
	<?php include("../../componentes/FRONT/com_con_meses.php"); ?>
	</td>
  </tr>
</table>
<table width="777" border="0" cellpadding="0" cellspacing="0">
  <tr>
	<td width="76" class="BarraBusqueda"><div align="right"><span class="Etiquetas"><span class=
		  "Asterisco">*</span> </span>B&uacute;squeda:</div></td>
	<td width="334" class="BarraBusqueda">
<input name="txt_busqueda" type="text" id="txt_busqueda" style="text-transform:uppercase" value="" size="45"></td>
	<td width="20" align="center">&nbsp;</td>
	<td width="347" align="center"><div align="left">
		<input name="bnt_buscar" type="button" class="Boton_Buscar" title="Buscar" id="bnt_buscar"
		
onClick="validar_requeridos(this.form, 'txt_busqueda', 0)"	value= "Buscar" >
		<input name="hdd_buscar" type="hidden" id="hdd_buscar" value="insertar">
	</div></td>
  </tr>
</table>
</FIELDSET>

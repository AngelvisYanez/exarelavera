<?Php
/* Componente para la busqueda de personas:estudiante, cliente, proveedor, etc */
?>
<FIELDSET>
<LEGEND>
	<label class="Titulos2">Buscar por:</label>
</LEGEND>

<?Php
/*Muestra el mensaje de requerido*/
mensaje_requerido(); 
?>
<table width="515" border="0">
  <tr>
	<td width="164"><input name="op_opciones" type="radio" value="d"   
	onClick="setfocus(this.form.txt_busqueda)" style="cursor:pointer"  checked>
	<span class="Etiqueta1">Apellidos</span></td>
	<td width="341">
	<input type="radio" name="op_opciones"  
	onClick="setfocus(this.form.txt_busqueda)" style="cursor:pointer" value="r">
	<span class="Etiqueta1">C&eacute;dula/R.U.C.</span></td>
  </tr>
</table>
<table width="572" height="35" border="0" cellpadding="0" cellspacing="0">
  <tr>
	<td width="453" class="BarraBusqueda"><span class="Asterisco">*</span> B&uacute;squeda:
	  <input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="45">&nbsp;&nbsp;
	  
	  
	  <input name="hdd_buscar" type="hidden" id="hdd_buscar" value="insertar">	  </td>
	<td width="119" align="center"><button type="button" class="btn btn-success fileinput-button" title="Buscar" onClick="validar_requeridos(this.form, 'txt_busqueda', 0)">
	    <i class="icon-search icon-white"></i>
	    <span>&nbsp;&nbsp;Buscar&nbsp;&nbsp;</span>
	    </button></td>
	</tr>
</table>
</FIELDSET>
<?Php
/**
* Componente para la busqueda de personal "Docentes" 
* Fecha de actualización 2014-Abril-19
*/
?>
<div id="div_marco">
<FIELDSET>
<LEGEND>
	<label class="Titulos2">Buscar Personal por:</label>
</LEGEND>
<table width="341" border="0" cellpadding="0" cellspacing="0">
  <tr>
	<td width="164"><input name="op_opciones" id="op_opciones" type="radio" value="d" style="cursor:pointer"  checked onClick="setfocus(this.form.txt_busqueda); document.getElementById('opcion').value = this.value">
	<span class="Etiqueta1">Apellidos</span></td>
	<td width="167">
	<input type="radio" name="op_opciones" id="op_opciones" style="cursor:pointer" value="r" onClick="setfocus(this.form.txt_busqueda); document.getElementById('opcion').value = this.value">
	<span class="Etiqueta1">C&eacute;dula/R.U.C.</span></td>
  </tr>
</table>
<table width="596" border="0" cellpadding="0" cellspacing="0">
  <tr>
	<td width="76" class="BarraBusqueda"><div align="right"><span class="Etiquetas"><span class=
		  "Asterisco">*</span> </span>B&uacute;squeda:</div></td>
	<td width="334" class="BarraBusqueda">
<input name="txt_busqueda" type="text" id="txt_busqueda" value="" size="45"></td>
	<td width="20" align="center">&nbsp;</td>
	<td width="166" align="center"><div align="left">	    
<button type="button" class="btn btn-success fileinput-button" name="bnt_buscar" id="bnt_buscar" title="Buscar Personal" onClick="if (document.getElementById('txt_busqueda').value != ''){ ajax_datos('<?Php echo $_SERVER['PHP_SELF']; ?>?ajax_perDocente=1&ref='+ document.getElementById('txt_busqueda').value +'&opc=' + document.getElementById('opcion').value,'div_busDocente') }
    else { alert('El dato de este campo es requerido') }" value="">
							<i class="icon-search icon-white"></i>
							<span>Buscar</span>
			</button>      
		<input name="hdd_buscar" type="hidden" id="hdd_buscar" value="insertar">
        <input name="opcion" type="hidden" id="opcion" value="d">
	</div></td>
  </tr>
</table>
<div id="div_busDocente"></div>
</FIELDSET>
</div>
<script language="javascript">
//ShowHide('div_marco');
</script>
<?php
/**
* Descripcion: Componente que muestra los codigos ICE.
* Fecha de actualizacion: 2015-17-28
* Desarrollador: Jose Cumbicos
*/
	
?>
<table width="100%" border="0" cellspacing="0" cellpadding="0" id="Tbl_Ice">
  <tr>
    <td>
	<FIELDSET>
      <LEGEND>
        <label class="Titulos2">B&uacute;squeda de Rubros Ice: </label>
      </LEGEND>
      <table width="542" height="27" border="0">
        <tr>
          <td width="181" height="23" class="LetraNegra"><input name="op_opciones" type="radio" value="d" checked="checked" onclick="document.getElementById('Hdd_Op_ice').value = this.value; setfocus(this.form.busrtaIce)" style="cursor:pointer" />
            <strong>Descripci&oacute;n</strong></td>
          <td width="351" class="LetraNegra"><input type="radio" name="op_opciones" value="p" onclick="document.getElementById('Hdd_Op_ice').value = this.value; setfocus(this.form.busrtaIce)" style="cursor:pointer" />
          	<input name="Hdd_Op_ice" id="Hdd_Op_ice" type="hidden" value="d" />
            <strong>% de Ice</strong> </td>
          </tr>
      </table>
      <table width="98%" height="36" border="0" cellpadding="0" cellspacing="0">
        <tbody id="tbusqueda">
          <tr class="BarraBusqueda">
            <td width="84" height="28" ><div align="right"><strong>Busqueda: </strong></div></td>
            <td width="391" ><input name="busrtaIce" type="text" id="busrtaIce" size="50" maxlength="50" onKeyUp="" onKeyPress="if (trim(document.getElementById('busrta').value) != ''){ enter_ajax('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_renta_ice_buscar&op='+ document.getElementById('Hdd_Op_ice').value +'&ref='+ this.value,'busqueda_rubro_ice')}">            
			<input name="hdd_Ice_Por" id="hdd_Ice_Por" type="hidden" value="" />
            <input name="hdd_Ice_Sri" id="hdd_Ice_Sri" type="hidden" value="" />
            <input name="hdd_Ice_Int" id="hdd_Ice_Int" type="hidden" value="" />
			</td>
            <td width="111" align="center">
        <button type="button" name="btn_buscarcta" id="btn_buscarcta" class="btn btn-success fileinput-button" title="Buscar rubros Ice" 
        onClick="ajax_datos('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_renta_ice_buscar&op='+ document.getElementById('Hdd_Op_ice').value +'&ref='+ document.getElementById('busrtaIce').value,'busqueda_rubro_ice')">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
           </button>  
</td></tr></tbody></table>
  <table width="100%" border="0">
    <tr>
      <td><br /><div id="busqueda_rubro_ice"></div></td>
    </tr>
  </table>
</FIELDSET></td></tr></table>
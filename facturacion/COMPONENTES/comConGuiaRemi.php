<?Php 
/**
* Componente que nos permite buscar Destinatario-Transporte 
*/
?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
	<FIELDSET>
      <LEGEND>
        <label class="Titulos2">B&uacute;squeda de <?php echo $titulo;?>: </label>
      </LEGEND>
      <table width="342" height="27" border="0">
        <tr>
          <td width="147" height="23" class="LetraNegra"><input name="op_opciones" type="radio" value="d" checked="checked" onclick="document.getElementById('Hdd_Op').value = this.value; setfocus(this.form.busrta)" style="cursor:pointer" />
            <strong>Apellidos</strong></td>
          <td width="179" class="LetraNegra"><input type="radio" name="op_opciones" value="p" onclick="document.getElementById('Hdd_Op').value = this.value; setfocus(this.form.busrta)" 
		  style="cursor:pointer" />
            <strong>C&eacute;dula/R.U.C.</strong> </td>         
      </table>
      <table width="70%" height="36" border="0" cellpadding="0" cellspacing="0">
        <tbody id="tbusqueda">
          <tr class="BarraBusqueda">
            <td width="84" height="28" ><div align="right">Busqueda:</div></td>
            <td width="320" >
            <input name="bus" type="text" id="bus" size="34" onKeyUp="parametro_injection(this)" onKeyPress="if (trim(document.getElementById('bus').value) != ''){ enter_ajax('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_buscar_det=1&txt_busqueda='+ this.value +'&titulo=<?php echo $titulo;?>&Emp_Cod=<?php echo $Emp_Cod;?>&op=<?php echo $op; ?>&opcion='+document.getElementById('Hdd_Op').value ,'datos_busqueda')  }">			
			<input name="Hdd_Ren_Por" id="Hdd_Ren_Por" type="hidden"/>
			<input name="Hdd_Op" id="Hdd_Op" type="hidden" value="d"/>
			</td>
            <td width="161" align="center">
        <button type="button" name="btn_buscarcta" id="btn_buscarcta" class="btn btn-success fileinput-button" title="Buscar" 
        onClick="if(trim(document.getElementById('bus').value) != ''){ ajax_datos('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_buscar_det=1&txt_busqueda='+ document.getElementById('bus').value +'&Emp_Cod=<?php echo $Emp_Cod;?>&titulo=<?php echo $titulo;?>&op=<?php echo $op; ?>&opcion='+ document.getElementById('Hdd_Op').value ,'datos_busqueda');}">
           <i class="icon-search icon-white"></i>
           <span>Buscar</span>
           </button>  
</td></tr></tbody></table>
  <table width="100%" border="0">
    <tr>
      <td><br /><div id="datos_busqueda"></div></td>
    </tr>
  </table>
</FIELDSET></td></tr></table>
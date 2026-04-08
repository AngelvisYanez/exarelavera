<?Php 
/* Alias: [--]
   Descripción: Componente que permite buscar por descripción los procentajes de retención.
   Fecha de actualización: 07-08-2009
   Desarrollador: Freddy Jumbo
*/
if(isset($Com_Pec_Cod)){ /*inicio if(isset($Adq)){ */
	if(isset($Pla_Cod)){ /*inicio if(isset($Tipo_Rta)){ */
?>
<table width="600" border="0" cellspacing="0" cellpadding="0" id="Tbl_Rentas">
  <tr>
    <td>
	<FIELDSET class="Busqueda_ajax">
      <LEGEND></LEGEND>
      <table width="542" height="27" border="0">
        <tr>
          <td width="147" height="23" class="LetraNegra"><input name="op_opciones" type="radio" value="d" checked="CHECKED" onclick="document.getElementById('Hdd_Op').value = this.value; setfocus(this.form.busrta)" style="cursor:pointer" />
            <strong>Descripci&oacute;n</strong></td>
          <td width="179" class="LetraNegra"><input type="radio" name="op_opciones" value="p" onclick="document.getElementById('Hdd_Op').value = this.value; setfocus(this.form.busrta)" 
		  style="cursor:pointer" />
            <strong>% de Retenci&oacute;n</strong> </td>
          <td width="202" class="LetraNegra"><strong>
            <input type="checkbox" name="check_renta_iva" id="check_renta_iva" value="checkbox" style="cursor:pointer" />
          Aplicar a todos </strong></td>
        </tr>
      </table>
      <table width="91%" height="36" border="0" cellpadding="0" cellspacing="0">
        <tbody id="tbusqueda">
          <tr class="Busqueda_contenido_ajax">
            <td width="101" height="28"><div align="right"><strong>Busqueda: </strong></div></td>
            <td width="550"><input name="busrta" type="text" id="busrta" size="50" maxlength="50" style="text-transform:uppercase" onKeyUp="parametro_injection(this)" onKeyPress="if (trim(document.getElementById('busrta').value) != ''){ enter_ajax('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_renta_iva_buscar='+document.getElementById('busrta').value+'&Adq='+document.getElementById('datos['+  document.getElementById('Hdd_Txt_Ide').value +','+ 16 +']').value+'&Tipo_Rta='+document.getElementById('Hdd_Tip_Rta').value+'&Pec_Cod=<?Php echo $Com_Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&ajax_op='+document.getElementById('Hdd_Op').value,'busqueda_renta')  }">
			<?Php  /* Defino el parámetro para la consulta de renta o I.V.A. */ ?>
			<input name="Hdd_Tip_Rta" id="Hdd_Tip_Rta" type="hidden"  >
			<input name="Hdd_Txt_Ide" id="Hdd_Txt_Ide" type="hidden">
			<input name="Hdd_Ren_Con" id="Hdd_Ren_Con" type="hidden" />
			<input name="Hdd_Ren_Ide" id="Hdd_Ren_Ide" type="hidden" />
			<input name="Hdd_Ren_Por" id="Hdd_Ren_Por" type="hidden" />
			<input name="Hdd_Op" id="Hdd_Op" type="hidden" value="d" />
			</td>
            <td width="92" align="center"><input name="btn_buscarcta" type="button" class="Boton_Buscar" id="btn_buscarcta" onClick="if(trim(document.getElementById('busrta').value) != ''){ ajax_datos('<?Php echo $_SERVER['PHP_SELF'];?>?ajax_renta_iva_buscar='+document.getElementById('busrta').value+'&Adq='+document.getElementById('datos['+  document.getElementById('Hdd_Txt_Ide').value +','+ 16 +']').value+'&Tipo_Rta='+document.getElementById('Hdd_Tip_Rta').value+'&Pec_Cod=<?Php echo $Com_Pec_Cod; ?>&Pla_Cod=<?Php echo $Pla_Cod; ?>&ajax_op='+document.getElementById('Hdd_Op').value,'busqueda_renta') }else{
			validar_requeridos(this.form,'busrta',0);
			}" 
		value="Buscar">
</td></tr></tbody></table><div id="busqueda_renta"></div></FIELDSET></td></tr></table>
<?Php
	}else{  /*else inicio if(isset($Pla_Cod)){ */
		echo error_alerta("<< Error de componente: tes_com_bus_renta_iva.php >> <br>Descripción: No se ha definido la Propiedad: Pla_Cod<br>
        Pla_Cod: Variable que contiene el nombre del texto que posse el código del plan de cuentas", 2);
		}/*fin inicio if(isset($Pla_Cod)){ */
	}else{ /*else inicio if(isset($Com_Pec_Cod)){ */
		echo error_alerta("<< Error de componente: tes_com_bus_renta_iva.php >> <br>Descripción: No se ha definido la Propiedad: Com_Pec_Cod<br>
        Com_Pec_Cod: Variable que contiene el nombre del texto que posse el codigo del comprobante", 2);
	}/*fin inicio if(isset($Com_Pec_Cod)){ */
 ?>
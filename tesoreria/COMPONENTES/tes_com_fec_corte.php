<FIELDSET>
<LEGEND>
<label class="Titulos2">Fecha de Corte </label>
</LEGEND>

<table width="80%" border="0">
<tr>
<td width="12%" class="Etiqueta1"><span class="Asterisco">* </span>Hasta: </td>
<td colspan="2">
  <input name="txt_fec_fin" type="text" id="txt_fec_fin" value="<?php echo date("Y-m-d"); ?>" size="10" onkeyup="mascara(this,'-',patron, true);" onBlur="validar_fecha2(this)" />
        <img src="../../imagenes/calendario.jpg" alt="Ver calendario" style="cursor:pointer" name="calendariof" width= "25" height="17" border="0" align="absmiddle" id="calendariof" />
		<script type="text/javascript">
		    Calendar.setup({
        	inputField     :    "txt_fec_fin",     // id of the input field
		    ifFormat       :    "%Y-%m-%d",      // format of the input field
	        button         :    "calendariof",  // trigger for the calendar (button ID)
	        align          :    "Bl",           // alignment (defaults to "Bl")
    	    singleClick    :    true,
			step           :    1
    		});
		</script></td>
<td width="67%">
<input name="btn_buscar1" type="submit" title="Aceptar" class="Boton_Buscar" id="btn_buscar1" onclick="" value="Buscar" />
<input name="hdd_save" type="hidden" id="hdd_save">
	
</td>
</tr>
</table>
</FIELDSET>
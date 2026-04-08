<?Php /* Componente para la busqueda por estado Activas o Anuladas */ ?>
<FIELDSET>
<LEGEND>
<label class="Etiqueta1">Estado:</label>
</LEGEND>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td width="25%" class="Etiqueta1" ><div align="left">
	<?Php 
	if (!isset($optest))
	{
		$optest = 'A';
	}
	?>
        <input name="optest" type="radio" value="A" <?Php if ($optest == 'A'){ echo "checked='checked'"; } ?> style="cursor:pointer" > 
    Activas</div></td>
     <td width="75%" class="Etiqueta1"><div align="left">
        <input name="optest" type="radio" value="I" <?Php if ($optest == 'I'){ echo "checked='checked'"; } ?> style="cursor:pointer" >
    Anuladas   </div></td>
  </tr>
</table>
</LEGEND>
</FIELDSET>
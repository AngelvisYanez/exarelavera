<?php 
/**
* Componente de las opciones de presentacion 
*/
?>
<FIELDSET>  
	<LEGEND>
		<label class="Etiqueta1">Opciones de presentaci&oacute;n</label>
	</LEGEND>	
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
	  <tr>
		<td width="14%" class="Etiqueta1">Ordenar:</td>
		<td width="23%"><select name="ordenar" id="ordenar">
		  <option <?php if ($ordenar == 'ORDER BY Com_Fec ASC'){ echo "selected";} ?> value="ORDER BY Com_Fec ASC">Ascendente A->Z</option>
		  <option <?php if ($ordenar == 'ORDER BY Com_Fec DESC'){ echo "selected";} ?> value="ORDER BY Com_Fec DESC">Descendente Z->A</option>
	    </select></td>
		<td width="45%" align="right">Modo generaci&oacute;n:</td>
	    <td width="18%" align="right"><select name="Com_Aut" id="Com_Aut">
          <option <?php if ($Com_Aut == 't'){ echo "selected";} ?> value="t">&lt;&lt; TODOS &gt;&gt;</option>
          <option <?php if ($Com_Aut == 'm'){ echo "selected";} ?> value="m">Manual</option>
          <option <?php if ($Com_Aut == 'a'){ echo "selected";} ?> value="a">Autom&aacute;ticos</option>
        </select></td>
	  </tr>
</table>														
</FIELDSET>